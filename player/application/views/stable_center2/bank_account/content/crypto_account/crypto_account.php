<div id="crypto" class="form-group has-feedback">
    <hr />
    <p class="step">
        <span class='crypto-step-icon step-icon'><?=$step?></span>
        <label class="control-label crypto-deposit-label"><?=sprintf(lang('Please Enter Crypto Amount'), lang("$cryptocurrency-Crypto"))?></label>
        <label class="control-label crypto-withdrawal-label"><?=sprintf(lang('Converter Crypto Withdrawal Amount'), lang("$cryptocurrency-Crypto"))?></label>
        <p style="color:red"><b><?=lang('exchange_rate_reference_only_hint')?></b></p>
        <p style="color:red"><b><?=lang('Cryptocurrencies Exchange Rate')?></b></p>
        <p id="crypto_rate_conversion_msg" style="color:red"><b>1 <?=lang("$cryptocurrency-Crypto")?> &asymp; <a style="color:red" id="crypto_rate"><?=$cryptocurrency_rate?></a> <?=lang("$defaultCurrency-Yuan")?></b></p>
    </p>
    <div id="crypto_input" class="input-group">
        <?php
            if (!empty($preset_amount_buttons)) {
                include __DIR__ . '/../../../cashier/deposit/manual/manual_preset_amount_crypto.php';
            }
        ?>
        <input type="number" step="0.0001" class="form-control" id="cryptoQty" name="cryptoQty"
            placeholder="<?=lang("$cryptocurrency-Crypto")?>"
            data-error="<?=lang('text.error')?>"
            data-step-error="<?=sprintf(lang('notify.124'), $custCryptoInputDecimalPlaceSetting)?>"
            data-required-error="<?=lang('Fields with (*) are required.')?>"
            onKeyUp="crypto_converter_current_currency()"
        />
        <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
    </div>
    <div class="help-block with-errors"></div>
    <div class="helper-content"></div>
    <hr />
</div>
<script type="text/javascript">
    var CRYPTO_CURRENCY_CONVERSION_RATE = "<?=isset($cryptocurrency_rate) ? $cryptocurrency_rate : '0' ?>";
    var CUST_CRYPTO_UPDATE_TIMING = "<?=isset($custCryptoUpdateTiming) ? $custCryptoUpdateTiming : '1800' ?>";
    var CUST_FIX_RATE = "<?=isset($custFixRate) ? $custFixRate : '1' ?>";
    var CUST_CRYPTO_INPUT_DECIMAL_PLACE_SETTING = "<?=isset($custCryptoInputDecimalPlaceSetting) ? $custCryptoInputDecimalPlaceSetting : '0' ?>";
    var CUST_CRYPTO_INPUT_DECIMAL_RECIPROCAL = "<?=isset($custCryptoInputDecimalReciprocal) ? $custCryptoInputDecimalReciprocal : '0' ?>";
    var REGSTR_PATTERN = new RegExp('^\\D*(\\d*(?:\\.\\d{0,CUST_CRYPTO_INPUT_DECIMAL_RECIPROCAL})?).*$'.replace('CUST_CRYPTO_INPUT_DECIMAL_RECIPROCAL',CUST_CRYPTO_INPUT_DECIMAL_RECIPROCAL), 'g');
     var CUST_API_TIME_ID = 0;

    function showDepositHint(){
        var hint_url = '<?=$this->utils->getConfig('usdt_hint_url')?>'; //"http://777ole.com/usdt/"
        if(hint_url){
            $('.submit-deposit-order').append('<a id="hintBtn" type="button" class="btn btn-submit form-control" href="'+hint_url+'" target="_blank"><i class="fa fa-question-circle" aria-hidden="true" style="padding: 0;"></i>&nbsp;<?=lang('usdt_deposit_hint')?></a>');
        }
    }
    function enableDisplayCrypto(type) {
        var remove_required = '<?=$this->utils->getConfig('remove_cryptoqty_required')?>';
        if(type == 'deposit'){
            $('.amountTitle').hide();
            $('.cryptoAmountTitle').show();
            $('.crypto-withdrawal-label').remove();
            $('#crypto').show();
            $('#cryptoQty').addClass('active').attr("required", "required").attr("step", CUST_CRYPTO_INPUT_DECIMAL_PLACE_SETTING).trigger('change');
            $('#crypto_input').addClass('col col-xs-12 col-sm-12 col-md-4');
            $('#bankDepositAmount').css('background-color', '#e9e9e9');
            $('#displayBankDepositAmount').css('background-color', '#e9e9e9');
        }else if(type == 'withdrawal'){
            $('.crypto-deposit-label').remove();
            $('.crypto-step-icon').remove();
            $('#crypto').show();
            $('#cryptoQty').addClass('active').attr("required", "required").attr("step", CUST_CRYPTO_INPUT_DECIMAL_PLACE_SETTING).css({'width':'260px','background-color':'#e9e9e9'}).trigger('change');
        }
        if (remove_required) {$('#cryptoQty').removeAttr('required');}
    }

    function disableDisplayCrypto(type) {
        if(type == 'deposit'){
            $('.amountTitle').show();
            $('#crypto').hide();
            $('#cryptoQty').removeClass('active').removeAttr('required').removeAttr('step');
            $('.cryptoAmountTitle').hide();
            $('#bankDepositAmount').css('background-color', '');
            $('#displayBankDepositAmount').css('background-color', '');
        }else if(type == 'withdrawal'){
            $('#crypto').hide();
            $('#cryptoQty').removeClass('active').removeAttr('required').removeAttr('step');
        }
    }

    function setValueOnChangeAccountCrypto(type) {
        if(type == 'deposit'){
            $('#cryptoQty').val('');
            $('#bankDepositAmount').val('');
            $('#displayBankDepositAmount').val('');
        }else if(type == 'withdrawal'){
            $('#cryptoQty').val('');
            $('#amount').val('');
        }
    }

    function validateCryptoQuantity(){
        if(!$('#cryptoQty').hasClass('active') && $('#cryptoQty').val().length > 0){
            return false;
        }else{
            return true;
        }
    }

</script>
