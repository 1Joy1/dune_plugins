<?

class CurentTimeSleepTimer
{
    const CRON_FILE = '/tmp/cron/crontabs/root';

    private static function save_cron_file($data, $mode="w") {
        if($mode !== "a" && $mode !== "w"){
            throw new Exception("Save_cron_file:: Mode (2-arg) not valid. Mode must be 'w' or 'a', bun not '$mode'.");
        }
        $cron_data = fopen(self::CRON_FILE, $mode);
        if (!$cron_data)
            hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
        fwrite($cron_data, $data);
        @fclose($cron_data);
        chmod(self::CRON_FILE, 0575);

        return true;
    }


    private static function del_or_set_cron_data($doc, $save_cron = "") {
        $tmp = explode('#*#*#', $doc);
        if (count($tmp) > 1) {
            $sleep_old = strstr($tmp[1], '#-#-#', true);
        }
        $sleep_old = "\n#*#*#" . $sleep_old . "#-#-#";
        $data = str_replace($sleep_old, $save_cron, $doc);

        return $data;
    }

    private static function get_reserved_sleep_timer_from_cron_file() {
        $sleep_time = '';
        $doc = file_get_contents(self::CRON_FILE);
        if (preg_match('|Выключение в:(.*)\+|', $doc, $match)) {
            $sleep_time = $match[1];
        }
        return $sleep_time;
    }

    private static function get_time_shift() {
        $docs = file_get_contents('/config/settings.properties');
        if (preg_match('/time_zone =(.*)\s/', $docs, $match)) {
            $tmp = explode(':', $match[1]);
            $time_shift = ($tmp[0] * 3600 ) + ($tmp[1] * 60 );
        } else {
            $time_shift = 0;
        }
        return $time_shift;
    }



    public static function sleep_timer_init() {
        $sleep_time = self::get_reserved_sleep_timer_from_cron_file();

        //Если у крона уже есть задание, определяем на какое оно время, в формате unixtime
        if (preg_match('/\[(\d\d)\:(\d\d)\] \[(\d\d)\-(\d\d)\]/', $sleep_time, $matches)) {
            $doc = file_get_contents(self::CRON_FILE);
            $time_shift = self::get_time_shift();
            $year =  date("y");
            $timestamp = mktime($matches[1], $matches[2], 0, $matches[4], $matches[3], $year);
            $unix_time = time() - $time_shift;


            //Проверяем не поставленно ли это задание на прошлое, если да то удаляем это задание из крона
            if($timestamp < $unix_time) {
                if (preg_match('/Выключение/i', $doc)){
                    $data = self::del_or_set_cron_data($doc);
                    self::save_cron_file($data);
                }
                shell_exec('crontab -e');
                $sleep_time = '';
            }
        }
        return $sleep_time;
    }


    public static function reset_unix_time_tmp_file() {
        $sleep_time = self::get_reserved_sleep_timer_from_cron_file();
        HD::save_item_tmp('unix_time', 0);
        return $sleep_time;
    }


    public static function clear_sleep_timer() {
        $doc = file_get_contents(self::CRON_FILE);
        if (preg_match('/Выключение/i', $doc)) {
            $data = self::del_or_set_cron_data($doc);
            self::save_cron_file($data);
            HD::save_item_tmp('unix_time', 0);
            shell_exec('crontab -e');
            return true;
        }
        return false;
    }


    public static function sleep_timer_set($user_input) {
        $sleep_time_hour = $user_input->sleep_time_hour;

        if ($sleep_time_hour == 0) {
            return null;
        }
        $sleep_time_hour = $sleep_time_hour * 3600;
        $time_shift = self::get_time_shift();
        $doc = file_get_contents(self::CRON_FILE);

        $unix_time = time() - $time_shift + $sleep_time_hour;
        $date = date("m-d H:i:s" , $unix_time);
        $day_s = date("d", $unix_time);
        $mns_s = date("m", $unix_time);
        $hrs_s = date("H", $unix_time);
        $min_s = date("i", $unix_time);
        $unix_time = time() + $sleep_time_hour;
        HD::save_item_tmp('unix_time', $unix_time);
        $date = date("m-d H:i:s" , $unix_time);
        $day_s1 = date("d", $unix_time);
        $mns_s1 = date("m", $unix_time);
        $hrs_s1 = date("H", $unix_time);
        $min_s1 = date("i", $unix_time);
        $save_cron = "\n#*#*# Выключение в: [$hrs_s:$min_s] [$day_s-$mns_s] + \n$min_s1 $hrs_s1 $day_s1 $mns_s1 * wget --quiet -O - \"http://127.0.0.1/cgi-bin/do?cmd=standby\"\n#-#-#";

        if (preg_match('/Выключение/i', $doc)){
            $data = self::del_or_set_cron_data($doc, $save_cron);
            self::save_cron_file($data);
        }else{
            self::save_cron_file($save_cron, "a");

        }
        shell_exec('crontab -e');
        return null;
    }


    public static function get_sleep_timer_ops() {
        return array (
                    '0' => 'Установите',
                    '0.05' => '3 минуты',
                    '0.25' => '15 минут',
                    '0.5' => '30 минут',
                    '0.75' => '45 минут',
                    '1' => '1 час',
                    '1.25' => '1 час 15 минут',
                    '1.5' => '1 час 30 минут',
                    '1.75' => '1 час 45 минут',
                    '2' => '2 часа',
                    '2.5' => '2 часа 30 минут',
                    '3' => '3 часа',
                    '3.5' => '3 часа 30 минут',
                    '4' => '4 часа',
                    '4.5' => '4 часа 30 минут',
                    '5' => '5 часов',
                    '5.5' => '5 часов 30 минут',
                    '6' => '6 часов',
                    );
    }
}

?>