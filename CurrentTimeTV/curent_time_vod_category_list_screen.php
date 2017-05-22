<?php

///////////////////////////////////////////////////////////////////////////

class CurentTimeVodCategoryListScreen extends AbstractPreloadedRegularScreen
implements UserInputHandler
{
    const ID = 'vod_category_list';

    public static function get_media_url_str($category_id)
    {
        return MediaURL::encode(
            array
            (
                'screen_id'     => self::ID,
                'category_id'   => $category_id,
            ));
    }

    ///////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct(
            self::ID, $this->get_folder_views());

		 UserInputHandlerRegistry::get_instance()->
            register_handler($this);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        $actions = array();
		$add_action = UserInputHandlerRegistry::create_action($this, 'setup');
        $add_action['caption'] = 'Настройки';
        $actions[GUI_EVENT_KEY_C_YELLOW] = $add_action;
		$actions[GUI_EVENT_KEY_SETUP] = $add_action;
		$actions[GUI_EVENT_KEY_ENTER] = ActionFactory::open_folder();
        return  $actions;
    }

	public function get_handler_id()
    { return self::ID; }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        if ($user_input->control_id == 'setup')
        {
            if (!isset($user_input->selected_media_url))
                return null;
			return ActionFactory::open_folder('setup', "Настройки");
        }
        return null;
    }

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $items = array();

        if ($media_url->category_id == 'tv_shows'){
            $tvshows = CurentTimeConfig::get_tv_shows();
            foreach ($tvshows as $key => $value) {
                $url = CurentTimeConfig::SITE_URL . $value['url_path'];

                $img = 'plugin_file://logo/thumb/' . $key . '_thumb.png';
                $name = $value['caption'];

                $items[] = array
                    (
                        PluginRegularFolderItem::media_url => VodSeriesListScreen::get_media_url_str($url . '|||' . $key),
                        PluginRegularFolderItem::caption =>$name,
                        PluginRegularFolderItem::view_item_params => array
                        (
                            ViewItemParams::icon_path => $img,
                            ViewItemParams::item_detailed_icon_path => $img
                        )
                    );
            }
            return $items;
        }
    }
    ///////////////////////////////////////////////////////////////////////

    private function get_folder_views()
    {
        return CurentTimeConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS();
    }
}

///////////////////////////////////////////////////////////////////////////
?>
