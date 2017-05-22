<?

class Logger {

    const DEBUG_MODE = false;

    public static function log($debug) {

        if (self::DEBUG_MODE == true) {

            if(is_object($debug) || is_array($debug)) {
                $str = print_r($debug,1);
            }
            else{
                $str = $debug;
            }
            $backtrace = debug_backtrace();

            $time_shift = self::get_time_shift();

            $str = "\n\nTime: " . date('Y-m-d H:i:s', time() - $time_shift).
                   "\n    File: " . $backtrace[1]["file"].
                   "\n    Function: " . $backtrace[1]["function"]."()".
                   "\n    Line: " . $backtrace[1]["line"].
                   "\n(".gettype($debug).")\n".$str;

            file_put_contents(dirname(__FILE__).'/debug.log', $str, FILE_APPEND);
        }
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
}

?>