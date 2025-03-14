<?php
    $standard_js = [
        //for template public
        site_url('/pub/variables?v=' . PRODUCTION_VERSION),
        $this->utils->playerResUrl('template_header.js'),
    ];

    $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');

    $standard_css = [
    ];

    $lang_now= $this->language_function->getCurrentLangForPromo();

    if( ! isset($result4fromLine) ){
        $result4fromLine = null;
    }
?>
<?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences'))) :?>
    <link rel="stylesheet" type="text/css" href="<?=$this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css')?>" />
    <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js');?>"></script>
<?php endif;?>

<div id="accountInformation" class="tab-pane main-content">
    <h1><?=lang("Account Information") ?></h1>
    <?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences')) && $registration_fields['Player Preference']['account_visible'] == Registration_setting::VISIBLE) :?>
        <ul class="row fm-ul display_tablist" role="tablist">
            <li class="col-xs-4 col-sm-3 active">
                <a data-toggle="tab" href="#basicInfoTab" id="basic_info_btn"  aria-expanded="false"><?=lang("Basic Information")?></a>
            </li>
            <li class="col-xs-4 col-sm-3">
                <a data-toggle="tab" href="#playerPrefTab" id="player_pref_btn"  aria-expanded="false"><?=lang("pi.player_pref")?></a>
            </li>
        </ul>
    <?php endif;?>

    <div class="tab-content">
        <!-- BASIC INFORMATION SECTION START -->
        <div id="basicInfoTab" class="tab-pane fade in active">
            <div class="row">
                <form id="frmEditPlayer" class="col-md-12" action="<?php echo site_url('/player_center/postEditPlayer'); ?>" method="post" enctype="multipart/form-data" novalidate>
                    <div class="form-group col-md-6 col-sm-6 info-left">
                        <?php if (!$this->utils->isEnabledFeature('hidden_avater_upload')) :?>
                            <p><?=lang("Profile Picture") ?>:</p>
                            <div class="avatar-container">
                                <div class="avatar">
                                    <?php  if (isset($profilePicture)) : ?>
                                        <img src="<?=$profilePicture?>" id="imgProfilePicture" alt="<?php echo $this->authentication->getUsername(); ?>"/>
                                    <?php  else : ?>
                                        <img src="<?=base_url() . $this->utils->getPlayerCenterTemplate() . '/img/default-profile.png' ?>" id="imgProfilePicture" alt="<?php echo $this->authentication->getUsername(); ?>"/>
                                    <?php  endif; ?>
                                </div>
                                <div class="upload-avatar">
                                    <label class="btn btn-default btn-file">
                                        <span><?=lang("Upload Image") ?></span>
                                        <input type="file" name="profileToUpload[]" id="profileToUpload" onchange="AccountInformation.previewProfilePicture(this);" accept="image/*" hidden>
                                        <input type="hidden" name="has_profileToUpload" value="0">
                                    </label>
                                    <br/>
                                    <span id="fileNameToUpload">
                                        <?=lang('Same dimension required')?>
                                        <br/>
                                        (<?=lang('e.g. 200x200, 350x350')?>)
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php $nameOrder = ['firstName','lastName']; ?>
                        <?php if ($this->utils->getConfig('switch_last_name_order_before_first_name')) {
                            $nameOrder = ['lastName','firstName'];
                        }?>
                        <?php
                        foreach ($nameOrder as $item) {
                            switch ($item) {
                                case 'firstName': ?>
                                        <?php if (in_array('FIRSTNAME', $fields)) : ?>
                                            <p class="accountinfo field">
                                                <?= $this->player_functions->checkAccount_displaySymbolHint('First Name')?>
                                                <?=lang("First Name") ?>:
                                                <?= $this->player_functions->checkAccount_displayInputHint('firstName')?>
                                            </p>
                                            <div class="input-group">
                                            <input type="text" class="form-control accountinfo-field" name="name" id="name"
                                                   value="<?= !empty($player['firstName']) ? ucfirst($player['firstName']) : '' ?>"
                                                   placeholder="<?=lang('pi.1') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('First Name')?>"
                                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'firstName') ? '' : 'disabled' ; ?>
                                            />
                                            <?= form_error('name', '<p class="form__error">', '</p>'); ?>
                                            <span class="help-block"><?=lang('notify.108');?></span>
                                            </div>
                                        <?php endif; break;
                                case 'lastName': ?>
                                    <?php if (in_array('LASTNAME', $fields)) : ?>
                                            <p class="accountinfo field">
                                                <?= $this->player_functions->checkAccount_displaySymbolHint('Last Name')?>
                                                <?=lang("Last Name") ?>:
                                            </p>
                                            <div class="input-group">
                                            <input type="text" class="form-control accountinfo-field" name="lastname" id="lname"
                                                   value="<?= !empty($player['lastName']) ? ucfirst($player['lastName']) : '' ?>"
                                                   placeholder="<?=lang('Last Name') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Last Name')?>"
                                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'lastName') ? '' : 'disabled' ; ?>
                                            />
                                            <?= form_error('lastName', '<p class="form__error">', '</p>'); ?>
                                            </div>
                                    <?php endif; break;
                                }
                        }?>

                        <?php if (in_array('GENDER', $fields)) : ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Gender')?>
                                <?=lang("Gender") ?>:
                            </p>
                            <div class="radio">
                                <?php if (!$this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'gender')): ?>
                                    <input type="text" id="gender" class="form-control" value="<?=lang($player['gender'])?>" disabled>
                                <?php else: ?>
                                    <input type="hidden" name="gender" class="form-control" value="">
                                    <label>
                                        <input type="radio" name="gender" id="gender_m" value="Male" <?= ($player['gender'] == 'Male') ? 'checked' : '' ?>/><?=lang('Male')?>
                                    </label>
                                    <label>
                                        <input type="radio" name="gender" id="gender_f" value="Female" <?= ($player['gender'] == 'Female') ? 'checked' : '' ?>><?=lang('Female')?>
                                    </label>
                                <?php endif;?>
                                <?= form_error('gender', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (in_array('BIRTHDATE', $fields)) : ?>
                            <?php
                            $today = new DateTime('now');
                            $validBday = $today->modify('-18 year')->format('Y-m-d');
                            ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Birthday')?>
                                <?=lang("Birthday") ?>:
                            </p>
                            <div class="input-group">
                                <?php if ($this->operatorglobalsettings->getSettingIntValue('birthday_option') == 2 || !empty($player['birthdate'])) : ?>
                                    <input type="date" name="birthdate" id="birthdate" class="datepicker player-birthday form-control input-sm" max="<?=$validBday?>"
                                           value="<?=(set_value('birthdate') != null) ? set_value('birthdate') : $player['birthdate'];?>"
                                           oninvalid="setCustomValidity('<?= lang('validation.ageValidation') ?>')"
                                        <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'birthdate') ? '' : 'disabled' ; ?>
                                    />
                                <?php else : ?>
                                    <div id="year_group" class="col-md-4 birthday-option">
                                        <div class="custom-dropdown">
                                            <select class="selectbox form-control registration-field" id="dob_year" name="dob_year" onchange="AccountInformation.validateDOB()">
                                                <option value=""><?= lang('reg.14') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="month_group" class="col-md-4 lr-padding birthday-option">
                                        <div class="custom-dropdown ">
                                            <select class="selectbox form-control registration-field" id="dob_month" name="dob_month" onchange="AccountInformation.validateDOB()">
                                                <option value=""><?= lang('reg.59') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="day_group" class="col-md-4 lr-padding birthday-option">
                                        <div  class="custom-dropdown ">
                                            <select class="selectbox form-control registration-field" id="dob_day" name="dob_day" onchange="AccountInformation.validateDOB()">
                                                <option value=""><?= lang('reg.13') ?></option>
                                                <?php if( $this->utils->getConfig('birthday_display_format') == 'ddmmyyyy') :?>
                                                    <?php for($d = 1; $d <= 31; $d++): ?>
                                                        <option value="<?=sprintf("%02d", $d)?>"><?=sprintf("%02d", $d)?></option>
                                                    <?php endfor; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <input type="<?= empty(trim($player['birthdate'])) ? "hidden" : "text" ?>" name="birthdate" id="birthdate" class="player-birthday form-control input-sm" value="<?=(set_value('birthdate') != null) ? set_value('birthdate') : $player['birthdate'];?>"/>
                                <?php endif; ?>
                            </div>
                            <?= form_error('birthdate', '<p class="form__error">', '</p>'); ?>

                            <div class="clearfix"></div>
                        <?php endif; ?>

                        <?php if (in_array('BIRTHPLACE', $fields)) : ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('BirthPlace')?>
                                <?=lang("reg.24") ?>:
                            </p>
                            <input type="text" class="form-control"  name="birthplace" id="birthplace" class="form-control"
                                   value="<?= !empty($player['birthplace']) ? ucfirst($player['birthplace']) : "" ?>"
                                   placeholder="<?=lang('reg.24') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('BirthPlace')?>"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'birthplace') ? '' : 'disabled' ; ?>
                            >
                            <?= form_error('birthplace', '<p class="form__error">', '</p>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('CITIZENSHIP', $fields)) : ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Nationality')?>
                                <?=lang("player.61") ?>:
                            </p>
                            <div class="custom-dropdown">
                                <select id="nationality_list" class="form-control registration-field" name="citizenship"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'citizenship') ? '' : 'disabled' ; ?>
                                >
                                    <option value=""><?= lang('reg.22') . ' ' . $this->registration_setting->displayPlaceholderHint('Nationality')?></option>
                                    <?php if(!$this->utils->isEnabledFeature('disable_frequently_use_country_in_registration')) : ?>
                                        <optgroup label="<?=lang('lang.frequentlyUsed')?>">
                                            <?php foreach ($this->utils->getCommonCountryList() as $key) {?>
                                                <option value="<?=$key?>" <?= ($player['citizenship'] == $key) ? "selected" : ""; ?>><?=lang('country.' . $key)?></option>
                                            <?php } ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <optgroup label="<?=lang('lang.alphabeticalOrder')?>">
                                        <?php foreach ($this->utils->getCountryList() as $key) {?>
                                            <option value="<?=$key?>" <?= ($player['citizenship'] == $key) ? "selected" : ""; ?>><?=lang('country.' . $key)?></option>
                                        <?php } ?>
                                    </optgroup>
                                </select>
                            </div>

                            <?= form_error('citizenship', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('LANGUAGE', $fields)) : ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Language')?>
                                <?=lang("player.62") ?>:
                            </p>
                            <select class="form-control input-sm" name="language" <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'language') ? '' : 'disabled' ; ?>>
                                <?php foreach(Language_function::PlayerSupportLanguageNames() as $lang_key => $lang_value): ?>
                                    <option value="<?=$lang_key?>" <?=(strtolower($player['language']) == strtolower($lang_key)) ? "selected":''?>><?php echo $lang_value;?></option>
                                <?php endforeach ?>
                            </select>
                            <?= form_error('language', '<p class="form__error">', '</p>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('EMAIL', $fields)) : ?>
                            <?php
                            $obfuscatedEmail = '';
                            if(!empty($player['email']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_email')){
                                $em   = explode("@",$player['email']);
                                $name = implode(array_slice($em, 0, count($em)-1), '@');
                                $len  = floor(strlen($name)/2);
                                $obfuscatedEmail = substr($name,0, $len) . str_repeat('*', $len) . "@" . end($em);
                            }

                            $_email = !empty($player['email']) ? (($obfuscatedEmail) ? $obfuscatedEmail : $player['email']) : "";
                            # Enable verification email and email verified
                            $_email_disable_edit = ($_email && $player['verified_email']);
                            ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Email')?>
                                <?=lang("Email") ?>:
                            </p>
                            <div class="input-group">
                            <input type="email" name="email" class="form-control accountinfo-field" id="fm-cn"
                                   value="<?= $_email ?>"
                                   placeholder="<?=lang('lang.email') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Email')?>"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'email', $_email_disable_edit) ? '' : 'disabled' ; ?>
                            />
                            <span id="email_verified_status" class="input-group-addon hide"></span>
                            </div>
                        <?php endif; ?>

                        <?php if (in_array('DIALING_CODE', $fields)) : ?>
                            <?php
                            $countryNumList = unserialize(COUNTRY_NUMBER_LIST_FULL);
                            $requireDialingCode = ($registration_fields['Dialing Code']['account_required'] == Registration_setting::REQUIRED) ? 1 : 0;
                            ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint($registration_fields['Dialing Code']['field_name'])?>
                                <?=lang("player.107") ?>:
                            </p>
                            <select id="dialing_code" class="form-control input-sm <?=($requireDialingCode) ? 'required' : ''?>" name="dialing_code"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'dialing_code') ? '' : 'disabled' ; ?>
                            >
                                <option title="<?=lang('reg.77')?><?= $this->registration_setting->displayPlaceholderHint('Dialing Code')?>" country="" value="">
                                    <?=lang('reg.77') . ' ' . $this->registration_setting->displayPlaceholderHint('Dialing Code')?>
                                </option>
                                <?php foreach ($countryNumList as $country => $nums) : ?>
                                    <?php if (is_array($nums)) : ?>
                                        <?php foreach ($nums as $_nums) : ?>
                                            <option title="(+<?=$_nums?>)" country="<?=$country?>" value="<?=$_nums?>" <?= (set_value('dialing_code', $player['dialing_code']) == $_nums) ? 'selected' : '' ; ?>>
                                                <?= sprintf("%s (+%s)", lang('country.'.$country), $_nums);?>
                                            </option>
                                        <?php endforeach ; ?>
                                    <?php else : ?>
                                        <option title="(+<?=$nums?>)" country="<?=$country?>" value="<?=$nums?>" <?= (set_value('dialing_code', $player['dialing_code']) == $nums) ? 'selected' : '' ; ?>>
                                            <?= sprintf("%s (+%s)", lang('country.'.$country), $nums); ?>
                                        </option>
                                    <?php endif ; ?>
                                <?php endforeach ; ?>
                            </select>
                        <?php endif; ?>

                        <?php if (in_array('CONTACTNUMBER', $fields)) : ?>
                            <?php
                            $obfuscatedPhone = '';
                            if (!empty($player['contactNumber']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_phone')) {
                                $obfuscatedPhone = $this->utils->keepOnlyString($player['contactNumber'], -4);
                            }
                            $_contactNumber = !empty($player['contactNumber']) ? (($obfuscatedPhone) ? $obfuscatedPhone : $player['contactNumber']) : '';
                            $_contact_number_disable_edit = ($_contactNumber && ($this->utils->isEnabledFeature('enabled_show_player_obfuscated_phone') || $player['verified_phone']));
                            ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Contact Number')?>
                                <?=lang("Contact No.") ?>:
                            </p>
                            <?php if($this->utils->getConfig('contact_number_note')): ?>
                                <span class="help-block <?= ($player['contactNumber']) ? 'hidden' : "" ?>" style="clear:left; padding-top:5px"><?= lang('phone_note') ?></span>
                            <?php endif; ?>
                            <div class="input-group">
                            <input type="text" name="contact_number" id="contact_number" class="form-control input-sm accountinfo-field" value="<?= $_contactNumber ?>"
                                   placeholder="<?=lang('contact_number_placeholder') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Contact Number')?>"
                                   onkeyup="this.value=this.value.replace(/[^(0-9)]/g,'');"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'contactNumber', $_contact_number_disable_edit) ? '' : 'disabled' ; ?>
                            />
                            <span id="contactnumber_verified_status" class="input-group-addon hide"></span>
                            </div>
                            <?php if(!$this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'contactNumber', $_contact_number_disable_edit)): ?>
                                <span class="help-block <?= empty($player['contactNumber']) ? 'hidden' : "" ?>" style="clear:left; padding-top:5px"><?= lang('Please contact our Customer Service if you would like to update your contact number.') ?></span>
                            <?php endif; ?>
                            <?php if ($this->utils->getConfig('enable_verify_phone_number_in_account_information_of_player_center') && !$player['verified_phone']): ?>
                                <div class="fcmonu-note mb20 msg-container" style="display:none">
                                    <p class="pl15"><i class="icon-warning red f16 mr5"></i><span id="sms_verification_msg"></span></p>
                                </div>
                                <button type="button" id="send_sms_verification" class="btn form-control"
                                    onclick="send_verification_code()" >
                                    <?=lang('Send SMS')?>
                                </button>

                                <p class="accountinfo field">
                                    <?php if(! empty($enable_OGP19808 ) ): ?>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('SMS Verification Code')?>
                                    <?php endif; // EOF if(! empty($enable_OGP19808 ) ): ?>
                                    <?=lang("SMS Code") ?>:
                                </p>
                                <input type="text" class="form-control input-sm" name="sms_verification_code" placeholder="<?=lang('SMS Code')?>"/>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php 
                            $custom_new_imaccount_rules = $this->utils->getConfig('custom_new_imaccount_rules');
                            $imAccountOnlyNumber  = isset($custom_new_imaccount_rules['imAccount']['onlyNumber']) ? $custom_new_imaccount_rules['imAccount']['onlyNumber'] : false;
                            $imAccount2OnlyNumber = isset($custom_new_imaccount_rules['imAccount2']['onlyNumber']) ? $custom_new_imaccount_rules['imAccount2']['onlyNumber'] : false;
                            $imAccount4OnlyNumber = isset($custom_new_imaccount_rules['imAccount4']['onlyNumber']) ? $custom_new_imaccount_rules['imAccount4']['onlyNumber'] : false;
                            $imAccount5OnlyNumber = isset($custom_new_imaccount_rules['imAccount5']['onlyNumber']) ? $custom_new_imaccount_rules['imAccount5']['onlyNumber'] : false;
                            $imAccountShowMsg  = isset($custom_new_imaccount_rules['imAccount']['showMsg'])  ? $custom_new_imaccount_rules['imAccount']['showMsg'] : false;
                            $imAccount2ShowMsg = isset($custom_new_imaccount_rules['imAccount2']['showMsg']) ? $custom_new_imaccount_rules['imAccount2']['showMsg'] : false;
                            $imAccount4ShowMsg = isset($custom_new_imaccount_rules['imAccount4']['showMsg']) ? $custom_new_imaccount_rules['imAccount4']['showMsg'] : false;
                            $imAccount5ShowMsg = isset($custom_new_imaccount_rules['imAccount5']['showMsg']) ? $custom_new_imaccount_rules['imAccount5']['showMsg'] : false;
                            $imaccount_sort = $this->utils->getConfig('account_imformation_imaccount_sort');
                            foreach($imaccount_sort as $field_name) {
                                switch ($field_name){
                                    case 'IMACCOUNT':
                                        if (in_array($field_name, $fields)) {
                                            $obfuscatedIm = '';
                                            if (!empty($player['imAccount']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                                $obfuscatedIm = $this->utils->keepOnlyString($player['imAccount'], -4);
                                            }
                                            $_im = !empty($player['imAccount']) ? (($obfuscatedIm) ? $obfuscatedIm : $player['imAccount']) : '';
                                            $_im_disable_edit = ($_im && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                            $_im_allow_edit = $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount', $_im_disable_edit);
                                            $im1 = $this->config->item('Instant Message 1', 'cust_non_lang_translation');?>
                                            <p class="accountinfo field">
                                                <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 1')?>
                                                <?= ($im1) ? $im1 : lang('Instant Message 1')?>:
                                            </p>
                                            <input type="text" name="im_account" id="im_account" class="form-control input-sm" value="<?= $_im ?>"
                                                placeholder="<?=(($im1) ? $im1 : lang('Instant Message 1')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 1')?>"
                                            <?php if($imAccountShowMsg && $_im_allow_edit){ ?>
                                                onfocus="AccountInformation.showWarningMsg(this)" onblur="AccountInformation.hideWarningMsg()"
                                            <?php }?>
                                            <?php if($imAccountOnlyNumber){ ?>
                                                onkeyup="this.value=this.value.replace(/[^(0-9)]/g,'');"
                                            <?php }?>
                                            <?= $_im_allow_edit ? '' : 'readonly' ; ?>
                                            >
                                            <div class="fcimaccount-note registration-field-note hide mb20">
                                                <p class="pl15 mb0"><i id="imaccount_format" class="icon-warning red f16 mr5"></i> <?=lang('Masukkan Nomor HP dengan format yang benar')?></p>
                                                <p class="pl15"><i id="imaccount_warning" class="icon-warning red f16 mr5"></i> <?=lang('Angka 0 diawal tidak perlu dimasukkan')?></p>
                                            </div>
                                            <?= form_error('im_account', '<p class="form__error">', '</p>'); ?><?php
                                        }
                                    break;
                                    case 'IMACCOUNT2':
                                        if (in_array('IMACCOUNT2', $fields)) {
                                            $obfuscatedIm2 = '';
                                            if (!empty($player['imAccount2']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                                $obfuscatedIm2 = $this->utils->keepOnlyString($player['imAccount2'], -4);
                                            }
                                            $_im2 = !empty($player['imAccount2']) ? (($obfuscatedIm2) ? $obfuscatedIm2 : $player['imAccount2']) : '';
                                            $_im2_disable_edit = ($_im2 && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                            $_im2_allow_edit = $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount2', $_im2_disable_edit);
                                            $im2 = $this->config->item('Instant Message 2', 'cust_non_lang_translation');?>
                                            <p class="accountinfo field">
                                                <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 2')?>
                                                <?=($im2) ? $im2 : lang('Instant Message 2')?>:
                                            </p>
                                            <input type="text" name="im_account2" id="im_account2" class="form-control input-sm" value="<?= $_im2 ?>"
                                                   placeholder="<?=(($im2) ? $im2 : lang('Instant Message 2')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 2')?>"
                                            <?php if($imAccount2ShowMsg && $_im2_allow_edit){ ?>
                                                onfocus="AccountInformation.showWarningMsg(this)" onblur="AccountInformation.hideWarningMsg()"
                                            <?php }?>
                                            <?php if($imAccount2OnlyNumber){ ?>
                                                onkeyup="this.value=this.value.replace(/[^(0-9)]/g,'');"
                                            <?php }?>
                                            <?= $_im2_allow_edit ? '' : 'readonly' ; ?>
                                            />
                                            <div class="fcimaccount-note registration-field-note hide mb20">
                                                <p class="pl15 mb0"><i id="imaccount_format" class="icon-warning red f16 mr5"></i> <?=lang('Masukkan Nomor HP dengan format yang benar')?></p>
                                                <p class="pl15"><i id="imaccount_warning" class="icon-warning red f16 mr5"></i> <?=lang('Angka 0 diawal tidak perlu dimasukkan')?></p>
                                            </div>
                                            <?= form_error('im_account2', '<p class="form__error">', '</p>'); ?><?php
                                        }
                                    break;
                                    case 'IMACCOUNT3':
                                        if (in_array('IMACCOUNT3', $fields)) {
                                            $obfuscatedIm3 = '';
                                            if (!empty($player['imAccount3']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                                $obfuscatedIm3 = $this->utils->keepOnlyString($player['imAccount3'], -4);
                                            }
                                            $_im3 = !empty($player['imAccount3']) ? (($obfuscatedIm3) ? $obfuscatedIm3 : $player['imAccount3']) : '';
                                            $_im3_disable_edit = ($_im3 && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                            $_im3_allow_edit = $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount3', $_im3_disable_edit);
                                            $im3 = $this->config->item('Instant Message 3', 'cust_non_lang_translation');?>
                                            <p class="accountinfo field">
                                                <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 3')?>
                                                <?=($im3) ? $im3 : lang('Instant Message 3')?>:
                                            </p>
                                            <input type="text" name="im_account3" id="im_account3" class="form-control input-sm" value="<?= $_im3 ?>"
                                                   placeholder="<?=(($im3) ? $im3 : lang('Instant Message 3')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 3')?>"
                                            <?= $_im3_allow_edit ? '' : 'readonly' ; ?>
                                            >
                                            <?= form_error('im_account3', '<p class="form__error">', '</p>'); ?><?php
                                        }
                                    break;
                                    case 'IMACCOUNT4':
                                        if (in_array('IMACCOUNT4', $fields)) {
                                            $obfuscatedIm4 = '';
                                            if (!empty($player['imAccount4']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                                $obfuscatedIm4 = $this->utils->keepOnlyString($player['imAccount4'], -4);
                                            }
                                            $_im4 = !empty($player['imAccount4']) ? (($obfuscatedIm4) ? $obfuscatedIm4 : $player['imAccount4']) : '';
                                            $_im4_disable_edit = ($_im4 && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                            $_im4_allow_edit = $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount4', $_im4_disable_edit);
                                            $im4 = $this->config->item('Instant Message 4', 'cust_non_lang_translation');?>
                                            <p class="accountinfo field">
                                                <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 4')?>
                                                <?=($im4) ? $im4 : lang('Instant Message 4')?>:
                                            </p>
                                            <input type="text" name="im_account4" id="im_account4" class="form-control input-sm" value="<?= $_im4 ?>"
                                                   placeholder="<?=(($im4) ? $im4 : lang('Instant Message 4')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 4')?>"
                                            <?php if($imAccount4ShowMsg && $_im4_allow_edit){ ?>
                                                onfocus="AccountInformation.showWarningMsg(this)" onblur="AccountInformation.hideWarningMsg()"
                                            <?php }?>
                                            <?php if($imAccount4OnlyNumber){ ?>
                                                onkeyup="this.value=this.value.replace(/[^(0-9)]/g,'');"
                                            <?php }?>
                                            <?= $_im4_allow_edit ? '' : 'readonly' ; ?>
                                            >
                                            <div class="fcimaccount-note registration-field-note hide mb20">
                                                <p class="pl15 mb0"><i id="imaccount_format" class="icon-warning red f16 mr5"></i> <?=lang('Masukkan Nomor HP dengan format yang benar')?></p>
                                                <p class="pl15"><i id="imaccount_warning" class="icon-warning red f16 mr5"></i> <?=lang('Angka 0 diawal tidak perlu dimasukkan')?></p>
                                            </div>
                                            <?= form_error('im_account4', '<p class="form__error">', '</p>'); ?><?php
                                        }
                                    break;
                                    case 'IMACCOUNT5':
                                        if (in_array('IMACCOUNT5', $fields)) {
                                            $obfuscatedIm5 = '';
                                            if (!empty($player['imAccount5']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                                $obfuscatedIm5 = $this->utils->keepOnlyString($player['imAccount5'], -4);
                                            }
                                            $_im5 = !empty($player['imAccount5']) ? (($obfuscatedIm5) ? $obfuscatedIm5 : $player['imAccount5']) : '';
                                            $_im5_disable_edit = ($_im5 && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                            $_im5_allow_edit = $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount5', $_im5_disable_edit);
                                            $im5 = $this->config->item('Instant Message 5', 'cust_non_lang_translation');?>
                                            <p class="accountinfo field">
                                                <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 5')?>
                                                <?=($im5) ? $im5 : lang('Instant Message 5')?>:
                                            </p>
                                            <input type="text" name="im_account5" id="im_account5" class="form-control input-sm" value="<?= $_im5 ?>"
                                                   placeholder="<?=(($im5) ? $im5 : lang('Instant Message 5')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 5')?>"
                                            <?php if($imAccount5ShowMsg && $_im5_allow_edit){ ?>
                                                onfocus="AccountInformation.showWarningMsg(this)" onblur="AccountInformation.hideWarningMsg()"
                                            <?php }?>
                                            <?php if($imAccount5OnlyNumber){ ?>
                                                onkeyup="this.value=this.value.replace(/[^(0-9)]/g,'');"
                                            <?php }?>
                                            <?= $_im5_allow_edit ? '' : 'readonly' ; ?>
                                            >
                                            <div class="fcimaccount-note registration-field-note hide mb20">
                                                <p class="pl15 mb0"><i id="imaccount_format" class="icon-warning red f16 mr5"></i> <?=lang('Masukkan Nomor HP dengan format yang benar')?></p>
                                                <p class="pl15"><i id="imaccount_warning" class="icon-warning red f16 mr5"></i> <?=lang('Angka 0 diawal tidak perlu dimasukkan')?></p>
                                            </div>
                                            <?= form_error('im_account5', '<p class="form__error">', '</p>'); ?><?php
                                        }
                                    break;
                                }
                            }
                        ?> <!-- end account imformation imaccount sort -->

                        <?php if (in_array('ZIPCODE', $fields)) : ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Zip Code')?>
                                <?=lang("a_reg.48") ?>:
                            </p>
                            <input type="text" name="zipcode" id="zipcode" class="form-control letters_only"
                                   value="<?= !empty($player['zipcode']) ? $player['zipcode'] : '' ?>"
                                   placeholder="<?= lang("a_reg.48") . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Zip Code')?>"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'zipcode') ? '' : 'disabled' ; ?>
                            >
                            <?= form_error('zipcode', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('RESIDENTCOUNTRY', $fields)) : ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Resident Country')?>
                                <?=lang("reg.a42") ?>:
                            </p>
                            <div class="custom-dropdown">
                                <select id="country_list" class="form-control registration-field" name="residentCountry"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'residentCountry') ? '' : 'disabled' ; ?>
                                >
                                    <option value=""><?= lang('reg.a42') . ' ' . $this->registration_setting->displayPlaceholderHint('Resident Country')?></option>
                                    <?php if(!$this->utils->isEnabledFeature('disable_frequently_use_country_in_registration')) : ?>
                                        <optgroup label="<?=lang('lang.frequentlyUsed')?>">
                                            <?php foreach ($this->utils->getCommonCountryList() as $key) {?>
                                                <option value="<?=$key?>"><?=lang('country.' . $key)?> <?= ($player['residentCountry'] == $key) ? "selected" : ""; ?></option>
                                            <?php } ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <optgroup label="<?=lang('lang.alphabeticalOrder')?>">
                                        <?php foreach ($this->utils->getCountryList() as $key) {?>
                                            <option value="<?=$key?>" <?= ($player['residentCountry'] == $key) ? "selected" : ""; ?>><?=lang('country.' . $key)?></option>
                                        <?php } ?>
                                    </optgroup>
                                </select>
                            </div>
                            <?= form_error('residentCountry', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        <?php endif; ?>

                        <?php $address_keys = ['REGION','CITY','ADDRESS','ADDRESS2'];?>
                        <?php $exist_required_symbol = false ?>
                        <?php if(array_intersect($address_keys, $fields)): ?>
                            <p class="accountinfo field">
                                <?php if(count(array_intersect($address_keys, $fields)) == 1 && $this->utils->getConfig('only_exist_single_address_key_in_acc_info')): ?>
                                    <?php $exist_single_address_key = array_values(array_intersect($address_keys, $fields)); ?>
                                    <?php $exist_required_symbol = true ?>
                                    <?php if(is_array($exist_single_address_key)): ?>
                                        <?= $this->player_functions->checkAccount_displaySymbolHint(ucfirst(strtolower($exist_single_address_key[0])))?>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?=lang('player.59');?>:
                                <?php $address_prompt = $this->utils->getConfig('account_information_address_prompt');
                                if($address_prompt): ?>
                                    <div class="address_prompt"><?=lang('account_information_address_prompt');?></div>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if (in_array('REGION', $fields)) : ?>
                            <?php $placeholder = ($full_address_in_one_row) ? lang('Please input your full address') : lang('a_reg.37.placeholder');?>
                            <?php if(!$exist_required_symbol): ?>
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Region')?>
                            <?php endif; ?>
                            <input type="text" name="region" id="region" class="form-control" data-toggle="popover"
                                   value="<?= !empty($player['region']) ? $player['region'] : '' ?>"
                                   placeholder="<?=$placeholder;?> <?= $this->player_functions->checkAccount_displayPlaceholderHint('Region')?>"
                                   maxlength="120"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'region') ? '' : 'disabled' ; ?>
                            >
                            <?= form_error('region', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('CITY', $fields)) : ?>
                            <?php if(!$exist_required_symbol): ?>
                                <?= $this->player_functions->checkAccount_displaySymbolHint('City')?>
                            <?php endif; ?>
                            <input type="text" name="city" id="city" class="form-control letters_only"
                                   value="<?= !empty($player['city']) ? $player['city'] : '' ?>"
                                   placeholder="<?= lang("a_reg.36.placeholder") . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('City')?>"
                                   maxlength="120"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'city') ? '' : 'disabled' ; ?>
                            >
                            <?= form_error('city', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('ADDRESS', $fields)) : ?>
                            <?php if(!$exist_required_symbol): ?>
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Address')?>
                            <?php endif; ?>
                            <input type="text" name="address" id="address" class="form-control" data-toggle="popover"
                                   value="<?= !empty($player['address']) ? $player['address'] : '' ?>"
                                   placeholder="<?=lang('a_reg.43.placeholder') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Address')?>"
                                   maxlength="120"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'address') ? '' : 'disabled' ; ?>
                            >
                            <?= form_error('address', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('ADDRESS2', $fields)) : ?>
                            <?php if(!$exist_required_symbol): ?>
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Address2')?>
                            <?php endif; ?>
                            <input type="text" name="address2" id="address2" class="form-control" data-toggle="popover"
                                   value="<?= !empty($player['address2']) ? $player['address2'] : '' ?>"
                                   placeholder="<?=lang('a_reg.44.placeholder');?> <?= $this->player_functions->checkAccount_displayPlaceholderHint('Address2')?>"
                                   maxlength="120"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'address2') ? '' : 'disabled' ; ?>
                            >
                            <?= form_error('address2', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('ID_CARD_TYPE', $fields)) : ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('ID Card Type')?>
                                <?=lang("a_reg.51") ?>:
                            </p>
                            <select class="form-control input-sm" name="id_card_type" <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'id_card_type') ? '' : 'disabled' ; ?>>
                                <option value=""><?= lang('Select ID Card Type')?></option>
                                <?php foreach($this->utils->idCardType() as $key => $value): ?>
                                    <option value="<?=$value['code_type']?>" <?=(strtolower($player['id_card_type']) == strtolower($value['code_type'])) ? "selected":''?>><?php echo $value['type_name'];?></option>
                                <?php endforeach ?>
                            </select>
                            <?= form_error('id_card_type', '<p class="form__error">', '</p>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('ID_CARD_NUMBER', $fields)) : ?>
                            <?php
                            $_id_card_number_disable_edit = (!empty($player['id_card_number']) && !$player_verification['verified']);
                            ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('ID Card Number')?>
                                <?=lang("a_reg.49") ?>:
                            </p>
                            <input type="text" name="id_card_number" id="id_card_number" class="form-control letters_only"
                                   value="<?= !empty($player['id_card_number']) ? $player['id_card_number'] : '' ?>"
                                   placeholder="<?= lang("a_reg.49") . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('ID Card Number')?>"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'id_card_number', $_id_card_number_disable_edit) ? '' : 'disabled' ; ?>
                            >
                            <?= form_error('id_card_number', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        <?php endif; ?>

                        <?php if (in_array('PIX_NUMBER', $fields)) : ?>
                            <?php
                            $_pix_number_disable_edit = false;
                            ?>
                            <p class="accountinfo field">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Pix Number')?>
                                <?=lang("a_reg.61") ?>:
                            </p>
                            <div class="input-group">
                            <input type="text" name="pix_number" id="pix_number" class="form-control letters_only accountinfo-field"
                                   value="<?= !empty($player['pix_number']) ? $player['pix_number'] : '' ?>"
                                   placeholder="<?= lang("a_reg.61") . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Pix Number')?>"
                                   onkeyup="this.value=this.value.replace(/[^(0-9)]/g,'');"
                                   <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'pix_number', $_pix_number_disable_edit) ? '' : 'disabled' ; ?>
                            >
                            <?= form_error('pix_number', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($this->utils->isEnabledFeature('enable_player_prefs_auto_transfer')) : ?>
                            <p><?=lang("Auto-transfer all balance to game wallet on game launch") ?>:</p>
                            <div class="row">
                                <div class="col-sm-4">
                                    <label>
                                        <?= form_radio([ 'name' => 'enable_auto_transfer' ,
                                            'value' => 'yes' , 'checked' => $enable_auto_transfer ]) ?>
                                        <?= lang('Enable') ?>
                                    </label>
                                </div>
                                <div class="col-sm-4">
                                    <label>
                                        <?= form_radio([ 'name' => 'enable_auto_transfer' ,
                                            'value' => 'no' , 'checked' => !$enable_auto_transfer ]) ?>
                                        <?= lang('Disable') ?>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($this->utils->getConfig('enable_thirdparty_bind_existing') && $lineInfo) {
                            $display_thirdparty_login_area = $this->utils->getConfig('enable_thirdparty_login_component') && is_array($this->utils->getConfig('thirdparty_sso_type'));
                            if($display_thirdparty_login_area):
                                $_lang_thirdparty_login_component_in_or__wrapper= "Bind";
                                require_once dirname(__FILE__) . '/../../includes/thirdparty_bind_component.php';
                            elseif ($this->utils->getConfig('line_credential')):?>
                                <div >
                                    <div class="col-md-12 col-lg-12">
                                        <a href="/iframe/auth/line_login" type="button" class="btn btn-primary btn-line-register">
                                            <span>
                                                <img src="/includes/images/line-logo.png">
                                                <?= lang('Click here to login'); ?>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            <?php endif;?>
                        <?php } ?>
                        <button id="save_btn" type="submit" class="btn form-control"><?=lang("Save") ?></button>
                    </div>
                    <div class="info-right col-md-6 col-sm-6">
                        <div class="circle">
                            <div class="pie_left">
                                <div class="left"></div>
                            </div>
                            <div class="pie_right">
                                <div class="right"></div>
                            </div>
                            <div class="mask"><span class="percent"><?=$profileProgress?></span>%</div>
                        </div>
                        <p><?=lang("Complete your information to secure your account and funds.") ?></p>
                        <img src="<?=base_url() . $this->utils->getPlayerCenterTemplate()?>/img/info-icon.png">
                    </div>
                </form>
            </div>
        </div>
        <!-- BASIC INFORMATION SECTION END -->

        <!-- PLAYER PREFERENCE SECTION START -->
        <div id="playerPrefTab" class="tab-pane fade">
            <div class="row">
                <div class="col-md-12">
                    <br>
                    <p><?=sprintf(lang('pi.player_pref.hint1'), lang('pi.player_pref_custom_name'))?></p>
                    <br>
                    <p><?=lang('pi.player_pref.hint2')?></p>
                    <br><br>
                    <table class="table table-sortable" id="player-preference" style="border:1px #eee solid;">
                        <div class="player-pref-table">
                            <?php foreach ($config_prefs as $key => $config_pref): ?>
                            <?php
                                $isChecked = '';
                                $pref_lang = 'Player Preference ' . lang($config_pref);

                                if($registration_fields[$pref_lang]['account_visible'] == Registration_setting::HIDDEN)
                                    continue;
                                if(!empty($current_preferences) && isset($current_preferences->$key) && $current_preferences->$key == 'true')
                                    $isChecked = 'checked';
                            ?>
                                <tr>
                                    <td><label class="checkbox-inline" for="pref-data-<?=$key?>"><?=lang($pref_lang)?></label></td>
                                    <td><input type="checkbox" name="pref-data-<?=$key?>" class="pref-data-<?=$key?>" value="<?=$key?>" <?=$isChecked?>></td>
                                </tr>
                            <?php endforeach ?>
                        </div>
                    </table>
                </div>
            </div>
        </div>
        <!-- PLAYER PREFERENCE SECTION END -->
    </div>
</div>
<?= $this->CI->load->widget('sms'); ?>
<?php include VIEWPATH . '/resources/third_party/DateRangePicker.php'; ?>
<?php
    foreach ($standard_css as $css_url) {
        echo '<link href="' . $css_url . '" rel="stylesheet"/>';
    }

    foreach ($standard_js as $js_url) {
        echo '<script type="text/javascript" src="' . $js_url . '"></script>';
    }
?>
<script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/account-information.js') ?>"></script>
 <?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences'))) :?>
     <script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/player-preferences.js') ?>"></script>
 <?php endif;?>

<script type="text/javascript">
    var birthday_display_format = "<?= $this->utils->getConfig('birthday_display_format') ? $this->utils->getConfig('birthday_display_format') : 'yyyymmdd' ?>";
    $(function(){

        var enabled_player_center_customized_accountinfo = "<?=$this->utils->getConfig('enabled_player_center_customized_accountinfo') ? '1' : '0'?>";
        var verified_phone = "<?=$player['verified_phone']?>";
        var verified_email = "<?=$player['verified_email']?>";

        if (enabled_player_center_customized_accountinfo == '1') {
            if (verified_email == '1') {
                $('#email_verified_status').removeClass('hide').append('<i class="fa fa-check-circle green"></i>');
            }else{
                $('#email_verified_status').removeClass('hide').append('<i class="fa fa-times-circle red"></i>');
            }

            if (verified_phone == '1') {
                $('#contactnumber_verified_status').removeClass('hide').append('<i class="fa fa-check-circle green"></i>');
            }else{
                $('#contactnumber_verified_status').removeClass('hide').append('<i class="fa fa-times-circle red"></i>');
            }

            var prompt_name = '<i class="fa fa-exclamation-circle" data-toggle="tooltip" data-placement="bottom" title="<?=lang('custom_prompt.name')?>" data-container="body"></i>';
            var prompt_lastname = '<i class="fa fa-exclamation-circle" data-toggle="tooltip" data-placement="bottom" title="<?=lang('custom_prompt.lastname')?>" data-container="body"></i>';
            var prompt_email = '<i class="fa fa-exclamation-circle" data-toggle="tooltip" data-placement="bottom" title="<?=lang('custom_prompt.email')?>" data-container="body"></i>';
            var prompt_contactnumber = '<i class="fa fa-exclamation-circle" data-toggle="tooltip" data-placement="bottom" title="<?=lang('custom_prompt.contactnumber')?>" data-container="body"></i>';
            var prompt_pix_number = '<i class="fa fa-exclamation-circle" data-toggle="tooltip" data-placement="bottom" title="<?=lang('custom_prompt.pix_number')?>" data-container="body"></i>';
            var prompt_birthday = '<i class="fa fa-exclamation-circle" data-toggle="tooltip" data-placement="bottom" title="<?=lang('mod.mustbefillrealbirthday')?>" data-container="body"></i>';

            $('#name').after(prompt_name);
            $('#lname').after(prompt_lastname);
            $('#fm-cn').after(prompt_email);
            $('#contact_number').after(prompt_contactnumber);
            $('#pix_number').after(prompt_pix_number);
            $('#birthdate').after(prompt_birthday);

            // $('.accountinfo-field').on('mouseover mouseout', function (e) {
            //     let target = e.target;
            //     let elName = target.name;
            //     checked_custom_prompt(target, elName);
            // });

            // function checked_custom_prompt(target, elName){
            //     if (elName == 'email' || elName == 'contact_number') {
            //         $(target).parent('div').next('p').toggleClass('hide');
            //     }else{
            //         $(target).next('p').toggleClass('hide');
            //     }
            // }
        }

        /** initialize status messages */
        AccountInformation.msgWait = "<?= lang('Please wait'); ?>";
        AccountInformation.msgSmsSent = "<?= lang('SMS sent'); ?>";
        AccountInformation.msgSmsFailed = "<?= lang('SMS failed'); ?>";
        AccountInformation.uploadLabel = "<?= lang('Upload: ') ?>";
        AccountInformation.allow_first_number_zero = <?= !empty($this->utils->getConfig('allow_first_number_zero')) ? 'true' : 'false' ?>;
        <?php if ($this->operatorglobalsettings->getSettingIntValue('birthday_option') == 2 || !empty($player['birthdate'])) : ?>
            $(".datepicker.player-birthday:enabled").datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                endDate: "-<?= $this->config->item('legal_age') ?>y",
                clearBtn: true,
                calendarWeeks: true,
                language: "<?= $lang_now?>"
                //todayHighlight: true
            });
        <?php else : ?>
            AccountInformation.initDOB({
                yearSelector:  "#dob_year",
                monthSelector: "#dob_month",
                daySelector:   "#dob_day",
                inputDOB : $('input[name="birthdate"]'),
                legal_age : <?= $this->config->item('legal_age') ?>
            });
            AccountInformation.showBirthdayDisplayFormat(birthday_display_format);
        <?php endif; ?>


        $('#frmEditPlayer').on('submit', function(){
            show_loading();

            // Client-side check for emoji and other invalid chars, OGP-12268
            var block_emoji = <?= $this->utils->isEnabledFeature('block_emoji_chars_in_real_name_field') ? 'true' : 'false' ?>;
            var fields = {
                'first_name': '<?= lang('First Name') ?>',
                'last_name': '<?= lang('Last Name') ?>',
                'email': '<?= lang('Email') ?>',
            };

            var valid_names = AccountInformation.validateNames(this, block_emoji);

            if (valid_names.result == false) {
                stop_loading();
                var mesg_valid_error = '<?= lang('formvalidation.regex_match') ?>'.replace('%s', fields[valid_names.field]);
                MessageBox.danger(mesg_valid_error);
                return false;
            }

            <?php
                if($this->utils->getConfig('client_side_email_validation')):
            ?>

                    var email = $('#fm-cn').val();

                    var email_test = {
                        test1: /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@/,
                        test2: /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))/,
                        test3: /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@([A-Za-z0-9.]+)$/,
                        test4: /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
                    };

                    if (email == "") {
                        MessageBox.danger("<?= lang('formvalidation.required') ?>".replace('%s', fields['email']));
                        stop_loading();
                        return false;
                    }
                    else if (email_test.test4.test(email)) {

                    }
                    else if (!email_test.test1.test(email) && !email_test.test2.test(email) && !email_test.test3.test(email) && !email_test.test4.test(email)) {
                        MessageBox.danger("<?= lang('formvalidation.email_missing_at') ?>".replace('%s', email));
                        stop_loading();
                        return false;
                    }
                    else if (email_test.test1.test(email) && email_test.test2.test(email) && !email_test.test3.test(email) && !email_test.test4.test(email)) {
                        var special_char = email.substring(email.indexOf('@')+1).replace(/[a-zA-Z0-9.]/g, '')
                        MessageBox.danger("<?= lang('formvalidation.email_has_special_character') ?>".replace('%s', special_char));
                        stop_loading();
                        return false;
                    }
                    else {
                        MessageBox.danger("<?= lang('formvalidation.email_incomplete') ?>".replace('%s', email));
                        stop_loading();
                        return false;
                    }
            <?php endif; ?>

            var response = {
                "status": null,
                "msg": null
            };

            var formdata = new FormData(this);

            $.ajax({
                type: "POST",
                url: $(this).attr('action'),
                mimeTypes:"multipart/form-data",
                contentType: false,
                cache: false,
                processData: false,
                data: formdata
            }).done(function(data){
                response = data;
            }).fail(function(){
                response.status = 'error';
                response.msg = '<?=lang('con.cms02')?>';
            }).always(function(){
                stop_loading();
                if(response.status == 'success'){
                    MessageBox.success(response.msg, null, function(){
                        show_loading();
                        window.location.reload(true);
                    });
                } else {
                    MessageBox.danger(response.msg);
                }
            });
            return false;
        });

        $('.circle').each(function(index, el) {
            var num = $(this).find('span').text() * 3.6;
            if (num <= 180) {
                $(this).find('.right').css('transform', "rotate(" + num + "deg)");
            } else {
                $(this).find('.right').css('transform', "rotate(180deg)");
                $(this).find('.left').css('transform', "rotate(" + (num - 180) + "deg)");
            }
        });

        $('[data-toggle="tooltip"]').tooltip();

        <?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences'))) :?>
            /** initialize player preference */
            $("[class^=pref-data-]").bootstrapSwitch();
            PlayerPreferences.initPlayerPreference();
        <?php endif;?>


        <?php if( empty($result4fromLine['success']) ): ?>
            <?php if(!  empty($enable_OGP19808) ): ?>
            MessageBox.info("<?=$result4fromLine['message']?>", '<?=lang('lang.info')?>', function(){

                },
                [
                    {
                        'text': '<?=lang('lang.close')?>',
                        'attr':{
                            'class':'btn btn-info',
                            'data-dismiss':"modal"
                        }
                    }
                ]);
            <?php endif; // EOF if(!  empty($enable_OGP19808) ): ?>
        <?php endif; ?>

    });


    function send_verification_code() {
        $('#sms_verification_msg').text('<?= lang("Please wait")?>');
        $(".msg-container").show().delay(2000).fadeOut();
        var smsValidBtn  = $('#send_sms_verification'),
            smstextBtn   = smsValidBtn.text(),
            mobileNumber = $('#contact_number').val(),
            dialing_code = $('#dialing_code').val();

        if(AccountInformation.allow_first_number_zero && mobileNumber.charAt(0) == '0') {
            var subMobileNumber = $('input#contact_number').val().substring(1);
            mobileNumber = subMobileNumber;
        }

        if(!mobileNumber || mobileNumber == '') {
            $('#sms_verification_msg').text('<?= lang("Please fill in mobile number")?>');
            $(".msg-container").show().delay(5000).fadeOut();
            $('#contactNumber').focus();
            return;
        }

        SMS_SendVerify(function(sms_captcha_val) {
            var verificationUrl = "<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/" + mobileNumber;
            var enable_new_sms_setting = '<?= !empty($this->utils->getConfig('use_new_sms_api_setting')) ? true : false ?>';
            var sms_cooldown_time = '<?=$this->utils->getConfig('sms_cooldown_time')?>';
            if (enable_new_sms_setting) {
                verificationUrl = '<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/' + mobileNumber + '/sms_api_accountinfo_setting';
            }

            if(sms_cooldown_time.length === 0){
                var smsCountdownnSec = 60;
            }else{
                var smsCountdownnSec = sms_cooldown_time;
            }

            var smsSendSuccess = function() {
                    $('#sms_verification_msg').text('<?= lang("SMS sent")?>');
                },
                smsSendFail = function(data=null) {
                    if (data && data.hasOwnProperty('isDisplay') && data['message']) {
                        $('#sms_verification_msg').text(data['message']);
                    } else {
                        $('#sms_verification_msg').text('<?= lang("SMS failed")?>');
                    }
                },
                smsCountDown = function() {
                        countdown = setInterval(function(){
                            smsValidBtn.text(smstextBtn + "(" + smsCountdownnSec-- + ")");
                            if(smsCountdownnSec < 0){
                                clearInterval(countdown);
                                smsValidBtn.text(smstextBtn);
                                disableSendBtn(false);
                            }
                        },1000);
                },
                disableSendBtn = function (bool) {
                    if (bool) {
                        smsValidBtn.prop('disabled', true);
                        smsValidBtn.removeClass('btn-success');
                    } else {
                        smsValidBtn.prop('disabled', false);
                        smsValidBtn.addClass('btn-success');
                    }
                };

            disableSendBtn(true);
            $.post(verificationUrl, {
                sms_captcha: sms_captcha_val,
                dialing_code: dialing_code
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
    }
</script>
