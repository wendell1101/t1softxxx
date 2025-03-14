<ul class="sidebar-nav" id="sidebar">

<?php
$permissions = $this->permissions->getPermissions();
if ($permissions != null) {
	foreach ($permissions as $value) {
		$icon_pull_right = ($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';
        $style_display_option =  ($this->session->userdata('sidebar_status') == 'active' ? 'initial' : 'none');
        $style_display = "style='display:{$style_display_option};' ";
		switch ($value) {
		case 'promo_category_setting': ?>
				<li>
					<a id="promoCategorySettings" class="list-group-item" style="border: 0px;" href="<?=site_url('/marketing_management/promoTypeManager')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('cms.promoCategorySettingsDesc');?>">
						<i class="icon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('cms.promoCategorySettings');?>
						</span>
					</a>
				</li>
		<?php break;
		case 'promo_rules_setting': ?>
						<li>
							<a class="list-group-item" id="view_promorules_settings" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="																						<?=BASEURL . 'marketing_management/promoRuleManager'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('cms.promoRuleSettings');?>">
								<i class="fa fa-book <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('cms.promoRuleSettings');?>
								</span>
							</a>
						</li>
			<?php break;
		case 'promocms':
			?>
						<li>
	                        <a class="list-group-item" id="view_promo_list" style="border: 0px;margin-bottom:0.1px;" href="																					<?=BASEURL . 'marketing_management/promoSettingList'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('cms.06');?>">
	                            <i class="icon-bullhorn <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
	                            <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('cms.06');?>
								</span>
	                        </a>
                    	</li>
        <?php break;
		case 'shopping_center_manager':
				if ($this->utils->isEnabledFeature('enable_shop')) {
			?>
						<li>
	                        <a class="list-group-item" id="view_shopping_center_list" style="border: 0px;margin-bottom:0.1px;" href="																					<?=BASEURL . 'marketing_management/shoppingCenterItemList'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Shop Manager');?>">
	                            <i class="fa fa-shopping-bag <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
	                            <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Shop Manager');?>
								</span>
	                        </a>
                    	</li>
            <?php
        		}
            break;
		case 'shopper_list':
				if ($this->utils->isEnabledFeature('enable_shop')) {
			?>
						<li>
	                        <a class="list-group-item" id="view_shopper_request_list" style="border: 0px;margin-bottom:0.1px;" href="																					<?=BASEURL . 'marketing_management/shoppingClaimRequestList'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Shopping Points Request List');?>">
	                            <i class="fa fa-shopping-bag <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
	                            <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Shopping Points Request List');?>
								</span>
	                        </a>
                    	</li>
            <?php
            	}
			break;
		case 'shop_point_expiration':
				if ($this->utils->isEnabledFeature('enable_shop')) {
			?>
						<li>
	                        <a class="list-group-item" id="view_shop_point_expiration" style="border: 0px;margin-bottom:0.1px;" href="																					<?=BASEURL . 'marketing_management/shoppingPointExpiration'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Shopping Points Expiration');?>">
	                            <i class="fa fa-shopping-bag <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
	                            <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Shopping Points Expiration');?>
								</span>
	                        </a>
                    	</li>
            <?php
            	}
            break;
		case 'promoapp_list':
			?>
						<li>
							<a class="list-group-item" id="view_promoapp_list" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'marketing_management/promoApplicationList'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('cms.promoReqAppList');?>">
								<i class="fa fa-hand-paper-o <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('cms.promoReqAppList');?>
								</span>
							</a>
						</li>
                        <?php if($this->utils->getConfig('enabled_friend_referral_promoapp_list')):?>
                            <li>
                                <a class="list-group-item" id="view_referral_promoapp_list" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'marketing_management/referralPromoApplicationList'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Friend Referral Request Report');?>">
                                    <i class="fa fa-hand-paper-o <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
                                    <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                                        <?=lang('Friend Referral Request Report');?>
                                    </span>
                                </a>
                            </li>
                        <?php endif;?>
						<?php if($this->utils->getConfig('enabled_hugebet_referral_promoapp_list')):?>
                            <li>
                                <a class="list-group-item" id="view_hugebet_referral_promoapp_list" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'marketing_management/hugebetPromoApplicationList'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Friend Referral Request Report');?>">
                                    <i class="fa fa-hand-paper-o <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
                                    <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                                        <?=lang('Referral Request Report');?>
                                    </span>
                                </a>
                            </li>
                        <?php endif;?>
			<?php break;
		case 'report_gamelogs':
			?>
						<li>
							<a class="list-group-item" id="view_game_logs" style="border: 0px;margin-bottom:0.1px;" href="																						<?=BASEURL . 'marketing_management/viewGameLogs'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('role.157');?>">
								<i class="fa fa-table <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('role.157');?>
								</span>
							</a>
						</li>
			<?php break;
		case 'gamelogs_export_hourly':
			?>
						<li>
							<a class="list-group-item" id="view_game_logs_export_hourly" style="border: 0px;margin-bottom:0.1px;" href="																						<?=BASEURL . 'marketing_management/viewGameLogsExportHourly'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('role.157');?>">
								<i class="fa fa-table <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('role.607');?>
								</span>
							</a>
						</li>
			<?php break;
		case 'cashback_setting': ?>
			<?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
			<li>
				<a id="cashbackSettings" class="list-group-item" style="border: 0px;" href="<?=site_url('/marketing_management/cashbackPayoutSetting')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('cms.cashbackSettingsDesc');?>">
					<i class="glyphicon glyphicon-usd <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('cms.cashbackSettings');?>
								</span>
				</a>
			</li>
		<?php endif?>

		<?php break;
		case 'friend_referral_setting': ?>
			<li>
				<a id="friendReferralSettings" class="list-group-item" style="border: 0px;" href="<?=site_url('/marketing_management/friend_referral_settings')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('cms.friendReferralSettingsDesc');?>">
				<i class="fa fa-users <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('cms.friendReferralSettings');?>
								</span>
				</a>
			</li>
		<?php break;
		case 'batch_adjust_balance':
			?>
						<li>
							<a class="list-group-item" id="view_batch_balance_adjustment" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?php echo site_url('marketing_management/batchBalanceAdjustment'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('role.96');?>">


				<i class="fa fa-users <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('role.96');?>
								</span>
							</a>
						</li>
		 	<?php break;
		case 'cashback_request':
			?>
			<?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
            <li>
                <a class="list-group-item" id="view_cashback_request_list" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?php echo site_url('marketing_management/manage_cashback_request'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('xpj.cashback.list');?>">
                <i class="glyphicon glyphicon-share <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
                    <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                            <?=lang('xpj.cashback.list');?>
                    </span>
                </a>
            </li>
            <?php endif; ?>
            <?php break;
		case 'bonus_game_settings' :
			if ($this->utils->isEnabledFeature('bonus_games__enable_bonus_game_settings')) {
			?>
				<li>
	                <a class="list-group-item" id="bonus_game_settings" style="border: 0px;line-height: 1.4; margin-bottom:0.1px;" href="<?php echo site_url('marketing_management/bonusGameSettings'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Bonus Game Settings');?>">
	                <i class="fa fa-gamepad <?= $icon_pull_right ?>" id="icon"></i>
	                    <span id="hide_text" <?= $style_display ?> >
	                            <?=lang('Bonus Game Settings');?>
	                    </span>
	                </a>
	            </li>
			<?php
			}
			break;
		case 'ole777_wager_sync' :
		?>
			<?php if ($this->utils->isEnabledFeature('ole777_wager_sync')) : ?>
				<li>
	                <a class="list-group-item" id="ole777_wager_sync" style="border: 0px;line-height: 1.4; margin-bottom:0.1px;" href="<?= site_url('marketing_management/ole777_wager_sync'); ?>" data-toggle="tooltip" data-placement="right" title="<?= lang('OLE777 Wager Sync'); ?>">
	                <i class="fa fa-book <?= $icon_pull_right ?>" id="icon"></i>
	                    <span id="hide_text" <?= $style_display ?> >
	                          <?=lang('OLE777 Wager Sync');?>
	                    </span>
	                </a>
	            </li>
			<?php endif; ?>
			<?php
			break;
		case 'png_free_game_offer' :
		?>
			<?php if ($this->utils->isEnabledFeature('enabled_png_freegame_api')) : ?>
				<li>
	                <a class="list-group-item" id="png_free_game_offer" style="border: 0px;line-height: 1.4; margin-bottom:0.1px;" href="<?= site_url('marketing_management/viewPNGFreeGameAPI'); ?>" data-toggle="tooltip" data-placement="right" title="<?= lang('PNG FreeGame Offers'); ?>">
					<i class="icon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
	                    <span id="hide_text" <?= $style_display ?> >
	                          <?=lang('PNG FreeGame Offers');?>
	                    </span>
	                </a>
	            </li>
			<?php endif; ?>
			<?php
			break;
		case 'game_free_spin_bonus' :
		?>
			<?php if (!empty($this->utils->getConfig('games_with_campaign_enabled'))) : ?>
				<li>
	                <a class="list-group-item" id="game_free_spin_bonus" style="border: 0px;line-height: 1.4; margin-bottom:0.1px;" href="<?= site_url('marketing_management/view_game_free_spin_setting'); ?>" data-toggle="tooltip" data-placement="right" title="<?= lang('Game Free Spin Bonus'); ?>">
					<i class="icon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
	                    <span id="hide_text" <?= $style_display ?> >
	                          <?=lang('Game Free Spin Bonus');?>
	                    </span>
	                </a>
	            </li>
			<?php endif; 
			break;
		default:
			break;
		} // end of switch
	} // end of foreach
	?>
		<?php if (!empty($this->utils->getConfig('enabled_quest'))) : ?>
			<li>
					<a class="list-group-item" id="quest_category" style="border: 0px;line-height: 1.4; margin-bottom:0.1px;" href="<?= site_url('marketing_management/quest_category'); ?>" data-toggle="tooltip" data-placement="right" title="<?= lang('Quest Category'); ?>">
					<i class="icon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
						<span id="hide_text" <?= $style_display ?> >
								<?=lang('Quest Category');?>
						</span>
					</a>
				</li>
				<li>
					<a class="list-group-item" id="quest_manager" style="border: 0px;line-height: 1.4; margin-bottom:0.1px;" href="<?= site_url('marketing_management/quest_manager'); ?>" data-toggle="tooltip" data-placement="right" title="<?= lang('Quest Manager'); ?>">
					<i class="icon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
						<span id="hide_text" <?= $style_display ?> >
								<?=lang('Quest Manager');?>
						</span>
					</a>
				</li>
		<?php endif; 
} // end of if
?>
	<?php if($this->config->item('enable_redemption_code_system') || $this->config->item('enable_static_redemption_code_system')):?>
	<li>
		<a  role="button" aria-expanded="false" data-toggle="collapse" class="list-group-item" id="viewRedemptionCodeSettings" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="#collapseSubmenu"  aria-controls="collapseSubmenu" data-placement="right" title="<?=lang('redemptionCode.redemptionCodeSettings');?>">
			<i class="icon-gift <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
			<span id="hide_text"<?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> ><?=lang('redemptionCode.redemptionCodeSettings');?></span>
		</a>
		<div class="collapse" id="collapseSubmenu">
			<div class="col-md-6">
				<ul class="sidebar-nav" id="sidebar">
				<?php if($this->config->item('enable_redemption_code_system') && $this->permissions->checkPermissions('view_redemption_code_category')) { ?> 
					<li>
						<a id="redemptionCodeCategoryManager" class="list-group-item" style="border: 0px;" href="<?=site_url('marketing_management/redemptionCodeCategoryManager')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('redemptionCode.redemptionCodeCategoryManager');?>">
							<?=lang('redemptionCode.redemptionCodeCategoryManager');?>
						</a>
					</li>
				<?php }
					if($this->config->item('enable_redemption_code_system') &&  $this->permissions->checkPermissions('manage_redemption_code')){
				?>
					<li>
						<a id="redemptionCodeList" class="list-group-item" style="border: 0px;" href="<?=site_url('marketing_management/redemptionCodeList')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('redemptionCode.redemptionCodeList');?>">
							<?=lang('redemptionCode.redemptionCodeList');?>
						</a>
					</li>
				<?php }
					if($this->config->item('enable_static_redemption_code_system') &&  $this->permissions->checkPermissions('view_static_redemption_code_category')){
				?>
					<li>
						<a id="staticRedemptionCodeCategoryManager" class="list-group-item" style="border: 0px;" href="<?=site_url('marketing_management/staticRedemptionCodeCategoryManager')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('redemptionCode.staticRedemptionCodeCategoryManager');?>">
							<?=lang('redemptionCode.staticRedemptionCodeCategoryManager');?>
						</a>
					</li>

				<?php }
					if($this->config->item('enable_static_redemption_code_system') &&  $this->permissions->checkPermissions('manage_static_redemption_code')){
				?>
					<li>
						<a id="staticRedemptionCodeList" class="list-group-item" style="border: 0px;" href="<?=site_url('marketing_management/staticRedemptionCodeList')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('redemptionCode.staticRedemptionCodeList');?>">
							<?=lang('redemptionCode.staticRedemptionCodeList');?>
						</a>
					</li>
				<?php }?>

				</ul>
			</div>
		</div>
	</li>
	<?php endif; ?>
</ul>
<ul id="sidebar_menu" class="sidebar-nav">
    <li class="sidebar-brand">
        <a id="menu-toggle" href="#" onclick="Template.changeSidebarStatus();">
        	<span id="main_icon" class="icon-arrow-<?=($this->session->userdata('sidebar_status') == 'active') ? 'left' : 'right';?> pull-right"></span></span>
        </a>
    </li>
</ul>