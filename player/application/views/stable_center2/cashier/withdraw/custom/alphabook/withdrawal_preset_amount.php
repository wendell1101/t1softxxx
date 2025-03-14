<style type="text/css">
	.setup-withdrawal_preset_amount{
		margin-bottom: 1rem;
	}
</style>

<?php $explode_amount = explode('|', $withdrawal_preset_amount);?>
<div class="setup-withdrawal_preset_amount">
    <?php foreach ($explode_amount as $key => $amount) : ?>
        <?php if(is_numeric((int)$amount)) :?>
			<?php $amount_text = $this->config->item('enabled_currency_sign_in_preset_amount') ? $this->utils->formatCurrency($amount, true, false, false, 0) : $amount;?>
			<?php if($this->config->item('enabled_accumulate_preset_amount')):?>
				<button type="button" class="btn btn-primary preset-amount" onclick="add_accumulate_preset_amount_buttons_custom(this.value);" id="<?='preset_amount_buttons_'.$key?>" value="<?=$amount?>"><?=$amount_text?></button>
			<?php else:?>
				<button type="button" class="btn btn-primary preset-amount" onclick="add_preset_amount_buttons_custom(this.value);" id="<?='preset_amount_buttons_'.$key?>" value="<?=$amount?>"><?=$amount_text?></button>
            <?php endif;?>
        <?php endif?>
    <?php endforeach?>
    <?php if($this->utils->isEnabledFeature('enable_preset_amount_helper_button_in_withdrawal_page')):?>
		<button type="button" class="btn btn-primary reset-amount" onclick="reset_amt_val()" id="reset-amount"><?= lang('Reset') ?></button>
		<button type="button" class="btn btn-primary reset-amount" onclick="add_preset_amount_buttons_custom(this.value);" id="main-wallet-amount"><?= lang('All amount') ?></button>
    <?php endif?>
</div>

<script type="text/javascript">
	var main_wallet_balance = '<?=$main_wallet_balance?>';

	$(document).ready(function(){
		$('#fm-withdrawal-custom #main-wallet-amount').val(main_wallet_balance);
	});

	function add_preset_amount_buttons_custom(val){
		if (enable_currency) {
			$('#withdrawal_conversion_amt').val(val).change();

			if(enable_thousands_separator_in_the_withdraw_amount){
				display_thousands();
			}
		}else{
			$('#fm-withdrawal-custom #amount').val(val);

			if(enable_thousands_separator_in_the_withdraw_amount){
				$('#fm-withdrawal-custom #thousands_separator_amount').val(val);
				display_thousands_separator_custom_withdrawal();
			}
		}
	}

	function add_accumulate_preset_amount_buttons_custom(val) {
		if (enable_currency) {
			var original_amt = $('#withdrawal_conversion_amt').val();

			if (isNaN(original_amt) || original_amt == "") {
				original_amt = 0;
			}
			var total_amt = parseInt(original_amt) + parseInt(val);

			$('#withdrawal_conversion_amt').val(total_amt).change();

			if(enable_thousands_separator_in_the_withdraw_amount){
				display_thousands();
			}
		}else{
			var original_amt = $('#fm-withdrawal-custom #amount').val();

			if (isNaN(original_amt) || original_amt == "") {
				original_amt = 0;
			}

			var total_amt = parseInt(original_amt) + parseInt(val);
			$('#fm-withdrawal-custom #amount').val(total_amt);

			if(enable_thousands_separator_in_the_withdraw_amount){
				$('#fm-withdrawal-custom #thousands_separator_amount').val(total_amt);
				display_thousands_separator_custom_withdrawal();
			}
		}
	}

	function reset_amt_val(){
		if (enable_currency) {
			var original_amt = $('#withdrawal_conversion_amt').val('');
			if(enable_thousands_separator_in_the_withdraw_amount){
				$('#fm-withdrawal-custom #thousands_separator_amount').val('');
				display_thousands();
			}
		}else{
			$('#fm-withdrawal-custom #amount').val('');

			if(enable_thousands_separator_in_the_withdraw_amount){
				$('#fm-withdrawal-custom #thousands_separator_amount').val('');
				display_thousands_separator_custom_withdrawal();
			}
		}
	}

	function display_thousands() {
	    var float_amount = $(formId + ' #thousands_separator_amount').val().replace(/,/g, "");
	    var housands_separator_amount = _export_sbe_t1t.utils.displayInThousands(float_amount);
	    $(formId + ' #thousands_separator_amount').val(housands_separator_amount);
	}
</script>