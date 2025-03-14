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
<form action="<?=site_url('system_management/post_sync_games_report_timezones'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync Games Report Timezones')?>
			</h4>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-md-2">
					<?=lang('Date');?>
				</div>
				<div class="col-md-6">
					<input id="sync_game_date" class="form-control input-sm dateInput" data-start="#dateFrom" data-end="#dateTo" data-time="true"/>
					<input type="hidden" id="dateFrom" name="dateFrom" value="<?=$dateFrom;?>" />
					<input type="hidden" id="dateTo" name="dateTo"  value="<?=$dateTo;?>"/>
				</div>
			</div>
			<div class="row">
				<div class="col-md-2">
					<?=lang('Game');?>
				</div>
				<div class="col-md-4">
					<select class="form-control input-sm" name="gameApiId" id="gameApiId">
						<option value="_null"><?=lang('All');?></option>
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
					<input class="form-control input-sm" type="text" name="playerName" value="">
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
