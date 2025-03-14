<?php
    $_is_forced_dryrun_in_vipsettingid = true;
    if($this->utils->isEnabledMDB()){
        $_isEnable4syncVipGroup2others = $this->utils->_getSyncVipGroup2othersWithMethod('SyncMDBSubscriber::syncMDB');
        $_is_forced_dryrun_in_vipsettingid = empty($_isEnable4syncVipGroup2others);
    }
?><style type="text/css">
	.panel-body .row{
		margin: 4px;
	}
	.loading-buttons {
		display: inline-flex;
		flex-direction: column;
		margin-top: 1em;
	}
</style>

<form action="<?=site_url('system_management/post_debug_queue'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Queue Server'); ?>: <?=$redis_channel_key?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#queue_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>

		<div id="queue_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div>
					<?php if(!empty($queue_server_info)){?>
						<pre>
							<code class="json">
								<?=json_encode($queue_server_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>
							</code>
						</pre>
						<a class="btn btn-portage" href="<?=site_url('system_management/post_debug_queue')?>"><?=lang('Debug Queue')?></a>
			            <a class="btn btn-portage" href="<?=site_url('system_management/post_debug_async_event')?>"><?=lang('Debug Async Event')?></a>
			            <a class="btn btn-portage" href="<?=site_url('system_management/post_debug_auto_queue')?>"><?=lang('Debug Auto Queue')?></a>
					<?php }else{?>
						<div class="text-warning"><?=lang('Wrong Queue Server')?></div>
					<?php }?>
				</div>
			</div>
		</div>
	</div>
</form>

<form action="<?=site_url('system_management/post_clear_memory_cache'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Clear Memory Cache'); ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#clear_cache_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="clear_cache_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div>
					<?=lang('It will clear all memory cache, like settings. so most settings will restore from DB')?>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-chestnutrose" value="<?=lang('Do it !'); ?>">
			</div>
		</div>
	</div>
</form>
<form action="<?=site_url('system_management/post_restart_queue_server'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Restart Queue Program'); ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#restart_queue_program" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="restart_queue_program" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div>
					<?=lang('It will signal to og sync server to restart running queue program on the supervisorctl.')?>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-chestnutrose" value="<?=lang('Restart !'); ?>">
			</div>
		</div>
	</div>
</form>
<form action="<?=site_url('system_management/clear_acl_ip'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Unblock IP for www/player login'); ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#clear_acl_ip_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="clear_acl_ip_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div>
					<?=lang('Unblock www/player login IP')?>
				</div>
				<div class="row">
				<div class="col-md-4">
					<select name="acl_player_config_key">
						<option value="iframe_login">iframe_login</option>
						<option value="login">login</option>
						<option value="loginCaptcha">loginCaptcha</option>
					</select>
					<input class="form-control input-sm" type="text" name="acl_player_ip" value="">
				</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-chestnutrose" value="<?=lang('Unblock'); ?>">
			</div>
		</div>
	</div>
</form>
<?php if(!$this->utils->getConfig('disabled_sync_game_logs_on_sbe')){?>
<form action="<?=site_url('system_management/post_sync_game_logs'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync Game Logs')?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#sync_game_logs_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="sync_game_logs_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date');?>
					</div>
					<div class="col-md-6">
		                <input id="sync_game_date" class="form-control input-sm dateInput" data-start="#by_date_from" data-end="#by_date_to" data-time="true"/>
		                <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$by_date_from;?>" />
		                <input type="hidden" id="by_date_to" name="by_date_to"  value="<?=$by_date_to;?>"/>
		                <strong><span class="text-info small" id="sync-game-rules-info"></span></strong>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="by_game_platform_id" id="by_game_platform_id">
							<option value=""><?=lang('All');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Player');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="playerName" value="<?=$playerName?>">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Merge Only');?>
					</div>
					<div class="col-md-4">
						<input type="checkbox" name="merge_only" value="true">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Original Only');?>
					</div>
					<div class="col-md-4">
						<input type="checkbox" name="only_original" value="true">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Dry Run');?>
					</div>
					<div class="col-md-4">
						<input type="checkbox" name="dry_run" value="true">
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>
<?php }?>

<script>

	/**
	 * for apply restrict-max-range function of daterangepicker
	 * @param {integer} end_date The timestamp of end_date
	 * @param {integer} start_date The timestamp of start_date
	 * @param {integer} restriction The timestamp of restriction, thats usually from data-restrict-max-range attribe by days.
	 * @return {bool} If true then valid selected, else revert input after show alert text.
	 */
	function restriction_callback(end_date, start_date, restriction){
		return (end_date - start_date) == restriction-1;
	}

	function getSyncDateRule(){

		var today = new Date(),
		thisMonth = today.getMonth()+1, //zero based
		thisYear = today.getFullYear(),
		minDatetime,maxDatetime,billingDay = <?php echo  $this->utils->getConfig('billing_day'); ?>;

		if(today.getDate() > billingDay  ){
			//only sync this month
		    minDatetime = new Date();
			minDatetime.setDate(1);
			minDatetime.setHours(0,0,0)
			maxDatetime = new Date(thisYear,thisMonth, 0);
			maxDatetime.setHours(23,59,59)
			//console.log(minDatetime);console.log(maxDatetime)

		}else{
			//only sync last month to this month
		    minDatetime = new Date(thisYear,thisMonth -1, 0);
			minDatetime.setDate(1);
			minDatetime.setHours(0,0,0)
			maxDatetime = new Date(thisYear,thisMonth, 0);
			maxDatetime.setHours(23,59,59)
			//console.log(minDatetime);console.log(maxDatetime);
		}

		return {minDatetime:minDatetime,maxDatetime:maxDatetime};
	}

	$(document).ready(function(){
		var syncGameRulesInfo =  getSyncDateRule(),
		minDatetime = moment(syncGameRulesInfo.minDatetime).format("YYYY-MM-DD HH:mm:ss"),
		maxDatetime = moment(syncGameRulesInfo.maxDatetime).format("YYYY-MM-DD HH:mm:ss");
		$('#sync-game-rules-info').html('<?php echo lang('You can only sync game logs by this dates')?> '+minDatetime+' - '+maxDatetime);
	});//doc ready end
</script>

