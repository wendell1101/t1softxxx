<form action="<?=site_url('system_management/post_sync_cashback'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync Cashback'); ?>
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