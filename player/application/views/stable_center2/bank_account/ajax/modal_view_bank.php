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

<div id="fields" class="row">
    <?php if(!$this->utils->getConfig('enable_cpf_number')): ?>
        <!-- Account -->
        <div class="col-md-6 top-buffer">
            <span><?= lang("financial_account.bankaccount") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->bankAccountNumber?>" readonly/>
        </div>
    <?php endif;?>

    <!-- Name -->
    <?php if(in_array(Financial_account_setting::FIELD_NAME, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer">
            <span><?= lang("financial_account.name") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->bankAccountFullName?>" readonly/>
        </div>
    <?php endif;?>

    <!-- Area/Province -->
    <?php if(in_array(Financial_account_setting::FIELD_BANK_AREA, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer">
            <span><?= lang("financial_account.province") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->province?>" readonly/>
        </div>
    <?php endif;?>

    <!-- Area/City -->
    <?php if(in_array(Financial_account_setting::FIELD_BANK_AREA, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer">
            <span><?= lang("financial_account.city") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->city?>" readonly/>
        </div>
    <?php endif;?>

    <!-- Branch -->
    <?php if(in_array(Financial_account_setting::FIELD_BANK_BRANCH, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer">
            <span><?= $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('financial_account.branch') ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->branch?>" readonly/>
        </div>
    <?php endif;?>

    <!-- Phone -->
    <?php if(in_array(Financial_account_setting::FIELD_PHONE, $account_validator['field_show'])) :?>
        <div class="col-md-6 top-buffer">
            <span><?= lang("financial_account.phone") ?>:</span>
            <input type="number" class="form-control" value="<?=$playerBankDetail->phone?>" readonly/>
        </div>
    <?php endif;?>

    <!-- Bank Address -->
    <?php if(in_array(Financial_account_setting::FIELD_BANK_ADDRESS, $account_validator['field_show'])) :?>
        <div class="col-md-12 top-buffer">
            <span><?= lang("financial_account.address") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->bankAddress?>" readonly/>
        </div>
    <?php endif;?>

    <?php if($this->utils->getConfig('enable_cpf_number')): ?>
        <div class="col-md-12 top-buffer">
            <span><?= lang("financial_account.pixtype") ?>:</span>
            <input type="text" class="form-control" value="<?=$playerBankDetail->pixType?>" readonly/>
        </div>
    <?php endif;?>

    <?php if($this->utils->getConfig('enable_cpf_number')): ?>
        <?php if($this->utils->getConfig('switch_cpf_type')): ?>
            <div class="col-md-12 top-buffer">
                <span><?=$playerBankDetail->pixKeyLabel?></span>
                <input type="text" class="form-control" value="<?=$playerBankDetail->pixKey?>" readonly/>
            </div>
        <?php else: ?>
            <div class="col-md-12 top-buffer">
                <span><?= lang("financial_account.CPF_number") ?>:</span>
                <input type="text" class="form-control" value="<?=$cpf_number?>" readonly/>
            </div>
        <?php endif;?>
    <?php endif;?>
</div>