<form action="<?=site_url('system_management/post_rebuild_games_total'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Rebuild Totals') ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#rebuild_totals_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>

		<div id="rebuild_totals_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date');?>
					</div>
					<div class="col-md-6">
						<input id="sync_total_date_rebuild" class="form-control input-sm dateInput" data-start="#rebuild_totals_by_date_from" data-end="#rebuild_totals_by_date_to" data-time="true"/>
						<input type="hidden" id="rebuild_totals_by_date_from" name="by_date_from" value="<?=$by_date_from;?>" />
						<input type="hidden" id="rebuild_totals_by_date_to" name="by_date_to"  value="<?=$by_date_to;?>"/>
						<?php if(!empty($lock_rebuild_reports_range)):?>
							<strong>
								<span class="text-info small" id="lock-reports-info"><?=sprintf(lang("Regenarate All Report has lock - should not be equal or older  %s  "),$lock_rebuild_reports_range['cutoff_day']); ?></span>
							</strong>
				    	<?php endif;?>
					</div>
				</div>
				<script>
					$(document).ready(function(){
						var rebuildMinute = $('#rebuild_minute'),
						rebuildHour = $('#rebuild_hour');
						rebuildMinute.change(function(){
							if($(this).is(":checked")){
								rebuildHour.prop("disabled", false);
							}else{
								rebuildHour.prop("disabled", true);
							}
						});
					});
				</script>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Rebuild Hour');?>
					</div>
					<div class="col-md-4">
						<input type="checkbox" id="rebuild_hour" name="rebuild_hour" checked value="true">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Rebuild Minute');?>
					</div>
					<div class="col-md-4">
						<input type="checkbox" id="rebuild_minute" name="rebuild_minute" checked value="true">
					</div>
				</div>
			</div>

			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<form action="<?=site_url('system_management/post_rebuild_seamless_balance_history'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Rebuild Seamless Balance History') ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#rebuild_seamless_balance_history_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>

		<div id="rebuild_seamless_balance_history_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date');?>
					</div>
					<div class="col-md-6">
						<input id="sync_total_date" class="form-control input-sm dateInput" data-start="#rebuild_seamless_balance_history_by_date_from" data-end="#rebuild_seamless_balance_history_by_date_to" data-time="true"/>
						<input type="hidden" id="rebuild_seamless_balance_history_by_date_from" name="by_date_from" value="<?=$by_date_from;?>" />
						<input type="hidden" id="rebuild_seamless_balance_history_by_date_to" name="by_date_to"  value="<?=$by_date_to;?>"/>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="by_game_platform_id" id="by_game_platform_id_rebuild_seamless">
							<option value=""><?=lang('All');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Rebuild Game Transactions');?>
					</div>
					<div class="col-md-4">
						<input type="checkbox" id="rebuild_game_transactions" name="rebuild_game_transactions" checked value="true">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Rebuild Balance Transfers');?>
					</div>
					<div class="col-md-4">
						<input type="checkbox" id="rebuild_game_balance_transfers" name="rebuild_game_balance_transfers" checked value="true">
					</div>
				</div>
			</div>

			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<?php if($this->utils->getConfig('enable_beting_amount_to_point')){ ?>
<form action="<?=site_url('system_management/post_rebuild_points_transaction_report_hour'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Rebuild Points Transaction Report Hour') ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#post_rebuild_points_transaction_report_hour" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="post_rebuild_points_transaction_report_hour" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date Time');?>
					</div>
					<div class="col-md-6">
						<input id="sync_total_date" class="form-control input-sm dateInput" data-start="#rebuild_points_transaction_report_hour_by_date_from" data-end="#rebuild_points_transaction_report_hour_by_date_to" data-time="true">
						<input type="hidden" id="rebuild_points_transaction_report_hour_by_date_from" name="by_date_from" value="<?=$by_date_from;?>">
						<input type="hidden" id="rebuild_points_transaction_report_hour_by_date_to" name="by_date_to"  value="<?=$by_date_to;?>">
					</div>
				</div>
                <div class="row">
					<div class="col-md-2">
						<?=lang('Player Username');?>
					</div>
					<div class="col-md-3">
						<input class="form-control input-sm" type="text" name="player_name" value="<?= $playerName ?>">
					</div>
				</div>
                <div class="row">
					<div class="col-md-2">
						<?=lang('Sync Player Points');?>
					</div>
					<div class="col-md-4">
						<input type="checkbox" name="is_sync_player_points" value="true">
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>
<?php }?>

<form action="<?=site_url('system_management/regenerate_all_report'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Regenerate All Report') ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#regenerate_all_report" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="regenerate_all_report" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date');?>
					</div>
					<div class="col-md-6">
						<input class="form-control input-sm dateInput" data-start="#regenerate_all_report_by_date_from" data-end="#regenerate_all_report_by_date_to" data-time="false"/>
						<input type="hidden" id="regenerate_all_report_by_date_from" name="by_date_from" value="<?=date('Y-m-d', strtotime('-1 day'));?>" />
						<input type="hidden" id="regenerate_all_report_by_date_to" name="by_date_to"  value="<?=date('Y-m-d', strtotime('-1 day'))?>"/>
						<?php if(!empty($lock_rebuild_reports_range)):?>
							<strong>
								<span class="text-info small" id="lock-reports-info">
									<?=sprintf(lang("Regenarate All Report has lock - should not be equal or older  %s  "),$lock_rebuild_reports_range['cutoff_day']); ?>
								</span>
							</strong>
				    	<?php endif;?>
					</div>
				</div>
			</div>

			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<form action="<?=site_url('system_management/post_sync_t1_gamegateway'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync T1 Gamegateway') ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#sync_t1_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="sync_t1_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date');?>
					</div>
					<div class="col-md-4">
						<input id="sync_t1_date" class="form-control input-sm dateInput" data-start="#sync_t1_by_date_from" data-end="#sync_t1_by_date_to" data-time="true"/>
						<input type="hidden" id="sync_t1_by_date_from" name="by_date_from" value="<?=$by_date_from;?>" />
						<input type="hidden" id="sync_t1_by_date_to" name="by_date_to"  value="<?=$by_date_to;?>"/>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Player Username');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="playerName" value="<?=$playerName?>">
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Run'); ?>">
			</div>
		</div>
	</div>
</form>

<?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
	<form action="<?=site_url('system_management/post_sync_cashback'); ?>" method="POST">
		<div class="panel panel-primary panel_main">
			<div class="panel-heading">
				<h4 class="panel-title"><?=lang('Sync Cashback'); ?>
					<span class="pull-right">
						<a data-toggle="collapse" href="#cashback_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
					</span>
				</h4>
			</div>

			<div id="cashback_panel" class="panel-collapse collapse in ">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-2">
							<?=lang('Date');?>
						</div>
						<div class="col-md-4">
			                <input id="cashback_date_picker" class="form-control input-sm dateInput" data-start="#cashback_date" data-time="false"/>
			                <input type="hidden" id="cashback_date" name="cashback_date" value="<?=$cashback_date;?>" />
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<?=lang('Dry Run');?>
						</div>
						<div class="col-md-4">
							<input type="checkbox" name="dry_run" value="true">
						</div>
					</div>
				</div>
				<div class="panel-footer">
					<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
				</div>
			</div>
		</div>
	</form>
