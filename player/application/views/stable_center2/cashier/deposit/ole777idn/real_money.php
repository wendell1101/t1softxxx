<div class="row deposit-process-mode-<?=in_array($deposit_process_mode, array('2','3')) ? '2' : $deposit_process_mode ?> setup-deposit-real-money">
    <div class="form-group has-feedback">
        <p class="step realAmountTitle"><span class="step-icon"><?=$step?></span><label class="control-label"><?=lang('Actual Amount')?></label>
        </p>
            <div class="input-group col col-xs-12 col-sm-12 col-md-4">
              <input type="text" class="form-control noscroll" id="real_money" placeholder="<?=lang('Actual Amount')?>" readonly>
            </div>
            <div class="help-block with-errors"></div>
        <div class="real-money-content">
            <p><?=lang('deposit_category_customize_content')?></p>
        </div>
    </div>
    <hr/>
</div>
<script type="text/javascript">
    var enable_thousands_separator =  '<?=$this->utils->getConfig('enable_thousands_separator_in_the_deposit_amount')?>';
    var amt_element = enable_thousands_separator ? '.setup-deposit-amount #thousands_separator_amount' : '.setup-deposit-amount #bankDepositAmount';
    var real_money = $('.setup-deposit-real-money #real_money');
    var is_crypto_type = "<?=$second_category_flag == '9' ? '1' : '0'?>";
    if (is_crypto_type == '1') {
        amt_element = '#displayBankDepositAmount';
    }

    $(document).on("change", amt_element, function(){
        let amount = $(amt_element).val();

        if (typeof amount != 'undefined' && amount != '') {
            if (enable_thousands_separator) {
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
</script>
<style type="text/css">
    .real-money-content{
        color:#ff0000;
    }
</style>