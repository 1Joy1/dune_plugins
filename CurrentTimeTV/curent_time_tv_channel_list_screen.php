<?php


require_once 'lib/tv/tv_channel_list_screen.php';

class CurentTimeTvChannelListScreen extends TvChannelListScreen
{
	protected function GetScreenID() { return 'curent_time_tv_channel_list'; }

	public function __construct(Tv $tv, $folder_views)
    {
        parent::__construct($tv, $folder_views);

        $this->tv = $tv;

        UserInputHandlerRegistry::get_instance()->register_handler($this);
    }

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        $actions = array();

        $actions[GUI_EVENT_KEY_ENTER]   = UserInputHandlerRegistry::create_action($this, 'add_actions');
        $actions[GUI_EVENT_KEY_PLAY]    = UserInputHandlerRegistry::create_action($this, 'add_actions');
        $info_view = UserInputHandlerRegistry::create_action($this, 'info_view');
        $info_view['caption'] = 'Изменения в v' . HD::ver();
        $setup_view = ActionFactory::open_folder(CurentTimeSetupScreen::get_media_url_str(), "%tr%setup");
        $setup_view['caption'] = "%tr%setup";
        $actions[GUI_EVENT_KEY_C_YELLOW] = $setup_view;
        $actions[GUI_EVENT_KEY_SETUP] = $setup_view;
        $actions[GUI_EVENT_KEY_B_GREEN] = $info_view;

        if ($this->tv->is_favorites_supported())
        {
            $add_favorite_action = UserInputHandlerRegistry::create_action($this, 'add_favorite');
            $add_favorite_action['caption'] = '%tr%add_favorite';
            $actions[GUI_EVENT_KEY_D_BLUE] = $add_favorite_action;
        }
        $actions[GUI_EVENT_KEY_INFO] = UserInputHandlerRegistry::create_action($this, 'info');

        return $actions;
    }

}
?>