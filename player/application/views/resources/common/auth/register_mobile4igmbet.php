<style>
    .checkbox{
        margin-left: 36px;
    }
    .green {
        color:  green;
    }
    .required_hint{
        position: absolute;
        left: 0;
    }
    .captcha-group {
        height: 50px; /* workaround for refresh captcha  */
    }
    #image_captcha {
        max-width:120px;
    }
</style>
<div class="register-form-container" >

    <div class="reg-left-col" data-ticket="OGP-27600">
        <div class="icon-close" style=""></div>
        <h3 class="bonus-h3"><?=lang('lang.registration_mod_promo_title_html_in_mobi')?></h3>
        <div class="license-wrapper">
            <div class="license-text">
                <p class="text-gambling"><?=lang('lang.registration_mod_promo_gambling_html_in_mobi')?></p>
            </div>
            <div class="license-regulate">
                <p class="text-regulate"><?=lang('lang.registration_mod_regulated_by_in_mobi')?></p>
                <div class="license-logo"></div>
            </div>
        </div>
    </div>

    <div class="cstm-mod registration-mod" role="document"  data-tpl="register_mobile4igmbet">
        <div class="modal-content">

            <div class="modal-header text-center">
                <h4 class="modal-title f24" id="myModalLabel"><?php echo lang('Register Your Account'); ?></h4>
            </div>

            <div class="modal-body">
                <?php if ($this->utils->getConfig('line_credential') && $this->utils->getConfig('enable_line_registration_in_mobile')):?>
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <a href="/iframe/auth/line_login" type="button" class="btn btn-primary btn-line-register">
                                <span>
                                    <img src="/includes/images/line-logo.png">
                                    <?= lang('Click here to register'); ?>
                                </span>
                            </a>
                            <span class="or__wrapper"><?=lang('lang.registration_mod_lang_in_or__wrapper')?></span>
                        </div>
                    </div>
                <?php endif;?>
                <form action="<?=site_url('player_center/postRegisterPlayer')?>" method="post" id="registration_form">
                    <?php if(!$displayAffilateCode && (!empty($tracking_code) || !empty($tracking_source_code))){
                        $has_input_tracking_code = true;
                    }else{
                        $has_input_tracking_code = false;
                    } ?>
                    <?php if(!$displayAffilateCode && (!empty($tracking_code) || !empty($tracking_source_code))): ?>
                        <input type="hidden" value="<?=set_value('tracking_code', $tracking_code)?>" name="tracking_code"/>
                        <input type="hidden" value="<?=set_value('tracking_source_code', $tracking_source_code)?>" name="tracking_source_code"/>
                    <?php endif ?>
                    <?php if(!$displayAgencyCode && (!empty($agent_tracking_code) || !empty($agent_tracking_source_code))): ?>
                        <input type="hidden" value="<?=set_value('agent_tracking_code', $agent_tracking_code)?>" name="agent_tracking_code"/>
                        <input type="hidden" value="<?=set_value('agent_tracking_source_code', $agent_tracking_source_code)?>" name="agent_tracking_source_code"/>
                    <?php endif ?>
                    <?php if(!$displayReferralCode && !empty($referral_code)): ?>
                        <input type="hidden" value="<?=set_value('invitationCode', $referral_code)?>" name="invitationCode"/>
                    <?php endif ?>

                    <?php if($this->utils->isEnabledFeature('enable_income_access') && isset($btag) && !empty($btag)) : ?>
                        <input type="hidden" name="btag" value="<?=set_value('btag', $btag)?>" />
                    <?php endif; ?>
                    <?php if($is_iovation_enabled):?>
                        <input type="hidden" name="ioBlackBox" id="ioBlackBox"/>
                    <?php endif; ?>
                    <?php
                        if($this->utils->getConfig('enable_3rd_party_affiliate')) {
                            include_once VIEWPATH . '/resources/common/includes/cpa_fields.php';
                        }
                    ?>

                    <?php if ($this->utils->isEnabledFeature('enable_player_register_form_keep_error_prompt_msg')) : ?>
                        <input type="hidden" name="keepErrorMsg" value="enabled" />
                    <?php endif; ?>
                    <div class="row">

                        <?php
                            $item_sequence = $this->utils->getConfig('player_register_item_sequence_mobile');
                            foreach ($item_sequence as $item) {
                                switch ($item) {
                                    case 'USERNAME': ?>
                                        <?php // USERNAME?>
                                        <div class="col-md-6 col-lg-12">
                                            <div class="form-group form-inline relative field_required">
                                                <?= $require_display_symbol?>
                                                <label><i class="icon-user"></i></label>
                                                <input type="text" class="form-control registration-field fcname" name="username" id="username" placeholder="<?php echo lang('Username');?> <?=$require_placeholder_text?>"
                                                onKeyUp="Register.lowerCase(this)"
                                                onfocus="return Register.validateUsernameRequirements(this.value)"
                                                value="<?=set_value('username')?>">
                                            </div>
                                            <div class="fcname-note registration-field-note hide mb20">
                                                <p class="pl15 mb0">
                                                    <i id="username_len" class="icon-warning red f16 mr5"></i> <?php echo lang('Username') .' '. sprintf(lang('validation.lengthRangeStandard'), $min_username_length, $max_username_length);?>
                                                </p>
                                                <?php if (!empty($this->utils->isRestrictUsernameEnabled())) : ?>
                                                    <p class="pl15 mb0">
                                                        <i id="username_charcombi" class="icon-warning red f16 mr5"></i> <?=lang('validation.validateUsername01')?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="pl15 mb0" id="username_exist_checking" style="display: none;">
                                                    <i class="icon-warning red f16 mr5"></i> <?=lang('validation.availabilityUsername_checking')?>
                                                </p>
                                                <p class="pl15 mb0" id="username_exist_failed">
                                                    <i class="icon-warning red f16 mr5"></i> <?=lang('validation.availabilityUsername_2')?>
                                                </p>
                                                <p class="pl15 mb0" id="username_exist_available" style="display: none;">
                                                    <i class="icon-checked green f16 mr5"></i> <?=lang('validation.availabilityUsername_available')?>
                                                </p>
                                                <p class="pl15">
                                                    <i id="username_lan" class="icon-checked f16 mr5" style="visibility: hidden;"></i> <?php echo lang('username.lan')?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php break;

                                    case 'PASSWORD': ?>
                                            <?php // PASSWORD?>
                                            <div class="col-md-6 col-lg-12">
                                                <div class="form-group form-inline relative field_required">
                                                    <?= $require_display_symbol?>
                                                    <label><i class="icon-pass"></i></label>
                                                    <input type="password" class="form-control registration-field fcpass" name="password" id="password" onfocus="return Register.validatePasswordRequirements(this.value)"  oninput="return Register.validatePasswordRequirements(this.value)" placeholder="<?php echo lang('Password'); ?> <?=$require_placeholder_text?>" value="<?=set_value('password')?>">
                                                    <?php if(!empty($this->utils->getConfig('registration_mod_has_toggle_password')) ):?>
                                                        <i class="fa fa-refresh icon-toggle_password toggle_password" style="cursor:pointer; float: right; font-size: 1.4em; color: #888; margin-left: 3px; " aria-hidden="true" onclick="Register.toggleViewInPassword(this)"></i>
                                                    <?php endif;?>
                                                </div>
                                                <div class="fcpass-note registration-field-note hide mb20">
                                                    <p class="pl15 mb0"><i id="password_len" class="icon-warning red f16 mr5"></i> <?php echo lang('Password') .' '. sprintf(lang('validation.lengthRangeStandard'), $min_password_length, $max_password_length) . ' ' .lang('validation.lengthRangeContent');?></p>
                                                    <p class="pl15 mb0"><i id="password_regex" class="icon-warning red f16 mr5"></i> <?=lang('validation.contentPassword01')?></p>
                                                    <p class="pl15"><i id="password_not_username" class="icon-warning red f16 mr5"></i> <?=lang('validation.contentPassword02')?></p>
                                                </div>
                                            </div>
                                            <?php // CONFIRM PASSWORD?>
                                            <div class="col-md-6 col-lg-12">
                                                <div class="form-group form-inline relative field_required">
                                                    <?= $require_display_symbol?>
                                                    <label><i class="icon-pass"></i></label>
                                                    <input type="password" class="form-control registration-field fccpass" name="cpassword" onfocus="return Register.validateConfirmPassword(this.value)"  oninput="return Register.validateConfirmPassword(this.value)" placeholder="<?php echo lang('Confirm Password'); ?> <?=$require_placeholder_text?>" value="<?=set_value('cpassword')?>">
                                                    <?php if(!empty($this->utils->getConfig('registration_mod_has_toggle_password')) ):?>
                                                        <i class="fa fa-refresh icon-toggle_password toggle_password" style="cursor:pointer; float: right; font-size: 1.4em; color: #888; margin-left: 3px; " aria-hidden="true" onclick="Register.toggleViewInPassword(this)"></i>
                                                    <?php endif;?>
                                                </div>
                                                <div class="fccpass-note registration-field-note hide mb20">
                                                    <p class="pl15 mb0"><i id="cpassword_reenter" class="icon-warning red f16 mr5"></i> <?=lang('validation.retypePassword')?></p>
                                                </div>
                                            </div>
                                        <?php break;
                                    case 'EMAIL': ?>
                                                <?php
                                                if (in_array('email', $visibled_fields)) {
                                                    $email_required = $registration_fields['Email']['required'];
                                                    // EMAIL?>
                                                    <div class="col-md-6 col-lg-12">
                                                        <div class="form-group form-inline relative <?php if ($registration_fields['Email']['required'] == Registration_setting::REQUIRED) {
                                                        echo 'field_required';
                                                    } ?> ">
                                                            <?php if ($registration_fields['Email']['required'] == Registration_setting::REQUIRED) {?>
                                                                <?= $this->registration_setting->displaySymbolHint($registration_fields['Email']["field_name"])?>
                                                            <?php } ?>
                                                            <label><i class="glyphicon glyphicon-envelope"></i></label>
                                                            <input type="text" class="form-control registration-field fcemail" name="email" id="email" data-validateRequired="<?= ($email_required == Registration_setting::REQUIRED) ? 1 : 0 ?>"
                                                            <?php if ($email_required == Registration_setting::REQUIRED) : ?>
                                                                required
                                                            <?php endif; ?>
                                                            placeholder="<?php echo lang('Email Address'); ?> <?= $this->registration_setting->displayPlaceholderHint($registration_fields['Email']["field_name"])?>" value="<?=set_value('email')?>" onfocus="return Register.validateEmail(this.value)" oninput="return Register.validateEmail(this.value)">
                                                        </div>
                                                        <div class="fcemail-note registration-field-note hide mb20">
                                                            <p class="pl15 mb0"><i id="email_required" class="registration-field-required-icon icon-warning red f16 mr5"></i> <?=lang('validation.requiredEmail')?></p>
                                                        </div>
                                                    </div>
                                                <?php
                                                }?>
                                        <?php break;
                                    case 'REGISTRATION_FIELDS': ?>
                                            <?php // REGISTRATION FIELDS?>
                                            <?php include_once VIEWPATH . '/resources/common/includes/registration_fields.php'; ?>

                                            <?php if ($this->operatorglobalsettings->getSettingJson('registration_captcha_enabled')):?>

                                                <div class="col-md-6 col-lg-12">
                                                        <?php if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha'):?>
                                                            <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('https://js.hcaptcha.com/1/api.js')?>" async defer></script>
                                                            <div class="h-captcha form-group form-inline relative text-center" data-sitekey="<?= $this->utils->getConfig('enabled_captcha_of_3rdparty')['site_key']?>" data-callback = hCaptchaOnSuccess data-size = "<?=$this->utils->getConfig('enabled_captcha_of_3rdparty')['size']?>" data-theme = "<?=$this->utils->getConfig('enabled_captcha_of_3rdparty')['theme']?>" >
                                                            </div>
                                                            <input required name='captcha' id='captcha' type="text" class="form-control registration-field fcrecaptcha hide">
                                                        <?php else: ?>
                                                            <div class="form-group form-inline relative captcha-group">
                                                                <label><i class="glyphicon glyphicon-qrcode"></i></label>
                                                                <input required name='captcha' id='captcha' type="text" class="form-control registration-field fcrecaptcha" placeholder="<?php echo lang('label.captcha'); ?> <?=$require_placeholder_text?>" style="width:60%">
                                                                <i class="fa fa-refresh" style="cursor:pointer; float: right; font-size: 1.4em; color: #888; margin-left: 3px; " aria-hidden="true" onclick="refreshCaptcha()"></i>
                                                                <img class="captcha" id='image_captcha' src='<?php echo site_url('/iframe/auth/captcha/default/120?' . random_string('alnum')); ?>' onclick="refreshCaptcha()">
                                                            </div>
                                                        <?php endif; ?>
                                                    <div class="fcrecaptcha-note registration-field-note hide mb20">
                                                        <p class="pl15 mb0">
                                                            <i id="referral_code" class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                                            <?=lang('captcha.required')?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php
                                                /// The related params,
                                                // - $has_input_tracking_code
                                                // - $tracking_code
                                                $_viewer['register_mobile'] = 1;
                                                include_once VIEWPATH . '/resources/common/includes/show_aff_tracking_code_field.php';
                                            ?>
                                        <?php break;
                                    case 'COMMUNICATION_PREFERENCES': ?>
                                            <?php // COMMUNICATION PREFERENCES?>
                                            <?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences')) && $registration_fields['Player Preference']['visible'] == Registration_setting::VISIBLE): ?>
                                            <div class="col-md-6 col-lg-12">
                                                <div class="checkbox pr10 reg-communication_preference-field">
                                                    <p><?=sprintf(lang('pi.player_pref.hint1'), lang('pi.player_pref_custom_name'))?></p>
                                                    <p><?=lang('pi.player_pref.hint2')?></p>
                                                </div>
                                                <?php
                                                $config_preferences = $this->utils->getConfig('communication_preferences');
                                                ?>
                                                <div class="checkbox pl10 pr10 comm_pref_mob_reg_items">
                                                    <?php foreach ($config_preferences as $key => $config_preference): ?>
                                                        <?php
                                                        $player_pref_key = 'Player Preference '.lang($config_preference);
                                                        if ($registration_fields[$player_pref_key]['visible'] != Registration_setting::VISIBLE) {
                                                            continue;
                                                        }
                                                        ?>
                                                    <?php $genPlayerFromKet = 'communication_preferences_' . $key ?>
                                                    <input type="checkbox" name="pref-data-<?=$key?>" id="pref-data-<?=$key?>" value="true" <?=set_value($genPlayerFromKet, $this->CI->config->item($genPlayerFromKet, 'player_form_registration')) ? 'checked="checked"' : ''?>/>
                                                    <div for="pref-data-<?=$key?>" class="lh24">
                                                        <?=lang($player_pref_key)?>
                                                    </div>
                                                    <?php endforeach ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        <?php break;
                                    case 'TERMS_AND_CONDITIONS': ?>
                                        <?php // TERMS AND CONDITIONS?>
                                        <?php if ($registration_fields['At Least 18 Yrs. Old and Accept Terms and Conditions']['visible'] == Registration_setting::VISIBLE): ?>
                                        <div class="col-md-6 col-lg-12">
                                            <div class="checkbox pl10 pr10 reg-age-terms-field">
                                                <p class="text-danger a_reg-31-hint"><?=lang('a_reg.31.hint')?></p>
                                                <div class="form-group">
                                                    <?php if ($this->utils->getConfig('kg_privacy_policy')): ?>
                                                        <div>
                                                            <div for="terms" class="lh24 age_limit_label">
                                                            <?php echo $this->utils->renderLang('register.kg.age.hint', ["$age_limit","$web_user_terms_url"]);?>
                                                            </div>
                                                            <input type="checkbox" class="registration-field" name="terms" id="terms" value="1" style="position:relative;margin-left:0" onfocus="return Register.validateTerms(this.checked)" onclick="return Register.validateTerms(this.checked)" data-validateRequired="undefined" />
                                                        </div>
                                                        <div>
                                                            <div for="policy_policy_check_terms" class="lh24 privacy_policy_limit_label">
                                                            <?php echo $this->utils->renderLang('register.kg.privacy.policy.hint', ["$web_privacy_policy_url"]);?>
                                                            </div>
                                                            <input type="checkbox" class="registration-field policy-check" name="policy_policy_check_terms" id="policy_policy_check_terms" value="1"  onfocus="return Register.validateTerms(this.checked)" onclick="return Register.validateTerms(this.checked)" data-validateRequired="undefined" />
                                                        </div>
                                                        <div>
                                                            <div for="civs_check_terms" class="lh24 civs_limit_label">
                                                            <?=lang('register.kg.CIVS.hint')?>
                                                            </div>
                                                            <input type="checkbox" class="registration-field policy-check" name="civs_check_terms" id="civs_check_terms" value="1" onfocus="return Register.validateTerms(this.checked)" onclick="return Register.validateTerms(this.checked)" data-validateRequired="undefined" />
                                                        </div>
                                                    <?php else: ?>
                                                        <input type="checkbox" class="registration-field" name="terms" id="terms" <?=set_value('terms', $this->CI->config->item('terms', 'player_form_registration')) ? 'checked="checked"' : ''?>
                                                        onfocus="return Register.validateTerms(this.checked)" onclick="return Register.validateTerms(this.checked)" data-validateRequired="undefined" >
                                                        <div for="terms" class="lh24">
                                                        <?=$this->utils->renderLangWithReplaceList('register.18age.hint',[
                                                            '{{age_limit}}' => $age_limit,
                                                            '{{web_user_terms_url}}' => $web_user_terms_url,
                                                            '{{web_privacy_policy_url}}' => $web_privacy_policy_url,
                                                        ]); ?>
                                                        </div>
                                                    <?php endif;?>
                                                </div>

                                                <div class="registration-field-note hide mb20 fterms">
                                                    <p class="mb0">
                                                        <i class="registration-field-required-icon icon-warning red f16 mr5 terms_required"></i>
                                                        <?=sprintf(lang('formvalidation.required'), $this->utils->renderLang('reminder.age.limit', ["$age_limit"]))?>
                                                    </p>
                                                </div>

                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php break;
                                    case 'REGISTER_BTN': ?>
                                        <div class="col-md-6 col-lg-12">
                                            <div class="error-message <?=(! empty($this->session->userdata('result'))) ? '' : 'hide'?>">
                                                <?=$this->session->userdata('message')?>
                                            </div> <!-- // EOF .error-message -->

                                            <?php
                                            $registration_mod_register_btn_list = $this->config->item('registration_mod_register_btn_list') && is_array($this->config->item('registration_mod_register_btn_list'));
                                            if($registration_mod_register_btn_list):
                                                include_once VIEWPATH . '/stable_center2/includes/register_btn_list_component.php';
                                            endif; // EOF if ($registration_mod_register_btn_list):...
                                            if( ! $registration_mod_register_btn_list ):  ?>
                                                <?php // REGISTER BTN ?>
                                                <div>
                                                    <button type="submit" class="btn btn-primary" style="margin-left: 16px;"><?= lang('Register Now'); ?></button>
                                                </div>
                                            <?php endif; // EOF if ( ! $registration_mod_register_btn_list):... ?>

                                        </div> <!-- // EOF .col-md-6.col-lg-12 -->

                                        <?php break;
                                    case 'HAVE_ACCOUNT': ?>
                                        <?php // HAVE ACCOUNT?>
                                        <div class="col-md-6 col-lg-12">
                                            <p class="pl10 pr10 pt20" style="margin-top: 15px">
                                                <?=sprintf(lang('Already have account and Please Login'), site_url('iframe/auth/login'))?>
                                            </p>
                                        </div>
                                        <?php break;
                                    case 'LIVE_CHAT': ?>
                                        <?php // LIVE CHAT?>
                                        <div class="col-md-6 col-lg-12">
                                            <div class="form-group form-link live-chat-box">
                                                <a id="contact_customer_service" href="javascript:void(0)" onclick="<?=$this->utils->getLiveChatOnClick();?>"><i class="icon-bubble"></i> <?=lang('Live Chat')?></a>
                                            </div>
                                        </div>
                                        <?php break;
                                    case 'CUSTOM_BLOCK' : ?>
                                        <div class="col-md-12 col-lg-12">
                                        <?php // CUSTOM BLOCK
                                            echo $this->CI->config->item('register_form_custom_block');
                                        ?>
                                        </div>
                                        <?php break;
                                    case 'THIRDPARTY_LOGIN':
                                        $display_thirdparty_login_area = $this->config->item('enable_thirdparty_login_component') && is_array($this->config->item('thirdparty_sso_type'));
                                        if($display_thirdparty_login_area){
                                            $_lang_thirdparty_login_component_in_or__wrapper = lang('lang.registration_mod_lang_in_or__wrapper');
                                            ?><div class="col-md-6 col-lg-12"><?php
                                            include_once VIEWPATH . '/stable_center2/includes/thirdparty_login_component.php';
                                            ?></div><?php
                                        }
                                        break;
                                    default:
                                        break;
                                }
                            } // EOF foreach
                        ?>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<?php if (!empty($this->config->item('enable_social_media'))){
    $social_media = $this->config->item('enable_social_media');
    include_once VIEWPATH . '/stable_center2/includes/social_media.php';
}?>
<!-- The Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="display: none;"></div>
            <div class="modal-body"></div>
            <div class="modal-footer">
            <button type="button" id="close_btn" class="btn btn-primary btn-close no" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
            </div>
        </div>
    </div>
