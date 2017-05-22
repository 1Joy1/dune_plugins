<?php

require_once 'lib/vod/vod.php';
require_once 'lib/abstract_preloaded_regular_screen.php';

class VodSeriesListScreen extends AbstractPreloadedRegularScreen
    implements UserInputHandler
{
    const ID = 'vod_series';

    public static function get_media_url_str($movie_id)
    {
        return MediaURL::encode(
            array(
                'screen_id' => self::ID,
                'movie_id' => $movie_id));
    }


	public function get_handler_id()
    { return self::ID; }

    private $vod;
	private $select_url = array();
    public function __construct(Vod $vod)
    {
        $this->vod = $vod;

        parent::__construct(self::ID, $this->get_folder_views());
		UserInputHandlerRegistry::get_instance()->
            register_handler($this);
    }


    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        $actions = array();
		$actions[GUI_EVENT_KEY_SETUP] = ActionFactory::open_folder(CurentTimeSetupScreen::get_media_url_str(), "%tr%setup");
		$actions[GUI_EVENT_KEY_ENTER] = ActionFactory::vod_play();
		$actions[GUI_EVENT_KEY_PLAY] = ActionFactory::vod_play();
		if ($media_url->movie_id != 'favorites'){
			$add_action = UserInputHandlerRegistry::create_action($this, 'favorites_tr');
			$add_action['caption'] = 'Добавить в Избранные видео';
			$actions[GUI_EVENT_KEY_D_BLUE] = $add_action;
		}else{
			$add_action = UserInputHandlerRegistry::create_action($this, 'del_item');
			$add_action['caption'] = 'Удалить';
			$actions[GUI_EVENT_KEY_D_BLUE] = $add_action;
		}
		$actions[GUI_EVENT_KEY_SELECT] = UserInputHandlerRegistry::create_action($this, 'select');
		return $actions;
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
		if ($user_input->control_id === 'cleer_folder')
		{
			if (!isset($user_input->parent_media_url))
				return null;
			$parent_media_url = MediaURL::decode($user_input->parent_media_url);
			$sel_ndx = $user_input->sel_ndx;
				if ($sel_ndx < 0)
					$sel_ndx = 0;
			$range = $this->get_folder_range($parent_media_url, 0, $plugin_cookies);
			return ActionFactory::update_regular_folder($range, true, $sel_ndx);
		}
		if ($user_input->control_id === 'cleer_folder_plus')
		{
			if (!isset($user_input->parent_media_url))
				return null;
			$parent_media_url = MediaURL::decode($user_input->parent_media_url);
			$sel_ndx = $user_input->sel_ndx+1;
				if ($sel_ndx < 0)
					$sel_ndx = 0;
			$range = $this->get_folder_range($parent_media_url, 0, $plugin_cookies);
			return ActionFactory::update_regular_folder($range, true, $sel_ndx);
		}
		if ($user_input->control_id == 'select')
        {
            if (!isset($user_input->selected_media_url))
                return null;
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$links = $this->select_url;
			if (isset($links[$media_url->series_id]))
				unset ($links[$media_url->series_id]);
			else{
				if (isset($media_url->nmbr))
					$links[$media_url->series_id] = $media_url->nmbr;
				else
					$links[$media_url->series_id] = $media_url->name;
			}

			$this->select_url = $links;
			$perform_new_action = UserInputHandlerRegistry::create_action($this, 'cleer_folder_plus');
			return ActionFactory::invalidate_folders(array('vod_series'), $perform_new_action);
		}
		if ($user_input->control_id == 'favorites_tr')
		{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$favorites = HD::get_items('favorites');
			if (count($this->select_url)>0){
				foreach ($this->select_url as $k => $v)
					array_unshift($favorites, array (
						'series_id'	=>	$k,
						'name'		=>	$v
					));
			}else
				array_unshift($favorites, array (
					'series_id'	=>	$media_url->series_id,
					'name'		=>	$media_url->name
				));
			HD::save_items('favorites', $favorites);
			$this->vod->clear_movie_cache();
			$action = ActionFactory::show_title_dialog_gl($media_url->name . " добавлен!");
			return ActionFactory::invalidate_folders(array(self::get_media_url_str('favorites')),$action);
		}
		if ($user_input->control_id == 'del_item')
        {
            if (!isset($user_input->selected_media_url))
                return null;
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$favorites = HD::get_items('favorites');
			if (count($this->select_url)>0){
				foreach ($this->select_url as $k => $v)
					unset ($favorites[$v]);
			}else
				unset ($favorites[$media_url->nmbr]);
			$favorites = array_values($favorites);
			$favorites = array_diff($favorites, array(''));
			HD::save_items('favorites', $favorites);
			$this->vod->clear_movie_cache();
			return ActionFactory::invalidate_folders(array(self::get_media_url_str('favorites')));
        }
		return null;
	}

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $this->vod->folder_entered($media_url, $plugin_cookies);
        $movie = $this->vod->get_loaded_movie($media_url->movie_id, $plugin_cookies);
        if ($movie === null)
            return array();
        $items = array();
        foreach ($movie->series_list as $series)
        {
            $color = 15;
			$mUrl = array(
				'screen_id'	=> self::ID,
				'movie_id'	=> $movie->id,
				'series_id'	=> $series->id,
				'name'		=> $series->name,
            );
			if (isset ($series->nmbr))
				$mUrl['nmbr'] = $series->nmbr;
			if (isset($this->select_url[$series->playback_url]))
				$color = 18;
			$items[] = array
            (
                PluginRegularFolderItem::media_url => MediaURL::encode($mUrl),
                PluginRegularFolderItem::caption => $series->name,
                PluginRegularFolderItem::view_item_params => array
                (
                    ViewItemParams::icon_path 			=> 'gui_skin://small_icons/video_file.aai',
					ViewItemParams::item_caption_color	=> $color,
                ),
            );
        }

        return $items;
    }

    private function get_folder_views()
    {

        return array(
            array
            (
                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 12,
					ViewParams::background_path => CurentTimeConfig::background(),
					ViewParams::optimize_full_screen_background => true,
					ViewParams::background_order => 'before_all',
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 10,
                    ViewItemParams::icon_dy => -5,
                    ViewItemParams::item_caption_dx => 60,
                    ViewItemParams::icon_path => 'gui_skin://small_icons/audio_file.aai'
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
        );
    }

    public function get_archive(MediaURL $media_url)
    {
        return $this->vod->get_archive($media_url);
    }
}


?>
