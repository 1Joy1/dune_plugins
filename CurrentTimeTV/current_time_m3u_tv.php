<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/hashed_array.php';
require_once 'lib/tv/abstract_tv.php';
require_once 'lib/tv/default_epg_item.php';

require_once 'current_time_channel.php';
require_once 'lib/user_input_handler_registry.php';

///////////////////////////////////////////////////////////////////////////

class CurrentTimeM3uTv extends AbstractTv
{
    public function __construct()
    {
        parent::__construct(
            AbstractTv::MODE_CHANNELS_N_TO_M,
            CurrentTimeConfig::TV_FAVORITES_SUPPORTED,
            false);
    }

    public function get_fav_icon_url()
    {
        return CurrentTimeConfig::FAV_CHANNEL_GROUP_ICON_PATH;
    }

    public function get_tv_stream_url($playback_url, &$plugin_cookies)
    {
	    $url = $playback_url;
	return $url;
    }


    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////

    private static function get_icon_path($channel_id)
    {
	   $channel_id = ($channel_id == CurrentTimeConfig::CURRENT_CHANNEL_ID) ? $channel_id : 0;

	return sprintf(CurrentTimeConfig::M3U_ICON_FILE_URL_FORMAT, $channel_id);
    }

    private static function get_future_epg_days($channel_id)
    {
	   $days = ($channel_id == CurrentTimeConfig::CURRENT_CHANNEL_ID) ? 1 : 0;
	   return $days;
    }

    ///////////////////////////////////////////////////////////////////////

    protected function load_channels(&$plugin_cookies)
    {
        $this->channels = new HashedArray();
        $this->groups = new HashedArray();

        /*if ($this->is_favorites_supported())
        {
            $this->groups->put(
                new FavoritesGroup(
                    $this,
                    '__favorites',
                    CurrentTimeConfig::FAV_CHANNEL_GROUP_CAPTION,
                    CurrentTimeConfig::FAV_CHANNEL_GROUP_ICON_PATH));
        }*/

        $all_channels_group =
            new AllChannelsGroup(
                $this,
                CurrentTimeConfig::LIVE_CHANNEL_GROUP_CAPTION,
                CurrentTimeConfig::LIVE_CHANNEL_GROUP_ICON_PATH);

        $this->groups->put($all_channels_group);

	    $out_lines = "";
	    $channels_id_parsed = array();
	    $m3u_lines = array();



        if (!($m3u_lines = file(dirname(__FILE__).'/default.php', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
		throw_m3u_error();

        $id = 0;

        for ($i = 0; $i < count($m3u_lines); ++$i)
        {
            if (preg_match('/^#EXTINF:[^,]+,(.+)$/', $m3u_lines[$i], $matches) != 1)
                continue;

            $caption = $matches[1];

			//hd_print("caption--> $caption");
            if ($i + 1 >= count($m3u_lines))
                break;

            $media_url = null;

            for (++$i; $i < count($m3u_lines); ++$i)
            {
                if (preg_match('/^udp:\/\//', strtolower($m3u_lines[$i])) == 1)
                {
                    $media_url = $m3u_lines[$i];
                    break;
                }
            	else if (preg_match('/^http:\/\//', strtolower($m3u_lines[$i])) == 1)
                {
                    $media_url = $m3u_lines[$i];
                    break;
                }

	        }

            if (is_null($media_url))
                break;

            /*
            $id_key = md5(strtolower(str_replace(array("\r", "\n", "\"", " "), '', $caption)));
            hd_print("id_key--> $id_key");
            $id = array_key_exists($id_key,$channels_id_parsed) ? $channels_id_parsed[$id_key] : CurrentTimeConfig::CURRENT_CHANNEL_ID + $i;
            */

            $channel =
                new CurrentTimeChannel(
                    $id++,
                    $caption,
                    self::get_icon_path(CurrentTimeConfig::CURRENT_CHANNEL_ID),
                    $media_url,
                    -1,
                    0,
                    self::get_future_epg_days(CurrentTimeConfig::CURRENT_CHANNEL_ID));

            $this->channels->put($channel);
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    public function get_day_epg_iterator($channel_id, $day_start_ts, &$plugin_cookies)
    {
        $epg_shift = isset($plugin_cookies->epg_shift) ? $plugin_cookies->epg_shift : 0;
        if (preg_match("|[a-zA-Z]|", $channel_id))
            return array();
        $epg_result = array();
        $epg = HD::get_vsetv_epg(CurrentTimeConfig::CURRENT_CHANNEL_ID, $day_start_ts);
        foreach ($epg as $time => $value) {
            $epg_result[] =
                    new DefaultEpgItem(
                        strval($value["name"]),
                        strval($value["desc"]),
                        intval($time + $epg_shift),
                        intval(-1));
        }
        return
            new EpgIterator(
                    $epg_result,
                    $day_start_ts,
                    $day_start_ts + 100400);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
