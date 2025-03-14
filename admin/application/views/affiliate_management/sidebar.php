<ul class="sidebar-nav" id="sidebar">
<?php
$permissions = $this->permissions->getPermissions();

if ($permissions != null) {
	foreach ($permissions as $value) {
		switch ($value) {
		case 'view_affiliates': ?>
						<li>
							<a class="list-group-item" id="view_affiliates_list" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/aff_list')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('aff.sb1');?>">
								<i id="icon" class="icon-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('aff.sb1');?>
								</span>
							</a>
						</li>
				<?php break;
		case 'banner_settings': ?>
						<li>
							<a class="list-group-item" id="banner_settings" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewAffiliateBanner')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('aff.sb2');?>">
								<i id="icon" class="icon-image <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('aff.sb2');?>
								</span>
							</a>
						</li>
				<?php break;
		case 'affiliate_earnings': ?>
							<li>
								<a class="list-group-item" id="affiliate_earnings" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewAffiliateEarnings')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('aff.sb7');?>">
									<i id="icon" class="icon-wallet <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
									<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
										<?=lang('Earnings Report');?>
									</span>
								</a>
							</li>
				<?php break;
		case 'affiliate_payments': ?>
						<li>
							<a class="list-group-item" id="affiliate_payments" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewAffiliatePayment')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('aff.sb3');?>">
								<i id="icon" class="icon-credit-card <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('aff.sb3');?>
								</span>
							</a>
						</li>
				<?php break;
		case 'affiliate_tag': ?>
						<li>
							<a class="list-group-item" id="affiliate_tag" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewAffiliateTag')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('aff.sb4');?>">
								<i id="icon" class="icon-price-tag <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('aff.sb4');?>
								</span>
							</a>
						</li>
				<?php break;
		case 'affiliate_statistics':
            if(!$this->utils->isEnabledFeature('switch_old_aff_stats_report_to_new')){?>
						<li>
							<a class="list-group-item" id="affiliate_statistics" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('affiliate_management/affiliate_statistics?enable_date=true'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Affiliate Statistics');?>">
								<i id="icon" class="icon-stats-bars <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Affiliate Statistics');?>
								</span>
							</a>
						</li>
				<?php }
            else{ ?>
                <li>
                    <a class="list-group-item" id="affiliate_statistics" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('affiliate_management/affiliate_statistics2?enable_date=true'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Affiliate Statistics');?>">
                        <i id="icon" class="icon-stats-bars <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Affiliate Statistics 2');?>
								</span>
                    </a>
                </li>
            <?php }
            break;
		case 'affiliate_traffic_statistics':
			?>
			<li>
				<a class="list-group-item" id="affiliate_traffic_statistics" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('affiliate_management/traffic_statistics?enable_date=true'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Affiliate Traffic Statistics');?>">
					<i id="icon" class="icon-stats-bars <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
					<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
						<?=lang('Affiliate Traffic Statistics');?>
					</span>
				</a>
			</li>				
		<?php break;	
		case 'affiliate_deposit': ?>
						<li>
							<a class="list-group-item" id="affiliate_deposit" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('affiliate_management/affiliate_deposit'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Deposit Affiliate');?>">
								<i id="icon" class="fa fa-plus <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Deposit Affiliate');?>
								</span>
							</a>
						</li>
				<?php break;
		case 'affiliate_withdraw': ?>
						<li>
							<a class="list-group-item" id="affiliate_withdraw" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('affiliate_management/affiliate_withdraw'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Affiliate Withdraw');?>">
								<i id="icon" class="fa fa-minus <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Affiliate Withdraw');?>
								</span>
							</a>
						</li>
				<?php break;
		case 'affiliate_terms': ?>
						<li>
							<a class="list-group-item" id="affiliate_terms" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewTermsSetup')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('aff.sb6');?>">
								<i id="icon" class="icon-settings <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('aff.sb9');?>
								</span>
							</a>
						</li>
						<?php if ($this->utils->isEnabledFeature('switch_to_ibetg_commission')) { ?>
						<li>
							<a class="list-group-item" id="affiliate_level_terms" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewAffilliateLevelSetup')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('aff.sb12');?>">
								<i id="icon" class="icon-settings <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('aff.sb12');?>
								</span>
							</a>
						</li>
						<?php } ?>
				<?php break;
		case 'aff_domain_setting': ?>
						<li>
							<a class="list-group-item" id="viewDomain" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewDomain')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word21');?>">
								<i id="icon" class="icon-sphere <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('system.word21');?>
								</span>
							</a>
						</li>
				<?php break;
		case 'adjust_player_benefit_fee':
			if($this->utils->isEnabledFeature('enable_player_benefit_fee')):?>
						<li>
							<a class="list-group-item" id="viewBatchBenefitFeeAdjustment" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewBatchBenefitFeeAdjustment')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Batch Benefit Fee Adjustment');?>">
								<i id="icon" class="fa fa-users <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Batch Benefit Fee Adjustment');?>
								</span>
							</a>
						</li>
			<?php endif;
				break;
		case 'adjust_addon_affiliates_platform_fee':
			if($this->utils->isEnabledFeature('enable_addon_affiliate_platform_fee')):?>
						<li>
							<a class="list-group-item" id="viewBatchAddonPlatformFeeAdjustment" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewBatchAddonPlatformFeeAdjustment')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Batch Addon Platform Fee Adjustment');?>">
								<i id="icon" class="fa fa-users <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Batch Addon Platform Fee Adjustment');?>
								</span>
							</a>
						</li>
			<?php endif;
				break;
		case 'affiliate_partners': ?>
						<li>
							<a class="list-group-item" id="affiliate_partners" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/affiliate_partners')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Affiliate Partners');?>">
								<i id="icon" class="icon-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Affiliate Partners');?>
								</span>
							</a>
						</li>
				<?php break;
        case 'view_affiliate_login_report': ?>
                        <li>
                            <a class="list-group-item" id="affiliate_login_report" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/viewAffiliateLoginReport')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Affiliate Login Report');?>">
                                <i id="icon" class="icon-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
                                <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                                    <?=lang('Affiliate Login Report');?>
                                </span>
                            </a>
                        </li>
                <?php break;
		case 'player_changeover_report':
			if($this->utils->getConfig('enabled_affiliate_changeover')):?>
						<li>
							<a class="list-group-item" id="affiliate_management_player_changeover" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/player_changeover')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Player Changeover Report');?>">
								<i id="icon" class="icon-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Player Changeover Management');?>
								</span>
							</a>
						</li>
			<?php endif;
				break;
		case 'affiliate_changeover_report':
			if($this->utils->getConfig('enabled_affiliate_changeover')):?>
						<li>
							<a class="list-group-item" id="affiliate_management_changeover" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('affiliate_management/affiliate_changeover')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Affiliate Changeover Report');?>">
								<i id="icon" class="icon-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>"></i>
								<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
									<?=lang('Affiliate Changeover Management');?>
								</span>
							</a>
						</li>
			<?php endif;
				break;
		default:
			break;
		}
	}
}
?>
</ul>
<ul id="sidebar_menu" class="sidebar-nav">
    <li class="sidebar-brand">
        <a id="menu-toggle" href="#" onclick="Template.changeSidebarStatus();">
        	<span id="main_icon" class="icon-arrow-<?=($this->session->userdata('sidebar_status') == 'active') ? 'left' : 'right';?> pull-right"></span></span>
        </a>
    </li>
</ul>
