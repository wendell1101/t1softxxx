<?php
/**
 *   filename:   agent_information.php
 *   date:       2016-05-25
 *   @brief:     view for agent Information
 */

// PHP {{{1
$agent_id = $agent['agent_id'];
$activate_url = site_url($controller_name . '/activate_agent/' . $agent_id);
$inactivate_url = site_url($controller_name . '/inactivate_agent/' . $agent_id);
$freeze_url = site_url($controller_name . '/freeze_agent/' . $agent_id);
$suspend_url = site_url($controller_name . '/suspend_agent/' . $agent_id);
$edit_url = site_url($controller_name . '/edit_agent/' . $agent_id);
$sub_list_url = site_url($controller_name . '/sub_agents_list?parent_id=' . $agent_id);
$reset_pass_url = site_url($controller_name . '/reset_password/' . $agent_id);
$reset_withdrawal_pass_url = site_url($controller_name . '/reset_withdrawal_password/' . $agent_id);
$adjust_credit_limit_url = site_url($controller_name . '/adjust_credit_limit/' . $agent_id);
$adjust_credit_url = site_url($controller_name . '/adjust_credit/' . $agent_id);
$parent_agent_url = site_url($controller_name . '/agent_information/' . $parent['agent_id']);
$session_agent_name = $this->session->userdata('agent_name');
$agent_level = $this->session->userdata('agent_level');
$this->utils->debug_log('agent_level', $agent_level, $parent['agent_level']);

// PHP }}}1
?>

