<div id="withdrawal" class="panel">
    <div class="panel-heading">
        <h1 class="hidden-xs hidden-sm"><?= lang('Security') ?></h1>
    </div>
    <div class="withdrawal-customize-content">
        <?php if($this->utils->getConfig('customize_content_withdrawal')) : ?>
            <p><?=lang('customize_content_withdrawal')?></p>
        <?php else : ?>
            <p><?=lang('deposit_category_customize_content')?></p>
        <?php endif; ?>
    </div>
    <div id="fm-withdrawal" class="panel-body withdrawal-form fmd-step1">
        <form name="withdrawForm" role="form" action="<?=site_url('player_center2/withdraw/verify')?>" method="POST">
            <?=$double_submit_hidden_field?>
            <input type="hidden" name="type" value="<?=$type?>">
            <?php if($isAlipayEnabled || $isWeChatEnabled) : ?>
                <ul class="nav inner-tab">
                    <li<?= $isBank ? ' class="active"' : ''?>><a href='<?=site_url('player_center2/withdraw/bank')?>'><?= lang('Bank') ?></a></li>
                    <?php if($isAlipayEnabled) : ?>
                        <li<?= $isAlipay ? ' class="active"' : ''?>><a href='<?=site_url('player_center2/withdraw/alipay')?>'><?= lang('Alipay') ?></a></li>
                    <?php endif; ?>
                    <?php if($isWeChatEnabled) : ?>
                        <li<?= $isWechat ? ' class="active"' : ''?>><a href='<?=site_url('player_center2/withdraw/wechat')?>'><?= lang('WeChat') ?></a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
            <?php
                $step = 1;
                foreach ($display_items as $key => $item) {
                    switch ($item) {
                        case 'RECEIVIING_ACCOUNT': ?>

                            <div class="form-group select-player-withdrawal-bank-account select-player-withdrawal-bank-account-wrapper">
                                <p class="step">
                                    <span class='step-icon'><?=$step++?></span>
                                    <label class="control-label"><?= lang('Please select receiving account') ?><em style="color:red">*</em></label>
                                </p>

                                <div class="form-group has-feedback player-withdrawal-bank-account-content">
                                    <div class="col-md-6 current-withdrawal-bank">
                                        <p><strong><?= lang('Your current receiving account') ?></strong></p>
                                        <ul class="bank-list">
                                            <li class="active">
                                                <a data-toggle="modal" href="#player-banks">
                                                    <?php if(empty($playerBankDetails)) :
                                                        $bankTypeId = $bankDetailsId = $bankName = $bankAccNum = $bankAccName = $bankAddress = $bankBranch = $mobileNum = $bank_icon_url = '';
                                                        ?>
                                                    <?php else :
                                                        $bankTypeId = $playerDefaultBankDetail['bankTypeId'];
                                                        $bankDetailsId = $playerDefaultBankDetail['playerBankDetailsId'];
                                                        $bankName = $playerDefaultBankDetail['bankName'];
                                                        $bankAccNum = $playerDefaultBankDetail['bankAccountNumber'];
                                                        $bankAccName = $playerDefaultBankDetail['bankAccountFullName'];
                                                        $bankAddress = $playerDefaultBankDetail['bankAddress'];
                                                        $bankBranch = $playerDefaultBankDetail['branch'];
                                                        $bankBranch = $bankBranch ?: '';
                                                        $mobileNum = $playerDefaultBankDetail['phone'];
                                                        $mobileNum = $mobileNum ?: '';
                                                        $bank_icon_url = isset($playerDefaultBankDetail['bank_icon_url']) ? $playerDefaultBankDetail['bank_icon_url'] : '';
                                                    endif; ?>
                                                    <i class="fa fa-check-circle" aria-hidden="true"></i>
                                                    <?php if (empty($bank_icon_url)) : ?>
                                                        <span id="activeBankName" class="b-icon <?= !empty($bankTypeId) ? 'bank_'.$bankTypeId : '' ?>">
                                                            &nbsp;<?= !empty($bankName) ? lang($bankName) : lang('No withdrawal account selected'); ?>
                                                        </span>
                                                    <?php else : ?>
                                                        <span id="activeBankName" class="b-icon-custom">
                                                            <img src="<?= $bank_icon_url ?>" />
                                                            &nbsp;<?= !empty($bankName) ? lang($bankName) : lang('No withdrawal account selected'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </a>
                                            </li>
                                        </ul>
                                        <div>
                                            <div class="dispBankInfo <?= !empty($playerDefaultBankDetail) ? '' : 'hide'; ?>" style="font-size:13px;">
                                                <p class='<?=(empty($bankAccNum)) ? 'hide' : ''?> <?=($this->utils->isEnabledFeature("hidden_player_bank_account_number_in_the_withdraw")) ? 'hidden' : ''?>'>
                                                    <strong id='acctnumber_label'><?= lang('pay.acctnumber') ?>:</strong>
                                                    <span id="activeAccNum">
                                            <?= $bankAccNum ?>
                                        </span>
                                                </p>
                                                <p class='<?= (empty($bankAccName)) ? 'hide' : ''?>'>
                                                    <strong><?= lang('pay.acctname') ?>:</strong>
                                                    <span id="activeAccName">
                                            <?= $bankAccName ?>
                                        </span>
                                                </p>
                                                <p class='<?= empty($bankBranch) ? 'hide' : ''?>'>
                                                    <strong id='bankbranch_label'><?= $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('pay.branchname') ?>:</strong>
                                                    <span id="activeBranch">
                                            <?= $bankBranch ?>
                                        </span>
                                                </p>
                                                <p class='<?= empty($mobileNum) ? 'hide' : ''?>'>
                                                    <strong><?= lang('Mobile Number') ?>:</strong>
                                                    <span id="activeMobileNum">
                                            <?= $mobileNum ?>
                                        </span>
                                                </p>
                                                <input type="hidden" id="activeBankTypeIdField" name="bankTypeId" value='' />
                                                <input type="hidden" id="activeBankDetailsIdField" name="bankDetailsId" value='<?= $bankDetailsId ?>' />
                                                <input type="hidden" id="activeAccNameField" name="bankAccName" value='' />
                                                <input type="hidden" id="activeBankCodeField" name="bankCode" value='' />
                                                <input type="hidden" id="activeAccNumField" name="bankAccNum" value='' />
                                                <input type="hidden" id="activeBankAddressField" name="bankAddress" value='' />
                                                <input type="hidden" id="activeCityField" name="city" value='' />
                                                <input type="hidden" id="activeProvinceField" name="province" value='' />
                                                <input type="hidden" id="activeBranchField" name="branch" value='' />
                                                <input type="hidden" id="activeMobileNumField" name="phone" value='' />
                                                <input type="hidden" id="activeRate" name="rate" value='' />
                                            </div>
                                        </div>
                                    <?php if($show_wc_detail):?>
                                        <div class="withdrawal-conditions-button">
                                            <p class="<?=$wc_unfinished ? 'incom' : 'complete';?>">
                                                <span><?=lang('cashier.withdrawal.withdraw_condition');?>:</span>
                                                <span class="status"><?=$wc_unfinished ? lang('cashier.withdrawal.withdraw_condition.incomplete') : lang('cashier.withdrawal.withdraw_condition.completed');?></span>
                                                <a href="<?=site_url('player_center2/withdraw/view_withdraw_condition_detail')?>" target="_blank">
                                                    <button type="button"><?=lang('cashier.withdrawal.withdraw_condition.detail');?></button>
                                                </a>
                                                <p><?= lang('cashier.withdrawal.withdraw_condition.hint') ?></p>
                                            </p>
                                        </div>
                                    <?php endif;?>
                                    </div>
                                    <div class="col-md-6 other-withdrawal-bank">
                                        <p><?= lang('Change your current receiving account') ?></p>
                                        <p>
                                            <a class="change-withdrawal-bank" href="#player-withdrawal-banks" data-toggle="modal">
                                                <?= lang('Select other bank account') ?>
                                            </a>
                                        </p>
                                        <!--
                                        <?php if (Playerbankdetails::AllowAddBankDetail(Playerbankdetails::WITHDRAWAL_BANK, $playerBankDetails)): ?>
                                            <?php if($isBank) : ?>
                                                <p>
                                                    <a class="mc-btn add-bank-account" href="javascript: void(0);" data-bank-type="withdrawal" data-callback="setupSelectPlayerWithdrawalBank" data-payment-type-flag="<?=Financial_account_setting::PAYMENT_TYPE_FLAG_BANK?>">
                                                        <?=lang('Add receiving bank account')?>
                                                    </a>
                                                </p>
                                            <?php elseif($isAlipay || $isWechat) : ?>
                                                <p>
                                                    <a class="mc-btn add-bank-account" href="javascript: void(0);" data-bank-type="withdrawal" data-callback="setupSelectPlayerWithdrawalBank" data-payment-type-flag="<?=Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET?>">
                                                        <?=lang('Add receiving bank account')?>
                                                    </a>
                                                </p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        -->
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <br>
                            <?php break;

                        case 'WITHDRAWAL_AMOUNT': ?>

                            <div class="form-group has-feedback withdrawal-amount-input-wrapper">
                                <p class="step">
                                    <span class='step-icon'><?=$step++?></span>
                                    <label class="control-label"><?= lang('Please Input Withdrawal Amount') ?></label>
                                </p>
                                <?php if($show_player_balance): ?>
                                <div class="player_balance">
                                    <span class='player_balance_text'>
                                        <?=sprintf(lang('withdrawal.player_balance_note'), $this->utils->formatCurrency($main_wallet_balance, false))?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <?php if($this->CI->config->item('custom_withdrawal_amount_note')):?>
                                    <p><?=lang('Withdrawal Amount Note')?></p>
                                <?php else :?>
                                    <p class="withdrawal_note bg bg-danger d-inline"><?=lang('Withdrawal Amount Note')?></p>
                                <?php endif; ?>

                                <?php
                                    $enabled_decimal = !$this->utils->getConfig('disabled_withdrawal_page_decimal');
                                    $enabled_thousands = !$this->utils->getConfig('disabled_withdrawal_page_thousands');
                                ?>

                                <?php
                                if (!empty($withdrawal_preset_amount)) {
                                    include __DIR__ . '/withdrawal_preset_amount.php';
                                }
                                ?>

                                <?php if (!$this->utils->isEnabledFeature('always_auto_transfer_if_only_one_game')) : ?>
                                    <?php if(!$this->CI->config->item('hide_withdrawal_transfer_balance_text')):?>
                                        <p>【<?= lang('Please transfer your balance back to main wallet') ?>】</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="input-group">
                                    <?php if($this->CI->config->item('enable_currency_symbol_in_the_withdraw_amount')):?>
                                        <span class="input-group-addon"><?= $this->utils->getCurrencyLabel()['currency_symbol'] ?></span>
                                    <?php endif; ?>
                                    <?php if($this->CI->config->item('enable_thousands_separator_in_the_withdraw_amount')): ?>
                                        <input type="text" id="thousands_separator_amount" class="form-control" name="thousands_separator_amount"
                                            <?php if (lang('Please Input Withdrawal Amount For Placeholder')) : ?>
                                                placeholder="<?= lang('Please Input Withdrawal Amount For Placeholder') ?>"
                                            <?php endif; ?>
                                            style="width:260px"
                                            onChange = "display_thousands_separator()"
                                            onkeyup = "display_thousands_separator()"
                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                            />
                                    <?php endif; ?>
                                    <input type="number" step="<?=$withdraw_amount_step_limit?>" class="form-control" id="amount" name="amount"
                                        <?php if (lang('Please Input Withdrawal Amount For Placeholder')) : ?>
                                            placeholder="<?= lang('Please Input Withdrawal Amount For Placeholder') ?>"
                                        <?php endif; ?>
                                        min="<?=$withdrawSetting['min_withdraw_per_transaction']?>"
                                        max="<?=$withdrawSetting['max_withdraw_per_transaction']?>"
                                        data-min-error="<?=sprintf(lang('formvalidation.greater_than'), lang('Withdrawal Amount'), $this->utils->formatCurrency($withdrawSetting['min_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal))?>"
                                        <?php if(isset($withdrawSetting['max_withdrawal_non_deposit_player']) && $withdrawSetting['max_withdrawal_non_deposit_player'] == 0 && $this->CI->config->item('display_hint_when_max_withdrawal_non_deposit_player')): ?>
                                            data-max-error="<?=lang('Almost there! You have not depoisted yet. Make a minimal deposit to withdraw!')?>"
                                        <?php else :?>
                                            data-max-error="<?=sprintf(lang('formvalidation.less_than'), lang('Withdrawal Amount'), $this->utils->formatCurrency($withdrawSetting['max_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal))?>"
                                        <?php endif; ?>
                                        data-required-error="<?=lang('Fields with (*) are required.')?>"
                                        data-error="<?=lang('text.error')?>"
                                        <?php if($this->CI->config->item('disable_withdraw_amount_is_decimal')):?>
                                            data-step-error="<?=lang('notify.118')?>"
                                        <?php endif; ?>
                                        style="width:260px" required
                                        oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                        onKeyUp="input_amount_keyup()" />
                                    <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
                                </div>
                                <div class="help-block with-errors"></div>

                                <?php if ($this->utils->getConfig('enable_withdrawl_fee_from_player')) : ?>
                                    <p class="withdraw-fee"><?= lang('fee.withdraw') ?>&nbsp;:&nbsp;<span id="withdraw_fee"></span>&emsp;<span class="fee_hint"><?= lang('fee.withdraw.hint') ?></span></p>
                                <?php endif; ?>

                                <div class="helper-content">
                                    <p class="deposit-limit"><?=lang('Withdraw.Amount.Note')?></p>

                                    <?php if ($this->utils->getConfig('enable_withdraw_times_limit')) :?>
                                        <p class="deposit-limit"><span><?= sprintf(lang('withdraw.times.limit'),$withdrawSetting['withdraw_times_limit']) ?></span></p>
                                    <?php endif; ?>

                                    <?php if (!empty($withdrawal_fee_percentage)) : ?>
                                        <p class="deposit-limit"><span><?= sprintf(lang('fee.withdraw.percentage'),$withdrawal_fee_percentage) ?></span></p>
                                    <?php endif; ?>

                                    <?php if (isset($accumulatedMonthlyWithdrawalAmount)) : ?>
                                        <p class="deposit-limit"><span><?= sprintf(lang('fee.withdraw.accumulatedmonthlyamt'),$this->utils->formatCurrency($accumulatedMonthlyWithdrawalAmount, $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal)) ?></span></p>
                                    <?php endif; ?>

                                    <?php if(stristr(lang('Min withdraw per transaction'),'%s') && stristr(lang('Max withdraw per transaction'),'%s') && stristr(lang('Daily Limit'),'%s')) :?>
                                        <p class="deposit-limit min">
                                            <?=sprintf(lang('Min withdraw per transaction'), '<span>'.$this->utils->formatCurrency($withdrawSetting['min_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal).'</span>'); ?>
                                        </p>
                                        <?php if($showMaxWithdrawalPerTransaction):?>
                                            <p class="deposit-limit max">
                                                <?=sprintf(lang('Max withdraw per transaction'), '<span>'.$this->utils->formatCurrency($withdrawSetting['max_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal).'</span>'); ?>
                                            </p>
                                        <?php endif;?>
                                        <?php if($showDailyMaxWithdrawalAmount):?>
                                            <?php if(lang('Daily Limit')) : ?>
                                                <p class="deposit-limit">
                                                    <?=sprintf(lang('Daily Limit'), '<span>'.$this->utils->formatCurrency($withdrawSetting['daily_max_withdraw_amount'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal).'</span>'); ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php endif;?>
                                    <?php else: ?>
                                        <?php if($this->utils->getConfig('format_max_min_transaction')) :?>
                                            <style type="text/css">
                                                .deposit-limit.min{
                                                    display: inline;
                                                }
                                                .deposit-limit.max{
                                                    display: inline;
                                                    padding: 0;
                                                    margin: 0;
                                                }
                                            </style>
                                            <p class="deposit-limit min"><?=  sprintf(lang('format_withdraw_max_min_transaction'), '<span>'. $this->utils->formatCurrency($withdrawSetting['min_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal) .'</span></p>' , ($showMaxWithdrawalPerTransaction) ? '<p class="deposit-limit max"> <span>' . $this->utils->formatCurrency($withdrawSetting['max_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal) . '</span></p>' : '') ;?>
                                        <?php else: ?>

                                            <p class="deposit-limit min">
                                                <?=lang('Min withdraw per transaction')?> : <span><?=$this->utils->formatCurrency($withdrawSetting['min_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal)?></span>
                                            </p>
                                            <?php if($showMaxWithdrawalPerTransaction):?>
                                                <p class="deposit-limit max">
                                                    <?=lang('Max withdraw per transaction')?> : <span><?=$this->utils->formatCurrency($withdrawSetting['max_withdraw_per_transaction'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal)?></span>
                                                </p>
                                            <?php endif;?>
                                        <?php endif ?>

                                        <?php if($showDailyMaxWithdrawalAmount):?>
                                            <?php if(lang('Daily Limit')) : ?>
                                                <p class="deposit-limit"><?= lang('Daily Limit') ?>: <span><?=$this->utils->formatCurrency($withdrawSetting['daily_max_withdraw_amount'], $this->utils->isEnabledFeature('enable_currency_symbol_in_the_withdraw'), $enabled_thousands, $enabled_decimal)?> </span></p>
                                            <?php endif; ?>
                                        <?php endif;?>
                                    <?php endif ?>

                                    <?php if(!empty($withdrawal_start_time) && !empty($withdrawal_end_time)): ?>
                                        <p class="deposit-limit"><span><?= sprintf(lang('withdraw_start_end_period'),$withdrawal_start_time, $withdrawal_end_time) ?></span></p>
                                    <?php endif;?>

                                    <?php if(!empty($this->utils->getConfig('player_center_withdrawal_page_fee_hint'))): ?>
                                        <p class= "deposit-limit"><?=lang('player_center_withdrawal_page_fee_hint')?></p>
                                    <?php endif;?>
                                </div>
                                <?php if ($this->utils->isEnabledFeature('enable_withdrawal_amount_note')) : ?>
                                    <div class="helper-content text-danger font-weight-bold" style="font-size:12px;">
                                        <p><?=lang('collection_withdrawal_amount')?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div id='crypto_content'>
                                <?php if(isset($cryptocurrencies)){
                                    $step  = '3' ;
                                    if(isset($is_cryptocurrency) && $is_cryptocurrency){
                                            // $cryptocurrency_rate = $cryptocurrencies['USDT'];
                                            include __DIR__ . '/../../bank_account/content/crypto_account/crypto_account_withdrawal.php';
                                        }
                                }?>

                                <?php
                                    if (!empty($withdrawal_crypto_currency)) {
                                        if ($withdrawal_crypto_currency['enabled']) {
                                            include __DIR__ . '/withdrawal_crypto_currency.php';
                                        }
                                    }
                                ?>
                            </div>
                            <div class="clearfix"></div>
                            <br>
                            <?php break;

                        case 'WITHDRAW_VERIFICATION': ?>
                            <?php if($this->utils->getConfig('withdraw_verification') == 'withdrawal_password' && $enabled_withdrawal_password) : ?>
                                <div class="form-group withdraw-verification-wrapper">
                                    <p class="step">
                                        <span class='step-icon'><?=$step++?></span>
                                        <label class="control-label"><?= lang('Please Input Withdrawal Password') ?></label>
                                    </p>
                                    <input type="password" class="form-control" id="password" name="withdrawal_password" placeholder="<?= lang('Type your password') ?>" required>
                                    <input type="hidden" id="hasWithdrawPass" value="1">
                                </div>
                                <div class="clearfix"></div>
                                <br>
                            <?php endif ?>
                           <?php break;

                        case 'SMS_VERIFICATION': ?>
                            <?php if($this->utils->getConfig('enable_sms_verify_in_withdraw')): ?>
                            <div class="form-group has-feedback withdraw-sms-verification-wrapper">
                                <?= $this->CI->load->widget('sms'); ?>
                                <p class="step">
                                    <span class='step-icon'><?=$step++?></span>
                                    <label class="control-label"><?= lang('Please enter verification code:') ?></label>
                                </p>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="fm-verification-code" name="sms_verification_code" required>
                                </div>
                                <div class="top-buffer form-group has-feedback has-input-tip">
                                    <button type="button" class="btn mc-btn top-buffer" id="send_sms_verification_code" onclick="send_verification_code();"><?= lang('Send SMS Verification') ?></button>
                                    <input type="hidden" id="contact_number" value="<?=$player_contact_info['contactNumber']?>">
                                    <input type="hidden" id="dialing_code" value="<?=$player_contact_info['dialing_code']?>">
                                    <input type="hidden" id="verify_phone" value="<?=$player_contact_info['verified_phone']?>">
                                    <div class="modal-body msg-container">
                                        <p id="sms_verification_msg"></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php break;
                        default:
                            break;
                    }
                }
            ?>

            <div class="form-group">
                <input class="btn form-control" id="submitBtn" 
                <?php if ($this->config->item('enabled_withdraw_submit_button')) : ?>
                    disabled="disabled"
                <?php endif; ?>
                value="<?= lang('Withdraw') ?>" type="submit" />
                <p class="step withdraw-pending-message" ><?= lang('Withdraw pending ask customer service') ?></p>
            </div>
        </form>
    </div>
</div>

<!-- Withdrawal Player Bank List Modal -->
<div class="modal fade" id="player-withdrawal-banks" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header text-center">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
				<h4 class="modal-title f24" id="myModalLabel"><?= lang('Select Withdrawal Bank Account') ?></h4>
			</div>

			<div class="modal-body withdrawal-modal-body">
				<div class="clearfix">
					<div class="col-md-12 fmd-step1">
						<?php if(!empty($playerBankDetails)) : ?>
							<ul class="bank-list bank-list-main" id="currentBankList">
							<?php foreach($playerBankDetails as $bank) : ?>
								<li data-id="<?= $bank['playerBankDetailsId'] ?>"
									data-bank-type-id="<?= $bank['bankTypeId'] ?>"
									data-bank-name="<?= lang($bank['bankName']) ?>"
                                    data-bank-code="<?= $bank['bank_code'] ?>"
                                    data-is-crypto="<?= $bank['is_crypto'] ?>"
									data-branch="<?= lang($bank['branch']) ?>"
									data-province="<?= lang($bank['province']) ?>"
									data-city="<?= lang($bank['city']) ?>"
									data-acc-num="<?= $bank['bankAccountNumber'] ?>"
									data-acc-name="<?= lang($bank['bankAccountFullName']) ?>"
									data-mobile-num="<?= lang($bank['phone']) ?>"
                                    data-default="<?=$bank['isDefault']?>"
								>
									<a href="#<?= lang($bank['bankTypeId']) ?>" data-toggle="tab" title="<?= $bank['displayName'] ?>">
										<i class="fa fa-check-circle" aria-hidden="true"></i>
										<?php if (empty($bank['bank_icon_url'])) : ?>
											<span class="b-icon bank_<?= lang($bank['bankTypeId']) ?>">
												<?= $bank['displayName'] ?>
											</span>
										<?php else : ?>
											<span class="b-icon-custom">
												<img src="<?= $bank['bank_icon_url'] ?>" />
												<?= $bank['displayName'] ?>
											</span>
										<?php endif; ?>
									</a>
								</li>
							<?php endforeach; ?>
							</ul>
							<div class="row">
								<div class="text-center col-md-6 top-buffer">
									<button type="button" class="btn btn-primary" id="saveChosenBank"><?= lang("lang.save") ?></button>
								</div>
							</div>
						<?php else : ?>
							<?= lang('No withdrawal bank account available') ?>.
						<?php endif ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
    var enabled_withdrawal_crypto = '<?=$withdrawal_crypto_currency['enabled']?>';
    var enabled_withdraw_submit_button = '<?=$this->config->item('enabled_withdraw_submit_button') ? '1' : '0' ?>';
    var withdraw_cryptocurrencies = '<?=json_encode($withdrawal_crypto_currency['withdraw_cryptocurrencies'])?>';
    var player_crypto_account = JSON.parse('<?=json_encode(isset($player_crypto_account)?$player_crypto_account:[])?>');
    var enable_withdrawl_fee_from_player = '<?=$this->utils->getConfig('enable_withdrawl_fee_from_player')?>';
    var withdraw_cust_crypto_data = JSON.parse('<?=json_encode($cryptocurrencies)?>');
    var enable_sms_verified_phone_in_withdrawal = "<?= $this->utils->getConfig('enable_sms_verified_phone_in_withdrawal') ? '1' : '0' ?>";
    var player_verified_phone = "<?= $player_verified_phone ? '1' : '0' ?>";
    var enable_verified_email_in_withdrawal = "<?= $this->utils->getConfig('enable_verified_email_in_withdrawal') ? '1' : '0' ?>";
    var player_verified_email = "<?= $player_verified_email ? '1' : '0' ?>";
    var enable_withdrawl_bank_fee = JSON.parse('<?=json_encode($this->utils->getConfig('enable_withdrawl_bank_fee'))?>');
    var enable_third_party_api_id_when_withdraw = "<?= !empty($this->utils->getConfig('third_party_api_id_when_withdraw')) ? '1' : '0' ?>";
    var player_filled_birthday = "<?= $player_filled_birthday ? '1' : '0' ?>";
    var enable_show_pop_up_after_submnit_exist_errors = "<?= $this->utils->getConfig('enable_show_pop_up_after_submnit_exist_errors') ? '1' : '0' ?>";
    var submit_after_result_message = "<?= !isset($result['message'])? $result['message'] : '' ?>";
    var enabled_set_realname_when_add_bank_card = '<?= $this->config->item('enabled_set_realname_when_add_bank_card') ? '1' : '0' ?>';
    var player_realname = "<?= !empty($realname)? $realname : '' ?>";

    if (enable_show_pop_up_after_submnit_exist_errors == '1') {
        if(submit_after_result_message){
            MessageBox.danger(submit_after_result_message);
        }
    }

    var enable_thousands_separator_in_the_withdraw_amount =  '<?=$this->utils->getConfig('enable_thousands_separator_in_the_withdraw_amount')?>';

    if(enable_thousands_separator_in_the_withdraw_amount){
        $('#fm-withdrawal #amount').addClass('hide');
        $(document).on("change", "#fm-withdrawal #amount" , function() {
            $('#fm-withdrawal #amount').focus();
        });
    }

    if (enable_withdrawl_fee_from_player) {
        $(document).on("change", "#fm-withdrawal #amount" , function() {
            // withdraw_fee
            var playerId = '<?=$playerId?>';
            var levelId = '<?=$levelId?>';
            var amount = $(this).val();

            $.ajax({
                'url' : '/api/getWithdrawFee/' + playerId,
                'type' : 'POST',
                'dataType' : "json",
                'data': {'levelId' :levelId, 'amount' :amount},
                'success' : function(data){
                    if(data['success']){
                        $('#withdraw_fee').text(data.amount);
                    }
                }
            });
        });
    }

    if (enable_sms_verified_phone_in_withdrawal == '1') {
        if (player_verified_phone == '0') {
            MessageBox.danger("<?=lang('withdrawal.msg8')?>", undefined, function(){
                show_loading();
                window.location.href = '/player_center2/security#withdrawal';
            },
            [
                {
                    'text': '<?=lang('lang.settings')?>',
                    'attr':{
                        'class':'btn btn-primary',
                        'data-dismiss':"modal"
                    }
                }
            ]);
        }
    }

    if (enable_verified_email_in_withdrawal == '1') {
        if (player_verified_email == '0') {
            MessageBox.danger("<?=lang('withdrawal.msg9')?>", undefined, function(){
                show_loading();
                window.location.href = '/player_center2/security#withdrawal';
            },
            [
                {
                    'text': '<?=lang('lang.settings')?>',
                    'attr':{
                        'class':'btn btn-primary',
                        'data-dismiss':"modal"
                    }
                }
            ]);
        }
    }

    if (enable_third_party_api_id_when_withdraw == '1') {
        if(player_filled_birthday == '0'){
            MessageBox.info(lang('promo_custom.birthdate_not_set_yet'),'', function(){
                show_loading();
                window.location.href=EMPTY_ACCOUNT_NAME_REDIRECT_URL;
            }, [
                {
                    'text': '<?=lang('lang.settings')?>',
                    'attr':{
                        'class':'btn btn-primary',
                        'data-dismiss':"modal"
                    }
                }
            ]);
        }
    }

    function send_verification_code() {
        $('#sms_verification_msg').text('<?= lang("Please wait")?>');
        $(".msg-container").show().delay(2000).fadeOut();
        var smsValidBtn = $('#send_sms_verification'),
            smstextBtn  = smsValidBtn.text(),
            mobileNumber = $('#contact_number').val(),
            dialing_code = $('#dialing_code').val(),
            sms_verification_code = $('#fm-verification-code').val();

        if(!mobileNumber || mobileNumber == '') {
            $('#sms_verification_msg').text('<?= lang("Please fill in mobile number")?>');
            $(".msg-container").show().delay(5000).fadeOut();
            $('#contactNumber').focus();
            return;
        }

        SMS_SendVerify(function(sms_captcha_val) {
            var smsSendSuccess = function() {
                    $('#sms_verification_msg').text('<?= lang("SMS sent")?>');
                },
                smsSendFail = function(data=null) {
                    if (data && data.hasOwnProperty('isDisplay') && data['message']) {
                        $('#sms_verification_msg').text(data['message']);
                    } else {
                        $('#sms_verification_msg').text('<?= lang("SMS failed")?>');
                    }
                },
                smsCountDown = function() {
                    var smsCountdownnSec = 60,
                        countdown = setInterval(function(){
                            smsValidBtn.text(smstextBtn + "(" + smsCountdownnSec-- + ")");
                            if(smsCountdownnSec < 0){
                                clearInterval(countdown);
                                smsValidBtn.text(smstextBtn);
                                disableSendBtn(false);
                            }
                        },1000);
                },
                disableSendBtn = function (bool) {
                    if (bool) {
                        smsValidBtn.prop('disabled', true);
                        smsValidBtn.removeClass('btn-success');
                    } else {
                        smsValidBtn.prop('disabled', false);
                        smsValidBtn.addClass('btn-success');
                    }
                };

            disableSendBtn(true);
            var verificationUrl = "<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/" + mobileNumber;
            var enable_new_sms_setting = '<?= !empty($this->utils->getConfig('use_new_sms_api_setting')) ? true : false ?>';

            if (enable_new_sms_setting) {
                verificationUrl = '<?= site_url('iframe_module/iframe_register_send_sms_verification')?>/' + mobileNumber + '/sms_api_withdrawal_setting';
            }

            $.post(verificationUrl, {
                sms_captcha: sms_captcha_val,
                dialing_code: dialing_code
            }).done(function(data){
                (data.success) ? smsSendSuccess() : smsSendFail(data);
                if (data.hasOwnProperty('field') && data['field'] == 'captcha') {
                    disableSendBtn(false)
                } else {
                    smsCountDown();
                }
            }).fail(function(){
                smsSendFail();
                smsCountDown();
            }).always(function(){
                $(".msg-container").show().delay(5000).fadeOut();
            });
        });
    }
</script>