<?php


require_once 'lib/tv/default_channel.php';



class CurentTimeChannel extends DefaultChannel
{
    private $number;
	private $_desc;
    private $past_epg_days;
    private $future_epg_days;
    private $epg_url;
	private $has_archive;


    public function __construct($id, $title, $desc, $icon_url, $streaming_url, $number, $past_epg_days, $future_epg_days, $has_archive)
    {
        parent::__construct($id, $title, $icon_url, $streaming_url);

        $this->number = $number;
		$this->_desc = $desc;
        $this->past_epg_days = $past_epg_days;
        $this->future_epg_days = $future_epg_days;
        $this->has_archive = $has_archive;
    }



    public function get_number()
    { return $this->number; }

	public function get_desc()
    { return $this->_desc; }

    public function get_past_epg_days()
    { return $this->past_epg_days; }

    public function get_future_epg_days()
    { return $this->future_epg_days; }

    public function get_epg_url()
    { return $this->epg_url; }

	public function has_archive()
    { return $this->has_archive; }

	public function get_timeshift_hours() {
		//hd_print('inside get_timeshift_hours, returning 0');
		return 0;
	}


}


?>
