<?php
	$cmsVersion = $this->CI->utils->getCmsVersion();

?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1, minimum-scale=1.0, maximum-scale=3.0">
	<link rel="stylesheet" type="text/css" href="<?= $this->utils->getSystemUrl('player','/resources/third_party/bootstrap/3.3.7/bootstrap.min.css') ?>">
	<script type="text/javascript" src="<?= $this->utils->getSystemUrl('player','/resources/third_party/jquery/jquery-3.1.1.min.js') ?>"></script>
	<script type="text/javascript" src="<?= $this->utils->getSystemUrl('player','/resources/third_party/bootstrap/3.3.7/bootstrap.js') ?>"></script>
	<link href="<?=$this->utils->getSystemUrl('m') . '/includes/css/style-mobile.css?v=' . $cmsVersion;?>" rel="stylesheet" />
</head>
<style type="text/css">
	.login__wrapper {
		padding-top: 20%;
		padding-left: 15px;
    	padding-right: 15px;
	}
	.login__wrapper form#oneworks_form_login button.login_btn.hy {
		width: 100%;
	}

	.login__wrapper form#oneworks_form_login img.currency__flag {
		width: 25px;
		height: 25px;
	}
	.login__wrapper form#oneworks_form_login ul.dropdown-menu {
		width: 25%;
		left: 0;
	    right: 0;
	    margin-left: auto;
	    margin-right: auto;
	}
	.login__wrapper form#oneworks_form_login .form-group span.warning__text {
		display: none;
	}
	.login__wrapper form#oneworks_form_login .form-group span.warning__text p {
		color: #F00;
		margin: 0;
		font-size: 14px;
	}
	.loader {
        background: rgba(0, 0, 0, 0.6);
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2005;
    }

    .loader-css {
        border: 16px solid #f3f3f3;
        border-radius: 50%;
        border-top: 16px solid #3498db;
        width: 60px;
        height: 60px;
        -webkit-animation: spin 2s linear infinite;
        /* Safari */
        animation: spin 2s linear infinite;
    }
    .custom-alert {
        margin: 0;
        font-size: 13px;
        position: fixed;
        width: 100%;
        max-width: 170px;
        top: 10px;
        right: 10px;
        padding-right: 20px;
        transition: all .5s;
    }
    .custom-alert button.close {
        position: absolute;
        top: -10px;
        right: -13px;
    }

    /* Safari */
    @-webkit-keyframes spin {
        0% {
            -webkit-transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(360deg);
        }
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .input-group {
	    display: block;
	    overflow: auto;
	    margin-top: 7px;
	}

	#captcha {
	    height: 46px;
	    width: 100%;
	    display: block;
	}

	.input-group-addon {
	    width: auto;
	    padding: 0;
	    position: absolute;
	    top: 0;
	    right: 0;
	    z-index: 3;
	}

	img#image_captcha {
	    height: 44px;
	}
</style>

<body>
	<div class="container-fluid">
		<div class="row">
			<div class="login__wrapper">
				<form  action="" role="form" id="oneworks_form_login">
					<div class="input">
						<div class="form-group">
							<?=$_csrf_hidden_field?>
							<input type="hidden" id="gamePlatform" name="gamePlatform" value="<?=ONEWORKS_API?>">
							<input type="text" name="username" id="username" placeholder="<?=lang('system.word38')?>" class="input_01">
								<span class="warning__text">
									<p><?=lang("Username can't be empty", $lang)?></p>
								</span>
							<!-- </input> -->
							<input type="password" name="password" id="password" placeholder="<?=lang('sys.em3')?>" class="input_01">
							<span class="warning__text">
								<p><?=lang("Password can't be empty", $lang)?></p>
							</span>
							<?php if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled')):?>
								<div class="form-group">
									<div class="input-group">
									<?php if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha'):?>
			                            <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('https://js.hcaptcha.com/1/api.js')?>" async defer></script>
			                            <div class="h-captcha" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccess>
			                            </div>
		                           		<input required name='login_captcha' id='captcha' type="text" class="form-control hide">
			                        <?php else: ?>
			                        	<input required name='login_captcha' id='captcha' type="text" class="form-control" placeholder="<?php echo lang('label.captcha'); ?>" style="height: 46px;">
											<div class="input-group-addon" style="min-width: 80px; height: 38px; padding: 0;">
												<img id='image_captcha' src='<?php echo site_url('/iframe/auth/captcha?' . random_string('alnum')); ?>' width='120' onclick="refreshCaptcha()">
											</div>
			                        <?php endif; ?>
									</div>

								</div>
							  <?php endif; ?>
							<!-- </input> -->
						</div>
						<div class="form-group">
							<button  class="login_btn hy" id="login_c"><?php echo lang('Login Now', $lang);?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</body>
