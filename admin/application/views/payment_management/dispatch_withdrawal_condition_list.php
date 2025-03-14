<div href="javascript:void(0);" class="btn btn-xs btn-link backToWithdrawalRiskProcessList">
    <h4>
        <span class="glyphicon glyphicon-chevron-left"></span>
        <span><?=$definitionDetail['name']?></span>
    </h4>
</div>

<!-- Delete WithdrawalCondition Modal Start -->
<div class="modal fade" id="deleteWithdrawalConditionModal" tabindex="-1" role="dialog" aria-labelledby="deletePromoruleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="deleteWithdrawalConditionModalLabel"><?=lang('Delete an Condition')?></h4>
            </div>
            <div class="modal-body deleteWithdrawalConditionModalBody">
                <?=lang('Do you want to delete the Condition ?')?>
            </div>
            <div class="modal-footer">
                <input type="hidden" class="deleteWithdrawalConditionId">
                <button type="button" class="btn btn-primary" id="deleteWithdrawalConditionDetail" data-dismiss="modal"><?=lang('Confirm')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Delete WithdrawalCondition Modal End -->


<!-- somethingWrong Modal Start -->
<div class="modal fade" id="somethingWrongModal" tabindex="-1" role="dialog" aria-labelledby="somethingWrongModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="somethingWrongModalLabel"></h4>
            </div>
            <div class="modal-body somethingWrongModalBody">
                <?=lang('con.pym01')?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=lang('Confirm')?></button>
            </div>
        </div>
    </div>
</div>
<!-- somethingWrong Modal End -->