<!-- HTML {{{1 -->
<input type="hidden" id="agent_id" value="<?=$agent_id?>"/>
<div class="content-container">
    <div class="col-md-12" id="toggleView">
        <div class="panel panel-primary">
            <!-- heading {{{2 -->
            <div class="panel-heading">
                <h4 class="panel-title pull-left custom-pt">
                    <i class="icon-list"></i> &nbsp;<?=lang('Agent Information');?>
                </h4>
                <div class="pull-right">
                    <?php if($this->utils->getConfig('enabled_otp_on_agency')){ ?>
                    <a id="btn_otp_settings" href="<?=site_url('/agency/otp_settings')?>" class="btn btn-info btn-sm"><?=lang('2FA Settings')?></a>
                    <?php } ?>
                </div>
                <div class="clearfix"></div>
            </div> <!-- heading }}}2 -->

            <div class="panel-body" id="agent_panel_body">
                <!-- Basic Info {{{2 -->
                <div class="panel panel-primary">
                    <!-- panel heading {{{3 -->
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a href="#basic_info" id="hide_agent_basic_info" class="btn btn-info btn-sm">
                                <i class="glyphicon glyphicon-chevron-up" id="hide_agentbi_up"></i>
                            </a>
                            &nbsp; <?=lang('Basic Information');?>
                            <?php if ($this->utils->isEnabledFeature('agency_information_self_edit')): ?>
                                <!-- panel buttons {{{4 -->
                                <div class="pull-right">
                                    <?php if ($agent['status'] == 'frozen') {?>
                                    <span style="color: red;"><?=lang('The agent is FROZEN!')?></span>
                                    <?php if ($agent['agent_name'] != $session_agent_name) {?>
                                    <input type='button' value='<?php echo lang('Activate'); ?>'
                                    class='btn btn-info btn-sm pull-right agent-oper'
                                    onclick="window.location.href='<?php echo $activate_url; ?>';">
                                    <?php } ?>
                                    <?php } else {?>
                                    <?php if ($agent['status'] == 'active') {?>
                                    <?php if ($agent['agent_name'] != $session_agent_name) {?>
                                    <a href="<?=$edit_url?>" class="btn btn-sm btn-default agent-oper">
                                        <i class="glyphicon glyphicon-edit"></i> <?=lang('lang.edit');?>
                                    </a>

                                    <input type='button' value='<?php echo lang('Suspend'); ?>'
                                    class='btn btn-warning btn-sm agent-oper'
                                    onclick="window.location.href='<?php echo $suspend_url; ?>';">
                                    <input type='button' value='<?php echo lang('Freeze'); ?>'
                                    class='btn btn-danger btn-sm agent-oper'
                                    onclick="window.location.href='<?php echo $freeze_url; ?>';">
                                    <?php } ?>
                                    <?php } else {?>
                                    <span style="color: red;"><?=lang('The agent is SUSPENDED!')?></span>
                                    <?php if ($agent['agent_name'] != $session_agent_name) {?>
                                    <input type='button' value='<?php echo lang('Activate'); ?>'
                                    class='btn btn-info btn-sm agent-oper'
                                    onclick="window.location.href='<?php echo $activate_url; ?>';">
                                    <?php } ?>
                                    <?php } ?>
                                    <?php } ?>
                                </div>
                                <!-- panel buttons }}}4 -->
                            <?php endif ?>
                        </h4>
                    </div>

                    <div class="panel-body agent_basic_panel_body" id="agent_basic_info">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered" style="margin-bottom:0;">
                                        <tr>
                                            <th class="active col-md-2"><?=lang('Agent Username');?></th>
                                            <td class="col-md-4"><?=$agent['agent_name']?></td>
                                            <th class="active col-md-2">
                                                <span id="_password_label"><?=lang('Password');?></span>
                                            </th>
                                            <td>
                                                <?php  if ($this->utils->isEnabledFeature('agency_information_self_edit') && $agent['agent_name'] == $session_agent_name && $agent['status'] == 'active') { ?>
                                                <a href="<?=$reset_pass_url;?>" class="btn btn-xs btn-primary">
                                                    <?=lang('lang.reset');?>
                                                </a>
                                                <?php }  ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="active col-md-2"><?=lang('Total Sub Agents');?></th>
                                            <td class="col-md-4">
                                                <?php if (count($sub_agents) > 0) {?>
                                                <?=count($sub_agents);?>
                                                <?php }?>
                                            </td>
                                            <th class="active col-md-2">
                                                <span id="_withdrawal_password_label"><?=lang('Withdrawal Password');?></span>
                                            </th>
                                            <td>
                                                <?php  if ($this->utils->isEnabledFeature('agency_information_self_edit') && $agent['agent_name'] == $session_agent_name && $agent['status'] == 'active') { ?>
                                                <a href="<?=$reset_withdrawal_pass_url;?>" class="btn btn-xs btn-primary">
                                                    <?=lang('lang.reset');?>
                                                </a>
                                                <?php }  ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="active col-md-2">
                                                <?=lang('Credit Limit');?>
                                            </th>
                                            <td class="col-md-4">
                                                <?=$agent['credit_limit']?>
                                                <?php if ($this->utils->isEnabledFeature('agency_information_self_edit') && $agent['status'] == 'active') {?>
                                                <?php if ($agent['agent_name'] != $this->session->userdata('agent_name')) {?>
                                                <a href="<?=$adjust_credit_limit_url;?>"
                                                    class="btn btn-primary btn-xs">
                                                    <?=lang('Adjust Credit Limit')?>
                                                </a>
                                                <?php } ?>
                                                <?php } ?>
                                            </td>
                                            <th class="active col-md-2"><?=lang('Status');?></th>
                                            <td class="col-md-4"><?php echo lang($agent['status']);?></td>
                                        </tr>
                                        <tr>
                                            <th class="active col-md-2"><?=lang('Settlement Period');?></th>
                                            <td class="col-md-4"><?=$agent['settlement_period']?></td>
                                            <th class="active col-md-2"><?=lang('Agent Tracking Code');?></th>
                                            <td class="col-md-10" colspan="3"><a href="/<?=$controller_name?>/tracking_link_list"><?=$agent['tracking_code']; ?></a></td>
                                        </tr>
                                        <tr>
                                            <th class="active col-md-2"><?=lang('VIP Groups');?></th>
                                            <td class="col-md-4"><?=$vip_groups ?></td>
                                            <?php if ( ! $this->utils->isEnabledFeature('agency_hide_binding_player')): ?>
                                                <?php include __DIR__.'/../includes/agent_binding_player.php'; ?>
                                            <?php endif ?>
                                        </tr>
                                        <tr>
                                            <th class="active col-md-2"><?=lang('Note');?></th>
                                            <td class="col-md-10" colspan="3"><?=$agent['note'] ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- panel body }}}3 -->
                </div>
                <!-- End of Basic Info }}}2 -->

                <?php include __DIR__.'/../includes/agent_bank_info.php'; ?>
                <?php if ($this->utils->isEnabledFeature('agent_tier_comm_pattern')) { ?>
                    <?=$this->load->view('includes/game_platform_settings_tier_comm', $game_platform_settings, TRUE)?>
                <?php } else { ?>
                    <?=$this->load->view('includes/game_platform_settings', $game_platform_settings, TRUE)?>
                <?php } ?>

                <?php if(!$this->utils->isEnabledFeature('disable_agent_hierarchy')){?>
                <?php include __DIR__.'/../includes/agent_hierarchic_tree.php'; ?>
                <?php } ?>
            </div>
            <div class="panel-footer"></div>
        </div>
    </div>
</div>
<!-- HTML }}}1 -->

