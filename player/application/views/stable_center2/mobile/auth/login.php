<?php
    $display_login_promo_html = $this->config->item('display_login_promo_html');
    if($display_login_promo_html):
        require_once dirname(__FILE__) . '/../../includes/login_promo_component.php';
    endif;
?>
<div class="panel">
    <div class="panel-body">
        <form method="POST" action="<?=site_url('iframe/auth/login')?>" role="form" id="frm_login" data-tpl="stable_center2/mobile/auth/login">
            <?=$_csrf_hidden_field?>
            <div class="login-banner"><!-- Provide designer use css to add banner -->
                <?php if($this->utils->getConfig('show_login_page_title')){ ?>
                    <h1><?=lang('lang.login_title')?></h1>
                <?php } ?>
            </div>
            <?php if($this->utils->getConfig('login_logo_img_path')): ?>
                <?php $login_logo_img_path = $this->CI->utils->getSystemUrl("www", $this->utils->getConfig('login_logo_img_path')) ?>
                <div class="login-logo">
                    <img src="<?=$login_logo_img_path?>">
                </div>
            <?php endif; ?>

            <div class="input">
                <input type="hidden" name="referer" id="referer" class="form-control"  value="<?=$referer_page;?>">

                <?php if ($is_enabled_iovation_in_player_login) { ?>
                    <input type="hidden" name="ioBlackBox" id="ioBlackBox" />
                <?php } ?>

                <input id="dl1" name="username" placeholder="<?php echo lang('login.Username'); ?>" type="text" class="input_01" value="<?= isset($username) ? $username : ''; ?>">
                <i class="fa fa-times input-box-right-btn" onclick="$('#dl1').val('');"></i>
                <ul id="dltext1"><i></i><?php echo lang("Username can't be empty");?></ul>
                <input id="dl2" name="password" placeholder="<?php echo lang('login.Password'); ?>" type="password" class="input_01" value="<?= $password_holder ?>" >
                <!-- <i class="fa fa-eye " onclick="login_password_toggle();"></i> -->
                <?php if($this->utils->getConfig('show_password_in_login_page')){ ?>
                    <i id="showVisible" class="fa fa-eye-slash input-box-right-btn" style="color: #888;" aria-hidden="true" onclick="showPassWord()"></i>
                <?php } ?>
                <ul id="dltext2"><i></i><?php echo lang("Password can't be empty");?></ul>
                <?php if(!empty($currency_select_html)){ ?>
                <?=$currency_select_html?>
                <?php }?>
                <?php
                    require_once dirname(__FILE__) . '/captcha_component.php';
                ?>
            </div>
            <div class="show">
                <?php if ($remember_password_enabled) : ?>
                <div class="remember-password-container">
                    <input type="checkbox" name="remember_me" id="remember_me" value="1" <?= $remember_me ? ' checked="1" ' : '' ?> > <?php echo lang('Remember me');?>
                </div>
                <?php endif; ?>
                <?php if ($forget_password_enabled):?>
                <div class="forgot-password-container">
                    <a href="/player_center/forget_password_select" class="forgot-pass block text-right" ><?=lang('lang.forgotpasswd')?></a>
                </div>
                <?php endif; ?>
                <div class="clearfix"></div>
            </div>


            <?php
                $frm_login_login_now_btn_list = $this->config->item('frm_login_login_now_btn_list') && is_array($this->config->item('frm_login_login_now_btn_list'));
                if($frm_login_login_now_btn_list):
                    // include_once VIEWPATH . '/stable_center2/includes/login_now_btn_list_component.php';
                    require_once dirname(__FILE__) . '/../../includes/login_now_btn_list_component.php';
                else: ?>
                    <?php if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha' && $this->utils->getConfig('enabled_captcha_of_3rdparty')['size'] == 'invisible'):?>
                        <button id="login" type="submit" class="login_btn hy h-captcha" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccessWhenInvisible data-open-callback = "showLoading" data-close-callback = "showLogin"><?=lang('Login Now');?></button>
                    <?php else:?>
                        <div class="login_btn hy" id="login"><?=lang('Login Now')?></div>
                    <?php endif;?>
                <?php
                endif; // EOF if ($frm_login_login_now_btn_list):...
            ?>

            <?php if($this->utils->isEnabledFeature('enable_mobile_acct_login')):?>
            <div class="lefttext">
                <p>
                    <a class="threepage" href="<?=site_url('/iframe/auth/login_by_mobile')?>" style="cursor:pointer;"><?php echo lang('Mobile number login');?></a>
                </p>
            </div>
            <?php endif; ?>

            <div class="show">
                <?php if(!$this->utils->isEnabledFeature('hidden_login_page_contact_customer_service_area')):?>
                <div class="contact-customer-service-container">
                    <?=lang('login.hint')?>
                    <a id="contact_customer_service" href="javascript:void(0)" onclick="<?=$this->utils->getLiveChatOnClick();?>"><?=lang('Contact Customer Service')?></a>
                </div>
                <?php endif;?>
                <div class="register-container">
                    <a class="block text-right" href="<?=site_url('player_center/iframe_register')?>"><?=lang('Free Registration')?></a>
                </div>
                <div class="clearfix"></div>
            </div>
        </form>
        <?php
            $display_thirdparty_login_area = $this->config->item('enable_thirdparty_login_component') && is_array($this->config->item('thirdparty_sso_type'));
            if ($display_thirdparty_login_area) {
                // include_once VIEWPATH . '/stable_center2/includes/thirdparty_login_component.php';
                require_once dirname(__FILE__) . '/../../includes/thirdparty_login_component.php';
            }

        if ($this->utils->getConfig('line_credential')):?>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <span class="or__wrapper">OR</span>
                    <a href="/iframe/auth/line_login" type="button" class="btn btn-primary btn-line-register">
                        <span>
                            <img src="/includes/images/line-logo.png">
                            <?= lang('Click here to login'); ?>
                        </span>
                    </a>
                </div>
            </div>
        <?php endif;?>
    </div>
