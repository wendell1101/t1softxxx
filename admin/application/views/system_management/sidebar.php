<ul class="sidebar-nav" id="sidebar">

<?php
$permissions = $this->permissions->getPermissions();

// arrange users, roles display. should display in top
$admin_roles_key = array_search('admin_manage_user_roles', $permissions);
if ($admin_roles_key) {
	unset($permissions[$admin_roles_key]);
	array_unshift($permissions, 'admin_manage_user_roles');
}

$view_admin_users_key = array_search('view_admin_users', $permissions);
if ($view_admin_users_key) {
    unset($permissions[$view_admin_users_key]);
    array_unshift($permissions, 'view_admin_users');
}

// avoid duplicate display
if (in_array("admin_manage_user_roles", $permissions) && in_array("role", $permissions)) {
	$role = array_search('role', $permissions);
	unset($permissions[$role]);
}
$permissions = array_unique($permissions);
if ($permissions != null) {
	foreach ($permissions as $value) {
		switch ($value) {
		case 'payment_api': ?>
				<li>
					<a class="list-group-item" id="viewPaymentApi" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'payment_api/viewPaymentApi'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word95');?>">
						<i class="icon-flag <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('system.word95');?>
						</span>
					</a>
				</li>
				<?php break;
		//case 'game_api': ?>
				<!-- <li>
					<a class="list-group-item" id="viewGameApi" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'game_api/viewGameApi'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word94');?>">
						<i class="icon-stop <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('system.word94');?>
						</span>
					</a>
				</li> -->
				<?php
				// break;
		case 'ip': ?>
				<li>
					<a class="list-group-item" id="viewIp" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'ip_management/viewList'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word20');?>">
						<i class="icon-tree <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('system.word20');?>
						</span>
					</a>
				</li>
				<?php break;
		case 'country_rules': ?>
			<li>
				<a class="list-group-item" id="viewCountry" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'country_rules_management/viewList'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Country Rules');?>">
					<i class="fa fa-flag <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
					<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
						<?=lang('Website IP Rules');?>
					</span>
				</a>
			</li>
			<?php break;
		case 'player_center_api_domains': ?>
			<li>
				<a class="list-group-item" id="viewPlayerDomains" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'system_management/view_player_center_api_domain'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Player Center API Domains');?>">
					<i class="fa fa-globe <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
					<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
						<?=lang('Player Center API Domains');?>
					</span>
				</a>
			</li>
			<?php break;
        case 'view_admin_users': ?>
        <li>
                    <a class="list-group-item" id="view_users" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'user_management/viewUsers'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word23');?>">
                        <i class="icon-users <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
                        <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                            <?=lang('system.word23');?>
                        </span>
                    </a>
                </li>
                <li>
                    <a class="list-group-item" id="add_users" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'user_management/viewAddUser'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word24');?>">
                        <i class="icon-user-plus <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
                        <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                            <?=lang('system.word24');?>
                        </span>
                    </a>
                </li>
		<?php break;
        case 'role':
		case 'admin_manage_user_roles': ?>
				<li>
					<a class="list-group-item" id="checkRole" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'role_management/viewRoles'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word25');?>">
						<i class="icon-key <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('system.word25');?>
						</span>
					</a>
				</li>
				<?php break;
		case 'currency_setting': ?>
				<li>
					<a class="list-group-item" id="view_currency" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'user_management/viewCurrency'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word26');?>">
						<i class="icon-banknote <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('system.word26');?>
						</span>
					</a>
				</li>
				<?php break;
		case 'user_logs_report': ?>
				<li>
					<a class="list-group-item" id="view_logs" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'user_management/viewLogs'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('report.s01');?>">
						<i class="icon-profile <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('report.s01');?>
						</span>
					</a>
				</li>
				<?php break;
		case 'duplicate_account_setting': ?>
				<li>
					<a class="list-group-item" id="view_api_settings" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?=BASEURL . 'user_management/viewDuplicateAccount'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('role.11');?>">
						<i class="icon-settings <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('role.11');?>
						</span>
					</a>
				</li>
				<?php break;
		case 'registration_setting': ?>
				<li>
					<a class="list-group-item" id="view_registration_setting" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'marketing_management/viewRegistrationSettings'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('mark.regsetting');?>">
						<i class="glyphicon glyphicon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> >
							<?=lang('mark.regsetting');?>
						</span>
					</a>
				</li>
			<?php break; ?>

		<?php
		case 'notification': ?>
				<li>
					<a class="list-group-item" id="viewNotification" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'notification_management'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('notify.notification');?>">
						<i class="icon-sound <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('notify.notification');?>
						</span>
					</a>

				</li>
				<?php break;
        case 'system_features':
            if($this->permissions->checkPermissions('system_features') && ($this->users->isT1User($this->authentication->getUsername()) || ($this->utils->getConfig('RUNTIME_ENVIRONMENT') == 'staging' && $this->utils->getConfig('enable_system_feature_on_staging_for_non_t1_users'))))  {?>
            <li>
                <a class="list-group-item" id="viewSystemFeatures" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'system_management/system_features'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('System Features');?>">
                    <i class="glyphicon glyphicon-cog  <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
                    <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                        <?=lang('System Features');?>
                    </span>
                </a>

            </li>
            <?php }
            break;
		case 'dev_functions':
			if($this->permissions->checkPermissions('dev_functions') && $this->users->isT1User($this->authentication->getUsername())){
			?>
			<li>
				<a role="button" aria-expanded="false" data-toggle="collapse" class="list-group-item" id="dev_functions" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="#collapseSubmenu_dev_functions"  aria-controls="collapseSubmenu_dev_functions" data-placement="right" title="<?=lang('role.310');?>">
					<i class="fa fa-code <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
					<span id="hide_text"<?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> ><?=lang('role.310');?></span>
				</a>
				<div class="collapse nvaSubmenu" id="collapseSubmenu_dev_functions">
					<div class="col-md-0">
						<ul class="sidebar-nav" id="sys_settings_submenu">
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_rebuild_totals')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Rebuild Totals');?>">
									<?=lang('Rebuild Totals');?>
								</a>
							</li>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_sync_game_logs')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Sync Game Logs');?>">
									<?=lang('Sync Game Logs');?>
								</a>
							</li>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_debug_queue')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Debug Queue');?>">
									<?=lang('Debug Queue');?>
								</a>
							</li>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_clear_cache')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Clear Cache');?>">
									<?=lang('Clear Cache');?>
								</a>
							</li>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_sync_t1_gamegateway')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Sync T1 Gamegateway');?>">
									<?=lang('Sync T1 Gamegateway');?>
								</a>
							</li>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_rebuild_seamless_balance_history')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Rebuild Seamless Balance History');?>">
									<?=lang('Rebuild Seamless Balance History');?>
								</a>
							</li>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_sync_missing_payout_report')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Sync Missing Payout Report');?>">
									<?=lang('Sync Missing Payout Report');?>
								</a>
							</li>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_sync_games_report_timezones')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Sync Games Report Timezones');?>">
									<?=lang('Sync Games Report Timezones');?>
								</a>
							</li>
							<?php if(!$this->utils->isEnabledFeature('close_cashback') && $this->users->isT1Admin($this->authentication->getUsername())): ?>
								<li>
									<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_sync_cashback')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Sync Cashback');?>">
										<?=lang('Sync Cashback');?>
									</a>
								</li>
							<?php endif; ?>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_regenerate_all_report')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Regenerate All Report');?>">
									<?=lang('Regenerate All Report');?>
								</a>
							</li>
                            <?php if ($this->utils->getConfig('enable_batch_export_player_id')) { ?>
                                <li>
                                    <a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_batch_export_player_id')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Batch Export Player Id');?>">
                                        <?=lang('Batch Export Player Id');?>
                                    </a>
                                </li>
                            <?php } ?>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_sync_summary_game_total_bet_daily')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Sync Summary Game Total Bet Daily');?>">
									<?=lang('Sync Summary Game Total Bet Daily');?>
								</a>
							</li>
							<?php if(!$this->utils->isEnabledFeature('close_aff_and_agent') && $this->users->isT1Admin($this->authentication->getUsername())): ?>
								<li>
									<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_sync_affiliate_earnings')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Generate Affiliate Earnings');?>">
										<?=lang('Generate Affiliate Earnings');?>
									</a>
								</li>
							<?php endif; ?>
							<?php if ($this->utils->getConfig('enable_balance_check_report')) { ?>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_balance_check_report')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Balance Check Report');?>">
									<?=lang('Balance Check Report');?>
								</a>
							</li>
							<?php } ?>
                            <?php if ($this->utils->getConfig('enable_cancel_game_round')) { ?>
                                <li>
                                    <a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_cancel_game_round')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Cancel Game Round');?>">
                                        <?=lang('Cancel Game Round');?>
                                    </a>
                                </li>
                            <?php } ?>
							<?php if ($this->utils->getConfig('enable_refresh_all_player_balance_in_specific_game_provider')) { ?>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_refresh_all_player_balance_in_specific_game_provider')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Balance Check Report');?>">
									<?=lang('Refresh all player balance');?>
								</a>
							</li>
							<?php } ?>
                            <?php if ($this->utils->getConfig('enable_clear_game_logs_md5_sum')) { ?>
                                <li>
                                    <a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_clear_game_logs_md5_sum')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Clear Game Logs Md5 Sum');?>">
                                        <?=lang('Clear Game Logs Md5 Sum');?>
                                    </a>
                                </li>
                            <?php } ?>
                            <li>
								<a class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/dev_update_player_agent_and_affiliate')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Regenerate All Report');?>">
									<?=lang('Update player affiliate or agent');?>
								</a>
							</li>
							<li>
								<a class="list-group-item" style="border: 0px;" href="<?php echo site_url('system_management/other_functions');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('All');?>">
									<?=lang('All');?>
								</a>
							</li>
						</ul>
					</div>
				</div>

			</li>
			<?php
			}
			break;
		case 'view_task_list':
			if($this->users->isT1User($this->authentication->getUsername())){
			?>
				<li>
					<a class="list-group-item" id="view_task_list" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/view_task_list');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('View Task List');?>">
						<i class="fa fa-tasks <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('View Task List');?>
						</span>
					</a>
				</li>
			<?php
			}
			break;
		case 'view_resp_result':
			if($this->users->isT1User($this->authentication->getUsername())){
			?>
				<li>
					<a class="list-group-item" id="view_resp_result" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/view_resp_result');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('View Task List');?>">
						<i class="fa fa-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('View Response Result');?>
						</span>
					</a>
				</li>
			<?php
			}
			break;

		case 'view_sms_api_settings':
			if($this->utils->getConfig('use_new_sms_api_setting')){
			?>
				<li>
					<a class="list-group-item" id="view_sms_api_settings" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/view_sms_api_settings');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('view_sms_api_settings');?>">
						<i class="fa fa-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('view_sms_api_settings');?>
						</span>
					</a>
				</li>
			<?php
			}
			break;

		case 'view_sms_report':?>
				<li>
					<a class="list-group-item" id="view_sms_report" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/view_sms_report');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('View SMS Report');?>">
						<i class="fa fa-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('View SMS Report');?>
						</span>
					</a>
				</li>
			<?php
			break;
		case 'view_smtp_api_report':?>
				<li>
					<a class="list-group-item" id="view_smtp_report" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/view_smtp_api_report');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('View SMTP API Report');?>">
						<i class="fa fa-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('View SMTP API Report');?>
						</span>
					</a>
				</li>
			<?php
			break;
		case 'view_withdrawal_declined_category': ?>
				<?php if($this->utils->isEnabledFeature('enable_withdrawal_declined_category')) : ?>
				<li>
					<a class="list-group-item" id="view_withdrawal_declined_category" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/view_withdrawal_declined_category');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Withdrawal Declined Category');?>">
						<i class="fa fa-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('Withdrawal Declined Category');?>
						</span>
					</a>
				</li>
				<?php endif; ?>
			<?php break;

		/// Adjusted Deposits / Game Totals
		case 'view_adjusted_deposits_game_totals': ?>
			<li>
				<a class="list-group-item" id="view_adjusted_deposits_game_totals" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/view_adjusted_deposits_game_totals');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('This function is to add previous records to current existing record for VIP upgrade computation');?>">
					<i class="fa fa-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
					<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
						<?=lang('Adjusted Deposits / Game Totals');?>
					</span>
				</a>
			</li>
		<?php break;

		case 'view_adjustment_category': ?>
				<?php if($this->utils->isEnabledFeature('enable_adjustment_category')) : ?>
				<li>
					<a class="list-group-item" id="view_adjustment_category" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/view_adjustment_category');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Adjustment Category');?>">
						<i class="fa fa-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('Adjustment Category');?>
						</span>
					</a>
				</li>
				<?php endif; ?>
			<?php break;
		case 'game_wallet_settings': ?>
				<li>
					<a class="list-group-item" id="game_wallet_settings" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/game_wallet_settings');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('role.393');?>">
						<i class="fa fa-list <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('role.393');?>
						</span>
					</a>
				</li>
			<?php break;
		case 'lottery_bo_role_binding': ?>

			<?php if ( $this->utils->isAvailableT1LotteryBO() && ( $this->permissions->checkPermissions('t1lottery_bo') || $this->utils->isUserListedInLotteryExtra() ) ) {?>
				<li>
					<a class="list-group-item" id="view_resp_result" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('user_management/lottery_bo_role_binding');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Lottery Backoffice Role Binding');?>">
						<i class="fa fa-ticket <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
							<?=lang('Lottery BO Roles');?>
						</span>
					</a>
				</li>
			<?php }?>
			<?php break;
		default:
			break;
		}
	}
}
?>

