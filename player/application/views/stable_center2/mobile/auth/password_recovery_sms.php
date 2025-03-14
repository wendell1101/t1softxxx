<style type="text/css">
/*---- DEFAULT CSS FOR PW RECOVERY SMS SECTION ------*/
.fr-sms-wrapper {
  text-align: center;
  margin-top: 10%;
  margin-bottom: 10%;
}
.fr-sms-wrapper .panel {
  box-shadow: 0 0 0 rgba(0,0,0,0);
  border: 0;
}
.fr-sms-wrapper .panel-heading {
  border: 0;
}
.fr-sms-wrapper .panel-heading h2 {
  margin: 0;
  font-size: 18px;
}
.fr-sms-wrapper .panel-body input {
  padding: 19px 7px;
  border-radius: 0;
  margin-bottom: 15px !important;
}
.fr-sms-wrapper .panel-body input#captcha {
  width: 60%;
  float: left;
}
.fr-sms-wrapper .panel-body div.captcha-wrapper {
  display: inline-block;
  padding-top: 3px;
  float: right;
}
.fr-sms-wrapper .panel-body div.captcha-wrapper a#refreshCaptcha {
  padding-right: 4px;
}
.fr-sms-wrapper .panel-body button {
  clear: both;
  display: block;
  width: 100%;
  background: #d5d5d5;
  color: #000;
  border: 1px #a4a4a4 solid;
  border-radius: 0;
}
#error-message {
    padding: 5px 10px;
    display: none;
}
#image_captcha {
  max-width: 120px;
}
</style>

<div class="col-md-4 col-md-offset-4">
    <div class="panel panel-default password-recovery fr-sms-wrapper">
        <div class="panel-heading">
            <h2><?=lang('Find password by SMS')?></h2>
        </div>
        <div class="panel-body">
            <form id="recoveryForm" action="/iframe_module/password_recovery_reset_code" method="post" autocomplete="off">
                <div class="form-group">
                    <input type="hidden" name="title" value="<?=lang('Find password by SMS')?>" />
                    <input type="hidden" name="source" value="sms" />
                    <input type="text" class="form-control" id="username" name="username" value="<?=set_value('username')?>" placeholder="<?=lang('forgot.02')?>" required="required" autofocus="autofocus"/>
                    <input type="text" class="form-control" id="mobile" name="mobile" value="<?=set_value('mobile')?>" placeholder="<?=lang('Mobile number')?>" required="required" />
                    <?php 
                    $captcha_namespace = 'password_recovery_sms';
                    $current_c = $this->utils->getCapchaSetting($captcha_namespace);
                    switch($current_c) {
                      case 'hcaptcha':
                        ?>
                        <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('https://js.hcaptcha.com/1/api.js')?>" async defer></script>
                        <div class="h-captcha" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccess></div>
                        <input type="text" class="form-control hide" id='login_captcha'name='login_captcha'  required="required"/>
                        <?php
                        break;
                      default:
                      ?>
                        <input type="text" class="form-control" id='login_captcha'name='login_captcha' placeholder='<?=lang('label.captcha'); ?>' required="required"/>
                        <div>
                            <a href="javascript:void(0)" id="refreshCaptcha"><i class="glyphicon glyphicon-refresh"></i></a>
                            <img id='image_captcha' src='<?=site_url('/iframe/auth/captcha/default/120?' . random_string('alnum')); ?>'>
                        </div>
                    <?php } ?>
                    <button id="nextButton" class="btn btn-primary"><?=lang('forgot.03')?></button>
                    <div id="error-message" class="alert-danger mt30"></div>
                </div>
            </form>
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

    $('#refreshCaptcha, #image_captcha').click(refreshCaptcha);
    $('#nextButton').click(sendResetCode);
});
</script>