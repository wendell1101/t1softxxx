<div id="withdrawal" class="panel">
    <div id="fm-withdrawal" class="panel-body withdrawal-form">
        <form name="withdrawForm" role="form" action="<?=site_url('player_center2/withdraw/verify')?>" method="POST">
            <?=$double_submit_hidden_field?>
            <input type="hidden" name="type" value="<?=$type?>">
            <?php
                $step = 1;
                foreach ($display_items as $key => $item) {
                    switch ($item) {
                        case 'RECEIVIING_ACCOUNT': ?>
                            <div class="form-group select-player-withdrawal-bank-account select-player-withdrawal-bank-account-wrapper">
                                <p class="withdraw_label">
                                    <img src="<?= $this->utils->getSystemUrl('player', 'stable_center2/img/icons/withdraw_icon.svg'); ?>">
                                    <label class="control-label"> <?= lang('Please select receiving account') ?><em style="color:red">*</em></label>
                                </p>

                                <?php if(empty($playerBankDetails)) :
                                    $bankTypeId = $bankDetailsId = $bankName = $bankAccNum = $bankAccName = $bankAddress = $bankBranch = $mobileNum = $bank_icon_url = '';
                                    ?>
                                <?php else :
                                    $bankTypeId = $playerDefaultBankDetail['bankTypeId'];
                                    $bankDetailsId = $playerDefaultBankDetail['playerBankDetailsId'];
                                    $bankName = $playerDefaultBankDetail['bankName'];
                                    $bankAccNum = $playerDefaultBankDetail['bankAccountNumber'];
                                    $bankAccName = $playerDefaultBankDetail['bankAccountFullName'];
                                    $bankAddress = $playerDefaultBankDetail['bankAddress'];
                                    $bankBranch = $playerDefaultBankDetail['branch'];
                                    $bankBranch = $bankBranch ?: '';
                                    $mobileNum = $playerDefaultBankDetail['phone'];
                                    $mobileNum = $mobileNum ?: '';
                                    $bank_icon_url = isset($playerDefaultBankDetail['bank_icon_url']) ? $playerDefaultBankDetail['bank_icon_url'] : '';
                                endif; ?>

                                <div class="form-group player-bank-account-content">
                                    <div class="current-bank-form">
                                        <div class="select_form">
                                            <div class="dropdown current-bank-list withdraw_bank_dropdown">
                                                <button class="btn btn-warning dropdown-toggle" type="button" id="choose_bank_account_toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span>
                                                        <?php if(!empty($playerDefaultBankDetail)):?>
                                                            <?=lang($bankName) . ' (' . $bankAccNum . ' - ' . lang($bankAccName) ?>
                                                        <?php else:?>
                                                            <?=lang('Select other bank account')?>
                                                        <?php endif;?>
                                                    </span>
                                                    <span class="caret"></span>
                                                </button>

                                                <ul class="dropdown-menu" aria-labelledby="choose_bank_account_toggle" id="currentBankList">
                                                    <?php if(!empty($playerBankDetails)):?>
                                                    <?php foreach($playerBankDetails as $bank): ?>
                                                        <li class="<?=$bank['isDefault']?'active':''?>"
                                                            data-id="<?= $bank['playerBankDetailsId'] ?>"
                                                            data-bank-type-id="<?= $bank['bankTypeId'] ?>"
                                                            data-bank-name="<?= lang($bank['bankName']) ?>"
                                                            data-bank-code="<?= $bank['bank_code'] ?>"
                                                            data-is-crypto="<?= $bank['is_crypto'] ?>"
                                                            data-branch="<?= lang($bank['branch']) ?>"
                                                            data-province="<?= lang($bank['province']) ?>"
                                                            data-city="<?= lang($bank['city']) ?>"
                                                            data-acc-num="<?= $bank['bankAccountNumber'] ?>"
                                                            data-acc-name="<?= lang($bank['bankAccountFullName']) ?>"
                                                            data-mobile-num="<?= lang($bank['phone']) ?>"
                                                            data-default="<?=$bank['isDefault']?>"
                                                            role="presentation" value="<?=$bank['playerBankDetailsId']?>"
                                                        >
                                                            <a href="javascript: void(0);" role="menuitem">
                                                                <?=lang($bank['bankName']) . ' (' . $bank['bankAccountNumber'] . ' - ' . lang($bank['bankAccountFullName']) ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                    <?php endif;?>
                                                </ul>
                                                <div class="hidden">
                                                    <button type="button" class="btn btn-primary" id="saveChosenBank"><?= lang("lang.save") ?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="withdraw_bank_details">
                                        <p class='<?=(!empty($bankAccNum)) ? '' : 'hide'?> <?=($this->utils->isEnabledFeature("hidden_player_bank_account_number_in_the_withdraw")) ? 'hidden' : ''?>'>
                                            <strong id='acctnumber_label'><?= lang('player.withdrawal.acctnumber') ?></strong>
                                            <span id="activeAccNum" class="selectAccNum"><?= $bankAccNum ?></span>
                                        </p>
                                        <p class='<?= (!empty($bankAccName)) ? '' : 'hide'?>'>
                                            <strong><?= lang('player.withdrawal.acctname') ?> </strong>
                                            <span id="activeAccName" class="selectAccName"><?= $bankAccName ?></span>
                                        </p>
                                    </div>

                                    <div class="hidden">
                                        <div class="dispBankInfo <?= !empty($playerDefaultBankDetail) ? '' : 'hide'; ?>" style="font-size:13px;">
                                            <input type="hidden" id="activeBankTypeIdField" name="bankTypeId" value='' />
                                            <input type="hidden" id="activeBankDetailsIdField" name="bankDetailsId" value='<?= $bankDetailsId ?>' />
                                            <input type="hidden" id="activeAccNameField" name="bankAccName" value='' />
                                            <input type="hidden" id="activeBankCodeField" name="bankCode" value='' />
                                            <input type="hidden" id="activeAccNumField" name="bankAccNum" value='' />
                                            <input type="hidden" id="activeBankAddressField" name="bankAddress" value='' />
                                            <input type="hidden" id="activeCityField" name="city" value='' />
                                            <input type="hidden" id="activeProvinceField" name="province" value='' />
                                            <input type="hidden" id="activeBranchField" name="branch" value='' />
                                            <input type="hidden" id="activeMobileNumField" name="phone" value='' />
                                            <input type="hidden" id="activeRate" name="rate" value='' />
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="clearfix"></div>
                            <br>
                        <?php break;

                        case 'WITHDRAWAL_AMOUNT': ?>
                            <div class="form-group has-feedback withdrawal-amount-input-wrapper">
                                <p class="withdraw_label">
                                    <img src="<?= $this->utils->getSystemUrl('player', 'stable_center2/img/icons/withdraw_amount.svg'); ?>">
                                    <label class="custom-withdrawal-amount-label"> <?= lang('Please Input Withdrawal Amount') ?><em style="color:red">*</em></label>
                                </p>
                                <div class="withdraw_amount">
                                    <label><?= lang('Amount') ?><em style="color:red">*</em></label>
                                    <div class="input-group">
                                        <?php if($this->CI->config->item('enable_currency_symbol_in_the_withdraw_amount')):?>
                                        <span class="input-group-addon"><?= $this->utils->getCurrencyLabel()['currency_symbol'] ?></span>
                                        <?php endif; ?>
                                        <?php
                                            $withdraw_placehoder = lang('Amount') .
                                                ' MIN:' . $this->utils->formatCurrency($withdrawSetting['min_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'))
                                                . ' / MAX ' . $this->utils->formatCurrency($withdrawSetting['max_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'));
                                        ?>
                                        <?php if($this->CI->config->item('enable_thousands_separator_in_the_withdraw_amount')): ?>
                                            <input type="text" id="thousands_separator_amount" class="form-control" name="thousands_separator_amount"
                                                <?php if (lang('Please Input Withdrawal Amount For Placeholder')) : ?>
                                                    placeholder="<?= $withdraw_placehoder ?>"
                                                <?php endif; ?>
                                                style="width:260px"
                                                onChange = "display_thousands_separator()"
                                                onkeyup = "display_thousands_separator()"
                                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                                />
                                        <?php endif; ?>
                                        <input type="number" step="<?=$withdraw_amount_step_limit?>" class="form-control" id="amount" name="amount"
                                            <?php if (lang('Please Input Withdrawal Amount For Placeholder')) : ?>
                                                placeholder="<?= $withdraw_placehoder ?>"
                                            <?php endif; ?>
                                            min="<?=$withdrawSetting['min_withdraw_per_transaction']?>"
                                            max="<?=$withdrawSetting['max_withdraw_per_transaction']?>"
                                            data-min-error="<?=sprintf(lang('formvalidation.greater_than'), lang('Withdrawal Amount'), $this->utils->formatCurrency($withdrawSetting['min_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw')))?>"
                                            <?php if(isset($withdrawSetting['max_withdrawal_non_deposit_player']) && $withdrawSetting['max_withdrawal_non_deposit_player'] == 0 && $this->CI->config->item('display_hint_when_max_withdrawal_non_deposit_player')): ?>
                                                data-max-error="<?=lang('Almost there! You have not depoisted yet. Make a minimal deposit to withdraw!')?>"
                                            <?php else :?>
                                                data-max-error="<?=sprintf(lang('formvalidation.less_than'), lang('Withdrawal Amount'), $this->utils->formatCurrency($withdrawSetting['max_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw')))?>"
                                            <?php endif; ?>
                                            data-required-error="<?=lang('Fields with (*) are required.')?>"
                                            data-error="<?=lang('text.error')?>"
                                            <?php if($this->CI->config->item('disable_withdraw_amount_is_decimal')):?>
                                                data-step-error="<?=lang('notify.118')?>"
                                            <?php endif; ?>
                                            style="width:270px" required
                                            onKeyUp="crypto_converter_current_currency()" />
                                        <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
                                        <?php
                                        $withdraw_min_mx_text ='Min/Max Limit: ' .
                                            $this->utils->formatCurrency($withdrawSetting['min_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'))
                                            . '-' .
                                            $this->utils->formatCurrency($withdrawSetting['max_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'));
                                        ?>
                                        <?=lang('Withdraw.Amount.Note')?>
                                        <span class="withdraw_min_max"><?=$withdraw_min_mx_text?></span>
                                    </div>
                                    <div class="help-block with-errors"></div>

                                </div>

                                <div class="withdraw_amount">
                                    <label class="custom-withdrawal-amount-label"> <?= lang('Real Money') ?></label>
                                    <div class="withdraw_amount real_amount_area">
                                        <input class="withdraw_amount" type="text" class="form-control" id="real_amount" style="width:270px" disabled>
                                    </div>
                                </div>

                                <?php if ($this->utils->getConfig('enable_withdrawl_fee_from_player')) : ?>
                                    <p class="withdraw-fee"><?= lang('fee.withdraw') ?>&nbsp;:&nbsp;<span id="withdraw_fee"></span>&emsp;<span class="fee_hint"><?= lang('fee.withdraw.hint') ?></span></p>
                                <?php endif; ?>

                                <div class="helper-content">
                                    <p class="deposit-limit"><?=lang('Withdraw.Amount.Note')?></p>

                                </div>
                                <?php if ($this->utils->isEnabledFeature('enable_withdrawal_amount_note')) : ?>
                                    <div class="helper-content text-danger font-weight-bold" style="font-size:12px;">
                                        <p><?=lang('collection_withdrawal_amount')?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div id='crypto'>
                                <?php if(isset($cryptocurrencies)){
//                                    $step  = '3' ;
                                    if(isset($is_cryptocurrency) && $is_cryptocurrency){
                                        // $cryptocurrency_rate = $cryptocurrencies['USDT'];
                                        include __DIR__ . '/../../../../../bank_account/content/crypto_account/crypto_account_withdrawal.php';
                                    }
                                }?>

                                <?php
                                if (!empty($withdrawal_crypto_currency)) {
                                    if ($withdrawal_crypto_currency['enabled']) {
                                        include __DIR__ . '/../../../withdrawal_crypto_currency.php';
                                    }
                                }
                                ?>
                            </div>
                            <div class="clearfix"></div>
                            <?php break;

                        case 'WITHDRAW_VERIFICATION': ?>
                            <?php if($this->utils->getConfig('withdraw_verification') == 'withdrawal_password' && $enabled_withdrawal_password) : ?>
                                <div class="form-group withdraw-verification-wrapper">
                                    <p class="step">
                                        <span class='step-icon'><?=$step++?></span>
                                        <label class="control-label"><?= lang('Please Input Withdrawal Password') ?></label>
                                    </p>
                                    <input type="password" class="form-control" id="password" name="withdrawal_password" placeholder="<?= lang('Type your password') ?>" required>
                                    <input type="hidden" id="hasWithdrawPass" value="1">
                                </div>
                                <div class="clearfix"></div>
                                <br>
                            <?php endif ?>
                           <?php break;

                        case 'SMS_VERIFICATION': ?>
                            <?php if($this->utils->getConfig('enable_sms_verify_in_withdraw')): ?>
                            <div class="form-group has-feedback withdraw-sms-verification-wrapper">
                                <?= $this->CI->load->widget('sms'); ?>
                                <p class="step">
                                    <span class='step-icon'><?=$step++?></span>
                                    <label class="control-label"><?= lang('Please enter verification code:') ?></label>
                                </p>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="fm-verification-code" name="sms_verification_code" required>
                                </div>
                                <div class="top-buffer form-group has-feedback has-input-tip">
                                    <button type="button" class="btn mc-btn top-buffer" id="send_sms_verification_code" onclick="send_verification_code();"><?= lang('Send SMS Verification') ?></button>
                                    <input type="hidden" id="contact_number" value="<?=$player_contact_info['contactNumber']?>">
                                    <input type="hidden" id="dialing_code" value="<?=$player_contact_info['dialing_code']?>">
                                    <input type="hidden" id="verify_phone" value="<?=$player_contact_info['verified_phone']?>">
                                    <div class="modal-body msg-container">
                                        <p id="sms_verification_msg"></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php break;
                        default:
                            break;
                    }
                }
            ?>

            <?php if($this->CI->config->item('custom_withdrawal_amount_note')):?>
                <p><?=lang('Withdrawal Amount Note')?></p>
            <?php else :?>
                <p class="withdrawal_note bg bg-danger d-inline"><?=lang('Withdrawal Amount Note')?></p>
            <?php endif; ?>

            <div class="withdrawal-customize-content">
                <?php if($this->utils->getConfig('customize_content_withdrawal')) : ?>
                    <p><?=lang('customize_content_withdrawal')?></p>
                <?php else : ?>
                    <p><?=lang('withdrawal_category_customize_content')?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <input class="btn withdraw_button form-control" id="submitBtn" value="<?= lang('player.withdraw.submit') ?>" type="submit" />
                <div class="note_bar">
                    <p><?= lang('Withdraw pending ask customer service') ?></p>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- Redirect Success Modal -->
