<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class CurrentTimeSetupScreen extends AbstractControlsScreen
{
    const ID = 'setup';

    ///////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct(self::ID);

	try {
		//$this->version_msg = HD::http_get_document(CurrentTimeConfig::CHECK_VERSION_URL);
	}
	catch (Exception $e) {
		$this->version_msg = 'unknown';
	}
    }

    public function do_get_control_defs(&$plugin_cookies)
    {
        $defs = array();

        $show_tv = isset($plugin_cookies->show_tv) ?
            $plugin_cookies->show_tv : 'yes';

        $m3u = isset($plugin_cookies->m3u) ?
            $plugin_cookies->m3u : '';

        $use_proxy = isset($plugin_cookies->use_proxy) ?
            $plugin_cookies->use_proxy : 'no';

        $proxy_ip = isset($plugin_cookies->proxy_ip) ?
            $plugin_cookies->proxy_ip : '192.168.1.1';

	$proxy_port = isset($plugin_cookies->proxy_port) ?
            $plugin_cookies->proxy_port : '9999';

        $show_ops = array();
        $show_ops['yes'] = 'Да';
        $show_ops['no'] = 'Нет';

	$plugin_version = CurrentTimeConfig::PLUGIN_VERSION;

	//$plugin_version_msg = $this->version_msg;

	$this->add_label($defs,
    	    'Версия Triolan plugin:', "$plugin_version");

	/* $this->add_label($defs,
    	    'Доступная версия Triolan plugin:', "$plugin_version_msg"); */

        $this->add_combobox($defs,
            'show_tv', 'Показывать Triolan в разделе ТВ:',
            $show_tv, $show_ops, 0, true);

    	$this->add_text_field($defs,
        	'm3u', 'Ссылка на M3U-файл:', $m3u,
		false, false, false, true, 500, false, true);

        $this->add_combobox($defs,
            'use_proxy', 'Использовать UDP/HTTP proxy-сервер:',
            $use_proxy, $show_ops, 0, true);

	if ($use_proxy == 'yes') {

    	    $this->add_text_field($defs,
        	'proxy_ip', 'Адрес proxy-сервера (IP или DNS):', $proxy_ip,
		false, false, false, true, 500, false, true);

	    $this->add_text_field($defs,
    		'proxy_port', 'Порт proxy-сервера:', $proxy_port,
        	true, false, false,  true, null, false, true);
	}

        return $defs;
    }

    public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
    {
        return $this->do_get_control_defs($plugin_cookies);
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        hd_print('Setup: handle_user_input:');
        foreach ($user_input as $key => $value)
            hd_print("  $key => $value");

        if ($user_input->action_type === 'confirm' || $user_input->action_type === 'apply' )
        {
            $control_id = $user_input->control_id;
            $new_value = $user_input->{$control_id};
            hd_print("Setup: changing $control_id value to $new_value");

            if ($control_id === 'show_tv')
                $plugin_cookies->show_tv = $new_value;
            else if ($control_id === 'm3u')
                $plugin_cookies->m3u = $new_value;

            else if ($control_id === 'use_proxy') {
            	    $plugin_cookies->use_proxy = $new_value;
            	    $plugin_cookies->proxy_ip = isset($plugin_cookies->proxy_ip) ? $plugin_cookies->proxy_ip : '192.168.1.1';
		    $plugin_cookies->proxy_port = isset($plugin_cookies->proxy_port) ? $plugin_cookies->proxy_port : '9999';
                }
            else if ($control_id === 'proxy_ip')
                $plugin_cookies->proxy_ip = $new_value;
            else if ($control_id === 'proxy_port')
                $plugin_cookies->proxy_port = $new_value;

        }

        return ActionFactory::reset_controls(
            $this->do_get_control_defs($plugin_cookies));
    }
}

///////////////////////////////////////////////////////////////////////////
?>
