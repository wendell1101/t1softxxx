<?php
    $birthday_MaxDate = date('Y-m-d', now() - (86400 * 365 * $this->utils->getConfig('legal_age')));
    $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
    if( ! isset($result4fromLine) ){
        $result4fromLine = null;
    }
?>

<?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences'))) :?>
    <link rel="stylesheet" type="text/css" href="<?=$this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css')?>" />
    <script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js');?>"></script>
<?php endif;?>

<div class="member">
    <?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences')) && $registration_fields['Player Preference']['account_visible'] == Registration_setting::VISIBLE) :?>
        <ul class="row fm-ul profile-tab" role="tablist">
            <li class="col-xs-6 col-sm-6 active">
                <a data-toggle="tab" href="#basicInfoTab" id="basic_info_btn"  aria-expanded="false"><?php echo lang("Basic Information")?></a>
            </li>
            <li class="col-xs-6 col-sm-6">
                <a data-toggle="tab" href="#playerPrefTab" id="player_pref_btn"  aria-expanded="false"><?php echo lang("pi.player_pref")?></a>
            </li>
        </ul>
    <?php endif;?>

    <div class="tab-content">
        <!-- BASIC INFORMATION SECTION START -->
        <div id="basicInfoTab" class="tab-pane fade in active">
            <div class="row">
                <form class="col-md-12" id="frmEditPlayer" action="<?php echo site_url('/player_center/postEditPlayer'); ?>" method="post" enctype="multipart/form-data" novalidate>
                    <div class="form-group col-md-6 col-sm-6 info-left">
                        <div class="username_info">
                            <p><?=lang('sys.item1')?></p>
                            <input type="text" name="username" readonly="<?=$player['username']?>" class="form-control" value="<?=$player['username_on_register']?>">
                        </div>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('First Name')): ?>
                            <div class="firstname_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('First Name')?>
                                    <?=lang("First Name") ?>
                                    <?= $this->player_functions->checkAccount_displayInputHint('firstName')?>
                                </p>
                                <input type="text" class="form-control" id="name" name="name"
                                placeholder="<?=lang('Your real name'). ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('First Name')?>"
                                value="<?= !empty($player['firstName']) ? ucfirst($player['firstName']) : '' ?>"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'firstName') ? '' : 'disabled' ; ?>
                                >
                                <?= form_error('name', '<p class="form__error">', '</p>'); ?>
                                <?php if (lang('notify.108')) : ?>
                                    <?php $noteOffirstInto = explode('，', lang('notify.108')); ?>
                                    <span><?= $noteOffirstInto[0] ?></span>
                                <?php endif;?>
                            </div>
                        <?php endif;?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Last Name')): ?>
                            <div class="lastname_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Last Name')?>
                                    <?=lang("Last Name") ?>

                                </p>

                                <input type="text" class="form-control" id="lname" name="lastname"
                                placeholder="<?=lang('player.05') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Last Name')?>"
                                value="<?= !empty($player['lastName']) ? ucfirst($player['lastName']) : '' ?>"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'lastName') ? '' : 'disabled' ; ?>
                                >
                            </div>
                            <?= form_error('lastName', '<p class="form__error">', '</p>'); ?>
                        <?php endif;?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Gender')): ?>
                            <div class="gender_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Gender')?>
                                    <?=lang("Gender") ?>
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

                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Birthday')): ?>
                            <div class="birthday_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Birthday')?>
                                    <?=lang("Birthday") ?>
                                </p>

                                <?php if ($this->operatorglobalsettings->getSettingIntValue('birthday_option') == 2 || !empty($player['birthdate'])) : ?>
                                    <input id="birthdate" value="<?=$player['birthdate']?>" name="birthdate" placeholder="选择出生年月日" type="date" class="input_01 datetimepicker-date" data-maxDate="<?=$birthday_MaxDate?>"

                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'birthdate') ? '' : 'disabled' ; ?>

                                    >
                                <?php else : ?>
                                    <input type="hidden" id="birthdate" value="<?=$player['birthdate']?>" name="birthdate" placeholder="选择出生年月日" type="date">
                                    <?php
                                        $now = date('Y');
                                        $cutoff = $now - 100;
                                        $legal_age = $this->config->item('legal_age');
                                    ?>
                                    <div class="col-md-4 birthday-option">
                                        <div class="custom-dropdown">
                                            <select class="selectbox form-control registration-field" id="year" name="year" onchange="validateDOB()">
                                                <option value=""><?= lang('reg.14') ?></option>
                                                <?php for($y = ($now - $legal_age); $y >= $cutoff; $y--): ?>
                                                    <option value="<?=$y?>"><?=$y?></option>
                                                <?php endfor?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 lr-padding birthday-option">
                                        <div class="custom-dropdown ">
                                            <select class="selectbox form-control registration-field" id="month" name="month" onchange="validateDOB()">
                                                <option value=""><?= lang('reg.59') ?></option>
                                                <?php for($m = 1; $m <= 12; $m++): ?>
                                                    <option value="<?=sprintf("%02d",$m)?>"><?=sprintf("%02d",$m)?></option>
                                                <?php endfor?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 lr-padding birthday-option">
                                        <div class="custom-dropdown ">
                                            <select class="selectbox form-control registration-field" id="day" name="day" onchange="validateDOB()">
                                                <option value=""><?= lang('reg.13') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endif;?>
                                <?= form_error('birthdate', '<p class="form__error">', '</p>'); ?>
                                <div class="clearfix"></div>


                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('BirthPlace')): ?>
                            <div class="birthplace_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('BirthPlace')?>
                                    <?=lang('reg.24')?>

                                </p>

                                <input type="text" class="form-control" id="birthplace" name="birthplace"
                                value="<?= !empty($player['birthplace']) ? ucfirst($player['birthplace']) : "" ?>"
                                placeholder="<?=lang('reg.25') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('BirthPlace')?>"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'birthplace') ? '' : 'disabled' ; ?>
                                >
                            </div>
                             <?= form_error('birthplace', '<p class="form__error">', '</p>'); ?>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Nationality')): ?>
                            <div class="citizenship_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Nationality')?>
                                    <?=lang("player.61") ?>

                                </p>

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
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Language')): ?>
                            <div class="language_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Language')?>
                                    <?=lang('player.62')?>

                                    </p>

                                <select class="form-control" name="language" <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'language') ? '' : 'disabled' ; ?>>
                                    <?php foreach(Language_function::PlayerSupportLanguageNames() as $lang_key => $lang_value): ?>
                                        <option value="<?=$lang_key?>" <?=(strtolower($player['language']) == strtolower($lang_key)) ? "selected":''?>><?php echo $lang_value;?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <?= form_error('language', '<p class="form__error">', '</p>'); ?>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Email')): ?>
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

                            <div class="email_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Email')?>
                                    <?=lang("Email") ?>
                                </p>
                                <input type="email" name="email" class="form-control" id="fm-cn"
                                       value="<?= $_email ?>"
                                       placeholder="<?=lang('lang.email') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Email')?>"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'email', $_email_disable_edit) ? '' : 'disabled' ; ?>
                                />
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Dialing Code')): ?>
                            <div class="dialing_code_info">
                                <?php
                                $countryNumList = unserialize(COUNTRY_NUMBER_LIST_FULL);
                                $requireDialingCode = ($registration_fields['Dialing Code']['account_required'] == Registration_setting::REQUIRED) ? 1 : 0;
                                ?>
                                <p class="accountinfo field">
                                    <?= $this->player_functions->checkAccount_displaySymbolHint($registration_fields['Dialing Code']['field_name'])?>
                                    <?=lang("Dialing Code") ?>:
                                </p>
                                <select id="dialing_code" class="form-control <?=($requireDialingCode) ? 'required' : ''?>" name="dialing_code"
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
                             </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Contact Number')): ?>
                            <div class="contact_number_info">
                                <?php
                                $obfuscatedPhone = '';
                                if (!empty($player['contactNumber']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_phone')) {
                                    $obfuscatedPhone = $this->utils->keepOnlyString($player['contactNumber'], -4);
                                }
                                $_contact_number = !empty($player['contactNumber']) ? (($obfuscatedPhone) ? $obfuscatedPhone : $player['contactNumber']) : lang('not_filled_fill_now');
                                $_contact_number_disable_edit = ($_contact_number && ($this->utils->isEnabledFeature('enabled_show_player_obfuscated_phone') || $player['verified_phone']));
                                $_contact_allow_edit   = $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'contactNumber', $_contact_number_disable_edit);
                                ?>
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Contact Number')?>
                                    <?=lang("Contact No.") ?>
                                </p>
                                <input name="contact_number" id="contact_number" type="text" class="form-control" min="11" max="11"
                                value="<?=$player['contactNumber']?>"
                                placeholder="<?=lang('reg.30')?>"
                                onkeyup="this.value=this.value.replace(/[^(0-9)]/g,'');"
                                <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'contactNumber', $_contact_number_disable_edit) ? '' : 'disabled' ; ?>
                                />
                            </div>
                        <?php endif ?>

                        <?php if ($this->utils->getConfig('enable_verify_phone_number_in_account_information_of_player_center') && !$player['verified_phone']): ?>
                            <div class="verification_number_info">
                                <p>
                                    <?=lang('Send SMS')?>
                                </p>
                                <div class="login_btn" onclick="send_verification_code()">
                                    <?=lang('Send SMS')?>
                                </div>
                                <div class="fcmonu-note mb20 msg-container" style="display:none">
                                    <p class="pl15">
                                        <i class="icon-warning red f16 mr5"></i>
                                        <span id="sms_verification_msg"></span>
                                    </p>
                                </div>
                                <div class="inputtwo sms-code-wrapper">
                                    <input type="text" class="form-control" id="verity_code" name="sms_verification_code" placeholder="<?=lang('SMS Code')?>"/>
                                    <ul class="tsvc notify">
                                        <i></i>
                                        <a><?=lang('SMS Code')?></a>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>


                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Instant Message 1')): ?>
                            <div class="instant_message_1_info">
                                <?php
                                $obfuscatedIm = '';
                                if (!empty($player['imAccount']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                    $obfuscatedIm = $this->utils->keepOnlyString($player['imAccount'], -4);
                                }
                                $_im = !empty($player['imAccount']) ? (($obfuscatedIm) ? $obfuscatedIm : $player['imAccount']) : '';
                                $_im_disable_edit = ($_im && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                $im1 = $this->config->item('Instant Message 1', 'cust_non_lang_translation');
                                ?>
                                <p class="accountinfo field">
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 1')?>
                                    <?= ($im1) ? $im1 : lang('Instant Message 1')?>:
                                </p>

                                <input type="text" name="im_account" id="im_account" class="form-control input-sm" value="<?= $_im ?>"
                                       placeholder="<?=(($im1) ? $im1 : lang('Instant Message 1')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 1')?>"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount', $_im_disable_edit) ? '' : 'disabled' ; ?>
                                >

                            </div>
                                <?= form_error('im_account', '<p class="form__error">', '</p>'); ?>

                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Instant Message 2')): ?>
                            <div class="instant_message_2_info">
                                <?php
                                $obfuscatedIm2 = '';
                                if (!empty($player['imAccount2']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                    $obfuscatedIm2 = $this->utils->keepOnlyString($player['imAccount2'], -4);
                                }
                                $_im2 = !empty($player['imAccount2']) ? (($obfuscatedIm2) ? $obfuscatedIm2 : $player['imAccount2']) : '';
                                $_im2_disable_edit = ($_im2 && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                $im2 = $this->config->item('Instant Message 2', 'cust_non_lang_translation');
                                ?>
                                <p class="accountinfo field">
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 2')?>
                                    <?=($im2) ? $im2 : lang('Instant Message 2')?>:
                                </p>
                                <input type="text" name="im_account2" id="im_account2" class="form-control input-sm" value="<?= $_im2 ?>"
                                       placeholder="<?=(($im2) ? $im2 : lang('Instant Message 2')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 2')?>"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount2', $_im2_disable_edit) ? '' : 'disabled' ; ?>
                                />

                                <?= form_error('im_account2', '<p class="form__error">', '</p>'); ?>
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Instant Message 3')): ?>
                            <div class="instant_message_3_info">
                                <?php
                                $obfuscatedIm3 = '';
                                if (!empty($player['imAccount3']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                    $obfuscatedIm3 = $this->utils->keepOnlyString($player['imAccount3'], -4);
                                }
                                $_im3 = !empty($player['imAccount3']) ? (($obfuscatedIm3) ? $obfuscatedIm3 : $player['imAccount3']) : '';
                                $_im3_disable_edit = ($_im3 && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                $im3 = $this->config->item('Instant Message 3', 'cust_non_lang_translation');
                                ?>
                                <p class="accountinfo field">
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 3')?>
                                    <?=($im3) ? $im3 : lang('Instant Message 3')?>:
                                </p>
                                <input type="text" name="im_account3" id="im_account3" class="form-control input-sm" value="<?= $_im3 ?>"
                                       placeholder="<?=(($im3) ? $im3 : lang('Instant Message 3')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 3')?>"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount3', $_im3_disable_edit) ? '' : 'disabled' ; ?>
                                >

                                <?= form_error('im_account3', '<p class="form__error">', '</p>'); ?>
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Instant Message 4')): ?>
                            <div class="instant_message_4_info">
                                <?php
                                $obfuscatedIm4 = '';
                                if (!empty($player['imAccount4']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                    $obfuscatedIm4 = $this->utils->keepOnlyString($player['imAccount4'], -4);
                                }
                                $_im4 = !empty($player['imAccount4']) ? (($obfuscatedIm4) ? $obfuscatedIm4 : $player['imAccount4']) : '';
                                $_im4_disable_edit = ($_im4 && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                $im4 = $this->config->item('Instant Message 4', 'cust_non_lang_translation');
                                ?>
                                <p class="accountinfo field">
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 4')?>
                                    <?=($im4) ? $im4 : lang('Instant Message 4')?>:
                                </p>
                                <input type="text" name="im_account4" id="im_account4" class="form-control input-sm" value="<?= $_im4 ?>"
                                       placeholder="<?=(($im4) ? $im4 : lang('Instant Message 4')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 4')?>"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount4', $_im4_disable_edit) ? '' : 'disabled' ; ?>
                                >

                                <?= form_error('im_account4', '<p class="form__error">', '</p>'); ?>
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Instant Message 5')): ?>
                            <div class="instant_message_5_info">
                                <?php
                                $obfuscatedIm5 = '';
                                if (!empty($player['imAccount5']) && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im')) {
                                    $obfuscatedIm5 = $this->utils->keepOnlyString($player['imAccount5'], -4);
                                }
                                $_im5 = !empty($player['imAccount5']) ? (($obfuscatedIm5) ? $obfuscatedIm5 : $player['imAccount5']) : '';
                                $_im5_disable_edit = ($_im5 && $this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
                                $im5 = $this->config->item('Instant Message 5', 'cust_non_lang_translation');
                                ?>
                                <p class="accountinfo field">
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Instant Message 5')?>
                                    <?=($im5) ? $im5 : lang('Instant Message 5')?>:
                                </p>
                                <input type="text" name="im_account5" id="im_account5" class="form-control input-sm" value="<?= $_im5 ?>"
                                       placeholder="<?=(($im5) ? $im5 : lang('Instant Message 5')) . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Instant Message 5')?>"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'imAccount5', $_im5_disable_edit) ? '' : 'disabled' ; ?>
                                >

                                <?= form_error('im_account5', '<p class="form__error">', '</p>'); ?>
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Zip Code')): ?>
                            <div class="zipcode_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Zip Code')?>
                                    <?=lang('a_reg.48')?>

                                </p>
                                <input type="text" name="zipcode" id="zipcode" class="form-control letters_only"
                                       value="<?= !empty($player['zipcode']) ? $player['zipcode'] : '' ?>"
                                       placeholder="<?= lang("a_reg.48") . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Zip Code')?>"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'zipcode') ? '' : 'disabled' ; ?>
                                >
                                <?= form_error('zipcode', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                            </div>
                        <?php endif ?>




                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Resident Country')): ?>
                            <div class="residen_country_info">
                                <p>
                                    <?= $this->player_functions->checkAccount_displaySymbolHint('Resident Country')?>
                                    <?=lang('reg.a42')?>

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
                            </div>
                        <?php endif ?>

                        <?php $address_keys = ['REGION','CITY','ADDRESS','ADDRESS2'];?>
                        <?php if(array_intersect($address_keys, $fields)): ?>
                            <p class="accountinfo field">
                                <?=lang('player.59');?>:
                            </p>
                        <?php endif; ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Region')): ?>
                            <div class="region_info">
                                <?php $placeholder = ($full_address_in_one_row) ? lang('Please input your full address') : lang('a_reg.37.placeholder');?>
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Region')?>

                                <input type="text" name="region" id="region" class="form-control" data-toggle="popover"
                                       value="<?= !empty($player['region']) ? $player['region'] : '' ?>"
                                       placeholder="<?=$placeholder;?> <?= $this->player_functions->checkAccount_displayPlaceholderHint('Region')?>"
                                       maxlength="120"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'region') ? '' : 'disabled' ; ?>
                                >
                                <?= form_error('region', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('City')): ?>
                            <div class="city_info">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('City')?>
                                <input type="text" name="city" id="city" class="form-control letters_only"
                                       value="<?= !empty($player['city']) ? $player['city'] : '' ?>"
                                       placeholder="<?= lang("a_reg.36.placeholder") . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('City')?>"
                                       maxlength="120"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'city') ? '' : 'disabled' ; ?>
                                >
                                <?= form_error('city', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Address')): ?>
                            <div class="address_info">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Address')?>
                                <input type="text" name="address" id="address" class="form-control" data-toggle="popover"
                                       value="<?= !empty($player['address']) ? $player['address'] : '' ?>"
                                       placeholder="<?=lang('a_reg.43.placeholder') . ' ' . $this->player_functions->checkAccount_displayPlaceholderHint('Address')?>"
                                       maxlength="120"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'address') ? '' : 'disabled' ; ?>
                                >
                                <?= form_error('address', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('Address2')): ?>
                            <div class="address2_info">
                                <?= $this->player_functions->checkAccount_displaySymbolHint('Address2')?>
                                <input type="text" name="address2" id="address2" class="form-control" data-toggle="popover"
                                       value="<?= !empty($player['address2']) ? $player['address2'] : '' ?>"
                                       placeholder="<?=lang('a_reg.44.placeholder');?> <?= $this->player_functions->checkAccount_displayPlaceholderHint('Address2')?>"
                                       maxlength="120"
                                    <?= $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'address2') ? '' : 'disabled' ; ?>
                                >
                                <?= form_error('address2', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('ID Card Type')): ?>
                            <div class="id_card_number_info">

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
                            </div>
                        <?php endif ?>

                        <?php if ($this->player_functions->checkAccountFieldsIfVisible('ID Card Number')): ?>
                            <div class="id_card_number_info">
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

                            </div>
                        <?php endif ?>
                        <button id="save_btn" type="submit" class="btn form-control"><?=lang("Save") ?></button>
                    </div>
                </form>
            </div>
        </div>
        <!-- BASIC INFORMATION SECTION END -->
        <!-- PLAYER PREFERENCE SECTION START -->
        <div id="playerPrefTab" class="tab-pane fade">
            <div class="col-xs-11 col-md-12">
                <br>
                <p><?=sprintf(lang('pi.player_pref.hint1'), lang('pi.player_pref_custom_name'))?></p>
                <br>
                <p><?=lang('pi.player_pref.hint2')?></p>
                <br><br>
                <table class="table table-sortable" style="border:1px #eee solid;">
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
                                <td><input type="checkbox" name="pref-data-<?=$key?>" class="pref-data-<?=$key?>" value="<?=$key?>" <?=$isChecked?> /></td>
                            </tr>
                        <?php endforeach ?>
                    </div>
                </table>
            </div>
        </div>
        <!-- PLAYER PREFERENCE SECTION END -->
    </div>
</div>

<?php if($this->utils->isEnabledFeature('enable_mobile_copyright_footer')): ?>
    <?=$this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/mobile/includes/template_footer');?>
<?php endif; ?>


<script type="text/javascript" src="<?=base_url()?>resources/js/jquery.mask.min.js"></script>
<script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/account-information.js') ?>"></script>
<?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences'))) :?>
    <script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/player-preferences.js') ?>"></script>
<?php endif;?>
<?= $this->CI->load->widget('sms'); ?>
<script type="text/javascript">
    // var profile_field_req = <?= json_encode($profile_field_req) ?>;
    var allow_first_number_zero = <?= !empty($this->utils->getConfig('allow_first_number_zero')) ? 'true' : 'false' ?>;
    $(document).ready(function () {

        $('#birthdate').mask("9999-99-99", {
            placeholder: 'YYYY-MM-DD'
        });

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

        var birthInit =  function (options) {

            var date = new Date(),
    			DateY = date.getFullYear(),
    			DateM = date.getMonth(),
    			DateD = date.getDate(),
                minDOBDate = new Date(DateY - <?= $this->utils->getConfig('legal_age') ?>, DateM, DateD);

            var defaults = {
                    yearSelector:  "#year",
                    monthSelector: "#month",
                    daySelector:   "#day"
                },
                opts = $.extend({}, defaults, options),
                $yearSelector  = $(opts.yearSelector),
                $monthSelector = $(opts.monthSelector),
                $daySelector   = $(opts.daySelector),
                $dayDefaultOption = $daySelector.find("option:first").clone()
                $monthDefaultOption = $monthSelector.find("option:first").clone(),
                changeDay = function () {
    				var _year = $yearSelector.val(),
    					_month = $monthSelector.val();

    				$daySelector.html($dayDefaultOption);

    				if (_year == "" || _month == "") {
    					return;
    				}
    				var _curentDayOfMonth = new Date(_year, _month, 0).getDate(),
    					_isSameYear  = (Number(_year) == minDOBDate.getFullYear()),
    					_isSameMonth = (Number(_month) == (minDOBDate.getMonth() + 1)),
    					_isLessMonth = (Number(_month) > (minDOBDate.getMonth() + 1)),
    					_minDay 	 = minDOBDate.getDate();

    				for (var i = 1; i <= _curentDayOfMonth; i++) {
    					var dV = i.toString(),
    						d = (dV.length > 1) ? dV : "0" + dV ;
    					if ((_isSameYear && _isSameMonth && i > _minDay) || (_isSameYear && _isLessMonth)) {
    						$daySelector.append($("<option>").attr('disabled', 'disabled').val(d).text(d));
    					} else {
    						$daySelector.append($("<option>").val(d).text(d));
    					}
    				}
    			},changeMonth = function () {
    				var _year = $yearSelector.val(),
    					_month = $monthSelector.val();

    				$daySelector.html($dayDefaultOption);
    				$monthSelector.html($monthDefaultOption);

    				var _isSameYear = (Number(_year) == minDOBDate.getFullYear()),
    					_minMonth   = minDOBDate.getMonth() + 1;

    				for (var i = 1; i <= 12; i++) {
    					var mV = i.toString(),
    						m = (mV.length > 1) ? mV : "0" + mV;
    					if ((_isSameYear && i > _minMonth)) {
    						$monthSelector.append($("<option>").attr('disabled', 'disabled').val(m).text(m));
    					} else {
    						if (Number(_month) == i) {
    							$monthSelector.append($("<option>").attr('selected', 'selected').val(m).text(m));
    						} else {
    							$monthSelector.append($("<option>").val(m).text(m));
    						}
    					}
    				}
    			};

            $monthSelector.change(changeDay);
            $yearSelector.change(changeDay);
    		$yearSelector.change(changeMonth);
        }

        birthInit();

        <?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences'))) :?>
            /** initialize player preference */
            $("[class^=pref-data-]").bootstrapSwitch();
            PlayerPreferences.initPlayerPreference();
        <?php endif;?>

        <?php if( empty($result4fromLine['success']) ): ?>
            <?php if( ! empty($enable_OGP19808) ): ?>
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
            <?php endif; // EOF if( ! empty($enable_OGP19808) ):?>
        <?php endif; ?>

    });

    function validateDOB() {
        var cdv = $("#day").val(),
            cmv = $("#month").val(),
            cyv = $("#year").val(),
            DOB = cyv + '-' + cmv + '-' + cdv;

        if (cdv != "" && cmv != "" && cyv != "") {
            $("#birthdate").val(DOB);
        } else {
            $("#birthdate").val("");
        }
    }

    function send_verification_code() {
        $('#sms_verification_msg').text('<?= lang("Please wait")?>');
        $(".msg-container").show().delay(2000).fadeOut();
        var smsValidBtn = $('#send_sms_verification'),
            smstextBtn  = smsValidBtn.text(),
            mobileNumber = $('#contact_number').val(),
            dialing_code = $('#dialing_code').val();

        if(allow_first_number_zero && mobileNumber.charAt(0) == '0') {
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
                    var smsCountdownnSec = 60,
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
            var verificationUrl = "<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/" + mobileNumber;
            var enable_new_sms_setting = '<?= !empty($this->utils->getConfig('use_new_sms_api_setting')) ? true : false ?>';

            if (enable_new_sms_setting) {
                verificationUrl = '<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/' + mobileNumber + '/sms_api_accountinfo_setting';
            }

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
<style>
.sms-code-wrapper {
    height:auto;
}
</style>