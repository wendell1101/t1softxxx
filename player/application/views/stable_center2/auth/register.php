<?php
$registration_template_for_mobile_reference = $this->utils->getConfig('registration_template_for_mobile_reference');

$enable_OGP19860 = false;
if( ! empty($this->utils->getConfig('enable_OGP19860') ) ){
    $enable_OGP19860 = true;
}

$use_line_register_method_default = false;
if( ! empty($this->utils->getConfig('use_line_register_method_default') ) ){
    $use_line_register_method_default = true;
}

if (!$this->utils->is_mobile()) {
    $web_user_terms_url = $this->utils->getConfig("web_user_terms_url");
    if (!empty($web_user_terms_url)) {
        $web_user_terms_url = $this->utils->getSystemUrl("www", $web_user_terms_url);
    } else {
        $web_user_terms_url = "#";
    }

    $web_privacy_policy_url =  $this->utils->getConfig("web_privacy_policy_url");
    if (!empty($web_privacy_policy_url)) {
        $web_privacy_policy_url = $this->utils->getSystemUrl("www", $web_privacy_policy_url);
    } else {
        $web_privacy_policy_url = "#";
    }
} else {
    $mobile_user_terms_url = $this->utils->getConfig("mobile_user_terms_url")?:$this->utils->getConfig("web_user_terms_url");
    if (!empty($mobile_user_terms_url)) {
        $web_user_terms_url = $this->utils->getSystemUrl("m", $mobile_user_terms_url);
    } else {
        $web_user_terms_url = "#";
    }

    $mobile_privacy_policy_url =  $this->utils->getConfig("mobile_privacy_policy_url")?:$this->utils->getConfig("web_privacy_policy_url");
    if (!empty($mobile_privacy_policy_url)) {
        $web_privacy_policy_url = $this->utils->getSystemUrl("m", $mobile_privacy_policy_url);
    } else {
        $web_privacy_policy_url = "#";
    }
}

$rule = $this->utils->getConfig('player_validator');
$contactRule = isset($rule['contact_number']) ? $rule['contact_number']  : "" ;
$firstNameRule = isset($rule['first_name']) ? $rule['first_name']  : [] ;

$visibled_fields=[];
foreach ($registration_fields as $registration_field){
    if ($registration_field['visible'] == Registration_setting::VISIBLE){
        $visibled_fields[]=$registration_field['alias'];
    }
}

$require_placeholder_text = '';
if($this->system_feature->isEnabledFeature('enabled_display_placeholder_hint_require')){
    $require_placeholder_text = '(' . lang('Required') . ')';
}
$require_display_symbol ='<span class="required_hint"><i class="text-danger register required">*</i></span>';
?>

<?php include_once $embedded_register_template_form; ?>

<?= $this->CI->load->widget('sms'); ?>

<?php if ( ! isset($preview) || ! $preview): ?>
<script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('jquery-validate/1.6.0/jquery.validate.min.js')?>"></script>
<script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('jquery-form/3.20/jquery.form.min.js')?>"></script>
<script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/register-recommended.js')?>"></script>
<?php if( $enable_OGP19860 ):?>
    <script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/register-recommended.line.js')?>"></script>
