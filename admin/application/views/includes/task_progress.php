<?php

$test_player_lock_balance_seconds = !empty($this->session->userdata('test_player_lock_balance_seconds')) ? $this->session->userdata('test_player_lock_balance_seconds') : 0;

$test_lock_table_seconds = !empty($this->session->userdata('remote_lock_table_seconds')) ? $this->session->userdata('remote_lock_table_seconds') : 0;

?>

<style type="text/css">

.panel_main{
	margin: auto;
}

.text-green {
    color: green;
}

.text-orange {
    color: orange;
}

.text-red {
    color: red;
}

.text-blue {
    color: blue;
}

@media (min-width: 1200px) {
	.panel_main{
		width: 1100px;
	}
}
</style>

<div class="panel panel-primary panel_main">

	<div class="panel-heading">
		<h4 class="panel-title"><i class="fa fa-cogs"></i> &nbsp;<?php echo lang('Task Progress'); ?> : <?=$func_name?>
            <?php if (!empty($test_player_lock_balance_seconds) && $this->utils->getConfig('show_test_player_lock_balance_seconds') && $func_name == 'remote_player_lock_balance') { ?>
                <span class="text-blue" id="test_player_lock_balance_seconds">
                    <?= $test_player_lock_balance_seconds ?>
                </span>
            <?php } ?>
            <?php if (!empty($test_lock_table_seconds) && $func_name == 'remote_lock_table') { ?>
                <span class="text-blue" id="test_lock_table_seconds">
                    <?= $test_lock_table_seconds ?>
                </span>
            <?php } ?>
		</h4>
	</div>

	<div class="panel-body">
		<div class="row">
			<div class="col-md-12">
				<?php echo lang('Task ID').': '.$result_token; ?>
			</div>
		</div>
		<?php if($is_remote_sync_game_logs):?>
		<div class="row"  style="margin-bottom:10px;height:35px;">
			<div class="col-md-12">
			<button  style="display:none;" id= "stop-process" class="btn btn-xs btn-danger pull-right" queue_token="<?php echo  $result_token; ?>">
				<?php echo lang('STOP'); ?>
			</button>
			</div>
		</div>
	    <?php endif; ?>
		<div class="progress">
		  <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
		    <span class="sr-only"></span>
		  </div>
		</div>

		<div class="row queue_failed" style="display:none;">
			<div class="col-md-12">
				<p class="bg-danger"><?php echo lang('Failed');?></p>
			</div>
		</div>

		<div class="row queue_stopped" style="display:none;">
			<div class="col-md-12">
				<p class="bg-warning"><?php echo lang('Stopped');?></p>
			</div>
		</div>

		<div class="row queue_done" style="display:none;">
			<div class="col-md-12">
				<p class="bg-success"><?php echo lang('Done');?></p>
			</div>
		</div>

		<?php include $result_template; ?>

	</div>

	<div class="panel-footer">
		<input type="button" id='btn_refresh_result' class="btn btn-portage" value="<?php echo lang('Refresh Result'); ?>">
		<?php if($this->users->isT1User($this->authentication->getUsername())){?>
		<input type="button" id='btn_run_task_again' class="btn btn-burntsienna" value="<?php echo lang('Run this task again'); ?>">
		<?php }?>
	</div>

</div>

<script type="text/javascript">

var timeoutRefresh=<?php echo $this->utils->getConfig('timeout_refresh_queue');?>;
var refreshUrl="<?php echo site_url('system_management/check_queue/'.$result_token);?>";
let function_name = "<?= $func_name; ?>";
let test_player_lock_balance_seconds = <?= $test_player_lock_balance_seconds; ?>;
console.log('test_player_lock_balance_seconds', test_player_lock_balance_seconds);

