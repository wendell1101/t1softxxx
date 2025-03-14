<?php if (isset($preview) && $preview): ?>
    <style type="text/css">
        .mt100 {
            margin-top: 0 !important;
        }
    </style>
<?php endif ?>

<style type="text/css">

    /* For player/application/views/resources/common/auth/register_recommended_line.php */
    /**
    * ".main-wrapper" Mapping To ".registration-mod"
    */
    .main-wrapper, .registration-mod {
        width: 100%;
        max-width: 500px;
        /* margin: 80px auto; */ /* Patch for workaround */
    }
    .registration-tabs ul {
        display: flex !important;
    }
    .registration-tabs ul li {
        width: 50%;
        flex-grow: 1;
        margin: 0;
    }
    .registration-tabs ul li a {
        background: #000;
        text-transform: uppercase;
        border: 2px solid;
        border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        border-image-slice: 1;
        border-radius: 0;
        padding: 0;
        height: 50px;
        line-height: 45px;
    }
    @media (max-width: 375px) {
    .registration-tabs ul li a {
        font-size: 12px;
    }
    }
    @media (max-width: 335px) {
    .registration-tabs ul li a {
        font-size: 10px;
    }
    }
    .registration-tabs ul li a:hover {
        background: none;
    }
    .registration-tabs ul li:first-child a {
        border-right: 0;
    }
    .registration-tabs ul li.active a {
        background-color: transparent !important;
        background-image: -moz-linear-gradient( -84deg, rgb(254, 217, 129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        background-image: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        background-image: -ms-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
    }
    a .line-img {
        width: 20px;
        margin-right: 5px;
    }
    a.line-reg {
        color:#FFF;
    }

    .reg-form-tabs form {
        margin-top: 20px;
    }

    /**
    * Add "#smsVerifyForm input" for sms-veri dialog.
    */
    .reg-form-tabs form input
    , .reg-form-tabs form select
    , #smsVerifyForm input {
        border: 2px solid;
        border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        border-image-slice: 1;
        text-indent: 0 !important;
        padding: 0 20px !important;
    }

    /**
    * ".reg-form-tabs form .form-group" mapping to ".reg-form-tabs form .field_required"
    */
    .reg-form-tabs form .form-group, .reg-form-tabs form .field_required {
        position: relative;
    }

    /**
    * ".reg-form-tabs form .form-group .required" mapping to ".reg-form-tabs form .field_required .required_hint .required".
    */
    .reg-form-tabs form .form-group .required, .reg-form-tabs form .field_required .required_hint .required {
        color: #a00000;
        position: absolute;
        top: 12px;
        left: 10px;
        z-index: 1;
        font-size: 20px;
    }
    /* .contact-number */ contact-number
    .reg-form-tabs .contact-number {
        display: flex;
    }

    /**
    * Mapping to ".reg-form-tabs .contact-number .btn-group button.dropdown-toggle".
    */
    .reg-form-tabs .contact-number button.dropdown-toggle, .reg-form-tabs .contact-number .btn-group button.dropdown-toggle {
        background: #000;
        border: 2px solid;
        border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        border-image-slice: 1;
        height: 50px;
        padding: 0 20px;
    }

    /**
    * Mapping to ".reg-form-tabs .contact-number input[name="contactNumber"]".
    */
    .reg-form-tabs .contact-number .num-field {
        flex-grow: 1;
        margin-left: 10px;
        position: relative;
    }

    /* EOF .contact-number */

    .reg-form-tabs .sms-code {
        display: flex;
    }
    .reg-form-tabs .sms-code .sms-veri-field{
        flex-grow: 1;
        position: relative;
    }

    /**
    * Mapping to #send_sms_verification
    */
    .reg-form-tabs .sms-code .sms-send,.reg-form-tabs #send_sms_verification {
        width: 150px;
        border-radius: 0;
        background-color: transparent !important;
        background-image: -moz-linear-gradient( -84deg, rgb(254, 217, 129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        background-image: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        background-image: -ms-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
    }

    /**
    * Mapping to ".btn.btn-primary"
    * Add "#smsVerifyForm .btn.btn-primary" for sms-veri dialog.
    */
    .reg-form-tabs .register-btn,.reg-form-tabs .btn.btn-primary,#smsVerifyForm .btn.btn-primary {
        width: 100%;
        border-radius: 0;
        background-color: transparent !important;
        background-image: -moz-linear-gradient( -84deg, rgb(254, 217, 129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        background-image: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
        background-image: -ms-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
    }

    /**
    * workaround
    */
    #smsVerifyForm .btn.btn-primary {
        color:#FFF;
    }
    .reg-form-tabs form .field_required .required_hint .required {
        top:auto;
    }
    .reg-form-tabs .contact-number input[name="contactNumber"]
    {
        border: 2px solid !important;
        border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;
        border-image-slice: 1 !important;
        background-color: #FFF !important;
        /* text-indent: 0 !important; */
        text-indent: 120px !important;
        padding: 0 20px !important;
    }

    /* EOF For player/application/views/resources/common/auth/register_recommended_line.php */

    .col-register-now-fixed {
        margin-top: 16px;
    }
</style>

<div  class="container mt100">

    <?php
    $registration_mod_prepend_html_list = $this->utils->getConfig('registration_mod_prepend_html_list');
    $tpl_filename = basename(__FILE__, '.php'); // register_recommended4sexycasino_line
    if( ! empty($registration_mod_prepend_html_list[ $tpl_filename ]) ): ?>
        <?=$registration_mod_prepend_html_list[$tpl_filename]?>
    <?php endif; // EOF if( ! empty($registration_mod_prepend_html_list[$tpl_filename) ]) ):.... ?>

    <div class="cstm-mod registration-mod" role="document" style="max-width: 100% !important;">
        <div class="modal-content">

            <div class="modal-header text-center">
                <h4 class="modal-title f24" id="myModalLabel"><?php echo lang('Register Your Account'); ?></h4>
            </div>

            <div class="modal-body">
                <?php if($this->utils->getConfig('line_credential') && $this->utils->getConfig('enable_line_registration_in_desktop')):?>
                    <div class="registration-tabs ">
                        <ul class="nav nav-pills nav-justified">
                            <li class="nav-item active">
                                <a class="nav-link normal-reg"><?=lang('Registration')?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link line-reg">
                                <img class="line-img" src="/images/line.svg" alt="">
                                <?=lang('Line Registration')?></a>
                            </li>
                        </ul>
                    </div>
                <?php endif;?>
                <div class="reg-form-tabs">
                    <form action="<?=site_url('player_center/postRegisterPlayer')?>"
                        data-post_register_player-action="<?=site_url('player_center/postRegisterPlayer')?>"
                        data-post_register_line-action="<?=site_url('iframe/auth/line_register')?>"
                    method="post" id="registration_form">
                        <?php if($this->utils->getConfig('line_credential')):?>
                            <input type="hidden" name="line_reg" value="0"/>
                        <?php endif;?>
                        <?php if($this->utils->getConfig('gotoAddBankAfterRegister')):?>
                            <input type="hidden" value="<?=$this->utils->getPlayerBankAccountUrl('triggerAddBank')?>" name="goto_url" />
                        <?php endif;?>
                        <? $has_input_tracking_code = false; ?>
                        <?php if(!$displayAffilateCode && (!empty($tracking_code) || !empty($tracking_source_code))): ?>
                            <? $has_input_tracking_code = true; ?>
                            <input type="hidden" value="<?=set_value('tracking_code', $tracking_code)?>" name="tracking_code" />
                            <input type="hidden" value="<?=set_value('tracking_source_code', $tracking_source_code)?>" name="tracking_source_code" />
                        <?php endif ?>
                        <?php if(!$displayAgencyCode && (!empty($agent_tracking_code) || !empty($agent_tracking_source_code))): ?>
                            <input type="hidden" value="<?=set_value('agent_tracking_code', $agent_tracking_code)?>" name="agent_tracking_code" />
                            <input type="hidden" value="<?=set_value('agent_tracking_source_code', $agent_tracking_source_code)?>" name="agent_tracking_source_code" />
                        <?php endif ?>
                        <?php if(!$displayReferralCode && !empty($referral_code)): ?>
                            <input type="hidden" value="<?=set_value('invitationCode', $referral_code)?>" name="invitationCode" />
                        <?php endif ?>

                        <?php if($this->utils->isEnabledFeature('enable_income_access') && isset($btag) && !empty($btag)) : ?>
                            <input type="hidden" name="btag" value="<?=set_value('btag', $btag)?>" />
                        <?php endif; ?>

                        <?php if ($this->utils->isEnabledFeature('enable_player_register_form_keep_error_prompt_msg')) : ?>
                            <input type="hidden" name="keepErrorMsg" value="enabled" />
                        <?php endif; ?>

                        <?php if($is_iovation_enabled):?>
                            <input type="hidden" name="ioBlackBox" id="ioBlackBox"/>
                        <?php endif; ?>

                        <div class='row'>
                            <?php
                                $item_sequence = $this->utils->getConfig('player_register_item_sequence_web');
                                foreach ($item_sequence as $item) {
                                    switch ($item) {
                                        case 'USERNAME': ?>
                                            <?php // USERNAME ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="form-group form-inline relative field_required">
                                                    <?= $require_display_symbol?>
                                                    <label><i class="icon-user"></i></label>
                                                    <input type="text" class="form-control registration-field fcname" name="username" id="username" placeholder="<?php echo lang('Username');?> <?=$require_placeholder_text?>"
                                                    onKeyUp="Register.lowerCase(this)"
                                                    onfocus="return Register.validateUsernameRequirements(this.value)"
                                                    value="<?=set_value('username')?>">
                                                </div>
                                                <div class="fcname-note registration-field-note hide mb20">
                                                    <p class="pl15 mb0"><i id="username_len" class="icon-warning red f16 mr5"></i> <?php echo lang('Username').' '. sprintf(lang('validation.lengthRangeStandard'), $min_username_length, $max_username_length);?></p>
                                                    <?php if (!empty($this->utils->isRestrictUsernameEnabled())) : ?>
                                                        <p class="pl15 mb0"><i id="username_charcombi" class="icon-warning red f16 mr5"></i> <?=lang('validation.validateUsername01')?></p>
                                                    <?php endif; ?>
                                                    <p id="username_exist_checking" class="pl15" style="display: none;"><i class="icon-warning red f16 mr5"></i> <?=lang('validation.availabilityUsername_checking')?></p>
                                                    <p id="username_exist_failed" class="pl15"><i class="icon-warning red f16 mr5"></i> <?=lang('validation.availabilityUsername_2')?></p>
                                                    <p id="username_exist_available" class="pl15" style="display: none;"><i class="icon-checked green f16 mr5"></i> <?=lang('validation.availabilityUsername_available')?></p>
                                                </div>
                                            </div>
                                            <?php break;
                                        case 'PASSWORD': ?>
                                            <?php // PASSWORD ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="form-group form-inline relative field_required">
                                                    <?= $require_display_symbol?>
                                                    <label><i class="icon-pass"></i></label>
                                                    <input type="password" class="form-control registration-field fcpass" name="password" id="password" onfocus="return Register.validatePasswordRequirements(this.value)"  oninput="return Register.validatePasswordRequirements(this.value)" placeholder="<?php echo lang('Password'); ?> <?=$require_placeholder_text?>" value="<?=set_value('password')?>">
                                                </div>
                                                <div class="fcpass-note registration-field-note hide mb20">
                                                    <p class="pl15 mb0"><i id="password_len" class="icon-warning red f16 mr5"></i> <?php echo lang('Password') . ' ' . sprintf(lang('validation.lengthRangeStandard'), $min_password_length, $max_password_length);?></p>
                                                    <p class="pl15 mb0"><i id="password_regex" class="icon-warning red f16 mr5"></i> <?=lang('validation.contentPassword01')?></p>
                                                    <p class="pl15"><i id="password_not_username" class="icon-warning red f16 mr5"></i> <?=lang('validation.contentPassword02')?></p>
                                                </div>
                                            </div>

                                            <?php // CONFIRM PASSWORD ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="form-group form-inline relative field_required">
                                                    <?= $require_display_symbol?>
                                                    <label><i class="icon-pass"></i></label>
                                                    <input type="password" class="form-control registration-field fccpass" name="cpassword" onfocus="return Register.validateConfirmPassword(this.value)"  oninput="return Register.validateConfirmPassword(this.value)" placeholder="<?php echo lang('Confirm Password'); ?> <?=$require_placeholder_text?>" value="<?=set_value('cpassword')?>">
                                                </div>
                                                <div class="fccpass-note registration-field-note hide mb20">
                                                    <p class="pl15 mb0"><i id="cpassword_reenter" class="icon-warning red f16 mr5"></i><span id="cpassword_reenter_msg"><?=lang('validation.retypePassword')?></span></p>
                                                </div>
                                            </div>
                                            <?php break;
                                        case 'EMAIL': ?>
                                            <?php
                                            if(in_array('email', $visibled_fields)){
                                                $email_required = $registration_fields['Email']['required'];
                                                // EMAIL ?>
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="form-group form-inline relative <?php if ($email_required == Registration_setting::REQUIRED) echo 'field_required';?> ">
                                                        <?php if ( $email_required == Registration_setting::REQUIRED ) {?>
                                                        <?= $require_display_symbol?>
                                                        <?php }?>
                                                        <label><i class="glyphicon glyphicon-envelope"></i></label>
                                                        <input type="text" class="form-control registration-field fcemail" name="email" id="email" data-validateRequired="<?= ($email_required == Registration_setting::REQUIRED) ? 0 : 1 ?>" placeholder="<?php echo lang('Email Address');?> <?= $this->registration_setting->displayPlaceholderHint($registration_fields['Email']["field_name"])?>" value="<?=set_value('email')?>" onfocus="return Register.validateEmail(this.value)" oninput="return Register.validateEmail(this.value)" <?php if ($email_required == Registration_setting::REQUIRED) echo 'required';?>>
                                                    </div>
                                                    <div class="fcemail-note registration-field-note hide mb20">
                                                        <p class="pl15 mb0"><i id="email_required" class="registration-field-required-icon icon-warning red f16 mr5"></i> <?=lang('validation.requiredEmail')?></p>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <?php break;
                                        case 'REGISTRATION_FIELDS': ?>
                                            <?php // REGISTRATION FIELDS ?>
                                            <?php include_once VIEWPATH . '/resources/common/includes/registration_fields.php'; ?>

                                            <?php if ($this->operatorglobalsettings->getSettingJson('registration_captcha_enabled')):?>
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="form-group form-inline relative">
                                                        <label><i class="glyphicon glyphicon-qrcode"></i></label>
                                                        <input required name='captcha' id='captcha' type="text" class="form-control registration-field fcrecaptcha" placeholder="<?php echo lang('label.captcha'); ?> <?=$require_placeholder_text?>"  style="width: 60%" oninput="return Register.validateVerificodeLength(this.value)">
                                                        <i class="fa fa-refresh" style="cursor:pointer; float: right; font-size: 1.4em; color: #888; margin-left: 3px; " aria-hidden="true" onclick="refreshCaptcha()"></i>
                                                        <img class="captcha" id='image_captcha' src='<?php echo site_url('/iframe/auth/captcha?' . random_string('alnum')); ?>' onclick="refreshCaptcha()" />
                                                    </div>
                                                    <div class="fcrecaptcha-note registration-field-note hide mb20">
                                                        <p class="pl15 mb0">
                                                            <i id="verifi_code_len" class="icon-warning red f16 mr5"></i>
                                                            <?=lang('captcha.required')?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php
                                                    /// The related params,
                                                    // - $has_input_tracking_code
                                                    // - $tracking_code
                                                    $_viewer['register_recommended4sexycasino_line'] = 1;
                                                    include_once VIEWPATH . '/resources/common/includes/show_aff_tracking_code_field.php';
                                                ?>
                                            <?php break;
                                        case 'TERMS_AND_CONDITIONS': ?>
                                            <?php // TERMS AND CONDITIONS ?>
                                            <?php if ($registration_fields['At Least 18 Yrs. Old and Accept Terms and Conditions']['visible'] == Registration_setting::VISIBLE): ?>
                                            <div class="col-md-12 col-lg-12">
                                                <div class="checkbox pl10 pr10 reg-age-terms-field">
                                                    <div class="form-group">
                                                        <input type="checkbox" class="registration-field" name="terms" id="terms" <?=set_value('terms', $this->CI->config->item('terms', 'player_form_registration')) ? 'checked="checked"' : ''?> value="1"
                                                            style="position:relative;margin-left:0"
                                                            onfocus="return Register.validateTerms(this.checked)"
                                                            onclick="return Register.validateTerms(this.checked)" data-validateRequired="undefined"
                                                        />
                                                        <label for="terms" class="lh24" style="position:relative;left:0;top:0;padding-left:0">
                                                            <?=$this->utils->renderLangWithReplaceList('register.18age.hint',[
                                                                '{{age_limit}}' => $age_limit,
                                                                '{{web_user_terms_url}}' => $web_user_terms_url,
                                                                '{{web_privacy_policy_url}}' => $web_privacy_policy_url,
                                                            ]); ?>
                                                        </label>
                                                    </div>

                                                    <!-- <div class="registration-field-note hide mb20 fterms">
                                                        <p class="pl10 mb0">
                                                            <i class="registration-field-required-icon icon-checked green f16 mr5 terms_required"></i>
                                                            <?=sprintf(lang('formvalidation.required'), $this->utils->renderLang('reminder.age.limit', ["$age_limit"]))?>
                                                        </p>
                                                    </div> -->

                                                </div>
                                            </div>
                                            <?php endif ?>
                                            <?php break;
                                        case 'COMMUNICATION_PREFERENCES': ?>
                                            <?php // COMMUNICATION PREFERENCES ?>
                                            <?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences')) && $registration_fields['Player Preference']['visible'] == Registration_setting::VISIBLE): ?>
                                            <div class="col-md-12 col-lg-12">
                                                <div class="pl10 pr10 reg-communication_preference-field">
                                                    <p><?=sprintf(lang('pi.player_pref.hint1'), lang('pi.player_pref_custom_name'))?></p>
                                                    <p><?=lang('pi.player_pref.hint2')?></p>
                                                </div>
                                                <?php
                                                $config_preferences = $this->utils->getConfig('communication_preferences');
                                                ?>
                                                <div class="checkbox pl10 pr10">
                                                    <?php foreach ($config_preferences as $key => $config_preference): ?>
                                                        <?php
                                                            $player_pref_key = 'Player Preference '.lang($config_preference);
                                                            if($registration_fields[$player_pref_key]['visible'] != Registration_setting::VISIBLE){
                                                                continue;
                                                            }
                                                        ?>
                                                        <?php $genPlayerFromKet = 'communication_preferences_' . $key ?>
                                                        <input type="checkbox" name="pref-data-<?=$key?>" id="pref-data-<?=$key?>" value="true" style="position:relative;margin-left:15px" <?=set_value($genPlayerFromKet, $this->CI->config->item($genPlayerFromKet, 'player_form_registration')) ? 'checked="checked"' : ''?>/>
                                                        <label for="pref-data-<?=$key?>" class="lh24" style="position:relative;left:0;top:0;padding-left:0">
                                                            <?=lang($player_pref_key)?>
                                                        </label>
                                                    <?php endforeach ?>
                                                </div>
                                            </div>
                                            <?php else: ?>

                                            <?php // NEWSLETTER SUBSCRIPTION ?>
                                            <?php if($registration_fields['Newsletter Subscription']['visible'] == Registration_setting::VISIBLE){ ?>
                                            <div class="col-md-12 col-lg-12">
                                                <div class="checkbox pl10 pr10 reg-newsletter-field">
                                                    <input type="checkbox" name="newsletter_subscription" id="newsletter_subscription" <?=set_value('terms', $this->CI->config->item('newsletter_subscription', 'player_form_default')) ? 'checked="checked"' : ''?> value="1" <?php if ($registration_fields['Newsletter Subscription']['required'] == Registration_setting::REQUIRED) echo 'required';?>
                                                        style="position:relative;margin-left:0"
                                                    />
                                                    <label for="newsletter_subscription" class="lh24" style="position:relative;left:0;top:0;padding-left:0">
                                                        <?=lang('Newsletter Subscription')?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php }?>

                                            <?php endif ?>
                                            <?php break;
                                        case 'REGISTER_BTN': ?>
                                            <div class="col-md-12 col-lg-12 col-register-now">
                                            <?php // REGISTER BTN ?>
                                            <button type="submit" class="btn btn-primary"><?= lang('Register Now'); ?></button>
                                            </div>
                                            <?php break;
                                        case 'LOGIN_LINK': ?>
                                            <?php // LOGIN LINK ?>
                                            <?php if($this->utils->isEnabledFeature('register_page_show_login_link')) : ?>
                                            <div class="col-md-12 col-lg-12">
                                            <p class="pt10 mb0">
                                                <?= $this->utils->renderLang('register_page_login_link', site_url('iframe/auth/login')) ?>
                                            </p>
                                            </div>
                                            <?php endif; ?>
                                            <?php break;
                                        case 'CUSTOM_BLOCK' : ?>
                                            <div class="col-md-12 col-lg-12">
                                            <?php // CUSTOM BLOCK
                                                echo $this->CI->config->item('register_form_custom_block');
                                            ?>
                                            </div>
                                            <?php break;
                                        default:
                                            # code...
                                            break;
                                    }
                                }
                            ?>
                        </div>
                    </form>
                </div> <!-- EOF .reg-form-tabs -->

            </div>

        </div>
    </div>
</div>

<script>
    var display_registration_announcement = '<?=$this->utils->getConfig('display_registration_announcement')?>';

    $(document).ready(function(){

        // move registration-tabs to bottom.
        // $( ".col-register-now" ).after( '<div class="col-md-12 col-lg-12 col-register-now-fixed"></div>' );
        // $('.registration-tabs').prependTo(".col-register-now-fixed");

        var tplFile = 'views/resources/common/auth/register_recommended4sexycasino_line';

        $('body').on('shown.bs.select refreshed.bs.select', '#dialing_code', function(e){
            $(e.target).data('adjust-by-453', tplFile)
                        .attr('data-adjust-by-453',tplFile);
            $('input[name="contactNumber"]').data('adjust-by-453', tplFile);
            var css = 'width: 120px !important;';
            $('.contact-number .btn-group.bootstrap-select')
                .attr('style',css)
                .css({
                    width:'120px !important' /* Add for heigh priority with m site */
            });
        });
        $('.selectpicker').selectpicker('refresh');

        var css = '';
        css += 'border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;';
        css += 'border-image-slice: 1 !important;';
        // css += 'background-color: #000 !important;';
        css += 'text-indent: 120px !important;';

        if($('input[name="contactNumber"]').length > 0){
            $('input[name="contactNumber"]')
                .attr('data-adjust-by-453',tplFile)
                .data('adjust-by-453', tplFile);

            $('input[name="contactNumber"]').attr('style',css);
        }

        if (display_registration_announcement) {
            // clearCookie('registration_announcement');
            var registration_announcement = getCookie('registration_announcement');
            if (registration_announcement == '' || registration_announcement == null) {
                setCookie('registration_announcement','true',1);
                MessageBox.info(
                    "<?=lang('registration_announcement.message')?>", '<?=lang('lang.info')?>', function(){
                    },
                    [{
                        'text': '<?=lang('lang.close')?>',
                        'attr':{
                            'class':'btn btn-info',
                            'data-dismiss':"modal"
                        }
                    }]
                );
            }
        }
    });

    //设置cookie
    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + "; " + expires;
    }
    //获取cookie
    function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1);
            if (c.indexOf(name) != -1) return c.substring(name.length, c.length);
        }
        return "";
    }
    //清除cookie
    function clearCookie(name) {
        setCookie(name, "", -1);
    }
</script>

