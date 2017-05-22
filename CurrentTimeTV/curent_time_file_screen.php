<?
require_once 'lib/abstract_regular_screen.php';
require_once 'lib/user_input_handler_registry.php';
require_once 'lib/utils.php';

class CurentTimeFileSystemScreen extends AbstractRegularScreen implements UserInputHandler
{
	const ID = 'file_list';

    public function __construct()
    {
        parent::__construct(self::ID, CurentTimeFileSystemScreen::get_folder_views());
		UserInputHandlerRegistry::get_instance()->register_handler($this);
    }

    public function get_file_list($dir)
    {
		$smb_shares = new smbtree ();
		$fileData['folder'] = array();
		$fileData['file'] = array();
		if ($dir=='/tmp/mnt/smb'){
			$s['smb'] = $smb_shares->get_mount_all_smb ();
			return $s;
		}
		if ($dir=='/tmp/mnt/network'){
			$s['nfs'] = $smb_shares->get_mount_nfs ();
			return $s;
		}
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$absolute_filepath = $dir.'/'.$file;
					if ((preg_match('|^/tmp/mnt/smb/|', $absolute_filepath))&&($smb_shares->get_bug_platform_kind() == true))
						$is_dir = (bool)trim(shell_exec("test -d \"$absolute_filepath\" && echo 1 || echo 0"));
					else
						$is_dir = is_dir($absolute_filepath);
					if($is_dir==true){
						if ($absolute_filepath == '/tmp/mnt/nfs')
							continue;
						$fileData['folder'][$file]['filepath'] = $absolute_filepath;
					}
					else{
						if ((preg_match('|^/tmp/mnt/smb/|', $absolute_filepath))&&($smb_shares->get_bug_platform_kind() == true))
							$fileData['file'][$file]['size'] = '';
						else
							$fileData['file'][$file]['size'] = filesize($absolute_filepath);
						$fileData['file'][$file]['filepath'] = $absolute_filepath;
					}
				}
			}
			closedir($handle);
		}
		if ($dir=='/tmp/mnt'){
			$absolute_filepath = str_replace('plugins/liteIPTV', 'main_screen_items', DuneSystem::$properties['install_dir_path']);
			$fileData['folder']['Dune Favorites Folder']['filepath'] = $absolute_filepath;
		}
		return $fileData;
    }
	public function get_handler_id()
    {
        return self::ID;
    }
    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        $actions =array();
		$save_folder = UserInputHandlerRegistry::create_action($this,'save_folder');
		$save_folder['caption'] = "Выбрать папку";
		$clear_folder = UserInputHandlerRegistry::create_action($this,'clear_folder');
		$clear_folder['caption'] = "Очистить выбор";
		$open_folder = UserInputHandlerRegistry::create_action($this,'open_folder');
		$open_folder['caption'] = "Браузер файлов";
		$create_folder = UserInputHandlerRegistry::create_action($this,'create_folder');
		$create_folder['caption'] = "Создать папку";
		$smb_setup = UserInputHandlerRegistry::create_action($this,'smb_setup');
		$smb_setup['caption'] = "Настройки SMB";
        $actions[GUI_EVENT_KEY_ENTER] = UserInputHandlerRegistry::create_action($this, 'fs_action');
		$actions[GUI_EVENT_KEY_PLAY] = UserInputHandlerRegistry::create_action($this, 'fs_action');
		if ($media_url->filepath==false){
			$actions[GUI_EVENT_KEY_D_BLUE] = $smb_setup;
			if ((file_exists(DuneSystem::$properties['data_dir_path'] .'/'. $media_url->save_data))&&(isset($media_url->save_data)))
				$actions[GUI_EVENT_KEY_C_YELLOW] = $clear_folder;
		}
		if (($media_url->filepath==true)&&
		($media_url->filepath!='/tmp/mnt/storage')&&
		($media_url->filepath!='/tmp/mnt/network')&&
		($media_url->filepath != '/tmp/mnt/smb')){
			if ($media_url->save_data==true){
				$actions[GUI_EVENT_KEY_D_BLUE] = $save_folder;
				$actions[GUI_EVENT_KEY_B_GREEN] = $open_folder;
				$actions[GUI_EVENT_KEY_C_YELLOW] = $create_folder;}
		$actions[GUI_EVENT_TIMER] = UserInputHandlerRegistry::create_action($this, 'timer');
		}
        return $actions;
    }
    public function get_folder_range(MediaURL $media_url, $from_ndx, &$plugin_cookies)
    {
		$items = array();
		if($media_url->filepath != '')
			$dir = $media_url->filepath;
		else
			$dir="/tmp/mnt";
		$err = false;
		$ip_path = isset($media_url->ip_path) ? $media_url->ip_path : false;
		$nfs_protocol = isset($media_url->nfs_protocol) ? $media_url->nfs_protocol : false;
		$user = isset($media_url->user) ? $media_url->user : false;
		$password = isset($media_url->password) ? $media_url->password : false;
		$save_data = isset($media_url->save_data) ? $media_url->save_data : false;
		$save_file = isset($media_url->save_file) ? $media_url->save_file : false;
		foreach($this->get_file_list($dir) as $key => $itm)
		{
			ksort($itm);
			foreach($itm as $k => $v)
			{
				$detailed_icon = 'plugin_file://icons/line.png';
				if ($key == 'smb'){
					$caption = $v['foldername'];
					$filepath = $k;
					$icon_file = self::get_folder_icon('smb_folder',$filepath);
					$info = "SMB folder:||$caption||".$v['ip'];
					$type = 'folder';
					$ip_path = $v['ip'];
					if ((isset($v['user']))&&($v['user'] != ''))
						$user = $v['user'];
					if ((isset($v['user']))&&($v['password'] != ''))
						$password = $v['password'];
					if (isset($v['err'])){
						$info = "SMB folder:||ERROR!!!||".$v['err'];
						$err = $v['err'];
					}
				}
				if ($key == 'nfs'){
					$caption = $v['foldername'];
					$filepath = $k;
					$icon_file = self::get_folder_icon('smb_folder',$filepath);
					$info = "NFS folder:||$caption||".$v['ip'];
					$type = 'folder';
					$ip_path = $v['ip'];
					$nfs_protocol = $v['protocol'];
					if (isset($v['err'])){
						$info = "NFS folder:||ERROR!!!||".$v['err'];
						$err = $v['err'];
					}
				}
				else if ($key == 'file'){
					$caption = $k;
					$icon_file = self::get_file_icon($caption);
					$size = HD::get_filesize_str($v['size']);
					$filepath = $v['filepath'];
					$info = "File:||$caption||$size";
					$type = 'file';
					$path_parts = pathinfo($caption);
					if ((isset($media_url->save_file->extension))&&
					($media_url->save_file->extension=='all_extension')){
						$info = "File:||$caption||$size||||Нажмите ENTER для выбора $caption";
						if ($icon_file =='gui_skin://small_icons/image_file.aai')
							$detailed_icon = $filepath;
						else
							$detailed_icon = $icon_file;
						if (isset($media_url->save_file->extension_ico))
							$icon_file = $media_url->save_file->extension_ico;
					}
					if ((isset($media_url->save_file->extension))&&
					(isset($path_parts['extension']))&&
					(preg_match("|^".$media_url->save_file->extension."$|i", $path_parts['extension']))){
						$type = $media_url->save_file->extension;
						$info = "File:||$caption||$size||||Нажмите ENTER для выбора $caption";
						if ($icon_file =='gui_skin://small_icons/image_file.aai')
							$detailed_icon = $filepath;
						else
							$detailed_icon = $icon_file;
						if (isset($media_url->save_file->extension_ico))
							$icon_file = $media_url->save_file->extension_ico;
					}
				}else if ($key == 'folder'){
					$caption = $k;
					if ($k=='network')
						$caption = 'NFS';
					if ($k=='smb')
						$caption = 'SMB';
					if ($k=='storage')
						$caption = 'Storage';
					$filepath = $v['filepath'];
					$icon_file = self::get_folder_icon($caption,$filepath);
					$info = "Folder:||$caption";
					$type = 'folder';
				}

				$items[] = array
				(
					PluginRegularFolderItem::caption			=> $caption,
					PluginRegularFolderItem::media_url			=> self::get_media_url_str
						(
							'file_list',
							$caption,
							$filepath,
							$type,
							$ip_path,
							$user,
							$password,
							$nfs_protocol,
							$err,
							$save_data,
							$save_file

						),
					PluginRegularFolderItem::view_item_params	=> array
					(
						ViewItemParams::icon_path				=> $icon_file,
						ViewItemParams::item_detailed_info		=> $info,
						ViewItemParams::item_detailed_icon_path => $detailed_icon
					)
				);
			}
		}
		if (empty($items)){
			$items[] = array
				(
					PluginRegularFolderItem::caption			=> '(пусто)',
					PluginRegularFolderItem::media_url			=> '',
					PluginRegularFolderItem::view_item_params	=> array
					(
						ViewItemParams::icon_path				=> 'missing://',
						ViewItemParams::item_detailed_info		=> '',
					)
				);
		}
		return array
		(
			PluginRegularFolderRange::total => count($items),
			PluginRegularFolderRange::more_items_available => false,
			PluginRegularFolderRange::from_ndx => 0,
			PluginRegularFolderRange::count => count($items),
			PluginRegularFolderRange::items => $items
		);
    }

    static function get_folder_views()
    {
		$view = array
		(
			PluginRegularFolderView::view_params => array
			(
				ViewParams::num_cols => 1,
				ViewParams::num_rows => 10,
				ViewParams::paint_details => true,
				ViewParams::paint_item_info_in_details => true,
				ViewParams::zoom_detailed_icon => true,
				ViewParams::detailed_icon_scale_factor => 0.5,
				ViewParams::item_detailed_info_text_color => 10,
				ViewParams::item_detailed_info_auto_line_break => true
			),
			PluginRegularFolderView::base_view_item_params => array
			(
				ViewItemParams::item_paint_icon => true,
				ViewItemParams::icon_sel_scale_factor =>1.2,
				ViewItemParams::item_layout => HALIGN_LEFT,
				ViewItemParams::icon_valign => VALIGN_CENTER,
				ViewItemParams::icon_dx => 10,
				ViewItemParams::icon_dy => -5,
				ViewItemParams::icon_width => 50,
				ViewItemParams::icon_height => 50,
				ViewItemParams::icon_sel_margin_top => 0,
				ViewItemParams::item_paint_caption => true,
				ViewItemParams::item_caption_width => 1100,
				ViewItemParams::item_detailed_icon_path => 'missing://'
			),
			PluginRegularFolderView::not_loaded_view_item_params => array(),
			PluginRegularFolderView::async_icon_loading => false,
			PluginRegularFolderView::timer => array(GuiTimerDef::delay_ms => 5000),
		);
        return array($view);
    }
	    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        if (!isset($user_input->selected_media_url))
            return null;
		$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
        if ($user_input->control_id == 'timer')
		{
				$media_url = MediaURL::decode($user_input->parent_media_url);
				$actions = $this->get_action_map($media_url,$plugin_cookies);
				$media_url = MediaURL::decode($user_input->parent_media_url);
				if ((isset($media_url->filepath))&&($media_url->filepath != '/tmp/mnt/smb')&&($media_url->filepath != '/tmp/mnt/network')){
					$media_url= self::get_media_url_str
					(
						'file_list',
						$media_url->caption,
						$media_url->filepath,
						$media_url->type,
						isset($media_url->ip_path) ? $media_url->ip_path : false,
						isset($media_url->user) ? $media_url->user : false,
						isset($media_url->password) ? $media_url->password : false,
						isset($media_url->nfs_protocol) ? $media_url->nfs_protocol : false,
						isset($media_url->err) ? $media_url->err : false,
						$media_url->save_data,
						$media_url->save_file
					);
					$invalidate = ActionFactory::invalidate_folders(array($user_input->parent_media_url));
				}
				else
					$invalidate = null;
				return  ActionFactory::change_behaviour($actions, 1000, $invalidate);
		};
		if ($user_input->control_id == 'save_folder')
		{
				$media_url = MediaURL::decode($user_input->parent_media_url);
				if ((isset($media_url->ip_path))&&($media_url->ip_path==true)&&($media_url->nfs_protocol==false)){
					$save_folder[$media_url->ip_path]['foldername'] = preg_replace("#^\/tmp\/mnt\/smb\/\d*#", '', $media_url->filepath);
					$save_folder[$media_url->ip_path]['user'] = isset($media_url->user) ? $media_url->user : false;
					$save_folder[$media_url->ip_path]['password'] = isset($media_url->password) ? $media_url->password : false;
				}
				else if ((isset($media_url->ip_path))&&($media_url->ip_path==true)&&($media_url->nfs_protocol==true)){
					$save_folder[$media_url->ip_path]['foldername'] = preg_replace("#^\/tmp\/mnt\/network\/\d*#", '', $media_url->filepath);
				}
				else
					$save_folder['filepath'] = $media_url->filepath;
				if ($media_url->save_data=='export_data'){
					$caption = $media_url->caption;
					shell_exec("cp -rf ".DuneSystem::$properties['data_dir_path']." ".$media_url->filepath." > /dev/null &");
					return ActionFactory::show_title_dialog("Запушена выгрузка папки DATA в $caption !!!",null, 'Выгрузка запущена в фоновом режиме, это может занять какоето время в зависимости от размера папки DATA в плагине!',800);
				}elseif ($media_url->save_data=='import_data'){
					$caption = $media_url->caption;
					shell_exec("cp -rf ".$media_url->filepath."/* ".DuneSystem::$properties['data_dir_path']." > /dev/null &");
					return ActionFactory::show_title_dialog("Запушена загрузка папки $caption в DATA плагина!!!",null, 'Загрузка запущена в фоновом режиме, это может занять какоето время в зависимости от размера папки DATA!',800);
				}else{
					$link = DuneSystem::$properties['data_dir_path'] . '/'. $media_url->save_data;
					$data = fopen($link,"w");
					if (!$data)
						return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
					fwrite($data, serialize($save_folder));
					@fclose($data);
					$caption = $media_url->caption;
					$post_action = ActionFactory::invalidate_folders(array('setup','main_menu','vod_category_list'), null);
					return ActionFactory::show_title_dialog("Выбрано папку $caption !!!",$post_action,"При необходимости для применения изменений выйдите из плагина, нажмите кнопку POPUP и выберите 'Обновить'.",800);
				}
		};
		if ($user_input->control_id == 'fs_action')
		{
			$defs = array();
			$media_url = MediaURL::decode($user_input->selected_media_url);
			if ($media_url->type == 'folder'){
			$caption = $media_url->caption;
			if ($media_url->err == true){
				if ($media_url->nfs_protocol == true){
					ControlFactory::add_multiline_label($defs, 'Error mount:', $media_url->err, 3);
					ControlFactory::add_label($defs, 'NFS folder:', $media_url->caption);
					ControlFactory::add_label($defs, 'NFS IP:', $media_url->ip_path);
					ControlFactory::add_label($defs, 'Transport Protocol:', $media_url->nfs_protocol);
					ControlFactory::add_close_dialog_button($defs, 'ОК', 300);
					return ActionFactory::show_dialog('Error NFS!!!', $defs, true);
				}else{
					ControlFactory::add_multiline_label($defs, 'Error mount:', $media_url->err, 4);
					ControlFactory::add_label($defs, 'SMB folder:', $media_url->caption);
					ControlFactory::add_label($defs, 'SMB IP:', $media_url->ip_path);
					if (preg_match("|Permission denied|",$media_url->err)){
						$user = isset($media_url->user) ? $media_url->user : '';
						$password = isset($media_url->password) ? $media_url->password : '';
						ControlFactory::add_text_field($defs, $this, null,
								'new_user',
								'Имя пользователя SMB папки:',
								$user,
								0, 0, 0, 1, 500, 0, 0
						);

						ControlFactory::add_text_field($defs, $this, null,
								'new_pass',
								'Пароль SMB папки:',
								$password,
								0, 0, 0, 1, 500, 0, 0
						);

						$new_smb_data = UserInputHandlerRegistry::create_action($this, 'new_smb_data');
						ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,'new_smb_data', 'Применить', 300, $new_smb_data);
						ControlFactory::add_close_dialog_button($defs, 'Отмена', 300);
					}else{
					ControlFactory::add_label($defs, '', '');
					ControlFactory::add_close_dialog_button($defs, 'ОК', 300);
					}
					return ActionFactory::show_dialog('Error SMB!!!', $defs, true, 1100);
				}
			}
			$media_url= $user_input->selected_media_url;
				return ActionFactory::open_folder($media_url, $caption);
			}else if ($media_url->save_file->extension == $media_url->type){
				if (!file_exists(CurentTimeConfig::get_data_path() . "/icons/"))
						mkdir(CurentTimeConfig::get_data_path() . "/icons/",0777,true);
				if (!file_exists(CurentTimeConfig::get_data_path() . "/logo/"))
						mkdir(CurentTimeConfig::get_data_path() . "/logo/",0777,true);
				if ($media_url->save_file->action == 'portals_link'){
					$file = CurentTimeConfig::get_data_path() . "/icons/". $media_url->caption;
					if ((file_exists($file))&&(!isset($user_input->rewrite))){
						$defs = $this->do_rewrite_defs();
						return  ActionFactory::show_dialog("Такой файл уже существует в папке DATA",$defs, true);
					}
					if (!copy($media_url->filepath, $file))
						return ActionFactory::show_title_dialog("Не удалось скопировать ".$media_url->caption);
					$portals_link = HD::get_items($media_url->save_file->action);
					$portals_link[$media_url->save_file->arg]['portal_icons'] = $file;
					HD::save_items($media_url->save_file->action, $portals_link);
					$post_action = ActionFactory::invalidate_folders(array($media_url->save_file->parent));
					return ActionFactory::show_title_dialog("Сохранено: ". $media_url->caption,$post_action);
				}
				if ($media_url->save_file->action == 'plugin_ico'){
					$file = CurentTimeConfig::get_data_path() . "/icons/". $media_url->caption;
					if ((file_exists($file))&&(!isset($user_input->rewrite))){
						$defs = $this->do_rewrite_defs();
						return  ActionFactory::show_dialog("Такой файл уже существует в папке DATA",$defs, true);
					}
					if (!copy($media_url->filepath, $file))
						return ActionFactory::show_title_dialog("Не удалось скопировать ".$media_url->caption);
					$plugin_ico = HD::get_items($media_url->save_file->action);
					$plugin_ico[$media_url->save_file->arg] = $file;
					HD::save_items($media_url->save_file->action, $plugin_ico);
					$post_action = ActionFactory::invalidate_folders(array($media_url->save_file->parent));
					if (isset($media_url->save_file->msg))
						return ActionFactory::show_title_dialog("Сохранено: ". $media_url->caption,$post_action,$media_url->save_file->msg,800);
					else
						return ActionFactory::show_title_dialog("Сохранено: ". $media_url->caption,$post_action);
				}
				if ($media_url->save_file->action == 'ch_ico'){
					$file = CurentTimeConfig::get_data_path() . "/logo/". $media_url->save_file->arg . '.png';
					if ((file_exists($file))&&(!isset($user_input->rewrite))){
						$defs = $this->do_rewrite_defs();
						return  ActionFactory::show_dialog("Такой файл уже существует в папке DATA",$defs, true);
					}
					if (!copy($media_url->filepath, $file))
						return ActionFactory::show_title_dialog("Не удалось скопировать ".$media_url->caption);
					$mURL = MediaURL::encode (array(
					'screen_id' => $media_url->save_file->sid,
					'group_id' => $media_url->save_file->gid,
					));
					$post_action = ActionFactory::invalidate_folders(array($mURL),null);
					$post_action = ActionFactory::invalidate_folders(array($media_url->save_file->parent),$post_action);
					return ActionFactory::show_title_dialog("Сохранено: ". $media_url->caption, $post_action);
				}
}else if ($media_url->save_file->extension == 'all_extension'){
				if ($media_url->save_file->action == 'import_file_data'){
					$file = CurentTimeConfig::get_data_path() . "/". $media_url->caption;
					if ((file_exists($file))&&(!isset($user_input->rewrite))){
						$defs = $this->do_rewrite_defs();
						return  ActionFactory::show_dialog("Такой файл уже существует в папке DATA",$defs, true);
					}
					if (!copy($media_url->filepath, $file))
						return ActionFactory::show_title_dialog("Не удалось скопировать ".$media_url->caption);
					$post_action = ActionFactory::invalidate_folders(array($media_url->save_file->parent),null);
					return ActionFactory::show_title_dialog_gl("Сохранено: ". $media_url->caption, $post_action);
				}

			}

		};
		if ($user_input->control_id == 'new_smb_data')
		{
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$smb_shares = new smbtree ();
			$new_ip_smb[$media_url->ip_path]['foldername'] = $media_url->caption;
			$new_ip_smb[$media_url->ip_path]['user'] = $user_input->new_user;
			$new_ip_smb[$media_url->ip_path]['password'] = $user_input->new_pass;
			$q = $smb_shares->get_mount_smb ($new_ip_smb);
			if (isset($q['err_'.$media_url->caption])){
				$defs = $this->do_get_mount_smb_err_defs($q['err_'.$media_url->caption]['err'], $media_url->caption, $media_url->ip_path, $user_input->new_user, $user_input->new_pass);
				return ActionFactory::show_dialog('Error SMB!!!', $defs, true, 1100);
			}else{
			$caption = $media_url->caption;
			$media_url= self::get_media_url_str
						(
							'file_list',
							$media_url->caption,
							key($q),
							$media_url->type,
							$media_url->ip_path,
							$user_input->new_user,
							$user_input->new_pass,
							false,
							false,
							$save_data->save_data,
							$save_file->save_file
						);
				return ActionFactory::open_folder($media_url, $caption);
			}
		};
		if ($user_input->control_id == 'clear_folder')
		{
			$media_url = MediaURL::decode($user_input->parent_media_url);
			if (file_exists(DuneSystem::$properties['data_dir_path'] .'/'. $media_url->save_data))
				unlink(DuneSystem::$properties['data_dir_path'] .'/'. $media_url->save_data);
			else if (file_exists(CurentTimeConfig::get_data_path() .'/'. $media_url->save_data))
				unlink(CurentTimeConfig::get_data_path() .'/'. $media_url->save_data);
			$post_action = ActionFactory::invalidate_folders(array('setup','main_menu','vod_category_list'), null);
			return ActionFactory::show_title_dialog('Выбор папки сброшен!!!',$post_action,"При необходимости для применения изменений выйдите из плагина, нажмите кнопку POPUP и выберите 'Обновить'.",800);
		};
		if ($user_input->control_id == 'create_folder')
		{
			$defs = array();
			ControlFactory::add_text_field($defs,
			$this, $add_params=null,
			'do_folder_name', '',
			'', 0, 0, 1, 1, 1230,0,1);
			ControlFactory::add_vgap($defs, 500);
			return ActionFactory::show_dialog('Задайте имя папки', $defs, true);
		};
		if ($user_input->control_id == 'do_folder_name')
        {
			$do_mkdir = UserInputHandlerRegistry::create_action($this, 'do_mkdir');
			return ActionFactory::close_dialog_and_run($do_mkdir);
		}
		if ($user_input->control_id == 'do_mkdir')
        {
			$media_url = MediaURL::decode($user_input->parent_media_url);
			mkdir($media_url->filepath .'/'.$user_input->do_folder_name, 0777);
			return ActionFactory::invalidate_folders(array($user_input->parent_media_url));
		}
		if ($user_input->control_id == 'open_folder')
		{
				$media_url = MediaURL::decode($user_input->parent_media_url);
				$path = $media_url->filepath;
				if (preg_match('|^/tmp/mnt/storage/|', $path))
					$path = preg_replace('|^/tmp/mnt/storage/|', 'storage_name://', $path);
				else if ((isset($media_url->ip_path))&&(preg_match('|^/tmp/mnt/smb/|', $path))&&($media_url->user==true)&&($media_url->password==true))
					$path = 'smb://'. $media_url->user .':'. $media_url->password .'@'. preg_replace("|^\/tmp\/mnt\/smb\/\d|", str_replace('//', '',$media_url->ip_path), $path);
				else if ((isset($media_url->ip_path))&&(preg_match('|^/tmp/mnt/smb/|', $path)))
					$path = 'smb:' . preg_replace("|^\/tmp\/mnt\/smb\/\d|", $media_url->ip_path, $path);
				else if ((isset($media_url->ip_path))&&(preg_match('|^/tmp/mnt/network/|', $path))&&($media_url->nfs_protocol==true)){
					if ($media_url->nfs_protocol=='tcp')
						$path = 'nfs-tcp://' . preg_replace("|^\/tmp\/mnt\/network\/\d|", $media_url->ip_path.':/', $path);
					else
						$path = 'nfs-udp://' . preg_replace("|^\/tmp\/mnt\/network\/\d|", $media_url->ip_path.':/', $path);
				}
				$url = 'embedded_app://{name=file_browser}{url='.$path.'}{caption=File Browser}';
				return ActionFactory::launch_media_url($url);
		};
		if ($user_input->control_id == 'smb_setup')
		{
			$defs = array();
			$smb_shares = new smbtree ();
			$smb_view = $smb_shares->get_smb_infs ();

			$smb_view_ops = array();
			$smb_view_ops[1] = 'Сетевые папки';
			$smb_view_ops[2] = 'Сетевые папки + поиск SMB шар';
			$smb_view_ops[3] = 'Поиск SMB шар';

			ControlFactory::add_combobox($defs, $this, null,
            'smb_view', 'Отображать:',
            $smb_view, $smb_view_ops, 0, $need_confirm = false, $need_apply = false
			);
			$save_smb_setup = UserInputHandlerRegistry::create_action($this, 'save_smb_setup');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
			'_do_save_smb_setup', 'Применить', 250, $save_smb_setup);
            return ActionFactory::show_dialog('Настройка поиска SMB', $defs, true, 1000);
		};
		if ($user_input->control_id == 'save_smb_setup')
		{
			$smb_shares = new smbtree ();
			$smb_view_ops = array();
			$smb_view_ops[1] = 'Сетевые папки';
			$smb_view_ops[2] = 'Сетевые папки + поиск SMB шар';
			$smb_view_ops[3] = 'Поиск SMB шар';
			if (isset($user_input->smb_view))
				$smb_view = $smb_shares->get_smb_infs ($user_input->smb_view);
			return ActionFactory::show_title_dialog("Используется: ".$smb_view_ops[$smb_view]);
		}
        return null;
    }
	public static function get_media_url_str($id, $caption, $filepath, $type, $ip_path, $user, $password, $nfs_protocol, $err, $save_data, $save_file)
    {
        return  MediaURL::encode
                (
                    array
                    (
                        'screen_id'     => $id,
                        'caption'		=> $caption,
						'filepath' 		=> $filepath,
						'type' 			=> $type,
						'ip_path'		=> $ip_path,
						'user'			=> $user,
						'password'		=> $password,
						'nfs_protocol'	=> $nfs_protocol,
						'err'			=> $err,
						'save_data'		=> $save_data,
						'save_file'		=> $save_file,
                    )
                );
    }
	public static function get_file_icon($ref)
	{
		$file_icon = 'gui_skin://small_icons/unknown_file.aai';
		$audio_pattern = '/.*\.(mp3|ac3|wma|ogg|ogm|m4a|aif|iff|mid|mpa|ra|wav|flac|ape|vorbis|aac|a52)$/i';
		$video_pattern = '/.*\.(avi|mp4|mpg|mpeg|divx|m4v|3gp|asf|wmv|mkv|mov|ogv|vob|flv|ts|3g2|swf|asf|ps|qt|m2ts)$/i';
		$image_pattern = '/.*\.(png|jpg|jpeg|bmp|gif|psd|pspimage|thm|tif|yuf|svg|aai|ico|djpg|dbmp|dpng|image_file.aai)$/i';
		$play_list_pattern = '/.*\.(m3u|m3u8|pls)$/i';
		$torrent_pattern = '/.*\.torrent$/i';
		if (preg_match($audio_pattern,$ref))
			$file_icon = 'gui_skin://small_icons/audio_file.aai';
		if (preg_match($video_pattern,$ref))
			$file_icon = 'gui_skin://small_icons/video_file.aai';
		if (preg_match($image_pattern,$ref))
			$file_icon = 'gui_skin://small_icons/image_file.aai';
		if (preg_match($play_list_pattern,$ref))
			$file_icon = 'gui_skin://small_icons/playlist_file.aai';
		if (preg_match($torrent_pattern,$ref))
			$file_icon = 'gui_skin://small_icons/torrent_file.aai';
		return $file_icon;
	}
	public static function get_folder_icon($caption,$filepath){
		$folder_icon = "gui_skin://small_icons/folder.aai";
		if ($caption == 'Storage')
			$folder_icon = "gui_skin://small_icons/sources.aai";
		if ($caption == 'SMB')
			$folder_icon = "gui_skin://small_icons/smb.aai";
		if ($caption == 'smb_folder')
			$folder_icon = "gui_skin://small_icons/network_folder.aai";
		if ($caption == 'NFS')
			$folder_icon = "gui_skin://small_icons/network.aai";
		if ((preg_match("|/tmp/mnt/storage/.*$|",$filepath))&&(!preg_match("|/tmp/mnt/storage/.*/|",$filepath)))
			$folder_icon = "gui_skin://small_icons/hdd.aai";
	return $folder_icon;
	}
	public function do_rewrite_defs()
	{
		ControlFactory::add_label($defs, "", "Перезаписать Файл?");
		$add_params ['rewrite'] = true;
		ControlFactory::add_close_dialog_and_apply_button(&$defs,
		$this, $add_params,
		'fs_action', 'Да', 250, $gui_params = null);
		ControlFactory::add_close_dialog_button($defs, 'Нет', 250);
		return $defs;
	}
	public function do_get_mount_smb_err_defs($err, $caption, $ip_path, $user, $password)
	{
		$defs = array();
		ControlFactory::add_multiline_label($defs, 'Error mount:', $err, 4);
		ControlFactory::add_label($defs, 'SMB folder:', $caption);
		ControlFactory::add_label($defs, 'SMB IP:', $ip_path);
		if (preg_match("|Permission denied|",$err)){
			ControlFactory::add_text_field($defs, $this, null,
				'new_user',
				'Имя пользователя SMB папки:',
				$user,
				0, 0, 0, 1, 500, 0, 0
			);

			ControlFactory::add_text_field($defs, $this, null,
				'new_pass',
				'Пароль SMB папки:',
				$password,
				0, 0, 0, 1, 500, 0, 0
			);

			$new_smb_data = UserInputHandlerRegistry::create_action($this, 'new_smb_data');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,'new_smb_data', 'Применить', 300, $new_smb_data);
			ControlFactory::add_close_dialog_button($defs, 'Отмена', 300);
		}else{
			ControlFactory::add_label($defs, '', '');
			ControlFactory::add_close_dialog_button($defs, 'ОК', 300);
		}
		return $defs;
	}
}
/**
 * @author: Andrii Kopyniak
 * @date:   2013 Jan 10
 */

