<style type="text/css">
	.panel-body .row{
		margin: 4px;
	}
	.loading-buttons {
		display: inline-flex;
		flex-direction: column;
		margin-top: 1em;
	}
</style>
<form action="<?=site_url('system_management/post_rebuild_games_total/false'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Rebuild Totals') ?>
			</h4>
		</div>

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
</form>
<script type="text/javascript">
	$( document ).ready(function() {
	    $("#dev_functions").addClass('active');
	});
</script>
