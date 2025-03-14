<form action="<?=site_url('system_management/post_sync_summary_game_total_bet_daily'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync Summary Game Total Bet Daily'); ?>
			</h4>
		</div>

		<div id="cashback_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Date');?>
					</div>
					<div class="col-md-4">
		                <input id="summary_game_total_date_picker" class="form-control input-sm dateInput" data-start="#summary_game_total_date" data-time="false"/>
		                <input type="hidden" id="summary_game_total_date" name="summary_game_total_date" value="<?=$date;?>" />
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>