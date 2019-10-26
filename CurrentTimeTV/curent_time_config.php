<?php

class CurentTimeConfig
{
    const TV_FAVORITES_SUPPORTED   		= false;
    const ALL_CHANNEL_GROUP_CAPTION     = '%tr%live';
    const ALL_CHANNEL_GROUP_ICON_PATH   = 'plugin_file://logo/live.png';
    const FAV_CHANNEL_GROUP_CAPTION     = '%tr%tv_fav';
    const FAV_CHANNEL_GROUP_ICON_PATH   = 'plugin_file://logo/fav.png';
    const USE_M3U_FILE 					= true;
    const CHANNEL_SORT_FUNC_CB 			= 'CurentTimeConfig::sort_channels_cb';

    const SITE_URL                      = 'http://www.currenttime.tv';
    const LIVE_CHANEL_ICO               = 'plugin_file://logo/1185.png';

    const QUAL_LIVE                     = 'all';
    const QUAL_ARHIV                    = 'hls';
    const PAGI                          = '5';



    public static function sort_channels_cb($a, $b)
    {
        return strnatcasecmp($a->get_number(), $b->get_number());
    }

    public static function get_live_streams() {
        return array(
            '1080p' =>
            array('caption' => 'Прямой эфир 1080p',
                  'media_url' => 'http://rfe-lh.akamaihd.net/i/rfe_tvmc5@383630/index_1080_av-p.m3u8',
                  'id' => '1',
            ),
            '720p' =>
            array('caption' => 'Прямой эфир 720p',
                  'media_url' => 'http://rfe-lh.akamaihd.net/i/rfe_tvmc5@383630/index_0720_av-p.m3u8',
                  'id' => '2',
            ),
            '540p' =>
            array('caption' => 'Прямой эфир 540p',
                  'media_url' => 'http://rfe-lh.akamaihd.net/i/rfe_tvmc5@383630/index_0540_av-p.m3u8',
                  'id' => '3',
            ),
        );
    }


	public static function get_menu() {
		return array(
            'tv_shows'    => array (
                'caption' => 'Программы по рубрикам',
                'mUrl' => CurentTimeVodCategoryListScreen::get_media_url_str('tv_shows'),
                'url' => ''
            ),
            'broadcasts'  => array (
                'caption' => 'Последние эфиры',
                'mUrl' => VodSeriesListScreen::get_media_url_str('broadcasts'),
                'url' => '/z/17317'
            ),
            'all_videos'   => array (
                'caption' => 'Все видео',
                'mUrl' => VodSeriesListScreen::get_media_url_str('all_videos'),
                'url' => '/z/17192'
            ),
            'daily_shoots' => array (
                'caption' => 'Кадры дня',
                'mUrl' => VodSeriesListScreen::get_media_url_str('daily_shoots'),
                'url' => '/z/17226'
            ),
            'reportages' => array (
                'caption' => 'Репортажи',
                'mUrl' => VodSeriesListScreen::get_media_url_str('reportages'),
                'url' => '/z/17318'
            ),
            'interviews' => array (
                'caption' => 'Интервью',
                'mUrl' => VodSeriesListScreen::get_media_url_str('interviews'),
                'url' => '/z/17319'
            ),
            'favorites' => array (
                'caption' => 'Избранные передачи',
                'mUrl' => VodSeriesListScreen::get_media_url_str('favorites'),
            ),
		);
    }


