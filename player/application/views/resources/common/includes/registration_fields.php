<?php
$feature_show_player_upload_realname_verification = $this->utils->isEnabledFeature('show_player_upload_realname_verification');

$address_title = FALSE;
foreach($registration_fields as $registration_field){
    if ($registration_field['registrationFieldId'] == 49 && !$feature_show_player_upload_realname_verification) {
        continue;
    }
    if (in_array($registration_field['registrationFieldId'], [54,55,56,57,58])) {
        continue;
    }
    if($registration_field['visible'] == Registration_setting::VISIBLE){
        $required_class = ($registration_field['required'] == Registration_setting::REQUIRED) ? 'field_required' : '';
?>
        <?php switch($registration_field['registrationFieldId']){
            case 3: # BIRTHDAY ?>
            <?php
            // lowest year wanted
            $cutoff = 1900;
            // current year
            $now = date('Y');

            $birth = set_value($registration_field['alias']);
            if(isset($birth) && !empty($birth)){
                $_dateList = explode("-", $birth);
                $_year = $_dateList[0];
                $_month = $_dateList[1];
                $_day = $_dateList[2];
                $_dayOfMonth = date("t", strtotime("$_year-$_month-01"));
            }
            ?>
            <div class="col-md-6 col-lg-4">
            <div  id="age-limit" ></div>
                <div class="form-group form-inline relative birthday-option">
                    <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>

                    <input type="hidden" class="registration-field" name="<?=$registration_field['alias']?>" value="<?=set_value($registration_field['alias'])?>">
                    <div class="row">
                        <div class="col-md-4">
                            <span class="custom-label"><?=lang($registration_field['field_name'])?><?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?></span>
                        </div>
                        <div class="col-md-3" id="year_group">
                            <div class="custom-dropdown">
                                <select class="selectbox form-control registration-field" id="year" name="year" onchange="Register.validateDOB()" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>>
                                    <option value=""><?=lang('reg.14')?></option>
                                    <?php  for($y = ($now - $age_limit_num); $y >= $cutoff; $y--): ?>
                                        <option value="<?=$y?>" <?php echo (isset($_year) && ($_year == $y)) ? "selected" : ""; ?>><?=$y?></option>
                                    <?php endfor ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 lr-padding" id="month_group">
                            <div class="custom-dropdown">
                                <select class="selectbox form-control registration-field" id="month" name="month" onchange="Register.validateDOB()" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>>
                                    <option value=""><?=lang('reg.59')?></option>
                                    <?php for($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?=sprintf("%02d", $m)?>" <?php echo (isset($_month) && ($_month == $m)) ? "selected" : ""; ?>><?=sprintf("%02d", $m)?></option>
                                    <?php endfor ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 lr-padding" id="day_group">
                            <div class="custom-dropdown">
                                <select class="selectbox form-control registration-field" id="day" name="day" onchange="Register.validateDOB()" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>>
                                    <option value=""><?=lang('reg.13')?></option>
                                    <?php if(isset($_dayOfMonth)) : ?>
                                        <?php for($d = 1; $d <= $_dayOfMonth; $d++): ?>
                                            <option value="<?=sprintf("%02d", $d)?>" <?php echo (isset($_day) && ($_day == $d)) ? "selected" : ""; ?>><?=sprintf("%02d", $d)?></option>
                                        <?php endfor; ?>
                                    <?php else : ?>
                                        <?php for($d = 1; $d <= 31; $d++): ?>
                                            <option value="<?=sprintf("%02d", $d)?>"><?=sprintf("%02d", $d)?></option>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($registration_field['required'] == Registration_setting::REQUIRED or $registration_fields['At Least 18 Yrs. Old and Accept Terms and Conditions']['visible'] == Registration_setting::VISIBLE):?>
                    <div class="registration-field-note hide mb20">
                        <p class="pl15 mb0">
                            <i class="icon-warning red f16 mr5" id="val_dob"></i>
                            <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                        </p>

                        <p class="pl15 mb0">
                            <i class="icon-warning red f16 mr5" id="val_dob18"></i>
                            <?=sprintf($this->utils->renderLang('mod.mustbeAboveLimitAge', ["$age_limit"]), lang($registration_field['field_name']))?>

                        </p>
                        <?php if(!empty(lang('mod.mustbefillrealbirthday'))):?>
                            <p class="pl15 mb0">
                                <i class="icon-warning red f16 mr5" id="otherBirthdayMessage"></i>
                                <?=lang('mod.mustbefillrealbirthday')?>
                            </p>
                        <?php endif ?>
                    </div>

                <?php endif ?>
            </div>
            <?php break; ?>

        <?php case 1: # FIRSTNAME 
        $first_name_vaild_prompt=$this->utils->getConfig('player_registration_first_name_vaild_prompt');
        ?>
                <?php if($first_name_vaild_prompt):?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <input type="text" class="form-control registration-field" name="<?=$registration_field['alias']?>" data-rule="<?= htmlentities(json_encode($firstNameRule)); ?>" placeholder="<?=lang('a_reg.1')?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>"
                                <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'])?>" onfocus="return Register.validateFirstName(this, this.getAttribute('data-rule'))" oninput="return Register.validateFirstName(this, this.getAttribute('data-rule'))"/>
                        </div>
                        <div class="registration-field-note hide mb20">
                            <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=lang('Please Fill In Your Name')?>
                                </p>
                            <?php endif ?>
                            <p class="pl15 mb0">
                                <i id="firstNameRegex" class="icon-warning red f16 mr5"></i>
                                <?=lang('Name Cannot Enter Numbers and Special Characters')?>
                            </p>
                            <?php if($this->utils->isEnabledFeature('enabled_player_registration_restrict_min_length_on_first_name_field')):?>
                                <p class="pl15">
                                    <i id="firstNameRestrictMinChars" class="icon-warning red f16 mr5"></i>
                                    <?= sprintf(lang('validation.firstNameRestrictMinChars'), $min_first_name_length)?>
                                </p>
                            <?php endif;?>
                        </div>
                    </div>
                    <?php if (lang('notify.108')) : ?>
                            <div class="noteOffirstInto">
                                    <?php $noteOffirstInto = explode('，', lang('notify.108')); ?>
                                    <span><?= $noteOffirstInto[0] ?></span>
                            </div>
                        <?php endif;?>
                    <?php break; ?>
                <?php else : ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <input type="text" class="form-control registration-field" name="<?=$registration_field['alias']?>" data-rule="<?= htmlentities(json_encode($firstNameRule)); ?>" placeholder="<?=lang('a_reg.1')?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>"
                                <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'])?>" onfocus="return Register.validateFirstName(this, this.getAttribute('data-rule'))" oninput="return Register.validateFirstName(this, this.getAttribute('data-rule'))"/>
                        </div>
                        <?php if (lang('notify.108')) : ?>
                                <div class="noteOffirstInto">
                                        <?php $noteOffirstInto = explode('，', lang('notify.108')); ?>
                                        <span><?= $noteOffirstInto[0] ?></span>
                                </div>
                            <?php endif;?>
                        <div class="registration-field-note hide mb20">
                            <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=lang('validation.realname')?>
                                </p>
                            <?php endif ?>
                            <p class="pl15 mb0">
                                <i id="firstNameRegex" class="icon-warning red f16 mr5"></i>
                                <?=lang('validation.firstNameRegex')?>
                            </p>
                            <?php if($this->utils->isEnabledFeature('enabled_player_registration_restrict_min_length_on_first_name_field')):?>
                                <p class="pl15">
                                    <i id="firstNameRestrictMinChars" class="icon-warning red f16 mr5"></i>
                                    <?= sprintf(lang('validation.firstNameRestrictMinChars'), $min_first_name_length)?>
                                </p>
                            <?php endif;?>
                        </div>
                    </div>
                    <?php break; ?>
                <?php endif;?>

                <?php case 2: # LASTNAME ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <input type="text" class="form-control registration-field" name="<?=$registration_field['alias']?>" placeholder="<?=lang('a_reg.' . $registration_field['registrationFieldId'])?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'])?>" onfocus="return Register.validateLastName(this)" oninput="return Register.validateLastName(this)"/>
                        </div>
                        <div class="registration-field-note hide mb20">
                            <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang('a_reg.' . $registration_field['registrationFieldId']))?>
                                </p>
                            <?php endif ?>
                            <p class="pl15">
                                <i id="lastNameRegex" class="icon-warning red f16 mr5"></i>
                                <?=lang('validation.lastNameRegex')?>
                            </p>
                        </div>
                    </div>
                    <?php break; ?>

                <?php case 4: # GENDER ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <div class="custom-dropdown">
                                <select class="form-control registration-field" name="<?=$registration_field['alias']?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>>
                                    <option value=""><?php echo lang('a_reg.' . $registration_field['registrationFieldId']); ?><?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?></option>
                                    <option value="Male" <?=(set_value($registration_field['alias']) == 'Male') ? 'selected' : '';?>><?php echo lang('Male'); ?></option>
                                    <option value="Female" <?=(set_value($registration_field['alias']) == 'Female') ? 'selected' : '';?>><?php echo lang('Female'); ?></option>
                                </select>
                            </div>
                        </div>
                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                </p>
                            </div>
                        <?php endif ?>
                    </div>
                    <?php break; ?>

                <?php case 5: # Nationality ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?> resident_country_option">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <div class="custom-dropdown">
                                <select id="nationality_list" class="form-control registration-field" name="<?=$registration_field['alias']?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> onchange="Register.chosenCountry(this)">
                                    <option value=""><?php echo lang('reg.22'); ?><?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?></option>
                                    <?php if(!$this->utils->isEnabledFeature('disable_frequently_use_country_in_registration')) : ?>
                                        <optgroup label="<?=lang('lang.frequentlyUsed')?>">
                                            <?php foreach($this->utils->getCommonCountryList() as $key){ ?>
                                                <option value="<?=$key?>" <?=(set_value($registration_fields['Nationality']['alias']) == $key) ? "selected" : "";?>><?=lang('country.' . $key)?></option>
                                            <?php } ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <optgroup label="<?=lang('lang.alphabeticalOrder')?>">
                                        <?php foreach($this->utils->getCountryList() as $key){ ?>
                                            <option value="<?=$key?>" <?=(set_value($registration_fields['Nationality']['alias']) == $key) ? "selected" : "";?>><?=lang('country.' . $key)?></option>
                                        <?php } ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note countryField hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                </p>
                            </div>
                        <?php endif ?>
                    </div>
                <?php break; ?>

                <?php case 7: # LANGUAGE ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <div class="custom-dropdown">
                                <select class="form-control registration-field" name="<?=$registration_field['alias']?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>>
                                    <option value=""><?php echo lang('a_reg.' . $registration_field['registrationFieldId']); ?><?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?></option>
                                    <?php foreach(Language_function::PlayerSupportLanguageNames() as $lang_key => $lang_value): ?>
                                        <option value="<?=$lang_key?>" <?=(set_value($registration_field['alias']) == $lang_key) ? 'selected' : '';?>><?php echo $lang_value; ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                </p>
                            </div>
                        <?php endif ?>
                    </div>
                    <?php break; ?>

                <?php case 8: # CONTACT NUMBER ?>
                    <?php
                    $visibleContactNumber = 0;
                    if($registration_field['visible'] == Registration_setting::VISIBLE){
                        $visibleContactNumber = 1;
                    }

                    $requireContactNumber = 0;
                    if($registration_field['required'] == Registration_setting::REQUIRED){
                        $requireContactNumber = 1;
                    }
                    $field_name4DialingCode = 'Dialing Code'; // @todo apply the string,'Dialing Code'.
                    $countryNumList = unserialize(COUNTRY_NUMBER_LIST_FULL);
                    $visibleDialingCode = ($registration_fields['Dialing Code']['visible'] == Registration_setting::VISIBLE) ? 1 : 0;
                    $requireDialingCode = ($registration_fields['Dialing Code']['required'] == Registration_setting::REQUIRED) ? 1 : 0;
                    $getDefaultDialingCode = $this->utils->getConfig('enable_default_dialing_code');
                    ?>
                    <div class="col-md-6 col-lg-4 contact-number relative <?=$required_class?>">
                        <?php if ( !$this->utils->is_mobile() ) {?>
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                        <?php }?>
                        <div class="form-group form-inline relative">
                            <?php if ( $this->utils->is_mobile() ) {?>
                                <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <?php }?>
                            <label class="<?=($visibleDialingCode) ? "bstselect" : "";?>"><i class="fa fa-phone"></i></label>
                            <?php if($visibleDialingCode) : ?>
                                <select id="dialing_code" class="selectpicker registration-field <?php if($requireDialingCode) echo 'required'; ?>" data-width="100px" name="dialing_code" data-requiredfield="dialing-code">
                                    <?php if(!empty($getDefaultDialingCode)) : ?>
                                        <?php foreach($getDefaultDialingCode as $country => $nums) : ?>
                                            <option title="(+<?=$nums?>)" country="<?=$country?>" value="<?=$nums?>" <?=(set_value('dialing_code') == $nums) ? 'selected' : '';?>><?=sprintf("%s (+%s)", lang('country.' . $country), $nums);?></option>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <option title="<?=lang('reg.77')?> <?=$this->registration_setting->displayPlaceholderHint($registration_fields['Dialing Code']["field_name"])?>" country="" value=""><?=lang('reg.77')?><?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?></option>
                                    <?php endif; ?>

                                    <?php if(!empty($getDefaultDialingCode) && $this->utils->getConfig('only_show_default_dialing_code')) : ?>
                                    <?php else : ?>
                                        <?php foreach($countryNumList as $country => $nums) : ?>
                                            <?php if(is_array($nums)) : ?>
                                                <?php foreach($nums as $_nums) : ?>
                                                    <option title="(+<?=$_nums?>)" country="<?=$country?>" value="<?=$_nums?>" <?=(set_value('dialing_code') == $_nums) ? 'selected' : '';?>><?=sprintf("%s (+%s)", lang('country.' . $country), $_nums);?></option>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <option title="(+<?=$nums?>)" country="<?=$country?>" value="<?=$nums?>" <?=(set_value('dialing_code') == $nums) ? 'selected' : '';?>><?=sprintf("%s (+%s)", lang('country.' . $country), $nums);?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            <?php endif; ?>
                            <input type="text" class="form-control registration-field fcmonu <?=($visibleDialingCode) ? "bstselect" : "";?>" name="<?=$registration_field['alias']?>"
                                <?php if($contactRule) : ?> data-rule="<?=htmlentities(json_encode($contactRule));?>" maxlength="<?php echo $contactRule['max'] ? $contactRule['max'] : 11; ?>" <?php endif; ?>
                                id="<?=$registration_field['alias']?>" onkeyup="return Register.validateContactNumber(this.value, this.getAttribute('data-rule'))"
                                placeholder="<?=lang($registration_field['field_name'])?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>"
                                <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>
                                value="<?=set_value($registration_field['alias'])?>"
                                data-requiredfield="contact-number">
                                <?php if($this->utils->getConfig('enable_contact_number_custom_display')) : ?>
                                <p class="contact_number_notif"></p>
                                <?php endif; ?>
                        </div>
                        <div class="fcmonu-note registration-field-note hide mb20">
                            <p class="pl15 mb0">
                                <?php if ((!$this->utils->getConfig('disable_validate_contact_number_display') && $this->utils->getCurrentLanguageCode() == 'th') || $this->utils->getCurrentLanguageCode() != 'th') : ?>
                                    <i id="mobile_format" class="icon-warning red f16 mr5"></i>
                                    <span class="validate-mesg format"><?=lang('validation.validateContactNumber')?></span>
                                <?php endif; ?>
                                <span class="validate-mesg in-use" style="display: none;"><?=lang('The number is in use')?></span>
                            </p>
                            <?php if ($this->registration_setting->isRegistrationFieldVisible('Dialing Code') && !$this->CI->config->item('allow_first_number_zero')) : ?>
                                <p class="pl15 mb0">
                                    <i id="remove_leading_zero" class="icon-warning red f16 mr5"></i>
                                    <?= lang('validation.contactNumber_remove_leading_zero') ?>
                                </p>
                            <?php endif; ?>
                            <?php if($contactRule) : ?>
                                <?php if($this->utils->is_mobile() && isset($registration_template_for_mobile_reference) && $registration_template_for_mobile_reference == 4): ?>
                                    <?php if(isset($contactRule['min']) && isset($contactRule['max']) && ($contactRule['min'] != @$contactRule['max'])) : ?>
                                        <p class="pl15 mb0">
                                            <i id="contact_len_between" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthStandard'), $contactRule['min'] . '-' . $contactRule['max']); ?>
                                        </p>
                                    <?php else: ?>
                                        <?php if(isset($contactRule['min']) && $contactRule['min'] != @$contactRule['max']) : ?>
                                            <p class="pl15 mb0">
                                                <i id="contact_len_min" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthTooShortStart'), $contactRule['min']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if(isset($contactRule['max']) && $contactRule['max'] != @$contactRule['min']) : ?>
                                            <p class="pl15 mb0">
                                                <i id="contact_len_max" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthTooLongStart'), $contactRule['max']); ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if(isset($contactRule['min'], $contactRule['max']) && $contactRule['min'] == $contactRule['max']) : ?>
                                        <p class="pl15 mb0">
                                            <i id="contact_len_same" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthStandard'), $contactRule['min']); ?>
                                        </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if(isset($contactRule['min']) && isset($contactRule['max']) && ($contactRule['min'] != @$contactRule['max'])) : ?>
                                        <p class="pl15 mb0">
                                            <i id="contact_len_between" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthStandard'), $contactRule['min'] . '-' . $contactRule['max']); ?>
                                        </p>
                                    <?php else: ?>
                                        <?php if(isset($contactRule['min']) && $contactRule['min'] != @$contactRule['max']) : ?>
                                        <p class="pl15 mb0">
                                            <i id="contact_len_min" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthTooShortStart'), $contactRule['min']); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if(isset($contactRule['max']) && $contactRule['max'] != @$contactRule['min']) : ?>
                                            <p class="pl15 mb0">
                                                <i id="contact_len_max" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthTooLongStart'), $contactRule['max']); ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if(isset($contactRule['min'], $contactRule['max']) && $contactRule['min'] == $contactRule['max']) : ?>
                                        <p class="pl15 mb0">
                                            <i id="contact_len_same" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthStandard') , $contactRule['min']) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if($requireDialingCode) : ?>
                                <p class="pl15 mb0">
                                    <i class="dialing-code registration-field-required-icon icon-warning red f16 mr5"></i> <?php echo lang('validation.validateDialingCode'); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="fcmonu-note mb20 msg-container" style="display:none">
                            <p class="pl15"><i class="icon-warning red f16 mr5"></i><span id="sms_verification_msg"></span></p>
                        </div>
                    </div>
                    <?php break; ?>

                <?php case 11: # SECURITY ?>
                    <?php $securityList = ['reg.37', 'reg.38', 'reg.39', 'reg.40', 'reg.41'] ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <div class="custom-dropdown">
                                <select name="<?=$registration_field['alias']?>" class="form-control registration-field" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>>
                                    <option value=""><?php echo lang('a_reg.' . $registration_field['registrationFieldId']); ?><?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?></option>
                                    <?php foreach($securityList as $val) : ?>
                                        <option value="<?=$val?>" <?=set_select('security_question', $val)?> <?=(set_value('secretQuestion') == $val) ? "selected" : ""?>><?=lang($val)?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                </p>
                            </div>
                        <?php endif ?>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <input type="text" class="form-control registration-field" name="secretAnswer" placeholder="<?php echo lang('forgot.06'); ?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value('secretAnswer')?>"/>
                        </div>
                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                </p>
                            </div>
                        <?php endif ?>
                    </div>
                    <?php break; ?>

                <?php case 13: # REFERRAL CODE ?>
                    <?php if($displayReferralCode) : ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group form-inline relative <?=$required_class?>">
                                <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                                <label><i class="glyphicon glyphicon-qrcode"></i></label>
                                <input type="text" class="form-control registration-field fcreco" name="<?=$registration_field['alias']?>" onkeyup="return Register.validateReferralCode(this.value)" placeholder="<?=lang('Referral Code Placeholder')?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'], $referral_code)?>"/>
                            </div>
                            <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                                <div class="fcreco-note registration-field-note hide mb20">
                                    <p class="pl15 mb0">
                                        <i id="referral_code" class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                        <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php break; ?>

                <?php case 14: # AFFILIATE CODE ?>
                    <?php if($displayAffilateCode): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group form-inline relative <?=$required_class?>">
                                <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                                <label><i class="glyphicon glyphicon-qrcode"></i></label>
                                <input
                                    type="text" class="form-control registration-field fcreco" name="tracking_code"
                                    placeholder="<?=lang($registration_field['field_name'])?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>
                                    value="<?=set_value('tracking_code');?>"
                                    <?php if($this->utils->getConfig('registration_time_aff_tracking_code_validation') ||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')): ?>
                                    onKeyUp="Register.validateTrackingCode(this.value)"
                                    onfocus="return Register.validateTrackingCode(this.value)"
                                    <?php endif;?>
                                />
                            </div>

                            <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                                <div class="fcreco-note registration-field-note hide mb20">
                                    <p class="pl15 mb0">
                                        <i id="tracking-code" class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                        <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                    </p>
                                    <?php if($this->utils->getConfig('registration_time_aff_tracking_code_validation')||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')): ?>
                                    <p id="affcode_exist_failed" class="pl15" style="display: none;"><i class="icon-warning red f16 mr5"></i> <?=lang('validation.trackingCodeNotAvalibale')?></p>
                                    <?php endif;?>
                                </div>
                            <?php elseif($this->utils->getConfig('registration_time_aff_tracking_code_validation') ||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')):?>
                                <div id="affcode_exist_failed" class="fcreco-note registration-field-note hide mb20" style="display: none;">
                                    <p class="pl15 mb0"><i class="icon-warning red f16 mr5"></i> <?=lang('validation.trackingCodeNotAvalibale')?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php break; ?>

                <?php case 46: # AGENT TRACKING CODE ?>
                    <?php if($displayAgencyCode): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group form-inline relative <?=$required_class?>">
                                <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                                <label><i class="fa fa-qrcode"></i></label>
                                <input type="text" class="form-control registration-field fcreco" name="agent_tracking_code" placeholder="<?=lang('a_reg.' . $registration_field['registrationFieldId'])?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value('agent_tracking_code', $agent_tracking_code);?>"/>
                            </div>

                            <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                                <div class="fcreco-note registration-field-note hide mb20">
                                    <p class="pl15 mb0">
                                        <i id="tracking-code" class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                        <?=sprintf(lang('formvalidation.required'), lang('a_reg.' . $registration_field['registrationFieldId']))?>
                                    </p>
                                </div>
                            <?php endif ?>
                        </div>
                    <?php endif ?>
                    <?php break; ?>

                <?php case 31: # TERMS OF SERVICE ; ?>
                <?php case 52: # NEWSLETTER SUBSCRIPTION ?>
                    <?php break; ?>

                <?php case 33: # COUNTRY ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?> resident_country_option">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <div class="custom-dropdown">
                                <select id="country_list" class="form-control registration-field" name="<?=$registration_field['alias']?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> onchange="Register.chosenCountry(this)">
                                    <option value=""><?php echo lang('reg.a42'); ?><?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?></option>
                                    <?php if(!$this->utils->isEnabledFeature('disable_frequently_use_country_in_registration')) : ?>
                                        <optgroup label="<?=lang('lang.frequentlyUsed')?>">
                                            <?php foreach($this->utils->getCommonCountryList() as $key){ ?>
                                                <option value="<?=$key?>" <?=(set_value($registration_fields['Resident Country']['alias']) == $key) ? "selected" : "";?>><?=lang('country.' . $key)?></option>
                                            <?php } ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <optgroup label="<?=lang('lang.alphabeticalOrder')?>">
                                        <?php foreach($this->utils->getCountryList() as $key){ ?>
                                            <option value="<?=$key?>" <?=(set_value($registration_fields['Resident Country']['alias']) == $key) ? "selected" : "";?>><?=lang('country.' . $key)?></option>
                                        <?php } ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note countryField hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                </p>
                            </div>
                        <?php endif ?>
                    </div>
                    <?php break; ?>

                <?php case 34: # SMS VERIFICATION CODE ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <label><i class="glyphicon-phone"></i></label>
                            <input type="text" class="form-control registration-field" name="sms_verification_code" placeholder="<?=lang($registration_field['field_name'])?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value('sms_verification_code')?>"/>
                            <?php if ($showSMSField): ?>
                                <?php if (!$this->CI->config->item('disabled_voice')): ?>
                                    <?php if ($this->utils->is_mobile()): ?>
                                            <?php $voice_class='voice_mobile_class' ?>
                                        <?php else: ?>
                                            <?php $voice_class='voice_class' ?>
                                        <?php endif ?>
                                    <button type="button" id="send_voice_verification" class="btn btn-success <?=$voice_class ?>" >
                                        <?=lang('Send Voice service')?>
                                    </button>
                                <?php endif ?>

                                <button type="button" id="send_sms_verification" class="btn btn-success"
                                    <?php if (isset($registration_template_for_mobile_reference) && $registration_template_for_mobile_reference == 4): ?>
                                        style=""
                                    <?php else: ?>
                                        style="position:absolute;right:10px;top:-2px"
                                    <?php endif ?>
                                >
                                    <?=lang('Send SMS')?>
                                </button>
                            <?php endif ?>
                        </div>

                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required.sms_verification_code'), lang($registration_field['field_name']))?>
                                </p>
                                <p id="sms_code_failed" class="pl15 mb0 hide"><i class="icon-checked green f16 mr5"></i> <?=lang('Verify SMS Code Failed')?></p>
                            </div>
                        <?php endif ?>
                    </div>
                    <?php break; ?>

                <?php case 35: # Withdrawal Password ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <label><i class="icon-pass"></i></label>
                            <input type="password" class="form-control registration-field" name="<?=$registration_field['alias']?>" placeholder="<?=lang($registration_field['field_name'])?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'])?>"/>
                        </div>

                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                </p>
                            </div>
                        <?php endif ?>
                    </div>
                    <?php break; ?>

                <?php case 40: # Bank Name ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <div class="custom-dropdown">
                                <select name="<?=$registration_field['alias']?>" class="form-control registration-field" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>>
                                    <option value =""  ><?=lang("pay.bank")?></option>
                                    <?php foreach($banks as $row): ?>
                                        <?php if($row['payment_type_flag'] == Financial_account_setting::PAYMENT_TYPE_FLAG_BANK && $row['enabled_withdrawal'] && $row['enabled_deposit']): ?>
                                            <option value="<?=$row['bankTypeId']?>" <?=(set_value($registration_field['alias']) == $row['bankTypeId']) ? 'selected' : '';?>><?=lang($row['bankName'])?></option>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), lang($registration_field['field_name']))?>
                                </p>
                            </div>
                        <?php endif ?>
                    </div>
                    <?php break; ?>

                <?php case 41: # Bank Account Number ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?php if($account_validator['only_allow_numeric']):?>
                                <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                                <input type="number" class="form-control registration-field remove_number_spinner" name="<?=$registration_field['alias']?>" placeholder="<?=lang('financial_account.bankaccount')?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'])?>" onfocus="return Register.validateBankAccountRequirements(this.value)"  oninput="return Register.validateBankAccountRequirements(this.value)"
                                />
                            <?php else: ?>
                                <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                                <input type="text" class="form-control registration-field" name="<?=$registration_field['alias']?>" placeholder="<?=lang('financial_account.bankaccount')?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'])?>" onfocus="return Register.validateBankAccountRequirements(this.value)"  oninput="return Register.validateBankAccountRequirements(this.value)"
                                />
                            <?php endif;?>
                        </div>

                        <div class="registration-field-note hide mb20">
                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <p class="pl15 mb0">
                                <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                <?=sprintf(lang('formvalidation.required'), lang('financial_account.bankaccount'))?>
                            </p>
                        <?php endif ?>

                        <?php if(!empty($account_validator['bankAccountNumber']['min_max_length'])): ?>
                            <p class="pl15 mb0">
                                <i id="bank-acc-num" class="icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang("form.validation.invalid_min_max_length"), lang("financial_account.bankaccount"))?>
                            </p>
                        <?php endif; ?>
                        </div>
                    </div>
                    <?php break; ?>


                <?php case 49: //  ID card number ?>
                    <?php $fieldName = $im1 = $this->config->item($registration_field['field_name'], 'cust_non_lang_translation'); ?>
                    <div class="col-md-6 col-lg-4">
                        <!-- ID: <?= $registration_field['registrationFieldId'] ?> -->
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <input type="text" class="form-control registration-field" name="<?=$registration_field['alias']?>" placeholder="<?=($fieldName) ? $fieldName : lang('a_reg.' . $registration_field['registrationFieldId'])?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'])?>"/>
                        </div>

                        <div class="registration-field-note hide mb20">
                            <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5" id="val_id_card_num"></i>
                                    <?= sprintf(lang('formvalidation.required'), lang('a_reg.' . $registration_field['registrationFieldId']), $this->utils->getConfig('id_card_number_validator')['char_lenght']) ?>
                                </p>
                                <p class="pl15 mb0">
                                    <i class="icon-warning red f16 mr5" id="val_id_card_num_len"></i>
                                    <?= sprintf(lang('formvalidation.format_and_len_must_match'), lang('a_reg.' . $registration_field['registrationFieldId']), $this->utils->getConfig('id_card_number_validator')['char_lenght']) ?>
                                </p>
                            <?php endif ?>
                        </div>

                    </div>
                    <?php break; ?>

                <?php case 36: # City     ?>
                <?php case 37: # Region   ?>
                <?php case 43: # Address  ?>
                <?php case 44: # Address 2?>
                    <?php if(!$address_title):?>
                        <?php $address_title = TRUE;?>
                        <div class="col-md-12 col-lg-12" id="register-address-title" style="display: none;">
                            <?=lang('player.59');?>
                        </div>
                    <?php endif;?>
                    <?php if($full_address_in_one_row): ?>
                        <?php $placeholder = lang('Please input your full address'); ?>
                    <?php else: ?>
                        <?php $placeholder = lang('a_reg.'.$registration_field['registrationFieldId'].'.placeholder');?>
                    <?php endif;?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <input type="text"
                                class="form-control registration-field"
                                name="<?=$registration_field['alias']?>"
                                placeholder="<?=$placeholder;?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>"
                                <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?>
                                value="<?=set_value($registration_field['alias'])?>"
                                maxlength="120"
                            />
                        </div>
                        <div class="registration-field-note hide mb20">
                            <p class="pl15 mb0">
                                <i class="icon-checked green f16 mr5"></i> <?=sprintf(lang('validation.maxlength'), 120)?>
                            </p>
                            <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=lang('a_reg.' . $registration_field['registrationFieldId'] . '.hint')?>
                                </p>
                            <?php endif ?>
                        </div>
                    </div>
                    <?php break; ?>

                <?php case 45: # Email  ignore?>
                <?php case 50: # Dialing code use in case 8?>
                    <?php break; ?>


                <?php default: ?>
                    <?php $fieldName = $im1 = $this->config->item($registration_field['field_name'], 'cust_non_lang_translation'); ?>
                    <div class="col-md-6 col-lg-4">

                        <div class="form-group form-inline relative <?=$required_class?>">
                            <?=$this->registration_setting->displaySymbolHint($registration_field["field_name"])?>
                            <input type="text" class="form-control registration-field" name="<?=$registration_field['alias']?>" placeholder="<?=($fieldName) ? $fieldName : lang('a_reg.' . $registration_field['registrationFieldId'])?> <?=$this->registration_setting->displayPlaceholderHint($registration_field["field_name"])?>" <?php if($registration_field['required'] == Registration_setting::REQUIRED) echo 'required'; ?> value="<?=set_value($registration_field['alias'])?>"/>
                        </div>

                        <?php if($registration_field['required'] == Registration_setting::REQUIRED): ?>
                            <div class="registration-field-note hide mb20">
                                <p class="pl15 mb0">
                                    <i class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                    <?=sprintf(lang('formvalidation.required'), (($fieldName) ? $fieldName : lang('a_reg.' . $registration_field['registrationFieldId'])))?>
                                </p>

                            </div>
                        <?php endif ?>
                    </div>
                    <?php break; ?>

                    <?php
                }
            }
        };
        ?>
<script>
    $(document).ready(function () {
        $('#age-limit').val(<?php echo $age_limit ?>); //set age limit to div for store
        Register.showBirthdayDisplayFormat(birthday_display_format);
    });
    var allow_first_number_zero = <?= !empty($this->utils->getConfig('allow_first_number_zero')) ? 'true' : 'false' ?>;
    var birthday_display_format = "<?= $this->utils->getConfig('birthday_display_format') ? $this->utils->getConfig('birthday_display_format') : 'yyyymmdd' ?>";
    //OGP-16006
    var getDefaultDialingCode = <?= !empty($this->utils->getConfig('enable_default_dialing_code')) ? 'true' : 'false' ?>;
    $(document).ready(function(){
        if(getDefaultDialingCode){
            Register.validateRequired($('#dialing_code'), $('#dialing_code').val());
        }
    });
    // export sys feature block_emoji_chars_in_real_name_field, OGP-12268
    var sys_feature_block_emoji = <?= $this->utils->isEnabledFeature('block_emoji_chars_in_real_name_field') ? 'true' : 'false' ?>;
</script>