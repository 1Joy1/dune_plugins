<?php


class HD
{
    public static function is_map($a)
    {
        return is_array($a) &&
            array_diff_key($a, array_keys(array_keys($a)));
    }



    public static function has_attribute($obj, $n)
    {
        $arr = (array) $obj;
        return isset($arr[$n]);
    }


    public static function get_map_element($map, $key)
    {
        return isset($map[$key]) ? $map[$key] : null;
    }



    public static function starts_with($str, $pattern)
    {
        return strpos($str, $pattern) === 0;
    }



    public static function format_timestamp($ts, $fmt = null)
    {
        // NOTE: for some reason, explicit timezone is required for PHP
        // on Dune (no builtin timezone info?).

        if (is_null($fmt))
            $fmt = 'Y:m:d H:i:s';

        $dt = new DateTime('@' . $ts);
        return $dt->format($fmt);
    }



    public static function format_duration($msecs)
    {
        $n = intval($msecs);

        if (strlen($msecs) <= 0 || $n <= 0)
            return "--:--";

        $n = $n / 1000;
        $hours = $n / 3600;
        $remainder = $n % 3600;
        $minutes = $remainder / 60;
        $seconds = $remainder % 60;

        if (intval($hours) > 0)
        {
            return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
        }
        else
        {
            return sprintf("%02d:%02d", $minutes, $seconds);
        }
    }

    public static function sec_format_duration($secs)
    {
        $n = intval($secs);

        if (strlen($secs) <= 0 || $n <= 0)
            return "--:--";

        $hours = $n / 3600;
        $remainder = $n % 3600;
        $minutes = $remainder / 60;
        $seconds = $remainder % 60;

        if (intval($hours) > 0)
        {
            return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
        }
        else
        {
            return sprintf("%02d:%02d", $minutes, $seconds);
        }
    }
    public static function min_format_duration($secs)
    {
        $n = intval($secs);

        if (strlen($secs) <= 0 || $n <= 0)
            return "--:--";

        $hours = $n / 3600;
        $remainder = $n % 3600;
        $minutes = $remainder / 60;
        $seconds = $remainder % 60;

        if (intval($hours) > 0)
            return sprintf("%d hrs. %02d min. %02d sec.", $hours, $minutes, $seconds);
        else if (intval($minutes) > 0)
            return sprintf("%02d min. %02d sec.", $minutes, $seconds);
        else
            return 'меньше 1 минуты';
    }
    public static function encode_user_data($a, $b = null)
    {
        $media_url = null;
        $user_data = null;

        if (is_array($a) && is_null($b))
        {
            $media_url = '';
            $user_data = $a;
        }
        else
        {
            $media_url = $a;
            $user_data = $b;
        }

        if (!is_null($user_data))
            $media_url .= '||' . json_encode($user_data);

        return $media_url;
    }



    public static function decode_user_data($media_url_str, &$media_url, &$user_data)
    {
        $idx = strpos($media_url_str, '||');

        if ($idx === false)
        {
            $media_url = $media_url_str;
            $user_data = null;
            return;
        }

        $media_url = substr($media_url_str, 0, $idx);
        $user_data = json_decode(substr($media_url_str, $idx + 2));
    }



    public static function create_regular_folder_range($items,
        $from_ndx = 0, $total = -1, $more_items_available = false)
    {
        if ($total === -1)
            $total = $from_ndx + count($items);

        if ($from_ndx >= $total)
        {
            $from_ndx = $total;
            $items = array();
        }
        else if ($from_ndx + count($items) > $total)
        {
            array_splice($items, $total - $from_ndx);
        }

        return array
        (
            PluginRegularFolderRange::total => intval($total),
            PluginRegularFolderRange::more_items_available => $more_items_available,
            PluginRegularFolderRange::from_ndx => intval($from_ndx),
            PluginRegularFolderRange::count => count($items),
            PluginRegularFolderRange::items => $items
        );
    }