<form id="withdrawalCondition_detail_form">
    <div id="withdrawalCondition_detail" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?=lang('cms.addNewWithdrawalCondition');?></h5>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group required">
                                    <label for="name" class="control-label"><?=lang('cms.title');?><span class="text-danger"></span></label>
                                    <input type="text" name="name" class="form-control input-sm">
                                    <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="h5"><?=lang('cms.WithdrawalConditionDetailIntro')?></div>
                            </div>

                        </div>

                        <div class="row row-deposit-requirements">
                            <div class="col-md-12">
                                <fieldset>
                                    <legend>
                                        <div class="row row-totaldepositcount">
                                            <div class="col-md-10">
                                            <h5><b><?=lang('Deposit Requirements');?></b></h5>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="onoffswitch">
                                                    <input type="checkbox" name="totalDepositCount_isEnable" class="onoffswitch-checkbox" id="myonoffswitch-deposit-requirements" tabindex="0" value="1" checked>
                                                    <label class="onoffswitch-label" for="myonoffswitch-deposit-requirements">
                                                        <span class="onoffswitch-inner"></span>
                                                        <span class="onoffswitch-switch"></span>
                                                    </label>
                                                </div> <!-- / .onoffswitch -->
                                            </div>
                                        </div>
                                    </legend>

                                    <div class="form-group">
                                        <span> <?=lang('Total Deposit count');?> </span>
                                        <span>
                                            <select name="totalDepositCount_symbol" class="selectpicker" data-width="auto">
                                                <option value="-2"><?=lang('symbol.lessThan')?></option>
                                                <option value="-1"><?=lang('symbol.lessThanOrEqualTo')?></option>
                                                <option value="0"><?=lang('symbol.equalTo')?></option>
                                                <option value="1"><?=lang('symbol.greaterThanOrEqualTo')?></option>
                                                <option value="2"><?=lang('symbol.greaterThan')?></option>
                                            </select>
                                        </span>
                                        <span> <input type="text" name="totalDepositCount_limit" value="12"> </span>
                                        <span> <?=lang('number of times');?> </span>
                                    </div>
                                    <div class="text-danger invalid-prompt hide"></div>
                                </fieldset>

                            </div>
                        </div> <!-- EOF .row-deposit-requirements -->

                        <div class="row row-withdrawal-condition-requirements">
                            <div class="col-md-12">
                                <fieldset>
                                    <legend>
                                        <div class="row row-betandwithdrawalrate">
                                            <div class="col-md-10">
                                                <h5><b><?=lang('Withdrawal Condition Requirements');?></b></h5>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="onoffswitch">
                                                    <input type="checkbox" name="betAndWithdrawalRate_isEnable" class="onoffswitch-checkbox" id="myonoffswitch-withdrawal-condition-requirements" tabindex="0" value="1" checked>
                                                    <label class="onoffswitch-label" for="myonoffswitch-withdrawal-condition-requirements">
                                                        <span class="onoffswitch-inner"></span>
                                                        <span class="onoffswitch-switch"></span>
                                                    </label>
                                                </div> <!-- / .onoffswitch -->
                                            </div>
                                        </div>
                                    </legend>

                                    <div class="form-group">
                                        <span> <?=lang('Bet Amount');?> </span>
                                        <span>
                                            <select name="betAndWithdrawalRate_symbol" class="selectpicker" data-width="auto">
                                                <option value="-2"><?=lang('symbol.lessThan')?></option>
                                                <option value="-1"><?=lang('symbol.lessThanOrEqualTo')?></option>
                                                <option value="0"><?=lang('symbol.equalTo')?></option>
                                                <option value="1"><?=lang('symbol.greaterThanOrEqualTo')?></option>
                                                <option value="2"><?=lang('symbol.greaterThan')?></option>
                                            </select>
                                        </span>
                                        <span> <input type="text" name="betAndWithdrawalRate_rate" value="12" step="0.01"> </span>
                                        <span> <?=lang('times of the Bet Amount on Withdrawal Conditions');?> </span>
                                    </div>
                                    <div class="text-danger invalid-prompt hide"></div>
                                </fieldset>
                            </div>
                        </div> <!-- EOF .row-withdrawal-condition-requirements -->

                        <div class="row row-win-and-deposit-formula">
                            <div class="col-md-12">
                                <fieldset>
                                    <legend>
                                        <div class="row row-winanddepositrate">
                                            <div class="col-md-10">
                                                <h5><b><?=lang('Win and Deposit Formula');?></b></h5>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="onoffswitch">
                                                    <input type="checkbox" name="winAndDepositRate_isEnable" class="onoffswitch-checkbox" id="myonoffswitch-win-and-deposit-formula" tabindex="0" value="1" checked>
                                                    <label class="onoffswitch-label" for="myonoffswitch-win-and-deposit-formula">
                                                        <span class="onoffswitch-inner"></span>
                                                        <span class="onoffswitch-switch"></span>
                                                    </label>
                                                </div> <!-- / .onoffswitch -->
                                            </div>
                                        </div>
                                    </legend>

                                    <div class="form-group">
                                        <span> <?=lang('Win Amount');?> </span>
                                        <span>
                                            <select name="winAndDepositRate_symbol" class="selectpicker" data-width="auto">
                                                <option value="-2"><?=lang('symbol.lessThan')?></option>
                                                <option value="-1"><?=lang('symbol.lessThanOrEqualTo')?></option>
                                                <option value="0"><?=lang('symbol.equalTo')?></option>
                                                <option value="1"><?=lang('symbol.greaterThanOrEqualTo')?></option>
                                                <option value="2"><?=lang('symbol.greaterThan')?></option>
                                            </select>
                                        </span>
                                        <span> <input type="text" name="winAndDepositRate_rate" value="12"> </span>
                                        <span> <?=lang('times of Deposit Amount');?> </span>
                                    </div>
                                    <div class="text-danger invalid-prompt hide"></div>
                                </fieldset>
                            </div>
                        </div> <!-- EOF .row-win-and-deposit-formula -->


                        <div class="row row-included-game-types">
                            <div class="col-md-12">
                                <fieldset>
                                    <legend>
                                        <div class="row row-includedgametype">
                                            <div class="col-md-10">
                                                <h5><b><?=lang('cms.allowedGameType');?></b></h5>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="onoffswitch">
                                                    <input type="checkbox" name="includedGameType_isEnable" class="onoffswitch-checkbox" id="myonoffswitch-included-game-types" tabindex="0" value="1" checked>
                                                    <label class="onoffswitch-label" for="myonoffswitch-included-game-types">
                                                        <span class="onoffswitch-inner"></span>
                                                        <span class="onoffswitch-switch"></span>
                                                    </label>
                                                </div> <!-- / .onoffswitch -->
                                            </div>
                                        </div>
                                    </legend>
                                    <button type="button" class="btn btn-sm btn-primary btn-selectall" id="checkAll">
                                        <i class="fa"></i> <?= lang('Select All'); ?>
                                    </button>
                                    <div class="form-group">                                             
                                        <input type="hidden" name="selected_game_tree" value="">
                                        <div id="includedGameTypeTree" class="includedGameTypeTree"></div>
                                    </div>
                                    <div class="text-danger invalid-prompt hide"></div>
                                </fieldset>
                            </div>
                        </div>



                        <div class="row row-exception-player-tags">
                            <div class="col-md-12">
                                <fieldset>
                                    <legend>
                                        <div class="row">
                                            <div class="col-md-10">
                                                <h5><b><?=lang('Exception Player Tags');?></b></h5>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="onoffswitch">
                                                    <input type="checkbox" name="excludedPlayerTag_isEnable" class="onoffswitch-checkbox" id="myonoffswitch-exception-player-tags" tabindex="0" value="1" checked>
                                                    <label class="onoffswitch-label" for="myonoffswitch-exception-player-tags">
                                                        <span class="onoffswitch-inner"></span>
                                                        <span class="onoffswitch-switch"></span>
                                                    </label>
                                                </div> <!-- / .onoffswitch -->
                                            </div>
                                        </div>
                                    </legend>

                                    <div class="form-group">
                                        <label for="player_tag" class="control-label"><?=lang('exclude_player')?></label>
                                        <select data-orig-name="tag_list[]" name="excludedPlayerTag_list[]" id="tag_list" multiple="multiple" class="form-control input-sm">
                                            <!-- <option value="notag" id="notag" <?=is_array($selected_tags) && in_array('notag', $selected_tags) ? "selected" : "" ?>><?=lang('player.tp12')?></option> -->
                                            <?php foreach ($player_tags as $tag): ?>
                                                <option value="<?=$tag['tagId']?>" <?=is_array($selected_tags) && in_array($tag['tagId'], $selected_tags) ? "selected" : "" ?> ><?=$tag['tagName']?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                    <div class="text-danger invalid-prompt hide"></div>
                                </fieldset>
                            </div>
                        </div> <!-- EOF .row-exception-player-tags -->

                        <div class="row row-exception-player-levels">
                            <div class="col-md-12">
                                <fieldset>
                                    <legend>
                                        <div class="row">
                                            <div class="col-md-10">
                                                <h5><b><?=lang('Exception Player Levels');?></b></h5>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="onoffswitch">
                                                    <input type="checkbox" name="excludedPlayerLevels_isEnable" class="onoffswitch-checkbox" id="myonoffswitch-exception-player-levels" tabindex="0" value="1" checked>
                                                    <label class="onoffswitch-label" for="myonoffswitch-exception-player-levels">
                                                        <span class="onoffswitch-inner"></span>
                                                        <span class="onoffswitch-switch"></span>
                                                    </label>
                                                </div> <!-- / .onoffswitch -->
                                            </div>
                                        </div>
                                    </legend>

                                    <div class="form-group">
                                        <label for="player_level" class="control-label"><?=lang('exclude_player_level')?></label>
                                        <select data-orig-name="level_list[]" name="excludedPlayerLevel_list[]" id="level_list" multiple="multiple" class="form-control input-sm">
                                            <!-- <option value="notag" id="notag" <?=is_array($selected_levels) && in_array('notag', $selected_levels) ? "selected" : "" ?>><?=lang('player.tp12')?></option> -->
                                            <?php foreach ($player_levels as $level): ?>
                                                <option value="<?=$level['vipsettingcashbackruleId']?>" <?=is_array($selected_levels) && in_array($level['vipsettingcashbackruleId'], $selected_levels) ? "selected" : "" ?> ><?=lang($level['groupName']). ' - '. lang($level['vipLevelName'])?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                    <div class="text-danger invalid-prompt hide"></div>
                                </fieldset>
                            </div>
                        </div> <!-- EOF .row-exception-player-levels -->

                        <div class="row row-etc">
                            <div class="col-md-12">
                                <fieldset>
                                    <legend>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h5><b><?=lang('Other Conditions');?></b></h5>
                                            </div>
                                        </div>
                                    </legend>

                                    <div class="row row-withdrawalamount">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="withdrawalAmount_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('cms.WithdrawalAmount')?>
                                            </span>
                                            <span>
                                                <select name="withdrawalAmount_symbol" class="selectpicker" data-width="auto">
                                                    <option value="-2"><?=lang('symbol.lessThan')?></option>
                                                    <option value="-1"><?=lang('symbol.lessThanOrEqualTo')?></option>
                                                    <option value="0"><?=lang('symbol.equalTo')?></option>
                                                    <option value="1"><?=lang('symbol.greaterThanOrEqualTo')?></option>
                                                    <option value="2"><?=lang('symbol.greaterThan')?></option>
                                                </select>
                                            </span>
                                            <span>
                                                <input type="text" name="withdrawalAmount_limit" class="" value="12">
                                            </span>
                                        </div>
                                        <div class="col-md-offset-2 col-md-10 text-danger invalid-prompt hide"></div>
                                    </div><!-- EOF .row-withdrawalamount -->

                                    <div class="row row-todaywithdrawalcount">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="todayWithdrawalCount_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('Today Withdrawal Count')?>
                                            </span>
                                            <span>
                                                <select name="todayWithdrawalCount_symbol" class="selectpicker" data-width="auto">
                                                    <option value="-2"><?=lang('symbol.lessThan')?></option>
                                                    <option value="-1"><?=lang('symbol.lessThanOrEqualTo')?></option>
                                                    <option value="0"><?=lang('symbol.equalTo')?></option>
                                                    <option value="1"><?=lang('symbol.greaterThanOrEqualTo')?></option>
                                                    <option value="2"><?=lang('symbol.greaterThan')?></option>
                                                </select>
                                            </span>
                                            <span>
                                                <input type="text" name="todayWithdrawalCount_limit" class="" value="12">
                                            </span>
                                        </div>
                                        <div class="col-md-offset-2 col-md-10 text-danger invalid-prompt hide"></div>
                                    </div> <!-- EOF .row-todaywithdrawalcount -->

                                    <div class="row row-afterdepositwithdrawalcount">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="afterDepositWithdrawalCount_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('After Deposit Withdrawal Count')?>
                                            </span>
                                            <span>
                                                <select name="afterDepositWithdrawalCount_symbol" class="selectpicker" data-width="auto">
                                                    <option value="-2"><?=lang('symbol.lessThan')?></option>
                                                    <option value="-1"><?=lang('symbol.lessThanOrEqualTo')?></option>
                                                    <option value="0"><?=lang('symbol.equalTo')?></option>
                                                    <option value="1"><?=lang('symbol.greaterThanOrEqualTo')?></option>
                                                    <option value="2"><?=lang('symbol.greaterThan')?></option>
                                                </select>
                                            </span>
                                            <span>
                                                <input type="text" name="afterDepositWithdrawalCount_limit" class="" value="12">
                                            </span>
                                        </div>
                                        <div class="col-md-offset-2 col-md-10 text-danger invalid-prompt hide"></div>
                                    </div> <!-- EOF .row-afterdepositwithdrawalcount -->

                                    <div class="row row-gamerevenuepercentage">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="gameRevenuePercentage_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('Game Revenue Percentage')?>
                                            </span>
                                            <span>
                                                <select name="gameRevenuePercentage_symbol" class="selectpicker" data-width="auto">
                                                    <option value="-2"><?=lang('symbol.lessThan')?></option>
                                                    <option value="-1"><?=lang('symbol.lessThanOrEqualTo')?></option>
                                                    <option value="0"><?=lang('symbol.equalTo')?></option>
                                                    <option value="1"><?=lang('symbol.greaterThanOrEqualTo')?></option>
                                                    <option value="2"><?=lang('symbol.greaterThan')?></option>
                                                </select>
                                            </span>
                                            <span>
                                                <input type="text" name="gameRevenuePercentage_rate" class="" value="12" step="0.01">
                                            </span>
                                            <span>
                                                &#37;
                                            </span>
                                        </div>
                                        <div class="col-md-offset-2 col-md-10 text-danger invalid-prompt hide"></div>
                                    </div> <!-- EOF .row-gamerevenuepercentage -->

                                    <div class="row row-nodepositwithpromo">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="noDepositWithPromo_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('No the deposit with promo')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-nodepositwithpromo -->

                                    <div class="row row-noaddbonussincethelastwithdrawal">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="noAddBonusSinceTheLastWithdrawal_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('No add bonus since the last withdrawal')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-noaddbonussincethelastwithdrawal -->

                                    <div class="row row-calcavailablebetonly"><!-- OGP-18088 calcavailablebetonly項目 使用 nohedgingrecord 演算 -->
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="calcAvailableBetOnly_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('No hedging record')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-calcavailablebetonly -->

                                    <div class="row row-calcenabledgameonly">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="calcEnabledGameOnly_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('Only calculate data for the enabled games')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-calcenabledgameonly -->

                                    <div class="row row-ignorecanceledgamelogs">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="ignoreCanceledGameLogs_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('No the canceled game')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-ignorecanceledgamelogs -->


                                    <div class="row row-noduplicatefirstnames">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="noDuplicateFirstNames_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('No duplicate first names')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-noduplicatefirstnames -->

                                    <div class="row row-noduplicatelastnames">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="noDuplicateLastNames_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('No duplicate last names')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-noduplicatelastnames -->

                                    <div class="row row-noduplicateaccounts">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="noDuplicateAccounts_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('No duplicate accounts')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-noduplicateaccounts -->

                                    <div class="row row-calcpromodepositonly hide">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="calcPromoDepositOnly_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('Only count the deposit with promo')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-calcpromodepositonly -->

                                    <div class="row row-existiniovation">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="thePlayerHadExistsInIovation_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('The player had exists in Iovation.')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-existiniovation -->

                                    <div class="row row-totalbetgreaterorequalrequired">
                                        <div class="col-md-2">
                                            <div class="pull-right">
                                                <input name="theTotalBetGreaterOrEqualRequired_isEnable" type="checkbox" class="" data-size='mini' data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" value="1">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <span>
                                                <?=lang('Total Bet Amount &ge; Total Required Bet Amount')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .row-totalbetgreaterorequalrequired -->
                                </fieldset>
                            </div>
                        </div>



                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group required">
                                    <label><?=lang('lang.status')?></label>
                                    <span class="help-block m-b-0">
                                        <label class="radio-inline"><input type="radio" name="status" value="1" checked="checked"><?=lang('Active')?></label>
                                        <label class="radio-inline"><input type="radio" name="status" value="0"><?=lang('Inactive')?></label>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>  <!-- EOF .container-fluid -->
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id" value="">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <button type="button" class="btn btn-scooter" id="saveWithdrawalConditionDetail"><?=lang('lang.save');?></button>
                </div>
            </div>
        </div>
    </div> <!-- EOF #withdrawalCondition_detail -->
