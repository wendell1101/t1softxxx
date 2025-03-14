<?php
$live_person_button_id = $this->config->item('live_person_button_id');
$col_user_name = 9;
$col_user_msg = 3;
$col_live_person = 0;
$col_currency_list = 0;
if (empty($live_person_button_id) && empty($currency_select_html)) {
    $col_user_name = 9;
    $col_user_msg = 3;
} elseif (!empty($live_person_button_id) && empty($currency_select_html)) {
    $col_user_name = 6;
    $col_live_person = 3;
} elseif (empty($live_person_button_id) && !empty($currency_select_html)) {
    $col_user_name = 5;
    $col_currency_list=4;
} else {
    $col_user_name = 4;
    $col_live_person = 2;
    $col_currency_list=3;
}
?>
<div class="actmain col col-md-12">
    <div class="username row">
        <div class="col col-sm-<?=$col_user_name?> col-xs-<?=$col_user_name?> user_name">
            <p><?=lang('Hello')?> <span id="player_uname"><?=$username_on_register?></span>
                <?php if($this->utils->getConfig('enable_hide_show_username_player_center')) : ?>
                <span id="uname_hidden"><i class="fa fa-eye"></i></span>
                <span id="uname_show" hidden><i class="fa fa-eye-slash"></i></span>
                <span id="hidden_uname" hidden><?=$this->authentication->getUsername();?></span>
                <?php endif; ?>
            </p>

            <?php if ($this->utils->isEnabledFeature('mobile_show_vip_referralcode')): ?>
                <div class="vip-content">

                    <div class="vip-image">
                        <img id="vip-icon"/>
                    </div>
                    <p><?=lang($player['groupName'])?>-<?=lang($player['vipLevelName'])?></p>
                    <!-- overview  -->
                    <?php if (!$this->utils->isEnabledFeature('hidden_vip_status_ExpBar') && $this->config->item('mobile_vip_status_ExpBar')): ?>
                        <div class="overview" >
                            <div class="row col-sm-12 col-xs-12">
                                <?php if (!$this->config->item('disabled_mobile_expbar_vip_level_name')): ?>
                                <div class="col-sm-4 col-xs-4 vipLvlName" ><p id="vipLvlName"></p></div>
                                <?php endif; ?>
                                <div class="col-sm-6 col-xs-6 vip-progress-bar-content">
                                    <div class="progress-bar-color" id="vipGroupNextLvlPercentage" style="width:0%">
                                    </div>
                                </div>
                                <div class="col-sm-2 col-xs-2 vipGroupNextLvlPercentageTxt"><p id="vipGroupNextLvlPercentageTxt"></div>
                            </div>
                            <p class="vip_max_detail col-sm-6 col-xs-6" style="display:none;"><?= lang('vip.is.max.member') ?></p>
                        </div>
                        <div class="vip_pane">
                            <div class="col col-sm-12 col-xs-12 vip_detail">
                                <?= lang("Deposit") ?> (<?= $currency['symbol'] ?>):
                                <span class="vipexp" id="currentVipGroupDepAmtTxt"></span>/<span class="vipexp" id="vipUpradeDepAmtReqTxt"></span>
                                <?php if (!$this->utils->isEnabledFeature('hidden_vip_betting_Amount_part')): ?>
                                    <br/>
                                    <?= lang("Betting") ?> (<?= $currency['symbol'] ?>):
                                    <span class="vipexp" id="currentVipGroupBettingAmtTxt"></span>/<span class="vipexp" id="vipUpradeBetAmtReqTxt"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!-- overview  -->
                </div>
            <?php endif; ?>
            <?php if ($this->utils->getConfig('display_vip_club_button_in_player')): ?>
            <div class="col col-sm-12 col-xs-12">
                 <a id="vip_club_button" href="<?= $this->utils->getSystemUrl('m') . '/vip.html' ?>" class="vipClubButton btn" style="display: inline-block;"><?=lang('lang.vipclubbutton')?></a>
            </div>
            <?php endif; ?>
            <?php if ($this->utils->isEnabledFeature('display_total_bet_amount_in_overview')): ?>
            <div class="col col-sm-12 col-xs-12">
                <p>
                    <?=lang('Betting today');?>: <?=$player_today_total_betting_amount?>
                </p>
            </div>
            <?php endif; ?>
            <?php if ($this->utils->isEnabledFeature('enable_shop')): ?>
            <div class="col col-sm-12 col-xs-12">
                <p>
                    <?=lang('Available Points');?>: <?=$player_available_points?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($currency_select_html)) { ?>
        <div class="col col-sm-<?=$col_currency_list?> col-xs-<?=$col_currency_list?> currency_list">
            <?=$currency_select_html?>
        </div>
        <?php }?>

        <?php if (!empty($live_person_button_id)) : ?>
            <div id="<?=lang($live_person_button_id)?>" class="chat-button" style="display: none;"></div>
            <div class="col col-sm-2 col-xs-2 chat_btn" style="cursor:pointer;">
                <div id="userchat" class="threepage" style="cursor:pointer;">
                    <a href="javascript:void(0)" onclick="<?=$this->utils->getLiveChatOnClick();?>">
                        <img src="<?=$this->utils->getAnyCmsUrl('/includes/images/chat_icon_white.svg')?>">
                        <span class="labelmsg"><?=lang('cu.16');?></span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="col col-sm-<?=$col_user_msg?> col-xs-<?=$col_user_msg?> user_msg" style="cursor:pointer;">
            <div id="usermessage" class="threepage">
                <a href="<?=$this->utils->getPlayerMessageUrl()?>">
                    <img src="<?=$this->utils->getAnyCmsUrl('/includes/images/message_icon_white.svg')?>">
                    <span class="labelmsg"><?=lang('cs.messages');?></span>
                    <span class="new-msg-tip"><?= $count_broadcast_messages > 0 ? ($this->utils->unreadMessages($player['playerId']) + $count_broadcast_messages) : $this->utils->unreadMessages($player['playerId'])?></span>
                </a>
            </div>
            <?php if ($this->config->item('line_add_friend_link')): ?>
            <div class="add-line-friend"
                 style="position: absolute;
                        right: 0;
                        top: 70px;">
                <a href="<?= $this->config->item('line_add_friend_link')?: 'javascript:void(0);'?>" class="btn"
                style='background: transparent !important; width: 100px;'>
                    <img class='line-logo' style="width: 100%;" src="<?= $this->config->item('line_add_friend_link_img_path')?: '/includes/images/add-friend-icon-mobile.png'?>">
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if(!$this->config->item('mobile_vip_status_ExpBar')):?>
    <div class="row vip_pane">
        <div class="col col-sm-12 col-xs-12 vip_detail">
            <?= lang("Deposit") ?> (<?= $currency['symbol'] ?>):
            <span class="vipexp" id="currentVipGroupDepAmtTxt"></span>/<span class="vipexp" id="vipUpradeDepAmtReqTxt"></span>
            <?php if (!$this->utils->isEnabledFeature('hidden_vip_betting_Amount_part')): ?>
                <br/>
                <?= lang("Betting") ?> (<?= $currency['symbol'] ?>):
                <span class="vipexp" id="currentVipGroupBettingAmtTxt"></span>/<span class="vipexp" id="vipUpradeBetAmtReqTxt"></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row wallet main-wallet">
        <div class="col-sm-8 col-xs-8">
            <p class="text"><?=lang("Main Wallet Total")?><span class="money playerTotalBalance"><?=$this->CI->utils->displayCurrency($big_wallet_simple['main_wallet']['balance'])?></span></p>
        </div>
        <div class="col-sm-4 col-xs-4">
            <a href="javascript:void(0);" class="btn refreshBalanceButton"><?=lang('lang.refreshbalance')?></a>
        </div>
    </div>
    <?php if (!$this->utils->getConfig('seamless_main_wallet_reference_enabled')) : ?>
    <div class="row wallet game-wallet">
        <div class="col-sm-7 col-xs-7">
            <p class="text"><?=lang("Game Wallet Total")?><span class="money playerTotalBalance"><?=$this->CI->utils->displayCurrency($big_wallet_simple['game_total'])?></span></p>
        </div>
        <div class="col-sm-5 col-xs-5">
            <a href="javascript: void(0);" class="btn transferAllToMainBtn"><?=lang("Transfer Back All")?></a>
        </div>
    </div>
    <?php endif; ?>

</div>