class smbtree
{
  private $cmd            = 'smbtree';
  private $descriptorspec = array ();
  private $cwd            = '/';
  private $env            = array ();
  private $smbtree_output = '';
  private $return_value   = 0;
  private $no_pass        = true;
  private $debuglevel     = 0;

  public function __construct ()
  {
    $this->cmd = '/tango/firmware/bin/smbtree ';
    $this->cwd = '/tmp';

    $this->descriptorspec = array
      (
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
      );
    $this->env = array ('LD_LIBRARY_PATH' => '/tango/firmware/lib');

    $no_pass = true;
  }

  private function get_auth_options ()
  {
    if ($this->is_no_pass ())
      return '-N';

    return '';
  }

  private function get_debug_level ()
  {
    return '--debuglevel ' . $this->debuglevel;
  }

  private function is_no_pass ()
  {
    return $this->no_pass;
  }

  /*
   * @return 0 if success
   */
  private function execute ($args = '')
  {
    $process = proc_open ($this->cmd
      . $this->get_auth_options () . ' '
      . $this->get_debug_level () . ' '
      . $args,
      $this->descriptorspec, $pipes, $this->cwd, $this->env);

    if (is_resource ($process))
    {
      //fclose ($pipes[0]);

      $this->smbtree_output = stream_get_contents ($pipes[1]);
      fclose ($pipes[1]);
      fclose ($pipes[2]);

      // Важно закрывать все каналы перед вызовом
      // proc_close во избежание мертвой блокировки
      $this->return_value = proc_close ($process);
    }

    return $this->return_value;
  }

