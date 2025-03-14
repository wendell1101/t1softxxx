<style type="text/css">
	.setup-deposit-presetamount{
		margin-bottom: 1rem;
	}
</style>

<?php $explode_amount = explode('|', $preset_amount_buttons);?>
<div class="setup-deposit-presetamount">
    <?php foreach ($explode_amount as $key => $amount) : ?>
        <?php if(is_numeric((int)$amount)) :?>
			<?php
				$enabled_currencysym = $this->config->item('enabled_currency_sign_in_preset_amount');
				$enabled_thousands = $this->config->item('enabled_thousands_in_preset_amount');
				$amount_text = $this->utils->formatCurrency($amount, $enabled_currencysym, $enabled_thousands, false, 0);
			?>
			<?php if($this->config->item('enabled_accumulate_preset_amount')):?>
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
		let bank_deposit_amount = $('.deposit-form input[name=bankDepositAmount]');
		let thousands_separator_amount = $('input[name="thousands_separator_amount"]');
		if(enable_thousands_separator_in_the_deposit_amount){
	        thousands_separator_amount.val(val);
	        if (enable_custom_view) {
		        thousands_separator_amount.trigger('change');
		    }
	        display_thousands_separator();
	    }
		bank_deposit_amount.val(val);
		if (enable_custom_view) {
		    bank_deposit_amount.trigger('change');
		}

	}

	function add_accumulate_preset_amount_buttons(val) {
		let bank_deposit_amount = $('.deposit-form input[name=bankDepositAmount]');
		let original_amt = bank_deposit_amount.val();
		original_amt = isNaN(original_amt) || original_amt === "" ? 0 : parseInt(original_amt);
		let total_amt = parseInt(original_amt) + parseInt(val);

		if (enable_thousands_separator_in_the_deposit_amount) {
		    let thousands_separator_amount = $('input[name="thousands_separator_amount"]');
		    thousands_separator_amount.val(total_amt);
		    if (enable_custom_view) {
		        thousands_separator_amount.trigger('change');
		    }
		    display_thousands_separator();
		}

	    bank_deposit_amount.val(total_amt);
		if (enable_custom_view) {
		    bank_deposit_amount.trigger('change');
		}
	}

	function reset_amt_val(){
		if(enable_thousands_separator_in_the_deposit_amount){
	        $('input[name="thousands_separator_amount"]').val('');
	        display_thousands_separator();
	    }
		$('.deposit-form input[name=bankDepositAmount]').val('');
	}
</script>