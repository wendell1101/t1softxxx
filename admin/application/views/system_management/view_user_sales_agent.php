<div class="col-md-6" id="container">
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h4 class="panel-title"><i class="icon-lock"></i> <?= lang('sales_agent.information.contact.info'); ?></h4>
		</div>
		<div class="panel panel-body" id="email_panel_body">
			<form method="post" action="<?= BASEURL . 'user_management/postActionSalesAgent/' . $user['userId']?>" id="my_form" autocomplete="off" role="form" class="form-horizontal">
                <input type="hidden" name="sales_agent_id" value="<?=isset($sales_agent['id']) ? $sales_agent['id'] : '';?>">
				<div class="form-group">
					<label for="username" class="control-label col-md-4"><?= lang('sys.rp02'); ?> </label>
					<div class="col-md-7">
						<input type="text" value="<?= $user['username'] ?>" name="username" class="form-control" readonly>
						<?php echo form_error('username', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
					</div>
				</div>

				<div class="form-group">
					<label for="chat_platform1" class="control-label col-md-4"><?= lang('sales_agent.chat_platform1'); ?> </label>
					<div class="col-md-7">
						<input type="text" name="chat_platform1" id="chat_platform1" class="form-control input-sm" value="<?=isset($sales_agent['chat_platform1']) ? $sales_agent['chat_platform1'] : '';?>">
						<?php echo form_error('chat_platform1', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
					</div>
				</div>
				<div class="form-group">
					<label for="chat_platform2" class="control-label col-md-4"><?= lang('sales_agent.chat_platform2'); ?> </label>
					<div class="col-md-7">
						<input type="text" name="chat_platform2" id="chat_platform2" class="form-control" value="<?=isset($sales_agent['chat_platform2']) ? $sales_agent['chat_platform2'] : '';?>">
						<?php echo form_error('chat_platform2', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
					</div>
				</div>
				<div class="col-md-offset-4" style="padding-left:7px;">
					<br/>
					<input type="button" value="<?= lang('sys.rp10'); ?>" class="btn btn-sm btn-linkwater" onclick="history.back();" />
					<input type="submit" value="<?= lang('submit'); ?>" class="btn btn-sm btn-scooter">
				</div>
			</form>
		</div>
	</div>
</div>
