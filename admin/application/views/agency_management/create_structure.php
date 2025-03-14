<?php
/**
 *   filename:   create_structure.php
 *   date:       2016-05-03
 *   @brief:     view for structure creating
 */
?>

<!-- form create-structure {{{1 -->    
<form method="POST" id="agency_main_form" 
    action="<?=site_url('agency_management/verify_structure')?>" accept-charset="utf-8">
    <div class="panel panel-primary ">
        <!-- panel heading of create-structure {{{2 -->    
        <div class="panel-heading">
            <h4 class="panel-title pull-left">
                <i class="glyphicon glyphicon-list-alt"></i> 
                <?=lang('Create Agent Template');?> 
            </h4>
            <div class="pull-right"><?=lang('Fields with')?> (<font style="color:red;">*</font>) <?=lang('are required.')?></div>
            <div class="clearfix"></div>
        </div> <!-- panel heading of create-structure }}}2 -->    

        <!-- panel body of create-structure  {{{2 -->    
        <div class="panel panel-body" id="create-structure_panel_body">
            <!-- Basic Info {{{3 -->    
            <div class="col-md-12">
                <!-- input structure_name (required) {{{4 -->
                <div class="col-md-3 fields">
                    <label for="structure_name">
                        <font style="color:red;">*</font> 
                        <?=lang('Agent Template Name');?>
                    </label>

                    <input type="text" name="structure_name" id="structure_name" class="form-control " 
                    value="<?=set_value('structure_name');?>" data-toggle="tooltip" 
                    title="<?=lang('Agent Template Name');?>" required>

                    <span class="errors"><?php echo form_error('structure_name'); ?></span>
                    <span id="error-structure_name" class="errors"></span>
                </div> <!-- input structure_name (required) }}}4 -->
                <!-- select currency {{{4 -->
                <div class="col-md-3 col-lg-3">
                    <?php include __DIR__.'/../includes/agency_form_currency.php'; ?>
                </div> <!-- end of select currency }}}4 -->
                <!-- select status {{{4 -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label">
                        <font style="color:red;">*</font> 
                        <?=lang('Status');?>
                    </label>
                    <select name="status" id="status" class="form-control input-sm">
                        <option value="" <?=empty($conditions['status']) ? 'selected' : ''?>>
                        --  <?=lang('None');?> --
                        </option>
                        <option value="active" <?=(set_value('status') == "active") ? 'selected' : ''?> >
                        <?=lang('Active');?>
                        </option>
                        <option value="suspended" <?=(set_value('status') == "suspended") ? 'selected' : ''?> >
                        <?=lang('Suspended');?>
                        </option>
                        <option value="frozen" <?=(set_value('status') == "frozen") ? 'selected' : ''?> >
                        <?=lang('Frozen');?>
                        </option>
                    </select>
                    <span class="errors"><?php echo form_error('status'); ?></span>
                    <span id="error-status" class="errors"></span>
                </div> <!-- end of select status }}}4 -->
                <!-- input credit_limit {{{4 -->
                <div class="col-md-3 fields">
                    <label for="credit_limit">
                        <font style="color:red;">*</font> 
                        <?=lang('Credit Limit');?>
                    </label>

                    <input type="text" name="credit_limit" id="credit_limit" class="form-control " 
                    value="<?=set_value('credit_limit', '0.00');?>" data-toggle="tooltip" 
                    title="<?=lang('Credit Limit');?>">

                    <span class="errors"><?php echo form_error('credit_limit'); ?></span>
                    <span id="error-credit_limit" class="errors"></span>
                </div> <!-- input credit_limit (required) }}}4 -->
            </div>
            <div class="col-md-12">
                <!-- input available_credit {{{4 -->
                <!--
                <div class="col-md-3 fields">
                    <label for="available_credit">
                        <font style="color:red;">*</font> 
                        <?=lang('Available Credit');?>
                    </label>

                    <input type="text" name="available_credit" id="available_credit" class="form-control " 
                    value="<?=set_value('available_credit', '0.00');?>" data-toggle="tooltip" 
                    title="<?=lang('Available Credit');?>">

                    <span class="errors"><?php echo form_error('available_credit'); ?></span>
                    <span id="error-available_credit" class="errors"></span>
                </div> -->
                <!-- input available_credit (required) }}}4 -->
            </div>
            <!-- Basic Info }}}3 -->    
            <!-- fieldset commission-setting {{{3 -->
            <div class="col-md-12">
                <label for="commission-setting">
                    <font style="color:red;">*</font> 
                    <?=lang('Commission Setting');?>
                </label>
                <fieldset>
                    <br>
                    <!-- input number rev_share (required) {{{4 -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <?=lang('Rev Share');?>
                                </div>
                                <input type="text" class="form-control" 
                                id="rev_share" name="rev_share" 
                                value="<?=set_value('rev_share','0.00')?>"
                                title="<?=lang('Input a number between 0~100')?>"/>
                                <div class="input-group-addon">%</div>
                            </div>
                        </div>
                        <span class="errors"><?php echo form_error('rev_share'); ?></span>
                        <span id="error-rev_share" class="errors"></span>
                    </div> <!-- input number rev_share (required) }}}4 -->
                    <!-- input number Rolling Comm (required) {{{4 -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <?=lang('Rolling Comm');?>
                                </div>
                                <input type="text" class="form-control" 
                                id="rolling_comm" name="rolling_comm" 
                                value="<?=set_value('rolling_comm', '0.00')?>"
                                title="<?=lang('Input a number between 0~3')?>" />
                                <div class="input-group-addon">%</div>
                            </div>
                        </div>
                        <span class="errors"><?php echo form_error('rolling_comm'); ?></span>
                        <span id="error-rolling_comm" class="errors"></span>
                    </div> <!-- input number Rolling Comm (required) }}}4 -->
                    <!-- Select Rolling Comm Basis (required) {{{4 -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <?=lang('Rolling Comm Basis');?>
                                </div>
                                <select name="rolling_comm_basis" id="rolling_comm_basis" 
                                    class="form-control input-sm" 
                                    title="<?=lang('Select Rolling Comm Basis')?>"
                                    onclick="setDisplayTotalBetsExcept()">
                                    <option value="" <?=set_select('rolling_comm_basis','',TRUE)?>>
                                    --  <?=lang('None');?> --
                                    </option>
                                    <option id="basis_total_bets" value="total_bets" 
                                    <?=set_select('rolling_comm_basis','total_bets')?> >
                                    <?=lang('Total Bets');?>
                                    </option>
                                    <option value="total_lost_bets" 
                                    <?=set_select('rolling_comm_basis','total_lost_bets')?> >
                                    <?=lang('Total Lost Bets');?>
                                    </option>
                                    <option value="total_bets_except_tie_bets" 
                                    <?=set_select('rolling_comm_basis','total_bets_except_tie_bets')?>
                                    >
                                    <?=lang('Total Bets Except Tie Bets');?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <span class="errors"><?php echo form_error('rolling_comm_basis'); ?></span>
                        <span id="error-rolling_comm_basis" class="errors"></span>
                    </div> <!-- Select Rolling Comm Basis (required) }}}4 -->
                    <!-- Select game type (display only when 'Total Bets' is selected) {{{4 -->
                    <div class="col-md-6" id="total_bets_except">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <?=lang('Except Game Type');?>
                                </div>
                                <select name="except_game_type" id="except_game_type" 
                                    class="form-control input-sm" 
                                    title="<?=lang('Select a game type')?>">
                                    <option value="" <?=set_select('except_game_type','',TRUE)?>>
                                    --  <?=lang('None');?> --
                                    </option>
                                    <?php for($i = 0; $i < count($game_types); $i++) { ?>
                                    <option value="<?=$game_types[$i]['game_type']?>" 
                                    <?=($conditions['total_bets_except'] == $game_types[$i]['game_type'])?'selected':''?> >
                                    <?=lang($game_types[$i]['game_type_lang']);?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <span class="errors"><?php echo form_error('except_game_type'); ?></span>
                        <span id="error-except_game_type" class="errors"></span>
                    </div> <!-- Select Rolling Comm Basis (required) }}}4 -->
                </fieldset>
            </div> <!-- fieldset commission-setting }}}3 -->
            <!-- fieldset permission-setting {{{3 -->
            <div class="col-md-12">
                <label for="permission-setting">
                    <?=lang('Permission Setting');?>
                </label>
                <fieldset>
                    <br>
                    <!-- input allowed_level {{{4 -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    #<?=lang('Allowed Level');?>
                                </div>
                                <input type="text" name="allowed_level" id="allowed_level" 
                                class="form-control " value="<?=set_value('allowed_level', 4);?>" 
                                data-toggle="tooltip" title="<?=lang('Allowed Level');?>">
                            </div>
                        </div>
                        <span class="errors"><?php echo form_error('allowed_level'); ?></span>
                        <span id="error-allowed_level" class="errors"></span>
                    </div> <!-- input allowed_level  }}}4 -->
                    <!-- button used to set leve names {{{4 -->
                    <div class="col-md-4">
                        <input type="button" class="btn btn-default btn-sm" value="<?=lang('Set Level Names');?>" 
                        onclick="set_level_names()">
                    </div> <!-- button }}}4 -->
                    <!-- checkboxes for agent type {{{4 -->
                    <div class="col-md-4">
                        <label><input type="checkbox" name="agent_type[]" value="can-have-sub-agents"
                        <?=isset($conditions['can_have_sub_agents']) && $conditions['can_have_sub_agents'] ? 'checked' : ''?>>
                        <?=lang('Can Have sub-agent');?>
                        </label>
                        <br>
                        <label><input type="checkbox" name="agent_type[]" value="can-have-players"
                        <?=isset($conditions['can_have_sub_agents']) && $conditions['can_have_sub_agents'] ? 'checked' : ''?>>
                        <?=lang('Can Have Players');?>
                        </label>
                        <br>
                        <label><input type="checkbox" name="agent_type[]" value="show-bet-limit-template"
                        <?=isset($conditions['show_bet_limit_template']) && $conditions['show_bet_limit_template'] ? 'checked' : ''?>>
                        <?=lang('Show Bet Limit Template');?>
                        </label>
                        <?php if ($this->utils->isEnabledFeature('rolling_comm_for_player_on_agency')): ?>
                        <br>
                        <label><input type="checkbox" name="agent_type[]" value="show-rolling-commission"
                        <?=isset($conditions['show_rolling_commission']) && $conditions['show_rolling_commission'] ? 'checked' : ''?>>
                        <?=lang('Show Rolling Commission');?>
                        </label>
                        <?php endif ?>
                        <br>
                        <label><input type="checkbox" name="agent_type[]" value="can-view-agents-list-and-players-list"
                        <?=isset($conditions['can_view_agents_list_and_players_list']) && $conditions['can_view_agents_list_and_players_list'] ? 'checked' : ''?>>
                        <?=lang('Can View Agents List and Players List');?>
                        </label>
                    </div> <!-- checkboxes }}}4 -->
                    <!--  leve names {{{4 -->
                    <?php for ($i = 0; $i < $max_allowed_level; $i++) { ?>
                    <?php $level_id = 'allowed_level_' . $i; ?>
                    <?php $agent_level_id = 'agent_level_' . $i; ?>
                    <input type="hidden" id="<?=$agent_level_id?>" name="<?=$agent_level_id?>">
                    <div class="col-md-12" id="<?=$level_id?>" name="<?=$level_id?>" >
                    </div>
                    <?php } ?>
                    <!-- Level names}}}4 -->
                </fieldset>

                <!--  modal for level name setting {{{4 -->
                <div class="modal fade in" id="level_name_modal" 
                    tabindex="-1" role="dialog" aria-labelledby="label_level_name_modal">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="label_level_name_modal"></h4>
                            </div>
                            <div class="modal-body"></div>
                            <div class="modal-footer"></div>
                        </div>
                    </div>
                </div> <!--  modal for level name setting }}}4 -->
            </div> <!-- fieldset permission-setting }}}3 -->
            <!-- fieldset vip_level {{{3 -->
            <div class="col-md-12">
                <label for="vip_level">
                    <font style="color:red;">*</font> 
                    <?=lang('VIP Level Setting (Choose at least one or more VIP level)');?>
                </label>
                <fieldset>
                    <!-- vip_level tree {{{4 -->
                    <div class="col-md-6 player_vip_level_tree">
                        <input type="hidden" id="selected_vip_levels" name="selected_vip_levels" value="">
                        <div class="row">
                            <div id="vip_level_tree" class="col-xs-12">
                            </div>
                        </div>
                        <span class="errors"><?php echo form_error('player_vip_levels'); ?></span>
                        <span id="error-player_vip_levels" class="errors"></span>
                    </div> <!-- vip_level tree }}}4 -->
                </fieldset>
            </div> <!-- fieldset vip_level }}}3 -->
            <!-- fieldset settlement-setting {{{3 -->
            <div class="col-md-12">
                <label for="settlement-setting">
                    <font style="color:red;">*</font> 
                    <?=lang('Settlement Setting');?>
                </label>
                <fieldset>
                    <br>
                    <!-- checkboxes for settlement period {{{4 -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="input-group pull-right">
                                    <?=lang('Settlement Period');?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="radio-inline">
                                    <input type="radio" name="settlement_period" 
                                    value="Daily" 
                                    <?=in_array('Daily',$conditions['settlement_period'])?'checked':''?>
                                    onclick="setDisplayWeeklyStartDay()">
                                    <?=lang('Daily');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="settlement_period" 
                                    id="settlement_period_weekly" value="Weekly" 
                                    <?=in_array('Weekly',$conditions['settlement_period'])?'checked':''?>
                                    onclick="setDisplayWeeklyStartDay()">
                                    <?=lang('Weekly');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="settlement_period" 
                                    value="Monthly" 
                                    <?=in_array('Monthly',$conditions['settlement_period'])?'checked':''?>
                                    onclick="setDisplayWeeklyStartDay()">
                                    <?=lang('Monthly');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="settlement_period" 
                                    value="Manual" 
                                    <?=in_array('Manual',$conditions['settlement_period'])?'checked':''?>
                                    onclick="setDisplayWeeklyStartDay()">
                                    <?=lang('Manual');?>
                                </label>
                            </div>
                            <span class="errors"><?php echo form_error('settlement_period[]'); ?></span>
                            <span id="error-settlement_period" class="errors"></span>
                        </div>
                    </div> <!-- checkboxes for settlement period }}}4 -->
                    <!-- radioes for weekly_start_day (only shown when Weekly is selected) {{{4 -->
                    <div id="weekly_start_day" class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="input-group pull-right">
                                    <?=lang('Start Day for Weekly Settlement:');?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="radio-inline">
                                    <input type="radio" name="start_day" value="Monday" 
                                    <?=$conditions['start_day']=='Monday'?'checked':''?>>
                                    <?=lang('Monday');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="start_day" value="Tuesday" 
                                    <?=$conditions['start_day']=='Tuesday'?'checked':''?>>
                                    <?=lang('Tuesday');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="start_day" value="Wednesday" 
                                    <?=$conditions['start_day']=='Wednesday'?'checked':''?>>
                                    <?=lang('Wednesday');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="start_day" value="Thursday" 
                                    <?=$conditions['start_day']=='Thursday'?'checked':''?>>
                                    <?=lang('Thursday');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="start_day" value="Friday" 
                                    <?=$conditions['start_day']=='Friday'?'checked':''?>>
                                    <?=lang('Friday');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="start_day" value="Saturday" 
                                    <?=$conditions['start_day']=='Saturday'?'checked':''?>>
                                    <?=lang('Saturday');?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="start_day" value="Sunday" 
                                    <?=$conditions['start_day']=='Sunday'?'checked':''?>>
                                    <?=lang('Sunday');?>
                                </label>
                            </div>
                            <span class="errors"><?php echo form_error('weekly_start_day'); ?></span>
                            <span id="error-weekly_start_day" class="errors"></span>
                        </div>
                    </div> <!-- radioes for weekly_start_day  }}}4 -->
                    <div class="row">
                        <div class="col-md-3">
                        </div>
                        <div class="col-md-9">
                            <span class="errors"><?php echo form_error('agent_settlement_period'); ?></span>
                            <span id="error-agent_settlement_period" class="errors"></span>
                        </div>
                    </div>
                </fieldset>
            </div> <!-- fieldset settlement-setting }}}3 -->
            <!-- button row {{{3 -->
            <div class="row">
                <div class="col-md-5 col-lg-5" style="padding: 10px;">
                </div>
                <div class="col-md-6 col-lg-6" style="padding: 10px;">
                    <input type="button" id="cancel" class="btn btn-default btn-sm" value="<?=lang('lang.reset');?>" 
                    onclick="window.location.href='<?php echo site_url('agency_management/create_structure'); ?>'">
                    <input type="submit" id="submit" class="btn btn-sm btn-primary" value="<?=lang('Create');?>" />
                </div>
            </div>
            <!-- button row }}}3 -->
        </div> <!-- panel body of create-structure  }}}2 -->    
    </div>
</form> <!-- end of form create-structure }}}1 -->    

<?php
$vip_levels_str = str_replace(' ', '', $conditions['vip_levels']);
$vip_levels_str = str_replace(',', '_', $vip_levels_str);
?>
<script>
$(document).ready(function(){
    var ajax_url = "<?=site_url('agency_management/structure_validation_ajax')?>";
    var labels = '<?=json_encode($labels)?>';
    var fields = '<?=json_encode($fields)?>';
    agency_form_validation(ajax_url, fields, labels);

    //var data_url = '<?= site_url('/api/get_player_vip_level_tree'); ?>';
    var data_url = '<?= site_url('api/get_player_vip_level_tree/'. $vip_levels_str); ?>';
    set_player_vip_level_tree(data_url);

});
</script>
<?php
// zR to open all folded lines
// vim:ft=php:fdm=marker
// end of create_structure.php
