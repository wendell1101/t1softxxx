<?php
    $display_login_promo_html = $this->config->item('display_login_promo_html');
    if($display_login_promo_html):
        require_once dirname(__FILE__) . '/../../includes/login_promo_component.php';
    endif;
?>
<form method="POST" action="<?=site_url('iframe/auth/login')?>" role="form" id="frm_login" data-tpl="stable_center2/mobile/auth/login_recommended">
    <?=$_csrf_hidden_field?>
    <div class="login-banner"><!-- Provide designer use css to add banner --></div>
    <div class="input">
        <input id="dl1" name="username" placeholder="<?php echo lang('login.Username'); ?>" type="text" class="input_01" value="<?= isset($username) ? $username : ''; ?>">
        <ul id="dltext1"><i></i><?php echo lang("Username can't be empty");?></ul>
        <input id="dl2" name="password" placeholder="<?php echo lang('login.Password'); ?>" type="password" class="input_01" value="<?= $password_holder ?>">
        <ul id="dltext2"><i></i><?php echo lang("Password can't be empty");?></ul>

        <?php
            require_once dirname(__FILE__) . '/captcha_component.php';
        ?>
        <?php if ($remember_password_enabled) : ?>
            <div class="remember-password-container">
                <input type="checkbox" name="remember_me" id="remember_me" value="1" <?= $remember_me ? ' checked="1" ' : '' ?> > <?php echo lang('Remember me');?>
            </div>
        <?php endif; ?>
        <?php if ($forget_password_enabled):?>
            <a href="/player_center/forget_password_select" class="forgot-pass" ><?=lang('lang.forgotpasswd')?></a>
        <?php endif; ?>
    </div>
    <?php
        $frm_login_login_now_btn_list = $this->config->item('frm_login_login_now_btn_list') && is_array($this->config->item('frm_login_login_now_btn_list'));
        if($frm_login_login_now_btn_list):
            include_once VIEWPATH . '/stable_center2/includes/login_now_btn_list_component.php';
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
    <div class="lefttext" style="margin-bottom: 10px;">
        <a class="threepage" href="<?=site_url('/iframe/auth/login_by_mobile')?>" style="cursor:pointer;"><?php echo lang('Mobile number login');?></a>
    </div>
    <?php endif; ?>

    <?php $login_page_show_register_link = $this->config->item('login_page_show_register_link');?>
    <?php if( $login_page_show_register_link): ?>
    <div class="lefttext">
        <?=lang('reg.createAcc')?><a class="threepage" href="<?=site_url('player_center/iframe_register')?>#main-content" style="cursor:pointer;"><?=lang('reg.clickHere')?></a><?=lang('Account')?>
    </div>
    <?php endif; // EOF if( $login_page_show_register_link):...?>
</form>
        <?php
        $display_thirdparty_login_area = $this->utils->getConfig('enable_thirdparty_login_component') && is_array($this->utils->getConfig('thirdparty_sso_type'));
        if($display_thirdparty_login_area):
            $_lang_thirdparty_login_component_in_or__wrapper= lang('lang.frm_login_lang_in_or__wrapper');
            require_once dirname(__FILE__) . '/../../includes/thirdparty_login_component.php';
        elseif ($this->utils->getConfig('line_credential')):?>
            <div >
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
<script>
$(document).ready(function () {
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

        var ajax_options = {
            url: $('#frm_login').attr('action'),
            type: 'POST',
            dataType: 'json',
            data: $('#frm_login').serialize(),
            success: function (data) {
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

                    $('.captcha').removeClass('hide');
                    $('#captcha').val('');
                    $('#captcha').prop('disabled', false);
                    $('#image_captcha').attr('src', '<?=site_url('/iframe/auth/captcha/default/120')?>?' + Math.random());
                    // location.reload(true);
                    return;
                }

                var redirect_url = "<?= (empty($redirect_url = $this->utils->getConfig("mobile_lgoin_redirect_url"))) ? $this->utils->getSystemUrl('m', '/') : site_url($redirect_url) ?>";
                //success and redirect
                    window.location = redirect_url;
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
            });
            modal.modal('show');
        }

        $.ajax(ajax_options);
    });
});
</script>