</form> <!-- EOF #withdrawalCondition_detail_form -->



<div class="panel panel-primary hide">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang('Search')?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseWithdrawalConditionSearch" class="btn btn-xs btn-primary" aria-expanded="false" aria-controls="collapseWithdrawalConditionSearch"></a>
            </span>
        </h4>
    </div>
    <div class="panel-body" id="collapseWithdrawalConditionSearch">
        <form id="search-form">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <input type="hidden" name="definition_id" value="<?=$definition_id?>">
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.reset'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-danger'?>">
                            <button type="submit" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>"><?=lang("lang.search")?></button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="glyphicon glyphicon-list"></i> <?=lang('Dispatch Withdrawal Condition List')?>
            <span class="pull-right">
                <a href="javascript:void(0);" class="btn btn-xs btn-primary addWithdrawalCondition" >
                    <i class="glyphicon glyphicon-plus"></i> <?=lang('Add Dispatch Withdrawal Condition')?>
                </a>
            </span>
        </h4>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body" >
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped table-hover" id="dispatch_withdrawal_condition_list" >
                        <thead>
                            <tr>
                                <th><?= lang('ID'); ?></th>
                                <th><?= lang('cms.title'); ?></th>
                                <!-- <th><?= lang('lang.formula'); ?></th> -->
                                <th><?= lang('lang.status'); ?></th>
                                <th><?= lang('Created At'); ?></th>
                                <th><?= lang('Updated At'); ?></th>
                                <th><?= lang('lang.action'); ?></th>
                            </tr>
                        </thead>
                    </table>

                </div>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">

