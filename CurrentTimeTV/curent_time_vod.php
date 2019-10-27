<?php

require_once 'lib/vod/abstract_vod.php';
require_once 'lib/vod/movie.php';


class CurentTimeVod extends AbstractVod
{
    public function __construct()
    {
        parent::__construct(
            false,
            false,
            true);
    }

    public function try_load_movie($movie_id, &$plugin_cookies)
    {
        $movie = CurentTimeConfig::parse_movie_page($movie_id, $plugin_cookies);

        if ($movie==true)
			$this->set_cached_movie($movie);
    }

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

    //Parse link on real videostream

    public function get_vod_stream_url($playback_url, &$plugin_cookies)
    {
        $videostreams = array();
        $doc = HD::http_get_document(CurentTimeConfig::SITE_URL . $playback_url);
        $desired_qual = (HD::get_item('qual_arhiv') !='') ? HD::get_item('qual_arhiv') : CurentTimeConfig::QUAL_ARHIV;

        preg_match('/^<video poster=.*? src="(.*?)".*?data-sources="\[(.*?)\]"/ims', $doc, $result);

        if ($desired_qual !== 'hls') {

            $json_str = str_replace('},{', '}|{', $result[2]);
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

            if (array_key_exists($desired_qual, $videostreams)) {
                $url = $videostreams[$desired_qual]['Src'];
            } else {
                $max_videostream = array_pop($videostreams);
                $url = $max_videostream['Src'];
                hd_print('Качество (' . $desired_qual . ') не найдено. Найдено максимально возможное качство (' . $max_videostream["DataInfo"] . ')');
            }

	    if ((preg_match('|\.mp4|i', $url)) && (!preg_match('|\.m3u8|i', $url))) {
		$url = str_replace('http://', 'http://mp4://', $url);
                $url = str_replace('https://', 'https://mp4://', $url);
            }

        } else {
            $url = "http://127.0.0.1/cgi-bin/plugins/CurrentTimeTV/current.sh?" . $result[1];
        }

        hd_print("Play ==>> $url");

        return $url;
    }
}

?>
