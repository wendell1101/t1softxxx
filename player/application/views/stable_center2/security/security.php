<?php
$hide_contact_customer_service_in_security_of_player_center = ! empty( $this->utils->getConfig('hide_contact_customer_service_in_security_of_player_center') );
?><div id="security" class="panel">
    <div class="panel-heading">
        <h1 class="hidden-xs hidden-sm"><?= lang('Security') ?></h1>
    </div>
    <div class="panel-body security-list">
        <?php if($this->utils->isEnabledFeature('show_player_upload_realname_verification')){ ?>
            <div class="col-md-6 col-sm-6 security-block-player-realname">
                <div class="se-bg"></div>
                <div class="se-content <?php if($player_verification['verified']) { echo 'completed'; }?>">
                    <p><?= lang('Real Name Verification') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <button id="security_realname_btn" type="button" class="btn mc-btn" onclick="PlayerSecurity.uploadfile.targetModal('realname')">
                        <?php
                        if(!$player_verification['verified'] && !$player_verification['count_proof_attachment']['photo_id']['allowUpload']){

                            echo lang('Verification View Details');
                        }elseif ($player_verification['verified']){

                            echo lang('Verification View Details');
                        }elseif($player_verification['count_proof_attachment']['photo_id']['total'] == 0){

                            echo lang('Upload Valid ID');
                        }else{

                            echo lang('Verifying');
                        }?>
                    </button>
                </div>
            </div>
        <?php }?>
        <?php if($this->utils->isEnabledFeature('show_player_upload_proof_of_address')){ ?>
            <div class="col-md-6 col-sm-6 security-block-player-address">
                <div class="se-bg"></div>
                <div class="se-content <?php if($player_verification['verified_address']) { echo 'completed'; }?>">
                    <p><?= lang('Proof of Address Verification') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <button id="security_address_btn" type="button" class="btn mc-btn" onclick="PlayerSecurity.uploadfile.targetModal('address')">
                        <?php
                        if (!$player_verification['verified_address'] && !$player_verification['count_proof_attachment']['address']['allowUpload']) {
                            echo lang('Verification View Details');
                        } elseif ($player_verification['verified_address']) {
                            echo lang('Verification View Details');
                        } elseif ($player_verification['count_proof_attachment']['address']['total'] == 0) {
                            echo lang('Upload');
                        } else {
                            echo lang('Verifying');
                        }?>
                    </button>
                </div>
            </div>
        <?php }?>
        <?php if($this->utils->isEnabledFeature('show_player_upload_proof_of_income')){ ?>
            <div class="col-md-6 col-sm-6 security-block-player-income">
                <div class="se-bg"></div>
                <div class="se-content <?php if($player_verification['verified_income']) { echo 'completed'; }?>">
                    <p><?= lang('Proof of Income Verification') ?> <i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <?php if($this->utils->isEnabledFeature('enable_upload_income_notes')): ?>
                        <p><i class="fa fa-info-circle" data-toggle="tooltip" title="<?= lang("upload_income_notes") ?>"></i></p>
                    <?php endif;?>
                    <button id="security_income_btn" type="button" class="btn mc-btn" onclick="PlayerSecurity.uploadfile.targetModal('income')">
                        <?php
                        if (!$player_verification['verified_income'] && !$player_verification['count_proof_attachment']['income']['allowUpload']) {
                            echo lang('Verification View Details');
                        } elseif ($player_verification['verified_income']) {
                            echo lang('Verification View Details');
                        } elseif ($player_verification['count_proof_attachment']['income']['total'] == 0) {
                            echo lang('Upload');
                        } else {
                            echo lang('Verifying');
                        }?>
                    </button>
                </div>
            </div>
        <?php }?>
        <?php if($this->utils->isEnabledFeature('show_player_upload_proof_of_deposit_withdrawal')){ ?>
            <div class="col-md-6 col-sm-6 security-block-player-deposit-withdrawal">
                <div class="se-bg"></div>
                <div class="se-content <?php if($player_verification['verified_dep_wd']) { echo 'completed'; }?>">
                    <p><?= lang('Proof of Deposit/Withdrawal') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <button id="security_deposit_witdrawal_btn" type="button" class="btn mc-btn" onclick="PlayerSecurity.uploadfile.targetModal('deposit_withdrawal')">
                        <?php
                        if (!$player_verification['verified_dep_wd'] && !$player_verification['count_proof_attachment']['dep_wd']['allowUpload']) {
                            echo lang('Verification View Details');
                        } elseif ($player_verification['verified_dep_wd']) {
                            echo lang('Verification View Details');
                        } elseif ($player_verification['count_proof_attachment']['dep_wd']['total'] == 0) {
                            echo lang('Upload');
                        } else {
                            echo lang('Verifying');
                        }?>
                    </button>
                </div>
            </div>
        <?php }?>
        <?php if ($showSMSField && !$this->utils->getConfig('disabled_mobile_verification_in_security')): ?>
            <div class="col-md-6 col-sm-6 security-block-player-contact-number">
                <div class="se-bg"></div>
                <div class="se-content <?php if($isPhoneVerified) { echo 'completed'; }?>">
                    <p><?= lang('Mobile Verification') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <?php if($this->utils->getConfig('contact_number_note')): ?>
                        <span class="help-block" style="clear:left; padding-top:5px"><?= lang('phone_note') ?></span>
                    <?php endif; ?>

                    <?php if($isPhoneVerified): ?>
                        <span class="btn mc-btn verified-btn"><?= lang('Verified') ?></span>
                    <?php else: ?>
                        <?php if(!$this->config->item('disabled_sms_verified_btn_in_security')): ?>
                            <button type="button" class="btn mc-btn" data-toggle="modal" onclick="return PlayerSecurity.sendMobileVerification('<?=$player['contactNumber']?>', '<?= sprintf(lang('msg.send.verification'),$player['contactNumber']) ?>', '<?= lang('mod.mobilePhoneAsContact') ?>' ,'<?=$player['dialing_code']?>')"><?= lang('Send SMS Verification') ?></button>
                        <?php endif; ?>
                        <?php if(!$this->config->item('disabled_voice')): ?>
                            <button type="button" id="send_voice_verification" class="btn mc-btn" data-toggle="modal" onclick="return PlayerSecurity.sendMobileVerification('<?=$player['contactNumber']?>', '<?= sprintf(lang('msg.send.verification'),$player['contactNumber']) ?>', '<?= lang('mod.mobilePhoneAsContact') ?>' ,'<?=$player['dialing_code']?>')"><?= lang('Send Voice service') ?></button>
                        <?php endif; ?>
                        <input type="text" id="send_verification_by" hidden="true" value="">
                        <button type="button" id="verification_code" class="btn mc-btn" data-toggle="modal" data-target="#security-mobile2"><?= lang('Enter Verification') ?></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif;?>
        <?php if($showEmailVerifyField): ?>
            <div class="col-md-6 col-sm-6 security-block-player-email">
                <div class="se-bg"></div>
                <div class="se-content <?php if(isset($isEmailVerified) && $isEmailVerified) { echo 'completed'; }?>">
                    <p><?= lang('Email Verification') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <?php if(isset($isEmailVerified) && $isEmailVerified): ?>
                        <span class="btn mc-btn verified-btn"><?= lang('Verified') ?></span>
                    <?php else: ?>
                        <button id="resend_email_btn" type="button" class="btn mc-btn" data-toggle="modal" onclick="resendEmail_wrapper.resendEmail();"><?= lang('Send Verification') ?> <span id="resend_email_btn_countdown"></span></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif;?>
        <?php if($enabled_withdrawal_password) : ?>
            <div class="col-md-6 col-sm-6 security-block-player-withdrawal-password">
                <div class="se-bg"></div>
                <div class="se-content <?php if(!empty($withdraw_password)): echo 'completed'; endif;?>">
                    <p><?= lang('Withdrawal Password') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <?php if (!empty($withdraw_password)) : ?>
                        <?php if($disable_player_change_withdraw_password): ?>
                                <a href="javascript:void(0)" class="link-btn <?=($hide_contact_customer_service_in_security_of_player_center)?'hide':''?>" onclick="<?=$this->utils->getLiveChatOnClick();?>"><?=lang('Contact Customer Service')?></a>
                        <?php else :?>
                            <button id="withdraw_change_password_btn" type="button" class="btn mc-btn" data-toggle="modal" data-target="#security-withdrawal"><?= lang('player_center.security.change_password') ?></button>
                            <?php if($this->utils->isEnabledFeature('enabled_forgot_withdrawal_password_use_email_to_reset')): ?>
                                <a href="javascript:void(0)" class="link-btn" data-toggle="modal" data-target="#security-withdrawal-forgot-password"><?= lang('player_center.security.forgot_password') ?>?</a>
                            <?php endif; ?>
                            <?php if ($showSMSField && $this->utils->getConfig('enabled_security_find_withdrawal_password_by_sms')) { ?>
                                <a href="javascript:void(0)" onclick="checkVerifyphone()" class="link-btn" id="withdrawal_password_by_sms_btn" ><?=lang('player_center.security.find_withdrawal_password_by_sms')?></a>
                            <?php } ?>
                                <a href="javascript:void(0)" class="link-btn <?=($hide_contact_customer_service_in_security_of_player_center)?'hide':''?>" onclick="<?=$this->utils->getLiveChatOnClick();?>"><?=lang('Contact Customer Service')?></a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button id="withdraw_password_btn" type="button" class="btn mc-btn" data-toggle="modal" data-target="#security-withdrawal"><?= lang('Create Password') ?></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($this->registration_setting->isRegistrationFieldVisible('Security Question')) : ?>
            <div class="col-md-6 col-sm-6 security-block-player-question">
                <div class="se-bg"></div>
                <div class="se-content <?php if(!empty($secretQuestion) && !empty($secretAnswer)): echo 'completed'; endif;?>">
                    <p><?= lang('Security Question') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <?php if (!empty($secretQuestion) && !empty($secretAnswer)) : ?>
                        <button type="button" class="btn mc-btn" data-toggle="modal" data-target="#security-question2"><?= lang('View Secret Question') ?></button>
                        <?php if(!$this->utils->isEnabledFeature('disabled_player_to_change_security_question')):?>
                            <a id="change_question_btn" href="" class="link-btn" type="button" data-toggle="modal" data-target="#security-question"><?= lang('Change Secret Question') ?></a>
                        <?php endif;?>

                    <?php else: ?>
                        <button id="select_question_btn" type="button" class="btn mc-btn" data-toggle="modal" data-target="#security-question"><?= lang('Select Question') ?></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($player_passwd_not_set) : ?>
            <div class="col-md-6 col-sm-6 security-block-player-password">
                <div class="se-bg"></div>
                <div class="se-content">
                    <p><?= lang('Login Password') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <button id="security_loginpassword_btn" type="button" class="btn mc-btn" data-toggle="modal" data-target="#security-setpassword"><?= lang('Set Password') ?></button>
                </div>
            </div>
        <?php else : ?>
            <div class="col-md-6 col-sm-6 security-block-player-password">
                <div class="se-bg"></div>
                <div class="se-content completed">
                    <p><?= lang('Login Password') ?><i class="fa fa-check-circle" aria-hidden="true"></i></p>
                    <button id="security_setpassword_btn" type="button" class="btn mc-btn" data-toggle="modal" data-target="#security-loginpassword"><?= lang('Change Password') ?></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>




