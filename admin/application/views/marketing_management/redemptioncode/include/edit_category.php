<div id="edit_category_modal" class="modal fade" tabindex="-1" role="dialog">
    <form id="edit_category_form">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?= lang('redemptionCode.redemptionCode'); ?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">

                                <!-- Name -->
                                <div class="col-md-3">
                                    <div class="form-group required">
                                        <label for="categoryName" class="control-label"><?= lang('redemptionCode.categoryName'); ?><span class="text-danger"></span></label>
                                        <input type="text" name="categoryName" class="form-control input-sm" id="categoryName" required>
                                    </div>
                                </div>

                                <!-- Quantity -->
                                <!-- <div class="col-md-3">
                                    <div class="form-group required">
                                        <label for="quantity" class="control-label"><?= lang('redemptionCode.quantity'); ?><span class="text-danger"></span></label>
                                        <input type="number" name="quantity" class="form-control input-sm" id="quantity">
                                    </div>
                                </div> -->

                                <!-- bonus -->
                                <div class="col-md-3">
                                    <div class="form-group required">
                                        <label for="bonus" class="control-label"><?= lang('redemptionCode.bonus'); ?><span class="text-danger"></span></label>
                                        <input type="number" name="bonus" class="form-control input-sm" id="bonus" required>
                                    </div>
                                </div>

                                <!-- expire_time -->
                                <div class="col-md-3">
                                    <div class="form-group required">
                                        <input type="hidden" name="appEndDate" value="" style="width:100%" id="appEndDate" class="form-control input-sm" required="">
                                        <label class="control-label"><?= lang('redemptionCode.apply_expire_time'); ?><span class="text-danger"></span></label>
                                        <!-- <i class="btn btn-default btn-xs" data-toggle="tooltip" title="Promo will hide automatically at this date and time" data-placement="right" style="height:18px;" rel="tooltip">?</i> -->
                                        <input type="text" name="hideDate" id="hideDate" class="form-control input-sm dateInput user-success" data-time="true" data-future="true" style="width:100%" required="">
                                    </div>
                                </div>
                                <!-- Allow duplicate apply -->
                            </div>
                        </div>
                        <!-- Withdraw condition -->
                        <div class="col-md-12 withdraw_condition_block">
                            <div class="col-md-8">
                                <fieldset>
                                    <legend>
                                        <h4>* <?= lang('cms.betCondition') ?></h4>
                                    </legend>
                                    <div class="condition-item">

                                        <input type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_BONUS_TIMES; ?>" id="withdrawRequirementBettingConditionOption5">
                                        <label class="control-label bonusCondLbl1" style="font-size:10px;"><?= lang('cms.betAmountCondition2'); ?> </label>
                                        <input type="number" step='any' name="withdrawReqBonusTimes" min="1" id="withdrawReqBonusTimes" style="width:80px;" class="form-control input-sm number_only">
                                    </div>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT; ?>" id="withdrawRequirementBettingConditionOption1">
                                        <label class="control-label"><span title="<?= lang('cms.greaterThan'); ?>" data-toggle="tooltip" rel="tooltip">(&#8805;)</span> <?= lang('cms.betAmount'); ?> </label>
                                        <input type="number" min="1" step="any" name="withdrawReqBetAmount" id="withdrawReqBetAmount" style="width:120px;" class="form-control input-sm amount_only">
                                    </div>
                                    <div class="condition-item">

                                        <input class="control-label bonusCondLbl1" type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_NOTHING; ?>" id="withdrawRequirementBettingConditionOption3" data-toggle="tooltip" title="<?= lang('cms.noWithdrawReqRisk') ?>" data-placement="top" rel="tooltip">
                                        <label class="control-label"><?= lang('cms.no_any_withdraw_condtion'); ?> </label>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <div class="col-md-12 withdraw_condition_block">
                            <div class="col-md-8">
                                <fieldset>
                                    <legend>
                                        <h4>* <?= lang('Minimum Deposit Requirements') ?></h4>
                                    </legend>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementDepositConditionOption" value="<?php echo Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT; ?>" id="withdrawRequirementDepositConditionOption2">
                                        <label class="control-label"><?= lang('pay.mindepamt'); ?> <span title="<?= lang('cms.greaterThan'); ?>" data-toggle="tooltip" rel="tooltip">(&#8805;)</span></label>
                                        <input type="number" min="1" step="any" name="withdrawReqDepMinLimit" id="withdrawReqDepMinLimit" style="width:120px;" class="form-control input-sm amount_only">
                                    </div>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementDepositConditionOption" value="<?php echo Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION; ?>" id="withdrawRequirementDepositConditionOption3">
                                        <label class="control-label"><?= lang('cms.totalDepAmountSinceRegistration'); ?> <span title="<?= lang('cms.greaterThan'); ?>" data-toggle="tooltip" rel="tooltip">(&#8805;)</span></label>
                                        <input type="number" min="1" step="any" name="withdrawReqDepMinLimitSinceRegistration" id="withdrawReqDepMinLimitSinceRegistration" style="width:120px;" class="form-control input-sm amount_only">
                                    </div>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementDepositConditionOption" value="<?php echo Promorules::DEPOSIT_CONDITION_TYPE_NOTHING; ?>" id="withdrawRequirementDepositConditionOption1">
                                        <label class="control-label"><?= lang('cms.no_any_deposit_condtion'); ?> </label>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <div class="col-md-12 m-t-25">
                            <div class="form-group">
                                <label for="catenote" class="control-label"><?= lang('cms.Remark'); ?>:</label><br>
                                <textarea name="catenote" id="catenote" class="form-control input-sm" style="width:100%" rows="10" required="" aria-invalid="true"></textarea>
                                <!-- <span class="text-danger help-block" id="addDescMaxCharacters" hide>*<?= lang('cms.Maximum 60 characters'); ?></span> -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?= lang('lang.cancel'); ?></button>
                    <button type="button" class="btn btn-scooter" id="updateCategory"><?= lang('lang.save'); ?></button>
                </div>
            </div>
        </div>
    </form>
</div>