</div>

<?php if ( ! isset($preview) || ! $preview): ?>
<script>
$(document).ready(function() {
    $("body").addClass("reg__page");
});

var modal = $('#summaryModal').modal({
    "backdrop": "static",
    "keyboard": false,
    "show": false
});

modal.off('show.bs.modal').on('show.bs.modal', function(){
    $('button.btn-close', modal).prop('disabled', true).attr('disabled', 'disabled').addClass('disabled');
});

modal.off('hide.bs.modal').on('hide.bs.modal', function(){
    $('.modal-body', modal).html('');
});

</script>
<?php endif ?>

<script>
$(document).ready(function() {
    // remove the icon of prefix in each inputs.
    $('div.form-inline:has(input.registration-field) label>i').addClass('hide');

    $('body').on('click', '.reg-left-col .icon-close',function(){
        $('[data-btn_case="HOME_BTN"]').trigger('click');
    });

}); // EOF $(document).ready(function() {...
</script>

<style>
.reg-left-col .icon-close {
    position: relative;
    float: right;
    right: 16px;
    /* top: 16px; */
    height: 24px;
    width: 24px;
    background-image: url(/includes/images/icon-close.png);
}

div.form-inline:has(input.registration-field) input {
    border-radius: 10px !important;
}

/** register btn list */
button[data-btn_case] {
    border-radius: 50px;
    height: 44px !important;
    line-height: 22px;
}
.btn-link[data-btn_case] {
    background: transparent;

    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 700;
    font-size: 14px;
    line-height: 17px;
    text-decoration-line: underline;
    color: #99A4B0;
}
.btn-outline[data-btn_case="SIGN_IN_BTN"] {
    background: transparent;
    border-style: solid !important; /** its need override the defined of style-mobile.css */
    border-color: #99A4B0 !important; /** its need override the defined of style-mobile.css */
    border-width: 1px !important; /** its need override the defined of style-mobile.css */

    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 700;
    font-size: 14px;
    line-height: 17px;
    letter-spacing: -0.02em;
    color: #99A4B0 !important; /** its need override the defined of style-mobile.css */
}

