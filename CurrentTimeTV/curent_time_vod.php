<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/vod/abstract_vod.php';
require_once 'lib/vod/movie.php';

///////////////////////////////////////////////////////////////////////////

class CurentTimeVod extends AbstractVod
{
    public function __construct()
    {
        parent::__construct(
            false,
            false,
            true);
    }

    ///////////////////////////////////////////////////////////////////////

    public function try_load_movie($movie_id, &$plugin_cookies)
    {
        $pagi = (HD::get_item('pagi') !='') ? HD::get_item('pagi') : CurentTimeConfig::PAGI;

        if ($movie_id == 'broadcasts' || $movie_id == 'all_videos' || $movie_id == 'daily_shoots' ||
            $movie_id == 'reportages' || $movie_id == 'interviews' || $movie_id == 'guests') {

            $main_menu = CurentTimeConfig::get_menu();

            ini_set('pcre.backtrack_limit', '5000000');
            $doc = HD::http_get_document(CurentTimeConfig::SITE_URL . $main_menu["$movie_id"]['url'] . '?p=' . $pagi);


            $movie = new Movie($movie_id);
            $movie->set_data($main_menu["$movie_id"]['caption'], "plugin_file://logo/$movie_id.png");

            $patern = '/<ul class="row" id="items">(.+?)<\/ul>/ims';

            if ($may_error = preg_match($patern, $doc, $result)) {

                $fragment_page = $result[0];
                $patern = '/<a href="(.+?)".+?title="(.+?)">.+?<img data-src="" src="(.+?)".+?<span class="date" >(.+?)<\/span>.+?<\/li>/ims';
                if ($may_error = preg_match_all($patern, $fragment_page, $result)) {

                    foreach ($result[1] as $key => $value) {

                        $img = $result[3][$key];

                        $str = html_entity_decode($result[2][$key], null , 'UTF-8');

                        $str = html_entity_decode($str);

                        $caption = $str . '    |' . $result[4][$key] . '|';
                        $caption = preg_replace('/\"/', "'", $caption);
                        $caption = preg_replace("/(?:'(.*?)')/", '«$1»', $caption);

                        $url = $result[1][$key];

                        $movie->add_series_data($url, $caption, $url, false);
                    }
                } else {
                    hd_print("Не смог выдернуть фрагмент страницы. Второй preg_match вернул: =>> $may_error");
                }
            } else {
                hd_print("Не смог выдернуть фрагмент страницы. Первый preg_match вернул: =>> $may_error");
            }
        }


        if (preg_match('/currenttime/', $movie_id)) {

            $arr_movie_id = explode('|||', $movie_id);

            $movie_url = $arr_movie_id[0];
            $movie_group_key = $arr_movie_id[1];
            $tv_shows = CurentTimeConfig::get_tv_shows();

            ini_set('pcre.backtrack_limit', '5000000');
            $doc = HD::http_get_document($movie_url . '?p=' . $pagi);

            $movie = new Movie($movie_id);
            $movie->set_data($tv_shows[$movie_group_key]['caption'],
                             'plugin_file://logo/thumb/' . $movie_group_key . '_thumb.png');

            $patern = '/<ul class="row" id="items">(.+?)<\/ul>/ims';

            if ($may_error = preg_match($patern, $doc, $result)) {
                $fragment_page = $result[0];
                $patern = '/<a href="(.+?)".+?title="(.+?)">.+?<img data-src="" src="(.+?)".+?<span class="date" >(.+?)<\/span>.+?<\/li>/ims';
                if ($may_error = preg_match_all($patern, $fragment_page, $result)) {

                    foreach ($result[1] as $key => $value) {

                        $img = $result[3][$key];

                        if ($movie_group_key != 'nveurope') {
                            $str = html_entity_decode($result[2][$key], null , 'UTF-8');
                            $str = html_entity_decode($str);
                        } else {
                            $str = $tv_shows[$movie_group_key]['caption'];
                        }


                        $caption = $str . '    |' . $result[4][$key] . '|';
                        $caption = preg_replace('/\"/', "'", $caption);
                        $caption = preg_replace("/(?:'(.*?)')/", '«$1»', $caption);

                        $url = $result[1][$key];

                        $movie->add_series_data($url, $caption, $url, true);
                    }
                } else {
                    hd_print("Не смог выдернуть фрагмент страницы. Второй preg_match вернул: =>> $may_error");
                }
            } else {
                hd_print("Не смог выдернуть фрагмент страницы. Первый preg_match вернул: =>> $may_error");
            }
        }


        if ($movie_id=='favorites'){
            $favorites = HD::get_items('favorites');
            $movie = new Movie($movie_id);
            $movie->set_data('Избранные передачи', "plugin_file://logo/$movie_id.png");
            if (is_array($favorites)){
                foreach ($favorites as $k => $v)
                    if(is_array($v))
                        $movie->add_series_data( $v['series_id'], $v['name'], $v['series_id'], true, $k);
            }//else return false;
        }
		//$movie = CurentTimeConfig::parse_movie_page($movie_id, $plugin_cookies);
		if ($movie==true)
			$this->set_cached_movie($movie);
    }

