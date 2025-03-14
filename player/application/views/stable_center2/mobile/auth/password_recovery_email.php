<div class="col-md-4 col-md-offset-4 password_recovery_email_wrapper">
    <div class="panel panel-default password-recovery password_recovery_email">
        <div class="panel-heading">
            <h2 class="title"><?=lang('Find password by email')?></h2>
        </div>
        <div class="panel-body">
            <form id="recoveryForm" action="/iframe_module/password_recovery_reset_code" method="post" autocomplete="off">
                <div class="form-group">
                    <input type="hidden" name="title" value="<?=lang('Find password by email')?>" />
                    <input type="text" id="username" name="username" class="form-control" value="<?=set_value('username')?>" placeholder="<?=lang('forgot.02')?>" required="required" autofocus="autofocus" autocomplete="username"/>
                    <input type="text" id="email" name="email" class="form-control" value="<?=set_value('email')?>" placeholder="<?=lang('Email Address')?>" required="required" autocomplete="email"/>
                    <?php 
                    $captcha_namespace = 'password_recovery_sms';
                    $current_c = $this->utils->getCapchaSetting($captcha_namespace);
                    switch($current_c) {
                      case 'hcaptcha':
                        ?>
                        <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('https://js.hcaptcha.com/1/api.js')?>" async defer></script>
                        <div class="h-captcha" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccess></div>
                        <input type='text' name='login_captcha' id='captcha' class='form-control hide'/>
                        <?php
                        break;
                      default:
                      ?>
                        <input type='text' name='login_captcha' id='captcha' class='form-control' placeholder='<?php echo lang('label.captcha'); ?>' autocomplete="one-time-code"/>
                        <div>
                            <a href="javascript:void(0)" id="refreshCaptcha"><i class="glyphicon glyphicon-refresh"></i></a>
                            <img id='image_captcha' src='<?php echo site_url('/iframe/auth/captcha/default/120?' . random_string('alnum')); ?>'>
                        </div>
                    <?php } ?>
                    <button id="nextButton" class="btn btn-primary"><?=lang('forgot.03')?></button>
                    <div id="ajaxError" class="alert-danger" style="margin-top: 30px; padding: 5px 10px; display: none">Error msg</div>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    #image_captcha {
        max-width:120px;
    }
</style>
<script>
function hCaptchaOnSuccess(){
    var hcaptchaToken = $('[name=h-captcha-response]').val();
    if(typeof(hcaptchaToken) !== 'undefined'){
        $('#captcha').val(hcaptchaToken);
    }
}
$(function() {
    $("body").addClass("pwd_recovery");

    var refreshCaptcha = function(){
        $('#image_captcha').attr('src','<?php echo site_url('/iframe/auth/captcha/default/120'); ?>?'+Math.random());
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
    var sendResetCode = function() {
        $("#ajaxError").hide();
        var url = '<?=site_url('/iframe_module/find_password_email')?>/';
        var username = $("#username").val();
        var captcha = $("#captcha").val();
        var email = encodeURIComponent($("#email").val());
        // url += username + '/' + email + '/' + captcha;
        $.ajax({
            type: 'POST',
            url : url,
            data: {
                username: username,
                email: email,
                captcha: captcha,
                captcha_namespace: `<?= $captcha_namespace ?>`
            }
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

    $('#refreshCaptcha, #image_captcha').click(refreshCaptcha);
    $('#nextButton').click(sendResetCode);
});
</script>