div.col-md-4:has(button[data-btn_case]){
    padding-right: 5px;
    padding-left: 5px;

    width: 33.33333333%; /** its need add */
    float: left; /** its need add */
}



/** SSO */
.sso-btn-group .sso-btn {
    width: 28px;
    height: 28px;
    border-radius: 15px;
}
.sso-btn.sso-btn-facebook::before {
    background-image: url(/includes/images/facebook-sso-logo.blue.svg);
    background-size: 30px auto !important;
}
.sso-btn.sso-btn-google::before {
    background-image: url(/includes/images/google-sso-logo.red.svg);
    background-size: 30px auto !important;
}


/** .registration-mod input:password toggle */
.icon-toggle_password {
    background-image: url(/includes/images/toggle.mask.password.svg);
    background-repeat: no-repeat;
    background-size: 18px 12px;
    background-position: center;
}
.registration-mod .field_required .required_hint {
    display:none;
    pointer-events: none;
}



/** .registration-mod inputs */
.registration-mod .field_required .required_hint {
    display:none;
    pointer-events: none;
}
.registration-mod.cstm-mod input[type="text"]
, .registration-mod.cstm-mod input[type="password"]
, .registration-mod.cstm-mod select
, .registration-mod.cstm-mod input[type="date"] {
    border: 1px solid #44474B;
    border-radius: 10px;
    color:#FFF;
}

.registration-mod.cstm-mod .form-control:focus {
    box-shadow: none;
}

.registration-field-note {
    background: #2A2D31 !important;
    border-radius: 2px;

    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 500;
    font-size: 12px; /** this will be overrided by .f16 style */
    line-height: 15px;
    /* identical to box height */


    color: #008C2E;
}
.registration-field-note p:has(.icon-checked.green) {
    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 500;
    font-size: 12px;
    /* line-height: 15px; */
    color: #008C2E;
}
.registration-field-note .icon-warning.green {
    color: #008C2E;
}
.registration-field-note p:has(.icon-warning.red) {
    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 500;
    font-size: 12px;
    /* line-height: 15px; */
    color: #D51C24;
}
.registration-field-note .icon-warning.red {
    color: #D51C24;
}
.registration-field-note p, p strong, span {
    /** ignore for defined in the file, "/includes/css/style-mobile.css". */
    color: inherit;
    font-weight: inherit;
    font-size: inherit;
}
</style>