$(document).ready(function() {

    $('#withdrawal_risk_process_list').addClass('active');

    var dispatchWithdrawalConditions = DispatchWithdrawalConditions.initialize({
        'defaultItemsPerPage': <?=$this->utils->getDefaultItemsPerPage()?>,
        'base_url':"<?=base_url()?>",
        langs: {

            addNewWithdrawalCondition: '<?=lang('New Withdrawal Conditions');?>',
            viewWithdrawalCondition: '<?=lang('View Withdrawal Conditions');?>',
            selectTags: '<?=lang('Select Tags');?>',
            selectLevels: '<?=lang('Select Levels');?>',
            whatCannotBeEmpty: '<?=lang('%s cannot be empty');?>',
            onlyAllowDigits: '<?=lang('Only allow digits')?>',
            default_html5_required_error_message: '<?=lang('default_html5_required_error_message');?>',
            gameTypeTreeNotInitialized:  '<?=lang('The Game Type Tree has not been initialized');?>',
            gameTypeTreeNotLoaded:  '<?=lang('The Game Type Tree has not been loaded');?>',
            exceptionPlayerTags: '<?=lang('Exception Player Tags');?>',
            allowedGameType: '<?=lang('cms.allowedGameType');?>',
            cmsTitle: '<?=lang('cms.title');?>',
        }
    });

    dispatchWithdrawalConditions.onReady();
    $('#checkAll').on('click', function() {
        let topSelected = $('#includedGameTypeTree').jstree('get_top_checked');
        let allOptions = $('#includedGameTypeTree').jstree('get_json');

        if(topSelected.length === allOptions.length) {
            $('#includedGameTypeTree').jstree('uncheck_all');
        } else {
            $('#includedGameTypeTree').jstree('check_all');
        }
        
        setTimeout(function () {
            var selectedNodes = $('#includedGameTypeTree').jstree('get_checked');
            $('input[name="selected_game_tree"]').val(selectedNodes.join(','));
        }, 100);
    });
});
</script>

