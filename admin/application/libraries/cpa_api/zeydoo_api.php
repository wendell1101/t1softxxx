<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Zeydoo api
 *
 *
 * @version		1.0.0
 */

class Zeydoo_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('zeydoo'));
    }

    public function regPostBack($clickid, $player, $extra_info = null)
    {
        // http://ad.propellerads.com/conversion.php?aid=3461132&pid=&tid=90829&visitor_id={xxxx}&price={yyyy}&goal=1
        $this->utils->debug_log("============3rdparty_affiliate_network_register_postback:[Zeydoo_api]============");
        $reg_postback_setting =  $this->_options['reg'];
        $post_back_data = array(
            'aid'       => $reg_postback_setting['aid']?:'',
            'pid'       => $reg_postback_setting['pid']?:'',
            'tid'       => $reg_postback_setting['tid']?:'',
            'visitor_id'=> $clickid,
            'price'    => $reg_postback_setting['payout']?:0,
            'goal'      => $reg_postback_setting['goal']?:1,
        );
        return $this->doPost($post_back_data, $player);
    }

    public function depositPostBack($clickid, $player, $deposit_order=null)
    {
        //http://ad.propellerads.com/conversion.php?aid=3461132&pid=&tid=90829&visitor_id={xxxx}&price={yyyy}&goal=2
        $this->utils->debug_log("============3rdparty_affiliate_network_first_deposit_postback:[Zeydoo_api]============");
        $reg_postback_setting =  $this->_options['ftd'];
        $post_back_data = array(
            'aid'       => $reg_postback_setting['aid']?:'',
            'pid'       => $reg_postback_setting['pid']?:'',
            'tid'       => $reg_postback_setting['tid']?:'',
            'visitor_id'=> $clickid,
            'price'    => $reg_postback_setting['payout']?:0,
            'goal'      => $reg_postback_setting['goal']?:2,
        );
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
        // return true;
    }
}