<?php endif; ?>

<?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
	<form action="<?=site_url('system_management/post_calculate_aff_earnings'); ?>" method="POST">
		<div class="panel panel-primary panel_main">
			<div class="panel-heading">
				<h4 class="panel-title"><?=lang('Generate Affiliate Earnings'); ?>
					<span class="pull-right">
						<a data-toggle="collapse" href="#aff_earnings" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
					</span>
				</h4>
			</div>
			<div id="aff_earnings" class="panel-collapse collapse in ">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-2">
							<?=lang('Affiliate Username');?>
						</div>
						<div class="col-md-4">
			                <input class="form-control input-sm" name="username"/>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<?=lang('Date');?>
						</div>
						<div class="col-md-4">
			                <input id="aff_earnings_date_picker" class="form-control input-sm dateInput" data-start="#startDate" data-end="#endDate" data-time="false"/>
			                <input type="hidden" id="startDate" name="startDate" value="<?=date('Y-m-d', strtotime('-1 day'));?>" />
			                <input type="hidden" id="endDate" name="endDate" value="<?=date('Y-m-d', strtotime('-1 day'))?>" />
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<?=lang('Dry Run');?>
						</div>
						<div class="col-md-4">
							<input type="checkbox" name="dry_run" value="true">
						</div>
					</div>
				</div>

				<div class="panel-footer">
					<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
				</div>
			</div>
		</div>
	</form>
<?php endif; ?>

<form action="<?=site_url('system_management/post_adjust_game_report'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
	    <div class="panel-heading">
	        <h4 class="panel-title"><?=lang('Adjust Game Report'); ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#adjust_game_report" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
	        </h4>
	    </div>
	    <div id="adjust_game_report" class="panel-collapse collapse in ">
		    <div class="panel-body">
		        <div class="row">
		            <div class="col-md-2">
		            	<?=lang('Password');?>
		            </div>
		            <div class="col-md-4">
		                <input type="password" class="form-control input-sm" name="password" value="" />
		            </div>
		        </div>
		        <div class="row">
		            <div class="col-md-2">
		            	<?=lang('Year Month');?>
		            	format: YYYYmm
		            </div>
		            <div class="col-md-4">
		                <input type="text" class="form-control input-sm" name="adjust_year_month" value="" />
		            </div>
		        </div>
		        <div class="row">
		            <div class="col-md-12">
		            	<?=lang('Adjust Content');?>
		            	format: username,game_description_id,bet,result
		                <textarea name="adjust_content" class="form-control input-sm" rows="10"></textarea>
		            </div>
		        </div>
		    </div>
		    <div class="panel-footer">
		        <input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
		    </div>
	    </div>
	</div>
</form>

<div class="panel panel-primary panel_main">
    <div class="panel-heading">
        <h4 class="panel-title"><?=lang('Import Players'); ?>
			<span class="pull-right">
				<a data-toggle="collapse" href="#import_players" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
			</span>
        </h4>
    </div>
    <div id="import_players" class="panel-collapse collapse in ">
    	<div class="panel-body">
			<a href="<?=site_url('/player_management/import_players')?>" class="btn btn-sm btn-portage"><?=lang('Import Players by Upload CSV')?></a>
    	</div>
    </div>
</div>

<div class="panel panel-primary panel_main">
    <div class="panel-heading">
        <h4 class="panel-title"><?=lang('Game Syncing Method List'); ?>
			<span class="pull-right">
				<a data-toggle="collapse" href="#sync_methods" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
			</span>
        </h4>
    </div>

    <div id="sync_methods" class="panel-collapse collapse in ">
	    <div class="panel-body">
	    	<table class="table table-striped" id="sync_methods_table">
	    		<thead>
	    			<tr>
	    				<th><?=lang('Game Platform ID')?></th>
	    				<th><?=lang('Game Name')?></th>
	    				<th><?=lang('Syncing By / Status')?></th>
	    			</tr>
	    		</thead>
	    		<tbody>
	    			<?php if (empty($api_with_sync_method)): ?>
	    				<tr><td colspan="3" style="text-align: center"><?=lang('lang.norec')?></td></tr>
	    			<?php else: ?>
		    			<?php foreach ($api_with_sync_method as $key => $value): ?>
		    				<tr>
			    				<td><?=isset($value['id']) && trim($value['id']) != "" ? $value['id']: lang('lang.norecyet')?></td>
			    				<td><?=isset($value['system_name']) && trim($value['system_name']) != "" ? $value['system_name']: lang('lang.norecyet')?></td>
			    				<td><?=isset($value['sync_status']) && trim($value['sync_status']) != ""  ? $value['sync_status']: lang('lang.norecyet')?></td>
			    			</tr>
		    			<?php endforeach ?>
	    			<?php endif ?>
	    		</tbody>
	    	</table>
	    </div>
    </div>
</div>

<form action="<?=site_url('system_management/post_batch_sync_balance'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Batch Sync All Player Balance')?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#batch_sync_all_player_balance" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="batch_sync_all_player_balance" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Mode');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="mode" id="mode">
							<option value="available" selected><?=lang('Available');?></option>
							<option value="last_one_hour"><?=lang('Last One Hour');?></option>
							<option value="all"><?=lang('All');?></option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Max Number');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="number" name="max_number" value="999999">
					</div>
				</div>

				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="apiId" id="apiId">
							<option value="" selected><?=lang('All');?></option>

							<?php foreach ($this->utils->getGameSystemMap() as $key => $value): ?>
							<option value="<?=$key;?>"><?=$value;?></option>
							<?php endforeach ?>
						</select>
					</div>
				</div>

				<div class="row">
					<div class="col-md-2">
						<?=lang('Dry Run');?>
					</div>
					<div class="col-md-4">
					<input type="checkbox" name="dry_run" value="true">
					</div>
				</div>
			</div>

			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<form action="<?=site_url('system_management/post_sync_mdb'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync Currency'); ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#sync_mdb" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="sync_mdb" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Player Username');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="playerUsername">
					</div>
				</div>
                <div class="row">
					<div class="col-md-2">
						<?=lang('Player Level by Username');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="playerLevelByUsername">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Agency Username');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="agencyUsername">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Affiliate Username');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="affiliateUsername">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Admin Username');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="adminUsername">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Role Name');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="roleName">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Reg Setting Type');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="regSettingType">
							<option value=""><?=lang('NONE')?></option>
							<option value="<?=Marketing::TYPE_PLAYER_REGISTRATION?>"><?=lang('Player Registration Settings')?></option>
							<option value="<?=Marketing::TYPE_AFFILIATE_REGISTRATION?>"><?=lang('Affiliate Registration Settings')?></option>
						</select>
					</div>
				</div>
                <div class="row">
					<div class="col-md-2">
						<?=lang('VIP Group Levels');?>
					</div>
					<div class="col-md-4">
                        <?=form_dropdown('vipsettingid', $vipSettingList, [], 'class="form-control input-sm" required id="vipsettingid" '); ?>
                    </div>
                    <div class="col-md-2" style="padding-top: 8px;">
                        <button id="preview_in_vipsettingid" type="button" class="btn btn-xs btn-portage btn_preview_in_vipsettingid">
                            <?= lang('Preview') ?> / <?=lang('Dry Run');?>
                        </button>
                    </div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-chestnutrose" value="<?=lang('Sync'); ?>">
			</div>
		</div>
	</div>
