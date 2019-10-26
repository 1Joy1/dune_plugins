<?php
///////////////////////////////////////////////////////////////////////////
require_once 'lib/abstract_preloaded_regular_screen.php';
require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class CurentTimeSetupScreen extends AbstractControlsScreen
{
    const ID = 'setup';

    ///////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct(self::ID);
    }
	public static function get_media_url_str()
    {
        return MediaURL::encode(array('screen_id' => self::ID));
    }
    public function do_get_control_defs(&$plugin_cookies)
    {
        $defs = array();

        /////////////////Версия приложения/////////////////
		$ver = file_get_contents(dirname(__FILE__).'/dune_plugin.xml');
		if (is_null($ver)) {
				hd_print('Can`t load dune_plugin.xml');
				return 'n/a';
			}
		$xml = HD::parse_xml_document($ver);
		$plugin_version = strval($xml->version);
		$plugin_caption = strval($xml->caption);
		$plugin_name = strval($xml->name);
		$this->add_label($defs,"$plugin_caption:",$plugin_version);



        $qual_live = (HD::get_item('qual_live') !='') ? HD::get_item('qual_live') : CurentTimeConfig::QUAL_LIVE;
        $qual_live_ops = array('1080p' =>  '1080p',
                               '720p'  =>  '720p',
                               '540p'  =>  '540p',
                               'all'  => 'Выбор во время просмотра.'
                              );

        $this->add_combobox($defs,
            'qual_live', '%tr%qual_live',
            $qual_live, $qual_live_ops, 0, true);


        $qual_arhiv = (HD::get_item('qual_arhiv') !='') ? HD::get_item('qual_arhiv') : CurentTimeConfig::QUAL_ARHIV;
        $qual_arhiv_ops = array('1080p'  =>  '1080p',
                                '720p'  =>  '720p',
                                '270p'  =>  '270p',
                                'hls'  =>   'hls - (рекомендованно для прошивок ниже b11)',
                               );

        $this->add_combobox($defs,
            'qual_arhiv', '%tr%qual_arhiv',
            $qual_arhiv, $qual_arhiv_ops, 0, true);
        $this->add_label($defs,'','');

        $pagi = (HD::get_item('pagi') !='') ? HD::get_item('pagi') : CurentTimeConfig::PAGI;
        $pagi_ops = array(
            '1' => '1стр. или 12 роликов',       //1*12
            '5' => '5стр. или 60 роликов',
            '10' => '10стр. или 120 роликов',
            '20' => '20стр. или 240 роликов',
            '30' => '30стр. или 360 роликов',
            '40' => '40стр. или 480 роликов',
            '50' => '50стр. или 600 роликов',    //50*12
			'100' => '100стр. или 1200 роликов',
        );

        $this->add_combobox($defs,
            'pagi', '%tr%pagi',
            $pagi, $pagi_ops, 0, true);



        $background = (HD::get_item('bg') !='') ? HD::get_item('bg') : 'yes';
        $background_ops = array('yes'  =>  '%tr%yes',
                                'no'  =>  '%tr%no',
                               );

        $this->add_combobox($defs,
            'bg', '%tr%bg',
            $background, $background_ops, 0, true);


		ControlFactory::add_smart_label($defs,
			"Папка DATA:",
			'<text color="10" size="normal">'.CurentTimeConfig::get_data_path().'</text>'
		);

		$this->add_button($defs,
            'data_path',
            '',
            'Изменить расположение', 0
		);

		$this->add_button(
            $defs,'data_dialog',
            '',
            'Выгрузить/Загрузить/Очистить', 0
		);

        return $defs;
    }

    public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
    {
        return $this->do_get_control_defs($plugin_cookies);
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {

        if ($user_input->control_id === 'qual_arhiv'){
            HD::save_item('qual_arhiv', $user_input->qual_arhiv);
            if ($user_input->qual_arhiv != 'hls') {
                return ActionFactory::show_title_dialog(null, null, '%tr%q_arh_ch_mess', 900, 1);
            } else {
                return ActionFactory::show_title_dialog(null, null, '%tr%q_arh_ch_mess_hls', 900, 1);
			}
        }

        if ($user_input->control_id === 'qual_live'){
            HD::save_item('qual_live', $user_input->qual_live);
            //return  ActionFactory::show_title_dialog('%tr%save_bg_path',null,"Или POPUP - обновить на иконке плагина.",900,1);
            //$perform_new_action = ActionFactory::show_title_dialog('%tr%q_live_ch_mess');
            return ActionFactory::invalidate_folders(array('curent_time_tv_channel_list'));
        }

        if ($user_input->control_id === 'bg'){
            HD::save_item('bg', $user_input->bg);
            return  ActionFactory::show_title_dialog('%tr%save_bg_path',null,"Или POPUP - обновить на иконке плагина.",900,1);
        }

        if ($user_input->control_id === 'pagi'){
            HD::save_item('pagi', $user_input->pagi);
        }

		if ($user_input->control_id === 'data_path'){
                $media_url = MediaURL::encode
                (
                    array
                    (
                        'screen_id'     => 'file_list',
						'save_data'		=> 'data_dir_path'
                    )
                );
				return ActionFactory::open_folder($media_url,'Folders');
			}
		if ($user_input->control_id === 'data_dialog'){
				$defs = array();
				ControlFactory::add_multiline_label($defs, 'Внимание', "Возможно удаление внесенных изменений!\nВыгрузка происходит в текущую папку.\nЗагрузка происходит из текущей папки.\nВыберите папку и нажмите синюю кнопку:",6);
				ControlFactory::add_img_label($defs, '', '<icon>gui_skin://special_icons/controls_button_blue.aai</icon><text dy="7" size="small"> - нажмите для выгрузки или загрузки DATA</text>', 0, 0, 0);
				$this->add_button(
					$defs,
					'export_data',
					'Выгрузить DATA',
					"Плагин => Накопитель",
					850
				);
				$this->add_button(
					$defs,
					'import_data',
					'Загрузить DATA',
					'Накопитель => Плагин',
					850
				);
				$this->add_button(
					$defs,
					'import_file_data',
					'Файл в DATA',
					'Накопитель => Плагин',
					850
				);
				$this->add_button(
					$defs,
					'clear_data',
					'',
					'Очистить DATA в плагине',
					850
				);
				return  ActionFactory::show_dialog("Папка DATA",$defs, true,1200);
			}
		if ($user_input->control_id === 'import_file_data'){
				$media_url = MediaURL::encode (
                    array
                    (
                        'screen_id'     => 'file_list',
						'save_file'		=> array(
						'parent' 		=> 'tv_group_list',
						'action' 		=> 'import_file_data',
						'extension' 	=> 'all_extension'
						),
                    )
				);
				$do_actions = ActionFactory::open_folder($media_url,'Выберите файл для загрузки в DATA');
				return ActionFactory::close_dialog_and_run($do_actions);
			}
		if ($user_input->control_id === 'clear_data'){

				if (file_exists(DuneSystem::$properties['data_dir_path']))
					shell_exec("rm -rf ".DuneSystem::$properties['data_dir_path']);
				if (!file_exists(DuneSystem::$properties['data_dir_path']))
				mkdir(DuneSystem::$properties['data_dir_path'],0777,true);
				$do_actions = ActionFactory::show_title_dialog_gl("Папка DATA в плагине очищена!");
				return ActionFactory::close_dialog_and_run($do_actions);
			}
		if ($user_input->control_id === 'import_data'){
				$media_url = MediaURL::encode
                (
                    array
                    (
                        'screen_id'     => 'file_list',
						'save_data'		=> 'import_data'
                    )
                );
				$do_actions = ActionFactory::open_folder($media_url,'Folders');
				return ActionFactory::close_dialog_and_run($do_actions);
			}
		if ($user_input->control_id === 'export_data'){
				$media_url = MediaURL::encode
                (
                    array
                    (
                        'screen_id'     => 'file_list',
						'save_data'		=> 'export_data'
                    )
                );
				$do_actions = ActionFactory::open_folder($media_url,'Folders');
				return ActionFactory::close_dialog_and_run($do_actions);
			}
        return ActionFactory::reset_controls(
            $this->do_get_control_defs($plugin_cookies));
    }
}

///////////////////////////////////////////////////////////////////////////
?>
