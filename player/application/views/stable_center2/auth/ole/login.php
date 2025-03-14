<?php if (isset($preview) && $preview): ?>
    <style type="text/css">
        .mt100 {
            margin-top: 0 !important;
        }
    </style>
<?php endif ?>
<style type="text/css">
    #image_captcha {
        max-width:120px;
    }
</style>
<div class="container">
<?php
    $display_login_promo_html = $this->config->item('display_login_promo_html');
    if($display_login_promo_html):
        require_once dirname(__FILE__) . '/../includes/login_promo_component.php';
    endif;
?>
    <div class="cstm-mod login-mod" role="document">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 class="modal-title f24" id="myModalLabel"><?=lang('Login Your Account')?></h4>
                <?php if(!empty($currency_select_html)){ ?>
                    <?=$currency_select_html?>
                <?php } ?>
            </div>
            <div class="modal-body">
                
                <div class="relative"> 
                    <div class="logo_wrapper"></div>
                </div>

                <form method="POST" action="<?=site_url('iframe/auth/login')?>" role="form" id="frm_login" data-tpl="stable_center2/auth/login">
                    <?=$_csrf_hidden_field?>
                    <input type="hidden" name="referer" id="referer" class="form-control"  value="<?=isset($referer_page) ? $referer_page : null;?>">

                    <?php if ($is_enabled_iovation_in_player_login) { ?>
                        <input type="hidden" name="ioBlackBox" id="ioBlackBox" />
                    <?php } ?>

                    <div class="form-group form-inline relative">
                        <label class="label-star">*</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="<?=lang('system.word38')?>" value="<?= isset($username) ? $username : set_value('username');?>">
                    </div>
                    <div class="fcname-note mb20">
                        <p id="username_empty" class="pl15 mb0 hide">
                            <i class="icon-warning red f16 mr5"></i>
                            <?=lang('account.required')?>
                        </p>
                    </div>
                    <?php if(isset($username_error)): ?>
                        <div class="fcname-note mb20">
                            <p class="pl15 mb0">
                                <i class="icon-warning red f16 mr5"></i>
                                <?=$username_error?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <div class="form-group form-inline relative">
                        <label class="label-star">*</label>
                        <input type="password" name="password" id="password" class="form-control"  style="width:95%" placeholder="<?=lang('sys.em3')?>" value="<?= $password_holder ?>"/>
                        <?php if($this->utils->getConfig('show_password_in_login_page')){ ?>
                            <i id="showVisible" class="fa fa-eye-slash" style="cursor:pointer; float: right; font-size: 1.4em; color: #888; margin-left: 3px; " aria-hidden="true" onclick="showPassWord()"></i>
                        <?php } ?>
                    </div>
                    <div class="fcpass-note mb20">
                        <p id="password_empty" class="pl15 mb0 hide">
                            <i class="icon-warning red f16 mr5"></i>
                            <?=lang('password.required')?>
                        </p>
                    </div>
                    <?php if(isset($password_error)): ?>
                        <div class="fcpass-note mb20">
                            <p class="pl15 mb0">
                                <i class="icon-warning red f16 mr5"></i>
                                <?=$password_error?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <?php if ($login_captcha_enabled):?>
                        <?php if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha'):?>
                            <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('https://js.hcaptcha.com/1/api.js')?>" async defer></script>
                            <div class="h-captcha" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-size = "<?=$this->utils->getConfig('enabled_captcha_of_3rdparty')['size']?>" data-theme = "<?=$this->utils->getConfig('enabled_captcha_of_3rdparty')['theme']?>" data-callback = hCaptchaOnSuccess>
                            </div>
                            <input required name='login_captcha' id='captcha' type="text" class="form-control fcrecaptcha hide">
                        <?php else: ?>
                            <div class="form-group form-inline relative">
                                <label class="label-star">*</label>
                                <input required name='login_captcha' id='captcha' type="text" class="form-control fcrecaptcha" placeholder="<?=lang('label.captcha'); ?>" style="width:95%">
                                <i class="fa fa-refresh" style="cursor:pointer; float: right; font-size: 1.4em; color: #888; margin-left: 3px; " aria-hidden="true" onclick="refreshCaptcha()"></i>
                                <img class="captcha" id='image_captcha' src='<?=site_url('/iframe/auth/captcha/default/120?' . random_string('alnum')); ?>' onclick="refreshCaptcha()">
                            </div>
                        <?php endif; ?>
                        <div class="fcrecaptcha-note hide mb20">
                            <p class="pl15 mb0">
                                <i id="captcha_required" class="icon-warning red f16 mr5"></i>
                                <?=lang('captcha.required')?>
                            </p>
                        </div>
                   <?php endif; ?>

                    <div class="show">
                        <?php if ($remember_password_enabled):?>
                            <div class="remember-password-container">
                                <input type="checkbox" name="remember_me" id="remember_me" value="1" <?= $remember_me ? ' checked="1" ' : '' ?> > <?=lang('Remember me');?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($forget_password_enabled['enabled']) && $forget_password_enabled['enabled']):?>
                            <div class="forgot-password-container">
                                <a href="<?=site_url('iframe_module/forget_password_select');?>" class="block text-right"><?=lang('lang.forgotpasswd');?></a>
                            </div>
                        <?php endif; ?>
                        <div class="clearfix"></div>
                    </div>
                    
                    <div class="security-container">
                        <br>
                        <p><?=lang('please maintain your account to ensure your account safety')?></p>
                    </div>

                    <div class="row">
                        <?php
                            $login_page_show_register_link = $this->config->item('login_page_show_register_link');
                            if($login_page_show_register_link):?>
                            <div class="col-md-6">
                                <a href="<?=site_url('player_center/iframe_register')?>" class="btn btn-secondary" id="free_registration_btn"><?=lang('Free Registration')?></a>
                            </div>
                        <?php endif;?>

                        <div class="col-md-6">
                        <?php
                            $frm_login_login_now_btn_list = $this->config->item('frm_login_login_now_btn_list') && is_array($this->config->item('frm_login_login_now_btn_list'));
                            if($frm_login_login_now_btn_list):
                                include_once VIEWPATH . '/stable_center2/includes/login_now_btn_list_component.php';
                            else: ?>
                                <?php if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha' && $this->utils->getConfig('enabled_captcha_of_3rdparty')['size'] == 'invisible'):?>
                                    <button id="login_now_btn" type="submit" class="btn btn-primary login h-captcha" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccessWhenInvisible data-open-callback = "showLoading" data-close-callback = "showLogin"><?=lang('Login Now');?></button>
                                <?php else:?>
                                    <button id="login_now_btn" type="submit" class="btn btn-primary login"><?=lang('Login Now');?></button>
                                <?php endif;?>
                                <br><br>
                        <?php endif; // EOF if ($frm_login_login_now_btn_list):... ?>
                        </div>
                    </div>

                    <?php if($this->utils->isEnabledFeature('enable_mobile_acct_login')):?>
                        <div class="text-center">
                            <p>
                                <a href="<?=site_url('player_center/view_mobile_login')?>"><?=lang('Mobile number login')?></a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php
                        $hidden_login_page_contact_customer_service_area = $this->utils->isEnabledFeature('hidden_login_page_contact_customer_service_area');
                        if(!$hidden_login_page_contact_customer_service_area):
                    ?>
                    <div class="show">
                        <?php if(!$this->utils->isEnabledFeature('hidden_login_page_contact_customer_service_area')):?>
                            <div class="contact-customer-service-container">
                                <?=lang('login.hint')?>
                                <a id="contact_customer_service" href="javascript:void(0)" onclick="<?=$this->utils->getLiveChatOnClick();?>">
                                    <?=lang('Contact Customer Service')?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="clearfix"></div>
                    </div>
                    <?php endif; ?>
                    <?php

                    $display_thirdparty_login_area = $this->config->item('enable_thirdparty_login_component') && is_array($this->config->item('thirdparty_sso_type'));
                    if($display_thirdparty_login_area):
                        $_lang_thirdparty_login_component_in_or__wrapper= lang('lang.frm_login_lang_in_or__wrapper');
                        require_once dirname(__FILE__) . '/../includes/thirdparty_login_component.php';
                    elseif ($this->utils->getConfig('line_credential')): ?>
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
                </form>
            </div>
        </div>
        <?php if( $this->utils->isEnabledFeature('enable_login_go_back_to_homepage') ):?>
            <div class="goback-home">
                <a href="<?=$this->utils->getSystemUrl('www')?>" class="goback-home-link"><i class="icon-home"></i> <?= lang('Go Back to Homepage') ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!isset($preview) || !$preview): ?>
    <script type="text/javascript" src="/resources/third_party/jquery-validate/1.6.0/jquery.validate.min.js"></script>
    <script type="text/javascript" src="/resources/third_party/jquery-form/3.20/jquery.form.min.js"></script>
    <script type="text/javascript" src="/common/js/player_center/player_login.js?v=<?= PRODUCTION_VERSION ?>"></script>
    <?php if(!empty($append_ole777id_js_content)):?>
        <script type="text/javascript" src="<?=$append_ole777id_js_content?>"></script>
    <?php endif;?>

    <script type="text/javascript">
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

        $(function(){
            PlayerLogin.init();

            <?php if(!empty($append_ole777id_js_content)):?>
                Ole777idPlayerLogin.append_custom_js();
            <?php endif;?>
        });

        function callback_after_login(){
            window.location.href = window.location.href;
        }

        function refreshCaptcha(){
            //refresh
            $('#image_captcha').attr('src','<?php echo site_url('/iframe/auth/captcha/default/120'); ?>?'+Math.random());
        }

        function hCaptchaOnSuccess(){
            var hcaptchaToken = $('#frm_login [name=h-captcha-response]').val();
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
            $('#login_now_btn').empty().text("<?=lang('Loading');?>")
        }

        function showLogin(){
            $('#login_now_btn').empty().text("<?=lang('Login Now');?>");
        }

        function showPassWord(){
            if($("#password").attr('type') == 'password'){
                $("#showVisible").removeClass('fa-eye-slash').addClass('fa-eye');
                $("#showVisible").css('color', '#000');
                $("#password").attr("type", "text");
            } else {
                $("#showVisible").removeClass('fa-eye').addClass('fa-eye-slash');
                $("#showVisible").css('color', '#888');
                $("#password").attr("type", "password");
            }
        }
    </script>
<?php endif ?>