<?php endif; // EOF if( $enable_OGP19860 )?>
<script type="text/javascript">
    var player_register_template = "<?=$this->utils->getPlayerCenterRegistration()?>";
    var birthInit =  function (options) {
        var defaults = {
                yearSelector:  "#year",
                monthSelector: "#month",
                daySelector:   "#day"
            },
            opts = $.extend({}, defaults, options),
            $yearSelector  = $(opts.yearSelector),
            $monthSelector = $(opts.monthSelector),
            $daySelector   = $(opts.daySelector),
            $dayDefaultOption = $daySelector.find("option:first").clone();
        var cdulDay;

        //Dropdown
        function DropDown(el) {
            this.cdul = el;
            this.placeholder = this.cdul.children('span');
            this.opts = this.cdul.find('ul.dropdown > li');
            this.val = '';
            this.index = -1;
            this.hiddenFields = this.cdul.find('input');
            this.initEvents();
        }

        DropDown.prototype = {
            initEvents : function() {
                var obj = this;
                obj.cdul.off('click');
                obj.cdul.on('click', function(event){
                    $(this).toggleClass('active');
                    return false;
                });
                obj.opts.off('click');
                obj.opts.on('click',function(){
                    var opt = $(this);
                    if(opt.attr('disabled')){
                        return;
                    }
                    obj.val = opt.text();
                    obj.index = opt.index();
                    obj.placeholder.text(obj.val);
                    //alert(obj.val);
                    obj.hiddenFields.val(obj.val).change();
                });
            },
            getValue : function() {
                return this.val;
            },
            getIndex : function() {
                return this.index;
            },
            // OGP-11293
            setIndex : function(v) {
                var opt = $(this.opts).eq(v);
                if(opt.attr('disabled')) { return; }
                this.val = opt.text();
                this.index = opt.index();
                this.placeholder.text(this.val);
                this.hiddenFields.val(this.val).change();
            }
        };

        function changeDay() {
            var _year = $yearSelector.val(),
                _month = $monthSelector.val(),
                _daysOfMonth = new Date(_year, _month, 0).getDate();

            var day_sel_val = $daySelector.val();
            var day_sel_val_legal = parseInt(day_sel_val) > _daysOfMonth ? _daysOfMonth : day_sel_val;

            if (_year == "" || _month == "") {
                return;
            }

            // $daySelector.html($dayDefaultOption);
            // $daySelector.html('');
            $daySelector.append($("<option>").val('').text('<?=lang('reg.13')?>'));

            for (var i = 1; i <= _daysOfMonth; i++) {
                $daySelector.append($("<option>").val(i).text(padLeft(i.toString(), 2)));
            }

            $daySelector.val(day_sel_val_legal);

            // Trigger day select change event
            $daySelector.change();
        }

        function changeDay2() {
            var _year = $yearSelector.val(),
                _month = $monthSelector.val();
            var _day = parseInt(this.cdulDay ? this.cdulDay.getValue() : 0);

            var dayDefaultOption = $daySelector.parent().find('ul');
            dayDefaultOption.empty();
            if (_year == "" || _month == "") {
                return;
            }
            var days_in_month = new Date(_year, _month, 0).getDate();

            for (var i = 1; i <= days_in_month; i++) {
                var _dd = padLeft(i.toString(), 2);
                dayDefaultOption.append($("<li />").append( $('<a />',{'html':_dd,'href':'javascript:void(0)'})));
            }

            delete this.cdulDay;
            this.cdulDay = new DropDown( $('#dropdown_day') );

            // OGP-11293: Set max possible value, if prior selected value is out of current range
            if (isNaN(_day)) {
                this.cdulDay.setIndex(0);
            }
            else if (_day > days_in_month) {
                // var _day_legal = _day > days_in_month ? days_in_month : _day;
                this.cdulDay.setIndex(days_in_month - 1);
            }
            else {
                this.cdulDay.setIndex(_day > 0 ? _day - 1 : 0);
            }

        }

        function padLeft(str, length) {
            if (str.length >= length)
                return str;
            else
                return padLeft("0" + str, length);
        }

        $monthSelector.change(function() {
            if(player_register_template === "template_4"){
                changeDay2();
            }else{
                changeDay();
            }
        });
        $yearSelector.change(function() {
            if(player_register_template === "template_4"){
                changeDay2();
            }else{
                changeDay();
            }
        });

        if(player_register_template === "template_4"){
            $(function(){
                //var cdul = new DropDown( $('.cutom-dropdown-ul') );
                var cdulYear = new DropDown( $('#dropdown_year') );
                var cdulMonth = new DropDown( $('#dropdown_month') );
                var cdulDay = "";
                var cdulSquestion = new DropDown( $('#dropdown_secret_question') );
                var cdulCountry = new DropDown( $('#dropdown_country') );
                //var cdulmonth = new DropDown( $('.cutom-dropdown-ul-month') );
                $(document).click(function() {
                    // all dropdowns
                    //$('.cutom-dropdown-ul, .cutom-dropdown-ul-year, .cutom-dropdown-ul-month').removeClass('active');
                    $('.cutom-dropdown-ul').removeClass('active');

                });
            });
        }
    };

    birthInit();
</script>

