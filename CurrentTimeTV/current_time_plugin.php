<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';

require_once 'lib/tv/tv_group_list_screen.php';
require_once 'lib/tv/tv_favorites_screen.php';

require_once 'current_time_config.php';
require_once 'current_time_m3u_tv.php';
require_once 'current_time_setup_screen.php';
require_once 'current_time_tv_channel_list_screen.php';

///////////////////////////////////////////////////////////////////////////

class CurrentTimePlugin extends DefaultDunePlugin
{
    public function __construct()
    {
        $this->tv = new CurrentTimeM3uTv();

        $this->add_screen(new CurrentTimeTvChannelListScreen($this->tv,
                CurrentTimeConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));
        $this->add_screen(new TvFavoritesScreen($this->tv,
                CurrentTimeConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));

        $this->add_screen(new TvGroupListScreen($this->tv,
            CurrentTimeConfig::GET_TV_GROUP_LIST_FOLDER_VIEWS()));


        $this->add_screen(new CurrentTimeSetupScreen());
    }
}

///////////////////////////////////////////////////////////////////////////
?>
