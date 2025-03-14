<style type="text/css">
	.panel-body .row{
		margin: 4px;
	}
</style>

<form action="<?=site_url('system_management/post_clear_memory_cache/false'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Clear Memory Cache'); ?>
			</h4>
		</div>
		<div class="panel-body">
			<div>
				<?=lang('It will clear all memory cache, like settings. so most settings will restore from DB')?>
			</div>
		</div>
		<div class="panel-footer">
			<input type="submit" class="btn btn-chestnutrose" value="<?=lang('Do it !'); ?>">
		</div>
	</div>
</form>

<script type="text/javascript">
	$( document ).ready(function() {
	    $("#dev_functions").addClass('active');
	});
</script>