<div class="modal fade security-modal" id="uploadFile-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><span><?= lang('Security') ?></span><small> - <?= lang('Real Name Verification') ?></small></h4>
                <div id="div_real_name_verification_pass_msg"></div>
            </div>
            <form id="form_upload_image">
                <input type="hidden" name="tag" value="photo_id">
                <div class="modal-body">
                    <ul class="nav nav-tabs image-upload">
                        <li role="presentation" class="active">
                            <a href="#uploadFile" id="uploadFile-tab" role="tab" data-toggle="tab" aria-controls="uploadFile" aria-expanded="true"><?= lang('Upload File') ?></a>
                        </li>
                        <li role="presentation" class="">
                            <a href="#uploadedFile" role="tab" id="uploadedFile-tab" data-toggle="tab" aria-controls="uploadedFile" aria-expanded="false"><?= lang('Uploaded File') ?></a>
                        </li>
                    </ul>
                    <div class="tab-content" >
                        <div class="tab-pane active" role="tabpanel" id="uploadFile" aria-labelledby="home-tab">
                            <div class="image-upload-container">
                                <div class="image-upload-entry">
                                    <label ondragover="PlayerSecurity.uploadfile.dragHandler(event)" ondrop="PlayerSecurity.uploadfile.dropImage(event)">
                                        <input type="file" <?=($ios_device)?'id="ios-upload"':'';?> accept="image/*" class="hidden <?=($ios_device)?'':'input-upload-file';?>" onchange="PlayerSecurity.uploadfile.filesHandler(this)" multiple>
                                        <div class="title"><?= lang('Upload File Preview') ?></div>
                                        <div class="upload-btn se-content completed">
                                            <?php if($ios_device):?>
                                                <label class="btn mc-btn form-control uploaded-btn" for="ios-upload">
                                                    <?= lang('Upload File') ?>
                                                </label>
                                            <?php else:?>
                                                <button type="button" class="btn mc-btn form-control uploaded-btn">
                                                    <?= lang('Upload File') ?>
                                                </button>
                                            <?php endif;?>
                                        </div>
                                        <div class="confirm-error">
                                            <ul>
                                                <li><?= sprintf( lang('attachment.upload_file_max_up_to'), $this->utils->getConfig("kyc_limit_of_upload_attachment"))?></li>
                                                <li><?= sprintf( lang('attachment.upload_file_type'), $lang_upload_image_types )?></li>
                                                <li><?= sprintf( lang('attachment.upload_file_size'), $lang_upload_image_max_size_limit )?></li>
                                                <li class="realname-area"><?= lang('attachment.pleaseEnterYourValidIdNumberProcessTheVerificationWithUploadedFile') ?></li>
                                            </ul>
                                        </div>
                                    </label>
                                </div>
                                <div class="image-upload-confirm" style="display: none;">
                                    <div class="image-confirm-display se-content completed">
                                        <button type="button" class="confirm-check-btn btn mc-btn btn-lg"
                                                onclick="PlayerSecurity.uploadfile.confirmCheck('confirm')"><?= lang('Confirm') ?>
                                        </button>
                                        <button type="button" class="confirm-cancel-btn btn mc-btn btn-lg"
                                                onclick="PlayerSecurity.uploadfile.confirmCheck('cancel')"><?= lang('lang.cancel') ?>
                                        </button>

                                        <div class="compressing">
                                            <img src="<?=site_url('/resources/images/loading.svg')?>">
                                            <div class="progress-num hide"><span>50</span>%</div>
                                            <div><?= lang('attachment.file_compression')?></div>
                                        </div>
                                        <!-- <img> preview -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" role="tabpanel" id="uploadedFile" aria-labelledby="profile-tab">
                            <div class="uploaded-file-container">
                                <div class="image-uploaded-file"></div>
                                <div style="clear: both;"></div>

                                <div id="overlay_photo_id" class="overlay">
                                    <div class="img_container">
                                        <img id="img_overlay_photo_id" src="" alt="">
                                    </div>
                                    <a href="javascript:void(0)" class="closebtn" onclick="return PlayerSecurity.closeNav(this)">&times;</a>
                                </div>
                            </div>
                            <div class="image-uploaded-file-prompt">
                                <p class="prompt_txt"><?= lang('Click each file to preview.')?> </p>
                                <p class="prompt_txt"><?= lang('Contact Customer Service to delete or edit files.')?> </p>
                                <p class="prompt_txt"><?= lang('Each file will not be able to delete or edit after verify.')?> </p>
                            </div>
                        </div>
                    </div>
                    <div class="realname-area id-card-number-area mt10">
                        <p><span class="color-red">*</span> <?=lang('attachment.card_number')?></p>
                        <?php if(!$player_verification['verified']):?>
                            <input type="text" class="form-control id_card_number" name="id_card_number" value="<?=isset($player['id_card_number']) ? $player['id_card_number'] : ''?>">
                        <?php else:?>
                            <input type="text" class="form-control id_card_number" name="id_card_number" value="<?=isset($player['id_card_number']) ? $player['id_card_number'] : ''?>" readonly disabled />
                        <?php endif;?>
                        <div id="div_real_name_verification_msg"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn upload-submit-btn"
                            onclick="PlayerSecurity.uploadfile.accountInfoUploadFileSubmit()"
                    >
                        <?= lang('lang.submit') ?>
                    </button>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.close') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Mobile Verification Modal -->