if (test_player_lock_balance_seconds > 0 && function_name == 'remote_player_lock_balance') {
    window.onload = function () {
        let countInterval = setInterval(function () {
            let element = document.getElementById('test_player_lock_balance_seconds');
            element.innerHTML = test_player_lock_balance_seconds;
            test_player_lock_balance_seconds -= 1;

            if (test_player_lock_balance_seconds <= 9) {
                element.classList.remove('text-blue');
                element.classList.add('text-orange');
            }

            if (test_player_lock_balance_seconds <= 4) {
                element.classList.remove('text-orange');
                element.classList.add('text-red');
            }

            if (test_player_lock_balance_seconds < 0) {
                clearInterval(countInterval);
                element.innerHTML = 'Done';
                element.classList.remove('text-red');
                element.classList.add('text-green');
                refreshResult();
                <?php //$this->session->unset_userdata('test_player_lock_balance_seconds'); ?>
            };
        }, 1000);
    };
}

function refreshResult(){

	utils.safelog('refresh result:'+timeoutRefresh);

	$.post(refreshUrl, function(data){
		 utils.safelog(data);

		if(data && data['success']){

			$("#stop-process").show();
			if(data['status']==<?=Queue_result::STATUS_DONE?>){
				$(".progress").hide();
				$(".queue_done").show();
				$("#stop-process").hide();
				//link or message
				showOriginalResult(data);
			}else if(data['status']==<?=Queue_result::STATUS_STOPPED?>){
				$(".progress").hide();
				$("#stop-process").hide();
				$(".queue_stopped").show();
                showOriginalResult(data);
			}else if(data['status']==<?=Queue_result::STATUS_ERROR?>){
				$(".progress").hide();
				$(".queue_failed").show();
				$("#stop-process").hide();
				//failed
				showOriginalResult(data);
			}else{
				if(data['queue_original_result']){
					showOriginalResult(data);
				}
				setTimeout( refreshResult , 1000 * timeoutRefresh);
			}
		}else{
			setTimeout( refreshResult , 1000 * timeoutRefresh);
		}

	}).fail(function(){
		setTimeout( refreshResult , 1000 * timeoutRefresh);
	});

}

// setTimeout( refreshResult , 1000 * timeoutRefresh);

var stopQueueUrl ="<?php echo site_url('export_data/stop_queue/'.$result_token);?>";

$(function(){
	refreshResult();

	$('#btn_refresh_result').click(function(){
		refreshResult();
	});

	$('#btn_run_task_again').click(function(){
		if(confirm("<?=lang('Are you sure run same task again')?>?")){
			window.location.href="<?=site_url('/system_management/run_task_again/'.$result_token)?>";
		}
	});

   <?php if($is_remote_sync_game_logs):?>
	$('#stop-process').click(function(){
		var token = $(this).attr('queue_token');
		if(confirm("<?=lang('Are you sure you want to stop syncing gamelogs?')?> \n" + token)){
			$(this).attr("disabled", "disabled");
			$.post(stopQueueUrl, function(data){
				utils.safelog(data);
				refreshResult();
			});
		}

	});
    <?php endif; ?>
	// hljs.initHighlightingOnLoad();
});

let test_lock_table_seconds = <?= $test_lock_table_seconds; ?>;
console.log('test_lock_table_seconds', test_lock_table_seconds);
if (test_lock_table_seconds > 0 && function_name == 'remote_lock_table') {
    window.onload = function () {
        let countInterval = setInterval(function () {
            let element = document.getElementById('test_lock_table_seconds');
            element.innerHTML = test_lock_table_seconds;
            test_lock_table_seconds -= 1;

            if (test_lock_table_seconds <= 9) {
                element.classList.remove('text-blue');
                element.classList.add('text-orange');
            }

            if (test_lock_table_seconds <= 4) {
                element.classList.remove('text-orange');
                element.classList.add('text-red');
            }

            if (test_lock_table_seconds < 0) {
                clearInterval(countInterval);
                element.innerHTML = 'Done';
                element.classList.remove('text-red');
                element.classList.add('text-green');
                refreshResult();
                <?php //$this->session->unset_userdata('test_lock_table_seconds'); ?>
            };
        }, 1000);
    };
}

</script>