<div class="modal fade" id="redirect_success_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="clearfix">
                    <div class="col-md-12">
                        <div class="redirect_success_image">
                            <img class="custom_img" src="<?= $this->utils->getSystemUrl('player', 'stable_center2/images/custom_withdraw_success.png'); ?>">
                        </div>
                        <div class="redirect_success_message">
                            <p><?=lang('player.withdraw.custom_success_message.1')?></p>
                            <p><?=lang('player.withdraw.custom_success_message.2')?></p>
                        </div>
                        <div class="redirect_success_button">
                            <button type="button" class="btn btn-warning redirect"><?=lang('player.withdraw.custom_go_menu')?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Redirect Success Modal-->

<script type="text/javascript">
    var enabled_withdrawal_crypto = '<?=$withdrawal_crypto_currency['enabled']?>';
    var withdraw_cryptocurrencies = '<?=json_encode($withdrawal_crypto_currency['withdraw_cryptocurrencies'])?>';
    var player_crypto_account = JSON.parse('<?=json_encode(isset($player_crypto_account)?$player_crypto_account:[])?>');
    var enable_withdrawl_fee_from_player = '<?=$this->utils->getConfig('enable_withdrawl_fee_from_player')?>';
    var withdraw_cust_crypto_data = JSON.parse('<?=json_encode($cryptocurrencies)?>');
    var enable_sms_verified_phone_in_withdrawal = "<?= $this->utils->getConfig('enable_sms_verified_phone_in_withdrawal') ? '1' : '0' ?>";
    var player_verified_phone = "<?= $player_verified_phone ? '1' : '0' ?>";
    var enable_verified_email_in_withdrawal = "<?= $this->utils->getConfig('enable_verified_email_in_withdrawal') ? '1' : '0' ?>";
    var player_verified_email = "<?= $player_verified_email ? '1' : '0' ?>";
    var enable_withdrawl_bank_fee = JSON.parse('<?=json_encode($this->utils->getConfig('enable_withdrawl_bank_fee'))?>');
    var enable_third_party_api_id_when_withdraw = "<?= !empty($this->utils->getConfig('third_party_api_id_when_withdraw')) ? '1' : '0' ?>";
    var player_filled_birthday = "<?= $player_filled_birthday ? '1' : '0' ?>";
    var enable_show_pop_up_after_submnit_exist_errors = "<?= $this->utils->getConfig('enable_show_pop_up_after_submnit_exist_errors') ? '1' : '0' ?>";
    var submit_after_result_message = "<?= !isset($result['message'])? $result['message'] : '' ?>";
    var enabled_set_realname_when_add_bank_card = '<?= $this->config->item('enabled_set_realname_when_add_bank_card') ? '1' : '0' ?>';
    if (enable_show_pop_up_after_submnit_exist_errors == '1') {
        if(submit_after_result_message){
            MessageBox.danger(submit_after_result_message);
        }
    }

    var enable_thousands_separator_in_the_withdraw_amount =  '<?=$this->utils->getConfig('enable_thousands_separator_in_the_withdraw_amount')?>';
    var fx_rate = 0;
    var enable_currency = false;
    var homePageUrl= "<?=$this->utils->getSystemUrl('player')?>";

    $(document).ready(function(){
        $("#currentBankList li").on("click", function(){
            if($(this).attr('disabled')){
                return false;
            }

            $("#currentBankList li").removeClass('active');
            $(this).addClass('active')

            var str = $(this).text();
            var val = $(this).attr('value');

            $(this).parent().parent().parent().find(".dropdown-toggle span:first").html(str);
            $(this).parent().parent().parent().find(".field").val(val);

            $('#saveChosenBank').trigger('click');

            var acc_name = $('#currentBankList li.active').data('acc-name');
            if(!!acc_name && $(".selectAccName").parent("p").hasClass('hide')){
                $(".selectAccName").parent("p").removeClass('hide')
                $('.selectAccName').html(acc_name);
            }

        });

        var select_item = $("#amount");
        if(enable_thousands_separator_in_the_withdraw_amount){
            select_item = $("#thousands_separator_amount");
        }

        select_item.on("keyup", function() {
            var amount = $(this).val();
            var real_money = $("#real_amount");
            if (typeof amount != 'undefined' && amount != '') {
                if (enable_thousands_separator_in_the_withdraw_amount) {
                    let float_amount = amount.replace(/,/g, "");
                    let real_amt = (float_amount * 1000).toString();
                    let housands_separator_amount = _export_sbe_t1t.utils.displayInThousands(real_amt);
                    real_money.val(housands_separator_amount);
                }else{
                    if (amount > 0) {
                        let real_amt = (amount * 1000).toString();
                        real_money.val(real_amt).change();
                    }else{
                        real_money.val('');
                    }
                }
            }else{
                real_money.val('');
            }
        });

        var allow_popup = "<?= (!empty($result) && $result['success']) ? true : false ?>";
        if(allow_popup){
            $('#redirect_success_modal').modal('show');
        }

        $('.redirect').on('click', function(){
            window.location.href = homePageUrl;
        });
    });

    function roundToTwo(num) {
        return +(Math.round(num + "e+2")  + "e-2");
    }

    function addCommas(nStr){
        nStr += '';
        var x = nStr.split('.');
        var x1 = x[0];
        var x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    }


    if(enable_thousands_separator_in_the_withdraw_amount){
        $('#fm-withdrawal #amount').addClass('hide');
        $(document).on("change", "#fm-withdrawal #amount" , function() {
            $('#fm-withdrawal #amount').focus();
        });
    }

    if (enable_withdrawl_fee_from_player) {
        $(document).on("change", "#fm-withdrawal #amount" , function() {
            var playerId = '<?=$playerId?>';
            var levelId = '<?=$levelId?>';
            var amount = $(this).val();

            $.ajax({
                'url' : '/api/getWithdrawFee/' + playerId,
                'type' : 'POST',
                'dataType' : "json",
                'data': {'levelId' :levelId, 'amount' :amount},
                'success' : function(data){
                    if(data['success']){
                        $('#withdraw_fee').text(data.amount);
                    }
                }
            });
        });
    }

    if (enable_sms_verified_phone_in_withdrawal == '1') {
        if (player_verified_phone == '0') {
            MessageBox.danger("<?=lang('withdrawal.msg8')?>", undefined, function(){
                show_loading();
                window.location.href = '/player_center2/security#withdrawal';
            },
            [
                {
                    'text': '<?=lang('lang.settings')?>',
                    'attr':{
                        'class':'btn btn-primary',
                        'data-dismiss':"modal"
                    }
                }
            ]);
        }
    }

    if (enable_verified_email_in_withdrawal == '1') {
        if (player_verified_email == '0') {
            MessageBox.danger("<?=lang('withdrawal.msg9')?>", undefined, function(){
                show_loading();
                window.location.href = '/player_center2/security#withdrawal';
            },
            [
                {
                    'text': '<?=lang('lang.settings')?>',
                    'attr':{
                        'class':'btn btn-primary',
                        'data-dismiss':"modal"
                    }
                }
            ]);
        }
    }

    if (enable_third_party_api_id_when_withdraw == '1') {
        if(player_filled_birthday == '0'){
            MessageBox.info(lang('promo_custom.birthdate_not_set_yet'),'', function(){
                show_loading();
                window.location.href=EMPTY_ACCOUNT_NAME_REDIRECT_URL;
            }, [
                {
                    'text': '<?=lang('lang.settings')?>',
                    'attr':{
                        'class':'btn btn-primary',
                        'data-dismiss':"modal"
                    }
                }
            ]);
        }
    }

    function send_verification_code() {
        $('#sms_verification_msg').text('<?= lang("Please wait")?>');
        $(".msg-container").show().delay(2000).fadeOut();
        var smsValidBtn = $('#send_sms_verification'),
            smstextBtn  = smsValidBtn.text(),
            mobileNumber = $('#contact_number').val(),
            dialing_code = $('#dialing_code').val(),
            sms_verification_code = $('#fm-verification-code').val();

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
                verificationUrl = '<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/' + mobileNumber + '/sms_api_withdrawal_setting';
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