<?php
require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';
require_once 'lib/tv/tv_group_list_screen.php';
require_once 'lib/tv/tv_favorites_screen.php';
require_once 'lib/vod/vod_list_screen.php';
require_once 'lib/vod/vod_movie_screen.php';
require_once 'lib/vod/vod_series_list_screen.php';
require_once 'lib/vod/vod_favorites_screen.php';
require_once 'curent_time_config.php';
require_once 'curent_time_m3u_tv.php';
require_once 'curent_time_tv_channel_list_screen.php';
require_once 'curent_time_setup_screen.php';
require_once 'curent_time_vod.php';
require_once 'curent_time_vod_category_list_screen.php';
require_once 'curent_time_file_screen.php';
require_once 'curent_time_sleep_timer.php';
require_once 'logger.php';

class CurentTimeTvPlugin extends DefaultDunePlugin
{
    public function __construct()
    {
        $this->tv	= new CurentTimeM3uTv();
		$this->vod	= new CurentTimeVod();
        $this->add_screen(new CurentTimeTvChannelListScreen($this->tv, CurentTimeConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));
		$this->add_screen(new TvFavoritesScreen($this->tv, CurentTimeConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));
		$this->add_screen(new CurentTimeSetupScreen());
		$this->add_screen(new CurentTimeFileSystemScreen());
		$this->add_screen(new CurentTimeVodCategoryListScreen());
		$this->add_screen(new VodSeriesListScreen($this->vod));
    }
}


?>
