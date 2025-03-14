<?php if($deposit_process_mode == '2') :?>

<div id="fm-deposit">
    <div class="img_label">
        <img class="transfer_method_img" src="<?= $this->utils->getSystemUrl('www', '/includes/images/transfer_method.svg?v='.$this->utils->getCmsVersion()); ?>"><p><?= lang('Transfer Method') ?></p>
    </div>

    <div role="group" class="tab-pane active promotions-section deposit-second-category-flag new_dp_content" id="depositTabList">
        <!-- Nav tabs -->
            <?php if (!empty($payment_category_list) && !empty($payment_accounts)):
                foreach ($payment_category_list as $category_id):
                    $accounts = array_filter($payment_accounts, function($account) use ($category_id) {
                        return $account->second_category_flag == $category_id;
                    });
                    if (!empty($accounts)):
                        $class = ($default_show_category_id === $category_id) ? 'active' : '';
            ?>
                    <div class="btn-group second-category-tab transfer_tabs" role="group">
                        <button type="button" class="<?= $class ?> btn" href="#tab<?= $category_id ?>" data-category_id="<?= $category_id ?>" data-toggle="tab">

                            <div class="lb_content content_<?= $category_id ?>">
                                <?php
                                    switch ($category_id):
                                        case '7':
                                        case '1':
                                        case '9':?>
                                    <img src="<?=$this->utils->getSystemUrl('www', '/includes/images/c025_img' .$category_id. '.svg?v='.$this->utils->getCmsVersion());?>">

                                    <div class="tpo_content">
                                        <span class="tab_content_text"><?= lang($second_category_flags[$category_id]) ?></span>
                                    </div>
                                <?php
                                        break;
                                        case '4':?>
                                    <img src="<?=$this->utils->getSystemUrl('www', '/includes/images/c025_img' .$category_id. '.svg?v='.$this->utils->getCmsVersion());?>">
                                    <div class="tpo_content">
                                        <span class="tab_content_text"><?= lang($second_category_flags[$category_id]) ?></span>
                                    </div>
                                <?php
                                        break;
                                        case '2':
                                        case '3':?>
                                    <img src="<?=$this->utils->getSystemUrl('www', '/includes/images/c025_img' .$category_id. '.png?v='.$this->utils->getCmsVersion());?>">
                                <?php
                                        break;
                                    endswitch;?>
                            </div>
                        </button>
                    </div>
            <?php
                    endif;
                endforeach;
            endif;
            ?>

        <div class="tab-content deposit_content">
            <?php
            if (!empty($payment_category_list)):
                foreach ($payment_category_list as $category_id):
                    $class = ($default_show_category_id === $category_id) ? 'active in' : '';
                    $accounts = array_filter($payment_accounts, function($account) use ($category_id) {
                        return $account->second_category_flag == $category_id;
                    });
            ?>
                    <div role="tabpanel" class="tab-pane <?=$class?>" id="tab<?=$category_id?>">
                        <div class="mc-content">
                            <ul class="nav inner-tab sub-accounts-tab">
                                <?php
                                if (!empty($accounts)):
                                    $isCategoryHasAcc = false;
                                    foreach ($accounts as $account):
                                        $isCategoryHasAcc = true;
                                        $currentAccount = $payment_account_id === $account->payment_account_id ? 'active in' : '';
                                        $bank_icon = !empty($account->account_icon_url) ? '<img class="bank_icon" src="' . $account->account_icon_url . '">' : '';
                                        $tab_toggle = $account->second_category_flag == '9' ? 'href="' . $this->utils->getSystemUrl('player').'/player_center2/deposit/deposit_custom_view/' . $account->payment_account_id. '"' : 'role="tab" data-toggle="tab" aria-expanded="true"';
                                ?>
                                        <div class="payment_accounts-body clearfix">
                                            <?php if($account->second_category_flag == '1'): ?>

                                                <li class="<?=($this->uri->segment(4) == $account->payment_account_id) ? 'active in' : ''?>">
                                                    <a href="<?=$this->utils->getSystemUrl('player')?>/player_center2/deposit/auto_payment/<?=$account->payment_account_id?>"
                                                        data-account_id="<?= $account->payment_account_id ?>"
                                                        >
                                                        <?= $bank_icon ?>
                                                        <?= lang($account->payment_type) ?>
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="<?=$currentAccount?>">
                                                    <a
                                                        data-account_id="<?= $account->payment_account_id ?>"
                                                        data-flag="<?=$account->flag?>"
                                                        data-min_deposit_trans="<?=$account->vip_rule_min_deposit_trans?>"
                                                        data-max_deposit_trans="<?=$account->vip_rule_max_deposit_trans?>"
                                                        data-preset_buttons="<?=$account->preset_amount_buttons?>"
                                                        <?= $tab_toggle ?>
                                                        >
                                                        <?= $bank_icon ?>
                                                        <?= lang($account->payment_type) ?>
                                                    </a>
                                                </li>

                                            <?php endif?>
                                        </div>
                                <?php
                                    endforeach;
                                endif;
                                if (!$isCategoryHasAcc) {
                                    echo '<br/><br/><br/><center><p>' . lang('cat.no.promo') . '</p></center>';
                                }
                                ?>
                            </ul>
                        </div>

                    </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
    </div>

    <?php if($deposit_method == 'auto') :?>
        <?php include __DIR__ . '/../'. $deposit_method . '.php';?>
    <?php else: ?>
        <?php include __DIR__ . '/../' .$deposit_method . '.php';?>
    <?php endif ?>