    ///////////////////////////////////////////////////////////////////////
    // Favorites.

    protected function load_favorites(&$plugin_cookies)
    {
        $fav_movie_ids = $this->get_fav_movie_ids_from_cookies($plugin_cookies);

        foreach ($fav_movie_ids as $movie_id)
        {
            if ($this->has_cached_short_movie($movie_id))
                continue;

            $this->ensure_movie_loaded($movie_id, $plugin_cookies);
        }

        $this->set_fav_movie_ids($fav_movie_ids);

        hd_print('The ' . count($fav_movie_ids) . ' favorite movies loaded.');
    }

    protected function do_save_favorite_movies(&$fav_movie_ids, &$plugin_cookies)
    {
        $this->set_fav_movie_ids_to_cookies($plugin_cookies, $fav_movie_ids);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_fav_movie_ids_from_cookies(&$plugin_cookies)
    {
        if (!isset($plugin_cookies->{'favorite_movies'}))
            return array();

        $arr = preg_split('/,/', $plugin_cookies->{'favorite_movies'});

        $ids = array();
        foreach ($arr as $id)
        {
            if (preg_match('/\S/', $id))
                $ids[] = $id;
        }
        return $ids;
    }

    public function set_fav_movie_ids_to_cookies(&$plugin_cookies, &$ids)
    {
        $plugin_cookies->{'favorite_movies'} = join(',', $ids);
    }

    ///////////////////////////////////////////////////////////////////////
    public function get_vod_stream_url($playback_url, &$plugin_cookies)
    {
        return self::get_real_url_video_stream($playback_url);
    }

    ///////////////////////////////////////////////////////////////////////
    // Folder views.

    public function get_vod_list_folder_views()
    {
        return CurentTimeConfig::GET_VOD_MOVIE_LIST_FOLDER_VIEWS();
    }

    public static function get_real_url_video_stream($playback_url) {
        $videostreams = array();
        $doc = HD::http_get_document(CurentTimeConfig::SITE_URL . $playback_url);
        preg_match('/video poster=".+?data-sources="\[(.+?)\]"/', $doc, $result);

        $json_str = str_replace('},{', '}|{', $result[1]);
        $json_str = str_replace('&quot;', '"', $json_str);
        $all_videos = explode('|', $json_str);

        foreach ($all_videos as $key => $value) {
            $ell = json_decode($value, 1);
            if (json_last_error() == 0) {
                $videostreams[$ell["DataInfo"]] = $ell;
            } else {
                hd_print('JSON parse error of method get_url_video_stream');
               return false;
            }
        }
        $desired_qual = (HD::get_item('qual_arhiv') !='') ? HD::get_item('qual_arhiv') : CurentTimeConfig::QUAL_ARHIV;

        if (array_key_exists($desired_qual, $videostreams)) {
            $url = $videostreams[$desired_qual]['Src'];
        } else {
            $max_videostream = array_pop($videostreams);
            $url = $max_videostream['Src'];
            hd_print('Качество (' . $desired_qual . ') не найдено. Найдено максимально возможное качство (' . $max_videostream["DataInfo"] . ')');
        }

        return preg_replace("/^https/i","http", $url);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
