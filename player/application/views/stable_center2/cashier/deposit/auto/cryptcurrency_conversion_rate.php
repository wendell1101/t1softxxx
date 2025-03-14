 <div id="auto_payment_crypto" class="form-group has-feedback">
    <hr />
    <p class="step">
        <label class="control-label crypto-hint"><?= isset($extra_info['cryptocurrency_hint']) ? $extra_info['cryptocurrency_hint'] : '' ?></label>
        <label class="control-label crypto-deposit-label mobile-crypto-deposit-label"><?=sprintf(lang('Please Enter Currency Amount'), lang($extra_info['targetCurrency']."-Yuan"))?></label>
        <p style="color:red"><b><?=lang('exchange_rate_reference_only_hint')?></b></p>
        <p id="crypto_rate_conversion_msg" style="color:red">
            <b>
                <a style="color:red" class="default_amount">1</a> <?=lang($extra_info['currency']."-Crypto")?> &asymp;
                <a style="color:red" id="coin_rate">0</a> <?=lang($extra_info['targetCurrency']."-Yuan")?>
            </b>
        </p>
        <p id="currency_conversion_rate_msg" style="color:red">
            <b>
                <a style="color:red" class="default_amount">1</a> <?=lang($extra_info['targetCurrency']."-Yuan")?> &asymp;
                <a style="color:red" id="currency_rate">0</a> <?=lang($extra_info['currency']."-Crypto")?>
            </b>
        </p>
    </p>
</div>
<script type="text/javascript">
    $(function(){
        var disable_crypto_input = "<?= isset($extra_info['disable_crypto_input']) ? $extra_info['disable_crypto_input'] : '' ?>";
        var enable_auto_payment_crypto = $('#auto_payment_crypto').length > 0 ? true : false;
        var show_crypto_input =
        '<div id="crypto_input" class="">' +
            '<input type="text" id="cryptoQty" name="cryptoQty" placeholder="<?=lang($extra_info['currency'].'-Crypto')?>" data-error="<?=lang('text.error')?>" data-step-error="<?=lang('notify.110')?>" data-required-error="<?=lang('Fields with (*) are required.')?>" onKeyUp="PlayerCashierAuto.currency_conversion_to_crypto()"/>' +
            '<span class="glyphicon form-control-feedback" aria-hidden="true"></span>' +
        '</div>' +
            '<div class="help-block with-errors"></div>' +
            '<div class="helper-content"></div>' +
            '<hr />';

        if (enable_auto_payment_crypto) {
            $('input[name="deposit_amount"]').after(show_crypto_input);
            $('input[name="deposit_amount"]').attr('placeholder',"<?=lang($extra_info['targetCurrency'].'-Yuan')?>");
            $('#cryptoQty').css('background-color', '#e9e9e9');
            $('input[name="deposit_amount"]').addClass('active').trigger('change');
            if (disable_crypto_input == '1') {$('#crypto_input').addClass('hide')}
        }
    });
</script>