<style type="text/css">
	/*// ref. to https://proto.io/freebies/onoff/ */
	.onoffswitch {
		position: relative; width: 70px;
		-webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
	}
	.onoffswitch-checkbox {
		position: absolute;
		opacity: 0;
		pointer-events: none;
	}
	.onoffswitch-label {
		display: block; overflow: hidden; cursor: pointer;
		border: 2px solid #999999; border-radius: 20px;
	}
	.onoffswitch-inner {
		display: block; width: 200%; margin-left: -100%;
		transition: margin 0.3s ease-in 0s;
	}
	.onoffswitch-inner:before, .onoffswitch-inner:after {
		display: block; float: left; width: 50%; height: 20px; padding: 0; line-height: 20px;
		font-size: 14px; color: white; font-family: Trebuchet, Arial, sans-serif; font-weight: bold;
		box-sizing: border-box;
	}
	.onoffswitch-inner:before {
		content: "ON";
		padding-left: 10px;
		background-color: #43AC6A; color: #FFFFFF;
	}
	.onoffswitch-inner:after {
		content: "OFF";
		padding-right: 10px;
		background-color: #EEEEEE; color: #999999;
		text-align: right;
	}
	.onoffswitch-switch {
		display: block;
		width: 14px;
		height: 8px;
		margin: 5px;
		background: #FFFFFF;
		position: absolute;
		top: 3px;
		bottom: 0;
		right: 46px;
		border: 2px solid #999999;
		border-radius: 20px;
		transition: all 0.3s ease-in 0s;
	}
	.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-inner {
		margin-left: 0;
	}
	.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-switch {
		right: 0px;
	}

</style>

<style type="text/css">
.backToWithdrawalRiskProcessList {
    padding-bottom: 0px;
    margin-bottom: -15px;
}

.includedGameTypeTree {
    height: 300px;
    overflow-y: auto;
    background-color: #8080801f;
}

legend .onoffswitch {
    bottom: -5px;
    left: -60px;
}

legend b {
    margin-left: 5px;
}

.row-etc div.row {
    margin: 4px auto;
    /* height: 29px; */
}

.row-etc .bootstrap-switch {
    height: 22px;
}

.btn-selectall{
    margin-bottom: 10px;
}

</style>

<style type="text/css">
/** // for $.button('loading'); */
 @keyframes spinner-border {
    to { transform: rotate(360deg); }
}
.spinner-border{
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: .25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    -webkit-animation: spinner-border .75s linear infinite;
    animation: spinner-border .75s linear infinite;
}
.spinner-border-sm{
    height: 1rem;
    width: 1rem;
    border-width: .2em;
}
</style>