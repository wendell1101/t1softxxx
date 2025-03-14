<div class="row" style="padding-top:10%; padding-bottom:10%;">
	<div class="col-md-4 col-md-offset-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<?=lang('Find password by SMS')?>
				<a href="/iframe/auth/login" class="close" aria-hidden="true">×</a>
			</div>
			<div class="panel-body">
				<form id="recoveryForm" action="/iframe_module/password_recovery_reset_code" method="post" autocomplete="off">
					<div class="form-group">
						<input type="hidden" name="title" value="<?=lang('Find password by SMS')?>" />
						<input type="text" id="username" name="username" class="form-control" value="<?=set_value('username')?>" placeholder="<?=lang('forgot.02')?>" required="required" autofocus="autofocus" style="margin-bottom: 5px"/>
						<input type="text" id="mobile" name="mobile" class="form-control" value="<?=set_value('mobile')?>" placeholder="<?=lang('Mobile number')?>" required="required" style="margin-bottom: 5px"/>
						<?php if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha'):?>
                            <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('https://js.hcaptcha.com/1/api.js')?>" async defer></script>
                            <div class="h-captcha" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccess>
                            </div>
                           	<input type='text' name='login_captcha' id='captcha' class=' form-control hide' required>
                        <?php else: ?>
							<input type='text' name='login_captcha' id='captcha' class='form-control' placeholder='<?php echo lang('label.captcha'); ?>' style="margin-bottom: 5px; width: 30%; margin-right: 20px; float: left;" />
							<div style="display: inline-block; padding-top: 5px;">
								<a href="javascript:void(0)" id="refreshCaptcha"><i class="glyphicon glyphicon-refresh" style="width:30px !important;"></i></a>
								<img id='image_captcha' src='<?php echo site_url('/iframe/auth/captcha?' . random_string('alnum')); ?>' width='110' height='34'>
							</div>
						<?php endif;?>
						<button id="nextButton" class="btn btn-primary" style="float:right"><?=lang('forgot.03')?></button>
						<div id="ajaxError" class="alert-danger" style="margin-top: 30px; padding: 5px 10px; display: none">Error msg</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script>

function hCaptchaOnSuccess(){
    var hcaptchaToken = $('[name=h-captcha-response]').val();
    if(typeof(hcaptchaToken) !== 'undefined'){
        $('#login_captcha').val(hcaptchaToken);
    }
}

$(function() {
	var refreshCaptcha = function(){
		$('#image_captcha').attr('src','<?php echo site_url('/iframe/auth/captcha'); ?>?'+Math.random());
	}

	var hCaptchaOnSuccess = function(){
        var hcaptchaToken = $('[name=h-captcha-response]').val();
        if(typeof(hcaptchaToken) !== 'undefined'){
            $('#captcha').val(hcaptchaToken);
        }
	}

	var sendResetCode = function() {
		$("#ajaxError").hide();
		var url = '<?=site_url('/iframe_module/find_password_sms')?>/';
		var username = $("#username").val();
		var captcha = $("#captcha").val();
		var mobile = $("#mobile").val();
		url += username + '/' + mobile + '/' + captcha;
		$.ajax({
			url : url
		}).done(function(data){
			if(data.message) {
				$("#ajaxError").text(data.message).fadeIn(200);
				$("#captcha").val('');
				$('#refreshCaptcha').click();
			}
			if(data.success) {
				$("#recoveryForm").submit();
			}
		});
		return false;
	};

	$('#refreshCaptcha').click(refreshCaptcha);
	$('#nextButton').click(sendResetCode);
});
</script>