<?php
if ($this->permissions->checkPermissions('bank/3rd_payment_list') ||
    $this->permissions->checkPermissions('collection_account') ||
    $this->permissions->checkPermissions('dispatch_account') ||
    $this->permissions->checkPermissions('view_previous_balances_checking_setting') ||
    $this->permissions->checkPermissions('edit_previous_balances_checking_setting') ||
    $this->permissions->checkPermissions('nonpromo_withdraw_setting') ||
    $this->permissions->checkPermissions('default_collection_account') ||
    $this->permissions->checkPermissions('withdrawal_workflow') ||
    $this->permissions->checkPermissions('payment_setting')) :
?>
	<li>
		<a  role="button"php aria-expanded="false" data-toggle="collapse" class="list-group-item" id="view_payment_settings" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="#collapseSubmenu"  aria-controls="collapseSubmenu" data-placement="right" title="<?=lang('pay.11');?>">
			<i class="icon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
			<span id="hide_text"<?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> ><?=lang('pay.11');?></span>
		</a>
		<div class="collapse" id="collapseSubmenu">
			<div class="col-md-6">
				<ul class="sidebar-nav" id="sidebar">
				<?php if($this->permissions->checkPermissions('bank/3rd_payment_list')) { ?>
					<li>
						<a id="bank3rdPaymentList" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/bank3rdPaymentList')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('pay.20');?>
						</a>
					</li>
				<?php } if($this->permissions->checkPermissions('hidden_banktype_list')) { ?>
					<li>
						<a id="hiddenBank3rdPaymentList" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/hiddenBank3rdPaymentList')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Hidden Banktype List');?>">
							<?=lang('Hidden Banktype List');?>
						</a>
					</li>
				<?php } if($this->permissions->checkPermissions('collection_account')) { ?>
					<li>
						<a id="viewPaymentAccount" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_account_management/list_payment_account')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('pay.payment_account');?>
						</a>
					</li>
					<?php if( $this->utils->getConfig('enabled_display_inactived_collection_account_page') ): ?>
					<li>
						<a id="viewPaymentAccountLite" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_account_management/list_payment_account_lite')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('pay.payment_account_lite');?>
						</a>
					</li>
					<?php endif; // EOF if( $this->utils->getConfig('enabled_display_inactived_collection_account_page'): ?>
				<?php } if($this->permissions->checkPermissions('dispatch_account')) { ?>
					<li>
						<a id="viewDispatchAccount" class="list-group-item" style="border: 0px;" href="<?=site_url('dispatch_account_management/dispatchAccountGroupList')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('pay.dispatch_account');?>
						</a>
					</li>
				<?php } if($this->permissions->checkPermissions('player_financial_account_rules_setting')) { ?>
					<li>
						<a id="viewPlayerCenterFinancialAccountSettings" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/viewPlayerCenterFinancialAccountSettings')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('pay.financial_account_setting');?>
						</a>
					</li>
                    <?php } if($this->permissions->checkPermissions('view_previous_balances_checking_setting') || $this->permissions->checkPermissions('edit_previous_balances_checking_setting')) { ?>
					<li>
						<a id="previousBalanceSetting" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/previousBalanceSetting')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('pay.17');?>
						</a>
					</li>
				<?php } if($this->permissions->checkPermissions('nonpromo_withdraw_setting')) { ?>
					<li>
						<a id="nonPromoWithdrawSetting" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/nonPromoWithdrawSetting')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('pay.18');?>
						</a>
					</li>
				<?php } if($this->permissions->checkPermissions('default_collection_account')) {?>
					<li>
						<a id="defaultCollectionAccount" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/defaultCollectionAccount')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('Default Collection Account');?>
						</a>
					</li>
				<?php } if($this->permissions->checkPermissions('withdrawal_workflow')) {?>
					<li>
						<a id="withdrawalProcessingStagesSetting" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/customWithdrawalProcessingStageSetting')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('Withdrawal Workflow');?>
						</a>
					</li>
				<?php } if($this->permissions->checkPermissions('deposit_count_setting')) {?>
					<!-- <li>
						<a id="depositCountSetting" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/depositCountSetting')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('Deposit Count Setting');?>
						</a>
					</li> -->
				<?php } ?>
				<?php if($this->permissions->checkPermissions('crypto_currency_conversion_setting')) {?>
					<li>
						<a id="cryptoCurrencySetting" class="list-group-item" style="border: 0px;" href="<?=site_url('payment_management/cryptoCurrencySetting')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.11');?>">
							<?=lang('pay.crypto_currency_setting');?>
						</a>
					</li>
				<?php } ?>
				</ul>
			</div>
		</div>
	</li>
