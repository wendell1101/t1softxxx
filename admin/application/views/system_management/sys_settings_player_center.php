<style type="text/css">
    select.disabledColor {
        color: #B7B4B6;
    }
</style>

<div class="panel panel-primary panel_main">

	<div class="panel-heading">
		<h4 class="panel-title"><i class="fa fa-cogs"></i> &nbsp;<?= lang('Player Center') ?>
		<a href="#main_panel" data-toggle="collapse" class="pull-right"><i class="fa fa-caret-down"></i></a>
		</h4>
	</div>

	<div id="main_panel" class="panel-collapse collapse in ">

	<div class="panel-body">

<form class="system_settings_form" action="<?php echo site_url('system_management/save_system_settings'); ?>" method="POST">
    <ul class="nav nav-tabs">
        <?php foreach ($settings as $category_name => $category_settings): ?>
        <li><a data-toggle="tab" href="#<?=$category_name?>_settings"><?=$category_settings['name']?></a></li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content panel-default">
        <?php foreach ($settings as $category_name => $category_settings): ?>
        <div id="<?=$category_name?>_settings" class="tab-pane fade">
            <table class="table table-hover table-striped table-bordered">
                <thead>
                    <tr>
                    <th class="col-md-4"><?php echo lang('aff.al36'); ?></th>
                    <th class="col-md-8"><?php echo lang('Value'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($category_settings['options'] as $setting_name => $setting_info) { ?>
                    <tr>
                        <td>
                        <?php
                        if (isset($setting_info['params']['label_lang']) && !empty($setting_info['params']['label_lang'])) {
                            echo lang($setting_info['params']['label_lang']);
                        } elseif (isset($setting_info['note']) && !empty($setting_info['note'])) {
                            echo $setting_info['note'];
                        } else {
                            echo $setting_name;
                        }
                        ?>
                        </td>
                    <td>
                    <div class="form-group">
                        <?=(isset($setting_info['params'])) ? Operatorglobalsettings::renderFormElement($setting_name, $setting_info) : ''?>
                    </div>
                    </td>
                    </tr>
                <?php }?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>

        <div class="row">
            <input type="submit" class="btn btn-scooter" value="<?=lang('Save'); ?>">
        </div>
    </div>
</form>
	</div>

	</div>

</div>

<script type="text/javascript">
    $("#collapseSubmenu_sys_settings").addClass("in");
    $("a#system_settings").addClass("active");
    $("a#sys_settings_player_center").addClass("active");
    var base_url = "<?=base_url()?>";
    var enable_change_password = $("input[name='value_enabled_change_withdrawal_password[]']").eq(0);
    var hidden_change_password = $("input[name='value_enabled_change_withdrawal_password[]']").eq(1);
    var enable_manually_deposit_cool_down  = $("input[name='value_manual_deposit_request_cool_down']").eq(0);
    var disable_manually_deposit_cool_down = $("input[name='value_manual_deposit_request_cool_down']").eq(1);
    $(document).ready(function(){
        window.URL = window.URL || window.webkitURL;

        $('.system_settings_form .nav-tabs li:first-child a').trigger('click');
        setHiddenChangePassword();
        setManualDepositRequestCoolDownTimeSettingDisable();
        $("input[name='value_limit_of_single_ip_registrations_per_day']").attr('data-toggle', 'tooltip');
        $("input[name='value_limit_of_single_ip_registrations_per_day']").attr('title', "<?= lang('sys_settings_player_center.limit_of_single_ip_registrations_per_day.tooltip') ?>");
    });
    // resizeSidebar();

    function setHiddenChangePassword() {
        hidden_change_password.attr("disabled",!enable_change_password.is(":checked"));
        if(!enable_change_password.is(":checked")){
            hidden_change_password.prop("checked", false);
        }
    }

    enable_change_password.click(function(){
        setHiddenChangePassword();
    });

    function setManualDepositRequestCoolDownTimeSettingDisable(){

        if(enable_manually_deposit_cool_down.is(":checked")){
            $("select[name='value_manual_deposit_request_cool_down_time']").prop("disabled", false).removeClass('disabledColor');
        }

        if(disable_manually_deposit_cool_down.is(":checked")){
            $("select[name='value_manual_deposit_request_cool_down_time']").prop("disabled", 'disabled').addClass('disabledColor');
        }
    }

    $("input[name='value_manual_deposit_request_cool_down']").change(function(){
        setManualDepositRequestCoolDownTimeSettingDisable();
    });

</script>