<div class="modal fade security-modal" id="security-mobile" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Mobile Verification') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <p id="security_sms_verification_msg"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" id="sms_return_message" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('button.back') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade security-modal" id="security-mobile2" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Mobile Verification') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <div id="security_verification_code_msg"></div>
                    <label>
                        <?= lang('Please enter verification code:') ?>
                    </label>
                    <input type="text" class="form-control" id="fm-verification-code">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" onclick="return PlayerSecurity.submitVerificationCode('<?=$player['contactNumber']?>', '<?= lang('con.aff04') ?>')"><?= lang('lang.submit') ?></button>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade security-modal" id="security-mobile-input" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Mobile Verification') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <div id="security_input_mobile_verification_code_msg"></div>
                    <label>
                        <?= lang('Please provide your phone number:') ?>
                    </label>
                    <input type="text" class="form-control security-contact-number-field" name="securityContactNumber" <?php if($contactRule): ?> data-rule="<?= htmlentities(json_encode($contactRule)); ?>" maxlength="<?php echo $contactRule['max'] ? $contactRule['max'] : 11; ?>" <?php endif; ?> onkeyup="return PlayerSecurity.validateContactNumber(this.value, this.getAttribute('data-rule'))">
                    <input type="hidden" name="validContactNumber" value="false">
                    <div class="fcmonu-note security-contact-number-field-note hide mb20">
                        <p class="pl15 mb0">
                            <i id="mobile_format" class="icon-warning red f16 mr5"></i>
                            <span class="validate-mesg format"><?=lang('validation.validateContactNumber')?></span>
                            <span class="validate-mesg in-use" style="display: none;"><?=lang('The number is in use')?></span>
                        </p>

                        <?php if($contactRule): ?>
                            <?php if (isset($contactRule['min']) && $contactRule['min'] != $contactRule['max']) : ?>
                                <p class="pl15 mb0"><i id="contact_len_min" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthTooShortStart'), $contactRule['min']);?></p>
                            <?php endif; ?>
                            <?php if (isset($contactRule['max']) && $contactRule['max'] != $contactRule['min']) : ?>
                                <p class="pl15 mb0"><i id="contact_len_max" class="icon-warning red f16 mr5"></i> <?php echo sprintf(lang('validation.lengthTooLongStart'),$contactRule['max']);?></p>
                            <?php endif; ?>
                            <?php if (isset($contactRule['min'], $contactRule['max']) && $contactRule['min'] == $contactRule['max']) : ?>
                                <p class="pl15 mb0"><i id="contact_len_same" class="icon-warning red f16 mr5"></i> <?php echo lang('validation.lengthStandard') . $contactRule['min']?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" onclick="return PlayerSecurity.inputMobileNum('<?= lang('msg.send.verification') ?>', '<?= lang('mod.mobilePhoneAsContact') ?>')"><?= lang('lang.submit') ?></button>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Email Verification Modal -->