<?php endif; ?>

<?php if($this->permissions->checkPermissions('game_api') || $this->permissions->checkPermissions('game_maintenance_schedule')) { ?>
	<li>
		<a  role="button"php aria-expanded="false" data-toggle="collapse" class="list-group-item" id="submenu_game_setting" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="#collapseSubmenuGameMaintenanceSchedule"  aria-controls="collapseSubmenuGameMaintenanceSchedule" data-placement="right" title="<?=lang('sys.gm.setting');?>">
            <i class="icon-stop <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
            <span id="hide_text"<?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                <?=lang('sys.gm.setting');?>
            </span>
        </a>
	        <div class="collapse nvaSubmenu" id="collapseSubmenuGameMaintenanceSchedule">
		        <div class="col-md-0">
		        	<?php if ($this->permissions->checkPermissions('game_api')) {?>
		            <ul class="sidebar-nav" >
		                <li>
		                    <a  id="viewGameApi" class="list-group-item" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'game_api/viewGameApi'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word94');?>">
								<?=lang('system.word94');?>
							</a>
		                </li>
		            </ul>
		            <?php  }
		            if ($this->permissions->checkPermissions('game_api_history')) {?>
		            <ul class="sidebar-nav" >
		                <li>
		                    <a  id="viewGameApiHistory" class="list-group-item" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'game_api/viewGameApiUpdateHistory'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('system.word94');?>">
								<?=lang('Game API History');?>
							</a>
		                </li>
		            </ul>
		            <?php  }
		             if ($this->permissions->checkPermissions('game_maintenance_schedule')) {
		            ?>
		            <ul class="sidebar-nav" >
		                <li>
		                    <a id="viewGameMaintenanceSchedule" class="list-group-item" style="border: 0px;" href="<?=site_url('game_api/viewGameMaintenanceSchedule')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('sys.gm.schedule');?>">
		                        <?=lang('sys.gm.schedule');?>
		                    </a>
		                </li>
		            </ul>
		            <?php
		        }
		             if ($this->permissions->checkPermissions('transactions_daily_summary_report')) {
		            ?>
		            <ul class="sidebar-nav" >
		                <li>
		                    <a id="transactionsDailySummaryReportSettings" class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/transactionsDailySummaryReportSettings')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('sys.gm.transactionsdailysummaryreportsettings');?>">
		                        <?=lang('sys.gm.transactionsdailysummaryreportsettings');?>
		                    </a>
		                </li>
		            </ul>
		            <?php  }?>
		            <?php if ($this->permissions->checkPermissions('game_api') && !$this->utils->setNotActiveOrMaintenance(SBOBET_API)) {?>
		            <ul class="sidebar-nav" >
		                <li>
		                    <a  id="view_sbobet_sport_bet_setting" class="list-group-item" style="border: 0px;margin-bottom:0.1px;" href="<?=BASEURL . 'game_api/view_sbobet_sport_bet_setting'?>" data-toggle="tooltip" data-placement="right" title="<?=lang('SBOBET Sport League Bet Setting');?>">
								<?=lang('SBOBET Sport League Bet Setting');?>
							</a>
		                </li>
		            </ul>
		            <?php  }?>
		        </div>
	       </div>
    </li>
<?php } ?>

