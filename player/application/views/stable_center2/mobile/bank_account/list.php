<style>
    .invalid_bank {
        background-color: #C6C6C6 !important;
        opacity:0.6;
    }
</style>

<div id="bank_account" class="panel">
    <div class="panel-heading">
        <h1 class="hidden-xs hidden-sm"><?= lang('cashier.16') ?></h1>
    </div>
    <div class="panel-body bank-account-list sub_content">
        <?php if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_enable_deposit_bank') || $this->config->item('allow_crypto_bank_in_disable_deposit_bank')): ?>
            <ul id="bank_account_tab_nav" class="list-inline nav-justified fm-ul text-center">
                <li><a href="#bank_account_deposit" class="btn btn-info button1 add-deposit-bank-account" data-toggle="tab"><?=lang('xpj.iframe_module.Deposit_Bank')?></a></li>
                <li><a href="#bank_account_withdrawal" class="btn btn-info button1 add-withdrawal-bank-account" data-toggle="tab"><?=lang('xpj.iframe_module.Withdraw_Bank')?></a></li>
            </ul>
            <div class="bank_account_tab_content tab-content fm-content">
                <div id="bank_account_deposit" class="tab-pane fade in">
                    <?php include __DIR__ . '/../../bank_account/content/deposit.php'; ?>
                </div>

                <div id="bank_account_withdrawal" class="tab-pane fade">
                    <?php include __DIR__ . '/../../bank_account/content/withdrawal.php'; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="bank_account_tab_content tab-content fm-content">
                <div id="bank_account_withdrawal" class="tab-pane fade in active">
                    <?php include __DIR__ . '/../../bank_account/content/withdrawal.php'; ?>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>

<script type="text/javascript">
    var EMPTY_ACCOUNT_NAME_REDIRECT_URL = '<?=(!$this->utils->is_mobile()) ? '/player_center/dashboard/index#accountInformation' : '/player_center/profile'?>';
    var ENABLE_SMS_VERIFY_IN_ADD_CRYPTO_BANK_ACCOUNT = '<?= $this->config->item('enable_sms_verify_in_add_crypto_bank_account') ? 'true' : 'false' ?>';
    var ENABLE_SMS_VERIFY_IN_ADD_BANK_ACCOUNT = '<?= $this->config->item('enable_sms_verify_in_add_bank_account') ? 'true' : 'false' ?>';
    var ENABLE_SMS_VERIFY_IN_ADD_EWALLET = '<?= $this->config->item('enable_sms_verify_in_add_ewallet') ? 'true' : 'false' ?>';
    var ENABLE_CPF_NUMBER = '<?= $this->config->item('enable_cpf_number') ? 'true' : 'false' ?>';
    var ENABLE_SWITCH_CPF_TYPE = '<?= $this->config->item('switch_cpf_type') ? 'true' : 'false' ?>';
    var EDIT_CPF_NUMBER_STATUS = '<?= $edit_cpf_number_status; ?>';
    var ENABLED_SET_REALNAME_WHEN_ADD_BANK_CARD = '<?= $this->config->item('enabled_set_realname_when_add_bank_card') ? 'true' : 'false' ?>';

</script>

<?php include __DIR__ . '/../../bank_account/content/modal.php'; ?>