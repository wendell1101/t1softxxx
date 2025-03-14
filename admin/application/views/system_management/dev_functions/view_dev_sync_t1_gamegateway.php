<style type="text/css">
	.panel-body .row{
		margin: 4px;
	}
</style>

<form action="<?=site_url('system_management/post_sync_t1_gamegateway/false'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync T1 Gamegateway') ?>
			</h4>
		</div>
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
</form>

<script type="text/javascript" src="<?=$this->utils->jsUrl('ace/ace.js')?>"></script>

<script type="text/javascript">
	$( document ).ready(function() {
	    $("#dev_functions").addClass('active');
	});
</script>
