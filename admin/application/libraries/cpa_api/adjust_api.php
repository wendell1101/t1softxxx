<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Adjust api
 * OGP-27040
 * doc: https://help.adjust.com/zh/article/s2s-api-reference
 *
 *
 * @version		1.0.0
 */

class Adjust_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('adjust'));
    }

    public function regPostBack($clickId, $player, $extra_info = null)
    {
        if (!empty($_COOKIE['reg_track'])) {
            $reg_track_cookie = json_decode($_COOKIE['reg_track'], true);
        }
        // http://ad.propellerads.com/conversion.php?aid=3461132&pid=&tid=90829&visitor_id={xxxx}&price={yyyy}&goal=1
        $this->utils->debug_log("============3rdparty_tracking_register_postback:[Adjust_api]============");
        $postback_setting =  !empty($this->_options['reg']) ? $this->_options['reg'] : false;
        if($postback_setting) {
            $eventName = isset($postback_setting['eventName']) ? $postback_setting['eventName']: '';
            $tokenName = isset($postback_setting['tokenName'])? $postback_setting['tokenName']: '';
            $deviceKeyName = isset($postback_setting['deviceKeyName'])? $postback_setting['deviceKeyName']: 'gps_adid';

            $post_back_data = array(
                's2s'        => 1,
                $deviceKeyName => $clickId,
                'event_token' => isset($reg_track_cookie[$eventName]) ? $reg_track_cookie[$eventName] : '',
                'app_token'  => isset($reg_track_cookie[$tokenName]) ? $reg_track_cookie[$tokenName] : '',
            );
            if(isset($postback_setting['environment'])) {
                $post_back_data['environment'] = $postback_setting['environment'];
            }
            return $this->doPost($post_back_data, $player);
        } 
        return false;
    }

    public function depositPostBack($cpaInfo, $player, $deposit_order = null)
    {
        //http://ad.propellerads.com/conversion.php?aid=3461132&pid=&tid=90829&visitor_id={xxxx}&price={yyyy}&goal=2
        $this->utils->debug_log("============3rdparty_tracking_first_deposit_postback:[Adjust_api]============");
        $sourceDetail = $this->getAffSourceDetail($player);
        $postback_setting =  !empty($this->_options['ftd']) ? $this->_options['ftd'] : false;
        if($postback_setting) {
            $eventName = isset($postback_setting['eventName']) ? $postback_setting['eventName']: '';
            $tokenName = isset($postback_setting['tokenName'])? $postback_setting['tokenName']: '';
            $currency = isset($postback_setting['currency'])? $postback_setting['currency']: '';
            $deviceKeyName = isset($postback_setting['deviceKeyName'])? $postback_setting['deviceKeyName']: 'gps_adid';
            $revenue = $deposit_order['amount'];

            $post_back_data = array(
                's2s'        => 1,
                $deviceKeyName => isset($sourceDetail["clickid"]) ? $sourceDetail['clickid'] : '',
                'event_token' => isset($sourceDetail[$eventName]) ? $sourceDetail[$eventName] : '',
                'app_token'  => isset($sourceDetail[$tokenName]) ? $sourceDetail[$tokenName] : '',
                'currency' => $currency,
                'revenue' => $revenue,

            );
            if(isset($postback_setting['environment'])) {
                $post_back_data['environment'] = $postback_setting['environment'];
            }
            return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
        }
        return false;
    }

    public function everyTimeDepositPostBack($cpaInfo, $player, $status, $deposit_order = null)
    {
        //http://ad.propellerads.com/conversion.php?aid=3461132&pid=&tid=90829&visitor_id={xxxx}&price={yyyy}&goal=2
        $this->utils->debug_log("============3rdparty_tracking_first_deposit_postback:[Adjust_api]============");
        $sourceDetail = $this->getAffSourceDetail($player);
        $postback_setting =  !empty($this->_options['dep']) ? $this->_options['dep'] : false;
        if($postback_setting) {
            $eventName = isset($postback_setting['eventName']) ? $postback_setting['eventName']: '';
            $tokenName = isset($postback_setting['tokenName'])? $postback_setting['tokenName']: '';
            $currency = isset($postback_setting['currency'])? $postback_setting['currency']: '';
            $deviceKeyName = isset($postback_setting['deviceKeyName'])? $postback_setting['deviceKeyName']: 'gps_adid';
            $revenue = $deposit_order['amount'];

            $post_back_data = array(
                's2s'        => 1,
                $deviceKeyName => isset($sourceDetail["clickid"]) ? $sourceDetail['clickid'] : '',
                'event_token' => isset($sourceDetail[$eventName]) ? $sourceDetail[$eventName] : '',
                'app_token'  => isset($sourceDetail[$tokenName]) ? $sourceDetail[$tokenName] : '',
                'currency' => $currency,
                'revenue' => $revenue,
            );
            if(isset($postback_setting['environment'])) {
                $post_back_data['environment'] = $postback_setting['environment'];
            }
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