<?php if($this->permissions->checkPermissions('game_description') || $this->permissions->checkPermissions('game_type')) { ?>
    <li>
        <a  role="button"php aria-expanded="false" data-toggle="collapse" class="list-group-item" id="view_game_description" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="#collapseSubmenuGameDescription"  aria-controls="collapseSubmenuGameDescription" data-placement="right" title="<?=lang('Game Description');?>">
            <i class="icon-pencil <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
            <span id="hide_text"<?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
                <?=lang('Game List');?>
            </span>
        </a>
        <div class="collapse nvaSubmenu" id="collapseSubmenuGameDescription">
            <div class="col-md-0">
            	<?php if($this->permissions->checkPermissions('game_description')) {?>
                <ul class="sidebar-nav" >
                    <li>
                        <a id="viewGameDescription" class="list-group-item" style="border: 0px;" href="<?=site_url('game_description/viewGameDescription')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('gamedesc.1');?>">
                            <?=lang('gamedesc.1');?>
                        </a>
                    </li>
                </ul>
                <ul class="sidebar-nav" >
                    <li>
                        <a id="view_game_tags" class="list-group-item" style="border: 0px;" href="<?=site_url('game_description/view_game_tags')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Game Tags');?>">
                            <?=lang('View Game Tags');?>
                        </a>
                    </li>
                </ul>
				<ul class="sidebar-nav" >
                    <li>
                        <a id="view_game_tag" class="list-group-item" style="border: 0px;" href="<?=site_url('game_tag/viewGameTag')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Game Tags');?>">
                            <?=lang('Game Tags');?>
                        </a>
                    </li>
                </ul>
                <?php }
                if ($this->permissions->checkPermissions('game_type')) {?>
                <ul class="sidebar-nav">
                    <li>
                        <a id="viewGameType" class="list-group-item" style="border: 0px;" href="<?=site_url('game_type/viewGameType')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('gamedesc.2');?>">
                            <?=lang('system.word96');?>
                        </a>
                    </li>
                </ul>
                <?php }
                if ($this->permissions->checkPermissions('dev_manual_sync_gamelist') && $this->users->isT1User($this->authentication->getUsername()) && $this->utils->getConfig('enable_dev_manual_sync_gamelist_from_json')) {?>
                <ul class="sidebar-nav">
                    <li>
                        <a id="devManualSyncFromJSON" class="list-group-item" style="border: 0px;" href="<?=site_url('game_description/dev_manual_sync_gamelist_from_json')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('gamedesc.2');?>">
                            <?=lang('Dev Manual Sync From JSON');?>
                        </a>
                    </li>
                </ul>
                <?php }
                if ($this->permissions->checkPermissions('dev_manual_sync_gamelist') && $this->users->isT1User($this->authentication->getUsername()) && !$this->utils->getConfig('enable_dev_manual_sync_gamelist_from_json')) {?>
                <ul class="sidebar-nav">
                    <li>
                        <a id="devManualSyncFromGameGateway" class="list-group-item" style="border: 0px;" href="<?=site_url('game_description/dev_manual_sync_gamelist_from_gategateway')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('gamedesc.2');?>">
                            <?=lang('Dev Manual Sync From GameGateway');?>
                        </a>
                    </li>
                </ul>
            <?php } ?>
            </div>
    	</div>
  	</li>
<?php } ?>

