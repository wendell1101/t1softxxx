<style type="text/css">
	.setup-deposit-presetamount{
		margin-bottom: 1rem;
	}
</style>

<?php $explode_amount = explode('|', $preset_amount_buttons);?>
<div class="from-group setup-deposit-presetamount igm-deposit-presetamount">
    <?php foreach ($explode_amount as $key => $amount) : ?>
        <?php if(is_numeric((int)$amount)) :?>
			<?php $amount_text = $this->config->item('enabled_currency_sign_in_preset_amount') ? $this->utils->formatCurrency($amount, true, false, false, 0) : $amount;?>
			<?php if(!$this->config->item('enabled_accumulate_preset_amount')):?>
				<button type="button" class="btn btn-primary preset-amount" onclick="add_accumulate_preset_amount_buttons(this.value);" id="<?='preset_amount_buttons_'.$key?>" value="<?=$amount?>"><?=$amount_text?></button>
			<?php else:?>
				<button type="button" class="btn btn-primary preset-amount" onclick="add_preset_amount_buttons(this.value);" id="<?='preset_amount_buttons_'.$key?>" value="<?=$amount?>"><?=$amount_text?></button>
            <?php endif;?>
        <?php endif?>
    <?php endforeach?>
    <?php if($this->utils->isEnabledFeature('enable_preset_amount_helper_button_in_deposit_page')):?>
		<button type="button" class="btn btn-primary reset-amount" onclick="reset_amt_val()" id="reset-amount"><?= lang('Reset') ?></button>
    <?php endif?>
</div>

<script type="text/javascript">
	function add_preset_amount_buttons(val){
		$('.deposit-form input[name=crypto_amount]').val(val).change();
		usdt_crypto_converter_current_currency();
		$('#auto_payment_submit').attr('disabled', false);
	}

	function add_accumulate_preset_amount_buttons(val) {
		var original_amt = $('.deposit-form input[name=crypto_amount]').val();

		if (isNaN(original_amt) || original_amt == "") {
			original_amt = 0;
		}

		var total_amt = parseInt(original_amt) + parseInt(val);
		$('.deposit-form input[name=crypto_amount]').val(total_amt).change();
		usdt_crypto_converter_current_currency();
		$('#auto_payment_submit').attr('disabled', false);
	}

	function reset_amt_val(){
		$('.deposit-form input[name=crypto_amount]').val('').change();
		usdt_crypto_converter_current_currency();
	}
</script>