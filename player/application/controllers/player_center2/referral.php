<?php
require_once 'PlayerCenterBaseController.php';

/**
 * Provides referral function
 *
 */
class Referral extends PlayerCenterBaseController{
    public function __construct(){
        parent::__construct();

        $this->load->vars('content_template', 'default_with_menu.php');
        $this->load->vars('activeNav', 'referral');
    }

    public function index(){
        $player_id = $this->load->get_var('playerId');

        $this->load->model(array('friend_referral_settings', 'player_model'));

        $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
        if( ! empty($enable_OGP19808) ){
            $result4fromLine = $this->player_model->check_playerDetail_from_line($player_id);
            if($result4fromLine['success'] === false ){
                if( $this->utils->is_mobile() ){
                    $url = site_url( $this->utils->getPlayerProfileUrl() );
                }else{
                    $url = site_url( $this->utils->getPlayerProfileSetupUrl() );
                }
                return redirect($url);
            }
        }


        $friend_referral_settings = $this->friend_referral_settings->getFriendReferralSettings();

        $friend_referral_settings['referralDetails'] = trim($this->decodePromoDetailItem($friend_referral_settings['referralDetails']));
        $data['friend_referral_settings'] = $friend_referral_settings;

        $data['friendRefExtraInfo'] = false;
        $data['enableFriendRefExtraInfo'] = false;
        $enableFriendRefExtraInfo = $this->utils->getConfig('enable_friend_referral_extra_info');
        if($enableFriendRefExtraInfo) {
            $referrer_bonus_rate = isset($friend_referral_settings['bonusRateInReferrer']) ? $friend_referral_settings['bonusRateInReferrer'] : 0;
            $data['enableFriendRefExtraInfo'] = true;
            $data['extraInfoTitle'] = lang('ExtraInfo.title');
            $friendRefExtraInfo = $this->getFriendReferralExtraInfo($player_id);
            $data['friendRefExtraInfo'] = json_encode($friendRefExtraInfo);
            if($this->utils->getConfig('enabled_referrer_bonus_rate') && $referrer_bonus_rate!=0){
                $data['extraInfoTitle'] = sprintf(lang('friendReferral.extraInfoTitle_rate'), $referrer_bonus_rate);
            }else{
                $data['extraInfoTitle'] = sprintf(lang('friendReferral.extraInfoTitle'), $this->utils->safeGetArray($friend_referral_settings, 'bonusAmount', '0'));
            }
        }

        $data['enableFriendRefMobileShare'] = false;
        $data['friendRefMobileSharingTitle'] = '';
        $data['friendRefMobileSharingText'] = '';
        $enableFriendRefMobileShare = $this->utils->getConfig('enable_friend_referral_mobile_share') && $this->utils->is_mobile();
        if($enableFriendRefMobileShare){
            $data['enableFriendRefMobileShare'] = true;
            $data['friendRefMobileSharingTitle'] = empty($this->utils->getConfig('friendRefMobileSharingTitle')) ? lang('friendReferral.friendRefMobileShare.sharingTitle') : $this->utils->getConfig('friendRefMobileSharingTitle');
            $data['friendRefMobileSharingText'] =  empty($this->utils->getConfig('friendRefMobileSharingText')) ? lang('friendReferral.friendRefMobileShare.sharingText') : $this->utils->getConfig('friendRefMobileSharingText');
        }

        $this->loadTemplate();
        $this->template->append_function_title(lang('Refer a Friend'));
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/player/referral', $data);
        $this->template->render();
    }
    public function getFriendReferralExtraInfo($player_id = null){

        if(empty($player_id)){
            return false;
        }
        $username = $this->load->get_var('username');

        // Player_friend_referral
        $this->load->model(array('Player_friend_referral'));
        // getPlayerReferral
        $countAllPlayerReferral = $this->Player_friend_referral->getPlayerReferral($player_id);
        $countPaidPlayerReferral = $this->Player_friend_referral->getPlayerReferral($player_id, Player_friend_referral::STATUS_PAID);

        // getTotalReferralBonusByPlayerId
        $totalReferralBonus = $this->Player_friend_referral->getTotalReferralBonusByPlayerId($player_id);
        $this->utils->debug_log('getFriendReferral ', [
            'countAllPlayerReferral' => $countAllPlayerReferral,
            'countPaidPlayerReferral' => $countPaidPlayerReferral,
            'totalReferralBonus' => $totalReferralBonus,
        ]);
        return [
            'username' => ['title' => lang('User'), 'value' => $username],
            'countReferral' => ['title' => lang('friendReferral.countReferral'), 'value' => count($countAllPlayerReferral)],
            'countAvlibleReferral' => ['title' => lang('friendReferral.countAvlibleReferral'), 'value' => count($countPaidPlayerReferral)],
            'earnedBonus' => ['title' => lang('friendReferral.earnedBonus'), 'value' => $totalReferralBonus],
        ];
    }
    public function gotoShop(){
        $this->load->model(array('player_model'));

        $url = $this->utils->getSystemUrl('player', '/player_center/dashboard/index#shop', false);
        $player_id = $this->load->get_var('playerId');
        $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
        if( ! empty($enable_OGP19808) ){
            $result4fromLine = $this->player_model->check_playerDetail_from_line($player_id);
            if($result4fromLine['success'] === false ){
                if( $this->utils->is_mobile() ){
                    $url = site_url( $this->utils->getPlayerProfileUrl() );
                }else{
                    $url = site_url( $this->utils->getPlayerProfileSetupUrl() );
                }
            }else{
                $url = site_url($this->utils->getPlayerProfileSetupUrl());
            }
        } // EOF if( ! empty($enable_OGP19808) ){...
            $custom_shop_ui = $this->utils->getConfig('custom_shop_ui');
            $this->loadTemplate();
            $this->template->append_function_title(lang('Refer a Friend'));
            if($custom_shop_ui === false) {
                $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/includes/dashboard/shop');
            }
            else {
                $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/includes/dashboard/' . $custom_shop_ui . '/shop');
            }
            $this->template->render();
    }

    
}