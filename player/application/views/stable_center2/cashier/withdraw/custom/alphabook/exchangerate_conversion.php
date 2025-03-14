 <div id="withdrawal_conversion" class="form-group has-feedback">
    <!-- <hr /> -->
    <div id="withdrawal_conversion_input" class="input-group">
        <input type="text" class="form-control" id="withdrawal_conversion_amt" name="withdrawal_conversion_amt"
        data-error="<?=lang('text.error')?>"
        data-required-error="<?=lang('Fields with (*) are required.')?>"
        oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
        />
       <!--  <input type="text" class="form-control" id="display_withdrawal_conversion_amt" name="display_withdrawal_conversion_amt"
            data-error="<?=lang('text.error')?>"
            data-required-error="<?=lang('Fields with (*) are required.')?>"
        /> -->
        <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
    </div>
    <input type="hidden" name="custom_type" value="<?=$custom_type?>">
    <!-- <div class="help-block with-errors"></div>
    <div class="helper-content"></div> -->
    <hr />
    <label class="control-label withdrawal-conversion-label"></label>
    <p class="step">
        <!-- <span class='withdrawal-conversion-step-icon step-icon'><?=$step?></span> -->
        <?php 
        $withdrawal_label = !empty($this->utils->getConfig('custom_withdrawal_conversion_label')) ? $this->utils->getConfig('custom_withdrawal_conversion_label') : $this->utils->getConfig('default_withdrawal_conversion_label');
        $style_label = !empty($this->utils->getConfig('custom_withdrawal_conversion_label')) ? "color:red;display:inline" : "color:red";
        foreach ($withdrawal_label as $label):
            switch ($label):
                case 'hint': ?>
             <p style="color:red"><b><?=lang('exchange_rate_reference_only_hint')?></b></p>
             <?php
                break;
                case 'rate': ?>
            <p style=<?=$style_label?>><b><?=lang('Withdrawal Currencies Exchange Rate')?></b></p>
            <?php
                break;
                case 'rate-msg': ?>
            <p id="withdrawal_rate_conversion_msg" style=<?=$style_label?>></p>
            <?php
                break;
            endswitch;
        endforeach;?>
            
    </p>

</div>
<script type="text/javascript">
    /*
        amount = player input amount
        fx_rate = currency rate
        currency = currency
        converted_amount = currency to USD
    */
    async function withdrawalConverterCurrency(params, amount){
        const data = {
                'country' :params,
                'amount' :amount
            };
        const response = await fetch('/api/getEndpointsApi/exchange_rates', {
            method: "POST",
            body: JSON.stringify(data)
        });

        return response.json();
    }

    function disableWithdrawalConversion() {
        enable_currency = false;
        $('#withdrawal_conversion_amt').val('');
        $('#withdrawal_conversion').hide();
        $('#withdrawal_conversion_amt').removeClass('active').removeAttr('required').removeAttr('step');
        $('#withdrawal_conversion_amt').removeAttr("placeholder");
        $('#withdrawal_rate_conversion_msg').html('');
        $('#fm-withdrawal-custom #thousands_separator_amount, #fm-withdrawal-custom #amount').val('').attr("placeholder", "<?= lang('Please Input Withdrawal Amount For Placeholder') ?>");
        $('.custom-withdrawal-amount-label').text("<?= lang('Please Input Withdrawal Amount For Placeholder') ?>");
        $('#fm-withdrawal-custom #thousands_separator_amount').val('').removeAttr('readonly');
        fx_rate = 0;
    }

    function enableWithdrawalConversion(w_country) {
        enable_currency = true;
        const default_currency = _export_sbe_t1t.variables.currency.currency_code;
        const amount = 1;
        const w_currency = $('#currency').val();
        const amount_label_lang = lang('Please Input Withdrawal Amount in ' + w_currency);

        $('.withdrawal-conversion-label').text('Converter ' + default_currency + ' Withdrawal Amount');
        $('.custom-withdrawal-amount-label').text(amount_label_lang);
        $('#withdrawal_conversion_amt').attr("placeholder", amount_label_lang);
        $('#fm-withdrawal-custom #thousands_separator_amount, #fm-withdrawal-custom #amount').attr("placeholder", default_currency);

        withdrawalConverterCurrency(w_country, amount).then((data) => {
            if (data['success']) {
                const res = data['result'];
                const rate_to_currency = (amount / res['fx_rate']).toFixed(5);
                $('#withdrawal_rate_conversion_msg').html('<b>' + amount  + ' ' + res['currency'] +' &asymp; <a id="withdrawal_rate" style="color:red">'+ rate_to_currency +'</a> '+  default_currency +'</b>');
                fx_rate = rate_to_currency;
            }
        });
        $('#withdrawal_conversion').show();
        $('#withdrawal_conversion_amt').addClass('active').attr("required", "required").trigger('change');
        $('#fm-withdrawal-custom #thousands_separator_amount').val('').attr('readonly', 'readonly');

    }

    function changeCurrencySymbol(currency){
        var symbol = _export_sbe_t1t.variables.currency.symbol;
        var preset_btn = $('#fm-withdrawal-custom .setup-withdrawal_preset_amount >button');
        var currency_list = ['PEN'];

        if (currency_list.includes(currency)) {
            symbol = $("#currency_symbol").val();
        }

        $.each(preset_btn ,function (k,v) {
            $(formId + " #" + v.id).html(symbol + ' ' + v.value);
        });
    }
</script>