<div class="modal fade security-modal" id="security-email" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Email Verification') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <?php if($isEmailFilledIn):?>
                        <p class="security-email-defaultMsg"><?php echo sprintf(lang('email.send.verification'),$player['email']); ?></p>
                        <?php if($this->utils->getConfig('enable_verify_mail_via_otp')):?>
                        <p class="security-email-otpFailMsg" style="display: none;"><i id="mobile_format" class="icon-warning red f16 mr5"></i><?=lang('Failed to verify, please try it again or contact customer service for assistance')?></p>
                        <?php endif;?>
                    <?php else:?>
                        <p class="security-email-defaultMsg"><?php echo lang('verification.emptyEmail'); ?></p>
                    <?php endif;?>
                    <?php if($isEmailFilledIn && $this->utils->getConfig('enable_verify_mail_via_otp')):?>
                        <div class="otp_code_container row">
                            <div class="col-md-12">
                                <label><?= lang('Please fill in the OTP provided in Email') ?>:</label>
                                <input type="text" class="col-md-6 col-sm-6 form-control" name="mail_verification_otp_code" id="mail_verification_otp_code" placeholder="OTP Code">
                                <span class="pull-right">
                                    <button id="resend_otp_btn" type="button" class="btn mc-btn" data-toggle="modal" onclick="return resendEmail_wrapper.resendEmail();"><?= lang('Resend') ?> <span id="resend_otp_btn_countdown"></span></button>
                                </span>
                                <p class="pl15 mb0 empty_otp" style="display: none;">
                                    <i id="mobile_format" class="icon-warning red f16 mr5"></i>
                                    <span class="validate-mesg" ><?=lang('Please fill in the OTP provided in Email')?></span>
                                </p>
                            </div>
                        </div>
                    <?php endif;?>
                </div>
                <div class="modal-footer">
                    <?php if($isEmailFilledIn && $this->utils->getConfig('enable_verify_mail_via_otp')):?>
                    <button type="button" class="btn mc-btn form-control submit-btn" onclick="return PlayerSecurity.submitMailVerificationOTPCode();"><?= lang('lang.submit') ?>
                    <?php endif;?>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('button.back') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade security-modal" id="security-email-opt-success" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Email Verification') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <p class="security-email-otpsuccessMsg"><?=lang('Verified, please close this modal and refresh the page to check it again.')?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" data-dismiss="modal"><?= lang('button.back') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Withdrawal Password Modal -->
