<div class="from-group">
    <label id="playerbank_dropdown_label" class="control-label"><?=lang($inputInfo['label_lang']);?></label>
    <div class="dropdown" id="playerbank_dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu-input_type-list-<?=$inputInfo['name']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" <?= isset($inputInfo['disabled_btn']) ?  $inputInfo['disabled_btn'] : '' ?>>
            <span><?=lang($inputInfo['select_lang']);?></span>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu playerbank" aria-labelledby="dropdownMenu-input_type-list-<?=$inputInfo['name']?>">
            <?php foreach($inputInfo['list'] as $key => $rows): ?>
                <?php if(!empty($rows['bankCode'])): ?>
                    <li data-text="<?=lang($rows['bankName']).' - '.$rows['bankAccountNumber']?>" data-value="<?=$rows['bankCode'].'-'.$rows['bankAccountNumber']?>">
                        <?php if( $inputInfo['default_option_value'] == $rows['bankCode']) :?>
                            <a id="selected_option" href="javascript:void(0)" class="font-weight-bold"><strong><?=lang($rows['bankName']).' - '.$rows['bankAccountNumber']?></strong></a>
                        <?php else :?>
                            <a href="javascript:void(0)" class="font-weight-bold"><strong><?=lang($rows['bankName']).' - '.$rows['bankAccountNumber']?></strong></a>
                        <?php endif; ?>

                    </li>
            <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <input type="hidden" name="<?=$inputInfo['name']?>" class="field" value=""/>
    </div>
    <div class="clear"></div>
</div>

<script type="text/javascript">
$(function(){
    var default_option_value = "<?=lang($inputInfo['default_option_value']);?>" ;
        if($('.dropdown-menu.playerbank>li').length >0){
            if($('.dropdown-menu.playerbank>li>#selected_option') > 0) {
                $('#selected_option').trigger('click');
            }else {
                $('.dropdown-menu.playerbank>li').first().trigger('click');
            }
        }else{
            $('#playerbank_dropdown').hide();
            $('#playerbank_dropdown_label').text('<?=lang('Please add a bank account before making a deposit');?>').addClass('text-danger');
        }
})
</script>
