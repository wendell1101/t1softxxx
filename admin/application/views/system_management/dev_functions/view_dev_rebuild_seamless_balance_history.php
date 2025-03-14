<style type="text/css">
	.panel-body .row{
		margin: 4px;
	}
</style>

<form action="<?=site_url('system_management/post_rebuild_seamless_balance_history'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Rebuild Seamless Balance History') ?></h4>
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

<script type="text/javascript">
	$( document ).ready(function() {
	    $("#dev_functions").addClass('active');
	});
</script>