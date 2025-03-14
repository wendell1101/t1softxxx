<div class="from-group setup-deposit-amount">
    <input type="hidden" name="minDeposit" value="<?=$minDeposit?>">
    <input type="hidden" name="maxDeposit" value="<?=$maxDeposit?>">
    <label class="control-label"><?=$label;?></label>
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu-input_type-list_amount" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <span><?=lang('Please Select')?></span>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenu-input_type-list_amount">
            <?php foreach($inputInfo['list'] as $key => $value): ?>
                <li data-text="<?=lang($value)?>" data-value="<?=$key?>">
                    <a href="javascript:void(0)" class="font-weight-bold"><strong><?=lang($value)?></strong></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <input type="hidden" name="<?=$inputInfo['name']?>" class="field" value=""/>
    </div>

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
        <p class="small min"><?= sprintf(lang('format_deposit_max_min_transaction'), '<span class="depamt min">' . $this->utils->formatCurrency($minDeposit) . '</span></p>', '<p class="small max"> <span class="depamt max">'. $this->utils->formatCurrency($maxDeposit) .'</span></p>') ;?>
    <?php else: ?>
        <div class="small">
            <?=lang('player.mindep')?>:
            <span class="depamt min"><?=$this->utils->formatCurrency($minDeposit)?></span>
        </div>
        <div class="small">
            <?=lang('pay.maxdepamt')?>:
            <span class="depamt max"><?=$this->utils->formatCurrency($maxDeposit)?></span>
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