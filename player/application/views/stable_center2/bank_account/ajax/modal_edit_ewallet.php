<style>
    /* remove input type=number spinner*/
    .remove_number_spinner::-webkit-outer-spin-button,
    .remove_number_spinner::-webkit-inner-spin-button{
        -webkit-appearance: none !important;
        margin: 0;
    }
    .remove_number_spinner{
        -moz-appearance: textfield;
    }
</style>
<input type="hidden" id="bankDetailId" name="input-bank-detail-id" value="<?=$playerBankDetail->playerBankDetailsId?>" />
<input type="hidden" id="bankTypeId" name="input-bank-type-id" value="<?=$playerBankDetail->bankTypeId?>" />

<div class="fmd-step1">
    <ul id="allBankList" class="bank-list bank-list-main">
        <?php if(!empty($preferredBankTypeList)) : ?>
            <?php foreach($preferredBankTypeList as $bankType) : ?>
                <?php if($bankType->payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET): ?>
                    <li data-bank-name="<?=lang($bankType->bankName)?>" data-bank-type-id="<?=$bankType->bankTypeId?>" class="<?=($bankType->bankTypeId == $playerBankDetail->bankTypeId) ? 'active': ''?>">
                        <a href="#<?=$bankType->bankTypeId?>" data-toggle="tab">
                            <i class="fa fa-check-circle" aria-hidden="true"></i>
                            <?=Banktype::renderBankEntry($bankType->bankTypeId, lang($bankType->bankName), $bankType->bankIcon)?>
                        </a>
                    </li>
                <?php endif;?>
            <?php endforeach ?>
        <?php endif ?>
    </ul>

    <?php if(!$this->utils->getConfig('enabled_hidden_financial_account_banklist_show_hide_btn')) : ?>
    <div class="show-btn" onclick="expandBankList();"><i class="fa fa-angle-down"></i> <?= lang('show Bank list') ?></div>
    <div class="hide-btn" style="display: none;" onclick="shrinkBankList();"><i class="fa fa-angle-up"></i> <?= lang('hide Bank list') ?></div>
    <?php endif ?>

    <hr />
    <div class="selected-bank">
        <span><?=lang('Your current selected bank')?></span>
        <div class="bank-entry active d-inline">
            <span class="b-icon">
                &nbsp;<?=lang('No bank selected'); ?>
            </span>
        </div>
    </div>
    <div class="clearfix"></div>

    <hr />
</div>

