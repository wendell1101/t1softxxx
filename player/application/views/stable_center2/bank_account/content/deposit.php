<?php if (Playerbankdetails::AllowAddBankDetail(Playerbankdetails::DEPOSIT_BANK, $bank_details['deposit'])): ?>
<div class="bank_account_container col-xs-10 col-xs-push-1 col-sm-10 col-sm-push-1 col-md-6 col-md-push-0">
    <div class="bank_account_info empty">
        <div class="bank_name">&nbsp;</div>
        <div class="bank_account_number">&nbsp;</div>
        <div class="bank_account_name">&nbsp;</div>
        <div class="bank_account_helper">
            <button class="btn btn-info">&nbsp;</button>
            <?php if((in_array(Financial_account_setting::PAYMENT_TYPE_FLAG_BANK, $active_flag['deposit']) || in_array(Financial_account_setting::PAYMENT_TYPE_FLAG_PIX, $active_flag['deposit'])) && $this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_enable_deposit_bank')) :?>


                <button class="btn btn-default add-bank-account" data-bank-type="deposit" data-payment-type-flag="<?=Financial_account_setting::PAYMENT_TYPE_FLAG_BANK?>">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    <?=lang('Add Bank Account')?>
                </button>
            <?php endif;?>
            <?php if(in_array(Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET, $active_flag['deposit']) && $this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_enable_deposit_bank')) :?>
                <button class="btn btn-default add-bank-account" data-bank-type="deposit" data-payment-type-flag="<?=Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET?>" style="top: 33%;" >
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    <span><?=lang('Add E-wallet Account')?></span>
                </button>
            <?php endif;?>
            <?php if((in_array(Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO, $active_flag['deposit']) && $this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_enable_deposit_bank')) || (in_array(Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO, $active_flag['deposit']) && $this->config->item('allow_crypto_bank_in_disable_deposit_bank'))) :?>
                <button class="btn btn-default add-bank-account" data-bank-type="deposit" data-payment-type-flag="<?=Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO?>" style="top: 66%;" >
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    <span><?=lang('Add Crypto Account')?></span>
                </button>
            <?php endif;?>
        </div>
    </div>
</div>
<?php endif ?>
<?php foreach ((array)$bank_details['deposit'] as $player_bank_account): ?>
<?php if(!$this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_enable_deposit_bank') && $this->config->item('allow_crypto_bank_in_disable_deposit_bank')): ?>
    <?php if(strpos(strtoupper($player_bank_account['bank_code']), 'USDT') !== false) : ?>
        <div class="bank_account_container col-xs-10 col-xs-push-1 col-sm-10 col-sm-push-1 col-md-6 col-md-push-0">
            <?php $valid_bank = (($player_bank_account['verified'] == Playerbankdetails::VERIFIED) && ($player_bank_account['status'] == Playerbankdetails::STATUS_ACTIVE)) ?>
            <div class="bank_account_info <?= ($valid_bank) ? '': 'invalid_bank'; ?>">
                <?php if($player_bank_account['verified'] != Playerbankdetails::VERIFIED) :?>
                    <span class="disabled-text" style="display: none"><span class="unverified_bank"><?=lang('financial_account.unverified.msg')?></span></span>
                <?php elseif($player_bank_account['status'] != Playerbankdetails::STATUS_ACTIVE) :?>
                    <span class="disabled-text" style="display: none"><span class="deactivated_bank"><?=lang('financial_account.deactivated.msg')?></span></span>
                <?php endif;?>
                <div class="bank_name"><?=Banktype::renderBankEntry($player_bank_account['bankTypeId'], lang($player_bank_account['bankName']), $player_bank_account['bankIcon'])?></div>
                <div class="bank_account_number">
                    <?=lang('xpj.withdraw.bank_account_number')?>:<?=Playerbankdetails::getDisplayAccNum($player_bank_account['bankAccountNumber'])?>
                </div>
                <div class="bank_account_name">
                    <?=lang('xpj.withdraw.bank_account_name')?>: <?=$player_bank_account['bankAccountFullName'];?>
                </div>
                <?php if($valid_bank) :?>
                    <div class="bank_account_helper text-center">
                        <?php if ($player_bank_account['isDefault'] == 1): ?>
                            <button class="btn btn-info disabled">
                                <?=lang('Default account');?>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-info set-default-bank-account" data-bank-type="deposit" data-bank-account-id="<?=$player_bank_account['playerBankDetailsId']?>" data-bank-type-status="<?=$player_bank_account['banktypeStatus']?>" data-err-msg="<?=lang('financial_account.blocked_bank.msg')?>">
                                <?=lang('cashier.110');?>
                            </button>
                        <?php endif ?>
                        <button class="btn btn-info view-bank-account" data-bank-type="deposit" data-bank-account-id="<?=$player_bank_account['playerBankDetailsId']?>" data-bank-type-status="<?=$player_bank_account['banktypeStatus']?>" data-err-msg="<?=lang('financial_account.blocked_bank.msg')?>">
                            <?=lang('View');?>
                        </button>
                        <!--
                        <?php if ($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_allow_edit')): ?>
                            <button class="btn btn-info edit-bank-account" data-bank-type="withdrawal" data-bank-account-id="<?=$player_bank_account['playerBankDetailsId']?>">
                                <?=lang('cashier.99');?>
                            </button>
                        <?php endif ?>
                        -->
                        <?php if ($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_allow_delete') && ($player_bank_account['isDefault'] != 1)): ?>
                            <button class="btn btn-info delete-bank-account" data-bank-type="deposit" data-bank-account-id="<?=$player_bank_account['playerBankDetailsId']?>">
                                <?=lang('cashier.108');?>
                            </button>
                        <?php endif ?>
                    </div>
                <?php endif;?>
            </div>
        </div>
    <?php endif; ?>
<?php else :?>
<div class="bank_account_container col-xs-10 col-xs-push-1 col-sm-10 col-sm-push-1 col-md-6 col-md-push-0">
    <?php $valid_bank = (($player_bank_account['verified'] == Playerbankdetails::VERIFIED) && ($player_bank_account['status'] == Playerbankdetails::STATUS_ACTIVE)) ?>
    <div class="bank_account_info <?= ($valid_bank) ? '': 'invalid_bank'; ?>">
        <?php if($player_bank_account['verified'] != Playerbankdetails::VERIFIED) :?>
            <span class="disabled-text" style="display: none"><span class="unverified_bank"><?=lang('financial_account.unverified.msg')?></span></span>
        <?php elseif($player_bank_account['status'] != Playerbankdetails::STATUS_ACTIVE) :?>
            <span class="disabled-text" style="display: none"><span class="deactivated_bank"><?=lang('financial_account.deactivated.msg')?></span></span>
        <?php endif;?>
        <div class="bank_name"><?=Banktype::renderBankEntry($player_bank_account['bankTypeId'], lang($player_bank_account['bankName']), $player_bank_account['bankIcon'])?></div>
        <div class="bank_account_number">
            <?php if ($this->utils->getConfig('enable_cpf_number')) :?>
                <?php if ($this->utils->getConfig('switch_cpf_type')) :?>
                    <?php if(strpos($player_bank_account['bank_code'], 'PIX_CPF') !== false): ?>
                        <?=lang('financial_account.CPF_number')?>:<?=Playerbankdetails::getDisplayAccNum($player_bank_account['bankAccountNumber'])?>
                    <?php elseif(strpos($player_bank_account['bank_code'], 'PHONE') !== false): ?>
                        <?=lang('financial_account.phone')?>:<?=Playerbankdetails::getDisplayAccNum($player_bank_account['bankAccountNumber'])?>
                    <?php elseif(strpos($player_bank_account['bank_code'], 'EMAIL') !== false): ?>
                        <?=lang('lang.email')?>:<?=Playerbankdetails::getDisplayAccNum($player_bank_account['bankAccountNumber'])?>
                    <?php else :?>
                        <?=lang('financial_account.CPF_number')?>:<?=Playerbankdetails::getDisplayAccNum($player_bank_account['bankAccountNumber'])?>
                    <?php endif;?>
                <?php else :?>
                    <?=lang('financial_account.CPF_number')?>:<?=Playerbankdetails::getDisplayAccNum($player_bank_account['bankAccountNumber'])?>
                <?php endif;?>
            <?php else :?>
                <?=lang('xpj.withdraw.bank_account_number')?>:<?=Playerbankdetails::getDisplayAccNum($player_bank_account['bankAccountNumber'])?>
            <?php endif;?>
        </div>
        <div class="bank_account_name">
            <?=lang('xpj.withdraw.bank_account_name')?>: <?=$player_bank_account['bankAccountFullName'];?>
        </div>
        <?php if($valid_bank) :?>
            <div class="bank_account_helper text-center">
                <?php if ($player_bank_account['isDefault'] == 1): ?>
                    <button class="btn btn-info disabled">
                        <?=lang('Default account');?>
                    </button>
                <?php else: ?>
                    <button class="btn btn-info set-default-bank-account" data-bank-type="deposit" data-bank-account-id="<?=$player_bank_account['playerBankDetailsId']?>" data-bank-type-status="<?=$player_bank_account['banktypeStatus']?>" data-err-msg="<?=lang('financial_account.blocked_bank.msg')?>">
                        <?=lang('cashier.110');?>
                    </button>
                <?php endif ?>
                <button class="btn btn-info view-bank-account" data-bank-type="deposit" data-bank-account-id="<?=$player_bank_account['playerBankDetailsId']?>" data-bank-type-status="<?=$player_bank_account['banktypeStatus']?>" data-err-msg="<?=lang('financial_account.blocked_bank.msg')?>">
                    <?=lang('View');?>
                </button>
                <!--
                <?php if ($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_allow_edit')): ?>
                    <button class="btn btn-info edit-bank-account" data-bank-type="withdrawal" data-bank-account-id="<?=$player_bank_account['playerBankDetailsId']?>">
                        <?=lang('cashier.99');?>
                    </button>
                <?php endif ?>
                -->
                <?php if ($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_allow_delete') && ($player_bank_account['isDefault'] != 1)): ?>
                    <button class="btn btn-info delete-bank-account" data-bank-type="deposit" data-bank-account-id="<?=$player_bank_account['playerBankDetailsId']?>">
                        <?=lang('cashier.108');?>
                    </button>
                <?php endif ?>
            </div>
        <?php endif;?>
    </div>
</div>
<?php endif; ?>
<?php endforeach ?>