<?php if (validation_errors()): ?>
	<div class="alert alert-danger"><?=validation_errors()?></div>
<?php endif?>
<div class="col-md-4 col-md-offset-4 password_recovery_email_wrapper">
	<div class="panel panel-default password-recovery password_recovery_email">
		<div class="panel-heading">
			<?=lang('lang.forgotpasswd')?>
			<a href="/iframe/auth/login" class="close" aria-hidden="true">×</a>
		</div>
		<div class="panel-body">
			<form action="/iframe_module/forgot_password" method="post" autocomplete="off">
				<div class="input" style="margin-top: 80px;">
					<input type="text" name="username" class="form-control" value="<?=set_value('username')?>" placeholder="<?=lang('forgot.02')?>" required="required" oninvalid="this.setCustomValidity('<?=lang('default_html5_required_error_message')?>')" oninput="setCustomValidity('')" autofocus="autofocus"/>
					<button type="submit" class="btn btn-primary"><?=lang('forgot.03')?></button>
				</div>

				<div class="form-group">
					<!--<div class="input-group">

						<div class="login_btn hy" id="login">立即登录type<type/div>type
						<span class="input-group-btn">
							<button type="submit" class="btn btn-primary"><?=lang('forgot.03')?></button>
						</span>
					</div>-->
					<div class="help-block"><?=lang('forgot.01')?></div>
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	console.log('sadasd');
    $(document).ready(function() {
		$("body").addClass("pwd_recovery");
	});
</script>