<div id="fields" class="row">
    <!-- Account -->
    <?php if(!$this->utils->getConfig('hide_financial_account_ewallet_account_number')): ?>
    <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
        <label for="inputAccNum" class="control-label"><span><?= lang("financial_account.bankaccount") ?>:</span></label>
        <div class="input-group">
            <?php if($account_validator['only_allow_numeric']):?>
                <input type="number" class="form-control remove_number_spinner" name="input-acct-num" id="inputAccNum" value="<?=$playerBankDetail->bankAccountNumber?>"
                    <?=validator_rule_builder('bankAccountNumber', lang("financial_account.bankaccount"), $account_validator['bankAccountNumber'], 'html')?>
                />
            <?php else: ?>
                <input type="text" class="form-control" name="input-acct-num" id="inputAccNum" value="<?=$playerBankDetail->bankAccountNumber?>"
                    <?=validator_rule_builder('bankAccountNumber', lang("financial_account.bankaccount"), $account_validator['bankAccountNumber'], 'html')?>
                />
            <?php endif;?>
            <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
        </div>
        <?=validator_input_tip_builder('bankAccountNumber', lang("financial_account.bankaccount"), $account_validator['bankAccountNumber'])?>
    </div>
    <?php endif; ?>

    <!-- Name -->
    <?php if(in_array(Financial_account_setting::FIELD_NAME, $account_validator['field_show'])) :?>
        <?php if($this->config->item('enabled_set_realname_when_add_bank_card')) :?>
            <?php $nameOrder = ['firstName','lastName']; ?>
            <?php if ($this->utils->getConfig('switch_last_name_order_before_first_name')) {
                $nameOrder = ['lastName','firstName'];
            }?>
            <?php
            foreach ($nameOrder as $item) {
                switch ($item) {
                    case 'firstName': ?>
                            <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                                <label for="inputFirstName" class="control-label"><span><?= lang("First Name") ?>:</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="input-first-name" id="inputFirstName" value="<?=$playerBankDetail->bankAccountFirstName?>"
                                        <?=validator_rule_builder('bankAccountFullName', lang("First Name"), $account_validator['bankAccountFullName'], 'html')?>
                                        <?=empty($playerBankDetail->bankAccountFirstName) ? '' : 'readonly'?>
                                        <?=(in_array(Financial_account_setting::FIELD_NAME, $account_validator['field_required'])) ? 'required' : ''?>
                                    />
                                </div>
                                <?=validator_input_tip_builder('bankAccountFullName', lang("First Name"), $account_validator['bankAccountFullName'])?>
                            </div>
                            <?php break;
                    case 'lastName': ?>
                        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                            <label for="inputLastName" class="control-label"><span><?= lang("Last Name") ?>:</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="input-last-name" id="inputLastName" value="<?=$playerBankDetail->bankAccountLastName?>"
                                    <?=validator_rule_builder('bankAccountFullName', lang("Last Name"), $account_validator['bankAccountFullName'], 'html')?>
                                    <?=empty($playerBankDetail->bankAccountLastName) ? '' : 'readonly'?>
                                    <?=(in_array(Financial_account_setting::FIELD_NAME, $account_validator['field_required'])) ? 'required' : ''?>
                                />
                            </div>
                            <?=validator_input_tip_builder('bankAccountFullName', lang("Last Name"), $account_validator['bankAccountFullName'])?>
                        </div>
                        <?php break;
                    }
            }?>
        <?php else :?>
            <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                <label for="inputAccName" class="control-label"><span><?= lang("financial_account.name") ?>:</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="input-acct-name" id="inputAccName" value="<?=$playerBankDetail->bankAccountFullName?>"
                        <?=validator_rule_builder('bankAccountFullName', lang("financial_account.name"), $account_validator['bankAccountFullName'], 'html')?>
                        <?=($account_validator['allow_modify_name']) ? '' : 'readonly'?>
                        <?=(in_array(Financial_account_setting::FIELD_NAME, $account_validator['field_required'])) ? 'required' : ''?>
                    />
                </div>
                <?=validator_input_tip_builder('bankAccountFullName', lang("financial_account.name"), $account_validator['bankAccountFullName'])?>
            </div>
        <?php endif;?>
    <?php endif;?>

    <!-- Phone -->
    <?php if(in_array(Financial_account_setting::FIELD_PHONE, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
            <label for="inputMobileNum" class="control-label"><span><?= lang("financial_account.phone") ?>:</span></label>
            <div class="input-group">
                <input type="number" class="form-control remove_number_spinner" name="input-mobile-num" id="inputMobileNum" value="<?=$playerBankDetail->phone?>"
                    <?=validator_rule_builder('phone', lang("financial_account.phone"), $account_validator['phone'], 'html')?>
                    <?=(in_array(Financial_account_setting::FIELD_PHONE, $account_validator['field_required'])) ? 'required' : ''?>
                />
            </div>
            <?=validator_input_tip_builder('phone', lang("financial_account.phone"), $account_validator['phone'])?>
        </div>
    <?php endif;?>

    <!-- SMS -->
    <?php if($this->utils->getConfig('enable_sms_verify_in_add_ewallet')): ?>
        <div class="col-md-6"></div>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                <?= $this->CI->load->widget('sms'); ?>
                <label for="fm-verification-code" class="control-label"><span><?= lang('Please enter verification code:') ?></span></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="fm-verification-code" name="input-verification-code" required>
                </div>
                <?=validator_input_tip_builder('smsVerificationCode', lang("cashier.enter.sms"), $account_validator['smsVerificationCode'])?>
            <div class="top-buffer form-group has-feedback has-input-tip">
                <button type="button" class="btn mc-btn top-buffer" id="send_sms_verification_code" onclick="send_verification_code();"><?= lang('Send SMS Verification') ?></button>
                <button type="button" class="btn mc-btn top-buffer" id="submit_sms_verification" onclick="smsCodeVerify();"><?= lang('submit verify sms code') ?></button>

                <input type="text" id="send_verification_by" hidden="true" value="">
                <input type="text" id="verify_phone" hidden="true" value="<?=$player_contact_info['verified_phone']?>">
                <div class="modal-body">
                    <p id="sms_verification_msg"></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
    $(function() {
        SMS_VERIFY_SUCCESSE = false;
        if(ENABLE_SMS_VERIFY_IN_ADD_EWALLET == 'true' && SUBMIT_PAYMENT_TYPE_FLAG == 2){
          $(".submit-add-bank-account").attr('disabled', true);
          $("#fields").addClass("bank-list");
        }
    });

    var enable_new_sms_setting = '<?= !empty($this->utils->getConfig('use_new_sms_api_setting')) ? true : false ?>';
    var countdown,
        smsValidBtn = $('#send_sms_verification_code'),
        smstextBtn  = smsValidBtn.text();
    function send_verification_code( successMsg , numEmptyMessage , dialingCode) {
        var dialing_code = dialingCode;

        SMS_SendVerify(function(sms_captcha_val) {
            var smsSendSuccess = function() {
                    $('#sms_verification_msg').text(successMsg);
                },
                smsSendFail = function(data=null) {
                    if (data && data.hasOwnProperty('isDisplay') && data['message']) {
                        $('#sms_verification_msg').text(data['message']);
                    } else {
                        $('#sms_verification_msg').text('<?= lang("SMS failed")?>');
                    }
                },
                smsCountDown = function() {
                    var smsCountdownnSec = 60;
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
                    } else {
                        smsValidBtn.prop('disabled', false);
                    }
                };

            disableSendBtn(true);
            var verificationUrl = this.document.location.origin + '/iframe_module/iframe_register_send_sms_verification/';

            if (enable_new_sms_setting) {
                verificationUrl = '<?= site_url('iframe_module/iframe_register_send_sms_verification/null/sms_api_bankinfo_setting')?>';
            }

            $.post(verificationUrl, {
                sms_captcha: sms_captcha_val,
                dialing_code: dialing_code
            }).done(function(data){
                (data.success) ? smsSendSuccess() : smsSendFail(data);
                var msg = (data.success) ? successMsg : data.message;
                $('#sms_verification_msg').text(msg);
                if (data.hasOwnProperty('field') && data['field'] == 'captcha') {
                    disableSendBtn(false)
                } else {
                    smsCountDown();
                }
            }).fail(function(){
                smsSendFail();
                smsCountDown();
            });
        });
    }

    function smsCodeVerify(errorVerifySMSCode){
        var sms_verification_code = $('#fm-verification-code').val();
        $('#sms_verification_msg').empty();
        show_loading();

        if(!sms_verification_code || sms_verification_code == '') {
            $('#sms_verification_msg').text(errorVerifySMSCode);
            stop_loading();
        }

        var verificationUrl = '/iframe_module/update_sms_verification/' + 'verified' +'/'+ sms_verification_code + '/1';

        if (enable_new_sms_setting) {
            verificationUrl = '/iframe_module/update_sms_verification/' + 'verified' +'/'+ sms_verification_code + '/sms_api_bankinfo_setting';
        }

        $.getJSON(document.location.origin+verificationUrl, function(data){
            if(data.success){
               $(".submit-add-bank-account").attr('disabled', false);
               $('#sms_verification_msg').text(data.message);
               SMS_VERIFY_SUCCESSE = true;
            }
            else {
               $('#sms_verification_msg').text(data.message);
            }
        }).always(function(){
            stop_loading();
        });
    }
</script>