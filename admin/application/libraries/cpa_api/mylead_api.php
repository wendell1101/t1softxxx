<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * MyLead api
 * OGP-27924
 * doc:https://docs.google.com/document/d/1a0qptkpIGm3CurZkRnEa8JVFPXZJ_lCRBp33M7BAhu4/edit
 *
 * @version 1.0.0 mylead
 */

class Mylead_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('mylead'));
    }

    public function regPostBack($clickId, $player, $extra_info = null)
    {
        if (!empty($_COOKIE['reg_track'])) {
            $reg_track_cookie = json_decode($_COOKIE['reg_track'], true);
        }
        $this->utils->debug_log("============3rdparty_tracking_register_postback:[Mylead_api]============");
        $postback_setting =  !empty($this->_options['reg']) ? $this->_options['reg'] : false;
        if($postback_setting) {
            $post_back_data = array(
                'nid'    => $postback_setting['nid'],
                'transaction_id' => $clickId,
            );

            return $this->doPost($post_back_data, $player);
        } 
        return false;
    }

    public function depositPostBack($clickId, $player, $deposit_order = null)
    {
        $this->utils->debug_log("============3rdparty_tracking_first_deposit_postback:[Mylead_api]============");
        $sourceDetail = $this->getAffSourceDetail($player);
        $postback_setting =  !empty($this->_options['ftd']) ? $this->_options['ftd'] : false;
        if($postback_setting) {
            $post_back_data = array(
                'nid'    => $postback_setting['nid'],
                'event_id'  => $postback_setting['event_id'],
                'transaction_id' => $clickId,
            );
            return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
        }
        return false;
    }

    private function getAffSourceDetail($player)
    {
        $playerId = $player['playerId'];
        $this->CI->load->model(['player_model']);
        $playerInfo = $this->CI->player_model->getPlayerDetailArrayById($playerId);
        if (!empty($playerInfo['cpaId'])) {
            $aff_source_detail = json_decode($playerInfo['cpaId'], true);
            $this->utils->debug_log("============3rdparty_affiliate_network_detail============", $aff_source_detail);
            return $aff_source_detail;
        }
        return [];
    }
}
