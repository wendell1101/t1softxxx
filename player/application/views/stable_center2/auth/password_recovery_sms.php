<style>
	#login_captcha {
		width: 30%;
		float: left;
	}

	#error-message {
		padding: 5px 10px;
		display: none;
	}
</style>

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
						<input type="hidden" name="source" value="sms" />
						<input type="text" class="form-control mb5" name="username" id="username" value="<?=set_value('username')?>" placeholder="<?=lang('forgot.02')?>" required="required" autofocus="autofocus" />
						<input type="text" class="form-control mb5" name="mobile" id="mobile" value="<?=set_value('mobile')?>" placeholder="<?=lang('Mobile number')?>" required="required" />
						<?php 
						$captcha_namespace = 'password_recovery_sms';
						$current_c = $this->utils->getCapchaSetting($captcha_namespace);

						switch($current_c) {
							case 'hcaptcha':
								?>
								<script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('https://js.hcaptcha.com/1/api.js')?>" async defer></script>
								<div class="h-captcha" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccess></div>
								<input type='text' name='login_captcha' id='login_captcha' class=' form-control hide' required>
								<?php
								break;
							default:
							?>
								<input type='text' class="form-control mb5 mr20" name='login_captcha' id='login_captcha' placeholder='<?=lang('label.captcha'); ?>' required="required" />
								<a href="javascript:void(0)" id="refreshCaptcha" class="inline-block pt5">
									<i class="glyphicon glyphicon-refresh"></i>
									<img id='image_captcha' src='<?=site_url('/iframe/auth/captcha/default/120?' . random_string('alnum')); ?>' width='110' height='34'>
								</a>
						<?php } ?>
						<button id="nextButton" class="btn btn-primary" style="float:right">
							<?=lang('forgot.03')?>
						</button>
						<div id="error-message" class="alert-danger mt30"></div>
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
var refreshCaptcha = function(){
	$('#image_captcha').attr('src','<?=site_url('/iframe/auth/captcha/default/120'); ?>?'+Math.random());
}
var onImgLoad = function(selector, callback){
	$(selector).each(function(){
		if (this.complete || /*for IE 10-*/ $(this).height() > 0) {
			callback.apply(this);
		}
		else {
			$(this).on('load', function(){
				callback.apply(this);
			});
		}
	});
};
onImgLoad('#image_captcha', function(){
	var img$El = $(this);
	var _now = Math.floor(Date.now() / 1000); // timestamp
	img$El.data('onloaded', _now ); // data-onloaded=timestamp
});
_export_smartbackend.on('init.t1t.smartbackend', function() {
	if( typeof( $('#image_captcha').data('onloaded')) !== 'undefined' ){
		var _now = Math.floor(Date.now() / 1000);// timestamp
		$('#image_captcha').data('ont1tinit', _now);
		if( $('#image_captcha').data('onloaded') <= _now ){
			refreshCaptcha(); // refresh for Captcha be overrided to old code by /async/variables.
		}
	}
});
$(function() {

	var sendResetCode = function() {
		$("#error-message").hide();
		var url = '<?=site_url('/iframe_module/find_password_sms')?>/';
		<?php if($source == 'voice'): ?>
			url = '<?=site_url('/iframe_module/find_password_voice')?>/';
		<?php endif; ?>
		var username = $("#username").val();
		var mobile = $("#mobile").val();
		var login_captcha = $("#login_captcha").val();

		if(username == '' || login_captcha == '' || mobile == '') {
			$("#error-message").text('<?=lang('Please make sure required items are not blank')?>');
			$("#error-message").show();
			$('#refreshCaptcha').click();
			return false;
		}

		url += username + '/' + mobile + '/' + login_captcha;
		const formData = new FormData();
		formData.append('captcha_namespace', `<?= $captcha_namespace ?>`);
		fetch(url, {
			method: 'POST',
			body: formData
		}).then(response => response.json()).then((data)=>{
			if(data.success) {
				$("#recoveryForm").submit();
			} else {
				if(data.message){
					$("#error-message").text(data.message);
				}
				$("#error-message").show();
				$("#login_captcha").val('');
				$('#refreshCaptcha').click();
			}
		});
		return false;
	};

	$('#refreshCaptcha').click(refreshCaptcha);
	$('#nextButton').click(sendResetCode);
});
</script>