</div>

<script type="text/javascript">
    var enabled_currency_sign_in_preset_amount = "<?= $this->config->item('enabled_currency_sign_in_preset_amount') ? '1' : '0';?>";
    var enabled_accumulate_preset_amount = "<?= $this->config->item('enabled_accumulate_preset_amount') ? '1' : '0';?>";
    var fn_name = enabled_accumulate_preset_amount == '1' ? 'add_accumulate_preset_amount_buttons' : 'add_preset_amount_buttons';
    var _flag_type = "<?=$second_category_flag?>";
    var enable_custom_view = "<?= $enable_custom_view ? '1' : '0';?>";
    var enable_thousands_separator =  '<?=$this->utils->getConfig('enable_thousands_separator_in_the_deposit_amount')?>';
    var deposit_method = "<?= $deposit_method ?>";

    $('.deposit-second-category-flag .second-category-tab button[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        Loader.show();
        let entry = $('#tab' + $(this).data('category_id') + ' a');
        let p_account_id = entry.data('account_id');

        if (!p_account_id) {
            return false;
        }

        entry.parent('li:first').addClass('active in');

        $('#deposit-tab-content-manual').empty();
        $('#form-deposit').empty();

        if ($(this).data('category_id') !== 1) {//second_category_flag == 1 is 3rd payment
            document.location.href= "<?= $this->utils->getSystemUrl('player') . '/player_center2/deposit/deposit_custom_view/'?>" + p_account_id;
        }else{
            document.location.href= "<?= $this->utils->getSystemUrl('player') . '/player_center2/deposit/auto_payment/'?>" + p_account_id;
        }
    });

    $('.deposit-second-category-flag .sub-accounts-tab a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        let entry = $(this);
        let p_account_id = entry.data('account_id');

        if (!p_account_id) {
            return false;
        }

        $('.payment_accounts-body').find('li').removeClass('active in');
        entry.parent('li').addClass('active in');

        $('#DepositPaymentAccount').val(p_account_id).trigger('change keyup');

        let min_deposit_trans = _export_sbe_t1t.utils.formatCurrency(entry.data('min_deposit_trans'));
        let max_deposit_trans = _export_sbe_t1t.utils.formatCurrency(entry.data('max_deposit_trans'));

        $('.deposit-limit.min span').html(min_deposit_trans);
        $('.deposit-limit.max span').html(max_deposit_trans);

        let bankDepositAmount = $('#bankDepositAmount');
        if (bankDepositAmount.length) {
            bankDepositAmount.attr({
                'min': entry.data('min_deposit_trans'),
                'max': entry.data('max_deposit_trans'),
                'data-min-error': bankDepositAmount.attr('lang-min-error').replace('{0}', min_deposit_trans),
                'data-max-error': bankDepositAmount.attr('lang-max-error').replace('{0}', max_deposit_trans)
            });
        }
        let displayBankDepositAmount = $('#displayBankDepositAmount');
        if (displayBankDepositAmount.length) {
            displayBankDepositAmount.attr({
                'data-stringmin': entry.data('min_deposit_trans'),
                'data-stringmax': entry.data('max_deposit_trans'),
                'data-stringmin-error': displayBankDepositAmount.attr('lang-stringmin-error').replace('{0}', min_deposit_trans),
                'data-stringmax-error': displayBankDepositAmount.attr('lang-stringmax-error').replace('{0}', max_deposit_trans)
            });
        }

        let accPresetButtons = entry.data('preset_buttons');
        let presetBtnContainer = $('.deposit-process-mode-2 .setup-deposit-presetamount');

        if (accPresetButtons !== undefined) {
            let splitBtns = accPresetButtons.split('|');
            let symbol = _export_sbe_t1t.variables.currency.symbol;

            presetBtnContainer.empty();

            splitBtns.forEach((amt, k) => {
                if (amt !== undefined) {
                    let amtText = enabled_currency_sign_in_preset_amount === '1' ? `${symbol} ${amt}` : amt;
                    let appendBtn = `<button type="button" class="btn btn-primary preset-amount" onclick="${fn_name}(this.value);" id="preset_amount_buttons_${k}" value="${amt}">${amtText}</button>`;
                    presetBtnContainer.append(appendBtn);
                }
            });
        }
        loadPaymentAccountDetail(p_account_id);
    });

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
                $(".real-money-content p").html("<?=lang("deposit_category_customize_content_on_pulsa")?>");
                break;
            default:
        }

        let amt_tit_img = '<img class="prepend__img prepend__deposit__amount" src="<?= $this->utils->getSystemUrl('www', '/includes/images/deposit_amount.svg?v='.$this->utils->getCmsVersion()) ?>">';
        $('.setup-deposit-amount .amountTitle').prepend(amt_tit_img);

        let choose_bank_tit_img = '<img class="prepend__img prepend__bank__account" src="<?= $this->utils->getSystemUrl('www', '/includes/images/bank_account.svg?v='.$this->utils->getCmsVersion()) ?>">';
        $('.select-player-deposit-bank-account .step').prepend(choose_bank_tit_img);

        let promo_tit_img = '<img class="prepend__img prepend__promotion" src="<?= $this->utils->getSystemUrl('www', '/includes/images/promotion.svg?v='.$this->utils->getCmsVersion()) ?>">';
        $('.setup-deposit-promo .step, .input_name_2.setup-deposit-promo .input_name_text').prepend(promo_tit_img);

        let proof_tit_img = '<img class="prepend__img prepend__deposit__proof" src="<?= $this->utils->getSystemUrl('www', '/includes/images/deposit_proof.svg?v='.$this->utils->getCmsVersion()) ?>">';
        $('.setup-deposit-uploads .step').prepend(proof_tit_img);

        let proof_doc = '<img class="prepend__img prepend__proof__doc" src="<?= $this->utils->getSystemUrl('www', '/includes/images/c025_img_Icon7.svg?v='.$this->utils->getCmsVersion()) ?>">';
        $('.setup-deposit-uploads .btn-file').prepend(proof_doc);

        let placeholder_lang = $('.format_min_max').text().trim();
        let deposit_amount_title = '<p class="step amountTitle"><label class="control-label"><?=lang('amount.tit.lang')?></label></p>';

        if (deposit_method == 'auto') {
            $('#third_payment-input_type-bank label').prepend(amt_tit_img);
            if (enable_thousands_separator) {
                $('#thousands_separator_amount').attr('placeholder', placeholder_lang);
            }else{
                $('input[name=deposit_amount]').attr('placeholder', placeholder_lang);
            }
        }else{
            $('#displayBankDepositAmount').attr('placeholder', placeholder_lang);
            if (enable_thousands_separator) {
                $('#thousands_separator_amount').before(deposit_amount_title);
                $('#thousands_separator_amount').attr('placeholder', placeholder_lang);
            }else{
                $('#bankDepositAmount').before(deposit_amount_title);
                $('#bankDepositAmount').attr('placeholder', placeholder_lang);
                $('input[name=deposit_amount]').attr('placeholder', placeholder_lang);
            }
        }
        $('#form-deposit .step-icon').hide();
        $('#form-deposit hr').remove();
    });