<div class="modal fade security-modal" id="security-withdrawal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Withdrawal Password') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <div id="div_withrawal_pass_msg"></div>
                    <?php if (!empty($withdraw_password)) : ?>
                        <label><?= lang('Old Password:') ?></label>
                        <input type="password" class="form-control" name="current_password" id="current_password">
                    <?php endif; ?>
                    <label><?= lang('New Password') ?></label>
                    <span class="pull-right"><?= lang('Please type in 4-12 digits of numbers or letters') ?></span>
                    <input type="password" class="form-control" name="new_password" id="new_password">
                    <label><?= lang('Confirm Password:') ?></label>
                    <span class="pull-right"><?= lang('Please type in 4-12 digits of numbers or letters') ?></span>
                    <input type="password" class="form-control" name="confirm_new_password" id="confirm_new_password">
                    <?php if($this->utils->getOperatorSetting('enabled_display_withdrawal_password_notification')){ ?>
                        <span class="pull-left withrawal_pass_notification"><?= lang('player_center.security.withdrawal_password.notification') ?></span>
                    <?php }?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" onclick="return PlayerSecurity.changeWithdrawalPassword();"><?= lang('lang.submit') ?></button>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Withdrawal Password Modal -->
<div class="modal fade security-modal" id="security-general-complete" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Withdrawal Password') ?></small></h4>
            </div>
            <form>
                <div class="panel-body">
                    <div id="general_complete_mesg" style="height: 300px;">
                        <div class="mesg_success" style="width: 300px; margin: 0 auto; text-align: center; padding-top: 8em; display: none;">
                            <i class="fa fa-check fa-5x"></i><br />
                            <span><?= lang('Withdraw Successfully Change') ?></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">

                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade security-modal" id="security-withdrawal-forgot-password" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Withdrawal Password') ?></small></h4>
            </div>
            <form>
                <div class="panel-body">
                    <div id="div_forgot_withdrawal_pass_msg"></div>
                    <label><?=lang('lang.email')?></label>
                    <input type="text" class="form-control" name="email" id="email">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" onclick="return PlayerSecurity.forgotWithdrawalPassword('<?= lang('con.aff04') ?>');"><?= lang('lang.submit') ?></button>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- enabled_security_find_withdrawal_password_by_sms -->
<?php if ($showSMSField && $this->utils->getConfig('enabled_security_find_withdrawal_password_by_sms')) { ?>
    <?php include VIEWPATH . '/stable_center2/security/withdrawal_password_recovery_reset_code.php';?>
<?php } ?>
<!--  -->




<?php if(!$this->utils->isEnabledFeature('disabled_player_to_change_security_question') || (empty($secretQuestion) && empty($secretAnswer)) ):?>
<!-- Security Question Modal -->
<div class="modal fade security-modal" id="security-question" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Security Question') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <div id="div_secret_ques_msg"></div>
                    <label>
                        <?= lang('Select Question:') ?>
                    </label>
                    <select class="form-control" id="security_question" name="security_question">
                        <option value="" disabled=""><?=lang('reg.58')?></option>
                        <option value="reg.37" <?=set_select('security_question', 'reg.37')?>><?=lang('reg.37')?></option>
                        <option value="reg.38" <?=set_select('security_question', 'reg.38')?>><?=lang('reg.38')?></option>
                        <option value="reg.39" <?=set_select('security_question', 'reg.39')?>><?=lang('reg.39')?></option>
                        <option value="reg.40" <?=set_select('security_question', 'reg.40')?>><?=lang('reg.40')?></option>
                        <option value="reg.41" <?=set_select('security_question', 'reg.41')?>><?=lang('reg.41')?></option>
                    </select>
                    <label><?= lang('Answer:') ?></label>
                    <input type="text" class="form-control" id="security_answer" name="security_answer">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" onclick="return PlayerSecurity.updateSecretQuestion('<?= lang('con.aff04') ?>');"><?= lang('lang.submit') ?></button>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif;?>

<div class="modal fade security-modal" id="security-question2" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Security Question') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <p>
                        <strong><?= lang('Selected Question:') ?></strong> <?=lang($secretQuestion)?>
                    </p>
                    <p>
                        <strong><?= lang('Your Answer:') ?></strong> <?= str_replace('%20', ' ', $secretAnswer) ?>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('Close') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Login Password Modal -->
<div class="modal fade security-modal" id="security-loginpassword" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('lang.security.password') ?> </small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <div id="div_user_pass_msg"></div>
                    <?php if(!$directChangePassword) : ?>
                    <label><?= lang('Old Password:') ?></label>
                    <input type="password" class="form-control" name="opassword" id="opassword">
                    <?php endif; ?>
                    <label><?= lang('New Password') ?>:</label>
                    <span class="pull-right"><?= lang('Please type in at least 6 digits of numbers or letters') ?><br><?=lang('Mix English letters and Numbers')?></span>
                    <input type="password" class="form-control" name="npassword" id="npassword">
                    <label><?= lang('Confirm New Password') ?>:</label>
                    <span class="pull-right"><?= lang('Please type in at least 6 digits of numbers or letters') ?><br><?=lang('Mix English letters and Numbers')?></span>
                    <input type="password" class="form-control" name="cpassword" id="cpassword">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" onclick="return PlayerSecurity.ResetPassword();"><?= lang('lang.security.submit') ?></button>
                    <button type="button" id="loginpassword-cancel" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Set Password Modal -->
