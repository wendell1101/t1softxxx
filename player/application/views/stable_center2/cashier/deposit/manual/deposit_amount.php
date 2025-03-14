<div class="row deposit-process-mode-<?=in_array($deposit_process_mode, array('2','3')) ? '2' : $deposit_process_mode ?> setup-deposit-amount">
    <div>
        <?php $step  = $deposit_step++ ;
                if(isset($is_cryptocurrency) && $is_cryptocurrency){
                    include __DIR__ . '/../../../bank_account/content/crypto_account/crypto_account.php';
                } ?>
    </div>
    <div class="form-group has-feedback">
        <p class="step amountTitle"><span class="step-icon"><?=$step?></span><label class="control-label"><?=lang('Please Enter Deposit Amount')?></label>
        </p>
        <?php if(isset($is_cryptocurrency) && $is_cryptocurrency): ?>
            <p class="step cryptoAmountTitle">
              <label class="control-label"><?=sprintf(lang('Converter Currency Deposit Amount'),lang("$defaultCurrency-Yuan"))?></label>
            </p>
            <div class="input-group col col-xs-12 col-sm-12 col-md-4">
                <input type="text" class="form-control w100 noscroll" id="displayBankDepositAmount" readonly
                    name="bankDepositAmount" placeholder="<?=lang('Deposit Amount')?>"
                    lang-stringmin-error="<?=sprintf(lang('formvalidation.deposit_greater_than'), lang('Deposit Amount'), '{0}')?>"
                    lang-stringmax-error="<?=sprintf(lang('formvalidation.less_than'), lang('Deposit Amount'), '{0}')?>"
                    data-required-error="<?=lang('Fields with (*) are required.')?>"
                    data-error="<?=lang('text.error')?>"
                    required="required"
                    onKeyUp="crypto_converter_current_currency()">
                <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
            </div>
            <div class="help-block with-errors noscroll"></div>
            <div class="input-group col col-xs-12 col-sm-12 col-md-4">
                <input type="hidden" step="0.01" class="form-control" id="bankDepositAmount"
                        name="bankDepositAmount" placeholder="<?=lang('Deposit Amount')?>"
                        lang-min-error="<?=sprintf(lang('formvalidation.greater_than'), lang('Deposit Amount'), '{0}')?>"
                        lang-max-error="<?=sprintf(lang('formvalidation.less_than'), lang('Deposit Amount'), '{0}')?>"
                        data-required-error="<?=lang('Fields with (*) are required.')?>"
                        data-error="<?=lang('text.error')?>"
                        required="required"
                        onKeyUp="crypto_converter_current_currency()">
            </div>
        <?php else:?>
            <?php
                if (!empty($preset_amount_buttons)) {
                    include __DIR__ . '/manual_preset_amount.php';
                }
            ?>
            <div class="input-group col col-xs-12 col-sm-12 col-md-4">
                <?php if($this->CI->config->item('enable_thousands_separator_in_the_deposit_amount')): ?>
                    <input type="text"  id="thousands_separator_amount" name="thousands_separator_amount"
                        placeholder="<?=lang('Deposit Amount')?>"
                        onChange = "display_thousands_separator()"
                        oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                        />
                <?php endif; ?>
              <input type="number" step="0.01" class="form-control noscroll" id="bankDepositAmount" name="bankDepositAmount" placeholder="<?=lang('Deposit Amount')?>"
                     lang-min-error="<?=sprintf(lang('formvalidation.greater_than'), lang('Deposit Amount'), '{0}')?>"
                     lang-max-error="<?=sprintf(lang('formvalidation.less_than'), lang('Deposit Amount'), '{0}')?>"
                     data-required-error="<?=lang('Fields with (*) are required.')?>"
                     data-error="<?=lang('text.error')?>"
                     required="required"
                     onKeyUp="crypto_converter_current_currency()">
                <?php if($second_category_flag == 4 && empty($this->utils->getConfig('enable_deposit_custom_view'))):?>
                        <div class="deposit-amount-hint">
                            <?= lang('deposit_amount_hint_on_flag_of_4'); ?>
                        </div>
                <?php endif; ?>
              <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
            </div>
            <div class="help-block with-errors"></div>
        <?php endif ?>
        <div class="helper-content">
            <div class="deposit_note"><?=lang('Deposit Amount Note')?></div>
            <?php if(stristr(lang('Min deposit per transaction'),'%s') && stristr(lang('Max deposit per transaction'),'%s')) :?>
                <p class="deposit-limit min"><?= sprintf(lang('Min deposit per transaction'), '<span data-default="0">0</span>'); ?></p>
                <p class="deposit-limit max">
                <?= sprintf(lang('Max deposit per transaction'), '<span data-default='.$this->utils->getConfig('defaultMaxDepositDaily').'><?=$this->utils->getConfig("defaultMaxDepositDaily")?></span>'); ?></p>
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
                    <div class="format_min_max">
                        <p class="deposit-limit min"><?=  sprintf(lang('format_deposit_max_min_transaction'), '<span data-default="0">0</span></p>' , '<p class="deposit-limit max"> <span data-default='.$this->utils->getConfig('defaultMaxDepositDaily').'><?=$this->utils->getConfig("defaultMaxDepositDaily")?></span>
                    </p>') ;?>
                    </div>
                <?php else: ?>
                    <p class="deposit-limit min"><?=lang('Min deposit per transaction')?>: <span data-default="0">0</span></p>
                    <p class="deposit-limit max"><?=lang('Max deposit per transaction')?>: <span data-default="<?=$this->utils->getConfig('defaultMaxDepositDaily')?>"><?=$this->utils->getConfig('defaultMaxDepositDaily')?></span></p>
                <?php endif ?>
            <?php endif ?>
        </div>
        <?php if(isset($fix_currency_conversion)): ?>
            <p class="deposit-limit exchange-rate-note"><?=$fix_rate_note?></p>
            <p class="deposit-limit"><?=$fix_rate_convert_result_note?></p>
        <?php endif; ?>
        <?php # Hint for deposts
        if($this->utils->isEnabledFeature('show_decimal_amount_hint') && !$isAlipay) :
            ?>
            <div class="decimal-point-hint" style="padding-top:20px">
                <?= lang('Please enter amount with decimal values for faster processing.'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->utils->isEnabledFeature('enable_deposit_amount_note')) : ?>
          <div class="helper-content text-danger font-weight-bold">
              <p><?=lang('collection_deposit_amount')?></p>
          </div>
        <?php endif; ?>
    </div>

    <hr/>
</div>
<script type="text/javascript">
    var enable_thousands_separator_in_the_deposit_amount =  '<?=$this->utils->getConfig('enable_thousands_separator_in_the_deposit_amount')?>';
    if(enable_thousands_separator_in_the_deposit_amount){
        $('#bankDepositAmount').addClass('hide');
        $(document).on("change", "#bankDepositAmount" , function() {
            $('#bankDepositAmount').focus();
        });
    }

    function display_thousands_separator() {
        var float_amount = $('#thousands_separator_amount').val().replace(/,/g, "");
        var housands_separator_amount = _export_sbe_t1t.utils.displayInThousands(float_amount);

        $('#thousands_separator_amount').val(housands_separator_amount);
        $('#bankDepositAmount').val(parseFloat(float_amount)).change();
    }
</script>