<?php
// -- OGP-8657 Remove LiveChat Setting from the SBE system menu
// -- Set this to true to re-enable access to pages of live chat management
$show_livechat = false;

if ( $show_livechat == true && ($this->utils->isEnabledFeature('show_admin_support_live_chat') || $this->utils->isEnabledFeature('enable_player_center_live_chat') || $this->utils->isEnabledFeature('enable_player_center_mobile_live_chat')) && $this->permissions->checkPermissions('live_chat_settings') ){ ?>
		<li>
			<a class="list-group-item" id="livechatSetting" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('livechat_management/livechatSetting');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Livechat Setting');?>">
				<i class="glyphicon glyphicon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
				<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
					<?=lang('Livechat Setting');?>
				</span>
			</a>
		</li>
<?php } ?>

	<?php if ($this->permissions->checkPermissions('risk_score_settings') && $this->utils->isEnabledFeature('show_risk_score')): ?>
		<li>
			<a class="list-group-item" id="risk_score_setting" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/risk_score_setting');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('risk score');?>">
				<i class="glyphicon glyphicon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
				<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
					<?=lang('risk score');?>
				</span>
			</a>
		</li>
	<?php endif; ?>
	<?php if ($this->permissions->checkPermissions('kyc_settings') && $this->utils->isEnabledFeature('show_kyc_status')): ?>
		<li>
			<a class="list-group-item" id="kyc_setting" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('system_management/kyc_setting');?>" data-toggle="tooltip" data-placement="right" title="<?=lang('KYC');?>">
				<i class="glyphicon glyphicon-cog <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
				<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> >
					<?=lang('KYC');?>
				</span>
			</a>
		</li>
	<?php endif; ?>