<div class="modal fade security-modal set_passwd" id="security-setpassword" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?= lang('Login Password') ?></small></h4>
            </div>
            <form>
                <div class="modal-body">
                    <div class="div_user_pass_msg"></div>
                    <label><?= lang('Set Password') ?>:</label>
                    <span class="pull-right"><?= lang('Please type in at least 6 digits of numbers or letters') ?></span>
                    <input type="password" class="form-control passwd" name="npassword" id="npassword">
                    <label><?= lang('Confirm Password') ?>:</label>
                    <span class="pull-right"><?= lang('Please type in at least 6 digits of numbers or letters') ?></span>
                    <input type="password" class="form-control cpasswd" name="cpassword" id="cpassword">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" onclick="return PlayerSecurity.setPassword('.set_passwd');"><?= lang('Save') ?></button>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Login Password After Change Password Modal -->
<div class="modal fade" id="security-gamepassword" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-backdrop="false" data-keyboard="false" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?=lang('con.17')?></h4>
            </div>
            <div class="modal-body">
                <div>
                    <span><?=lang('cp.changePassing')?></span>
                </div>
                <table class="listtable table" id="gamelist_table">
                    <?php foreach ($game_platforms as $key => $value):?>
                        <tr>
                            <td>
                                <input readonly="readonly" class="game_platforms_value form-control" value="<?=$value?>">
                            </td>
                            <td>
                                <input readonly="readonly" class="game_platforms_key form-control" id="game_platforms_<?=$key?>" name="game_platforms_<?=$key?>" value="<?=lang('Pending')?>">
                             </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" id="game_changpass_btn" class="btn mc-btn btn-default" data-dismiss="modal" onClick="PlayerSecurity.openDialog_setPassword_success()" ><?=lang('Confirm')?></button>
            </div>
        </div>
    </div>
</div>

<!-- setPassword success modal -->
<div class="modal fade" id="setPassword_success" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-backdrop="false" data-keyboard="false" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?=lang('Security')?> - <?= lang('Login Password') ?></h4>
            </div>
            <div class="modal-body">
                <div>
                    <span><?=lang('Login password set')?></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="setPassword_confirm_btn" class="btn mc-btn btn-default btn-success" data-dismiss="modal" onClick="window.location.reload();" ><?=lang('aff.ok')?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->CI->load->widget('sms'); ?>