  private function parse_smbtree_output ($input_lines)
  {
    $output = array ();

    if (!strlen ($input_lines))
      return array ();

    $output_lines = explode ("\n", $input_lines);
    if ($output_lines == false)
      return array ();

    foreach ($output_lines as $line)
    {
      if (strlen ($line))
      {
        $detail_info = explode ("\t", $line);

        if (count ($detail_info))
        {
		  $q = isset($detail_info[1]) ? $detail_info[1] : '';
		  $output[$detail_info[0]] = array
            (
              'name'     => $detail_info[0],
              'comment'  => $q,
            );
        }
      }
    }

    return $output;
  }

  public function get_xdomains ()
  {
    if ($this->execute ('--xdomains') != 0)
      return array ();

    return $this->parse_smbtree_output ($this->smbtree_output);
  }

  public function get_domains ()
  {
    if ($this->execute ('--domains') != 0)
      return array ();

    return $this->parse_smbtree_output ($this->smbtree_output);
  }

  public function get_workgroup_servers ($domain)
  {
    if ($this->execute ('--workgroup-servers=' . $domain) != 0)
      return array ();

    return $this->parse_smbtree_output ($this->smbtree_output);
  }

  public function get_server_shares ($server)
  {
    if ($this->execute ('--server-shares=' . $server) != 0)
      return array ();

    return $this->parse_smbtree_output ($this->smbtree_output);
  }
  public static function get_df_smb ()
  {
	$df_smb = array();
	$df_smb_exec = shell_exec ("df |grep /tmp/mnt/smb");
	preg_match_all('|(.*?) .*\%\s/tmp/mnt/smb/(.*)|', $df_smb_exec, $match);
	foreach ($match[2] as $k => $v)
		$df_smb[$match[1][$k]]=$v;
	return $df_smb;
  }
  public function get_network_folder_smb ()
  {
	$d = array();
	$network = parse_ini_file('/config/network_folders.properties',true);
	foreach ($network as $k => $v){
	preg_match("|(.*)\.(.*)|", $k, $match);
	$network_folder[$match[2]][$match[1]] = $v;
	}
	foreach ($network_folder as $k => $v){
		if ($v['type']==0){
			$dd['foldername'] =$v['name'];
			if ($v['user'] != '')
			$dd['user'] =$v['user'];
			if ($v['password'] != '')
			$dd['password'] =$v['password'];
			$d[$v['server']][$v['directory']] = $dd;
		}
	}
	return $d;
  }
  public function get_server_shares_smb ()
  {
	$d=array();
	$data = self::get_xdomains ();
	foreach ($data as $domain){
		$data = self::get_workgroup_servers ($domain['name']);
		 foreach ($data as $shares)
		 $d[$shares['name']] = self::get_server_shares ($shares['name']);
	}
	return $d;
  }

