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
    #bank_list_search_input{
        padding: 10px;
        width: 100%;
        margin-bottom: 5px;
    }
</style>
<input type="hidden" id="bankDetailId" name="input-bank-detail-id" value="<?=$playerBankDetail->playerBankDetailsId?>" />
<input type="hidden" id="bankTypeId" name="input-bank-type-id" value="<?=$playerBankDetail->bankTypeId?>" />

<div class="fmd-step1">
    <input type="text" name="bank_list_search_input" id="bank_list_search_input" placeholder="<?=lang('Search Bank')?>">
    <ul id="allBankList" class="bank-list bank-list-main">
        <?php if(!empty($preferredBankTypeList)) : ?>
            <?php foreach($preferredBankTypeList as $bankType) : ?>
                <?php if($bankType->payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_BANK ||
                         $bankType->payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_PIX) : ?>
                    <li data-bank-name="<?=lang($bankType->bankName)?>" data-bank-type-id="<?=$bankType->bankTypeId?>" data-bank-code="<?=$bankType->bank_code?>" class="<?=($bankType->bankTypeId == $playerBankDetail->bankTypeId) ? 'active': ''?>">
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

    <!-- PIX Type -->
    <?php if($this->utils->getConfig('enable_cpf_number')): ?>
        <?php if($this->utils->getConfig('switch_cpf_type')): ?>
            <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                <label for="inputPixtype" class="control-label"><span><?= lang("financial_account.pixtype") ?>:</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="input-pixtype" id="inputPixType"
                    data-required-error="<?=lang('Fields with (*) are required.')?>"
                    data-error="<?=lang('text.error')?>"
                    value="<?=$playerBankDetail->pixType?>"
                    readonly
                    />
                </div>
            </div>
        <?php else :?>
            <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                <label for="inputPixtype" class="control-label"><span><?= lang("financial_account.pixtype") ?>:</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="input-pixtype" id="inputPixType" value="<?=$playerBankDetail->pixType?>"
                    data-required-error="<?=lang('Fields with (*) are required.')?>"
                    data-error="<?=lang('text.error')?>"
                    readonly
                    />
                </div>
            </div>
        <?php endif;?>
    <?php endif;?>

    <!-- Account -->
    <?php if($this->utils->getConfig('enable_cpf_number')): ?>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip hidden"
        >
    <?php else: ?>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip"
        >
    <?php endif;?>
            <label for="inputAccNum" class="control-label">
                <span><?= lang("financial_account.bankaccount") ?>:</span>
            </label>
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

    <!-- CPF Number -->
    <?php if($this->utils->getConfig('enable_cpf_number')): ?>
        <?php if($this->utils->getConfig('switch_cpf_type')): ?>
            <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                <label for="inputPixKey" class="control-label"><span class="pix_key_label"><?= lang("financial_account.CPF_number") ?>:</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="input-pixkey" id="inputPixKey"
                    data-required-error="<?=lang('Fields with (*) are required.')?>"
                    data-error="<?=lang('text.error')?>"
                    required="required"
                    value="<?=$playerBankDetail->pixKey?>"
                    readonly
                    />
                </div>
                <?=validator_input_tip_builder('pixkey', lang("financial_account.pixkey"), $account_validator['pixkey'])?>
            </div>
        <?php else :?>
            <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                <label for="inputPixKey" class="control-label"><span><?= lang("financial_account.CPF_number") ?>:</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="input-pixkey" id="inputPixKey" value="<?=$playerBankDetail->pixKey?>"
                    data-required-error="<?=lang('Fields with (*) are required.')?>"
                    data-error="<?=lang('text.error')?>"
                    required="required"
                    readonly
                    />
                </div>
                <?=validator_input_tip_builder('pixkey', lang("financial_account.pixkey"), $account_validator['pixkey'])?>
            </div>
        <?php endif;?>
    <?php endif;?>

    <!-- Area/Province -->
    <?php if(in_array(Financial_account_setting::FIELD_BANK_AREA, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
            <label for="inputProvince" class="control-label"><span><?= lang("financial_account.province") ?>:</span></label>
            <div class="input-group"></div>
            <select class="form-control province" name="input-province" id="inputProvince" data-value="<?=$playerBankDetail->province?>" <?=(in_array(Financial_account_setting::FIELD_BANK_AREA, $account_validator['field_required'])) ? 'required' : ''?>>
                <option value=""><?=lang('please_select')?></option>
            </select>
            <?=validator_input_tip_builder('province', lang("financial_account.province"), $account_validator['area'])?>
        </div>
    <?php endif;?>

    <!-- Area/City -->
    <?php if(in_array(Financial_account_setting::FIELD_BANK_AREA, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
            <label for="inputCity" class="control-label"><span><?= lang("financial_account.city") ?>:</span></label>
            <div class="input-group"></div>
            <select class="form-control city" name="input-city" id="inputCity" data-value="<?=$playerBankDetail->city?>" <?=(in_array(Financial_account_setting::FIELD_BANK_AREA, $account_validator['field_required'])) ? 'required' : ''?>>
                <option value=""><?=lang('please_select')?></option>
            </select>
            <?=validator_input_tip_builder('city', lang("financial_account.city"), $account_validator['area'])?>
        </div>
    <?php endif;?>

    <!-- Branch -->
    <?php if(in_array(Financial_account_setting::FIELD_BANK_BRANCH, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
            <label for="inputBranch" class="control-label"><span><?= $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('financial_account.branch') ?>:</span></label>
            <div class="input-group">
                <input type="text" class="form-control" name="input-branch" id="inputBranch" value="<?=$playerBankDetail->branch?>"
                    <?=validator_rule_builder('branch', $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('financial_account.branch'), $account_validator['branch'], 'html')?>
                    <?=(in_array(Financial_account_setting::FIELD_BANK_BRANCH, $account_validator['field_required'])) ? 'required' : ''?>
                />
            </div>
            <?=validator_input_tip_builder('branch', $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('financial_account.branch'), $account_validator['branch'])?>
        </div>
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

    <!-- Bank Address -->
    <?php if(in_array(Financial_account_setting::FIELD_BANK_ADDRESS, $account_validator['field_show'])) :?>
        <div class="col-md-12 top-buffer form-group has-feedback has-input-tip">
            <label for="inputAddress" class="control-label"><span><?= lang("financial_account.address") ?>:</span></label>
            <div class="input-group">
                <input type="text" class="form-control" name="input-address" id="inputAddress" value="<?=$playerBankDetail->bankAddress?>"
                    <?=validator_rule_builder('bankAddress', lang("financial_account.address"), $account_validator['bankAddress'], 'html')?>
                    <?=(in_array(Financial_account_setting::FIELD_BANK_ADDRESS, $account_validator['field_required'])) ? 'required' : ''?>
                />
            </div>
            <?=validator_input_tip_builder('bankAddress', lang("financial_account.address"), $account_validator['bankAddress'])?>
        </div>
    <?php endif;?>

    <!-- SMS -->
    <?php if($this->utils->getConfig('enable_sms_verify_in_add_bank_account')): ?>
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
    if(ENABLE_SWITCH_CPF_TYPE == 'true'){
        PlayerBankaccount.switch_cpf_type_data = JSON.parse('<?=json_encode($playerBankDetail->pixTypeData)?>');
    }

    $(function() {
        SMS_VERIFY_SUCCESSE = false;
        if(ENABLE_SMS_VERIFY_IN_ADD_BANK_ACCOUNT == 'true' && SUBMIT_PAYMENT_TYPE_FLAG == 1){
          $(".submit-add-bank-account").attr('disabled', true);
          $("#fields").addClass("bank-list");
        }
    });

    var enable_new_sms_setting = '<?= !empty($this->utils->getConfig('use_new_sms_api_setting')) ? true : false ?>';
    var countdown,
        smsValidBtn = $('#send_sms_verification_code'),
        smstextBtn  = smsValidBtn.text();
    function send_verification_code() {
        var dialing_code    = "<?=$player_contact_info['dialing_code']?>";
        var successMsg      = "<?=sprintf(lang('msg.send.verification'),$this->utils->keepOnlyString($player_contact_info['contactNumber'],6)) ?>";
        var numEmptyMessage = "<?=lang("Please update phone number to add bank account") ?>";

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

    function smsCodeVerify(){
        var sms_verification_code = $('#fm-verification-code').val();
        var errorVerifySMSCode = "<?= lang("Verify SMS Code Failed") ?>";
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