<?php if ( $this->permissions->checkPermissions('system_settings_smart_backend') || $this->permissions->checkPermissions('system_settings_player_center') ) : ?>
	<li>
		<a role="button" aria-expanded="false" data-toggle="collapse" class="list-group-item" id="system_settings" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="#collapseSubmenu_sys_settings"  aria-controls="collapseSubmenu_sys_settings" data-placement="right" title="<?=lang('System Settings');?>">
			<i class="icon-tree <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
			<span id="hide_text"<?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> ><?=lang('System Settings');?></span>
		</a>
		<div class="collapse nvaSubmenu" id="collapseSubmenu_sys_settings">
			<div class="col-md-0">
				<ul class="sidebar-nav" id="sys_settings_submenu">
					<?php if ( $this->permissions->checkPermissions('system_settings_smart_backend') ) : ?>
					<li>
						<a id="sys_settings_smart_backend" class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/system_settings/smart_backend')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('sys_settings_smart_backend');?>">
							<?=lang('sys_settings_smart_backend');?>
						</a>
					</li>
					<?php endif; ?>
					<?php if ( $this->permissions->checkPermissions('system_settings_player_center') ) : ?>
					<li>
						<a id="sys_settings_player_center" class="list-group-item" style="border: 0px;" href="<?=site_url('system_management/system_settings/player_center')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Player Center');?>">
							<?=lang('Player Center');?>
						</a>
					</li>
					<?php endif; ?>
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
