<div class="form-group">
    <label class="control-label" id="deposit_method_dropdown_label"><?=$label;?></label>
    <div class="dropdown" id="deposit_method_dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu-input_type-list-<?=$inputInfo['name']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <span><?=lang('Please Select')?></span>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu deposit_method" aria-labelledby="dropdownMenu-input_type-list-<?=$inputInfo['name']?> " required>
            <?php if(!empty($inputInfo['list'])) : ?>
                <?php foreach($inputInfo['list'] as $key => $value): ?>
                    <li data-text="<?=lang($value['name'])?>" data-value="<?=$value['code']?>" required>
                        <div class="payment-methods-list">
                            <a href="javascript:void(0)" class="font-weight-bold">
                            <img src="<?=$value['logo']?>" width="40px" height="40px">
                            <strong><?=lang($value['name'])?></strong>
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <input type="hidden" name="<?=$inputInfo['name']?>" class="field" value=""/>
    </div>
    <div class="clear"></div>
</div>