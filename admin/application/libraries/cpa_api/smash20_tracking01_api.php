<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * propellerads api for smash20_tracking01
 *
 *
 * @version		1.0.0
 */

class Smash20_tracking01_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('smash20_tracking01'));
    }

    public function regPostBack($clickid, $player, $extra_info = null)
    {
        // http://ad.propellerads.com/conversion.php?aid=3501349&pid=&tid=98305&visitor_id=${SUBID}
        $this->utils->debug_log("============3rdparty_affiliate_network_register_postback:[Smash20_tracking01_api]============");
        $reg_postback_setting =  $this->_options['reg'];
        $post_back_data = array(
            'aid'       => $reg_postback_setting['aid']?:'',
            'pid'       => $reg_postback_setting['pid']?:'',
            'tid'       => $reg_postback_setting['tid']?:'',
            'visitor_id'=> $clickid
        );
        return $this->doPost($post_back_data, $player);
    }
}
