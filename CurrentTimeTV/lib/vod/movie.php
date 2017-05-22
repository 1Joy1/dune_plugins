<?php


class ShortMovie
{
    public $id;
    public $name;
    public $poster_url;

    public function __construct($id, $name, $poster_url)
    {
        if (is_null($id))
            throw new Exception("ShortMovie::id is null");

        $this->id = strval($id);
        $this->name = strval($name);
        $this->poster_url = strval($poster_url);
    }
}

class ShortMovieRange
{
    public $from_ndx;
    public $total;
    public $short_movies;

    public function __construct($from_ndx, $total, $short_movies = null)
    {
        $this->from_ndx = intval($from_ndx);
        $this->total = intval($total);
        $this->short_movies = $short_movies === null ?
            array() : $short_movies;
    }
}

class MovieSeries
{
    public $id;

    public function __construct($id)
    {
        if (is_null($id))
            throw new Exception("MovieSeries::id is null");

        $this->id = strval($id);
    }

    public $name = '';
    public $playback_url = '';
    public $playback_url_is_stream_url = true;
}

class Movie implements UserInputHandler
{
    public $id;
    public $name = '';
    public $poster_url = '';
    public $series_list = null;

	const ID = 'smart_movie';

    public function __construct($id)
    {
        if (is_null($id))
            throw new Exception("Movie::id is null");

        $this->id = strval($id);
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
////////////////////////////////////////////////////////////////////////////////////////////////////////


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

            ControlFactory::add_button_close ($defs, $this, $add_params=null,'cleer_sleep',	"Сброс Sleep таймера:", 'Очистить таймеры', 0);

			$sleep_time_hour = UserInputHandlerRegistry::create_action($this, 'sleep_time');
            ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,	'sleep_time', 'Применить', 250, $sleep_time_hour);

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

////////////////////////////////////////////////////////////////////////////////////////////////////

    private function to_string($v)
    {
        return $v === null ? '' : strval($v);
    }

    private function to_int($v, $default_value)
    {
        $v = strval($v);
        if (!is_numeric($v))
            return $default_value;
        $v = intval($v);
        return $v <= 0 ? $default_value : $v;
    }

    public function set_data($name, $poster_url)
    {
        $this->name = $this->to_string($name);
        $this->poster_url = $this->to_string($poster_url);
        $this->series_list = array();
    }

    public function add_series_data($id, $name,
        $playback_url, $playback_url_is_stream_url, $nmbr = null)
    {
        $series = new MovieSeries($id);
        $series->name = $this->to_string($name);
        $series->playback_url = $this->to_string($playback_url);
        $series->playback_url_is_stream_url = false;
		if (isset($nmbr)) //if ($nmbr == true)
			$series->nmbr = $this->to_string($nmbr);
        $this->series_list[] = $series;
    }

    public function get_movie_array()
    {
        return array(
            PluginMovie::name => $this->name,
            PluginMovie::name_original => $this->name_original,
            PluginMovie::description => '',
            PluginMovie::poster_url => $this->poster_url,
            PluginMovie::length_min => -1,
            PluginMovie::year => 0,
            PluginMovie::directors_str => '',
            PluginMovie::scenarios_str => '',
            PluginMovie::actors_str => '',
            PluginMovie::genres_str => '',
            PluginMovie::rate_imdb => '',
            PluginMovie::rate_kinopoisk => '',
            PluginMovie::rate_mpaa => '',
            PluginMovie::country => '',
            PluginMovie::budget => ''
        );
    }

    public function get_vod_info($sel_id, $buffering_ms)
    {
        if (!is_array($this->series_list) ||
            count($this->series_list) == 0)
        {
            throw new Exception('Invalid movie: series list is empty');
        }

        $series_array = array();
        $initial_series_ndx = -1;
        foreach ($this->series_list as $ndx => $series)
        {
            if (!is_null($sel_id) && $series->playback_url === $sel_id)
                $initial_series_ndx = $ndx;
            $series_array[] = array(
                PluginVodSeriesInfo::name => $series->name,
                PluginVodSeriesInfo::playback_url => $series->playback_url,
                PluginVodSeriesInfo::playback_url_is_stream_url =>
                    $series->playback_url_is_stream_url,
            );
        }

        return array(
            PluginVodInfo::id => $this->id,
            PluginVodInfo::name => $this->name,
            PluginVodInfo::description => '',
            PluginVodInfo::poster_url => $this->poster_url,
            PluginVodInfo::series => $series_array,
            PluginVodInfo::initial_series_ndx => $initial_series_ndx,
            PluginVodInfo::buffering_ms => $buffering_ms,
			PluginVodInfo::actions => $this->get_movie_actions(),
			PluginVodInfo::timer => array(GuiTimerDef::delay_ms => 5000),
        );
    }
}

?>