</form>

<form action="<?=site_url('system_management/batch_move_deposit_note_to_sale_orders_notes'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Batch move deposit note to sale_orders_notes'); ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#move_deposit_notes_data" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="move_deposit_notes_data" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div>
					<?=lang('It will move original deposit note data to sale_orders_notes table')?>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<form action="<?=site_url('system_management/batch_move_withdrawal_note_to_walletaccount_notes'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Batch move withdrawal note to walletaccount_notes'); ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#move_withdrawal_notes_data" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="move_withdrawal_notes_data" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div>
					<?=lang('It will move original withdrawal note data to walletaccount_notes table')?>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<?php if ($this->utils->getConfig('enable_dev_func_manual_update_admin_dashboard')) : ?>
<form action="<?=site_url('system_management/post_update_admin_dashboard'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?= lang('devfunc.manual_update_admin_dashboard'); ?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#manual_update_admin_dashboard" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="manual_update_admin_dashboard" class="panel-collapse collapse in">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						<p>
							<?=lang('devfunc.rebuilds_dashboard')?>
						</p>
						<div>
							<div class="small">
								<?= lang('devfunc.last_update') ?>:
								<?= $updated_at ?: '&mdash;' ?>
							</div>
							<table class="small devfunc">
								<tr>
									<th><?= lang('devfunc.base_date') ?>:</th>
									<td class="text-success"><?= $date_base ?: '&mdash;' ?></td>
								</tr>
								<tr>
									<th><?= lang('devfunc.date_range') ?>:</th>
									<td class="text-success"><?= $date_range_from ?> &mdash; <?= $date_range_to ?></td>
								</tr>
								<tr>
									<th><?= lang('devfunc.disp_date') ?>:</th>
									<td class="text-success"><?= $date_disp ?: '&mdash;' ?></td>
								</tr>
							</table>
						</div>
						<hr />
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<?=lang('devfunc.base_date')?>
						<ul class="small">
		                	<li><?= lang('devfunc.base_date.1a') ?> <strong><?= lang('devfunc.base_date.1b') ?></strong></li>
		                	<li><?= lang('devfunc.base_date.2a') ?> <strong><?= lang('devfunc.base_date.2b') ?></strong></li>
		                	<li><?= lang('devfunc.base_date.3a') ?> <strong><?= lang('devfunc.base_date.3b') ?></strong></li>
		                	<li><?= lang('devfunc.base_date.4a') ?> <strong><?= lang('devfunc.base_date.4b') ?></strong></li></li>
		                </ul>
					</div>
					<div class="col-md-4">
		                <input name="date_base" id="date_base" class="form-control input-sm dateInput" type="text" value="<?= set_value('date', $this->utils->getNowForMysql()) ?>" data-time="true" />
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<?= lang('devfunc.date_range') ?>
						<ul class="small">
							<li><?= lang('devfunc.date_range.1') ?></li>
							<li><?= lang('devfunc.date_range.2') ?></li>
		                </ul>
					</div>
					<div class="col-md-4">
						<input id="daterange_range" class="form-control input-sm dateInput"
							data-start="#date_range_from"
							data-end="#date_range_to"
							data-time="false"
							data-restrict-max-range="7"
							data-restriction-callback="restriction_callback"
							data-restrict-range-label="<?= sprintf(lang('devfunc.The_time_frame_must_n_days'), 7)?>"
							data-override-on-apply="false"
							/>
						<input type="hidden" id="date_range_from" name="date_range_from" value="<?= date('Y-m-d', strtotime('-6 days')) ?>" />
						<input type="hidden" id="date_range_to" name="date_range_to"  value=""/>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<?= lang('devfunc.disp_date') ?>
						<ul class="small">
		                	<li><?= lang('devfunc.disp_date.1') ?></li>
		                </ul>
					</div>
					<div class="col-md-4">
		                <input name="date_disp" id="date_disp" class="form-control input-sm dateInput" type="text" value="<?= set_value('date', $this->utils->getNowForMysql()) ?>" data-time="true" />
		                <div>
		                	<button id="date_disp_set_today" type="button" class="btn btn-sm btn-success date_disp set_today">
		                		<?= lang('devfunc.use_today') ?>
		                	</button>
		                	<button id="date_disp_set_base" type="button" class="btn btn-sm btn-info date_disp set_base">
		                		<?= lang('devfunc.use_base_date') ?>
		                	</button>
		                </div>
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-primary" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>
<?php endif; ?>

<div class="panel panel-primary panel_main">
	<div class="panel-heading">
		<h4 class="panel-title"><?=lang('Set Registration Fields To Default Order'); ?>
			<span class="pull-right">
				<a data-toggle="collapse" href="#set_registration_fields_order" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
			</span>
		</h4>
	</div>
	<div id="set_registration_fields_order" class="panel-collapse collapse in ">
		<div class="panel-body">
			<div>
				<?=lang('Will reorder according to config "registration_fields_default_order"')?>
			</div>
		</div>
		<div class="panel-footer">
	        <a href="<?= site_url('marketing_management/setRegistrationSettingsToDefaultOrder') ?>" class="btn btn btn-primary">
	            <?= lang('Reset Fields Order') ?>
	        </a>
		</div>
	</div>
</div>

