<?php
/**
 * player_main_js_addons_player.php
 *
 * @author Elvis Chen
 */
class Player_main_js_addons_player extends Player_main_js_addons_abstract {
    public function isEnabled(){
        return TRUE;
    }

    public function variables(){
        $logged = $this->CI->authentication->isLoggedIn();

        $playerId = 0;
        $playerUsername = null;
        $playerInfo = [
            'firstName' => ''
        ];
        $VIP_group = null;
        $messageCount = 0;

        $walletInfo = [];

        //

        $token = null;
        $p2p_chat_html = null;
        $p2p_chat_url = null;
        if ($logged) {
            $this->CI->session->unset_userdata('httpHeaderInfo');

            $playerId = $this->CI->authentication->getPlayerId();
            $playerUsername = $this->CI->authentication->getUsername();
            $playerInfo = $this->CI->utils->get_player_info($playerId);
            $VIP_group = $this->CI->authentication->getPlayerMembership();
            $VIP_level = $this->CI->authentication->getPlayerCurrentLevel();
            // $p2p_chat_html = '';
            $p2p_chat_url = $this->CI->utils->getChatAsyncUrl();

            $walletInfo = $this->CI->utils->getSimpleBigWallet($playerId);
            $messageCount = $this->CI->internal_message->countPlayerUnreadMessages($playerId);

            if ($this->CI->utils->getConfig('enabled_new_broadcast_message_job')) {
                $this->CI->load->library('player_message_library');
                $broadcast_messages = $this->CI->player_message_library->getPlayerAllBroadcastMessages($playerId, $playerInfo['playerCreatedOn']);
                 $this->CI->utils->debug_log('broadcast_messages',$broadcast_messages);
                if (!empty($broadcast_messages)) {
                    $messageCount = count($broadcast_messages) + $messageCount;
                }
            }

            if(!empty($playerId)){
                $token = $this->CI->common_token->getPlayerToken($playerId);
            }

            $roulette_setting = $this->CI->config->item('roulette_reward_odds_settings');
            if(is_array($roulette_setting)){
                $roulette_remain_times = 0;
                foreach ($roulette_setting as $key => $value) {

                    $api_name = 'roulette_api_' . $key;
                    $classExists = file_exists(strtolower(APPPATH.'libraries/roulette/'.$api_name.".php"));
                    if (!$classExists) {
                        continue;
                    }
                    $this->CI->load->library('roulette/'.$api_name);
                    $roulette_api = $this->CI->$api_name;

                    if (!$roulette_api) {
                        continue;
                    }
                    $roulette_to_cmsid_pair = $this->CI->config->item('roulette_to_cmsid_pair');
                    if(isset($roulette_to_cmsid_pair[$key])){
                        $promo_cms_id = $roulette_to_cmsid_pair[$key];
                    } else{
                        continue;
                    }
                    $roulette_res = $roulette_api->generateRouletteSpinTimes($playerId, $promo_cms_id);
                    if ($roulette_res['success'] == false) {
                        continue;
                    }
                    $roulette_remain_times = $roulette_remain_times + $roulette_res['spin_times_data']['remain_times'];
                }
            }
        }

        $total_balance = isset($walletInfo['total_balance']['balance']) ? $walletInfo['total_balance']['balance'] : 0;
        $total_withfrozen = isset($walletInfo['total_withfrozen']) ? $walletInfo['total_withfrozen'] : 0;

        $variables = [
            'currentLang' => $this->CI->language_function->getCurrentLanguage(),
            'currentLangName' => $this->CI->language_function->getCurrentLanguageName(),
            'is_mobile' => $this->CI->utils->is_mobile(),
            'logged' => $logged,
            'playerId' => $playerId,
            'playerUsername' => $playerUsername,
            'walletInfo' => $walletInfo,
            'main_wallet_id' => Wallet_model::MAIN_WALLET_ID,
            'token' => $token,
            'VIP_group' => $VIP_group,
            'firstName' => $playerInfo['firstName'],
            'ui' => [
                'withdraw_password' => empty($playerInfo['withdraw_password']) ? '' : '1',
                'total_balance' => $total_balance,
                'total_hasfrozen' => $total_withfrozen,
                'messageCount' => $messageCount,
				'popupBanner' => $this->CI->utils->getActivePopupBanner($playerId),
                'deadsimplechat' => $p2p_chat_html,
                'p2p_chat_url' => $p2p_chat_url
            ],
            'templates' => [
                'player_active_profile_picture' => $this->CI->utils->getPlayerActiveProfilePicture($playerId),
            ],
            'player_center' => [
                'player_auto_lock' => $this->CI->operatorglobalsettings->getSettingIntValue('player_auto_lock', 0),
                'player_auto_lock_time_limit' => $this->CI->operatorglobalsettings->getSettingIntValue('player_auto_lock_time_limit', 600),
                'player_auto_lock_password_failed_attempt' => $this->CI->operatorglobalsettings->getSettingIntValue('player_auto_lock_password_failed_attempt', $this->CI->utils->getUploadConfig('player_auto_lock_password_failed_attempt')),
                'player_auto_lock_window_auto_logout' => $this->CI->config->item('player_auto_lock_window_auto_logout'),
                'locale' => [
                    'player_auto_lock_window_header' => lang('player_auto_lock_window_header'),
                    'player_auto_lock_window_auto_logout' => lang('player_auto_lock_window_auto_logout'),
                    'player_auto_lock_window_submit' => lang('player_auto_lock_window_submit')
                ]
            ]
        ];

        if(isset($roulette_remain_times)){
            $variables['ui']['rouletteRemainTimes'] = $roulette_remain_times;
        }

        $captcha_of_3rdparty = $this->CI->config->item('enabled_captcha_of_3rdparty');
        if (!empty($captcha_of_3rdparty)) {
            unset($captcha_of_3rdparty['secret']);
        }
        $variables['ui']['captcha_3rdparty'] = $captcha_of_3rdparty;

        return $variables;
    }
}