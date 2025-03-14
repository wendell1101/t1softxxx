<?php
$_mobile_menu_list = [
    'Deposit' => [
        'id' => 'czxx',
        'content' => lang('Deposit'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accdeposit_icon',
        'link' => site_url('player_center2/deposit'),
        'images' => '/includes/images/money_icon.svg',
    ],
    'Withdraw' => [
        'id' => 'tkxx',
        'content' => lang('Withdraw'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accqukuan_icon',
        'link' => site_url('player_center2/withdraw'),
        'images' => '/includes/images/qukuan.svg',
    ],
    'Redemption_Code' => [
        'id' => 'Redemption_Code',
        'content' => lang('redemptionCode.redemptionCode'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'redemptionCode_icon',
        'link' => site_url('player_center2/redemption_code'),
        'images' => '/includes/images/redemption-code-icon.png',
    ],
    'Transfer' => [
        'id' => 'zzxx',
        'content' => lang('Transfer'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'acctransfer_icon',
        'link' => site_url('player_center/mobile_transfer'),
        'images' => '/includes/images/transfer_icon.svg',
    ],
    'Account_History' => [
        'id' => 'jlxx',
        'content' => lang($this->utils->getConfig('playercenter.mobile.allReportsName') ?: 'Account History'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accrecord_icon',
        'link' => site_url('player_center2/report'),
        'images' => '/includes/images/record_icon.svg',
    ],
    'Bank_Account' => [
        'id' => 'yhkxx',
        'content' => lang('Bank Account'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accbank_icon',
        'link' => site_url('player_center2/bank_account'),
        'images' => '/includes/images/bank_icon.svg',
    ],
    'Account_Information' => [
        'id' => 'usereditxx',
        'content' => lang($this->utils->getConfig('playercenter.mobile.profileName') ?: 'rainbow.Account Information'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'user_edit_icon',
        'link' => site_url('player_center/profile'),
        'images' => '/includes/images/edit-user.svg',
    ],
    'Promo_List' => [
        'id' => 'promosapply',
        'content' => lang('cms.mobile.promoReqAppList'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'promos_apply_icon',
        'link' => site_url('player_center2/promotion'),
        'images' => '/includes/images/promo_icon.svg',
    ],
    'Logout' => [
        'id' => 'logoutxx',
        'content' => lang('lang.logout'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'cashback_icon',
        'images' => '/includes/images/logout-icon.svg',
    ],
    'Refer_Friend' => [
        'id' => 'yhkxx',
        'content' => lang('Refer a Friend'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accbank_icon referal_icon',
        'link' => $this->utils->getPlayerReferralOnClick(),
        'images' => '/includes/images/friend-icon.svg',
        'system_feature' => 'enabled_player_referral_tab',
    ],
    'Security' => [
        'id' => 'security',
        'content' => lang('Security'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accbank_icon security_icon',
        'link' => site_url('player_center2/security'),
        'images' => '/includes/images/security-icon.svg',
    ],
    'Responsible_Gaming' => [
        'id' => 'responsible_gaming',
        'content' => lang('Responsible Gaming'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accbank_icon',
        'link' => site_url('player_center2/responsible_game'),
        'images' => '/includes/images/responsive_icon.svg',
        'system_feature' => 'responsible_gaming',
    ],
    'Cashback' => [
        'id' => 'realtime_cashback',
        'content' => lang('Realtime Cashback'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accbank_icon',
        'link' => site_url('player_center/cashback'),
        'images' => '/includes/images/cashback.svg',
        'system_feature' => 'mobile_player_center_realtime_cashback',
    ],
    'Live_Chat' => [
        'id' => 'live_chat',
        'content' => lang('customer.service.mobile.main.menu'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'accbank_icon chat_icon',
        'link' => $this->utils->getLiveChatOnClick(),
        'images' => '/includes/images/mobile_main_menu_live_chat.svg',
        'system_feature' => 'enable_player_center_mobile_main_menu_live_chat',
    ],
    'Shop' => [
        'id' => 'shop',
        'content' => lang('Shop'),
        'style' => 'cursor:pointer;',
        'td_class' => 'threepage',
        'li_class' => 'cashback_icon',
        'link' => site_url('player_center2/shop'),
        'images' => '/includes/images/shop-icon.png',
        'system_feature' => 'enable_shop',
    ],

];


$hide_transfer_tab_in_player_center = $this->config->item('hide_transfer_tab_in_player_center');
if($hide_transfer_tab_in_player_center){
    if( ! empty($_mobile_menu_list['Transfer']) ){
        unset($_mobile_menu_list['Transfer']);
    }
}

$row = 0;
$col = $this->config->item('mobile_main_menu_col');
$logout_param = [];
$mobile_menu_list = [];
$mobole_menu_suspend_list = ['Account_History', 'Account_Information', 'Promo_List'];
$player_is_suspend = $this->utils->getPlayerStatus($player['playerId']) == Player_model::SUSPENDED_STATUS;

$mobile_player_center_custom_buttons = $this->utils->getConfig('mobile_player_center_custom_buttons');
$exclude_mobile_player_center_menu_list = $this->utils->getConfig('exclude_mobile_player_center_menu_list');

if(!empty($mobile_player_center_custom_buttons)){
    foreach($mobile_player_center_custom_buttons as $custom_title => &$custom_params){
        // if (isset($params['system_feature']) && !$this->utils->isEnabledFeature($params['system_feature'])) {
        //     continue;
        // }

        //convert
        $custom_params['content'] = lang($custom_params['lang_text']);
        //link
        $custom_params['link'] = str_replace(
            ['{www}', '{m}', '{player}'],
            [$this->utils->getSystemUrl('www'), $this->utils->getSystemUrl('m'), $this->utils->getSystemUrl('player')],
            $custom_params['link']);

        $_mobile_menu_list[$custom_title] = $custom_params;
    }

    // $this->utils->debug_log('mobile_player_center_custom_buttons', $mobile_player_center_custom_buttons);

    //merge to
    // $_mobile_menu_list=array_merge($_mobile_menu_list, $mobile_player_center_custom_buttons);

    // foreach ($mobile_player_center_custom_buttons as $title => $params) {
    //     $_mobile_menu_list[$title]=$params;
    // }

    // $this->utils->debug_log('_mobile_menu_list', $_mobile_menu_list);
}

foreach($_mobile_menu_list as $title => $params){
    $params['image_url'] = $this->utils->getAnyCmsUrl($params['images']);

    if(isset($params['system_feature']) && !$this->utils->isEnabledFeature($params['system_feature'])){
        // $this->utils->debug_log('ignore '.$params['content'].' by feature '.$params['system_feature']);

        continue;
    }else if($title == 'Logout'){
        $logout_param = $params;

        continue;
    }

    if (!empty($exclude_mobile_player_center_menu_list)) {
        if (in_array($title, $exclude_mobile_player_center_menu_list)) {
           continue;
        }
    }

    if($params['id'] == 'zzxx' && $this->utils->isEnabledFeature('always_auto_transfer_if_only_one_game')){
        //transfer

        // $this->utils->debug_log('ignore transfer '.$params['content']);

        continue;
    }

    if($params['id'] == 'Redemption_Code' && !$this->utils->enableRedemptionCodeInPlayerCenter()){

        continue;
    }

    // $this->utils->debug_log('processing', $params['content']);

    $key = floor($row / $col); # Calculate columns
    if($player_is_suspend){
        if(in_array($title, $mobole_menu_suspend_list)){
            $mobile_menu_list[$key][] = $params;
            $row++;
        }
    }else{
        // $this->utils->debug_log('add it to mobile menu', $params);

        $mobile_menu_list[$key][] = $params;
        $row++;
    }
}

if (!$this->utils->getConfig('player_center_mobile_hide_logout_button')) {
    // Add logout to the last of the array
    if(count($mobile_menu_list[$key]) < $col){
        $mobile_menu_list[$key][] = $logout_param;
    }else{
        $mobile_menu_list[$key + 1][] = $logout_param;
    }
}

$this->utils->debug_log('mobile_menu_list', $mobile_menu_list);

?>

<!---menu-->
<table class="act_menu">
    <tbody>
    <?php foreach($mobile_menu_list as $row => $col_list) : ?>
        <tr>
            <?php foreach($col_list as $list) : ?>
                <td id="<?=$list['id']?>" class="<?=$list['td_class']?>" style="<?=$list['style']?>"
                    <?php if(isset($list['link'])) : ?>
                        <?php if($list['id'] == 'live_chat'):?>
                            onclick="<?=$list['link']?>"
                        <?php else:?>
                            onclick="document.location.href='<?=$list['link']?>'"
                        <?php endif;?>
                    <?php endif; ?>>
                    <li class="<?=$list['li_class']?>">
                        <img src="<?=$list['image_url']?>" >
                    </li>
                    <a><?=$list['content']?></a>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    <?php if($player_is_suspend): ?>
        <tr>
            <td colspan="4">
                <a href="#" class="btn btn-xs btn-warning"><?=lang("Your Status is Suspend. You cannot use  deposit , withdrawal , transfer functions")?></a>
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>