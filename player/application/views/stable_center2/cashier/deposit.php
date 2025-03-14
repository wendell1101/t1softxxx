<!-- DEPOSIT PAGE -->

<div id="fm-deposit">
    <?php if($this->utils->isEnabledFeature('enable_deposit_category_view') === false) { ?>
        <?php if(!$this->utils->is_mobile()) {?>
            <ul class="nav inner-tab show-bank" id="depositTabList">
            <?php if (!empty($payment_manual_accounts)):?>
            <li class="<?=($deposit_method == 'manual') ? 'active' : ''?>"><a href="<?=$this->utils->getSystemUrl('player')?>/player_center2/deposit/manual_payment"><?=lang('Bank Deposit') ?></a></li>
            <?php endif?>


            <?php foreach ($payment_auto_accounts as $key => $val): ?>
                <li class="<?=($this->uri->segment(4) == $val->payment_account_id) ? 'active' : ''?>">
                    <a href="<?=$this->utils->getSystemUrl('player')?>/player_center2/deposit/auto_payment/<?=$val->payment_account_id?>">
                        <?php if(!empty($val->bank_icon_url)): ?>
                            <img class="bank_icon" src="<?=$val->bank_icon_url?>">
                        <?php endif; // if(!empty($val->bank_icon_url)):... ?>
                        <?=lang($val->payment_type)?>
                    </a>
                </li>
            <?php endforeach ?>
            </ul>
        <?php } else { ?>

            <div class="form-group">
                <strong class='payment-method-text'><?=lang('con.pym10') ?></strong>
                <select class="form-control selectpicker deposit-mobile-select-list" id="depositTabList" onChange="location=this.value;">
                <?php if (!empty($payment_manual_accounts)):?>
                    <option value='<?=$this->utils->getSystemUrl('player')?>/player_center2/deposit/manual_payment' <?=($deposit_method == 'manual') ? 'selected' : ''?> >
                        <?=lang('Bank Deposit') ?>
                    </option>
                <?php endif?>
                <?php foreach ($payment_auto_accounts as $key => $val): ?>
                    <option value='<?=$this->utils->getSystemUrl('player')?>/player_center2/deposit/auto_payment/<?=$val->payment_account_id?>' <?=($this->uri->segment(4) == $val->payment_account_id) ? 'selected' : '' ?> >
                        <?=lang($val->payment_type)?>
                    </option>
                <?php endforeach ?>
                </select>
            </div>
        <?php } ?>

    <?php } else if(!$hide_deposit_selected_bank_and_text_for_ole777) { ?>
        <ul class="nav inner-tab show-bank">
            <li class="active"><a href="#" id='selected-payment-account-name'><?=$deposit_method == 'auto' ? lang($payment_account->payment_type) : ""; ?></a></li>
        </ul>
    <?php } ?>
    <div class="deposit-detail-content" style="padding-top:20px">
        <?php if($deposit_method == 'auto_static_html') :?>
            <?php include $static_html;?>
        <?php else: ?>
            <?php include __DIR__ . '/../cashier/deposit/' . $deposit_method . '.php';?>
        <?php endif ?>
    </div>
</div>
<style>
    #depositTabList li .bank_icon {
        height: 18px;
    }
</style>
<!-- $this->utils->getConfig('enable_deposit_custom_view') -->
<?php if($this->utils->getConfig('enable_custom_css_for_deposit')) :?>
<script type="text/javascript">
    let _flag_type = "<?=isset($second_category_flag) ? $second_category_flag : '0' ?>";
    $(document).ready(function() {
        let form_deposit = $('#form-deposit');

        form_deposit.removeClass('is_bank_type is_dada_type is_ovo_type is_3rd_type is_crypto_type is_pulsa_type');
        switch (_flag_type) {
            case '7':
                form_deposit.addClass('is_bank_type');
                break;
            case '3':
                form_deposit.addClass('is_dada_type');
                break;
            case '2':
                form_deposit.addClass('is_ovo_type');
                break;
            case '1':
                form_deposit.addClass('is_3rd_type');
                break;
            case '9':
                form_deposit.addClass('is_crypto_type');
                break;
            case '4':
                form_deposit.addClass('is_pulsa_type');
                break;
            default:
        }
    });
</script>
<?php else:?>
<script type="text/javascript">
    $(document).ready(function() {
        <?php if(!empty($append_ole777thb_js_content)):?>
            ole777thb_deposit.append_custom_js();
        <?php endif;?>
    });
</script>
<?php endif ?>

<?php include __DIR__ . '/../cashier/deposit/modal.php';?>
<?php include __DIR__ . '/../bank_account/content/modal.php';?>