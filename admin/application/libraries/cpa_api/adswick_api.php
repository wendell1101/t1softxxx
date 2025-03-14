<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Adswick api
 *
 *
 * @version		1.0.0
 */

class Adswick_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('adswick'));
    }

    public function regPostBack($clickid, $player, $extra_info = null)
    {
        $post_back_data = array('s4'=>$clickid);
        return $this->doPost($post_back_data, $player);

    }

    public function depositPostBack($clickid, $player, $deposit_order=null)
    {
        $this->utils->debug_log("============3rdparty_affiliate_network_first_deposit_postback:[Adswick_api]============");
        // $post_back_data = array('clickid'=>$clickid);
        // return $this->doPost($post_back_data);
        return true;
    }
}