</script>
<script type="text/javascript">
    // $(document).ready(function() {
    //     let amt_tit_img = '<img src="<?= '/includes/images/' . $clinet_name. '/deposit_amount.svg' ?>">';
    //     $('.setup-deposit-amount .amountTitle').prepend(amt_tit_img);

    //     let choose_bank_tit_img = '<img src="<?= '/includes/images/' . $clinet_name. '/bank_account.svg' ?>">';
    //     $('.select-player-deposit-bank-account .step').prepend(choose_bank_tit_img);

    //     let promo_tit_img = '<img src="<?= '/includes/images/' . $clinet_name. '/promotion.svg' ?>">';
    //     $('.setup-deposit-promo .step').prepend(promo_tit_img);

    //     let proof_tit_img = '<img src="<?= '/includes/images/' . $clinet_name. '/deposit_proof.svg' ?>">';
    //     $('.setup-deposit-uploads .step').prepend(proof_tit_img);

    //     let proof_doc = '<img src="<?= '/includes/images/' . $clinet_name. '/c025_img_Icon7.svg' ?>">';
    //     $('.setup-deposit-uploads .btn-file').prepend(proof_doc);

    //     let placeholder_lang = $('.format_min_max').text().trim();
    //     let deposit_amount_title = '<p class="step amountTitle"><label class="control-label"><?=lang('amount.tit.lang')?></label></p>';

    //     if (true) {}
    //     if (enable_thousands_separator) {
    //         $('#thousands_separator_amount').before(deposit_amount_title);
    //         $('#thousands_separator_amount').attr('placeholder', placeholder_lang);
    //     }else{
    //         $('#bankDepositAmount').before(deposit_amount_title);
    //         $('#bankDepositAmount').attr('placeholder', placeholder_lang);
    //         $('input[name=deposit_amount]').attr('placeholder', placeholder_lang);
    //     }

    //     $('#form-deposit .step-icon').hide();
    //     $('#form-deposit hr').remove();
    // });
</script>
<style>
    #depositTabList li .bank_icon {
        height: 24px;
    }
</style>
<?php include __DIR__ . '/../../../cashier/deposit/modal.php';?>
<?php include __DIR__ . '/../../../bank_account/content/modal.php';?>
<?php endif ?>