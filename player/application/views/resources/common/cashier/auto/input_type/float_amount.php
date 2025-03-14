<?php
    $enabled_decimal = !$this->utils->getConfig('disabled_deposit_page_decimal');
    $minDepositDisplay = $enabled_decimal ? $this->utils->formatCurrency($minDeposit) : $this->utils->formatCurrencyWithSymNoDecimal($minDeposit);
    $maxDepositDisplay = $enabled_decimal ? $this->utils->formatCurrency($maxDeposit) : $this->utils->formatCurrencyWithSymNoDecimal($maxDeposit);
?>
<div class="from-group setup-deposit-amount">
    <input type="hidden" name="minDeposit" value="<?=$minDeposit?>">
    <input type="hidden" name="maxDeposit" value="<?=$maxDeposit?>">
    <?php foreach ($special_limit_rules as $rule) : ?>
        <?php if(!empty($rule)) :?>
            <input type="hidden" name="special_limit_rules[]" value="<?=$rule?>">
        <?php endif?>
    <?php endforeach?>

    <label class="control-label"><?=$label;?></label>
    <?php
        if (!empty($preset_amount_buttons) && !isset($display_crypto_amount)) {
            include __DIR__ . '/auto_preset_amount.php';
        }
    ?>
    <?php if($this->utils->getConfig('enable_thousands_separator_in_the_deposit_amount')): ?>
        <input type="text" class="form-control" id="thousands_separator_amount" name="thousands_separator_amount"
            placeholder="<?=lang('custom_deposit_amount_placeholder');?>"
            onChange = "display_thousands_separator()"
            oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
            />
    <?php endif; ?>
    <input type="text" class="form-control" name="deposit_amount" onkeyup="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" placeholder="<?=lang('custom_deposit_amount_placeholder');?>"
        <?php if (isset($inputInfo['readonly'])) : ?>
            readonly="readonly"
        <?php endif; ?>
    >

    <?php if($this->utils->getConfig('format_max_min_transaction')) :?>
        <style type="text/css">
            .small.min{
                display: inline;
            }
            .small.max{
                display: inline;
                padding: 0;
                margin: 0;
            }
        </style>
        <div class="format_min_max">
            <p class="small min"><?= sprintf(lang('format_deposit_max_min_transaction'), '<span class="depamt min">' . $minDepositDisplay . '</span></p>', '<p class="small max"> <span class="depamt max">'. $maxDepositDisplay .'</span></p>') ;?>
        </div>
    <?php else: ?>
        <div class="small">
            <?php if(stristr(lang('player.mindep'),'%s')) :?>
                <?= sprintf(lang('player.mindep'), '<span class="depamt min">'.$minDepositDisplay.'</span> '); ?>
            <?php else: ?>
                <?=lang('player.mindep')?>:
                <span class="depamt min"><?=$minDepositDisplay?></span>
            <?php endif ?>
        </div>
        <div class="small">
            <?php if(stristr(lang('pay.maxdepamt'),'%s')) :?>
                <?= sprintf(lang('pay.maxdepamt'), '<span class="depamt max">'.$maxDepositDisplay.'</span> '); ?>
            <?php else: ?>
                <?=lang('pay.maxdepamt')?>:
                <span class="depamt max"><?=$maxDepositDisplay?></span>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php
    $hint = trim($external_system_api->getAmountHint());
    if(!empty($hint)) : ?>
        <div class="helper-content deposit_hint text-danger font-weight-bold">
            <p><?=$hint?></p>
        </div>
    <?php endif; ?>
    <div class="clear"></div>
</div>
<script type="text/javascript">
    var enable_thousands_separator_in_the_deposit_amount =  '<?=$this->utils->getConfig('enable_thousands_separator_in_the_deposit_amount')?>';
    if(enable_thousands_separator_in_the_deposit_amount){
        $('input[name="deposit_amount"]').addClass('hide');
        $(document).on("change", 'input[name="deposit_amount"]' , function() {
            $('input[name="deposit_amount"]').focus();
        });
    }

    function display_thousands_separator() {
        var float_amount = $('#thousands_separator_amount').val().replace(/,/g, "");
        var housands_separator_amount = _export_sbe_t1t.utils.displayInThousands(float_amount);

        $('#thousands_separator_amount').val(housands_separator_amount);
        $('input[name="deposit_amount"]').val(parseFloat(float_amount)).change();
    }
</script>
