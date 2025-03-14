<?php if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_enable_deposit_bank') || ($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO && $this->config->item('allow_crypto_bank_in_disable_deposit_bank')) ): ?>
<div class="row form-group deposit-process-mode-<?=in_array($deposit_process_mode, array('2','3')) ? '2' : $deposit_process_mode ?> select-player-deposit-bank-account">
    <p class="step"><span class="step-icon"><?=$deposit_step++?></span><label class="control-label"><?=lang('Please select deposit account') ?></label></p>
    <div id="deposit_bank_account_hint" class="helper-content text-danger font-weight-bold hide">
        <p class="deposit_bank_account_hint"><?=lang('player_deposit_bank_account_hint')?></p>
    </div>
    <div class="input-group hidden">
        <input type="hidden" id="activeBankTypeIdField" name="bankTypeId" />
        <input type="text" class="form-control hidden" id="activeBankDetailsIdField" name="bankDetailsId" required="required"
            data-required-error="<?=lang('cashier.deposit.force_setup_player_deposit_bank_hint')?>" />
        <input type="hidden" id="activeAccNameField" name="bankAccName" />
        <input type="hidden" id="activeAccBankCodeField" name="bankAccBankCode" />
        <input type="hidden" id="activeAccNumField" name="bankAccNum" />
        <input type="hidden" id="activeBankAddressField" name="bankAddress" />
        <input type="hidden" id="activeCityField" name="city" />
        <input type="hidden" id="activeProvinceField" name="province" />
        <input type="hidden" id="activeBranchField" name="branch" />
        <input type="hidden" id="activeMobileNumField" name="phone" />
    </div>
    <div class="player-deposit-bank-account-content">
        <div class="col col-xs-12 col-md-6 current-deposit-bank">
            <div class="deposit-bank-account-dropdown" id="deposit-bank-account-dropdown">
                <select class="form-control input-sm change-deposit-bank">
                    <!-- <option value =""  ><?=lang("lang.selectall")?> </option> -->
                    <?php foreach($player_bank_accounts as $player_bank_account) :?>
                        <?php if($player_bank_account['payment_type_flag'] == $payment_type_flag): ?>
                            <option value ="<?php echo $player_bank_account['displayName']?>"
                                    data-id="<?=$player_bank_account['playerBankDetailsId']?>"
                                    data-bank-type-id="<?=$player_bank_account['bankTypeId']?>"
                                    data-bank-name="<?=lang($player_bank_account['bankName'])?>"
                                    data-bank-code="<?= $player_bank_account['bank_code'] ?>"
                                    data-branch="<?=lang($player_bank_account['branch'])?>"
                                    data-province="<?=lang($player_bank_account['province'])?>"
                                    data-city="<?=lang($player_bank_account['city'])?>"
                                    data-acc-num="<?=Playerbankdetails::getDisplayAccNum($player_bank_account['bankAccountNumber'])?>"
                                    data-acc-name="<?=lang($player_bank_account['bankAccountFullName'])?>"
                                    data-mobile-num="<?=lang($player_bank_account['phone'])?>"
                                    <?php echo $player_bank_account['isDefault']  ? 'selected' : '' ?>
                                >
                                <?php echo $player_bank_account['displayName']?> 
                            </option>
                        <?php endif;?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <div class="dispBankInfo">
                    <p>
                        <strong><?=lang('pay.acctnumber') ?>:</strong>
                        <span id="activeAccNum"></span>
                    </p>
                    <p>
                        <strong><?=lang('pay.acctname.player') ?>:</strong>
                        <span id="activeAccName"></span>
                    </p>
                    <p <?php if($hide_bank_branch_in_payment_account_detail_player_center){?> class="hidden"<?php } ?> >
                        <strong><?= $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('pay.branchname') ?>:</strong>
                        <span id="activeBranch"></span>
                    </p>
                    <p <?php if($hide_mobile_in_payment_account_detail_player_center){?> class="hidden"<?php } ?> >
                        <strong><?=lang('Mobile Number') ?>:</strong>
                        <span id="activeMobileNum"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php if($enabled_quick_add_account_button): ?>
        <button type="button" id="add_deposit_acc" >
            <i class="fa fa-plus" aria-hidden="true"></i>
        </button>
    <?php endif; ?>

    <div class="clearfix"></div>
    <div class="help-block with-errors"></div>

    <hr/>