<script type="text/javascript">
	// ex:
    // OnebookAppLib.PostMessage("login", {token: "0000", transfer: "https://your.domain/transfer?username=xyz"});
    var OnebookAppLib = (function (global) {
        var OnebookInterface = global.webkit ? global.webkit.messageHandlers.OnebookInterface : global.OnebookInterface;
        function OnebookPostMessage(act, data) {
            var parameter = JSON.stringify({
                    act: act,
                    data: data
                });
            if (OnebookInterface) {
                OnebookInterface.postMessage(parameter);
            } else {
                throw "OnebookInterface is missing";
            }
        }
        return {
            PostMessage: OnebookPostMessage
        }
    })(this);

    function AppLogin(data){
        OnebookAppLib.PostMessage("login", data);
    }

    function removeLoader(){
    	$( ".loader" ).remove();
    }

    function loadLoader(){
    	$( "body" ).append( '<div class="loader"><div class="loader-css"></div></div>' );
    }

    function loadErrorMEssage(message){
    	$('.custom-alert').remove();
    	$( "body" ).append( '<div class="alert alert-danger alert-dismissible custom-alert" role="alert">'+
					        '<div style="position: relative;">'+message+'</div>'+
					      '</div>'
		);

		setTimeout(function(){
			$('.custom-alert').fadeOut('slow', this.remove);
		}, 5000);

    }

	$(document).on("click",".anchor",function(){
		$(".anchor").removeClass("active");
		$(this).addClass("active");
		$('#market').find('img').attr("src",$(this).find('img').attr('src'));
		$('#market').find('span').first().text($(this).text());
		$("#flag_selected:hidden").show();
		$('#oneworks_form_login').trigger("reset");
	});

	$(document).on("submit","#oneworks_form_login",function(e){
		e.preventDefault();
		var url = "<?= $this->utils->getSystemUrl('player','/iframe/auth/login') ?>";
		var username = $("#username").val();
		var password = $("#password").val();
		var valid = true;
		var array = $('#oneworks_form_login').serializeArray();
		var data = jQuery.parseJSON(JSON.stringify(array, null, 2));
		if(username.length === 0) {
			valid = false;
			$("#username").next('span').show();
		} else {
			$("#username").next('span').hide();
		}
		if(password.length === 0) {
			valid = false;
			$("#password").next('span').show();
		} else {
			$("#password").next('span').hide();
		}

		if(valid){
			loadLoader();
			$.ajax({
				type: "POST",
				url: url,
				dataType: 'json',
				data:data,
				success: function(result){
					// console.log(result);
					if(result.success == true){
						AppLogin({
							token: result.sb_token,
							transfer: result.fund_transfer_link,
						});
					} else {
						var message = "<?php echo lang('Username or password incorrect!', $lang); ?>";
						if(typeof result.msg !== 'undefined'){
							message = result.msg;
							if(message.search("captcha") != -1){
								message = "<?php echo lang('error.captcha', $lang); ?>";
							}
							if(message.search("username") != -1){
								message = "<?php echo lang('session.timeout', $lang); ?>";
								loadErrorMEssage(message);
								setTimeout(function(){ location.reload();}, 1000);
							}

						}
						loadErrorMEssage(message);
						refreshCaptcha();

					}
				},
				error: function(xhr, textStatus, error){
					// console.log(error);
					var message = "<?php echo lang('Please try again', $lang); ?>";
					loadErrorMEssage(message);
					// refreshCaptcha();
					setTimeout(function(){ location.reload();}, 1000);
				},
				complete: function(){
					removeLoader();
				}
			});
		} else {
			refreshCaptcha();
		}
    });

    function refreshCaptcha(){
        //refresh
        $('#image_captcha').attr('src','<?php echo site_url('/iframe/auth/captcha'); ?>?'+Math.random());
    }

    function hCaptchaOnSuccess(){
	    var hcaptchaToken = $('[name=h-captcha-response]').val();
	    if(typeof(hcaptchaToken) !== 'undefined'){
		    $('#captcha').val(hcaptchaToken);
	    }
	}
</script>
</html>