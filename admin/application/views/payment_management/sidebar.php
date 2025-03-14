<ul class="sidebar-nav" id="sidebar">
<?php $permissions = $this->permissions->getPermissions()?>
<?php if ($permissions != null) {
	?>
	<?php foreach ($permissions as $value) {
		?>
		<?php switch ($value) {
		case 'deposit_list': ?>
				<li>
					<a class="list-group-item" id="deposit_list" style="border: 0px;line-height: 1.42857143;margin-bottom:0.1px;" href="<?=site_url('/home/nav/deposit_today')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('role.74');?>">
						<i class="fa fa-money <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:initial;"' : 'style="display:none;"';?> ><?=lang('role.74');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'payment_withdrawal_list': ?>
				<li>
					<a class="list-group-item" id="view_withdrawal" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('/home/nav/withdrawal/true')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.04');?>">
						<i class="icon-drawer <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('pay.04');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'view_withdrawal_abnormal': ?>
				<?php if($this->utils->getConfig("enabled_withdrawal_abnormal_notification")) { ?>
				<li>
					<a class="list-group-item" id="view_withdrawal_abnormal" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/view_withdrawal_abnormal')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Excess Withdrawal Requests');?>">
						<i class="glyphicon glyphicon-exclamation-sign <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('Excess Withdrawal Requests');?></span>
					</a>
				</li>
				<?php } ?>
			<?php break;?>

			<?php case 'transfer_request': ?>
                <?php if($this->utils->getConfig('seamless_main_wallet_reference_enabled') && !$this->utils->getConfig('still_enabled_transfer_list_on_seamless_wallet')) {
                    break;
                } ?>
				<li>
					<a class="list-group-item" id="transfer_request" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/transfer_request')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Transfer Request');?>">
						<i class="glyphicon glyphicon-transfer <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('Transfer Request');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'report_transactions': ?>
				<li>
					<a class="list-group-item" id="view_transaction" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('payment_management/viewtransactionList'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('pay.transactions');?>">
						<i class="fa fa-table <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('pay.transactions');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'new_deposit': ?>
				<li>
					<a class="list-group-item" id="new_deposit" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('payment_management/newDeposit'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('lang.newDeposit');?>">
						<i class="fa fa-plus <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('lang.newDeposit');?></span>
					</a>
				</li>
			<?php break;?>
			<?php case 'new_internal_withdrawal': ?>
				<li>
					<a class="list-group-item" id="new_internal_withdrawal" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('payment_management/newInternalWithdrawal'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('New Internal Withdrawal');?>">
						<i class="fa fa-plus <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('New Internal Withdrawal');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'new_withdrawal': ?>
				<li>
					<a class="list-group-item" id="new_withdrawal" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('payment_management/newWithdrawal'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('lang.newWithdrawal');?>">
						<i class="fa fa-minus <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('lang.newWithdrawal');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'exception_order_list': ?>
				<li>
					<a class="list-group-item" id="exception_order_list" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/exception_order_list')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Exception Orders');?>">
						<i class="fa fa-exclamation-circle <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('Exception Orders');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'lock_deposit_list': ?>
			<li>
				<a class="list-group-item" id="lock_deposit" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/lockedDepositList')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Locked Deposit');?>">
					<i class="fa fa-credit-card-alt <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
					<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('Locked Deposit');?></span>
				</a>
			</li>
			<?php break;?>

			<?php case 'lock_withdrawal_list': ?>
			<li>
				<a class="list-group-item" id="lock_withdrawal" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/lockedWithdrawalList')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Locked Withdraw');?>">
					<i class="fa fa-shopping-bag <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
					<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> > <?=lang('Locked Withdraw');?></span>
				</a>
			</li>
			<?php break;?>

			<?php case 'batch_deposit': ?>
				<li>
					<a class="list-group-item" id="batch_deposit" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/batchDeposit')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Batch Deposit');?>">
						<i class="fa fa-users <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('Batch Deposit');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'withdrawal_risk_process_list': ?>
				<li>
					<a class="list-group-item" id="withdrawal_risk_process_list" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/withdrawal_risk_process_list')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Withdrawal Risk Process');?>">
						<i class="glyphicon glyphicon-indent-left <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'style="display:none;"';?> ><?=lang('Withdrawal Risk Process');?></span>
					</a>
				</li>
			<?php break;?>

			<?php case 'unusual_notification_requests_list': ?>
				<?php if($this->utils->getConfig("enabled_unusual_notification_requests_list")) { ?>
					<li>
						<a class="list-group-item" id="unusual_notification_requests_list" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/unusualNotificationRequestsList')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Unusual Notification Requests');?>">
							<i class="fa fa-exclamation-circle <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
							<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('Unusual Notification Requests');?></span>
						</a>
					</li>
				<?php } ?>
			<?php break;?>

            <?php case 'middle_conversion_exchange_rate_log': ?>
                <?php if( $this->utils->isEnabledMDB() ): ?>
				<li>
					<a class="list-group-item" id="middle_conversion_exchange_rate_log" style="border: 0px;margin-bottom:0.1px;" href="<?=site_url('payment_management/middle_exchange_rate_log')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('Multi-currencies Middle Conversion Exchange Rate');?>">
						<i class="glyphicon glyphicon-ice-lolly-tasted <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
						<span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'style="display:none;"';?> ><?=lang('Multi-currencies middle conversion exchange rate');?></span>
					</a>
				</li>
                <?php endif; // EOF if( $this->utils->isEnabledMDB() ):... ?>
			<?php break;?>

			<?php default: ?>
			<?php break;?>

		<?php }?>
	<?php }
	?>
<?php }
?>


</ul>
<ul id="sidebar_menu" class="sidebar-nav">
	<li class="sidebar-brand">
		<a id="menu-toggle" href="#" onclick="Template.changeSidebarStatus();">
			<span id="main_icon" class="icon-arrow-<?=($this->session->userdata('sidebar_status') == 'active') ? 'left' : 'right';?> pull-right"></span></span>
		</a>
	</li>
</ul>
<script type="text/javascript">
	$( document ).ready(function() {
	    $(document).on("click","#menu-toggle",function(){
	    	if($( "#wrapper" ).hasClass( "active" )){
	    		$('.sbn').attr('style','font-size: 12px;margin-right: 10px;position: absolute;right: 0;');
	    	}else{
	    		$('.sbn').attr('style','font-size: 8px;margin-right: 1px;position: absolute;right: 0;');
	    	}
		});
	});
</script>