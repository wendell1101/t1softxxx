<div class="from-group setup-deposit-amount">
    <label class="control-label <?= strtolower($crypto_currency_tag.'-label') ?>"><?=$label;?></label>
    <?php
        $display_crypto_amount = true;
        if (!empty($preset_amount_buttons)) {
            include __DIR__ . '/auto_preset_amount_crypto.php';
        }
    ?>
    <input type="text" class="form-control" name="crypto_amount" placeholder="<?=$crypto_currency_label?>" onKeyUp="usdt_crypto_converter_current_currency()">
    <input type="hidden" name="deposit_crypto_rate">
    <p style="color:red"><b><?=lang('exchange_rate_reference_only_hint')?></b></p>
    <p style="color:red"><b><?=lang('Cryptocurrencies Exchange Rate')?></b></p>
    <p id="deposit_crypto_rate_conversion_msg" style="color:red"><b>1 <?=$crypto_currency_label?> &asymp; <a style="color:red" id="deposit_crypto_rate"></a> <?=$default_currency_label?></b></p>
</div>
<script type="text/javascript">
var fixed_exchange_rate = <?= $fixed_exchange_rate ?>;
var CRYPTO_CURRENCY_CONVERSION_RATE = 0;
var enable_thousands_separator_in_the_deposit_amount =  '<?=$this->utils->getConfig('enable_thousands_separator_in_the_deposit_amount')?>';

$(function(){
    var timeid = 0;
    $('input[name="deposit_amount"]').css('background-color', '#e9e9e9');
    if(enable_thousands_separator_in_the_deposit_amount){
        $('input[name="thousands_separator_amount"]').css('background-color', '#e9e9e9');
    }
    var getCryptoCurrencyRate = function(){
        $.ajax({
        "url": '/api/getCryptoCurrencyRate/USDT/deposit'
        }).done(function (data) {
            Loader.hide();
            if (data['success']) {
                $('input[name="deposit_crypto_rate"]').val(data['rate']);
                $('#deposit_crypto_rate').text(data['rate']);
                CRYPTO_CURRENCY_CONVERSION_RATE = data['rate'];
            }else{
                CRYPTO_CURRENCY_CONVERSION_RATE = data['rate'];
                $('input[name="deposit_crypto_rate"]').val(data['rate']);
                $('#deposit_crypto_rate_conversion_msg').html('<b>Network Error</b>');
            }
        }).fail(function(response) {
            CRYPTO_CURRENCY_CONVERSION_RATE = 0;
            $('#deposit_crypto_rate_conversion_msg').html('<b>Network Error</b>');
            console.log(response);
        });
    };

    // OGP-23071: use extra_info.fixed_exchange_rate if present
    if (fixed_exchange_rate <= 0) {
        getCryptoCurrencyRate();
        timeid = setInterval(getCryptoCurrencyRate, 180000);
    }
    else {
        CRYPTO_CURRENCY_CONVERSION_RATE = fixed_exchange_rate;
        $('input[name="deposit_crypto_rate"]').val(fixed_exchange_rate);
        $('#deposit_crypto_rate').text(fixed_exchange_rate);
    }
})

function usdt_crypto_converter_current_currency() {
    var cryptoQty = $('input[name="crypto_amount"]').val();
    var bankDepositAmount = $('input[name="deposit_amount"]').val();
    var rateAmount = (cryptoQty * CRYPTO_CURRENCY_CONVERSION_RATE);
    var num = rateAmount.toFixed(2);

    if(enable_thousands_separator_in_the_deposit_amount){
        if(num > 0 && bankDepositAmount != num){
            $('input[name="thousands_separator_amount"]').val(num).prop('readonly', true).trigger('change');
            $('input[name="deposit_amount"]').val(num).prop('readonly', true).trigger('change');
        }else if(num <= 0){
            $('input[name="thousands_separator_amount"]').val(0).trigger('change');
            $('input[name="deposit_amount"]').val(0).trigger('change');
        }
    }else{
        if(num > 0 && bankDepositAmount != num){
            $('input[name="deposit_amount"]').val(num).prop('readonly', true).trigger('change');
        }else if(num <= 0){
            $('input[name="deposit_amount"]').val(0).trigger('change');
        }
    }
}


</script>