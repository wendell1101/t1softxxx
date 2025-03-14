 <div id="crypto" class="form-group has-feedback">
    <hr />
    <p class="step">
        <span class='crypto-step-icon step-icon'><?=$step?></span>
        <label class="control-label crypto-withdrawal-label"></label>
            <?php
            $withdrawal_label = !empty($this->utils->getConfig('custom_crypto_withdrawal_label')) ? $this->utils->getConfig('custom_crypto_withdrawal_label') : $this->utils->getConfig('default_crypto_withdrawal_label');
            $style_label = !empty($this->utils->getConfig('custom_crypto_withdrawal_label')) ? "color:red;display:inline" : "color:red";
            foreach ($withdrawal_label as $label):
            switch ($label):
                case 'hint': ?>
             <p style="color:red"><b><?=lang('exchange_rate_reference_only_hint')?></b></p>
             <?php
                break;
                case 'rate': ?>
            <p style=<?=$style_label?>><b><?=lang('Cryptocurrencies Exchange Rate')?></b></p>
            <?php
                break;
                case 'rate-msg': ?>
            <p id="crypto_rate_conversion_msg" style=<?=$style_label?>></p>
            <?php
                break;
            endswitch;
            endforeach;?>

    </p>
    <div id="crypto_input" class="input-group">
        <input type="hidden" class="form-control" id="cryptoQty" name="cryptoQty"
        data-error="<?=lang('text.error')?>"
        data-required-error="<?=lang('Fields with (*) are required.')?>"
        onKeyUp="crypto_converter_current_currency()"
        />
        <input type="text" class="form-control" id="displayCryptoQty" name="displayCryptoQty"
            data-error="<?=lang('text.error')?>"
            data-required-error="<?=lang('Fields with (*) are required.')?>"
            onKeyUp="crypto_converter_current_currency()"
            readonly
        />
        <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
    </div>
    <div class="help-block with-errors"></div>
    <div class="helper-content"></div>
    <hr />
</div>
<script type="text/javascript">
    var ENABLE_CRYPTO_CURRENCY = $('#crypto').length > 0 ? true : false;
    var CRYPTO_CURRENCY_CONVERSION_RATE = 0;
    var CUST_API_TIME_ID = 0;
    var use_branch_as_ifsc_in_withdrawal_accounts = "<?= $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? '1' : '0' ?>";

    function enableDisplayCrypto(type) {
        var remove_required = '<?=$this->utils->getConfig('remove_cryptoqty_required')?>';
        $('.crypto-step-icon').remove();
        $('#crypto').show();
        $('#cryptoQty').addClass('active').attr("required", "required").css({'width':'260px','background-color':'#e9e9e9'}).trigger('change');
        $('#acctnumber_label').empty().text("<?=lang('pay.walletaddress')?>"+":");
        $('#bankbranch_label').empty().text("<?=lang('crypto network')?>"+":");
        if (remove_required) {$('#cryptoQty').removeAttr('required');}
    }

    function disableDisplayCrypto(type) {
        if(use_branch_as_ifsc_in_withdrawal_accounts){
            $('#bankbranch_label').empty().text("<?=lang('pay.branchname')?>"+":");
        }else{
            $('#bankbranch_label').empty().text("<?=lang('financial_account.ifsc')?>"+":");

        }
        $('#acctnumber_label').empty().text("<?=lang('pay.acctnumber')?>"+":");
        $('#crypto').hide();
        $('#cryptoQty').removeClass('active').removeAttr('required').removeAttr('step');
    }

    function setValueOnChangeAccountCrypto(type) {
        $('#cryptoQty').val('');
        $('#amount').val('');
        clearInterval(CUST_API_TIME_ID);
    }

    function validateCryptoQuantity(){
        if(!$('#cryptoQty').hasClass('active') && $('#cryptoQty').val().length > 0){
            return false;
        }else{
            return true;
        }
    }

</script>