</div>
<?php endif; ?>
<script type="text/javascript">
    $(function(){

        const enabled_quick_add_account_button = '<?=$enabled_quick_add_account_button?>';
        const payment_type_flag = <?=$payment_type_flag?>;
        const payment_account_id = <?=$payment_account_id?>;

        if (enabled_quick_add_account_button) {
            $('#add_deposit_acc').on('click', function(){
                document.location.href = "/player_center2/bank_account?source=deposit_page&source_type=deposit&source_id=" + payment_account_id + "&source_flag=" + payment_type_flag;
            });
        }

        $("#deposit-bank-account-dropdown .change-deposit-bank").on("change", function(){
            var load_bank_data;
            enable_crypto_currency = $('#crypto').length > 0 ? true : false;
            var $chosenBank = $('#deposit-bank-account-dropdown .change-deposit-bank option:selected');
            //Add chosen bank when no default account in the payment type
            if ($chosenBank.length <= 0 && $('#deposit-bank-account-dropdown .change-deposit-bank').length > 0) {
                $chosenBank = $('#deposit-bank-account-dropdown .change-deposit-bank option:nth-child(1)');
                $chosenBank.prop("selected", true);
            }

            // Hack (append to last event.) - for waiting other jquery events.
            if(enable_crypto_currency){
                if (typeof player_crypto_account['not_exist'] != "undefined") {
                    $(function () {
                        // Check the player deposit bank is empty.
                        MessageBox.info(
                            lang('Please bind a crypto wallet before using this method').toString().replace('%s', player_crypto_account['not_exist']), null, function () {
                                document.location.href = "/player_center2/bank_account#bank_account_deposit";
                            },
                            [{
                                'attr': {'class': 'btn btn-primary'},
                                'text': lang('pay.reg')
                            }]
                        );
                    });
                }
            }else if (force_setup_player_deposit_bank_if_empty == 1) {
                $(function () {
                    // Check the player deposit bank is empty.
                    if ($chosenBank.length <= 0) {
                        MessageBox.info(
                            lang('cashier.deposit.force_setup_player_deposit_bank_hint'), null, function () {
                                document.location.href = "/player_center2/bank_account#bank_account_deposit";
                            },
                            [{
                                'attr': {'class': 'btn btn-primary'},
                                'text': lang('pay.reg')
                            }]
                        );
                    }
                });
            }
            if (force_setup_player_withdraw_bank_if_empty == 1) {
                $(function () {
                    // Check the player deposit bank is empty.
                    MessageBox.info(
                        lang('cashier.deposit.force_setup_player_withdraw_bank_hint'), null, function () {
                            document.location.href = "/player_center2/bank_account#bank_account_withdrawal";
                        },
                        [{
                            'attr': { 'class': 'btn btn-primary' },
                            'text': lang('pay.reg')
                        }]
                    );
                });
            }
            if ($chosenBank.length <= 0) {
                return;
            }

            load_bank_data = {
                'bankTypeId': $chosenBank.data('bank-type-id'),
                'bankDetailsId': $chosenBank.data('id'),
                'bankName': $('span', $chosenBank).html(),
                'bankCode': $chosenBank.data('bankCode'),
                'branch': $chosenBank.data('branch'),
                'province': $chosenBank.data('province'),
                'city': $chosenBank.data('city'),
                'accNum': $chosenBank.data('acc-num'),
                'accName': $chosenBank.data('acc-name'),
                'mobileNum': $chosenBank.data('mobile-num')
            };
        loadPlayerDepositBank(load_bank_data);
        });
    });
</script>