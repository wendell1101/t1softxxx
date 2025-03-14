<?php if (validation_errors()): ?>
	<div class="alert alert-danger"><?=validation_errors()?></div>
<?php endif?>
<div class="password_recovery_email_wrapper">
	<div class="panel panel-default password_recovery_email">
		<div class="panel-heading">
			<?=lang('lang.forgotpasswd')?>
			<a href="/iframe/auth/login" class="close" aria-hidden="true">×</a>
		</div>
		<div class="panel-body">
			<form action="/iframe_module/forgot_password/<?=$player['playerId']?>" method="post" autocomplete="off">
				<div class="form-group">
					<label class="control-label"><?=lang($player['secretQuestion']) ?: $player['secretQuestion'] ?>?</label>
					<div class="input-group">
						<input type="text" name="secretAnswer" class="form-control" placeholder="<?=lang('forgot.04')?>" required="required" autofocus="autofocus"/>
						<span class="input-group-btn">
							<button type="submit" class="btn btn-primary"><?=lang('forgot.03')?></button>
						</span>
					</div>
					<div class="help-block"><?=lang('lang.forgotpasswdMsg')?></div>
				</div>
			</form>
		</div>
	</div>
</div>