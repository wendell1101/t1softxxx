<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * PopCash api
 *
 *
 * @version		1.0.0
 */

class Popcash_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('popcash'));
    }

    public function regPostBack($clickid, $player, $extra_info = null)
    {
        $player = (array)$player;
        $reg_postback_setting =  $this->_options['reg'];
        $post_back_data = array(
            'aid' => $reg_postback_setting['aid'],
            'clickid' => $clickid,
            // 'payout' => '',
        );
        return $this->doPost($post_back_data, $player);
    }

    public function depositPostBack($clickid, $player, $deposit_order=null)
    {
        $this->utils->debug_log("============3rdparty_affiliate_network_first_deposit_postback:[Popcash_api]============");
        $player = (array)$player;
        $post_back_data = array(
            'aid' => $this->getOptions('aid'),
            'clickid' => $clickid,
            // 'payout' => $deposit_order,
        );
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
        // return true;
    }
}
