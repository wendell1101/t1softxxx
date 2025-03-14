<div class="row form-group deposit-process-mode-<?=in_array($deposit_process_mode, array('2','3')) ? '2' : $deposit_process_mode ?> select-payment-account">
    <!-- <p class="step"><span class="step-icon"><?=$deposit_step++?></span>
        <label class="control-label"><?=lang('Deposit To')?></label>
    </p> -->
    <div class="input-group hidden">
        <input type="text" class="hidden" id="DepositPaymentAccount" name="DepositPaymentAccount" value="" required="required" data-required-error=""/>
    </div>
    <div class="col-xs-12 deposit-payment-account-info nopadding">
        <div class="bank-list-container hide">
            <?php if(($deposit_process_mode === DEPOSIT_PROCESS_MODE2) || ($deposit_process_mode === DEPOSIT_PROCESS_MODE3) ): ?>
                <?php if($this->utils->isEnabledFeature('enable_deposit_category_view') === false) : ?>
                    <button type="button" class="bank-entry active">
                        <a href="javascript: void(0);" class="active-payment-account-info">
                            <i class="fa fa-check-circle" aria-hidden="true"></i>
                            <span id="active-payment-account-id" class="b-icon">
                                &nbsp;<?=lang('No payment account selected'); ?>
                            </span>
                        </a>
                        <i class="fa fa-caret-down" aria-hidden="true"></i>
                    </button>
                <?php endif ?>
            <?php endif ?>

            <ul id="bankDepositPanel" class="bank-list">
                <?php if (empty($payment_manual_accounts)):?>
                    <p><?=lang("No Availble Bank Deposit")?></p>
                <?php else:?>
                    <?php foreach ($payment_manual_accounts as $key => $payment_account): ?>
                        <li data-payment_account_id="<?=$payment_account->payment_account_id?>"
                            data-bank="<?=$payment_account->bankTypeId?>"
                            data-flag="<?=$payment_account->flag?>"
                            data-min_deposit_trans="<?=$payment_account->vip_rule_min_deposit_trans?>"
                            data-max_deposit_trans="<?=$payment_account->vip_rule_max_deposit_trans?>">
                            <a href="" data-toggle="tab" title="<?=sprintf('%s - %s', lang($payment_account->payment_type), $payment_account->payment_account_name)?>">
                                <i class="fa fa-check-circle" aria-hidden="true"></i>
                                <?php if(empty($payment_account->account_icon_url)): ?>
                                    <span class="b-icon bank_<?=$payment_account->bankTypeId?>"><?=sprintf('%s - %s', lang($payment_account->payment_type), $payment_account->payment_account_name)?></span>
                                <?php else: ?>
                                    <span class="b-icon-custom"><img src="<?=$payment_account->account_icon_url?>" /><?=sprintf('%s - %s', lang($payment_account->payment_type), $payment_account->payment_account_name)?></span>
                                <?php endif ?>
                            </a>
                        </li>
                    <?php endforeach;?>
                <?php endif?>
            </ul>
        </div>

        <div class="payment-account-detail">
            <div class="col col-md-12 qrcode_group">
                <span id="active-payment-account-image"><img src="" /></span>
                <div class="qrcode_img_copy text-center">
                    <button id="qrcode_img_copy_text" class="btn btn-copy btn-info hide"
                        data-clipboard-action="copy">
                        <?=lang('Copy Qrcode Message')?>
                    </button>
                </div>
            </div>
            <div class="col col-md-12 t1_form_wrapper">
                    <!-- <p id="active-payment-account-bank-name-block-custom">
                        <strong><?=lang('Bank Name') ?>:</strong>
                        <span id="active-payment-account-bank-name-custom"></span>
                    </p> -->
                <div class="form_content col-md-6">
                    <!-- Bank Account Name -->

                    <label class="control-label" id="dpa_acc_name"><?=lang('pay.acctname') ?></label><br>
                    <span id="active-payment-account-name"></span>
                    <button type="button" class="btn btn-copy pull-right"
                        data-clipboard-action="copy"
                        data-clipboard-target="#active-payment-account-name"
                        title="<?=lang('Copied')?>">
                        <i class="fa fa-clipboard" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="form_content col-md-6">
                    <!-- Bank Account # -->
                    <label class="control-label" id="dpa_acc_nunber"><?= $second_category_flag == '9' ? lang('Crypto Address') : lang('pay.acctnumber') ?></label><br>
                    <span id="active-payment-account-number"></span>
                    <button type="button" class="btn btn-copy pull-right"
                        data-clipboard-action="copy"
                        data-clipboard-target="#active-payment-account-number"
                        title="<?=lang('Copied')?>">
                        <i class="fa fa-clipboard" aria-hidden="true"></i>
                    </button>
                </div>
                
                <?php if(!$hide_bank_branch_in_payment_account_detail_player_center && !$isAlipay && !$isUnionpay && !$isWechat):?>
                    <p>
                        <strong><?= $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('pay.branchname') ?>:</strong>
                        <span id="active-payment-account-branch-name"></span>
                        <button type="button" class="btn btn-copy pull-right"
                            data-clipboard-action="copy"
                            data-clipboard-target="#active-payment-account-branch-name"
                            title="<?=lang('Copied')?>">
                            <?=lang('Copy')?>
                        </button>
                    </p>
                <?php endif;?>

            </div>
        </div>
    </div>

    <div class="clearfix"></div>
    <div class="help-block with-errors"></div>
</div>

<script type="text/javascript">
    $(function () {
        $('#qrcode_img_copy_text').click(function(event) {
            alert("<?= lang("Copied")?>");
        });
    })
</script>