<script type="text/javascript">
    $('#send_voice_verification').click(function(){
            $('#send_verification_by').val('send_voice_verification');
        })
    $(function(){
        PlayerSecurity.LANG_UPLOAD_FILE_ERRMSG ="<?= lang('collection.upload.msg.unsuccess')?>" +"<?= sprintf(lang('upload.validation.wrongFileSize'),$upload_image_max_size_limit/1000000) ?>" ;
        PlayerSecurity.LANG_UPLOAD_REAL_NAME= "<?= lang('pleaese enter real name') ?>" ;
        PlayerSecurity.LANG_PleaseTryAgain= "<?= lang('attachment.pleaseTryAgain') ?>" ;
        PlayerSecurity.LANG_CompressFailed= "<?= lang('attachment.compressFailed') ?>" ;
        PlayerSecurity.LANG_Note= "<?= lang('attachment.note') ?>" ;
        PlayerSecurity.LANG_AlreadyReachMaximumFileNumber= "<?= sprintf( lang('attachment.alreadyReachMaximumFileNumber'), $this->config->item('kyc_limit_of_upload_attachment') ) ?>" ;
        <?php if($currentLanguage == 'chinese'): ?>
        PlayerSecurity.LANG_PleaseSelectFileTypeAndSize= "<?= sprintf( lang('attachment.pleaseSelectFileTypeAndSize')
                                                                        , $lang_upload_max_filesize
                                                                        , $lang_upload_image_types )?>" ;
        <?php else: ?>
        PlayerSecurity.LANG_PleaseSelectFileTypeAndSize= "<?= sprintf( lang('attachment.pleaseSelectFileTypeAndSize')
                                                                        , $lang_upload_image_types
                                                                        , $lang_upload_max_filesize )?>" ;
        <?php endif; // EOF if($currentLanguage == 'chinese') ?>

        PlayerSecurity.LANG_EnterYourColumnForUpload = "<?= sprintf(lang('please enter your column for upload'), lang('ID Card Number')) ?>";
        PlayerSecurity.LANG_PleaseConfirm= "<?= lang('attachment.pleaseConfirm') ?>" ;
        PlayerSecurity.LANG_ValidIdCardNumberIsInput = "<?= lang('attachment.validIdCardNumberIsInput') ?>" ;
        PlayerSecurity.LANG_EmptyIdCardNumber= "<?= lang('notify.117') ?>" ;
        PlayerSecurity.LANG_MaxLengthIdCardNumber= "<?= lang('notify.116') ?>" ;
        PlayerSecurity.LANG_WrongFormatIdCardNumber= '<?= lang("notify.115") ?>' ;
        PlayerSecurity.LANG_UpdateFailed= "<?= lang('attachment.updateFailed') ?>" ;
        PlayerSecurity.LANG_UploadAtLeastOneFile= "<?= lang('attachment.uploadAtLeastOneFile') ?>" ;
        PlayerSecurity.LANG_PleaseEnterYourValidIdNumberProcessTheVerificationWithUploadedFile= "<?= lang('attachment.pleaseEnterYourValidIdNumberProcessTheVerificationWithUploadedFile') ?>" ;
        PlayerSecurity.upload_max_filesize = <?= $upload_max_filesize ?>;
        PlayerSecurity.LANG_CHECKVERIFYPHONE= "<?= lang('Please verify your mobile number first') ?>";

        PlayerSecurity.game_fail_msg ="<?= lang('Failed') ?>";
        PlayerSecurity.game_platforms = [];
        PlayerSecurity.GamePasswordProcessing ="<?=lang('Processing')?>";
        PlayerSecurity.IS_EMAIL_FILLED_IN = <?=$isEmailFilledIn?'1':'0';?>;
        PlayerSecurity.isPhoneVerified = <?= $isPhoneVerified?'1':'0'; ?>;
        PlayerSecurity.Verification_Photo_ID = "<?=BaseModel::Verification_Photo_ID;?>";
        PlayerSecurity.redirect_input_verification_code_when_send_verification = '<?=$this->config->item('redirect_input_verification_code_when_send_verification') ?>';

        <?php foreach ($game_platforms as $key => $value): ?>
        PlayerSecurity.game_platforms.push(<?=$key?>);
        <?php endforeach; ?>

        <?php $contactNumberRegex = $this->config->item('register_mobile_number_regex'); ?>
        <?php if(!empty($contactNumberRegex)):?>
            PlayerSecurity.contactNumberRegex = '<?=addslashes($contactNumberRegex)?>';
        <?php endif ?>

        PlayerSecurity.game_count = PlayerSecurity.game_platforms.length;
        PlayerSecurity.MAX_CHANGE_GAME_PLATFORM_COUNT = "<?= $this->utils->getConfig('max_change_password_game_count') ?>";
        PlayerSecurity.game_platforms.sort(sortNumber);
        PlayerSecurity.HIDE_CHANGE_PASSWORD_GAME_RESULT_TABLE = "<?=$this->utils->getConfig('hide_change_password_game_result_table') ?>" ;

        function sortNumber(a,b)
        {
            return b-a;
        }

        $('#security-loginpassword').on('hidden.bs.modal', function () {
            $(this).find('input').val('');
            $('#div_user_pass_msg').empty();
        });

        // OGP-17826
        $('#security-setpassword').on('hidden.bs.modal', function () {
            $(this).find('input').val('');
            $('.set_passwd .div_user_pass_msg').empty();
        });

        $('.security-contact-number-field').on('click focus', function() {
            $('.security-contact-number-field-note').removeClass('hide');
        });

        $('#security-mobile-input').on("hidden.bs.modal",function(){
            PlayerSecurity.resetSecurityContactNumberModal();
        });

        $('#security-mobile-input').on('shown.bs.modal', function () {
            PlayerSecurity.resetSecurityContactNumberModal();
        });

        $('#security-withdrawal').on("hidden.bs.modal",function(){
            PlayerSecurity.resetWithdrawPasswordModal();
        });

        <?php
            $serverImg = [
                'photo_id' => [],
                'address'  => [],
                'dep_wd'   => [],
                'income'   => [],
            ];

            $serverImgKeys = array_keys($serverImg);

            if (isset($player_verification['img_file'])) {
                foreach ($player_verification['img_file'] as $key => $val) {
                    if ($val['visible'] && in_array($val['tag'], $serverImgKeys)) {
                        $serverImg[$val['tag']][] = $val['file_name'];
                    }
                }
            }
        ?>

        var verified_upload_file_options = {
            'default_options': {
                'upload_img_max_size': "<?= $upload_image_max_size_limit ?>",
                'allowd_upload_file_format': "<?= $this->config->item('allowed_upload_file') ?>",
                'upload_img_count_limit': "<?= $this->config->item('kyc_limit_of_upload_attachment') ?>"
            },
            'field_options': {
                'doCompressImages':{
                    'uri':  "<?= site_url('/player_center2/security/doCompressImagesBySize/');?>"
                },
                'realname': {
                    'modal-title': "<?= lang('Security') ?>",
                    'modal-title-tip': "<?= lang('Real Name Verification') ?>",
                    'form_action': "<?= site_url('/player_center2/security/upload_proof_of_realname_verification/');?>",
                    'server_tag': "photo_id",
                    'server_img': <?= json_encode($serverImg['photo_id'], JSON_UNESCAPED_SLASHES) ?>,
                    'is_enable': "<?= $this->utils->isEnabledFeature('show_player_upload_realname_verification') ? 1 : 0 ?>",
                    'is_verified': "<?= (!$player_verification['verified'] && $player_verification['count_proof_attachment']['photo_id']['allowUpload']) ? 0 : 1 ?>"
                },
                'address': {
                    'modal-title': "<?= lang('Security') ?>",
                    'modal-title-tip': "<?= lang('Proof of Address Verification') ?>",
                    'form_action': "<?= site_url('/player_center2/security/upload_proof_of_address/');?>",
                    'server_tag': "address",
                    'server_img': <?= json_encode($serverImg['address'], JSON_UNESCAPED_SLASHES) ?>,
                    'is_enable': "<?= $this->utils->isEnabledFeature('show_player_upload_proof_of_address') ? 1 : 0 ?>",
                    'is_verified': "<?= (!$player_verification['verified_address'] && $player_verification['count_proof_attachment']['address']['allowUpload']) ? 0 : 1 ?>"
                },
                'deposit_withdrawal': {
                    'modal-title': "<?= lang('Security') ?>",
                    'modal-title-tip': "<?= lang('Proof of Deposit/Withdrawal') ?>",
                    'form_action': "<?= site_url('/player_center2/security/upload_proof_of_deposit_withdrawal/');?>",
                    'server_tag': "dep_wd",
                    'server_img': <?= json_encode($serverImg['dep_wd'], JSON_UNESCAPED_SLASHES) ?>,
                    'is_enable': "<?= $this->utils->isEnabledFeature('show_player_upload_proof_of_deposit_withdrawal') ? 1 : 0 ?>",
                    'is_verified': "<?= (!$player_verification['verified_dep_wd'] && $player_verification['count_proof_attachment']['dep_wd']['allowUpload']) ? 0 : 1 ?>"
                },
                'income': {
                    'modal-title': "<?= lang('Security') ?>",
                    'modal-title-tip': "<?= lang('Proof of Income Verification') ?>",
                    'form_action': "<?= site_url('/player_center2/security/upload_proof_of_income/');?>",
                    'server_tag': "income",
                    'server_img': <?= json_encode($serverImg['income'], JSON_UNESCAPED_SLASHES) ?>,
                    'is_enable': "<?= $this->utils->isEnabledFeature('show_player_upload_proof_of_income') ? 1 : 0 ?>",
                    'is_verified': "<?= (!$player_verification['verified_income'] && $player_verification['count_proof_attachment']['income']['allowUpload']) ? 0 : 1 ?>"
                }
            }
        }

        PlayerSecurity.uploadfile.options = verified_upload_file_options

        $('#uploadFile-modal').on("hidden.bs.modal",function(){
            PlayerSecurity.uploadfile.resetSetModal();
        });

        $('.uploaded-btn').click(function(){
            $(".input-upload-file").click()
        })

        // $('.set_passwd').find('input[type="password"]').focus(function () {
        //     $('.set_passwd').find('.div_user_pass_msg').empty();
        // })

        <?php if($this->utils->getConfig('open_verify_contactnumber_when_contactnumber_unverified') && $showVerifyContactnumber): ?>
            $('#security-mobile-input').modal('show');
        <?php endif; ?>

    });