</div>

<div class="modal fade" id="login-modal" role='dialog'>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-close" data-dismiss="modal"><?php echo lang('Close'); ?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<style type="text/css">
#image_captcha{
    width: 39%;
    position: absolute;
    top: 9px;
    right: 0;
}
</style>
<?php
if(empty($referer_page)){
    $referer_page = $this->utils->getConfig("mobile_lgoin_redirect_url");
    if(empty($referer_page)){
        $referer_page=$this->utils->getSystemUrl('m');
    }
}
?>
<script>

/**
 * Trigger a callback when the selected images are loaded:
 * @param {String} selector
 * @param {Function} callback
 */
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
})


_export_smartbackend.on('init.t1t.smartbackend', function() {
    if( typeof( $('#image_captcha').data('onloaded')) !== 'undefined' ){
        var _now = Math.floor(Date.now() / 1000);// timestamp
        $('#image_captcha').data('ont1tinit', _now);
        if( $('#image_captcha').data('onloaded') <= _now ){
            refreshCaptcha(); // refresh for Captcha be overrided to old code by /async/variables.
        }
    }
});

$(document).ready(function () {
    $("body").addClass("login__page");
    var btn = $(".user_btn,.menu_btn,.menu_btn_two");
    var menu = $(".menu_right,.menu_left");
    var leftmove = "left_move";
    var mmove = "menu_move";
    var leftm = $(".menu_left");
    var rightm = $(".menu_right");
    var tap = "click"; //"touchstart";//
    //换页效果

    var modal = $('#login-modal').modal({
        'show': false
    });

    // initCaptcha();

    $("#login").click(function () {
        var dltext1 = $("#dl1").val();
        var dltext2 = $("#dl2").val();
        var captcha = ($("#captcha").length) ? $("#captcha").val() : '';

        if (dltext1.length == 0) {
            $("#dltext1").show();
            return false;
        } else {
            $("#dltext1").hide();
        }
        if (dltext2.length == 0) {
            $("#dltext2").show();
            return false;
        } else {
            $("#dltext2").hide();
        }

        if($("#captcha").length){
            if (!$('.captcha').hasClass('hide') && captcha.length == 0) {
                $("#dltext3").show();
                return false;
            } else {
                $("#dltext3").hide();
            }
        }

        var params = window.location.search
        console.log(params)
        if(params.indexOf('sso_login') > -1){
            params = params.split('&')
            check = params[0].split('=')
            console.log(check)
            if(check[1] == 'true'){
                $("#frm_login").attr('action', '<?=site_url('/ole_sso')?>')
                $("#referer").val(window.location.href);
            }
        }

        var ajax_options = {
            url: $('#frm_login').attr('action'),
            type: 'POST',
            dataType: 'json',
            data: $('#frm_login').serialize(),
            success: function (data) {
                if(data.status == 'sso'){
                    window.location.href = data.url;
                    return;
                }
                if (data.status == "error") {
                    var message = JSON.parse(data.msg);
                    show_error_in_modal();
                    $('button.btn-close', modal).removeClass('disabled');
                    $('button.btn-close', modal).prop('disabled', false);
                    $('button.btn-close', modal).removeAttr('disabled');

                    if(typeof message == "object"){
                        var ul = $('<ul>');

                        $.each(message, function(key, value){
                            switch(key){
                                case "login":
                                    <?php if($this->utils->isEnabledFeature('responsible_gaming')):?>
                                    ul.append($('<li>').text(value));
                                    <?php else:?>
                                    ul.append($('<li>').text("<?=lang('macaopj.auth.user_not_exists')?>"));
                                    <?php endif;?>
                                break;
                                case "password":
                                    ul.append($('<li>').text("<?=lang('macaopj.auth.password_incorrect')?>"));
                                break;
                                default:
                                    ul.append($('<li>').text(value));
                                break;
                            }
                        });

                        $('.modal-body', modal).html(ul);
                    }else{
                        $('.modal-body', modal).html(data.msg);
                    }

                    // initCaptcha();
                    return;
                }else{
                    //success and redirect
                    window.location.href = data.redirect_url;
                }
            }
        };

        function show_error_in_modal () {
            modal.off('show.bs.modal').on('show.bs.modal', function(){
                $('button.btn-close', modal).addClass('disabled');
                $('button.btn-close', modal).prop('disabled', true);
                $('button.btn-close', modal).attr('disabled', 'disabled');
                $('.modal-body', modal).html('<img class="loading" src="/<?=$this->utils->getPlayerCenterTemplate(FALSE)?>/images/loading.gif">');
            });
            modal.off('hide.bs.modal').on('hide.bs.modal', function(){
                $('.modal-body', modal).html('');
                <?php if ($login_captcha_enabled):?>
                    location.reload(true);
                <?php endif; ?>
            });
            modal.modal('show');
        }

        $.ajax(ajax_options);
    });
});
</script>