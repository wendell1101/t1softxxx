<style>
    .view_bank{
        list-style: none;
        overflow: hidden;
        padding: 10px 10px 0 10px;
        box-sizing: border-box;
    }
</style>
<div class="view_bank">
    <ul class="bank-list bank-list-main">
        <li data-bank-name="<?=lang($bankTypeDetail->bankName) ?>" data-bank-type-id="<?=$bankTypeDetail->bankTypeId?>" class="active">
            <a href="javascript: void(0)">
                <i class="fa fa-check-circle" aria-hidden="true"></i>
                <?=Banktype::renderBankEntry($bankTypeDetail->bankTypeId, lang($bankTypeDetail->bankName), $bankTypeDetail->bankIcon)?>
            </a>
        </li>
    </ul>
</div>

<?php
    if ($this->utils->getConfig('enable_custom_crypto_bank_lang')) {
        $bankAccountLang = lang('financial_account.bankaccount.crypto');
        $financial_account_name = lang('financial_account.name.crypto');
    }else{
        $bankAccountLang = lang("financial_account.bankaccount");
        $financial_account_name = lang("financial_account.name");
    }
?>

<div id="fields" class="row">
    <!-- Account -->
    <div class="col-md-6 top-buffer">
        <span><?= $bankAccountLang ?>:</span>
        <input type="text" class="form-control" value="<?=$playerBankDetail->bankAccountNumber?>" readonly/>
    </div>

    <!-- Name -->
    <?php if(in_array(Financial_account_setting::FIELD_NAME, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer">
            <span><?= $financial_account_name ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->bankAccountFullName?>" readonly/>
        </div>
    <?php endif;?>

    <?php if($this->utils->getConfig('enable_crypto_details_in_crypto_bank_account') && in_array($bankTypeDetail->bankTypeId, $this->utils->getConfig('enable_crypto_details_in_crypto_bank_account')) && $playerBankDetail->dwBank == Playerbankdetails::WITHDRAWAL_BANK):?>
        <div class="col-md-6 top-buffer">
            <span><?= lang("financial_account.cryptousername") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerCryptoBankDetail->crypto_username?>" readonly/>
        </div>

        <div class="col-md-6 top-buffer">
            <span><?= lang("financial_account.cryptoemail") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerCryptoBankDetail->crypto_email?>" readonly/>
        </div>
    <?php endif;?>

    <!-- Crypto Networks -->
    <?php if(in_array(Financial_account_setting::FIELD_NETWROK, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer">
            <span><?= lang("financial_account.crypto_network") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->branch?>" readonly/>
        </div>
    <?php endif;?>
</div>