<?php if(!$this->utils->getConfig('disabled_sync_game_logs_on_sbe')){?>
<form action="<?=site_url('system_management/post_check_mgquickfire_livedealer_data'); ?>" method="POST">

	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Check MG Quickfire live dealer data')?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#check_mg_quick_fire_livedealer_data" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="check_mg_quick_fire_livedealer_data" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date');?>
					</div>
					<div class="col-md-3">
		                <input class="form-control input-sm dateInput" data-start="#mg_by_date_from" data-end="#mg_by_date_to" data-time="true"/>
		                <input type="hidden" id="mg_by_date_from" name="mg_by_date_from" value="<?=$by_date_from;?>" />
		                <input type="hidden" id="mg_by_date_to" name="mg_by_date_to"  value="<?=$by_date_to;?>"/>
		                <strong><span class="text-info small" id="sync-game-rules-info"></span></strong>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Player');?>
					</div>
					<div class="col-md-3">
						<input class="form-control input-sm" type="text" name="playerName" value="<?=$playerName?>" required>
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>
<form action="<?=site_url('system_management/post_sync_after_blance'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync Game After Balance')?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#sync_after_balance_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="sync_after_balance_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date');?>
					</div>
					<div class="col-md-6">
		                <input id="sync_game_date_after_balance" class="form-control input-sm dateInput" data-start="#sync_after_balance_by_date_from" data-end="#sync_after_balance_by_date_to" data-time="true"/>
		                <input type="hidden" id="sync_after_balance_by_date_from" name="by_date_from" value="<?=$by_date_from;?>" />
		                <input type="hidden" id="sync_after_balance_by_date_to" name="by_date_to"  value="<?=$by_date_to;?>"/>
		                <strong><span class="text-info small" id="sync-game-rules-info"></span></strong>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="by_game_platform_id" id="sync_balance_by_game_platform_id" required>
							<option value=""><?=lang('Choose Game');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Player');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="player_name" value="<?=$playerName?>" required>
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>
<form action="<?=site_url('player_management/batch_remove_player_tags'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Batch Remove Player Tags')?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#sync_batch_remove_tags_panel" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="sync_batch_remove_tags_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Select Player with tags');?>
					</div>

		            <!-- select tags -->
					<div class="col-md-4">
						<?php echo form_multiselect('select_player_with_tags[]', is_array($tags) ? $tags : [], [], ' class="form-control input-sm chosen-select" id="selected_tags_remove" data-placeholder="" data-untoggle="checkbox" data-target=""') ?>
					</div>

				</div>

				<div class="row">
					<div class="col-md-2">
						<?=lang('Select Player with VIP Level');?>
					</div>

                    <div class="col-md-4">
                        <select name="select_player_with_vip_level" id="playerlevel" class="form-control">
                            <?php foreach ($allLevels as $key => $value) {?>
                            <option value="<?=$key?>"><?=$value?></option>
<?php }
?>
                        </select>
                    </div>

				</div>

				<div class="row">
					<div class="col-md-2">
						<?=lang('Player tags to remove');?>
					</div>

		            <!-- select tags -->
					<div class="col-md-4">
						<?php echo form_multiselect('player_with_tags_to_remove[]', is_array($tags) ? $tags : [], [], ' class="form-control input-sm chosen-select" id="selected_tags" data-placeholder="" data-untoggle="checkbox" data-target=""') ?>
					</div>

				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>
<?php }?>

