<?php

require_once 'lib/hashed_array.php';
require_once 'lib/tv/abstract_tv.php';
require_once 'lib/tv/default_epg_item.php';
require_once 'curent_time_channel.php';



class CurentTimeM3uTv extends AbstractTv implements UserInputHandler
{
    const ID = 'smart_movie';

    private $arcUrl = null;
	public function __construct()
    {
        parent::__construct(
            AbstractTv::MODE_CHANNELS_N_TO_M,
            CurentTimeConfig::TV_FAVORITES_SUPPORTED,
			false);
    }

    public function get_fav_icon_url()
    {
        return CurentTimeConfig::FAV_CHANNEL_GROUP_ICON_PATH;
    }
	public function get_tv_stream_url($playback_url, &$plugin_cookies)
    {
		return $playback_url;
	}
    public function ensure_channels_loaded(&$plugin_cookies)
    {
        //if (!isset($this->channels))
            $this->load_channels($plugin_cookies);
    }

    protected function load_channels(&$plugin_cookies)
    {
        $this->channels = new HashedArray();
        $this->groups = new HashedArray();

		$default_group = new DefaultGroup(
		$id = '__all_channels',
		$title = CurentTimeConfig::ALL_CHANNEL_GROUP_CAPTION,
		$icon_url = CurentTimeConfig::ALL_CHANNEL_GROUP_ICON_PATH
		);

        $live_streams = CurentTimeConfig::get_live_streams();
        $qual_live = (HD::get_item('qual_live') !='') ? HD::get_item('qual_live') : CurentTimeConfig::QUAL_LIVE;
        $i=0;
        if ($qual_live == 'all'){
            foreach ($live_streams as $live_stream) {
                $num = $i+1;
                $channel =
                    new CurentTimeChannel(
                            $live_stream['id'],
                            $live_stream['caption'],
                            '',
                            CurentTimeConfig::LIVE_CHANEL_ICO,
                            $live_stream['media_url'],
                            $num,
                            $past_epg_days = 6,
                            $future_epg_days = 1,
                            0
                        );

                $channel->add_group($default_group);
                $this->channels->put($channel);
                $default_group->add_channel($channel);
                $i++;
            }
        } else {
            $num = $i+1;
            $channel =
                new CurentTimeChannel(
                        $live_streams[$qual_live]['id'],
                        $live_streams[$qual_live]['caption'],
                        '',
                        CurentTimeConfig::LIVE_CHANEL_ICO,
                        $live_streams[$qual_live]['media_url'],
                        $num,
                        $past_epg_days = 6,
                        $future_epg_days = 1,
                        0
                    );

            $channel->add_group($default_group);
            $this->channels->put($channel);
            $default_group->add_channel($channel);
            $i++;
        }

		$this->groups->put($default_group);
    }

	public function get_tv_playback_url($channel_id, $archive_ts, $protect_code, &$plugin_cookies)
    {
		$url = $this->get_channel($channel_id)->get_streaming_url();
		if (intval($archive_ts) > 0)
			$url = $this->arcUrl[$archive_ts];
        return $url;
    }

    public function get_day_epg_iterator($channel_id, $day_start_ts, &$plugin_cookies)
    {
        $channel_id = 1185;
        $epg_shift = isset($plugin_cookies->epg_shift) ? $plugin_cookies->epg_shift : 0;
        if (preg_match("|[a-zA-Z]|", $channel_id))
            return array();
        $epg_result = array();
        $epg = HD::get_vsetv_epg($channel_id, $day_start_ts);
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

	public function get_group($group_id=null)
    {
        $g = $this->groups->get($this->get_all_channel_group_id());

        if (is_null($g))
            throw new Exception("Unknown group: $group_id");

        return $g;
    }

    public function get_handler_id()
    { return self::ID; }

    protected function get_movie_actions()
    {
        UserInputHandlerRegistry::get_instance()->register_handler($this);
        $actions[GUI_EVENT_KEY_C_YELLOW] = UserInputHandlerRegistry::create_action($this, 'sleep');
        $actions[GUI_EVENT_TIMER] = UserInputHandlerRegistry::create_action($this, 'timer');
        return $actions;
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        $attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);

        if ($user_input->control_id == 'sleep') {
            $defs = array();

            $sleep_time = CurentTimeSleepTimer::sleep_timer_init();
            $sleep_time_hour = 0;
            $sleep_time_ops = CurentTimeSleepTimer::get_sleep_timer_ops();

            ControlFactory::add_combobox($defs, $this, null, 'sleep_time_hour', 'Выключение через:',
                $sleep_time_hour, $sleep_time_ops, 0, $need_confirm = false, $need_apply = false
            );
            ControlFactory::add_button_close ($defs, $this, $add_params=null,'cleer_sleep',
                "Сброс Sleep таймера:", 'Очистить таймеры', 0);

            ControlFactory::add_close_dialog_and_apply_button($defs, $this, null, 'sleep_time', 'Применить', 300, $params=null);


            $attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
            $attrs['actions'] = null;
            return ActionFactory::show_dialog("Sleep таймер $sleep_time", $defs, true,0, $attrs);
        }


        if ($user_input->control_id == 'sleep_time') {
            return CurentTimeSleepTimer::sleep_timer_set($user_input);
        }


        if ($user_input->control_id == 'sleep_warn') {
            $defs = array();

            $sleep_time = CurentTimeSleepTimer::reset_unix_time_tmp_file();

            $sleep_time_hour = 0;
            $sleep_time_ops = CurentTimeSleepTimer::get_sleep_timer_ops();

            ControlFactory::add_label($defs, 'Выключение через:', "1-2 мин.");

            ControlFactory::add_combobox($defs, $this, null, 'sleep_time_hour', 'Выключение через:', $sleep_time_hour, $sleep_time_ops, 0, $need_confirm = false, $need_apply = false);
            //$do_time_hour = UserInputHandlerRegistry::create_action($this, 'sleep_time_hour');

            ControlFactory::add_button_close ($defs, $this, $add_params=null,'cleer_sleep', "Сброс Sleep таймера:", 'Очистить таймеры', 0);

            $sleep_time_hour = UserInputHandlerRegistry::create_action($this, 'sleep_time');
            ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, 'sleep_time', 'Применить', 250, $sleep_time_hour);

            $attrs['timer'] = ActionFactory::timer(100000);
            $attrs['actions'] = array(GUI_EVENT_TIMER => ActionFactory::close_dialog());
            $attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
            return  ActionFactory::show_dialog("Sleep таймер $sleep_time", $defs, true, 0, $attrs);
        }



        if ($user_input->control_id == 'cleer_sleep') {
            if (!CurentTimeSleepTimer::clear_sleep_timer()) {
                return ActionFactory::show_title_dialog_gl("Таймер выключениня не был задан!");
            }
            return UserInputHandlerRegistry::create_action($this, 'sleep', $params=null);
        }



        if ($user_input->control_id == 'timer') {
            $actions = $this->get_movie_actions();
            $post_action = null;
            $sleep_unix_time = HD::get_item_tmp('unix_time');
            if ($sleep_unix_time > 0){
                $unix_time = time();
                $r = $sleep_unix_time - $unix_time;
                if (($r>120)||($r<0)) {
                    $post_action = null;
                } else {
                    $post_action = UserInputHandlerRegistry::create_action($this, 'sleep_warn');
                }
            }
            return  ActionFactory::change_behaviour($actions, 5000, $post_action);
        }
    }



}
?>