<div class="clearfix overview">
	<!-- Greeting section -->
	<div class="col-sm-12 col-md-3 profile-section">
		<div class="row" id="overview-profile">
			<p class="player-name">

				<?php if ($this->utils->getConfig('hide_player_center_overview_hail')) : ?>
                <?=$this->authentication->getUsername();?>
                <?php else: ?>
                <?=lang("Hi");?> <span id="player_uname"><?=$username_on_register;?></span>
                    <?php if($this->utils->getConfig('enable_hide_show_username_player_center')) : ?>
                    <span id="hidden_uname" hidden><?=$this->authentication->getUsername();?></span>
                    <span id="uname_hidden"><i class="fa fa-eye"></i></span>
                    <span id="uname_show" hidden><i class="fa fa-eye-slash"></i></span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if(!empty($currency_select_html)){ ?>
                    <?=$currency_select_html?>
                <?php } ?>
			</p>
			<p class="welcome-title"><?=lang("Welcome Back") ?>!</p>
			<div class="login-content">
				<p>
                    <span><?= lang("Last Login Time") ?> : </span>
                    <span id="lastLogintimezoneTxt" class="<?=($this->utils->isEnabledFeature('display_last_login_timezone_in_overview')) ? '' : ' hidden'?>"></span>
                    <span id="lastLogintimeTxt"></span>
                </p>
			</div>
		</div>
	</div>
	<!-- VIP section -->
	<?php if (!$this->utils->isEnabledFeature('hidden_vip_icon_LevelName') || !$this->utils->isEnabledFeature('hidden_vip_status_ExpBar') || $this->utils->isEnabledFeature('display_total_bet_amount_in_overview')): ?>
    	<div class="col-sm-6 col-md-5 points">
    		<div class="row" id="overview-points">
    		<?php if (!$this->utils->isEnabledFeature('hidden_vip_icon_LevelName')): ?>
    			<div class="vip-status">
    				<img class="vip-icon" id="vip-icon" src="<?=base_url() . $this->utils->getPlayerCenterTemplate()?>/img/icons/star.png">
    				<span id="vipGroupLvl"></span>
    				<?php if ($this->utils->isEnabledFeature('display_vip_upgrade_schedule_in_player')): ?>
    					<span style="margin-left: 10px !important;" class="upgrade_notice"></span>
    				<?php endif; ?>
					<?php if ($this->utils->getConfig('display_vip_club_button_in_player')): ?>
						<a id="vip_club_button" href="<?= $this->utils->getSystemUrl('www') . '/vip.html' ?>" class="vipClubButton btn" style="display: inline-block;"><?=lang('lang.vipclubbutton')?></a>
					<?php endif; ?>
					<?php if ($this->utils->getConfig('display_vip_click_to_know_more')): ?>
						<a id="vip_click_more" href="<?= $this->utils->is_mobile() ? $this->utils->getSystemUrl('m')  : $this->utils->getSystemUrl('www') . '/vip.html' ?>" class="vipClubButton btn" style="display: inline-block;width: 50%;float: right;"><?=lang('Click to know more')?></a>
					<?php endif; ?>
				</div>
    		<?php endif; ?>
    		<?php if (!$this->utils->isEnabledFeature('hidden_vip_status_ExpBar')): ?>
                <div class="clearfix vip-status-bar">
                    <div class="col-md-3 text-right" id="vipLvlName"></div>
                    <div class="col-md-6">
                        <div class="vip-progress-bar-content">
    						<div class="progress-bar-color" id="vipGroupNextLvlPercentage" style="width:0%">
    						</div>
    					</div>
                        <p class="vip_detail">
    						<?= lang("Deposit") ?> (<?= $currency['symbol'] ?>):
                        	<span id="currentVipGroupDepAmtTxt"></span>/<span id="vipUpradeDepAmtReqTxt"></span>
                        	<?php if (!$this->utils->isEnabledFeature('hidden_vip_betting_Amount_part')): ?>
    	                    	<br/>
    							<?= lang("Betting") ?> (<?= $currency['symbol'] ?>):
    							<span id="currentVipGroupBettingAmtTxt"></span>/<span id="vipUpradeBetAmtReqTxt"></span>
    						<?php endif; ?>
    					</p>
    					<p class="vip_max_detail" style="display:none;">
    						<span><?= lang('vip.is.max.member') ?></span>
                        </p>
                    </div>
                    <div id="vipGroupNextLvlPercentageTxt" class="col-md-2"></div>
                </div>
            <?php endif; ?>
            <?php if ($this->utils->isEnabledFeature('display_total_bet_amount_in_overview')): ?>
                <div class="total_bet_amount_container">
                    <span class="col-md-3 text-right total_bet_amount_label"><?=lang('Betting today');?></span>
                    <span class="col-md-8 total_bet_amount_result"></span>
                </div>
            <?php endif; ?>
			<?php if ($this->utils->isEnabledFeature('enable_shop')): ?>
                <div class="available_points_container" style="padding-top:20px;">
                    <span class="col-md-3 text-right available_points_label"><?=lang('Available Points');?></span>
                    <span class="col-md-8 available_points_result"></span>
                </div>
            <?php endif; ?>
    		</div>
    	</div>
	<?php endif; ?>
	<!-- Validation status section -->
	<?php # Verification status translation
    	$isBankInfoAdded = isset($isBankInfoAdded) ? $isBankInfoAdded : false;
    	$isPlayerVerified = isset($player_verification) ? $player_verification['verified'] : false;
    	$isEmailVerified = isset($isEmailVerified) ? $isEmailVerified : false;
    	$isPhoneVerificationNeeded = isset($showSMSField) ? $showSMSField : false;
    	$isPhoneVerified = isset($isPhoneVerified) ? $isPhoneVerified : false;

        $isEnableCustomizedMsg = $this->utils->getConfig('customized_after_security_verification_text');
        $playerVerifiedMsg = $isEnableCustomizedMsg ? lang('cms.verificationPlayer.ok') : lang('cms.verificationStatus.ok');
        $emailVerifiedMsg = $isEnableCustomizedMsg ? lang('cms.verificationEmail.ok') : lang('cms.verificationStatus.ok');
        $phoneVerifiedMsg = $isEnableCustomizedMsg ? lang('cms.verificationPhone.ok') : lang('cms.verificationStatus.ok');

        $count_broadcast_messages = isset($count_broadcast_messages) ? $count_broadcast_messages : 0;
        $display_player_turnover = $this->utils->getConfig('display_player_turnover');

	?>
	<div class="col-sm-6 <?= (!$this->utils->isEnabledFeature('hidden_vip_icon_LevelName') || !$this->utils->isEnabledFeature('hidden_vip_status_ExpBar')) ? 'col-md-4' : 'col-md-9' ?> vip-rewards">
		<div class="row" id="overview-rewards">
			<div class="clearfix verifiled">
				<div class="col-md-3">
					<a id="bank_account_info" href="<?= site_url('player_center2/bank_account')?>">
						<i class="top_icon top_icon_bank <?= $isBankInfoAdded ? 'verified' : '' ?>"></i>
	                    <p><?= $isBankInfoAdded ? lang('overview.bankcard.added') : lang('overview.bankcard.not.added') ?></p>
                	</a>
				</div>
				<?php if($this->utils->isEnabledFeature('show_player_upload_realname_verification')): ?>
    				<div class="col-md-3">
    					<a id="security_player_verified" href="<?= site_url('player_center2/security')?>">
    						<i class="top_icon top_icon_player <?= $isPlayerVerified ? 'verified' : '' ?>"></i>
                            <p><?= $isPlayerVerified ? $playerVerifiedMsg : lang('overview.realname.not.verify') ?></p>
    	                </a>
    				</div>
				<?php endif;?>
				<?php if($showEmailVerifyField): ?>
    				<div class="col-md-3">
    					<a id="security_email_verified" href="<?= site_url('player_center2/security')?>">
    						<i class="top_icon top_icon_email <?= $isEmailVerified ? 'verified' : '' ?>"></i>
                            <p><?= $isEmailVerified ? $emailVerifiedMsg : lang('cms.verificationStatus.none') ?></p>
    	                </a>
    				</div>
				<?php endif; ?>
				<?php if ($isPhoneVerificationNeeded): ?>
    				<div class="col-md-3">
    					<a id="security_phone_verified" href="<?= site_url('player_center2/security')?>">
    						<i class="top_icon top_icon_phone <?= $isPhoneVerified ? 'verified' : '' ?>"></i>
                            <p><?= $isPhoneVerified ? $phoneVerifiedMsg : lang('overview.mobile.not.verify') ?></p>
    	                </a>
    				</div>
				<?php endif; ?>
			</div>
			<div class="clearfix user-msg">
                <div class="col-md-6 t-amount">
                	<a id="playercenter_total_balance" href="<?= site_url('player_center/dashboard')?>#memberCenter">
                    	<p><?=lang("Total Balance")?>
                      		<br><span class="total-balance"><?=$this->utils->displayCurrency(isset($total_no_frozen) ? $total_no_frozen : 0)?></span>
                      	</p>
                    </a>
                </div>
                <div class="col-md-6 new-msg">
                	<?php # TODO: use onhashchagne event here to avoid page reload ?>
                	<a id="playercenter_message" href="<?= site_url('player_center2/messages')?>">
	                    <i class="fa fa-envelope-o" aria-hidden="true"></i>
	                    <span><?= lang("playercenter.messages") ?></span>
	                    <span class="new-msg-tip _player_internal_message_count"><?= $count_broadcast_messages > 0 ? ($this->utils->unreadMessages($player['playerId']) + $count_broadcast_messages) : $this->utils->unreadMessages($player['playerId'])?></span>
	                </a>
                </div>
			</div>
			<?php if ($display_player_turnover): ?>
				<div class="clearfix">
				    <div class="total_turnover_wrapper">
				        <p id="playercenter_total_turnover"><?=lang("PROGRESS TURNOVER GRANDPRIZE")?> (<?=date('F')?>):</p>
				        <span class="total-turnover"><?=$this->utils->displayCurrency(isset($total_turnover) ? $total_turnover : 0)?></span>
				    </div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>