    public function get_ip_network_folder_smb ()
  {
	$d=array();
	$network_folder_smb = self::get_network_folder_smb ();
	foreach ($network_folder_smb as $k => $v)
	{
		if (!preg_match('@((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)@', $k)){
			$out = shell_exec ('export LD_LIBRARY_PATH=/firmware/lib:$LD_LIBRARY_PATH&&/firmware/bin/nmblookup '.$k.' -S');
			if (preg_match('/(.*) (.*)<00>/', $out, $matches)){
				$ip = '//'. $matches[1] . '/';
				if ($matches[2] == $k)
					foreach ($v as $key => $vel){
						$d[$ip.$key] = $vel;
					}
			}
		}else{
			foreach ($v as $key => $vel){
						$d['//'.$k.'/'.$key] = $vel;
					}
		}
	}
	return $d;
  }

  public function get_ip_server_shares_smb ()
  {
	$d=array();
	$my_ip = HD::get_ip_address();
	$server_shares_smb = self::get_server_shares_smb ();
	foreach ($server_shares_smb as $k => $v)
	{
		$out = shell_exec ('export LD_LIBRARY_PATH=/firmware/lib:$LD_LIBRARY_PATH&&/firmware/bin/nmblookup '.$k.' -R');
		if (preg_match('/(.*) (.*)<00>/', $out, $matches)){
		if ($my_ip == $matches[1])
			continue;
		$ip = '//'. $matches[1] . '/';
		if ($matches[2] == $k)
			foreach ($v as $key => $vel){
				$vel['foldername'] = $key . ' in ' . $k;
				$d[$ip.$key] = $vel;
			}
		}
	}
	return $d;
  }

