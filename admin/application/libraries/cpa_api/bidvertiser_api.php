<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Bidvertiser api
 * doc: https://zendesk.bidvertiser.com/hc/en-us/articles/360009414399-Global-Postback
 * postback_url: http://secure.bidvertiser.com/performance/pc.dbm?ver=1.0&AID=359734248&CLICKID={BV_CLICKID}&revenue={REVENUE}
 *
 * @version		1.0.0
 */

class Bidvertiser_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('bidvertiser'));
    }

    public function regPostBack($clickid, $player, $extra_info = null)
    {
        $this->utils->debug_log("============3rdparty_affiliate_network_register_postback:[Bidvertiser_api]============");
        $player = (array)$player;
        if (!empty($player['affiliateId'])) {
            $this->getSpecificAffiliateSetting($player['affiliateId']);
        }
        $postback_value =  $this->_options['reg'];

        $ver = $postback_value['ver'];
        $aid = $postback_value['aid'];
        $revenue = $postback_value['revenue'];
        $post_back_data = array(
            'ver' => $ver,
            'AID' => $aid,
            'CLICKID' => $clickid,
            'revenue' => $revenue
        );
        return $this->doPost($post_back_data, $player);
    }

    public function depositPostBack($clickid, $player, $deposit_order=null)
    {
        $this->utils->debug_log("============3rdparty_affiliate_network_first_deposit_postback:[Bidvertiser_api]============");
        $player = (array)$player;
        if (!empty($player['affiliateId'])) {
            $this->getSpecificAffiliateSetting($player['affiliateId']);
        }
        $postback_value =  $this->_options['ftd'];

        $ver = $postback_value['ver'];
        $aid = $postback_value['aid'];
        $revenue = $postback_value['revenue'];
        $post_back_data = array(
            'ver' => $ver,
            'AID' => $aid,
            'CLICKID' => $clickid,
            'revenue' => $revenue
        );
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);

        // return true;
    }

}
