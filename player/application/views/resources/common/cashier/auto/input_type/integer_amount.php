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
        if (!empty($preset_amount_buttons)) {
            include __DIR__ . '/auto_preset_amount.php';
        }
    ?>
    <input type="text" class="form-control" name="deposit_amount" onkeyup="value=value.replace(/^(0+)|[^\d]+/g,'')" placeholder="<?=lang('custom_deposit_amount_placeholder');?>"
        <?php if (isset($inputInfo['readonly'])) : ?>
            readonly="readonly"
        <?php endif; ?>
    >

    <?php 
    $enabled_decimal = !$this->utils->getConfig('disabled_deposit_page_decimal');
    $minDepositDisplay= $this->utils->formatCurrency($minDeposit, true, true, $enabled_decimal, 0);
    $maxDepositDisplay= $this->utils->formatCurrency($maxDeposit, true, true, $enabled_decimal, 0);
    ?>

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
        <p class="small min"><?= sprintf(lang('format_deposit_max_min_transaction'), '<span class="depamt min">' . $minDepositDisplay . '</span></p>', '<p class="small max"> <span class="depamt max">'. $maxDepositDisplay .'</span></p>') ;?>
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