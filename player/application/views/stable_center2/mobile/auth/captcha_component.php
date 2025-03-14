<?php if ($login_captcha_enabled || $this->operatorglobalsettings->getSettingIntValue('captcha_login')):?>
<div style="position: relative" class="captcha hide">
    <?php if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha'):?>
        <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('https://js.hcaptcha.com/1/api.js')?>" async defer></script>
        <div class="h-captcha form-group form-inline relative text-center" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccess data-size = "<?=$this->utils->getConfig('enabled_captcha_of_3rdparty')['size']?>" data-theme = "<?=$this->utils->getConfig('enabled_captcha_of_3rdparty')['theme']?>" ></div>
        <input required name='login_captcha' id='captcha' disabled="disabled" type="text" class="input_01 hide">
    <?php else: ?>
        <img id='image_captcha' src='' onclick="refreshCaptcha()">
        <input required name='login_captcha' id='captcha' disabled="disabled" type="text" class="input_01"
            placeholder="<?php echo lang('label.captcha'); ?>">
    <?php endif; ?>
</div>
<ul id="dltext3"><i></i><?=lang('captcha.required')?>
</ul>
<?php endif; ?>
<style type="text/css">
#image_captcha{
    width: 39%;
    position: absolute;
    top: 9px;
    right: 0;
    max-width: 120px;
}
</style>
<script>
    $(document).ready(function () {
        initCaptcha();
        $("#login").on('click',function () {

            var captcha = ($("#captcha").length) ? $("#captcha").val() : '';
            if($("#captcha").length){
                if (!$('.captcha').hasClass('hide') && captcha.length == 0) {
                    $("#dltext3").show();
                    return false;
                } else {
                    $("#dltext3").hide();
                }
            }
        });
    });

    function initCaptcha() {
        $('.captcha').removeClass('hide');
        $('#captcha').val('');
        $('#captcha').prop('disabled', false);
        $('#image_captcha').attr('src',
            '<?=site_url('/iframe/auth/captcha/default/120')?>?' +
            Math.random());
    }

    function hCaptchaOnSuccess(){
        var hcaptchaToken = $('[name=h-captcha-response]').val();
        if(typeof(hcaptchaToken) !== 'undefined'){
            $('#captcha').val(hcaptchaToken);
        }
    }

    function hCaptchaOnSuccessWhenInvisible(token){
        var hcaptchaToken = token;
        if(typeof(hcaptchaToken) !== 'undefined'){
            $('#captcha').val(hcaptchaToken);
            $('#frm_login').submit();
        }
    }

    function showLoading(){
        $("#login").empty().text("<?=lang('Loading');?>")
    }

    function showLogin(){
        $("#login").empty().text("<?=lang('Login Now');?>");
    }

    function refreshCaptcha() {
        initCaptcha();
    }
</script>