<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Dummy track api
 *
 *
 * @version		1.0.0
 */

class Dummy_track_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('dummy_track'));
    }

    public function regPostBack($clickid, $player, $extra_info = null)
    {
        $player = (array)$player;
        $username = $player['username'];
        if(!empty($player['affiliateId'])) {
            $this->getSpecificAffiliateSetting($player['affiliateId']);
        }
        $post_back_data = array(
            'clickid' => $clickid,
            'q1' => $this->getOptions('q1'),
            'payout' => $this->getOptions('payout'),
        );
        return $this->doPost($post_back_data, $player);
    }

    public function depositPostBack($clickid, $player, $deposit_order=null)
    {
        $this->utils->debug_log("============3rdparty_affiliate_network_first_deposit_postback:[Dummy_track_api]============");
        $goal = $this->getOptions('deposit_goal')?:2;
        $revenue = $this->getOptions('deposit_revenue')?:0;
        $post_back_data = array('esub'=>$clickid, 'goal'=>$goal, 'revenue'=>$revenue);
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
        // return true;
    }
}
