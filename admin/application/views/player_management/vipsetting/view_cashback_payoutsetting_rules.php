<div class="panel panel-primary">
    <div class="panel-heading">

        <h4 class="panel-title pull-left"><i class="icon-gift"></i> <?=lang('player.curcashbckperset');?></h4>
        <div class="clearfix"></div>
    </div>

    <div class="panel panel-body" id="details_panel_body">
        <div class="col-md-5 nopadding">
            <table class="table table-bordered table-hover">
                <tbody>
                <tr><!--From Hour:-->
                    <th class="active"><?=lang('cb_settings2')?>:</th>
                    <td>
                        <span id="from-hour-view"></span>
                    </td>
                </tr>
                <tr><!--To Hour:-->
                    <th class="active"><?=lang('cb_settings3')?>:</th>
                    <td>
                        <span id="to-hour-view"></span>
                    </td>
                </tr>
                <tr><!--Pay Time Hour:-->
                    <th class="active"><?=lang('cb_settings4')?>:</th>
                    <td>
                        <span id="pay-time-hour-view"></span>
                    </td>
                </tr>
                <tr><!--minimum cashback amount:-->
                    <th class="active"><?=lang('Minimum Cashback Amount')?>:</th>
                    <td>
                        <span id="min_cashback_amount_view"></span>
                    </td>
                </tr>
                <tr><!--max cashback amount:-->
                    <th class="active"><?=lang('Max Cashback Amount')?>:</th>
                    <td>
                        <span id="max_cashback_amount_view"></span>
                    </td>
                </tr>
                <tr><!--withdraw condition:-->
                    <th class="active"><?=lang('Withdraw Condition')?>:</th>
                    <td>
                        <span id="withdraw_condition_view"></span>
                    </td>
                </tr>
                <tr><!--Pay Cashback Operation:-->
                    <th class="active"><?=lang('cb_settings6')?>:</th>
                    <td>
                        <span id="last-pay-cashback-update-view"> </span>
                    </td>
                </tr>
                <tr><!--Auto Tick New Game in Cashback Tree List:-->
                    <th class="active"><?=lang('Auto tick new games in cashback tree')?>:</th>
                    <td>
                        <span id="auto-tick-new-games-view"> </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="clearfix"></div>

        <form id="cashback_settings_form">
            <fieldset style="padding: 0 15px 15px 15px">
                <legend><h4><?=lang('Cashback Period Setting Form');?></h4></legend>
                <div class="row" >
                    <div class="col-md-12" style="margin-bottom:20px !important;">
                        <div class="col-md-4">
                            <label style="font-weight: bold;"><?= lang('Cashback Period'); ?> : </label>
                            <label class="radio-inline"><input type="radio" name="period" value="1" <?php if(!empty($enabled_weekly_cashback)) echo 'checked'; ?>><?= lang('lang.daily') ?></label>

                            <?php if(!empty($enabled_weekly_cashback)): ?>
                                <label class="radio-inline"><input type="radio" name="period" value="2"><?= lang('lang.weekly') ?></label>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5 weekly-schedule" >
                            <?php if(!empty($enabled_weekly_cashback)): ?>
                                <label class="radio-inline"><input type="radio" name="weekly" value="1"><?= lang('Monday') ?></label>
                                <label class="radio-inline"><input type="radio" name="weekly" value="2"><?= lang('Tuesday') ?></label>
                                <label class="radio-inline"><input type="radio" name="weekly" value="3"><?= lang('Wednesday') ?></label>
                                <label class="radio-inline"><input type="radio" name="weekly" value="4"><?= lang('Thursday') ?></label>
                                <label class="radio-inline"><input type="radio" name="weekly" value="5"><?= lang('Friday') ?></label>
                                <label class="radio-inline"><input type="radio" name="weekly" value="6"><?= lang('Saturday') ?></label>
                                <label class="radio-inline"><input type="radio" name="weekly" value="7"><?= lang('Sunday') ?></label>
                            <?php endif;?>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <?php if($this->utils->isEnabledFeature('enabled_cashback_of_multiple_range')): ?>
                        <div class="col-md-4">
                            <label><strong><?=lang('Common Cashback Rules Mode');?>: </strong></label>
                            <label class="radio-inline"><input type="radio" name="common_cashback_rules_mode" value="<?=Player_cashback_library::COMMON_CASHBACK_RULES_MODE_BY_SINGLE?>"><?=lang('common_cashback_rules_mode_default')?>
                            </label>
                            <label class="radio-inline"><input type="radio" name="common_cashback_rules_mode" value="<?=Player_cashback_library::COMMON_CASHBACK_RULES_MODE_BY_MULTIPLE_RANGE?>"><?=lang('common_cashback_rules_mode_multiple_range')?>
                            </label>
                        </div>
                        <?php endif ?>
                        <div class="col-md-4">
                            <div class="input-group form-group">
                                <input type="checkbox" name="no_cashback_bonus_for_non_deposit_player" id="no_cashback_bonus_for_non_deposit_player" class="">
                                <label><?=lang('No Cashback Bonus for Non-deposit Players')?></label><br/>
                                <!--                                          <input type="text" min="0.01" step="any" name="min_cashback_amount" id="min_cashback_amount" maxlength="10" class="form-control cbs-inputs number_only" required>-->

                                <!-- <span id="error-min_cashback_amount" class="help-block" style="color:#ff6666;font-size:11px;"></span> -->
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group form-group">
                                <input type="checkbox" value="true" name="auto_tick_new_game_in_cashback_tree" id="auto_tick_new_game_in_cashback_tree">
                                <label class="control-label" for="auto_tick_new_game_in_cashback_tree"l><?=lang('Auto tick new games in cashback tree')?></label>
                            </div>
                        </div>
                    </div>
                    <br/>

                    <div class="col-md-12">
                        <!--  <div class="col-md-2">
                                 <label><?=lang('cb_settings1')?>:</label> -->
                        <input type="hidden" min="1" max="1" name="days_ago" disabled="disabled" id="days_ago" maxlength="2"  onkeypress="isNumeric(event);" onkeyup="isNotExceedToMax(this)"  class="form-control" >
                        <!--  <span id="error-days_ago" class="help-block" style="color:#ff6666;font-size:11px;"></span>
                       </div> -->
                        <div class="col-md-2">
                            <!--From Hour:-->
                            <label><?=lang('cb_settings2')?>:</label>

                            <select name="from_hour" id="from_hour"  class="form-control cbs-inputs" style="width:70%;margin-right:2px;">
                                <?php
                                for ($i = 0; $i <= 23; $i++) {
                                    if ($i < 10) {
                                        echo '<option value="0' . $i . '">0' . $i . '</option>';
                                    } else {
                                        echo '<option value="' . $i . '">' . $i . '</option>';
                                    }

                                }
                                ?>
                            </select><div class="pull-left" style="margin-top:-30px;margin-left:75%;">:00:00</div>
                        </div>

                        <div class="col-md-2" >
                            <!--To Hour:-->
                            <label><?=lang('cb_settings3')?>:</label>

                            <input type="number" disabled="disabled" id="to_hour" class="form-control" style="width:70%;margin-right:2px;" readonly> <div class="pull-left" style="margin-top:-30px;margin-left:75%;">:59:59</div>

                        </div>
                        <div class="col-md-5" >
                            <!--Pay Time Hour:-->
                            <label><?=lang('cb_settings4')?>:</label> <span style="color:#31B0D5;"><span><b id="pth-hour-view"> 11</b></span><b>:</b><span><b id="pth-min-view">10</b></span></span>
                            <div class="row" style="margin-top:-24px;">
                                <div class="col-sm-3">
                                    <label>&nbsp;</label>
                                    <input id="pth-hour" type="number" min="0" max="23" value="11" maxlength="2"  onkeypress="isNumeric(event);" onkeyup="isNotExceedToMax(this)"
                                           class="form-control cbs-inputs" placeholder="HH" >
                                    <input type="hidden" id="paytime-hour" name="paytime-hour"/>

                                </div>
                                <div class="col-sm-3">
                                    <label>&nbsp;</label>
                                    <input id="pth-min" type="number" disabled="disabled" min="0" value="10" max="59" maxlength="2"  onkeypress="isNumeric(event);" onkeyup="isNotExceedToMax(this)"    size="2" class="form-control" placeholder="MM">
                                </div>
                            </div>
                            <span id="error-paytime-hour" class="help-block" style="color:#ff6666;font-size:11px;"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            <div class="input-group form-group" style="display: block;">
                                <label><?=lang('Minimum Limit Cashback Amount')?>:</label>
                                <input type="text" min="0.01" step="any" name="min_cashback_amount" id="min_cashback_amount" maxlength="10" class="form-control cbs-inputs number_only" required>
                                <span id="error-min_cashback_amount" class="help-block" style="color:#ff6666;font-size:11px;"></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group form-group" style="display: block;">
                                <label><?=lang('Max Cashback Amount')?>:</label>
                                <input type="text" min="0.01" step="any" name="max_cashback_amount" id="max_cashback_amount" maxlength="10" class="form-control cbs-inputs number_only" required>
                                <span id="error-max_cashback_amount" class="help-block" style="color:#ff6666;font-size:11px;"></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group form-group" style="display: block;">
                                <label><?=lang('Withdraw Condition (Cashback Amount X Times)')?>:</label>
                                <input type="text" min="0.01" step="any" name="withdraw_condition" id="withdraw_condition" maxlength="4" class="form-control cbs-inputs number_only" required>
                                <span id="error-withdraw_condition" class="help-block" style="color:#ff6666;font-size:11px;"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">

                        <button id="saveCashbackSetting" class="btn btn-sm pull-right <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info' ?>">
                            <?=lang('player.saveset');?>
                        </button>
                    </div>
                </div>
            </fieldset>
        </form>

        <div class="common_cashback_rules_container">
            <fieldset style="padding: 0 15px 15px 15px">
                <legend>
                    <div><h4><?=lang('Common Cashback Rules');?></h4></div>
                </legend>

                <?php if($this->utils->isEnabledFeature('enabled_cashback_of_multiple_range')): ?>
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#common_cashback_rules_by_default" class="common_cashback_rules_by_default_toggle" data-toggle="tab"><?=lang('common_cashback_rules_mode_default')?></a></li>
                    <li><a href="#common_cashback_rules_by_multiple_range" class="common_cashback_rules_mode_multiple_range_toggle" data-toggle="tab"><?=lang('common_cashback_rules_mode_multiple_range')?></a></li>
                    <li><a href="#common_cashback_rules_by_multiple_range_for_game_tags" class="common_cashback_rules_mode_multiple_range_for_game_tags_toggle" data-toggle="tab"><?=lang('common_cashback_rules_mode_multiple_range_for_game_tags')?></a></li>
                </ul>
                <?php endif ?>
                <div class="tab-content nopadding">
                    <div id="common_cashback_rules_by_default" class="tab-pane fade in active">
                        <a class="btn pull-right <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-xs btn-info' : 'btn-sm btn-primary' ?>" href="<?=BASEURL . 'marketing_management/addCashbackGameRuleSetting/'?>">
                            <?=lang('Add cashback rule');?>
                        </a>
                        <div class="clearfix"></div>

                        <div class="table-responsive">
                            <br/>
                            <table id='commonCashbackRuleTable' class="table table-striped table-hover">
                                <thead>
                                <th></th>
                                <th><?php echo lang('Count') ?></th>
                                <th><?php echo lang('Min Bet Amount') ?></th>
                                <th><?php echo lang('Max Bet Amount') ?></th>
                                <th><?php echo lang('Cashback Percentage') ?></th>
                                <th><?php echo lang('Created At') ?></th>
                                <th><?php echo lang('Updated At') ?></th>
                                <th><?php echo lang('Status') ?></th>
                                <?php if(!empty($this->utils->getConfig('enabled_cashback_settings_note'))):?>
                                    <th><?php echo lang('Note') ?></th>
                                <?php endif;?>
                                <th><?php echo lang('Action') ?></th>
                                </thead>

                                <tbody>
                                <?php
                                if (!empty($common_cashback_rule)) {
                                    $cnt = 1;
                                    foreach ($common_cashback_rule as $data) {
                                        ?>
                                        <tr>
                                            <td></td>
                                            <td><?php echo $cnt; ?></td>
                                            <td><?=$data['min_bet_amount'] == '' ? '<i class="help-block">'. lang("lang.norecyet").'<i/>' : $data['min_bet_amount']?></td>
                                            <td><?=$data['max_bet_amount'] == '' ? '<i class="help-block">'. lang("lang.norecyet").'<i/>' : $data['max_bet_amount']?></td>
                                            <td><?=$data['default_percentage'] == '' ? '<i class="help-block">'. lang("lang.norecyet").'<i/>' : $data['default_percentage']?></td>
                                            <td><?=$data['created_at'] == '' ? '<i class="help-block">'. lang("lang.norecyet").'<i/>' : $data['created_at']?></td>
                                            <td><?=$data['updated_at'] == '' ? '<i class="help-block">'. lang("lang.norecyet").'<i/>' : $data['updated_at']?></td>
                                            <td><?=$data['status'] ? lang('lang.active') : lang('Blocked')?></td>
                                            <?php if(!empty($this->utils->getConfig('enabled_cashback_settings_note'))):?>
                                                <td><?=$data['note'] == '' ? '<i class="help-block">'. lang("lang.norecyet").'<i/>' : $data['note']?></td>
                                            <?php endif;?>
                                            <td>
                                                <div class="actionVipGroup">
                                                    <?php if (!$data['status']) {?>
                                                        <a class="btn btn-sm btn-default" href="<?=BASEURL . 'marketing_management/updateStatusCashbackGameRuleSetting/' . Cashback_settings::ACTIVE . '/' . $data['id']?>">
                                                            <?=lang('lang.active');?>
                                                        </a>
                                                    <?php } else {?>
                                                        <a class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-burntsienna' : 'btn-info' ?>" href="<?=BASEURL . 'marketing_management/updateStatusCashbackGameRuleSetting/' . Cashback_settings::INACTIVE . '/' . $data['id']?>">
                                                            <?=lang('Blocked');?>
                                                        </a>
                                                    <?php }?>
                                                    <a class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-success' ?>" href="<?=BASEURL . 'marketing_management/editCashbackGameRuleSetting/' . $data['id']?>">
                                                        <?=lang('player.editset');?>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteCashbackGameRuleBtn('<?=BASEURL . 'marketing_management/deleteCashbackGameRuleSetting/' . $data['id']?>')">
                                                    <?=lang('player.deleteset');?>
                                                    </button>

                                                </div>
                                            </td>
                                        </tr>
                                        <?php $cnt++;}
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="9" style="text-align:center"><span class="help-block"><?=lang('lang.norec');?></span></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="common_cashback_rules_by_multiple_range" class="tab-pane fade">
                        <div class="container full-width">
                            <div class="common_cashback_rules_by_multiple_range row">
                            </div>
                            <div class="row">

                                <div class="pull-left col-md-5">
                                    <div class="dataTables_info paging_info_col" style="padding-top: 15px;"" >Showing 0 to 0 of 0 entries</div>
                                </div>
                                <div class="pull-right col-md-7 paging_links_col">
                                    <div class="dataTables_paginate paging_simple_numbers pull-right" id="multiple_range-table_paginate">
                                        <ul class="pagination">
                                            <li class="paginate_button previous disabled" id="multiple_range-table_previous"><a href="#" aria-controls="multiple_range-table" data-dt-idx="0" tabindex="0">Previous</a></li>
                                            <li class="paginate_button active"><a href="#" aria-controls="multiple_range-table" data-dt-idx="1" tabindex="0">1</a></li>
                                            <!-- <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="2" tabindex="0">2</a></li>
                                            <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="3" tabindex="0">3</a></li>
                                            <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="4" tabindex="0">4</a></li>
                                            <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="5" tabindex="0">5</a></li> -->
                                            <li class="paginate_button disabled" id="multiple_range-table_ellipsis"><a href="#" aria-controls="multiple_range-table" data-dt-idx="6" tabindex="0">…</a></li>
                                            <!-- <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="7" tabindex="0">48</a></li> -->
                                            <li class="paginate_button next" id="multiple_range-table_next"><a href="#" aria-controls="multiple_range-table" data-dt-idx="8" tabindex="0">Next</a></li>
                                        </ul>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div><!-- #common_cashback_rules_by_multiple_range -->


                    <div id="common_cashback_rules_by_multiple_range_for_game_tags" class="tab-pane fade">
                        <div class="container full-width">
                            <div class="common_cashback_rules_by_multiple_range_for_game_tags row">
                            </div>
                            <div class="row">
                                <div class="pull-left col-md-5">
                                    <div class="dataTables_info paging_info_col" style="padding-top: 15px;"" >Showing 0 to 0 of 0 entries</div>
                                </div>
                                <div class="pull-right col-md-7 paging_links_col">
                                    <div class="dataTables_paginate paging_simple_numbers pull-right" id="multiple_range-table_paginate">
                                        <ul class="pagination">
                                            <li class="paginate_button previous disabled" id="multiple_range-table_previous"><a href="#" aria-controls="multiple_range-table" data-dt-idx="0" tabindex="0">Previous</a></li>
                                            <li class="paginate_button active"><a href="#" aria-controls="multiple_range-table" data-dt-idx="1" tabindex="0">1</a></li>
                                            <!-- <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="2" tabindex="0">2</a></li>
                                            <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="3" tabindex="0">3</a></li>
                                            <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="4" tabindex="0">4</a></li>
                                            <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="5" tabindex="0">5</a></li> -->
                                            <li class="paginate_button disabled" id="multiple_range-table_ellipsis"><a href="#" aria-controls="multiple_range-table" data-dt-idx="6" tabindex="0">…</a></li>
                                            <!-- <li class="paginate_button "><a href="#" aria-controls="multiple_range-table" data-dt-idx="7" tabindex="0">48</a></li> -->
                                            <li class="paginate_button next" id="multiple_range-table_next"><a href="#" aria-controls="multiple_range-table" data-dt-idx="8" tabindex="0">Next</a></li>
                                        </ul>
                                    </div>
                                </div> <!-- EOF .paging_links_col -->
                            </div>
                        </div>
                    </div><!-- EOF #common_cashback_rules_by_multiple_range_for_game_tags -->
                </div>
            </fieldset>
        </div>
    </div>

</div>

<script type="text/template" id="tpl-rule_entry">
<!-- params:
cb_mr_rule_id
type
type_map_id
min_bet_amount
max_bet_amount
cashback_percentage
min_bet_amount_text
max_bet_amount_text
cashback_percentage
max_cashback_amount_text -->
    <tr class="rule_entry">
        <td>${cb_mr_rule_id}</td>
        <td class="rule_entry_actions">
        <!-- btn_attrs = data-rule_id="${cb_mr_rule_id}"  -->
        <!-- data-rule_id="${cb_mr_rule_id}"
        data-type="${type}"
        data-type_map_id="${type_map_id}"
        data-min_bet_amount="${min_bet_amount}"
        data-max_bet_amount="${max_bet_amount}"
        data-cashback_percentage="${cashback_percentage}"
        data-max_cashback_amount="${max_cashback_amount}"
        -->
        <button class="edit_rule"
            data-rule_id="${cb_mr_rule_id}"
            data-type="${type}"
            data-type_map_id="${type_map_id}"
            data-min_bet_amount="${min_bet_amount}"
            data-max_bet_amount="${max_bet_amount}"
            data-cashback_percentage="${cashback_percentage}"
            data-max_cashback_amount="${max_cashback_amount}"
        ><i class="glyphicon glyphicon-pencil"></i></button>
        <button class="delete_rule"
            data-rule_id="${cb_mr_rule_id}"
            data-type="${type}"
            data-type_map_id="${type_map_id}"
            data-min_bet_amount="${min_bet_amount}"
            data-max_bet_amount="${max_bet_amount}"
            data-cashback_percentage="${cashback_percentage}"
            data-max_cashback_amount="${max_cashback_amount}"
        ><i class="glyphicon glyphicon-trash"></i></button>
    </td>
    <td class="min_bet_amount"><span class="value">${min_bet_amount_text}</span></td>
    <td class="max_bet_amount"><span class="value">${max_bet_amount_text}</span></td>
    <td class="cashback_percentage"><span class="value">${cashback_percentage} %</span></td>
    <td class="max_cashback_amount"><span class="value">${max_cashback_amount_text}</span></td>
    </tr>
</script> <!-- EOF #tpl-rule_entry -->



<script type="text/template" id="tpl-game_tag_entry_container">
    <div id="game_tag_${game_tag_id}" data-game_tag_id="${game_tag_id}" data-cb_mr_sid="${cb_mr_sid}" class="game_tag_entry_container">
    ${theGameTagEntryBody}

    ${theGamePlatformEntryDetails}
    </div>
</script> <!-- EOF #tpl-game_tag_entry_container -->

<script type="text/template" id="tpl-game_tag_entry_body">
    <div id="game_tag_${game_tag_id}_body" class="game_tag_entry_body" >
        <div class="game_tag_details_toggle">
            <button data-toggle="collapse" data-target="#game_tag_${game_tag_id}_details">
                <i class="glyphicon glyphicon-chevron-up"></i>
            </button>
        </div> <!-- EOF .game_tag_details_toggle -->

        <div class="game_tag_entry_content">
            <div class="game_tag_entry_title">${game_tag_name}</div>
            <div class="game_tag_entry_summary">
            </div> <!-- EOF .game_tag_entry_summary -->
        </div> <!-- EOF .game_tag_entry_content -->

        <div class="clearfix"></div>
    </div><!-- EOF .game_tag_entry_body -->
</script> <!-- EOF #tpl-game_tag_entry_body -->

<script type="text/template" id="tpl-game_tag_entry_details">
    <!-- params:
    textSettings
    game_tag_id
    setting_type
    type_map_id
    textActive
    textInactive
    textActive_childs

    container_class
    textRules
    textAdd
    setting_type
    type_map_id
    textMin_bet_amount
    textMax_bet_amount
    textCashback_percentage
    textMax_cashback_amount
    -->
    <div id="game_tag_${game_tag_id}_details" class="game_tag_entry_details collapse">

        <div class="game_tag_entry_settings_container"> <!-- ref. to .game_platform_entry_settings_container -->

<!-- ref. to renderGamePlatformEntrySettings() -->
            <div class="title">
                <span>${textSettings}</span>
            </div> <!-- ref. to ' + this.options.text.settings + ' -->

            <div class="settings_body">

                <div class="form-group-del radio-group switch_cashback">

                    <!-- var radio_attrs = 'name="game_platform_' + game_platform_id + '_cashback_switch" data-type="' + COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_PLATFORM + '" data-type_map_id="' + game_platform_id + '" -->
                    <!-- name="game_tag_${game_tag_id}_cashback_switch" -->
                    <!-- data-type="${setting_type}" // should be game_type -->
                    <!-- data-type_map_id="${type_map_id}" -->

                    <div class="radio-inline">
                        <label>
                            <input type="radio" class="enable_cashback" name="game_tag_${game_tag_id}_cashback_switch" data-type="${setting_type}" data-type_map_id="${type_map_id}" value="1">
                            ${textActive}
                        </label> <!-- ref. to ' + this.options.text.active + ' -->
                    </div>

                    <div class="radio-inline">
                        <label>
                            <input type="radio" class="disable_cashback" name="game_tag_${game_tag_id}_cashback_switch" data-type="${setting_type}" data-type_map_id="${type_map_id}" value="0" checked="checked">
                            ${textInactive}
                        </label> <!-- ref. to ' + this.options.text.inactive + ' -->
                    </div>

                    <div class="radio-inline disabled">
                        <label>
                            <input type="radio" class="child_cashback" name="game_tag_${game_tag_id}_cashback_switch" data-type="${setting_type}" data-type_map_id="${type_map_id}" value="2" disabled="disabled">
                            ${textActive_childs}
                        </label> <!-- ref. to ' + this.options.text.active_childs + ' -->
                    </div>

                </div> <!-- EOF .switch_cashback -->
            </div> <!-- EOF .settings_body -->

        </div> <!-- EOF .game_tag_entry_settings_container -->


        <div class="game_tag_entry_settings_container"> <!-- ref. to .game_platform_entry_settings_container -->

            <div class="title">
                <span>${textTierCalc}</span>
            </div> <!-- ref. to ' + this.options.text.settings + ' -->

            <div class="settings_body">
                <div class="form-group-del radio-group switch_tier_calc_cashback">
                    <!-- var radio_attrs = 'name="game_platform_' + game_platform_id + '_cashback_switch" data-type="' + COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_PLATFORM + '" data-type_map_id="' + game_platform_id + '" -->
                    <!-- name="game_tag_${game_tag_id}_cashback_switch" -->
                    <!-- data-type="${setting_type}" // should be game_type -->
                    <!-- data-type_map_id="${type_map_id}" -->

                    <div class="radio-inline">
                        <label>
                            <input type="radio" class="enable_tier_calc_cashback" data-cb_mr_sid="${cb_mr_sid}" name="game_tag_${game_tag_id}_tier_calc_cashback_switch" data-type="${setting_type}" data-type_map_id="${type_map_id}" value="1" ${enable_tier_calc_cashback_checked}>
                            ${textActive}
                        </label> <!-- ref. to ' + this.options.text.active + ' -->
                    </div>

                    <div class="radio-inline">
                        <label>
                            <input type="radio" class="disable_tier_calc_cashback" data-cb_mr_sid="${cb_mr_sid}" name="game_tag_${game_tag_id}_tier_calc_cashback_switch" data-type="${setting_type}" data-type_map_id="${type_map_id}" value="0" ${disable_tier_calc_cashback_checked}> <!-- checked="checked" -->
                            ${textInactive}
                        </label> <!-- ref. to ' + this.options.text.inactive + ' -->
                    </div>

                </div> <!-- EOF .switch_tier_calc_cashback -->

            </div> <!-- EOF .settings_body -->
        </div> <!-- EOF .game_tag_entry_settings_container -->

<!-- ref. to renderRules() -->

        <!-- var default_attrs = 'data-type="' + type + '" data-type_map_id="' + type_map_id + '"'; -->
        <!-- data-type="${type}" data-type_map_id="$type_map_id" -->
        <div class="${container_class}"> <!--  ' + container_class + '-->
            <div class="title"><span>${textRules}</span></div> <!-- ' + this.options.text.rules + ' -->
            <div class="rules_body">

                <div class="rules_actions">
                    <a href="javascript: void(0);" class="btn btn-primary create_rule" data-type="${setting_type}" data-type_map_id="${type_map_id}" ><i class="glyphicon glyphicon-plus"></i>${textAdd}</a>
                </div>  <!-- // rules_actions -->

                <table class="rules_list">
                    <thead>
                        <tr class="rule_entry_header">
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>${textMin_bet_amount}</th>
                            <th>${textMax_bet_amount}</th>
                            <th>${textCashback_percentage}</th>
                            <th>${textMax_cashback_amount}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table><!-- EOF .rules_list -->

            </div><!-- EOF .rules_body -->

        </div>

<!-- ref. to renderGameTypeContainer() -->
    <!-- <div class="game_platform_types_container">';
        html += '<div class="title"><span>' + this.options.text.game_type + '</span></div>';

        html += '<div class="game_platform_types_body">';
        $.each(game_platform_data.types, function(game_type_id, game_type_data){
            html += '<div id="game_type_' + game_type_id + '" data-game_platform_id="' + game_platform_id + '" data-game_type_id="' + game_type_id + '" class="game_type_entry_container">';

            html += self.renderGameTypeEntryBody(game_type_id, game_type_data);

            html += self.renderGameTypeEntryDetails(game_type_id, game_type_data);

            html += '</div>'; // game_type_entry_container
        });
        html += '</div>'; // game_platform_types_body

        html += '</div>'; // game_platform_types_container
    -->

    </div> <!-- EOF .game_tag_entry_details -->
</script>



<script type="text/template" id="tpl-common_cashback_multiple_range_rules_modal">
<!-- params:
textＣashbackSettings
textSave
textClose
// patch for the issue, many $('.modal-backdrop) append to modal.
-->
    <div id="common_cashback_multiple_range_rules_modal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><span class="modal_default_title">${textCashbackSettings}</span></h4>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-submit">${textSave}</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">${textClose}</button>
                </div>
            </div> <!-- EOF .modal-content -->
        </div><!-- EOF .modal-dialog -->
    </div> <!-- EOF #common_cashback_multiple_range_rules_modal -->
</script> <!-- EOF #tpl-common_cashback_multiple_range_rules_modal -->


<script type="text/template" id="tpl-common_cashback_multiple_range_setting_tier_calculation_form">
    <form id="common_cashback_multiple_range_setting_tier_calculation_form">
        <!-- <input type="hidden" name="game_txag_id" value="${game_tag_id}"> -->
        <input type="hidden" name="setting_id" value="${setting_id}">
        <input type="hidden" name="to_enabled" value="${to_enabled}">
        <input type="hidden" name="type" value="${type}">
        <input type="hidden" name="type_map_id" value="${type_map_id}">
        <input type="hidden" name="tpl_id" value="${tpl_id}">

        <div class="form-group">
            <label class="control-label">
                ${textTier_calculation_confirm}
            </label>
        </div><!-- EOF .form-group -->
    </form>
</script>

<script type="text/template" id="tpl-common_cashback_multiple_range_rules_form">
<!-- params:
textMin_bet_amount
textMax_bet_amount
textCashback_percentage
textMax_cashback_amount -->
    <form id="common_cashback_multiple_range_rules_form">
        <input type="hidden" name="rule_id">
        <input type="hidden" name="type">
        <input type="hidden" name="type_map_id">
        <div class="form-group">
            <label class="control-label" for="min_bet_amount_field">${textMin_bet_amount}</label>
            <input type="number" class="form-control" step="1" min="1" id="min_bet_amount_field" name="min_bet_amount">
            <p class="help-block"></p>
        </div><!-- EOF .form-group -->
        <div class="form-group">
            <label class="control-label" for="max_bet_amount_field">${textMax_bet_amount}</label>
            <input type="number" class="form-control" step="1" min="1" id="max_bet_amount_field" name="max_bet_amount">
            <p class="help-block"></p>
        </div><!-- EOF .form-group -->
        <div class="form-group">
            <label class="control-label" for="cashback_percentage_field">${textCashback_percentage}<em style="color:red">*</em></label>
            <div class="input-group">
                <input type="number" class="form-control" step="0.001" min="0.001" id="cashback_percentage_field" name="cashback_percentage">
                <span class="input-group-addon">%</span>
            </div>
            <p class="help-block"></p>
        </div><!-- EOF .form-group -->
        <div class="form-group">
            <label class="control-label" for="max_cashback_amount_field">${textMax_cashback_amount}</label>
            <input type="number" class="form-control" step="1" min="1" id="max_cashback_amount_field" name="max_cashback_amount">
            <p class="help-block"></p>
        </div> <!-- EOF .form-group -->
    </form> <!-- EOF #common_cashback_multiple_range_rules_form -->
</script> <!-- EOF #tpl-common_cashback_multiple_range_rules_form -->


<script>
$(document).ready(function () {

    //$('[data-toggle="popover"]').popover();
    //Convert JSON string to Javascript object
    var defaultCashbackSettings = JSON.parse('<?php echo $cashBackSettings; ?>');

    $('#pth-hour, #pth-min').unbind('keyup change input paste').bind('keyup change input paste',function(e){
        var $this = $(this);
        var val = $this.val();
        var valLength = val.length;
        var maxCount = $this.attr('maxlength');
        if(valLength>maxCount){
            $this.val($this.val().substring(0,maxCount));
        }
    });

    $('input[name="period"]').on('click', function(){
        var period = $(this).val();
        if(period == 1) {
            hideWeekly();
        } else if (period == 2) {
            showWeekly();
        }
    });

    var fromHour = $("#from_hour"),
        toHour = $("#to_hour"),
            /*Existing CashBack Settings View*/
        //  lastCashbackCalc = $("#last-cash-cashback-update-view"),

        payTimeHourVal = '',
        payTimeHour_ = $("#paytime-hour"),
        pthHour = $("#pth-hour"),
        pthMin = $("#pth-min"),
        pthHourView = $("#pth-hour-view"),
        pthMinView = $("#pth-min-view"),

        saveSetting = $("#saveCashbackSetting"),
        cbsInputs = $(".cbs-inputs"),
        UPDATE_CASHBACK_SETTINGS_URL='<?php echo site_url('marketing_management/editCashbackPeriodSetting') ?>';


    setPaytimeHour();

    pthHour.change(function(){
        pthHourView.html($(this).val());
        setPaytimeHour();
    });

    pthMin.change(function(){
        var val = (Number($(this).val()) < 10) ? '0'+$(this).val() : $(this).val();
        pthMinView.html(val);
        setPaytimeHour();
    });


    saveSetting.click(function(){
        saveCashbackSettings();
        return false;
    });

    fromHour.change(function(){
        calculateHours();
    });

    function setPaytimeHour(){
        var hour = pthHour.val();
        var minutes = (Number(pthMin.val()) < 10) ? '0'+Number(pthMin.val()) : pthMin.val();

        payTimeHourVal = hour+":"+minutes;
        payTimeHour_.val(payTimeHourVal);

        var fromHour = $('#from_hour');
        var payTimeHour = Number(hour);
        var numFromHour = Number(fromHour.val());

        if(numFromHour >= payTimeHour) {
            $('#from_hour').val(pad(payTimeHour - 1, 2));
            fromHour.trigger('change');
        }
    }

    function pad(num, size) {
        var s = num+"";
        while (s.length < size) s = "0" + s;
        return s;
    }

    function calculateHours(){

        var fromHourVal = Number(fromHour.val());
        var payTimeHour = $('#pth-hour');


        var result = fromHourVal - 1;
        if(result < 0 ){
            toHour.val(23);
        }else{
            toHour.val(result);
        }

        // if from hour is greater than payTimeHour, set pay time to from hour+1
        if(fromHourVal >= payTimeHour.val() ) {
            payTimeHour.val(fromHourVal + 1);
            payTimeHour.trigger('change');
        }
    }

    var weekly = '';
    var period = '';
    function saveCashbackSettings() {
        var fH = fromHour.val(),
            tH = toHour.val(),
            ptH = payTimeHour_.val();

        // utils.safelog($("#withdraw_condition").val());

        var common_cashback_rules_mode = $('[name="common_cashback_rules_mode"]:checked').val();

        var cashback_bonus;
        if ($('#no_cashback_bonus_for_non_deposit_player').is(":checked"))
        {
            cashback_bonus = 1;
        } else {
            cashback_bonus = 0;
        }

        var auto_tick_new_game_in_cashback_tree;
        if ($('#auto_tick_new_game_in_cashback_tree').is(":checked"))
        {
            auto_tick_new_game_in_cashback_tree = 1;
        } else {
            auto_tick_new_game_in_cashback_tree = 0;
        }

        $("input[name='weekly']").each(function() {
            if($(this).is(':checked')) {
                weekly = $(this).val();
            } else {
                // console.log('not checked');
            }
        });

        $("input[name='period']").each(function() {
            if($(this).is(':checked')) {
                period = $(this).val();
            } else {
                // console.log('not checked');
            }
        });

        var cashBackSettings = {
            "common_cashback_rules_mode": common_cashback_rules_mode,
            "fromHour" : fH,
            "toHour" : tH,
            "payTimeHour" : ptH,
            "withdraw_condition": $("#withdraw_condition").val(),
            "min_cashback_amount": $("#min_cashback_amount").val(),
            "max_cashback_amount": $("#max_cashback_amount").val(),
            "no_cashback_bonus_for_non_deposit_player" : cashback_bonus,
            "weekly" : weekly,
            "period" : period,
            "auto_tick_new_game_in_cashback_tree" : auto_tick_new_game_in_cashback_tree
        };

        $.ajax({
            url : UPDATE_CASHBACK_SETTINGS_URL,
            type : 'POST',
            data : cashBackSettings,
            dataType : "json",
        }).done(function (obj) {
            if(obj.status == "success"){
                latestCbSettings = obj.cashbackSettings;
                showSettings(JSON.parse(latestCbSettings));
                ableFormWhenSubmitted();
                BootstrapDialog.show({
                    "message": '<?php echo lang('Successfully Update Setting');?>',
                    "onhide": function(){
                        window.location.reload(true);
                    }
                });
            }


        }).fail(function (jqXHR, textStatus) {
            /*Note: this is for session timeout,if the session is out because this is ajax, eventually it will go to log in page*/
            if(jqXHR.status>=300 && jqXHR.status<500){
                location.reload();
            }else{
                alert(textStatus);
            }
        });

    }

    function showSettings(latestCbSettings){
        const processLangForAutoTickNewGame = (d) => { return d.auto_tick_new_game_in_cashback_tree == 1 ? '<?=lang('lang.yes')?>' : '<?=lang('lang.no')?>'};
        if(parseInt(latestCbSettings['common_cashback_rules_mode'])){
            $('[name="common_cashback_rules_mode"][value="1"]').prop('checked', true);
        }else{
            $('[name="common_cashback_rules_mode"][value="0"]').prop('checked', true);
        }

        $("#from-hour-view").html(latestCbSettings.fromHour+":00:00");
        $("#to-hour-view").html(latestCbSettings.toHour+":59:59");
        $("#pay-time-hour-view").html(latestCbSettings.payTimeHour);
        //  lastCashbackCalc.html(latestCbSettings.calcLastUpdate);
        $("#last-pay-cashback-update-view").html(latestCbSettings.payLastUpdate);

        $("#auto-tick-new-games-view").html(processLangForAutoTickNewGame(latestCbSettings));

        fromHour.val(latestCbSettings.fromHour);
        toHour.val(latestCbSettings.toHour);
        // $('#to_hour_label').html(latestCbSettings.toHour);
        payTimeHour_.val(latestCbSettings.payTimeHour);
        var pthHour_ = latestCbSettings.payTimeHour.split(':')[0];
        var pthMin_= latestCbSettings.payTimeHour.split(':')[1];
        pthHourView.html(pthHour_);
        pthMinView.html(pthMin_);
        pthHour.val(pthHour_);
        pthMin.val(pthMin_);

        $("#withdraw_condition").val(latestCbSettings['withdraw_condition']);
        $("#min_cashback_amount").val(latestCbSettings['min_cashback_amount']);
        $("#max_cashback_amount").val(latestCbSettings['max_cashback_amount']);

        if(latestCbSettings['no_cashback_bonus_for_non_deposit_player'] == 1) {
            $('#no_cashback_bonus_for_non_deposit_player').attr('checked', true);
        }

        if(latestCbSettings['auto_tick_new_game_in_cashback_tree'] == 1) {
            $('#auto_tick_new_game_in_cashback_tree').attr('checked', true);
        }

        if(latestCbSettings['weekly'] != '' ) {
            // weekly
            $("input[name=weekly][value=" + latestCbSettings['weekly'] + "]").prop('checked', true);
        } else {
            $("input[name=weekly][value=1]").prop('checked', true);
        }

        // period ( if period is null set to daily ) 1 - daily   2 weekly
        if(latestCbSettings['period']  == '') {
            $("input[name=period][value=1]").prop('checked', true);
            hideWeekly();
        } else {
            if(latestCbSettings['period'] == 1) {
                hideWeekly();
            } else {
                showWeekly();
            }
            $("input[name=period][value=" + latestCbSettings['period'] + "]").prop('checked', true);
        }

        $('#withdraw_condition_view').html(latestCbSettings['withdraw_condition']);
        $('#min_cashback_amount_view').html(latestCbSettings['min_cashback_amount']);
        $('#max_cashback_amount_view').html(latestCbSettings['max_cashback_amount']);

    }

    function hideWeekly() {
        $('.weekly-schedule').addClass('hide');
    }

    function showWeekly() {
        $('.weekly-schedule').removeClass('hide');
    }

    function ableFormWhenSubmitted(){
        saveSetting.prop('disabled', false);
        cbsInputs.attr("disabled", false);
        // $('#days_ago').attr("disabled", true);
    }

    showSettings( defaultCashbackSettings);

    <?php
    if (!empty($common_cashback_rule)) {
    ?>

    $('#commonCashbackRuleTable').DataTable({
        "responsive": {
            details: {
                type: 'column'
            }
        },
        "columnDefs": [ {
            className: 'control',
            orderable: false,
            targets:   0
        },
        {
            visible: false, targets: [1]
        },
        {
            orderable: false,
            targets:   1
        } ],
        "order": [ 2, 'asc' ]
    });

    <?php
    }
    ?>

    Common_Cashback_Multiple_Range_Rules.init({
        'default_per_page':<?=$this->utils->getConfig('multiple_range_items_per_page')?>,
        "text": {
            "common_cashback_settings_of_multiple_range": "<?=lang('common_cashback_settings_of_multiple_range')?>",
            "unknown_error": "<?=lang('error.default.message')?>",
            "settings": "<?=lang('lang.settings')?>",
            "add": "<?=lang('lang.add')?>",
            "close": "<?=lang('lang.close')?>",
            "save": "<?=lang('lang.save')?>",
            "delete": "<?=lang('lang.delete')?>",
            "status": "<?=lang('lang.status')?>",
            "active": "<?=lang('lang.active')?>",
            "inactive": "<?=lang('lang.inactive')?>",
            "active_childs": "<?=lang('cb.mr.active_childs')?>",
            "rules": "<?=lang('lang.rules')?>",
            "game_type": "<?=lang('cms.gametype')?>",
            "game_list": "<?=lang('cms.gameslist')?>",
            "total_games": "<?=lang('cb.total_games')?>",
            "total_enabled_cashback_games": "<?=lang('cb.total_enabled_cashback_games')?>",
            "total_new_games": "<?=lang('cb.total_new_games')?>",
            "min_bet_amount": "<?=lang('Min Bet Amount')?>",
            "max_bet_amount": "<?=lang('Max Bet Amount')?>",
            "cashback_percentage": "<?=lang('Cashback Percentage')?>",
            "max_cashback_amount": "<?=lang('Max Cashback Amount')?>",
            "cashbackSettings": "<?=lang('cms.cashbackSettings')?>",
            "min_bet_amount_format_error": "<?=sprintf(lang('gen.error.invalid'), lang('Min Bet Amount'))?>",
            "max_bet_amount_format_error": "<?=sprintf(lang('gen.error.invalid'), lang('Max Bet Amount'))?>",
            "cashback_percentage_format_error": "<?=sprintf(lang('gen.error.invalid'), lang('Cashback Percentage'))?>",
            "max_cashback_amount_format_error": "<?=sprintf(lang('gen.error.invalid'), lang('cms.cashbackSettings'))?>",
            "cashback_percentage_is_required": "<?=sprintf(lang('gen.error.required'), lang('Cashback Percentage'))?>",
            "max_bet_ammount_must_be_greater_than_min_bet_amount": "<?=lang('Min bet amount must be greater than Min bet amount')?>",
            "save_success": "<?=lang('save.success')?>",
            "save_failed": "<?=lang('save.failed')?>",
            "confirm_delete_rule": "<?=lang('Do you really want to delete this cashback rule?')?>",
            "deleted_successfully": "<?=lang('lang.deleted_successfully')?>",
            "deleted_failed": "<?=lang('lang.deleted_failed')?>",
            "active_cashback_to_all_games_msg": "<?=lang('Are you sure you want to active cashback to all games?')?>",
            "inactive_cashback_to_all_games_msg": "<?=lang('Are you sure you want to inactive cashback to all games?')?>",
            "active_cashback": "<?=lang('Active cashback')?>",
            "inactive_cashback": "<?=lang('Inactive cashback')?>"
        }
    });
    $('.common_cashback_rules_mode_multiple_range_toggle').on('show.bs.tab', function(){
        if($(this).data('has-run')){
        }else{
            $(this).data('has-run', true);
            Common_Cashback_Multiple_Range_Rules.runWithPagination();
        }
    });

    var multiple_Range_Rules_By_Game_Tags = Object.create(Multiple_Range_Rules_By_Game_Tags);
    var options4MRRBGT = {}; // MRRBGT = multiple_Range_Rules_By_Game_Tags
    options4MRRBGT.text = Common_Cashback_Multiple_Range_Rules.options.text;
    options4MRRBGT.text.close = "<?=lang('lang.close')?>";
    options4MRRBGT.text.save = "<?=lang('lang.save')?>";

    options4MRRBGT.text.settings = "<?=lang('lang.settings')?>";
    options4MRRBGT.text.tier_calc = "<?=lang('lang.tier_calc')?>";
    options4MRRBGT.text.active = "<?=lang('lang.active')?>";
    options4MRRBGT.text.inactive = "<?=lang('lang.inactive')?>";
    options4MRRBGT.text.active_childs = "<?=lang('cb.mr.active_childs')?>";
    options4MRRBGT.text.rules = "<?=lang('lang.rules')?>";
    options4MRRBGT.text.add = "<?=lang('lang.add')?>";
    options4MRRBGT.text.min_bet_amount = "<?=lang('Min Bet Amount')?>";
    options4MRRBGT.text.max_bet_amount = "<?=lang('Max Bet Amount')?>";
    options4MRRBGT.text.cashback_percentage = "<?=lang('Cashback Percentage')?>";
    options4MRRBGT.text.common_cashback_settings_of_multiple_range = "<?=lang('common_cashback_settings_of_multiple_range')?>";
    options4MRRBGT.text.max_cashback_amount = "<?=lang('Max Cashback Amount')?>";
    options4MRRBGT.text.active_cashback = "<?=lang('Active cashback')?>";
    options4MRRBGT.text.inactive_cashback = "<?=lang('Inactive cashback')?>";
    options4MRRBGT.text.cashbackSettings = "<?=lang('cms.cashbackSettings')?>";
    options4MRRBGT.text.save_success = "<?=lang('save.success')?>";
    options4MRRBGT.text.save_failed = "<?=lang('save.failed')?>";

    options4MRRBGT.text.confirm_delete_rule = "<?=lang('Do you really want to delete this cashback rule?')?>";
    options4MRRBGT.text.delete = "<?=lang('lang.delete')?>";
    options4MRRBGT.text.deleted_successfully = "<?=lang('lang.deleted_successfully')?>";
    options4MRRBGT.text.deleted_failed = "<?=lang('lang.deleted_failed')?>";

    options4MRRBGT.text.total_games = "<?=lang('cb.total_games')?>";
    options4MRRBGT.text.total_enabled_cashback_games = "<?=lang('cb.total_enabled_cashback_games')?>";
    options4MRRBGT.text.total_new_games = "<?=lang('cb.total_new_games')?>";
    options4MRRBGT.text.status ="<?=lang('lang.status')?>";
    options4MRRBGT.text.tier_calculation_enabled_confirm = "<?=lang('Are you sure to Enable the tier calculation?')?>";
    options4MRRBGT.text.tier_calculation_disabled_confirm = "<?=lang('Are you sure to Disable the tier calculation?')?>";

    options4MRRBGT.default_per_page = <?=$this->utils->getConfig('multiple_range_by_game_types_items_per_page')?>;

    multiple_Range_Rules_By_Game_Tags.init(options4MRRBGT);

});//End document ready


    //global validation
    function isNotExceedToMax(object){
        if(object.max>0 && object.value > object.max){
            object.value = object.max;
        }
    }
    // function maxLengthCheck(object) {
    //   if (object.value.length > object.maxLength)
    //     object.value = object.value.slice(0, object.maxLength)
    // }

    function isNumeric (evt) {
        var theEvent = evt || window.event;
        var key = theEvent.keyCode || theEvent.which;
        key = String.fromCharCode (key);
        var regex = /[0-9]|\./;
        if ( !regex.test(key) ) {
            theEvent.returnValue = false;
            if(theEvent.preventDefault) theEvent.preventDefault();
        }
    }

    function deleteCashbackGameRuleBtn(deleteCashbackGameRuleSettingUrl) {
        if(confirm("Are you sure you want to delete this?")){
            javascript:location.href = deleteCashbackGameRuleSettingUrl;
        }
        else{
            return false;
        }
    }

</script>