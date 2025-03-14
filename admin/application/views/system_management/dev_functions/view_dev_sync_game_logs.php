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
<form action="<?=site_url('system_management/post_sync_game_logs/false'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync Game Logs')?>
			</h4>
		</div>
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
</form>

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
<script type="text/javascript">
	$( document ).ready(function() {
	    $("#dev_functions").addClass('active');
	});
</script>
