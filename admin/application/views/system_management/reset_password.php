<div class="col-md-6" id="container">
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h4 class="panel-title"><i class="icon-lock"></i> <?= lang('system.word8'); ?></h4>
		</div>
		<div class="panel panel-body" id="email_panel_body">

			<form method="post" action="<?= BASEURL . 'user_management/postResetPassword/' . $user['userId'] . '/' . $user['username'] ?>" id="my_form" autocomplete="off" role="form" class="form-horizontal">
				<div class="form-group">
					<label for="username" class="control-label col-md-4"><?= lang('sys.rp02'); ?> </label>
					<div class="col-md-7">
						<input type="text" value="<?= $user['username'] ?>" class="form-control" readonly>
						<?php echo form_error('username', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
					</div>
				</div>
				<div id="passwordField" style="margin-top:27px;">
					<div class="form-group">
						<label for="npassword" class="control-label col-md-4"><?= lang('sys.rp06'); ?> </label>
						<div class="col-md-7">
							<input type="password" name="npassword" id="npassword" class="form-control input-sm">
							<?php echo form_error('npassword', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							<input type="checkbox" id="showVisible" name="showVisible" onclick="showPassword(this.id);"> <i style="font-size:13px;"><?= lang('sys.rp11'); ?></i>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label for="ncpassword" class="control-label col-md-4"><?= lang('sys.rp07'); ?> </label>
					<div class="col-md-7">
						<input type="password" name="ncpassword" id="ncpassword" class="form-control">
						<?php echo form_error('ncpassword', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?> <span class="help-block" id="lcpassword"></span>
					</div>
				</div>
				<div class="form-group">
					<label for="deleteEmail" class="control-label col-md-4"><?= lang('sys.rp08'); ?> </label>
					<div class="col-md-7">
						<label class="checkbox-inline">
							<input type="checkbox" id="deleteEmail" name="deleteEmail" value="deleteEmail" checked>
						</label>
					</div>
				</div>
				<div class="col-md-offset-4" style="padding-left:7px;">
					<br/>
					<input type="button" value="<?= lang('sys.rp10'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-default'?>" onclick="history.back();" />
					<input type="submit" value="<?= lang('sys.vu16'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter ' : 'btn-info'?>">
				</div>
			</form>
		</div>
	</div>
</div>