  public static function get_mount_smb ($ip_smb)
  {
	$d = array();
	foreach ($ip_smb as $k => $vel){
		$df_smb = self::get_df_smb ();
		if (isset($df_smb[$k])){
			$d['/tmp/mnt/smb/'.$df_smb[$k]]['foldername'] = $vel['foldername'];
			$d['/tmp/mnt/smb/'.$df_smb[$k]]['ip'] = $k;
			if ((isset($vel['user']))&&($vel['user'] != ''))
				$d['/tmp/mnt/smb/'.$df_smb[$k]]['user'] = $vel['user'];
			if ((isset($vel['user']))&&($vel['password'] != ''))
				$d['/tmp/mnt/smb/'.$df_smb[$k]]['password'] = $vel['password'];
		}else{
			$q = false;
			if (count($df_smb)>0)
				$n = count($df_smb);
			else $n = 0;
			$fn = '/tmp/mnt/smb/'. $n;
			if (!file_exists($fn)){
				mkdir($fn,0777,true);
			}
				$username=$password='guest';
				if ((isset($vel['user']))&&($vel['user'] != ''))
					$username=$vel['user'];
				if ((isset($vel['user']))&&($vel['password'] != ''))
					$password=$vel['password'];
				$q = exec("mount -t cifs -o username=$username,password=$password,posixpaths,rsize=32768,wsize=130048 $k \"$fn\" 2>&1 &");
			if ($q==true){
				$d['err_'.$vel['foldername']]['foldername'] = $vel['foldername'];
				$d['err_'.$vel['foldername']]['ip'] = $k;
				$d['err_'.$vel['foldername']]['err'] = trim($q);
				if ((isset($vel['user']))&&($vel['user'] != ''))
					$d['err_'.$vel['foldername']]['user'] = $vel['user'];
				if ((isset($vel['user']))&&($vel['password'] != ''))
					$d['err_'.$vel['foldername']]['password'] = $vel['password'];
			}else{
				$d[$fn]['foldername'] = $vel['foldername'];
				$d[$fn]['ip'] = $k;
				if ((isset($vel['user']))&&($vel['user'] != ''))
					$d[$fn]['user'] = $vel['user'];
				if ((isset($vel['user']))&&($vel['password'] != ''))
					$d[$fn]['password'] = $vel['password'];
			}
		}

	}
	return $d;
  }
  public function get_smb_infs ($item=false)
  {
	$q = 1;
	$path = DuneSystem::$properties['data_dir_path'].'/smb_setup';
	if ($item==false){
	if (file_exists($path))
		$q = file_get_contents($path);
	}else{
		$data = fopen($path,"w");
		if (!$data)
		return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
		fwrite($data, $item);
		@fclose($data);
		$q =  $item;
	}
	return $q;
  }
 public function get_mount_all_smb ()
  {
	$q = self::get_smb_infs ();
	if ($q!=3)
		$a = self::get_mount_smb (self::get_ip_network_folder_smb());
	if ($q==1)
		return $a;
	$b = self::get_mount_smb (self::get_ip_server_shares_smb());
	if ($q==3)
		return $b;
	return array_merge($b,$a);
  }
  public function get_network_folder_nfs ()
  {
	$d = array();
	$network = parse_ini_file('/config/network_folders.properties',true);
	foreach ($network as $k => $v){
	preg_match("|(.*)\.(.*)|", $k, $match);
	$network_folder[$match[2]][$match[1]] = $v;
	}
	foreach ($network_folder as $k => $v){
		if ($v['type']==1){
			$p = 'udp';
			if ($v['protocol']==1)
				$p = 'tcp';
			$nfs[$v['server'].':'.$v['directory']]['foldername'] = $v['name'];
			$nfs[$v['server'].':'.$v['directory']]['protocol'] =$p;
		}
	}
	return $nfs;
  }
  public function get_df_nfs ()
  {
	$df_nfs = array();
	$df_nfs_exec = shell_exec ("mount |grep /tmp/mnt/network");
	preg_match_all('|(.*?)\son\s/tmp/mnt/network/(.*?)\s|', $df_nfs_exec, $match);
	foreach ($match[2] as $k => $v)
		$df_nfs[$match[1][$k]]=$v;
	return $df_nfs;
  }
  public function get_mount_nfs ()
  {
	$d = array();
	$ip_nfs = self::get_network_folder_nfs ();
	foreach ($ip_nfs as $k => $vel){
		$df_nfs = self::get_df_nfs ();
		if (isset($df_nfs[$k])){
			$d['/tmp/mnt/network/'.$df_nfs[$k]]['foldername'] = $vel['foldername'];
			$d['/tmp/mnt/network/'.$df_nfs[$k]]['ip'] = $k;
			$d['/tmp/mnt/network/'.$df_nfs[$k]]['protocol'] = $vel['protocol'];
		}else{
			$q = false;
			if (count($df_nfs)>0)
				$n = count($df_nfs);
			else $n = 0;
			$fn = '/tmp/mnt/network/'. $n;
			if (!file_exists($fn)){
				mkdir($fn,0777,true);
			}
			$q = shell_exec("mount -t nfs -o ".$vel['protocol']." $k $fn 2>&1");
			if ($q==true){
				$d['err_'.$vel['foldername']]['foldername'] = $vel['foldername'];
				$d['err_'.$vel['foldername']]['ip'] = $k;
				$d['err_'.$vel['foldername']]['protocol'] = $vel['protocol'];
				$d['err_'.$vel['foldername']]['err'] = trim($q);
			}else{
				$d[$fn]['foldername'] = $vel['foldername'];
				$d[$fn]['ip'] = $k;
				$d[$fn]['protocol'] = $vel['protocol'];
			}
		}

	}
	return $d;
  }
  public static function get_folder_info ($param)
  {
	$select_folder = false;
	if (file_exists(DuneSystem::$properties['data_dir_path'] .'/'. $param)){
		$save_folder = unserialize(file_get_contents(DuneSystem::$properties['data_dir_path'] .'/'. $param));
		if (isset($save_folder['filepath']))
			$select_folder = $save_folder['filepath'];
		else
			foreach ($save_folder as  $k => $v){
				if ((isset($v['foldername']))&&(isset($v['user'])))
					$q = self::get_mount_smb ($save_folder);
				if ((isset($v['foldername']))&&(!isset($v['user'])))
					$q = self::get_mount_nfs ();
				$select_folder = key($q).$v['foldername'];
			}
	}
	return $select_folder;
  }
  public static function get_bug_platform_kind()
	{
		static $bug_platform_kind = null;

		if (is_null($bug_platform_kind))
		{
			$arr = file('/tmp/run/versions.txt'); $v = '';
			foreach($arr as $line)
			{
				if ( stristr($line, 'platform_kind=') )
				{
					$v = trim( substr($line, 14) );
				}
			}
			$platform_kind = $v;
			if (($platform_kind =='8672')||($platform_kind =='8672'))
				$bug_platform_kind = true;
			else
				$bug_platform_kind = false;

		}
		return $bug_platform_kind;
	}
}
?>
