<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/tv/tv_channel_list_screen.php';

class CurrentTimeTvChannelListScreen extends TvChannelListScreen
{
    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        if (CurrentTimeConfig::USE_M3U_FILE && !isset($media_url->group_id))
            $media_url->group_id = $this->tv->get_all_channel_group_id();

        return parent::get_all_folder_items($media_url, $plugin_cookies);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
