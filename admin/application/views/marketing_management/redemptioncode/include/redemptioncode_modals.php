<style type="text/css">
    #add_category_modal textarea, #edit_category_modal textarea {
        resize: none;
    }

    .withdraw_condition_block .condition-item {
        display: flex;
        margin: 1.5rem 0;
        align-items: flex-start;
        align-items: baseline;
    }

    .withdraw_condition_block .control-label {
        padding: 1rem;
    }
</style>
<!-- add redemption Code category modal add_category_modal -->
<div id="add_category_modal" class="modal fade" tabindex="-1" role="dialog">
    <form id="add_category_form">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?= lang('redemptionCode.addNewCategory'); ?></h5>
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
                                        <span class="text-danger help-block m-b-0 addCategoryAlert" id="categoryNameAlert" hide>*<?= lang('Required'); ?></span>
                                        <span class="text-danger help-block m-b-0 addCategoryAlert" id="categoryNameErrorMsg" hide></span>
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
                                        <span class="text-danger help-block m-b-0 addCategoryAlert" id="bonusAlert" hide>*<?= lang('redemptionCode.setup.bonusLimit'); ?></span>
                                    </div>
                                </div>

                                <!-- expire_time -->
                                <div class="col-md-3">
                                    <div class="form-group required">
                                        <input type="hidden" name="appEndDate" value="" style="width:100%" id="appEndDate" class="form-control input-sm" required="">
                                        <label class="control-label"><?= lang('redemptionCode.apply_expire_time'); ?><span class="text-danger"></span></label>
                                        <!-- <i class="btn btn-default btn-xs" data-toggle="tooltip" title="Promo will hide automatically at this date and time" data-placement="right" style="height:18px;" rel="tooltip">?</i> -->
                                        <input type="text" name="hideDate" id="hideDate" class="form-control input-sm dateInput user-success" data-time="true" data-future="true" style="width:100%" required="">
                                        <span class="text-danger help-block m-b-0 addCategoryAlert" id="appEndDateAlert" hide>*<?= lang('Required'); ?></span>
                                    </div>
                                    <div class="input-group form-group">
                                        <input type="checkbox" name="isValidForever" id="isValidForever">&nbsp;
                                        <label class="control-label" for="isValidForever"><?= lang('redemptionCode.validForever') ?></label>
                                    </div>
                                </div>
                                <!-- Allow duplicate apply -->
                            </div>
                        </div>
                        <div class="col-md-12" id="bonusConditionByApplicationSec">
                            <div class="col-md-8">
                                <fieldset>
                                    <legend>
                                        <h4><?= lang('cms.singleOrMultiple'); ?>
                                            <i class="btn btn-default btn-xs" data-toggle="tooltip" title="<?= lang('cms.nonDepSuccession') ?>" data-placement="top" rel="tooltip">?</i>
                                        </h4>
                                    </legend>
                                    <div class="col-md-6 form-group">
                                        <!-- bonusApplicationLimitDateType : 0=none, 1=daily, 2=weekly, 3=monthly, 4=yearly -->
                                        <?php echo lang('Limit Date Type'); ?>:
                                        <select name="bonusApplicationLimitDateType" id="bonusApplicationLimitDateType" class="form-control">
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE; ?>"><?php echo lang('None'); ?></option>
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY; ?>"><?php echo lang('Daily'); ?></option>
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY; ?>"><?php echo lang('Weekly'); ?></option>
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY; ?>"><?php echo lang('Monthly'); ?></option>
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY; ?>"><?php echo lang('Yearly'); ?></option>
                                        </select>
                                    </div>

                                    <div class="col-md-12 form-group nopadding">
                                        <div class="col-md-6">
                                            <input type="radio" name="bonusReleaseTypeOptionByNonSuccessionLimitOption" value="<?= Promorules::BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT ?>" id="bonusConditionByApplicationWithLimit">
                                            <label class="control-label"><?= lang('cms.withLimit'); ?> </label>
                                            <input type="number" step='1' name="limitCnt" id="bonusConditionByApplicationLimitCnt" min="1" style="width:50%;" class="form-control input-sm number_only">
                                            <span class="text-danger help-block m-b-0 addCategoryAlert limitCntAlert" hide>*<?= lang('Limit should be > 0'); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="radio" name="bonusReleaseTypeOptionByNonSuccessionLimitOption" value="<?= Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT ?>" id="bonusConditionByApplicationNoLimit">
                                            <label class="control-label"><?= lang('cms.noLimit'); ?> </label>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <!-- Withdraw condition -->
                        <div class="col-md-12 withdraw_condition_block withdraw_condition_block_betting">
                            <div class="col-md-8">
                                <fieldset>
                                    <legend>
                                        <h4>* <?= lang('cms.betCondition') ?></h4>
                                    </legend>
                                    <div class="condition-item">

                                        <input type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_BONUS_TIMES; ?>" id="withdrawRequirementBettingConditionOption5">
                                        <label class="control-label bonusCondLbl1" style="font-size:10px;"><?= lang('cms.betAmountCondition2'); ?> </label>
                                        <input type="number" step='any' name="withdrawReqBonusTimes" min="1" id="withdrawReqBonusTimes" style="width:80px;" class="form-control input-sm number_only">
                                        <span class="text-danger help-block m-b-0 addCategoryAlert withdrawReqBonusTimes" hide>*<?= lang('Should be > 0'); ?></span>

                                    </div>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT; ?>" id="withdrawRequirementBettingConditionOption1">
                                        <label class="control-label"><span title="<?= lang('cms.greaterThan'); ?>" data-toggle="tooltip" rel="tooltip">(&#8805;)</span> <?= lang('cms.betAmount'); ?> </label>
                                        <input type="number" min="1" step="any" name="withdrawReqBetAmount" id="withdrawReqBetAmount" style="width:120px;" class="form-control input-sm amount_only">
                                        <span class="text-danger help-block m-b-0 addCategoryAlert withdrawReqBetAmount" hide>*<?= lang('Should be > 0'); ?></span>

                                    </div>
                                    <div class="condition-item">

                                        <input class="control-label bonusCondLbl1" type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_NOTHING; ?>" id="withdrawRequirementBettingConditionOption3" data-toggle="tooltip" title="<?= lang('cms.noWithdrawReqRisk') ?>" data-placement="top" rel="tooltip">
                                        <label class="control-label"><?= lang('cms.no_any_withdraw_condtion'); ?> </label>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <div class="col-md-12 withdraw_condition_block withdraw_condition_block_deposit" style="display: none;">
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
                    <button type="button" class="btn btn-scooter" id="saveAddCategory"><?= lang('lang.save'); ?></button>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- EOF add redemption Code category modal add_category_modal -->

<!-- edit redemption Code category modal add_category_modal -->
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
                                        <input type="text" name="categoryName" class="form-control input-sm" id="edit-categoryName" required>
                                        <input type="hidden" name="categoryId" class="form-control input-sm" id="edit-categoryId" required>
                                        <span class="text-danger help-block m-b-0 editCategoryAlert" id="edit-categoryNameAlert" hide>*<?= lang('Required'); ?></span>
                                        <span class="text-danger help-block m-b-0 editCategoryAlert" id="edit-categoryNameErrorMsg" hide></span>
                                    </div>
                                </div>

                                <!-- Quantity -->
                                <!-- <div class="col-md-3">
                                    <div class="form-group required">
                                        <label for="quantity" class="control-label"><?= lang('redemptionCode.quantity'); ?><span class="text-danger"></span></label>
                                        <input type="number" name="quantity" class="form-control input-sm" id="edit-quantity">
                                    </div>
                                </div> -->

                                <!-- bonus -->
                                <div class="col-md-3">
                                    <div class="form-group required">
                                        <label for="bonus" class="control-label"><?= lang('redemptionCode.bonus'); ?><span class="text-danger"></span></label>
                                        <input type="number" name="bonus" class="form-control input-sm" id="edit-bonus" required>
                                        <span class="text-danger help-block m-b-0 editCategoryAlert" id="edit-bonusAlert" hide>*<?= lang('redemptionCode.setup.bonusLimit'); ?></span>
                                    </div>
                                </div>

                                <!-- expire_time -->
                                <div class="col-md-3">
                                    <div class="form-group required">
                                        <input type="hidden" name="appEndDate" value="" style="width:100%" id="edit-appEndDate" class="form-control input-sm" required="">
                                        <label class="control-label"><?= lang('redemptionCode.apply_expire_time'); ?><span class="text-danger"></span></label>
                                        <!-- <i class="btn btn-default btn-xs" data-toggle="tooltip" title="Promo will hide automatically at this date and time" data-placement="right" style="height:18px;" rel="tooltip">?</i> -->
                                        <input type="text" name="hideDate" id="edit-hideDate" class="form-control input-sm dateInput user-success" data-time="true" data-future="true" style="width:100%" required="">
                                        <span class="text-danger help-block m-b-0 editCategoryAlert" id="edit-appEndDateAlert" hide>*<?= lang('Required'); ?></span>
                                    </div>
                                    <div class="input-group form-group">
                                        <input type="checkbox" name="isValidForever" id="edit-isValidForever">&nbsp;
                                        <label class="control-label" for="edit-isValidForever"><?= lang('redemptionCode.validForever') ?></label>
                                    </div>
                                </div>
                                <!-- Allow duplicate apply -->
                            </div>
                        </div>
                        <div class="col-md-12" id="bonusConditionByApplicationSec">
                            <div class="col-md-8">
                                <fieldset>
                                    <legend>
                                        <h4><?= lang('cms.singleOrMultiple'); ?>
                                            <i class="btn btn-default btn-xs" data-toggle="tooltip" title="<?= lang('cms.nonDepSuccession') ?>" data-placement="top" rel="tooltip">?</i>
                                        </h4>
                                    </legend>
                                    <div class="col-md-6 form-group">
                                        <!-- bonusApplicationLimitDateType : 0=none, 1=daily, 2=weekly, 3=monthly, 4=yearly -->
                                        <?php echo lang('Limit Date Type'); ?>:
                                        <select name="bonusApplicationLimitDateType" id="edit-bonusApplicationLimitDateType" class="form-control">
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE; ?>"><?php echo lang('None'); ?></option>
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY; ?>"><?php echo lang('Daily'); ?></option>
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY; ?>"><?php echo lang('Weekly'); ?></option>
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY; ?>"><?php echo lang('Monthly'); ?></option>
                                            <option value="<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY; ?>"><?php echo lang('Yearly'); ?></option>
                                        </select>
                                    </div>

                                    <div class="col-md-12 form-group nopadding">
                                        <div class="col-md-6">
                                            <input type="radio" name="bonusReleaseTypeOptionByNonSuccessionLimitOption" value="<?= Promorules::BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT ?>" id="edit-bonusConditionByApplicationWithLimit">
                                            <label class="control-label"><?= lang('cms.withLimit'); ?> </label>
                                            <input type="number" step='1' name="limitCnt" id="edit-bonusConditionByApplicationLimitCnt" min="1" style="width:50%;" class="form-control input-sm number_only">
                                            <span class="text-danger help-block m-b-0 editCategoryAlert limitCntAlert" hide>*<?= lang('Limit should be > 0'); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="radio" name="bonusReleaseTypeOptionByNonSuccessionLimitOption" value="<?= Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT ?>" id="edit-bonusConditionByApplicationNoLimit">
                                            <label class="control-label"><?= lang('cms.noLimit'); ?> </label>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <!-- Withdraw condition -->
                        <div class="col-md-12 withdraw_condition_block withdraw_condition_block_betting">
                            <div class="col-md-8">
                                <fieldset>
                                    <legend>
                                        <h4>* <?= lang('cms.betCondition') ?></h4>
                                    </legend>
                                    <div class="condition-item">

                                        <input type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_BONUS_TIMES; ?>" id="edit-withdrawRequirementBettingConditionOption5">
                                        <label class="control-label bonusCondLbl1" style="font-size:10px;"><?= lang('cms.betAmountCondition2'); ?> </label>
                                        <input type="number" step='any' name="withdrawReqBonusTimes" min="1" id="edit-withdrawReqBonusTimes" style="width:80px;" class="form-control input-sm number_only">
                                        <span class="text-danger help-block m-b-0 editCategoryAlert withdrawReqBonusTimes" hide>*<?= lang('Should be > 0'); ?></span>

                                    </div>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT; ?>" id="edit-withdrawRequirementBettingConditionOption1">
                                        <label class="control-label"><span title="<?= lang('cms.greaterThan'); ?>" data-toggle="tooltip" rel="tooltip">(&#8805;)</span> <?= lang('cms.betAmount'); ?> </label>
                                        <input type="number" min="1" step="any" name="withdrawReqBetAmount" id="edit-withdrawReqBetAmount" style="width:120px;" class="form-control input-sm amount_only">
                                        <span class="text-danger help-block m-b-0 editCategoryAlert withdrawReqBetAmount" hide>*<?= lang('Should be > 0'); ?></span>
                                    </div>
                                    <div class="condition-item">

                                        <input class="control-label bonusCondLbl1" type="radio" name="withdrawRequirementBettingConditionOption" value="<?php echo Promorules::WITHDRAW_CONDITION_TYPE_NOTHING; ?>" id="edit-withdrawRequirementBettingConditionOption3" data-toggle="tooltip" title="<?= lang('cms.noWithdrawReqRisk') ?>" data-placement="top" rel="tooltip">
                                        <label class="control-label"><?= lang('cms.no_any_withdraw_condtion'); ?> </label>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <div class="col-md-12 withdraw_condition_block withdraw_condition_block_deposit" style="display: none;">
                            <div class="col-md-8">
                                <fieldset>
                                    <legend>
                                        <h4>* <?= lang('Minimum Deposit Requirements') ?></h4>
                                    </legend>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementDepositConditionOption" value="<?php echo Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT; ?>" id="edit-withdrawRequirementDepositConditionOption2">
                                        <label class="control-label"><?= lang('pay.mindepamt'); ?> <span title="<?= lang('cms.greaterThan'); ?>" data-toggle="tooltip" rel="tooltip">(&#8805;)</span></label>
                                        <input type="number" min="1" step="any" name="withdrawReqDepMinLimit" id="edit-withdrawReqDepMinLimit" style="width:120px;" class="form-control input-sm amount_only">
                                    </div>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementDepositConditionOption" value="<?php echo Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION; ?>" id="edit-withdrawRequirementDepositConditionOption3">
                                        <label class="control-label"><?= lang('cms.totalDepAmountSinceRegistration'); ?> <span title="<?= lang('cms.greaterThan'); ?>" data-toggle="tooltip" rel="tooltip">(&#8805;)</span></label>
                                        <input type="number" min="1" step="any" name="withdrawReqDepMinLimitSinceRegistration" id="edit-withdrawReqDepMinLimitSinceRegistration" style="width:120px;" class="form-control input-sm amount_only">
                                    </div>
                                    <div class="condition-item">
                                        <input type="radio" name="withdrawRequirementDepositConditionOption" value="<?php echo Promorules::DEPOSIT_CONDITION_TYPE_NOTHING; ?>" id="edit-withdrawRequirementDepositConditionOption1">
                                        <label class="control-label"><?= lang('cms.no_any_deposit_condtion'); ?> </label>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <div class="col-md-12 m-t-25">
                            <div class="form-group">
                                <label for="catenote" class="control-label"><?= lang('cms.Remark'); ?>:</label><br>
                                <textarea name="catenote" id="edit-catenote" class="form-control input-sm" style="width:100%" rows="10" required="" aria-invalid="true"></textarea>
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
<!-- EOF edit redemption Code category modal add_category_modal -->

<div id="process-type-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <h5 class="modal-title"><?= lang('redemptionCode.redemptionCode'); ?></h5>
            </div>
            <div class="modal-body text-center">
                <p class="f-20" id="successMsg"></p>
                <button type="button" class="btn btn-scooter" data-dismiss="modal" onclick="addNewCodeTypeSuccess()"><?= lang('cms.OK'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="mainModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="mainModalLabel"></h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>


<!-- <div id="generate_code_modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <form id="generate_code_form">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang('redemptionCode.generateCode'); ?></h5>
                </div>
                <div class="modal-body generate_code_form_content">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group required">
                                <label for="quantity" class="control-label"><?= lang('redemptionCode.quantity'); ?><span class="text-danger"></span></label>
                                <input type="number" name="quantity" class="form-control input-sm" id="generateQuantity" min="1" step="1" value="1" required>
                                <input type="hidden" name="categoryId" class="form-control input-sm" id="generateCategoryId" required>
                                <div class="text-danger help-block m-b-0" id="generateQuantityErrorMsg" hide></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-body generate_process_content" style="display: none;">
                </div>
                <div class="modal-footer generate_code_footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?= lang('lang.cancel'); ?></button>
                    <button type="submit" class="btn btn-portage" id="submitGenerateCodeForm"><?= lang('redemptionCode.generateCode'); ?></button>
                </div>
                <div class="modal-footer generate_process_footer" style="display: none;">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?= lang('Finish'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div> -->

<div id="generate_code_modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('redemptionCode.generateCode'); ?>: <span class="code_type_name bold"></span></h5>
            </div>
            <div class="modal-body generate_code_form_content">
                <div class="generateCodeTab" >
                    <ul id="sendBatchTab-header" class="nav nav-tabs">
                        <?php $default_type = $this->utils->getConfig('default_redemption_Code_generate_type');?>
                        <li id="generateByCode" <?php echo $default_type=='byCode' ? 'class="active"' : '';?>>
                            <a href="#byCode" data-toggle="tab"> <?= lang("redemptionCode.generateCodeByType") ?>
                            </a>
                        </li>
                        <li id="generateByMessage" <?php echo $default_type=='byMessage' ? 'class="active"' : '';?>>
                            <a href="#byMessage" data-toggle="tab"> <?= lang("redemptionCode.messagesToPlayers") ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade <?php echo $default_type=='byCode' ? 'in active' : '';?>" id="byCode">
                        <form id="generate_code_form">
                            <div class="row">
                                <div class="form-group required">
                                    <label for="quantity" class="control-label"><?= lang('redemptionCode.quantity'); ?><span class="text-danger"></span></label>
                                    <input type="number" name="quantity" class="form-control input-sm" id="generateQuantity" min="1" step="1" value="1" required>
                                    <input type="hidden" name="categoryId" class="form-control input-sm" id="generateCategoryId" required>
                                    <div class="text-danger help-block m-b-0" id="generateQuantityErrorMsg" hide></div>
                                </div>
                                <div class="modal-footer generate_code_footer">
                                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?= lang('lang.cancel'); ?></button>
                                    <button type="submit" class="btn btn-portage" id="submitGenerateCodeForm"><?= lang('redemptionCode.generateCode'); ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade <?php echo $default_type=='byMessage' ? 'in active' : '';?>" id="byMessage">
                        <form id="generate_code_by_message_form" action="/marketing_management/generateCodeWithInternalMessage" method="POST" accept-charset="utf-8" enctype="multipart/form-data">
                            <div class="row">
                                <div class="form-group" style="display: none;">
                                    <label class="control-label"><?= lang('lang.select.players') ?></label>
                                    <div style=" max-height:100px;overflow-y:auto;">
                                        <style>
                                            .select2-selection__rendered {
                                                color: #008CBA;
                                            }
                                        </style>
                                        <select class="from-username form-control" id="player_username" multiple="true" style="width:100%;"></select>
                                        <button style="position:relative;bottom:30px;right:2px;" type="button" id="clear-member-selection" class="btn btn-default btn-xs pull-right">
                                            <fa class="glyphicon glyphicon-remove"></fa><?= lang('lang.clear.selections') ?>
                                        </button>
                                        <span class="help-block player-username-help-block" style="color:#F04124"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="">
                                        <input type="file" name="generate_code_with_message_csv_file" class="form-control input-sm" required="required" accept=".csv">
                                    </div>
                                    <div class="help-block">
                                        <strong><?= lang("csv_required") ?></strong>&nbsp;&nbsp;
                                        (<a href="<?= '/resources/sample_csv/sample_player_send_message.csv' ?>"><span><?= lang('Download Sample File') ?></span></a>)
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label"><?= lang("Subject") ?></label>
                                    <input type="text" class="form-control" id="batch_mail_subject" name='batch_mail_subject' placeholder="<?= lang("Subject") ?>" required="required" value="<?php echo $this->utils->getConfig('generate_redemption_code_with_message_default_subject'); ?>">
                                    <span class="help-block" style="color:#F04124"></span>
                                </div>
                                <div class="form-group">
                                    <label class="control-label"><?= lang("cu.7") ?></label>
                                    <div class="summernote-editor">
                                        <textarea class="form-control summernote" id="batch_mail_message_body" name="batch_mail_message_body" rows="8" required="required"><?php echo $this->utils->getConfig('generate_redemption_code_with_message_default_message'); ?></textarea>
                                    </div>
                                    <input type="hidden" name="summernoteDetailsLength" id="summernoteDetailsLength">
								    <input type="hidden" name="summernoteDetails" id="summernoteDetails">
                                    <span class="help-block" style="color:#F04124"></span>
                                </div>
                                <div class="form-group required">
                                    <input type="hidden" name="categoryId" class="form-control input-sm" id="generateCategoryIdByMsg" required>
                                    <div class="text-danger help-block m-b-0" id="generateQuantityErrorMsg" hide></div>
                                </div>
                                <div class="row">

                                    <div class="col-sm-6">
                                        <table class="table table-bordered email-replace-msg">
                                            <thead>
                                                <tr>
                                                    <td><?= lang('email_element.element') ?></td>
                                                    <td><?= lang('email_element.description') ?></td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>[username]</td>
                                                    <td><?= lang('Player Name'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>[code]</td>
                                                    <td><?= lang('redemptionCode.redemptionCode'); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer generate_code_footer">
                                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?= lang('lang.cancel'); ?></button>
                                    <button type="submit" class="btn btn-portage" id="submitGenerateCodeByMessageForm"><?= lang('redemptionCode.generateCode'); ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-body generate_process_content" style="display: none;">
            </div>
            <div class="modal-footer generate_process_footer" style="display: none;">
                <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?= lang('Finish'); ?></button>
            </div>
        </div>
    </div>
</div>