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

<form action="<?=site_url('system_management/post_debug_queue/false'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Queue Server'); ?>: <?=$redis_channel_key?>
			</h4>
		</div>

		<div class="panel-body">
			<div>
				<?php if(!empty($queue_server_info)){?>
					<pre>
						<code class="json"><?=json_encode($queue_server_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?></code>
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
</form>
<script type="text/javascript">
	$( document ).ready(function() {
	    $("#dev_functions").addClass('active');
	    hljs.initHighlightingOnLoad();
	});
</script>
