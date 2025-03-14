<?php if (validation_errors()): ?>
	<div class="alert alert-danger"><?=validation_errors()?></div>
<?php endif?>
<div class="password_recovery_email_wrapper">
	<div class="panel panel-default password_recovery_email">
		<div class="panel-heading">
				<?=lang('lang.forgotpasswd')?>
				<a href="/iframe/auth/login" class="close" aria-hidden="true">×</a>
			<?php if ($this->utils->isEnabledFeature('contact_customer_service_for_forgot_password')) : ?>
				<div class="panel-body">
					<?= lang('notify.104'); ?>
				</div>
			<?php else: ?>
				<div class="panel-body">
					<form action="/iframe_module/forgot_password" method="post" autocomplete="off">
						<div class="form-group">
							<div class="input-group">
								<input type="text" name="username" class="form-control" value="<?=set_value('username')?>" placeholder="<?=lang('forgot.02')?>" required="required" oninvalid="this.setCustomValidity('<?=lang('default_html5_required_error_message')?>')" oninput="setCustomValidity('')" autofocus="autofocus"/>
								<span class="input-group-btn">
									<button type="submit" class="btn btn-primary"><?=lang('forgot.03')?></button>
								</span>
							</div>
							<div class="help-block"><?=lang('forgot.01')?></div>
						</div>
					</form>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>