    public static function http_get_document($url, $opts = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,    0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,    0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,    40);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,    1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    true);
        curl_setopt($ch, CURLOPT_TIMEOUT,           40);
        curl_setopt($ch, CURLOPT_USERAGENT,         "Mozilla/5.0 (Windows NT 5.1; rv:7.0.1) Gecko/20100101 Firefox/7.0.1");
        curl_setopt($ch, CURLOPT_ENCODING,          1);
        curl_setopt($ch, CURLOPT_URL,               $url);

        if (isset($opts))
        {
            foreach ($opts as $k => $v)
                curl_setopt($ch, $k, $v);
        }

        hd_print("HTTP fetching '$url'...");

        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($content === false)
        {
            $err_msg = "HTTP error: $http_code (" . curl_error($ch) . ')';
            hd_print($err_msg);
        }

        if ($http_code != 200)
        {
            $err_msg = "HTTP request failed ($http_code)";
            hd_print($err_msg);
        }

        hd_print("HTTP OK ($http_code)");

        curl_close($ch);

        return $content;
    }



    public static function http_post_document($url, $post_data, $opts=null)
    {
        $arr [CURLOPT_POST] = true;
        $arr [CURLOPT_POSTFIELDS] = $post_data;
        if (isset($opts))
        {
            foreach ($opts as $k => $v)
               $arr[$k] = $v;
        }
        return self::http_get_document($url, $arr);
    }



    public static function parse_xml_document($doc)
    {
        $xml = simplexml_load_string($doc);

        if ($xml === false)
        {
            hd_print("Error: can not parse XML document.");
            hd_print("XML-text: $doc.");
            hd_print('Illegal XML document');
        }

        return $xml;
    }



    public static function make_json_rpc_request($op_name, $params)
    {
        static $request_id = 0;

        $request = array
        (
            'jsonrpc' => '2.0',
            'id' => ++$request_id,
            'method' => $op_name,
            'params' => $params
        );

        return $request;
    }

    public static function get_ip_address()
    {
        static $ip_address = null;

        if (is_null($ip_address))
        {
            $withV6 = true;
            preg_match_all('/inet'.($withV6 ? '6?' : '').' addr: ?([^ ]+)/', `ifconfig`, $ips);
            $ip_address = array_shift($ips[1]);
        }

        return $ip_address;
    }


    public static function get_mac_addr()
    {
        static $mac_addr = null;

        if (is_null($mac_addr))
        {
            $mac_addr = shell_exec(
                'ifconfig  eth0 | head -1 | sed "s/^.*HWaddr //"');
            $mac_addr = trim($mac_addr);
        }

        return $mac_addr;
    }



    // TODO: localization
    private static $MONTHS = array(
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    );

    public static function format_date_time_date($tm)
    {
        $lt = localtime($tm);
        $mon = self::$MONTHS[$lt[4]];
        return sprintf("%02d %s %04d", $lt[3], $mon, $lt[5] + 1900);
    }

    public static function format_date_time_time($tm, $with_sec = false)
    {
        $format = '%H:%M';
        if ($with_sec)
            $format .= ':%S';
        return strftime($format, $tm);
    }

    public static function print_backtrace()
    {
        hd_print('Back trace:');
        foreach (debug_backtrace() as $f)
        {
            hd_print(
                '  - ' . $f['function'] .
                ' at ' . $f['file'] . ':' . $f['line']);
        }
    }
    public static function get_filesize_str($size)
    {
    if( $size < 1024 )
    {
        $size_num = $size;
        $size_suf = "B";
    }
    else if( $size < 1048576 ) // 1M
    {
        $size_num = round($size / 1024, 2);
        $size_suf = "KiB";
    }
    else if( $size < 1073741824 ) // 1G
    {
        $size_num = round($size / 1048576, 2);
        $size_suf = "MiB";
    }
    else
    {
        $size_num = round($size / 1073741824, 2);
        $size_suf = "GiB";
    }
    return "$size_num $size_suf";
    }

    public static function get_items($path) {
    $items = array();
    if ($path=='data_dir_path')
        $link = DuneSystem::$properties['data_dir_path'] . '/'. $path;
    else
        $link = SmartConfig::get_data_path() . '/'. $path;
            if (file_exists($link)){
            $doc = file_get_contents($link);
            $items = unserialize($doc);}
    return $items;
    }

    public static function save_items($path, $items) {
    if ($path=='data_dir_path')
        $link = DuneSystem::$properties['data_dir_path'] . '/'. $path;
    else
        $link = SmartConfig::get_data_path() . '/'. $path;
    $skey = serialize($items);
                        $data = fopen($link,"w");
                        if (!$data)
                            return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
                        fwrite($data, $skey);
                        @fclose($data);
    }

    public static function save_items_tmp($path, $items) {
    $data_path = DuneSystem::$properties['tmp_dir_path']. '/' .$path;
    $skey = serialize($items);
                        $data = fopen($data_path,"w");
                        if (!$data)
                            return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
                        fwrite($data, $skey);
                        @fclose($data);
    }

    public static function get_items_tmp($path) {
    $item = '';
    $data_path = DuneSystem::$properties['tmp_dir_path']. '/' .$path;
            if (file_exists($data_path))
            $item = unserialize(file_get_contents($data_path));
    return $item;
    }

    public static function save_item($path, $item) {
    $link = SmartConfig::get_data_path() . '/'. $path;
                        $data = fopen($link,"w");
                        if (!$data)
                            return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
                        fwrite($data, $item);
                        @fclose($data);
    }
    public static function get_item($path) {
    $item = '';
    $link = SmartConfig::get_data_path() . '/'. $path;
            if (file_exists($link))
            $item = file_get_contents($link);
    return $item;
    }

    public static function save_item_tmp($path, $item) {
    $link = DuneSystem::$properties['tmp_dir_path'] . '/'. $path;
                        $data = fopen($link,"w");
                        if (!$data)
                            return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
                        fwrite($data, $item);
                        @fclose($data);
    }
    public static function get_item_tmp($path) {
    $item = '';
    $link = DuneSystem::$properties['tmp_dir_path'] . '/'. $path;
            if (file_exists($link))
            $item = file_get_contents($link);
    return $item;
    }
    public static function str_remove_tag($str)
    {
        if (preg_match("|(.*?)<font color='green'>Отзывы|ms", $str, $matches))
            $str = $matches[1];

        $search = array('@<script[^>]*?>.*?</script>@si',   // Strip out javascript
                   '@<[\/\!]*?[^<>]*?>@si',                 // Strip out HTML tags
                   '@<style[^>]*?>.*?</style>@siU',         // Strip style tags properly
                   '|.*{.*}|'
        );
        $str = preg_replace($search, '', $str);
        $str = strip_tags($str);
        $str = str_replace("●","*",$str);
        $str = str_replace("&nbsp;"," ",$str);
        $str = str_replace("&#8211;"," - ",$str);
        $str = str_replace(array("&laquo;","&raquo;"),"'",$str);
        $str = preg_replace("|&#.*?;|", "", $str);
        $str = html_entity_decode($str);
        $str = str_replace('"', "'", $str);
        $str = preg_replace('/\s{2,}/', ' ', $str);
        $str = trim($str);
        return $str;
    }
    public static function get_codec_start_info()
    {
        $check = shell_exec('ps -Af | grep httpd | grep -c 81');
        if ( $check <= 1){
            shell_exec("httpd -h /codecpack/WWW -p 81");
            usleep(500000);
        }
    }

    public static function ver()
    {
        $ver = file_get_contents(DuneSystem::$properties['install_dir_path'].'/dune_plugin.xml');
            if (is_null($ver)) {
                    hd_print('Can`t load dune_plugin.xml');
                    return 'n/a';
                }
        $xml = HD::parse_xml_document($ver);
        $plugin_version = strval($xml->version);
        return $plugin_version;
    }

    public static function alphabet()
    {
        return array(
        '0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9',
        'А' => 'А', 'Б' => 'Б', 'В' => 'В', 'Г' => 'Г', 'Д' => 'Д', 'Е' => 'Е', 'Ё' => 'Ё', 'Ж' => 'Ж', 'З' => 'З', 'И' => 'И', 'Й' => 'Й', 'К' => 'К', 'Л' => 'Л', 'М' => 'М', 'Н' => 'Н', 'О' => 'О', 'П' => 'П', 'Р' => 'Р', 'С' => 'С', 'Т' => 'Т', 'У' => 'У', 'Ф' => 'Ф', 'Х' => 'Х', 'Ц' => 'Ц', 'Ч' => 'Ч', 'Ш' => 'Ш', 'Щ' => 'Щ', 'Ъ' => 'Ъ', 'Ы' => 'Ы', 'Ь' => 'Ь', 'Э' => 'Э', 'Ю' => 'Ю', 'Я' => 'Я',
        'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J', 'K' => 'K', 'L' => 'L', 'M' => 'M', 'N' => 'N', 'O' => 'O', 'P' => 'P', 'Q' => 'Q', 'R' => 'R', 'S' => 'S', 'T' => 'T', 'U' => 'U', 'V' => 'V', 'W' => 'W', 'X' => 'X', 'Y' => 'Y', 'Z' => 'Z',
        );
    }
    public static function get_id_key($str)
    {
        $captions = mb_strtolower($str, 'UTF-8');
        $captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
        $id_key = md5($captions);
        return $id_key;
    }

    public static function get_m3u()
    {
        $m3u_files = array();
        $pl_folders = unserialize(file_get_contents(DuneSystem::$properties['data_dir_path'] . '/pl_folder'));
        if (empty($pl_folders))
            return false;
        foreach ($pl_folders as $v){
            if (isset($v['file_name'])){
                $q = array_slice($v, 0, 1);
            }elseif (!isset($v['pl_url']))
                $q =$v;
            if (!isset($v['pl_url']))
                $m3u_dir = smbtree::get_folder_info ($q);
            if (isset($v['file_name'])){
                $path_parts = pathinfo($v['file_name']);
                if ($path_parts['extension']=='txt'){
                    $pls_lines = file($m3u_dir . '/'.$v['file_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($pls_lines as $item)
                        $m3u_files[$item] = $item;
                }else
                    $m3u_files[$m3u_dir .'/'. $v['file_name']] = $m3u_dir .'/'. $v['file_name'];
            }elseif (isset($v['pl_url'])){
                $m3u_files[$v['pl_url']] = $v['pl_url'];
            }else{
                foreach (glob('{'.$m3u_dir.'/*.[mM]3[uU]}', GLOB_BRACE) as $file)
                        $m3u_files[$file]= $file;
                foreach (glob('{'.$m3u_dir.'/*.[mM]3[uU]8}', GLOB_BRACE) as $file)
                        $m3u_files[$file]= $file;
                if (file_exists($m3u_dir . '/pls.txt')){
                    $pls_lines = file($m3u_dir . '/pls.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($pls_lines as $item)
                        $m3u_files[$item] = $item;
                }
            }
        }
        return $m3u_files;
    }

    public static function get_vsetv_epg($channel_id, $day_start_ts)
    {
        $epg = array();
        $epg_date = date("Y-m-d", $day_start_ts);
        if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts))
            $epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
        else {
            try {
                $doc = iconv('WINDOWS-1251', 'UTF-8', self::http_get_document("http://www.vsetv.com/schedule_channel_".$channel_id."_day_".$epg_date."_nsc_1.html"));
                static $arr = null;
                if (is_null($arr))
                {
                    $docs = HD::http_get_document("http://www.vsetv.com/jquery-gs.js");
                    preg_match('|url: "(.*?)"|', $docs, $matches);
                    $opts [CURLOPT_HTTPHEADER] = array
                    (
                        'Accept: */*',
                        'Accept-Encoding: gzip, deflate',
                        'X-Requested-With: XMLHttpRequest',
                    );
                    $jsds = json_decode(HD::http_post_document("http://www.vsetv.com/".$matches[1],"",$opts));
                    if (preg_match_all('|(.*?)=myData\.(.*?);|', $docs, $match)){
                        foreach($match[2] as $k => $v)
                            $jsd[trim ($match[1][$k])] = $jsds->$v;
                    }
                    preg_match_all('|\((.*?)\)\.replaceWith\("(.*?)"\);|', $docs, $matches);
                    foreach ($matches[1] as $k => $v){
                        $doc = str_replace(str_replace('.', "class=", $jsd[$v]).">", ">".$matches[2][$k], $doc);
                        $arr[str_replace('.', "class=", $jsd[$v]).">"] = ">".$matches[2][$k];
                    }
                }else{
                    foreach ($arr as $k => $v)
                        $doc = str_replace($k, $v, $doc);
                }
            }
            catch (Exception $e) {
                hd_print("Can't fetch EPG ID:$id DATE:$epg_date");
                return array();
            }
            $patterns = array("/<div class=\"desc\">/", "/<div class=\"onair\">/", "/<div class=\"pasttime\">/", "/<div class=\"time\">/", "/<br><br>/", "/<br>/", "/&nbsp;/");
            $replace = array("|", "\n", "\n", "\n", ". ", ". ", "");
            $doc = strip_tags(preg_replace($patterns, $replace, $doc));
            preg_match_all("/([0-2][0-9]:[0-5][0-9])([^\n]+)\n/", $doc, $matches);
            $last_time = 0;
            foreach ($matches[1] as $key => $time) {
                $str = preg_split("/\|/", $matches[2][$key], 2);
                $name = $str[0];
                $desc = array_key_exists(1, $str) ? $str[1] : "";
                $u_time = strtotime("$epg_date $time EEST");
                $last_time = ($u_time < $last_time) ? $u_time + 86400 : $u_time;
                $epg[$last_time]["name"] = $name;
                $epg[$last_time]["desc"] = $desc;
            }
            file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));
        }
        ksort($epg, SORT_NUMERIC);
        return $epg;
    }

    public static function load_prov_info()
    {
        $ch_ops[0] = 'Не выбрано';
        $country = self::load_location_info();
        $path = 'http://dune-club.info/plugins/update/altiptv3/altiptv_prov_country.xml';
        $doc = file_get_contents($path);
        if ($doc === false)
            return false;
        $xml = simplexml_load_string($doc);
        if ($xml === false){
            hd_print("Error: can not parse XML document. XML-text: $doc.");
            return false;
        }
        if (isset($xml->$country))
        foreach ($xml->$country->children() as $xml_tv_playlist){
            $caption = (strval($xml_tv_playlist->caption));
            $desc = (strval($xml_tv_playlist->desc));
            $playlist_url = (strval($xml_tv_playlist->playlist_url));
            $ch_ops[$playlist_url] = "$caption [$desc]";
        }
        foreach ($xml->tv_items->children() as $xml_tv_playlist){
            $caption = (strval($xml_tv_playlist->caption));
            $desc = (strval($xml_tv_playlist->desc));
            $playlist_url = (strval($xml_tv_playlist->playlist_url));
            $ch_ops[$playlist_url] = "$caption [$desc]";
        }
        return $ch_ops;
    }

    public static function translit($str) {
    $str = preg_replace('/[-`~!#$%^&*()_=+\\\\|\\/\\[\\]{};:"\',<>?]+/','',$str);
    $rus = array(' ','А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
    $lat = array('_','A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
    return str_replace($rus, $lat, $str);
    }

    public static function load_location_info()
    {
        $country = "";
        if (is_file("/tmp/location_info.properties"))
            $location_info_path = "/tmp/location_info.properties";
        else if (is_file("/config/location_info.properties"))
            $location_info_path = "/config/location_info.properties";
        else
            return;
        $lines = file($location_info_path,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line)
        {
            if (preg_match('/^country =\s*(\S.*\S)\s*$/', $line, $matches))
            {
                $country = $matches[1];
                if (preg_match('/(\S[^,]*)\s*,/', $country, $matches))
                    $country = $matches[1];
                $country = str_replace(" ", "_", $country);
                hd_print("Using country: " . $country);
                return $country;
            }
        }
    }
}


?>