    public static function get_tv_shows() {
        return array(
                    'olevski' =>    array('url_path' => '/Olevsky/episodes',
                                          'caption' => 'Час Тимура Олевского'
                                          ),
                    'nveurope' =>   array('url_path' => '/z/18657/episodes',
                                          'caption' => 'Итоги дня'
                                          ),
                    'nvasia' =>     array('url_path' => '/Asia/episodes',
                                          'caption' => 'Азия'
                                          ),
                    'nvamerica' =>  array('url_path' => '/Amerika/episodes',
                                          'caption' => 'Америка'
                                          ),
                    'oba' =>        array('url_path' => '/smotrivoba/episodes',
                                          'caption' => 'Смотри в Оба'
                                          ),
                    'itogi' =>      array('url_path' => '/itogi/episodes',
                                          'caption' => 'Итоги'
                                          ),
                    'week' =>       array('url_path' => '/z/17498',
                                          'caption' => 'Неделя'
                                          ),
                    'baltia' =>     array('url_path' => '/Baltic_show/episodes',
                                          'caption' => 'Балтия'
                                          ),
                    'bisplan' =>    array('url_path' => '/business/episodes',
                                          'caption' => 'Бизнес-План'
                                          ),
                    'unknownrus' => array('url_path' => '/unknownrussia/episodes',
                                          'caption' => 'Неизвестная РОССИЯ'
                                          ),
                    'guests' =>     array('url_path' => '/z/20330',
                                          'caption' => '«Ждём в гости» с Зурабом Двали'
                                          ),
                    'shemi' =>     array('url_path' => '/scheme/Episodes',
                                          'caption' => '«Схемы»'
                                          ),
                    'vukraine' =>     array('url_path' => '/z/21041/episodes',
                                          'caption' => '«#Вукраине»'
                                          ),
                );
    }