<script type="text/javascript">
    $(function(){

        <?php $countryNumList = unserialize(COUNTRY_NUMBER_LIST_FULL); ?>

        Register.usernameMinLength = <?=$min_username_length?>;
        Register.usernameMaxLength = <?=$max_username_length?>;
        Register.passwordMinLength = <?=$min_password_length?>;
        Register.passwordMaxLength = <?=$max_password_length?>;
        Register.restrictUsername = <?=!empty($this->utils->isRestrictUsernameEnabled()) ? 1 : 0 ?>;
        Register.restrictUsernameRegEx = <?=!empty($this->utils->isRestrictUsernameEnabled()) ? 1 : 0 ?>;
        Register.validateUsername01 = "<?=$validateUsername01?>";
        Register.validateUsername02 = "<?=$validateUsername02?>";
        Register.username_requirement_mode = <?=$username_requirement_mode ?>;
        Register.username_case_insensitive = <?=$username_case_insensitive ?>;
        Register.usernameRegEx = <?=$regex_username?>;
        Register.passwordRegEx = <?=$regex_password?>;
        Register.retypePassword = "<?=lang('validation.retypePassword')?>";
        Register.retypePasswordCorrect = "<?=lang('validation.retypePasswordCorrect')?>";
        Register.idCardNumberLenght = '<?=$this->utils->getConfig('id_card_number_validator')['char_lenght']?>';
        Register.idCardNumberRequired = <?=$registration_fields['ID Card Number']['required']?>;
        Register.chooseDialingCode = "<?= ($registration_fields['Dialing Code']['visible'] == Registration_setting::VISIBLE) ? 1 : 0 ; ?>";
        Register.countryNumList = <?= json_encode($countryNumList); ?>;
        Register.restrictMinLengthOnFirstName = <?=($this->utils->isEnabledFeature('enabled_player_registration_restrict_min_length_on_first_name_field')) ? 1 : 0;?>;
        Register.enable_OGP19860 = <?=( $enable_OGP19860 )? '1':'0';?>;
        Register.bankAccountMinLength = <?= !empty($account_validator['bankAccountNumber']['min_max_length'])? $account_validator['bankAccountNumber']['min_max_length'][0]:'0';?>;
        Register.bankAccountMaxLength= <?= !empty($account_validator['bankAccountNumber']['min_max_length'])? $account_validator['bankAccountNumber']['min_max_length'][1]:'0';?>;
        Register.validatePlayerEmailExist = '<?= !empty($this->utils->getConfig('enabled_check_email_exist_on_register')) ? 1 :0 ; ?>';

        <?php $contactNumberRegex = $this->config->item('register_mobile_number_regex'); ?>
        <?php if(!empty($contactNumberRegex)):?>
        Register.contactNumberRegex = '<?=addslashes($contactNumberRegex)?>';
        <?php endif ?>
        Register.init();

        <?php if( $enable_OGP19860 ):?>
            if( typeof(LineRegister) !== 'undefined' ){

                LineRegister.callbackAfterOnReady = function(){
                    <?php if( $use_line_register_method_default ):?>
                        $('.registration-tabs .line-reg').trigger('click'); // default with line registration
                    <?php endif; // EOF if( $use_line_register_method_default ): ?>
                };

                LineRegister.onReady();
            } // EOF if( typeof(LineRegister) !== 'undefined' ){...
        <?php endif; // EOF if( $enable_OGP19860 ): ?>

        <?php if ($showSMSField): ?>
        $('#send_sms_verification').click( function() {
            send_verification_code(this);
        });
        $('#send_voice_verification').click( function() {
            send_verification_code(this);
        });
        <?php endif; ?>

        <?php if(!empty($append_js_content)):?>
            iframe_register.append_custom_js();
        <?php endif;?>

        <?php if(!empty($append_smash_js_content)):?>
            smash_iframe_register.append_custom_js();
        <?php endif;?>

        <?php if(!empty($append_ole777id_js_content)):?>
        ole777id_iframe_register.append_custom_js();
        <?php endif;?>

        <?php if(!empty($append_ole777thb_js_content)):?>
        ole777thb_iframe_register.append_custom_js();
        <?php endif;?>
    });

    function refreshCaptcha(){
        //refresh
        $('#image_captcha').attr('src','<?php echo site_url('/iframe/auth/captcha/default/120'); ?>?'+Math.random());
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
            $('#registration_form').submit();
        }
    }

    function showLoading(){
        $('#register_now_btn').empty().text("<?=lang('Loading');?>")
    }

    function showLogin(){
        $('#register_now_btn').empty().text("<?= lang('Register Now'); ?>");
    }

    function send_verification_code(source) {
        if ($('#contactNumber').valid()) {
                // Check contact number column
                $("#mobile_format").parents('form').find('.registration-field-note').addClass('hide');
                $('#sms_verification_msg').text('<?= lang('Please wait') . '...'; ?>');
                $(".msg-container").show().delay(2000).fadeOut();;
                var smsValidBtn = $('#send_sms_verification'),
                    smstextBtn  = smsValidBtn.text(),
                    voiceValidBtn = $('#send_voice_verification'),
                    voicetextBtn  = voiceValidBtn.text(),
                    mobileNumber = $('#contactNumber').val(),
                    dialing_code = $('#dialing_code').val();

                if(allow_first_number_zero && mobileNumber.charAt(0) == '0') {
                    var subMobileNumber = $('input#contactNumber').val().substring(1);
                    console.log(subMobileNumber);
                    mobileNumber = subMobileNumber;
                }

                if(!mobileNumber || mobileNumber == '') {
                    $('#sms_verification_msg').text('<?= lang('Please fill in mobile number')?>');
                    $(".msg-container").show().delay(5000).fadeOut();
                    $('#contactNumber').focus();
                    return;
                }

                SMS_SendVerify(function(sms_captcha_val) {
                    var smsSendSuccess = function() {
                            $('#sms_verification_msg').text('<?= lang('SMS sent')?>');
                            if(source.id == 'send_voice_verification'){
                                $('#sms_verification_msg').text('<?= lang('voice sent')?>');
                            }else{
                                $('#sms_verification_msg').text('<?= lang('SMS sent')?>');
                            }
                        },
                        smsSendFail = function(data=null) {
                            if (data && data.hasOwnProperty('isDisplay') && data['message']) {
                                $('#sms_verification_msg').text(data['message']);
                            } else {
                                if(source.id == 'send_voice_verification'){
                                    $('#sms_verification_msg').text('<?= lang('voice failed')?>');
                                }else{
                                    $('#sms_verification_msg').text('<?= lang('SMS failed')?>');
                                }
                            }
                        },
                        smsCountDown = function() {
                            var smsCountdownnSec = 60,
                                VoiceCountdownnSec = 60,
                                countdown = setInterval(function(){
                                    smsValidBtn.text(smstextBtn + "(" + smsCountdownnSec-- + ")");
                                    voiceValidBtn.text(voicetextBtn + "(" + VoiceCountdownnSec-- + ")");
                                    if(smsCountdownnSec < 0 || VoiceCountdownnSec<0){
                                        clearInterval(countdown);
                                        smsValidBtn.text(smstextBtn);
                                        voiceValidBtn.text(voicetextBtn);
                                        disableSendBtn(false);
                                    }
                                },1000);
                        },
                        disableSendBtn = function (bool) {
                            if (bool) {
                                smsValidBtn.prop('disabled', true);
                                smsValidBtn.removeClass('btn-success');
                                voiceValidBtn.prop('disabled', true);
                                voiceValidBtn.removeClass('btn-success');
                            } else {
                                smsValidBtn.prop('disabled', false);
                                smsValidBtn.addClass('btn-success');
                                voiceValidBtn.prop('disabled', false);
                                voiceValidBtn.addClass('btn-success');
                            }
                        };

                    disableSendBtn(true);

                    var enable_new_sms_setting = '<?= !empty($this->utils->getConfig('use_new_sms_api_setting')) ? true : false ?>';
                    var verificationUrl = '<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/' + mobileNumber;

                    if (enable_new_sms_setting) {
                        verificationUrl = '<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/' + mobileNumber + '/sms_api_register_setting';
                    }

                    // voice api url
                    if(enable_new_sms_setting && source.id == 'send_voice_verification'){
                        verificationUrl = '<?= site_url('iframe_module/iframe_register_send_voice_verification')?>/' + mobileNumber + '/sms_api_register_setting';
                    }else if(source.id == 'send_voice_verification'){
                        verificationUrl = '<?= site_url('iframe_module/iframe_register_send_voice_verification')?>/' + mobileNumber;
                    }

                    $.post(verificationUrl, {
                        sms_captcha: sms_captcha_val,
                        dialing_code: dialing_code,
                    }).done(function(data){
                        (data.success) ? smsSendSuccess() : smsSendFail(data);
                        if (data.hasOwnProperty('field') && data['field'] == 'captcha') {
                            disableSendBtn(false)
                        } else {
                            smsCountDown();
                        }
                    }).fail(function(){
                        smsSendFail();
                        smsCountDown();
                    }).always(function(){
                        $(".msg-container").show().delay(5000).fadeOut();
                    });
                });

            } else {
                $("#mobile_format").parents().find('.registration-field-note').addClass('hide');
                $("#mobile_format").parent().parent().parent().find('.registration-field-note').removeClass('hide');
            }
    }

</script>


    <?php if ($this->operatorglobalsettings->getSettingJson('registration_captcha_enabled')):?>
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
        $(function(){
            onImgLoad('#image_captcha', function(){
                var img$El = $(this);
                var _now = Math.floor(Date.now() / 1000); // timestamp
                img$El.data('onloaded', _now ); // data-onloaded=timestamp
            })
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
    </script>
    <?php endif ?>

<?php endif ?>

