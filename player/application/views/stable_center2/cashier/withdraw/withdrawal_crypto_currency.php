 <style type="text/css">
     .text_color{
        color: red;
     }
 </style>
 <div id="withdrawal_crypto" class="form-group hidden">
    <hr />
    <p class="step">
        <span class='withdrawal_crypto-step-icon step-icon'>3</span>
        <label class="crypto-withdrawal-label"><?= lang('Cryptocurrency rate conversion') ?></label>
        <p class="crypto-hint hidden"><?= lang('withdrawal_crypto_hint') ?></p>
        <p class="text_color"><b><?=lang('exchange_rate_reference_only_hint')?></b></p>
        <p class="text_color" id="crypto_rate_conversion_msg">
            <b>
                <a class="default_amount text_color">1</a> <a class="crypto-currency text_color"><?=lang("BTC-Crypto")?></a> &asymp; 
                <a id="coin_rate" class="text_color">0</a> <a class="default-currency text_color"><?=lang("USD-Yuan")?></a>
            </b>
        </p>
        <p class="text_color" id="currency_conversion_rate_msg">
            <b><a class="default_amount text_color">1</a> <a class="default-currency text_color"><?=lang("USD-Yuan")?></a>&asymp; 
                <a id="currency_rate" class="text_color">0</a> <a class="crypto-currency text_color"><?=lang("BTC-Crypto")?></a>
            </b>
        </p>
    </p>
    <div id="crypto_input" class="input-group">
        <input type="text" id="withdrawal_crypto_amt" class="form-control" name="withdrawal_crypto_amt" onKeyUp="currency_conversion_to_crypto()"/>
    </div>
    <hr />
</div>
<script type="text/javascript">
    function displayCrypto(){
        $('#withdrawal_crypto').removeClass('hidden');
        $('#withdrawal_crypto_amt').css({'width':'260px','background-color':'#e9e9e9'}).trigger('change');
        $('input[name="amount"]').addClass('active').trigger('change');
    }

    function hideCrypto(){
        $('#withdrawal_crypto').addClass('hidden');
        $('input[name="amount"]').removeClass('active');
    }
</script>