    public static function parse_movie_page($movie_id, $plugin_cookies) {
        $pagi = (HD::get_item('pagi') !='') ? HD::get_item('pagi') : CurentTimeConfig::PAGI;

        if ($movie_id == 'broadcasts' || $movie_id == 'all_videos' || $movie_id == 'daily_shoots' ||
            $movie_id == 'reportages' || $movie_id == 'interviews' || $movie_id == 'guests') {

            $main_menu = self::get_menu();

            $movie = new Movie($movie_id);
            $movie->set_data($main_menu["$movie_id"]['caption'], "plugin_file://logo/$movie_id.png");

            ini_set('pcre.backtrack_limit', '5000000');

            for ($i=0; $i<$pagi; $i++) {
                $doc = HD::http_get_document(self::SITE_URL . $main_menu["$movie_id"]['url'] . ($i ? '?p=' . $i : ''));

                $patern = '/<ul class="row" id="items">(.+?)<\/ul>/ims';

                if ($may_error = preg_match($patern, $doc, $result)) {

                    $fragment_page = $result[0];
                    $patern = '/<a href="(.*?)".*?title="(.*?)">.*?<img data-src="(.*?)".*?<span class="date" >(.*?)<\/span>.*?<\/li>/ims';
                    if ($may_error = preg_match_all($patern, $fragment_page, $result)) {

                        foreach ($result[1] as $key => $value) {

                            $img = $result[3][$key];

                            $str = html_entity_decode($result[2][$key], null, 'UTF-8');

                            $str = html_entity_decode($str);

                            $caption = $str . '    |' . $result[4][$key] . '|';
                            $caption = preg_replace('/\"/', "'", $caption);
                            $caption = preg_replace("/(?:'(.*?)')/", '«$1»', $caption);

                            $url = $result[1][$key];

                            $movie->add_series_data($url, $caption, $url, false);
                        }
                    } else {
                        hd_print("Не найденны эллементы «li» Второй preg_match вернул: =>> $may_error");
                    }
                } else {
                    hd_print('Не найдена часть страницы «ul class="row" id="items"». Первый preg_match вернул: =>>' . $may_error);
                }
            }
        }


        if (preg_match('/currenttime/', $movie_id)) {

            $arr_movie_id = explode('|||', $movie_id);

            $movie_url = $arr_movie_id[0];
            $movie_group_key = $arr_movie_id[1];
            $tv_shows = self::get_tv_shows();

            $movie = new Movie($movie_id);
            $movie->set_data($tv_shows[$movie_group_key]['caption'],
                'plugin_file://logo/thumb/' . $movie_group_key . '_thumb.png');


            ini_set('pcre.backtrack_limit', '5000000');

            for ($i=0; $i<$pagi; $i++) {
                $doc = HD::http_get_document($movie_url . ($i ? '?p=' . $i : ''));

                $patern = '/<ul class="row" id="items">(.+?)<\/ul>/ims';

                if ($may_error = preg_match($patern, $doc, $result)) {
                    $fragment_page = $result[0];
                    $patern = '/<a href="(.*?)".*?title="(.*?)">.*?<img data-src="(.*?)".*?<span class="date" >(.*?)<\/span>.*?<\/li>/ims';

                    if ($may_error = preg_match_all($patern, $fragment_page, $result)) {

                        foreach ($result[1] as $key => $value) {

                            $img = $result[3][$key];

                            if ($movie_group_key != 'nveurope') {
                                $str = html_entity_decode($result[2][$key], null, 'UTF-8');
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
                        hd_print('Не найденны эллементы «li» Второй preg_match вернул: =>>' . $may_error);
                    }
                } else {
                    hd_print('Не найдена часть страницы «ul class="row" id="items"». Первый preg_match вернул: =>>' . $may_error);
                }
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

        return $movie;
    }

	public static function background() {
        $bg = (HD::get_item('bg') !='') ? HD::get_item('bg') : 'yes';
        return ($bg == 'yes') ? 'plugin_file://logo/backgraund.jpg' : 'gui_skin://images/bg.jpg';
	}

	public static function get_data_path() {
		static $link = null;
		if (is_null($link))
			{
				$link = DuneSystem::$properties['data_dir_path'];
						if (file_exists($link . '/data_dir_path')){
							$link = smbtree::get_folder_info ('data_dir_path');
						}
			}
		return $link;
	}

    public static function GET_TV_CHANNEL_LIST_FOLDER_VIEWS() {
    	return array(

			array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 10,
                    ViewParams::paint_details => true,
					ViewParams::item_detailed_info_auto_line_break => true,
					ViewParams::background_path => self::background(),
					ViewParams::optimize_full_screen_background => true,
					ViewParams::background_order => 'before_all',
                    //ViewParams::sandwich_icon_upscale_enabled => true,
                    //ViewParams::sandwich_icon_keep_aspect_ratio => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
    		    ViewItemParams::item_paint_icon => true,
    		    ViewItemParams::item_layout => HALIGN_LEFT,
    		    ViewItemParams::icon_valign => VALIGN_CENTER,
    		    ViewItemParams::icon_dx => 10,
    		    ViewItemParams::icon_dy => -5,
				ViewItemParams::icon_width => 95,
				ViewItemParams::icon_height => 55,
                ViewItemParams::icon_keep_aspect_ratio => true,
    		    ViewItemParams::item_caption_font_size => FONT_SIZE_NORMAL,
    		    ViewItemParams::item_caption_width => 1060,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
            array
            (
                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 4,
					ViewParams::paint_details => true,
					// ViewParams::paint_path_box => false,
					// ViewParams::paint_scrollbar => false,
					// ViewParams::orientation => HORIZONTAL,
					// ViewParams::cycle_mode_enabled => true,
					// ViewParams::paint_icon_badge_box => true,
					// ViewParams::icon_selection_box_width => 320,
					// ViewParams::icon_selection_box_height => 110,
					// ViewParams::item_detailed_info_auto_line_break => true,
                    // ViewParams::paint_icon_selection_box => false,
					ViewParams::background_path => self::background(),
					ViewParams::optimize_full_screen_background => true,
					ViewParams::background_order => 'before_all',
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 0,
                    ViewItemParams::icon_dy => 10,
					ViewItemParams::icon_width => 250,
					ViewItemParams::icon_height => 135,
					//ViewItemParams::icon_sel_scale_factor  => 1.3,
                    ViewItemParams::item_paint_caption => true,
                    ViewItemParams::item_caption_dy => 15,
                    ViewItemParams::icon_keep_aspect_ratio => true,
                    ViewItemParams::item_caption_font_size => FONT_SIZE_SMALL,
                    ViewItemParams::icon_path => 'gui_skin://small_icons/movie.aai'
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (
					ViewItemParams::icon_path => 'plugin_file://icons/template.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                    ViewItemParams::item_paint_caption_within_icon => false,
                    ViewItemParams::item_caption_within_icon_color => 'white',
				),
            )
        );
    }

    public static function GET_VOD_CATEGORY_LIST_FOLDER_VIEWS() {
        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 4,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => 'gui_skin://special_icons/sandwich_base.aai',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),

            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => 'gui_skin://special_icons/sandwich_base.aai',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
        );
    }
}

?>
