<?php
$live_person_button_id = $this->config->item('live_person_button_id');
$col_user_name = 8;
$col_user_msg = 4;
$col_live_person = 0;
$col_currency_list = 6;
?>

<div class="actmain col col-md-12">
     <div class="username row">
          <div class="user_group">
               <div class="user-image">
                    <img id="vip-icon" src="<?=$this->utils->getAnyCmsUrl('/includes/images/mobile-user.png')?>" />
               </div>
               <div class="user-info">
                    <p>
                         <!-- <?=lang('Hello')?>, -->
                         <?=$player['username']?>
                    </p>
                    <?php if($this->utils->isEnabledFeature('mobile_show_vip_referralcode')): ?>
                    <p><?=lang($player['groupName'])?> - <?=lang($player['vipLevelName'])?></p>
                    <?php endif; ?>
                    </div>
               <div class="user-msg">
                    <a href="<?=$this->utils->getPlayerMessageUrl()?>">
                         <img src="<?=$this->utils->getAnyCmsUrl('/includes/images/message_icon_white.svg')?>" />
                         <span class="labelmsg"><?=lang('cs.messages');?></span>
                         <span class="new-msg-tip"><?=$this->utils->unreadMessages($player['playerId'])?></span>
                    </a>
               </div>
          </div>
     </div>
     <div class="vipLvl">
          <?php if (!$this->config->item('disabled_mobile_expbar_vip_level_name')): ?>
          <div class="vipLvlName">
               <p id="vipLvlName"></p>
          </div>
          <?php endif; ?>
          <div class="vip-progress-bar-content">
               <div class="progress-bar-color" id="vipGroupNextLvlPercentage" style="width: 0%"></div>
          </div>
          <div class="vipGroupNextLvlPercentageTxt"><p id="vipGroupNextLvlPercentageTxt"></p></div>
          <div>
               <p class="vip_max_detail col-sm-12 col-xs-12" style="display: none"><?= lang('vip.is.max.member') ?></p>
          </div>
     </div>
     <div class="button-grp">
          <div class="vip-detail">
               <p class="detail-item">
                    <span ><?= lang("Deposit") ?> (<?= $currency['symbol'] ?>): </span>
                    <span class="vipexp" id="currentVipGroupDepAmtTxt"></span>/<span class="vipexp" id="vipUpradeDepAmtReqTxt"></span>
               </p>
               <?php if (!$this->utils->isEnabledFeature('hidden_vip_betting_Amount_part')): ?>
               <p class="detail-item">
                    <span><?= lang("Betting") ?> (<?= $currency['symbol'] ?>): </span>
                    <span class="vipexp" id="currentVipGroupBettingAmtTxt"></span>/<span class="vipexp" id="vipUpradeBetAmtReqTxt"></span>
               </p>
               <?php endif; ?>
               <p class="detail-item">
                    <span><?=lang('Betting today');?> (<?= $currency['symbol'] ?>): </span>
                    <span class="vipexp" id="playerTodayTotalBettingAmount"><?=$player_today_total_betting_amount?></span>
               </p>
               <?php if ($this->utils->isEnabledFeature('enable_shop')): ?>
               <p class="detail-item overview">
                    <span><?=lang('Available Points');?> : </span>
                    <span class="vipexp available_points_result" id="available-points"></span>
               </p>
               <?php endif; ?>
          </div>
          <div class="btn-group">
          <?php if ($this->config->item('line_add_friend_link')): ?>
               <div class="line-add-friend">
                    <a href="<?= $this->config->item('line_add_friend_link')?: 'javascript:void(0);'?>">
                         <!-- <img class="line-logo" src="https://player.sexycasino.com/includes/images/add-friend-icon-mobile_2.png" /> -->
                         <img class='line-logo' src="/images/line.svg">
                         <span><?=lang('Add Friends')?></span>
                    </a>
               </div>
          <?php endif; ?>
          <?php if ($this->CI->utils->getConfig('enable_fast_track_integration')) {
               $setting = $this->CI->utils->getConfig('fast_track_notification_setting');
               if (!empty($setting) && key_exists('brand', $setting)) {
                    include VIEWPATH . '/resources/includes/custom_nav/'.$setting['brand'].'/fasttrack.php';
               }
          } ?>
          </div>
    </div>
</div>
<style>
     .username.row::before,
    .username.row::after {
        content: none;
    }

    .username.row .user_group {
        display: flex;
        align-items: center;
    }

    .username.row .user_group .user-image {
        margin-right: 10px;
    }

    .username.row .user_group .user-image img {
        width: auto;
        height: 65px;
    }

    .username.row .user_group .user-info p {
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 202px;
    }

    .username.row .user_group .user-info p:first-child {
        font-size: 20px;
        font-weight: 700;
    }

    .username.row .user_group .user-msg {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-left: auto;
        text-align: center;
    }

    .username.row .user_group .user-msg img {
        width: 20px;
        filter: invert(1);
    }

    .vipLvl {
        margin-top: 15px;
        position: relative;
        margin-bottom: 15px;
    }

    .vipLvl .vip-progress-bar-content {
        height: 12px;
        background: #cca352;
        border-radius: 4px;
        border: 1px solid rgba(0, 0, 0, .6);
    }

    .vipLvl .vip-progress-bar-content #vipGroupNextLvlPercentage {
        background: #fbe9c5;
        height: 10px;
        border-radius: 4px;
    }

    .vipLvl .vipGroupNextLvlPercentageTxt {
        position: absolute;
        right: 2px;
        top: 0;
    }

    .button-grp {
        display: flex;
        justify-content: space-between;
    }

    .button-grp .vip-detail p {
        margin-bottom: 5px;
    }
    .button-grp .btn-group a {
        background: #fcdb7b;
        background: -moz-linear-gradient(top, #fcdb7b 0%, #a3853f 36%, #3d2b03 100%);
        background: -webkit-linear-gradient(top, #fcdb7b 0%, #a3853f 36%, #3d2b03 100%);
        background: linear-gradient(to bottom, #fcdb7b 0%, #a3853f 36%, #3d2b03 100%);
        height: 40px !important;
        border-radius: 4px !important;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 10px;
    }

    .button-grp .btn-group a img {
        width: 20px !important;
        margin-right: 10px;
    }

    .button-grp .btn-group .inbox-container a img {
        filter: brightness(10);
    }
</style>