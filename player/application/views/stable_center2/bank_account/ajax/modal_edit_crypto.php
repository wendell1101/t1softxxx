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
                <?php if($bankType->payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO): ?>
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

    <?php
        if ($this->utils->getConfig('enable_custom_crypto_bank_lang')) {
            $lang_show_bank_list = lang('show Bank list crypto');
            $lang_hide_bank_list = lang('hide token list');
            $current_selected_bank = lang('Your current selected bank crypto');
            $bankAccountLang = lang("financial_account.bankaccount.walletaddress");
            $financial_account_name = lang('financial_account.name.crypto');
        }else{
            $lang_show_bank_list = lang('show Bank list');
            $lang_hide_bank_list = lang('hide Bank list');
            $current_selected_bank = lang('Your current selected bank');
            $bankAccountLang = lang("financial_account.bankaccount");
            $financial_account_name = lang("financial_account.name");
        }
    ?>

    <?php if(!$this->utils->getConfig('enabled_hidden_financial_account_banklist_show_hide_btn')) : ?>
    <div class="show-btn" onclick="expandBankList();"><i class="fa fa-angle-down"></i> <?= $lang_show_bank_list ?></div>
    <div class="hide-btn" style="display: none;" onclick="shrinkBankList();"><i class="fa fa-angle-up"></i> <?= $lang_hide_bank_list ?></div>
    <?php endif ?>

    <hr />
    <div class="selected-bank">
        <span><?= $current_selected_bank ?></span>
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
    <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
        <label for="inputAccNum" class="control-label"><span><?= $bankAccountLang ?>:</span></label>
        <div class="input-group">
            <?php if($account_validator['only_allow_numeric']):?>
                <input type="number" class="form-control remove_number_spinner" name="input-acct-num" id="inputAccNum" value="<?=$playerBankDetail->bankAccountNumber?>"
                    <?=validator_rule_builder('bankAccountNumber', $bankAccountLang, $account_validator['bankAccountNumber'], 'html')?>
                />
            <?php else: ?>
                <input type="text" class="form-control" name="input-acct-num" id="inputAccNum" value="<?=$playerBankDetail->bankAccountNumber?>"
                    <?=validator_rule_builder('bankAccountNumber', $bankAccountLang, $account_validator['bankAccountNumber'], 'html')?>
                />
            <?php endif;?>
            <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
        </div>
        <?=validator_input_tip_builder('bankAccountNumber', $bankAccountLang, $account_validator['bankAccountNumber'])?>
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

    <!-- Crypto Networks -->
    <?php if(in_array(Financial_account_setting::FIELD_NETWROK, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
            <label for="inputCryptoNetwork" class="control-label"><span><?= lang("financial_account.crypto_network_select") ?>:</span></label>
            <div class="input-group">
                <select class="form-control crypto_network" name="input-cryptonetwork" id="inputCryptoNetwork" data-value="<?=$playerBankDetail->branch?>" <?=(in_array(Financial_account_setting::FIELD_NETWROK, $account_validator['field_required'])) ? 'required' : ''?>>
                    <option value=""><?=lang('please_select')?></option>
                </select>
            </div>
            <?=validator_input_tip_builder('cryptonetwork', lang("financial_account.crypto_network"), $account_validator['cryptonetwork'])?>
        </div>
        <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
            <label class="control-label">
                <span style="color: red;"><?= lang("financial_account.crypto_network_note") ?></span>
            </label>
        </div>
    <?php endif;?>


    <!-- SMS -->
    <?php if($this->utils->getConfig('enable_sms_verify_in_add_crypto_bank_account')): ?>
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


    <?php if(!empty($this->utils->getConfig('enable_crypto_details_in_crypto_bank_account'))): ?>
        <div class="crypto_bank_detail hide">
            <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                <label for="inputCryptoName" class="control-label"><span><?= lang("financial_account.cryptousername") ?>:</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="input-crypto-name" id="inputCryptoName" value=""
                        <?=validator_rule_builder('bankAccountCryptoName', lang("financial_account.cryptousername"), $account_validator['bankAccountCryptoName'], 'html')?>
                    />
                </div>
                <?=validator_input_tip_builder('bankAccountCryptoName', lang("financial_account.cryptousername"), $account_validator['bankAccountCryptoName'])?>
            </div>

            <div class="col-md-6 top-buffer form-group has-feedback has-input-tip">
                <label for="inputCryptoEmail" class="control-label"><span><?= lang("financial_account.cryptoemail") ?>:</span></label>
                <div class="input-group">
                    <input type="email" class="form-control" name="input-crypto-email" id="inputCryptoEmail" value=""
                    />
                </div>
            </div>
        </div>
    <?php endif; ?>
<script>

    PlayerBankaccount.enable_crypto_details = '<?=json_encode($this->utils->getConfig('enable_crypto_details_in_crypto_bank_account'))?>';

    $(function() {
        SMS_VERIFY_SUCCESSE = false;
        if(ENABLE_SMS_VERIFY_IN_ADD_CRYPTO_BANK_ACCOUNT == 'true' && SUBMIT_PAYMENT_TYPE_FLAG == 3){
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