<!-- JS {{{1 -->
<script type="text/javascript">
    var controller_name = "<?=$controller_name?>";
    var agent_id = "<?=$agent_id?>";
    // basic {{{2
<?php $agent_status = $this->session->userdata('agent_status'); ?>
<?php if($agent_status == 'suspended') { ?>;
set_suspended_operations();
set_agent_operations();
<?php } ?>

//getMonthlyEarnings(0);
//getPayments(0);
$(document).ready(function() {
$('#earningsTable').DataTable( {
"responsive": {
details: {
type: 'column'
}
},
"columnDefs": [ {
className: 'control',
orderable: false,
targets:   0
} ],
"order": [ 1, 'asc' ]
} );

// Filters
$('#yearmonth').on('change', function(){
var filter = $(this).val();
$('#earningsTable').DataTable().search(filter).draw();
});


$('#sub_option_submit').on('click', function(){
$('#form_sub_option').submit();
});

// prevent negative value
$('input[type="number"]').on('change', function(){
if($(this).val() < 0) $(this).val(0);
});


// START DEFAULT AFFILIATE SHARES JS ===============================================

$('#form_option_1 input').on('change', function(){
// enable save button
$('#btn_save').prop('disabled', false);
$('#option_1_submit').prop('disabled', false);
// get option and enable it
var option = $(this).closest('.panel').find('.panel-heading').find('.option').prop('checked', 'checked');
});

$('#form_option_2 input').on('change', function(){
// enable save button
$('#btn_save').prop('disabled', false);
$('#option_2_submit').prop('disabled', false);

// get option and enable it
var option = $(this).closest('.panel').find('.panel-heading').find('.option').prop('checked', 'checked');
});

$('.btn_collapse').on('click', function(){
// get current state
var child = $(this).find('i');

// change ui
if(child.hasClass('glyphicon-chevron-down')) {
    child.removeClass('glyphicon-chevron-down');
    child.addClass('glyphicon-chevron-up')
} else {
child.removeClass('glyphicon-chevron-up');
child.addClass('glyphicon-chevron-down')
}
});

$('#btn_save').on('click', function(){
// check which option is selected
var option = $('input[name=option]:checked').val();

switch(option) {
case 'option1':
    $('#option_1_submit').trigger('click');
        break;
case 'option2':
    $('#option_2_submit').trigger('click');
        break;
}
});

// END DEFAULT AFFILIATE SHARES JS ====================================================

// START DEFAULT SUB AFFILIATES SHARES JS =============================================
var sub_level_shares = 0;

$('#sub_agent_main_panel_body input').on('change', function(){
$('#btn_save_sub').prop("disabled", false);
});

$('#sub_level').on('change', function(){
var levels = $(this).val();
var label = $('#sub_level_label');
var container = $('#sub_level_container');
container.html("");

var tpl_singular = $('#single_sub_level').html();
var tpl_multiple = $('#multi_sub_level').html();

if(levels == 0) {
    label.addClass('hide');

    $('#btn_save_sub_1').prop('disabled', 'disabled');
    $('#sub_option2_body input[type="submit"]').prop('disabled', false);
} else if(levels == 1) {
    label.removeClass('hide');
    // get template
    var html = tpl_singular;

    // append single row template
    container.html(html);
} else if(levels > 1) {
    label.removeClass('hide');

    // append multiple template
    for(i=sub_level_shares; i<levels; i++) {
        var html = Mustache.render(tpl_multiple, {counter: i + 1});
        container.append(html);
    }
}
});

// main option
$('#btn_sub_allow').parent().on('click', function(){
$('#btn_group_sub_allowed').removeClass('hide');
$('.sub-agent-options').removeClass('hide');
});
$('#btn_sub_anallow').parent().on('click', function(){
$('btn_group_sub_allowed label').removeClass('active');
$('btn_group_sub_allowed input').prop('checked', false);
$('#btn_group_sub_allowed').addClass('hide');
$('.sub-agent-options').addClass('hide');

$('#sub_option1_body input[type="number"]').prop('disabled', 'disabled');
$('#sub_option2_body input[type="number"]').prop('disabled', 'disabled');
});

// sub option
$('#btn_sub_all').parent().on('click', function(){
$('#sub_option1_body input[type="number"]').prop('disabled', false);
$('#sub_option2_body input[type="number"]').prop('disabled', false);
});

$('#btn_sub_manual').parent().on('click', function(){
$('#sub_option1_body input[type="number"]').prop('disabled', false);
$('#sub_option2_body input').prop('disabled', 'disabled');
});

$('#btn_sub_link').parent().on('click', function(){
$('#sub_option1_body input').prop('disabled', 'disabled');
$('#sub_option2_body input[type="number"]').prop('disabled', false);
});

// main setting
$('#sub_option1_body input').on('change', function(){
$('#btn_save_sub').prop('disabled', false);
$('#form_sub_option input[type="submit"]').prop('disabled', false);
$('#form_sub_option input[type="hidden"]').prop('disabled', false);
});
$('#sub_option2_body input').on('change', function(){
$('#btn_save_sub').prop('disabled', false);
$('#form_sub_option input[type="submit"]').prop('disabled', false);
$('#form_sub_option input[type="hidden"]').prop('disabled', false);
});

// trigger save
$('#btn_save_sub').on('click', function(){
$('#sub_option_submit').trigger('click');
});
// others }}}2

});

</script>
<!-- JS }}}1 -->
<?php
// zR to open all folded lines
// vim:ft=php:fdm=marker
// end of agent_information.php