</script>
<script>
    var passwd_len = <?= json_encode($passwd_len) ?>;
    var passwd_regex = /([A-Za-z]+[0-9]+)|([0-9]+[A-Za-z]+)[A-Za-z0-9]*/;
    var passwd_mesgs = {
        not_match  : "<?= lang('Must be the same with your password set above') ?>" ,
        length     : ("<?= lang('password.validation.lengthRangeStandard') ?>").replace('%s-%s', passwd_len.min + ' - ' + passwd_len.max) ,
        alnum      : "<?= lang('validation.contentPassword04') ?>"
    };
    var showSetPasswordSuccess = false;

    function setPassword_check(container, passwd, cpasswd) {
        try {
            if (passwd != cpasswd) {
                throw { code: 1, mesg: passwd_mesgs.not_match };
            }

            if (passwd.length < passwd_len.min || passwd.length > passwd_len.max) {
                throw { code: 2, mesg: passwd_mesgs.length };
            }

            if (!passwd_regex.test(passwd)) {
                throw { code: 3, mesg: passwd_mesgs.alnum };
            }
        }
        catch (ex) {
            console.log(ex);
            $(container).find('.div_user_pass_msg').append($('<div />', { 'class':'alert alert-danger', 'role':'alert' , 'html': ex.mesg }));
            $(container).find('input[type="password"]').val('');
            return false;
        }

        return true;
    }

</script>
<style type="text/css">
    .color-red {
        color:#FF0000;
    }
</style>
<script>
    function ResendEmail_wrapper() {
        var time_last_vemail = NaN;
        var verify_email_cd_interval = <?= $verify_email_cd_interval ?>;
        var resendEmail_countdown = null;

        // Executes on page load
        (function() {
            if (!time_last_vemail) {
                // console.log('time_last_vemail', time_last_vemail, 'cookie', $.cookie('time_last_vemail'));
                time_last_vemail = parseInt($.cookie('time_last_vemail'));
            }
            resendEmail_show_countdown();
        })();

        function resendEmail_time_left() {
            var t = new Date();
            var duration_to_last_vemail = (t.getTime() - time_last_vemail) / 1000;
            // console.log('duration_to_last_vemail', duration_to_last_vemail, 'time_last_vemail', time_last_vemail, 'now', t.getTime());
            return duration_to_last_vemail;
        }

        function resendEmail_set_cookie() {
            var t = new Date();
            time_last_vemail = t.getTime();
            $.cookie('time_last_vemail', time_last_vemail);
        }

        function resendEmail_show_countdown() {
            var time_left = resendEmail_time_left();
            if (isNaN(time_left) || verify_email_cd_interval <= 0 || time_left > verify_email_cd_interval) {
                $('#resend_email_btn_countdown').text('');
                $('#resend_email_btn').removeAttr('disabled');
                $('#resend_otp_btn_countdown').text('');
                $('#resend_otp_btn').removeAttr('disabled');
                clearTimeout(resendEmail_countdown);
                return;
            }

            var time_left_sec = verify_email_cd_interval - Math.ceil(time_left);
            var text_countdown = '(' + time_left_sec + ')';
            $('#resend_email_btn_countdown').text(text_countdown);
            $('#resend_email_btn').attr('disabled', 1);
            $('#resend_otp_btn_countdown').text(text_countdown);
            $('#resend_otp_btn').attr('disabled', 1);

            resendEmail_countdown = setTimeout(resendEmail_show_countdown, 1000);
        }

        function resendEmail() {
            PlayerSecurity.resendEmail();
            if(PlayerSecurity.IS_EMAIL_FILLED_IN) {

                resendEmail_set_cookie();

                resendEmail_show_countdown();
            }

            return;
        }

        return { resendEmail: resendEmail };
    }

    var resendEmail_wrapper = new ResendEmail_wrapper();
</script>
<?php if($force_reset_password) : ?>
<script type="text/javascript">
    $(function(){
        $("#security-loginpassword").attr('data-backdrop', 'static');
        $("#security-loginpassword").modal('show');
        $('#loginpassword-cancel').remove();
    });
</script>
<?php endif; ?>