<form action="<?=site_url('system_management/manual_fix_missing_payout'); ?>" method="POST">

	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Seamless Game Manual Fix Missing Payout')?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#manual_fix_missing_payout" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="manual_fix_missing_payout" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="by_game_platform_id" id="by_game_platform_id_missing_payout">
							<option value=""><?=lang('All');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="row">
		            <div class="col-md-12">
		            	<?=lang('Parameters');?>
		                <textarea name="json_parameters" class="form-control input-sm" rows="10"></textarea>
		            </div>
		        </div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<form action="<?=site_url('system_management/manual_fix_missing_bet'); ?>" method="POST">

	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Seamless Game Manual Fix Missing Bet')?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#manual_fix_missing_bet" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="manual_fix_missing_bet" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="by_game_platform_id" id="by_game_platform_id_missing_bet">
							<option value=""><?=lang('All');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="row">
		            <div class="col-md-12">
		            	<?=lang('Parameters');?>
		                <textarea name="json_parameters" class="form-control input-sm" rows="10"></textarea>
		            </div>
		        </div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<form action="<?=site_url('system_management/manual_fix_missing_refund'); ?>" method="POST">

	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Seamless Game Manual Refund')?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#manual_fix_missing_refund" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="manual_fix_missing_refund" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="by_game_platform_id" id="by_game_platform_id_refund">
							<option value=""><?=lang('All');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="row">
		            <div class="col-md-12">
		            	<?=lang('Parameters');?>
		                <textarea name="json_parameters" class="form-control input-sm" rows="10"></textarea>
		            </div>
		        </div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<?php if ($this->utils->getConfig('enable_test_player_lock_balance')) { ?>
<form action="<?=site_url('system_management/player_lock_balance'); ?>" method="POST">
    <div class="panel panel-primary panel_main">
        <div class="panel-heading">
            <h4 class="panel-title"><?=lang('Player Lock Balance')?>
                <span class="pull-right">
                <a data-toggle="collapse" href="#manual_player_lock_balance" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
                </span>
            </h4>
        </div>
        <div id="manual_player_lock_balance" class="panel-collapse collapse in ">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('Username');?>
                    </div>
                    <div class="col-md-4">
                        <input class="form-control input-sm" type="text" name="username" id="username" placeholder="*Username" value="" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('Seconds');?>
                    </div>
                    <div class="col-md-4">
                        <?php
                            if ($this->utils->getConfig('use_select_test_player_lock_balance_seconds')) {
                                $list_of_seconds = $this->utils->getConfig('test_player_lock_balance_seconds_list');
                                if (is_array($list_of_seconds) && !empty($list_of_seconds)) {
                        ?>
                            <select class="form-control input-sm" name="seconds" id="seconds" style="width: 70px" required>
                                <?php foreach ($list_of_seconds as $seconds) { ?>
                                    <option value="<?= $seconds; ?>" <?php if ($seconds == 120) { echo 'selected'; } ?>><?= $seconds; ?></option>
                                <?php } ?>
                            </select>
                        <?php
                                } else {
                        ?>
                            <input class="form-control input-sm" type="number" name="seconds" id="seconds" placeholder="*Seconds" value="120" max="120" min="10" style="width: 70px" required>
                        <?php
                                }
                            } else {
                        ?>
                            <input class="form-control input-sm" type="number" name="seconds" id="seconds" placeholder="*Seconds" value="120" max="120" min="10" style="width: 70px" required>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
            </div>
        </div>
    </div>
</form>
<?php } ?>
<?php if ($this->utils->getConfig('enable_test_lock_table')) { ?>
<form id='test_lock_table_form' action="<?=site_url('system_management/remote_lock_table'); ?>" method="POST">
    <div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Test Lock Table')?>
				<!-- <span class="lock_count" style="color: blue;">20</span> -->
				<span class="pull-right">
					<a data-toggle="collapse" href="#test_lock" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="test_lock" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-1">
						<?=lang('Game');?>
					</div>
					<div class="col-md-2">
						<select class="form-control input-sm" name="by_game_platform_id" id="by_game_platform_id_on_locktable" required>
							<option value=""><?=lang('Select');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="row">
                    <div class="col-md-1">
                        <?=lang('Table name');?>
                    </div>
                    <div class="col-md-2">
                        <input class="form-control input-sm" type="text" name="lock_table_name" id="lock_table_name" placeholder="*lock_table_name" value="" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1">
                        <?=lang('Seconds');?>
                    </div>
                    <div class="col-md-2">
                        <input class="form-control input-sm" type="number" name="lock_table_sec" placeholder="*Seconds" value="60" min="10" id="lock_table_sec" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1">
                        <?=lang('Timer');?>
                    </div>
                    <div class="col-md-2">
                    	<span class="lock_count badge badge-light">60</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
					  <input class="form-check-input" type="checkbox" value="" id="remote_check" checked>
					  <label class="form-check-label" for="remote_check">
					    Use remote request
					  </label>
					</div>
                </div>
			</div>
			<div class="panel-footer">

				<button type="submit" id="lock_table_button" class="btn btn-primary btn-lg" id="load" data-loading-text="<i class='fa fa-spinner fa-spin'></i>   Ongoing lock table. Kindly wait ..."><i aria-hidden="true"></i>Press me! To lock table...</button>
			</div>
		</div>
	</div>
</form>
<?php } ?>


<?php if ($this->utils->getConfig('enable_free_round_creation')) { ?>
<form id='free_round_creation_form'  action="<?=site_url('system_management/create_free_rounds'); ?>" method="POST">
    <div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Create Free Round')?>
				<!-- <span class="lock_count" style="color: blue;">20</span> -->
				<span class="pull-right">
					<a data-toggle="collapse" href="#free_round" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="free_round" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-1">
						<?=lang('Game');?>
					</div>
					<div class="col-md-2">
						<select class="form-control input-sm" name="free_round_game_platform_id" id="free_round_game_platform_id" required>
							<?php foreach ($games_with_free_rounds as $item) { ?>
								<option value="<?=$item['id'];?>"><?=$item['system_name'];?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<input type="hidden" name="free_round_parameters" id="free_round_parameters">
				<div class="row">
					<div class="form-group"> 
						<p class="bg-danger" id="error-msg" style="padding:10px;display:none;color:#D9534F"></p>
						<label for="extra_info">Parameters</label>
						<pre class="form-control" id="extra_info" name="extra_info" style="height:200px;overflow:auto;">
{
	"name": null,
	"assign_code": null,
	"count": 10,
	"game_code": "null",
	"player_username": null,
	"stake": 0.1,
	"lines": 25
}
</pre>
					</div>
                </div>

			</div>
			<div class="panel-footer">
				<button type="submit" id="create_free_round_btn" class="btn btn-primary btn-md" id="load"><i aria-hidden="true"></i>Create</button>
			</div>
		</div>
	</div>
</form>
<?php } ?>


<?php if ($this->utils->getConfig('enable_batch_refund')) { ?>
<form id='test_betch_refund_form' action="<?=site_url('system_management/remote_batch_refund'); ?>" enctype="multipart/form-data" method="POST">
    <div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Batch Refund')?>
				<!-- <span class="lock_count" style="color: blue;">20</span> -->
				<span class="pull-right">
					<a data-toggle="collapse" href="#batch_refund" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="batch_refund" class="panel-collapse collapse in">
			<div class="panel-body">
				<div class="row">
					<label>Download Sample:  <a href="/sample_batch_refunds.csv" target="_blank">Click Here</a></label>
				</div>
			
				<div class="form-group row">
				<label class="col-md-1"><?php echo lang('Game'); ?></label>
					<div class="col-md-2">
						<select class="form-control input-sm" name="game_platform_id" id="game_platform_id" required>
							<option value=""><?=lang('Select');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="form-group row">
                    <label class="col-md-1"><?php echo lang('Upload File'); ?></label>
                    <div class="col-md-2">
                        <div class="">
                            <input type="file" name="batch_refund_file" class="form-control input-sm" required="required" accept=".csv"/>
                        </div>
                        <section id="note-footer" style="color:red; font-size: 12px; margin-top: 4px;" class="five"><?=lang('Note: Upload file format must be CSV')?></section>
                    </div>
                </div>
			</div>
			<div class="panel-footer">
				<button type="submit" id="lock_table_button" class="btn btn-primary btn-lg" id="load" data-loading-text="<i class='fa fa-spinner fa-spin'></i>   Ongoing batch refund. Kindly wait ..."><i aria-hidden="true"></i>Batch Refund</button>
			</div>
		</div>
	</div>
</form>
<?php } ?>

<?php if ($this->utils->getConfig('set_game_provider_bet_limit')) { ?>
<form id="set_game_provider_bet_limit_form" action="<?=site_url('system_management/set_game_provider_bet_limit'); ?>" method="POST">
    <div class="panel panel-primary panel_main">
        <div class="panel-heading">
            <h4 class="panel-title"><?=lang('Set Game Provider Bet Limit')?>
                <span class="pull-right">
                <a data-toggle="collapse" href="#toggle_set_game_provider_bet_limit" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
                </span>
            </h4>
        </div>
        <div id="toggle_set_game_provider_bet_limit" class="panel-collapse collapse in ">
            <div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?= lang('Game Provider');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="set_bet_limit_game_platform_id" id="set_bet_limit_game_platform_id">
							<option value="" disabled selected>Select Game Provider</option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>

				<!-- username -->
                <div class="row">
                    <div class="col-md-2">
                        <?= lang('Username');?>
                    </div>
                    <div class="col-md-4">
                        <input class="form-control input-sm" type="text" name="set_bet_limit_username" id="set_bet_limit_username" placeholder="*Username" value="" required>
                    </div>
                </div>

				<!-- bet limit type -->
				<div class="row">
					<div class="col-md-2">
						<?= lang('Bet Limit Type'); ?>
					</div>
					<div class="col-md-4" onchange="set_bet_limit_input_type()">
						<input type="radio" name="set_bet_limit_type" value="min_max" id="min_max_radio" checked>
						<label for="min_max_radio">Min/Max Bet Limit</label>

						<input type="radio" name="set_bet_limit_type" value="range" id="range_radio">
						<label for="range_radio">Range Limit ID</label>
					</div>
				</div>

				<div id="max_min_bet_limit_selection">
					<div class="row">
						<div class="col-md-2">
							<?= lang('Min Bet Limit');?>
						</div>
						<div class="col-md-4">
							<input class="form-control input-sm" type="number" name="min_bet_limit" id="min_bet_limit" placeholder="Min Bet Limit" value="">
						</div>
					</div>
					
					<div class="row" >
						<div class="col-md-2">
							<?= lang('Max Bet Limit');?>
						</div>
						<div class="col-md-4">
							<input class="form-control input-sm" type="number" name="max_bet_limit" id="max_bet_limit" placeholder="Max Bet Limit" value="">
						</div>
					</div>	
				</div>

				<div class="row" id="range_limit_id_selection"style="display:none">
					<div class="col-md-2">
						<?= lang('Range Limit ID'); ?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="range_limit_id" id="range_limit_id" placeholder="Range Limit ID" value="">
					</div>
				</div>
				<div class="panel-footer">
					<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
				</div>
			</div>
        </div>
    </div>
</form>
<?php } ?>


<?php if ($this->utils->getConfig('enable_batch_export_player_id')) { ?>
<form id='batch_export_player_id_form' action="<?=site_url('system_management/remote_batch_export_player_id'); ?>" enctype="multipart/form-data" method="POST">
    <div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Batch Export Player Id')?>
				<!-- <span class="lock_count" style="color: blue;">20</span> -->
				<span class="pull-right">
					<a data-toggle="collapse" href="#toggle_batch_export_player_id" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
				</span>
			</h4>
		</div>
		<div id="toggle_batch_export_player_id" class="panel-collapse collapse in">
			<div class="panel-body">
				<div class="row">
					<label>Download Sample:  <a href="/sample_batch_export_player_ids.csv" target="_blank">Click Here</a></label>
				</div>
		
				<div class="form-group row">
                    <label class="col-md-1"><?php echo lang('Upload File'); ?></label>
                    <div class="col-md-2">
                        <div class="">
                            <input type="file" name="batch_export_player_id_file" class="form-control input-sm" required="required" accept=".csv"/>
                        </div>
                        <section id="note-footer" style="color:red; font-size: 12px; margin-top: 4px;" class="five"><?=lang('Note: Upload file format must be CSV')?></section>
                    </div>
                </div>
			</div>
			<div class="panel-footer">
				<button type="submit" id="batch_export_player_id_button" class="btn btn-primary btn-lg" id="load" data-loading-text="<i class='fa fa-spinner fa-spin'></i>   Ongoing batch export. Kindly wait ..."><i aria-hidden="true"></i>Batch Export</button>
			</div>
		</div>
	</div>
</form>
<?php } ?>

<?php if ($this->utils->getConfig('enable_sync_latest_game_records')) { ?>
    <form action="<?=site_url('system_management/post_sync_latest_game_records'); ?>" method="POST">
        <div class="panel panel-primary panel_main">
            <div class="panel-heading">
                <h4 class="panel-title"><?=lang('Sync Latest Game Records')?>
                    <span class="pull-right">
                    <a data-toggle="collapse" href="#manual_sync_latest_game_records" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
                    </span>
                </h4>
            </div>
            <div id="manual_sync_latest_game_records" class="panel-collapse collapse in ">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-2">
                            <?=lang('Date');?>
                        </div>
                        <div class="col-md-6">
                            <input id="sync_sync_latest_game_records" class="form-control input-sm dateInput" data-start="#sync_latest_game_records_date_from" data-end="#sync_latest_game_records_date_to" data-time="true"/>
                            <input type="hidden" id="sync_latest_game_records_date_from" name="date_from" value="<?=$by_date_from;?>" />
                            <input type="hidden" id="sync_latest_game_records_date_to" name="date_to"  value="<?=$by_date_to;?>"/>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
                </div>
            </div>
        </div>
    </form>
<?php } ?>

<?php if ($this->utils->getConfig('enable_cancel_game_round')) { ?>
    <form action="<?=site_url('system_management/post_cancel_game_round'); ?>" method="POST">
        <div class="panel panel-primary panel_main">
            <div class="panel-heading">
                <h4 class="panel-title"><?=lang('Cancel Game Round')?>
                    <span class="pull-right">
                    <a data-toggle="collapse" href="#manual_cancel_game_round" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
                    </span>
                </h4>
            </div>
            <div id="manual_cancel_game_round" class="panel-collapse collapse in ">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-2">
                            <?=lang('Game API');?>
                        </div>
                        <div class="col-md-6">
                            <select class="form-control input-sm" id="cgr_game_platform_id" name="game_platform_id" required>
                                <option value="">Select Game API</option>
                                <?php foreach ($cancel_round_game_apis as $game_api) { ?>
                                    <option value="<?=$game_api['game_platform_id'];?>"><?php echo "[{$game_api['game_platform_id']}] {$game_api['game_platform_name']}";?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <?=lang('Game Username');?>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control input-sm" id="cgr_game_username" name="game_username" placeholder="<?=lang('Game Username');?>" required />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <?=lang('Round ID');?>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control input-sm" id="cgr_round_id" name="round_id" placeholder="<?=lang('Round ID');?>" required />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <?=lang('Game Code');?>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control input-sm" id="cgr_game_code" name="game_code" placeholder="<?=lang('Game Code');?>" required />
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
                </div>
            </div>
        </div>
    </form>
<?php } ?>

<?php if ($this->utils->getConfig('enable_clear_game_logs_md5_sum')) { ?>
    <form action="<?=site_url('system_management/post_clear_game_logs_md5_sum'); ?>" method="POST">
        <div class="panel panel-primary panel_main">
            <div class="panel-heading">
                <h4 class="panel-title"><?=lang('Clear Game Logs Md5 Sum')?>
                    <span class="pull-right">
                    <a data-toggle="collapse" href="#manual_clear_game_logs_md5_sum" class="btn btn-info btn-xs collapsed" aria-expanded="true"></a>
                    </span>
                </h4>
            </div>
            <div id="manual_clear_game_logs_md5_sum" class="panel-collapse collapse in ">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-2">
                            <?=lang('Game');?>
                        </div>
                        <div class="col-md-6">
                            <select class="form-control input-sm" name="game_platform_id" id="clear_game_logs_md5_sum_game_platform_id" required>
                                <option value=""><?=lang('Select Game API');?></option>
                                <?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
                                    <option value="<?=$key;?>"><?=$value;?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <?=lang('External Unique Ids');?>
                        </div>
                        <div class="col-md-6">
                            <textarea class="form-control input-sm" rows="10" name="external_unique_ids" placeholder='["sample1", "sample2", "sample3"]' required ></textarea>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
                </div>
            </div>
        </div>
    </form>
<?php } ?>

<?php
    // include admin/application/views/includes/vipsetting_sync.php
    include __DIR__ . '/../includes/vipsetting_sync.php';

    // admin/application/views/includes/something_wrong_modal.php
    include __DIR__ . '/../includes/something_wrong_modal.php';
?>

<script type="text/javascript" src="<?=$this->utils->jsUrl('ace/ace.js')?>"></script>

<script type="text/javascript">
	$( document ).ready(function() {
	    $("#dev_functions").addClass('active');

	    hljs.initHighlightingOnLoad();
		var sync_methods_table_dataTable = $('#sync_methods_table').DataTable();

		var doUpdateLockCount = function() {
			$('.lock_count').each(function() {
				var count = parseInt($(this).html());
				if(count !== 0) {
					$(this).html(count - 1);
				}
			});
		};

		$('#by_game_platform_id_on_locktable').on('change', function() {
			id = this.value;
			$.ajax({
	            method: "GET",
	            url: "/system_management/get_transaction_table_by_platform_id/"+id,
	            success: function(data) {
	                console.log(data);
	                if(data.success){
	                	$('#lock_table_name').val(data.table);
	                }
	            }
	        });
		});

		$('#lock_table_sec').bind('click keyup', function(){
		   $('.lock_count').html($(this).val());
		});

		const asyncPostCall = async () => {
			try {
				$('.lock_count').html($('#lock_table_sec').val());
				$("#lock_table_button").button('loading');
				const lock_interval = setInterval(doUpdateLockCount, 1000);
				const response = await fetch('/system_management/test_lock_table',
				{
					method: 'POST',
					headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					table: $('#lock_table_name').val(),
					sleep: $('#lock_table_sec').val()
				})
				});

				if(!response.ok){
					console.log("-->");
					throw new Error('Bad response', {
						cause: {
							response,
						}
					})
				}

				const data = await response.json();
				console.log("data here:", data);
				if(data.success == true){
					$("#lock_table_button").button('reset');
                	$('.lock_count').html(0);
                	clearInterval(lock_interval);
				}
			} catch(error) {
				console.log("Error: ", error);
				console.log("Error Cause: ", error.cause.response);
				$("#lock_table_button").button('reset');
            	$('.lock_count').html(0);
				switch (error.cause.response?.status){
					case 400: alert("Bad request!"); break;
					case 401: alert("Unauthorized!"); break;
					case 404: alert("Not found"); break;
					case 500: alert("Internal Server Error!");break;
					case 502: alert("Bad Gateway!");break;
					case 503: alert("Service Unavailable!");break
					case 504: alert("Gateway Timeout!");break;
					default:
						alert("Encounter error!");
				}


			}
		}


		$("#test_lock_table_form").submit(function(e){
			is_remote = $('#remote_check').prop('checked');
			if(!is_remote){
				e.preventDefault();
				asyncPostCall();
			}
		});

        /// OGP-28577
        var vipsetting_sync =  VIPSETTING_SYNC.init({
            DRY_RUN_MODE_LIST: gDRY_RUN_MODE_LIST
            , CODE_DECREASEVIPGROUPLEVEL: gCODE_DECREASEVIPGROUPLEVEL
        });
        vipsetting_sync.assignLangList2Options(theLangList4vipsetting_sync);
        vipsetting_sync.onReadyInView('<?=pathinfo(basename(__FILE__), PATHINFO_FILENAME); // aka. view_other_functions ?>');

	});

	$('.date_disp.set_today').click( function () {
		$('#date_disp').val(moment().format('YYYY-MM-DD HH:mm:ss'));
	});

	$('.date_disp.set_base').click( function () {
		$('#date_disp').val($('#date_base').val());
	});

	$(".chosen-select").select2({
		disable_search: true,
		width: '100%',
	});

    $('#vipsettingid').multiselect({
        enableFiltering: true,
        enableCaseInsensitiveFiltering: true,
        includeSelectAllOption: true,
        selectAllJustVisible: false,
        buttonWidth: '100%',
        buttonClass: 'btn btn-sm btn-default',
        buttonText: function(options, select) {
            if (options.length === 0) {
                return '<?=lang('Select Player Level');?>';
            } else {
                var labels = [];
                options.each(function() {
                    if ($(this).attr('label') !== undefined) {
                        labels.push($(this).attr('label'));
                    }
                    else {
                        labels.push($(this).html());
                    }
                });
                return labels.join(', ') + '';
            }
        }
    });

	var extraInfoEditor = ace.edit("extra_info");
	extraInfoEditor.setTheme("ace/theme/tomorrow");
	extraInfoEditor.session.setMode("ace/mode/json");

	let freeRoundCreateBtnSubmit =  $('#create_free_round_btn');
	let freeRoundCreationForm =  $('#free_round_creation_form');

	// free_round_parameters
	let errorMsg = '';
	freeRoundCreationForm.submit((event) => {
		event.preventDefault();
	    if (!isJsonString(extraInfoEditor.getValue()) ) {
			showValidationError('Invalid JSON');
		} else {
			let parameters = extraInfoEditor.getValue();
			let game_platform_id = $("#free_round_game_platform_id").val();

			let data = {
				parameters: parameters,
				game_platform_id: game_platform_id
			}
			$('#free_round_parameters').val(parameters);

			freeRoundCreationForm.get(0).submit();			
		}
	});

	function showValidationError(msg) {
		$("#error-msg").stop(true, true).fadeIn().html(msg).fadeOut(10000, function () {
			$(this).html("");
		});
	}

	function isJsonString(str) {
		if (str == '') return false;
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}

	function set_bet_limit_input_type() {
    var selectedValue = document.querySelector('input[name="set_bet_limit_type"]:checked').value;
    console.log('Radio button changed. New value:', selectedValue);

    var maxMinSelection = document.getElementById("max_min_bet_limit_selection");
    var rangeIdSelection = document.getElementById("range_limit_id_selection");

    if (selectedValue == 'min_max') {
        maxMinSelection.style.display = "block";
        rangeIdSelection.style.display = "none";

        // Set 'required' for max and min inputs
        document.getElementById("min_bet_limit").setAttribute("required", "true");
        document.getElementById("max_bet_limit").setAttribute("required", "true");

        // Remove 'required' for range limit id input
        document.getElementById("range_limit_id").removeAttribute("required");
    } else {
        maxMinSelection.style.display = "none";
        rangeIdSelection.style.display = "block";

        // Set 'required' for range limit id input
        document.getElementById("range_limit_id").setAttribute("required", "true");

        // Remove 'required' for max and min inputs
        document.getElementById("min_bet_limit").removeAttribute("required");
        document.getElementById("max_bet_limit").removeAttribute("required");
    }
}

</script>
