<style type="text/css">
    /* for onoffswitch */
    .onoffswitch {
        position: relative;
        width: 120px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    .onoffswitch-checkbox {
        display: none;
    }

    .onoffswitch-label {
        display: block;
        overflow: hidden;
        cursor: pointer;
        border: 1px solid #999999;
        border-radius: 20px;
    }

    .onoffswitch-inner {
        display: block;
        width: 200%;
        margin-left: -100%;
        -moz-transition: margin 0.3s ease-in 0s;
        -webkit-transition: margin 0.3s ease-in 0s;
        -o-transition: margin 0.3s ease-in 0s;
        transition: margin 0.3s ease-in 0s;
    }

    .onoffswitch-inner:before,
    .onoffswitch-inner:after {
        display: block;
        float: left;
        width: 50%;
        height: 20px;
        padding: 0;
        line-height: 20px;
        font-size: 10px;
        color: white;
        font-family: Trebuchet, Arial, sans-serif;
        font-weight: bold;
        -moz-box-sizing: border-box;
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
    }

    .onoffswitch-inner:before {
        content: "<?= lang('ON') ?>";
        padding-left: 10px;
        background-color: #43ac6a;
        color: #FFFFFF;
    }

    .onoffswitch-inner:after {
        content: "<?= lang('OFF') ?>";
        padding-right: 10px;
        background-color: #EEEEEE;
        color: #999999;
        text-align: right;
    }

    .onoffswitch-default:after {
        content: "<?= lang('DEFAULT') ?>";
        padding-right: 10px;
        background-color: #EEEEEE;
        color: #999999;
        text-align: right;
    }

    .onoffswitch-switch {
        display: block;
        width: 18px;
        margin: 6px;
        background: #FFFFFF;
        border: 1px solid #999999;
        border-radius: 20px;
        position: absolute;
        top: 0;
        bottom: 0;
        right: 90px;
        -moz-transition: all 0.3s ease-in 0s;
        -webkit-transition: all 0.3s ease-in 0s;
        -o-transition: all 0.3s ease-in 0s;
        transition: all 0.3s ease-in 0s;
    }

    .onoffswitch-checkbox:checked+.onoffswitch-label .onoffswitch-inner {
        margin-left: 0;
    }

    .onoffswitch-checkbox:checked+.onoffswitch-label .onoffswitch-switch {
        right: 0px;
    }

    .onoffswitch-checkbox:disabled+.onoffswitch-label {
        background-color: #ffffff;
        cursor: not-allowed;
    }

    .note-tooltip+.tooltip .tooltip-inner {
        max-width: 400px;
    }

    .onoffswitch-box{
        margin-left: 5px;
        margin-bottom: -6px;
    }
    /* end onoffswitch */

    /* for smsApiSettings*/
    #smsApiSettings .col-md-4:first-child .form-group {
        overflow: hidden;
        margin-bottom: 5px;
        border-bottom: 1px solid rgba(0,0,0,.1);
        padding: 10px;
    }
    #smsApiSettings .rotation-order {
        background: #f4f4f4;
        padding: 20px;
        width: 100%;
        max-width: 300px;
        margin-left: 20px;
    }
    #smsApiSettings .rotation-order .form-group input[type="text"] {
        width: 30px;
        text-align: center;
        float: right;
        font-size: 12px;
    }
    #smsApiSettings .rotation-order .form-group {
        margin-bottom: 5px;
    }
    #smsApiSettings .apiOptions {
        display: grid;
        grid-gap: 18px;
        margin-top: 15px;
        margin-left: 14px;
    }
    #smsApiSettings .apiOptions .radio-inline {
        margin-left: 0;
    }
    #smsApiSettings .rotation {
        margin-left: 60px;
        position: relative;
        float: left;
    }
    #smsApiSettings .single {
        position: relative;
        float: left;
    }
    #smsApiSettings .single:before, #smsApiSettings .rotation:before {
        content: '';
        width: 1px;
        height: 118px;
        background: #aaa;
        display: block;
        position: absolute;
        left: 6px;
        top: 23px;
    }
    #smsApiSettings .radio-inline.remove-line:before {
        height: 0;
    }
    #smsApiSettings .radio-inline.remove-line .apiOptions {
        opacity: 0;
    }
    #smsApiSettings .apiOptions .radio-inline:before {
        content: '';
        height: 1px;
        width: 24px;
        background: #aaa;
        display: block;
        position: absolute;
        top: 7px;
        left: -27px;
    }
    #smsApiSettings .rotation .apiOptions {
        margin-left: 0;
    }
    #smsApiSettings .rotation .apiOptions .radio-inline:before {
        width: 31px;
        left: -14px;
    }
    #smsApiSettings .rotation:before {
        height: 123px;
    }
    /* end smsApiSettings*/

    /* For Rotation Order Settings list */
    .draggable-list {
        border: 1px solid var(--border-color);
        color: var(--text-color);
        padding: 0;
        list-style-type: none;
    }

    .draggable-list li {
        background-color: var(--background-color);
        display: flex;
        flex: 1;
    }

    .draggable-list li:not(:last-of-type) {
        border-bottom: 1px solid var(--border-color);
    }

    .draggable-list .number {
        background-color: var(--background-secondary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        width: 3.75rem;
        height: 3.75rem;
    }

    .draggable-list li.over .draggable {
        background-color: var(--draggable-color);
    }

    .draggable-list .smsapi-name {
        margin: 0 1.25rem 0 0;
    }

    .draggable-list li.right .smsapi-name {
        color: var(--right-color);
    }

    .draggable-list li.wrong .smsapi-name {
        color: var(--wrong-color);
    }

    .draggable {
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        flex: 1;
    }
    /* end Rotation Order Settings list */
</style>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title pull-left">
            <i class="glyphicon glyphicon-list-alt"></i> <?= lang('view_sms_api_settings'); ?>
        </h4>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body">
        <form action="<?= site_url('system_management/saveSmsApiSettings') ?>" method="post" id="smsApiSettings">
            <div class="col-md-4">
                <div class="col-md-12">
                    <!-- Registration -->
                    <div class="form-group">
                        <div class="pull-left" style="margin-right: 20px;">
                            <strong><?= lang('sms_api_register_setting'); ?>
                                <i class="fa fa-exclamation-circle" title="<?=lang("sms_api_register_setting.hint")?>"></i>
                            </strong>
                        </div>
                        <div class="pull-right">
                            <div class="onoffswitch">
                                <input type="checkbox" class="onoffswitch-checkbox" name="sms_api_register_setting_enabled" id="sms_api_register_setting_enabled" value="<?=$sms_api_register_setting_enabled?>" onClick="on_off_sms('register')" <?=$sms_api_register_setting_enabled == '1' ? 'checked' : ''?>>
                                <label class="onoffswitch-label" for="sms_api_register_setting_enabled">
                                  <span class="onoffswitch-inner"></span>
                                  <span class="onoffswitch-switch"></span>
                                </label>
                              </div>
                        </div>
                    </div>
                    <div class="form-group sms_api_register_setting_enabled" onchange="changeMode('register')">
                        <label class="radio-inline single">
                            <input type="radio" name="sms_api_register_setting_mode" value="single_mode" <?=($sms_api_register_setting_mode == "single_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_single_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="radio" name="sms_api_register_setting_single" value="<?=$sms_api?>" <?=($sms_api == $sms_api_register_setting_single) ? ' checked="checked"' : ''?>><?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>

                        <label class="radio-inline rotation remove-line">
                            <input type="radio" name="sms_api_register_setting_mode" value="rotation_mode" <?=($sms_api_register_setting_mode == "rotation_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_rotation_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="checkbox" name="sms_api_register_setting_rotation[]" value="<?=$sms_api?>" <?=in_array($sms_api, $sms_api_register_setting_rotation) ? ' checked="checked"' : ''?>> <?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                    </div>
                    <!-- end Registration -->
                    <!-- Login -->
                    <div class="form-group">
                        <div class="pull-left" style="margin-right: 20px;">
                            <strong><?= lang('sms_api_login_setting'); ?></strong>
                        </div>
                        <div class="pull-right">
                            <div class="onoffswitch">
                                <input type="checkbox" class="onoffswitch-checkbox" name="sms_api_login_setting_enabled" id="sms_api_login_setting_enabled" value="<?=$sms_api_login_setting_enabled?>" onClick="on_off_sms('login')" <?=$sms_api_login_setting_enabled == '1' ? 'checked' : ''?>>
                                <label class="onoffswitch-label" for="sms_api_login_setting_enabled">
                                  <span class="onoffswitch-inner"></span>
                                  <span class="onoffswitch-switch"></span>
                                </label>
                              </div>
                        </div>
                    </div>
                    <div class="form-group sms_api_login_setting_enabled" onchange="changeMode('login')">
                        <label class="radio-inline single">
                            <input type="radio" name="sms_api_login_setting_mode" value="single_mode" <?=($sms_api_login_setting_mode == "single_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_single_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="radio" name="sms_api_login_setting_single" value="<?=$sms_api?>" <?=($sms_api == $sms_api_login_setting_single) ? ' checked="checked"' : ''?>><?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>

                        <label class="radio-inline rotation remove-line">
                            <input type="radio" name="sms_api_login_setting_mode" value="rotation_mode" <?=($sms_api_login_setting_mode == "rotation_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_rotation_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="checkbox" name="sms_api_login_setting_rotation[]" value="<?=$sms_api?>" <?=in_array($sms_api, $sms_api_login_setting_rotation) ? ' checked="checked"' : ''?>> <?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                    </div>
                    <!-- end  Login-->

                    <!-- Bank info -->
                    <div class="form-group">
                        <div class="pull-left" style="margin-right: 20px;"><strong><?= lang('sms_api_bankinfo_setting'); ?></strong></div>
                        <div class="pull-right">
                            <div class="onoffswitch">
                                <input type="checkbox" class="onoffswitch-checkbox" name="sms_api_bankinfo_setting_enabled" id="sms_api_bankinfo_setting_enabled" value="<?=$sms_api_bankinfo_setting_enabled?>" onClick="on_off_sms('bankinfo')" <?=$sms_api_bankinfo_setting_enabled == '1' ? 'checked' : ''?>>
                                <label class="onoffswitch-label" for="sms_api_bankinfo_setting_enabled">
                                  <span class="onoffswitch-inner"></span>
                                  <span class="onoffswitch-switch"></span>
                                </label>
                              </div>
                        </div>
                    </div>
                    <div class="form-group sms_api_bankinfo_setting_enabled" onchange="changeMode('bankinfo')">
                        <label class="radio-inline single">
                            <input type="radio" name="sms_api_bankinfo_setting_mode" value="single_mode" <?=($sms_api_bankinfo_setting_mode == "single_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_single_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="radio" name="sms_api_bankinfo_setting_single" value="<?=$sms_api?>" <?=($sms_api == $sms_api_bankinfo_setting_single) ? ' checked="checked"' : ''?>><?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                        <label class="radio-inline rotation remove-line">
                            <input type="radio" name="sms_api_bankinfo_setting_mode" value="rotation_mode" <?=($sms_api_bankinfo_setting_mode == "rotation_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_rotation_mode')?>

                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="checkbox" name="sms_api_bankinfo_setting_rotation[]" value="<?=$sms_api?>" <?=in_array($sms_api, $sms_api_bankinfo_setting_rotation) ? ' checked="checked"' : ''?>> <?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                    </div>
                    <!-- end Bank info -->

                    <!-- Send message -->
                    <div class="form-group">
                        <div class="pull-left" style="margin-right: 20px;"><strong><?= lang('sms_api_sendmessage_setting'); ?></strong></div>
                        <div class="pull-right">
                            <div class="onoffswitch">
                                <input type="checkbox" class="onoffswitch-checkbox" name="sms_api_sendmessage_setting_enabled" id="sms_api_sendmessage_setting_enabled" value="<?=$sms_api_sendmessage_setting_enabled?>" onClick="on_off_sms('sendmessage')" <?=$sms_api_sendmessage_setting_enabled == '1' ? 'checked' : ''?>>
                                <label class="onoffswitch-label" for="sms_api_sendmessage_setting_enabled">
                                  <span class="onoffswitch-inner"></span>
                                  <span class="onoffswitch-switch"></span>
                                </label>
                              </div>
                        </div>
                    </div>
                    <div class="form-group sms_api_sendmessage_setting_enabled" onchange="changeMode('sendmessage')">
                        <label class="radio-inline single">
                            <input type="radio" name="sms_api_sendmessage_setting_mode" value="single_mode" <?=($sms_api_sendmessage_setting_mode == "single_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_single_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="radio" name="sms_api_sendmessage_setting_single" value="<?=$sms_api?>" <?=($sms_api == $sms_api_sendmessage_setting_single) ? ' checked="checked"' : ''?>><?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                        <label class="radio-inline rotation remove-line">
                            <input type="radio" name="sms_api_sendmessage_setting_mode" value="rotation_mode" <?=($sms_api_sendmessage_setting_mode == "rotation_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_rotation_mode')?>

                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="checkbox" name="sms_api_sendmessage_setting_rotation[]" value="<?=$sms_api?>" <?=in_array($sms_api, $sms_api_sendmessage_setting_rotation) ? ' checked="checked"' : ''?>> <?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                    </div>
                    <!-- end Send message -->
                    <!-- Forgot password -->
                    <div class="form-group">
                        <div class="pull-left" style="margin-right: 20px;"><strong><?= lang('sms_api_forgotpassword_setting'); ?></strong></div>
                        <div class="pull-right">
                            <div class="onoffswitch">
                                <input type="checkbox" class="onoffswitch-checkbox" name="sms_api_forgotpassword_setting_enabled" id="sms_api_forgotpassword_setting_enabled" value="<?=$sms_api_forgotpassword_setting_enabled?>" onClick="on_off_sms('forgotpassword')" <?=$sms_api_forgotpassword_setting_enabled == '1' ? 'checked' : ''?>>
                                <label class="onoffswitch-label" for="sms_api_forgotpassword_setting_enabled">
                                  <span class="onoffswitch-inner"></span>
                                  <span class="onoffswitch-switch"></span>
                                </label>
                              </div>
                        </div>
                    </div>
                    <div class="form-group sms_api_forgotpassword_setting_enabled" onchange="changeMode('forgotpassword')">
                        <label class="radio-inline single">
                            <input type="radio" name="sms_api_forgotpassword_setting_mode" value="single_mode" <?=($sms_api_forgotpassword_setting_mode == "single_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_single_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="radio" name="sms_api_forgotpassword_setting_single" value="<?=$sms_api?>" <?=($sms_api == $sms_api_forgotpassword_setting_single) ? ' checked="checked"' : ''?>><?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                        <label class="radio-inline rotation remove-line">
                            <input type="radio" name="sms_api_forgotpassword_setting_mode" value="rotation_mode" <?=($sms_api_forgotpassword_setting_mode == "rotation_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_rotation_mode')?>

                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="checkbox" name="sms_api_forgotpassword_setting_rotation[]" value="<?=$sms_api?>" <?=in_array($sms_api, $sms_api_forgotpassword_setting_rotation) ? ' checked="checked"' : ''?>> <?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                    </div>
                    <!-- end Forgot password -->
                    <!-- Security Page-->
                    <div class="form-group">
                        <div class="pull-left" style="margin-right: 20px;"><strong><?= lang('sms_api_security_setting'); ?></strong></div>
                        <div class="pull-right">
                            <div class="onoffswitch">
                                <input type="checkbox" class="onoffswitch-checkbox" name="sms_api_security_setting_enabled" id="sms_api_security_setting_enabled" value="<?=$sms_api_security_setting_enabled?>" onClick="on_off_sms('security')" <?=$sms_api_security_setting_enabled == '1' ? 'checked' : ''?>>
                                <label class="onoffswitch-label" for="sms_api_security_setting_enabled">
                                  <span class="onoffswitch-inner"></span>
                                  <span class="onoffswitch-switch"></span>
                                </label>
                              </div>
                        </div>
                    </div>
                    <div class="form-group sms_api_security_setting_enabled" onchange="changeMode('security')">
                        <label class="radio-inline single">
                            <input type="radio" name="sms_api_security_setting_mode" value="single_mode" <?=($sms_api_security_setting_mode == "single_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_single_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="radio" name="sms_api_security_setting_single" value="<?=$sms_api?>" <?=($sms_api == $sms_api_security_setting_single) ? ' checked="checked"' : ''?>><?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                        <label class="radio-inline rotation remove-line">
                            <input type="radio" name="sms_api_security_setting_mode" value="rotation_mode" <?=($sms_api_security_setting_mode == "rotation_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_rotation_mode')?>

                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="checkbox" name="sms_api_security_setting_rotation[]" value="<?=$sms_api?>" <?=in_array($sms_api, $sms_api_security_setting_rotation) ? ' checked="checked"' : ''?>> <?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                    </div>
                    <!-- end ecurity Page -->
                    <!-- Account info  -->
                    <div class="form-group">
                        <div class="pull-left" style="margin-right: 20px;"><strong><?= lang('sms_api_accountinfo_setting'); ?></strong></div>
                        <div class="pull-right">
                            <div class="onoffswitch">
                                <input type="checkbox" class="onoffswitch-checkbox" name="sms_api_accountinfo_setting_enabled" id="sms_api_accountinfo_setting_enabled" value="<?=$sms_api_accountinfo_setting_enabled?>" onClick="on_off_sms('accountinfo')" <?=$sms_api_accountinfo_setting_enabled == '1' ? 'checked' : ''?>>
                                <label class="onoffswitch-label" for="sms_api_accountinfo_setting_enabled">
                                  <span class="onoffswitch-inner"></span>
                                  <span class="onoffswitch-switch"></span>
                                </label>
                              </div>
                        </div>
                    </div>
                    <div class="form-group sms_api_accountinfo_setting_enabled" onchange="changeMode('accountinfo')">
                        <label class="radio-inline single">
                            <input type="radio" name="sms_api_accountinfo_setting_mode" value="single_mode" <?=($sms_api_accountinfo_setting_mode == "single_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_single_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="radio" name="sms_api_accountinfo_setting_single" value="<?=$sms_api?>" <?=($sms_api == $sms_api_accountinfo_setting_single) ? ' checked="checked"' : ''?>><?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                        <label class="radio-inline rotation remove-line">
                            <input type="radio" name="sms_api_accountinfo_setting_mode" value="rotation_mode" <?=($sms_api_accountinfo_setting_mode == "rotation_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_rotation_mode')?>

                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="checkbox" name="sms_api_accountinfo_setting_rotation[]" value="<?=$sms_api?>" <?=in_array($sms_api, $sms_api_accountinfo_setting_rotation) ? ' checked="checked"' : ''?>> <?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                    </div>
                    <!-- end Account info  -->

                    <!-- Manager info  -->
                    <div class="form-group">
                        <div class="pull-left" style="margin-right: 20px;"><strong><?= lang('sms_api_manager_setting'); ?></strong></div>
                        <div class="pull-right">
                            <div class="onoffswitch">
                                <input type="checkbox" class="onoffswitch-checkbox" name="sms_api_manager_setting_enabled" id="sms_api_manager_setting_enabled" value="<?=$sms_api_manager_setting_enabled?>" onClick="on_off_sms('manager')" <?=$sms_api_manager_setting_enabled == '1' ? 'checked' : ''?>>
                                <label class="onoffswitch-label" for="sms_api_manager_setting_enabled">
                                  <span class="onoffswitch-inner"></span>
                                  <span class="onoffswitch-switch"></span>
                                </label>
                              </div>
                        </div>
                    </div>
                    <div class="form-group sms_api_manager_setting_enabled" onchange="changeMode('manager')">
                        <label class="radio-inline single">
                            <input type="radio" name="sms_api_manager_setting_mode" value="single_mode" <?=($sms_api_manager_setting_mode == "single_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_single_mode')?>
                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="radio" name="sms_api_manager_setting_single" value="<?=$sms_api?>" <?=($sms_api == $sms_api_manager_setting_single) ? ' checked="checked"' : ''?>><?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                        <label class="radio-inline rotation remove-line">
                            <input type="radio" name="sms_api_manager_setting_mode" value="rotation_mode" <?=($sms_api_manager_setting_mode == "rotation_mode") ? ' checked="checked"' : ''?>><?=lang('sms_api_rotation_mode')?>

                            <div class="apiOptions">
                                <?php foreach($sms_api_list as $idx => $sms_api): ?>
                                    <label class="radio-inline">
                                        <input type="checkbox" name="sms_api_manager_setting_rotation[]" value="<?=$sms_api?>" <?=in_array($sms_api, $sms_api_manager_setting_rotation) ? ' checked="checked"' : ''?>> <?=lang('operator_settings.sms_api_list.'.$idx)?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                    </div>
                    <!-- end Manager info  -->
                </div>
            </div>
            <div class="col-md-8">
                <div class="rotation-order">
                    <div class="form-group">
                        <label for=""><strong><?=lang('Rotation Order Settings')?> <i class="fa fa-exclamation-circle" title="<?=lang("Rotation Order Settings.hint")?>"></i></strong></label>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" class="use_random_order" name="use_random_order" id="use_random_order" value="<?=$use_random_order?>" <?=($use_random_order == "1") ? ' checked="checked"' : ''?>>
                        <label for="use_random_order"><?=lang('Use Random Order')?></label>
                    </div>
                    <ul class="draggable-list" id="draggable-list"></ul>
                </div>
            </div>
            <div class="col-md-12">
                <div class="text-center">
                    <input type="submit" class="btn btn-portage" value="<?=lang('lang.save')?>" style="margin-top: 20px;">
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    const listItems = [];
    const smsApiRotationList = [];
    let dragStartIndex;
    const draggableList = document.getElementById("draggable-list");
    let getSmsApiRotationList = JSON.parse('<?=json_encode($api_rotation_order)?>');
    let mode_list = JSON.parse('<?=json_encode($mode_list)?>')
    let enable_list = JSON.parse('<?=json_encode($enable_list)?>');
    let map_sms_lang = JSON.parse('<?=json_encode($map_sms_lang)?>');

    $(document).ready(function(){

        $.each(getSmsApiRotationList ,function (key,val) {
            smsApiRotationList.push(val);
        });

        $.each(enable_list ,function (key,sms_api_setting_enable) {
            checkSettingEnable(sms_api_setting_enable);
        });

        $.each(mode_list ,function (key,sms_api_setting_mode) {
            checkModeSelection(sms_api_setting_mode);
        });

        const use_random_order = $('#use_random_order').is(':checked');
        if (use_random_order) {
            $('#use_random_order').val('1');
            $('#draggable-list').hide();
        }else{
            $('#use_random_order').val('0');
            $('#draggable-list').show();
        }
        createList();
    });

    function checkModeSelection(sms_api_setting_mode){
        var currentSelectionSingleMode = $('input[name="' + sms_api_setting_mode + '"][value=single_mode]');
        var currentSelectionRotationMode = $('input[name="' + sms_api_setting_mode + '"][value=rotation_mode]');

        if (currentSelectionSingleMode.is(':checked')) {
            currentSelectionSingleMode.parent().siblings().addClass('remove-line');
            currentSelectionSingleMode.siblings().show();
            currentSelectionSingleMode.parent().removeClass('remove-line');
        }
        else if (currentSelectionRotationMode.is(':checked')) {
            currentSelectionRotationMode.parent().siblings().addClass('remove-line');
            currentSelectionRotationMode.siblings().show();
            currentSelectionRotationMode.parent().removeClass('remove-line');
        }else{
            currentSelectionSingleMode.attr('checked','checked');
            currentSelectionSingleMode.closest('div').find('.apiOptions:first').children(':first').children(':first').attr('checked','checked');
        }
    }

    function checkSettingEnable(sms_api_setting_enable){
        const isCheck = $('#'+ sms_api_setting_enable).is(':checked');
        if (isCheck) {
            $('.' + sms_api_setting_enable).show();
        }else{
            $('.' + sms_api_setting_enable).hide();
        }
    }

    function createList() {
        const newList = [...smsApiRotationList];
        newList
            // .map((smsapi) => ({ value: smsapi, sort: Math.random() })) // randomize list
            // .sort((a, b) => a.sort - b.sort) // generate new order
            // .map((smsapi) => smsapi.value) // retrieve original strings
            .forEach((smsapi, index) => {
                let smsapi_name = map_sms_lang[smsapi] || smsapi;
                const listItem = document.createElement("li");

                listItem.setAttribute("data-index", index);
                listItem.innerHTML = `
                    <span class="number">${index + 1}</span>
                    <div class="draggable" draggable="true">
                    <p class="smsapi-name">${smsapi_name}</p>
                    <i class="fas fa-grip-lines"></i>
                    <input type="hidden" name="api_rotation_order[]" value="${smsapi}">
                    </div>`;
                listItems.push(listItem);
                draggableList.appendChild(listItem);
            });
        addListeners();
    }

    function addListeners() {
        const draggables = document.querySelectorAll(".draggable");
        const dragListItems = document.querySelectorAll(".draggable-list li");
            draggables.forEach((draggable) => {
            draggable.addEventListener("dragstart", dragStart);
        });
        dragListItems.forEach((item) => {
            item.addEventListener("dragover", dragOver);
            item.addEventListener("drop", dragDrop);
            item.addEventListener("dragenter", dragEnter);
            item.addEventListener("dragleave", dragLeave);
        });
    }

    function dragStart() {
        dragStartIndex = +this.closest("li").getAttribute("data-index");
    }

    function dragEnter() {
        this.classList.add("over");
    }

    function dragLeave() {
        this.classList.remove("over");
    }

    function dragOver(e) {
        e.preventDefault(); // dragDrop is not executed otherwise
    }
    function dragDrop() {
        const dragEndIndex = +this.getAttribute("data-index");
        swapItems(dragStartIndex, dragEndIndex);
        this.classList.remove("over");
    }

    function swapItems(fromIndex, toIndex) {
        // Get Items
        const itemOne = listItems[fromIndex].querySelector(".draggable");
        const itemTwo = listItems[toIndex].querySelector(".draggable");
        // Swap Items
        listItems[fromIndex].appendChild(itemTwo);
        listItems[toIndex].appendChild(itemOne);
    }

    $('#use_random_order').on('click', function(){
        const use_random_order = $(this).is(':checked');
        if (use_random_order) {
            $(this).val('1');
            $(this).attr('checked', true);
            $('#draggable-list').hide();
        }else{
            $(this).val('0');
            $(this).attr('checked', false);
            $('#draggable-list').show();
        }
    });

    function changeMode(type) {
        const currentSelection = $('input[name="sms_api_'+type+'_setting_mode"]:checked').val();
        const $this = $('input[name="sms_api_'+type+'_setting_mode"][value=' + currentSelection + ']');

        if (currentSelection == 'single_mode') {
            $this.parent().siblings().addClass('remove-line');
            $this.siblings().show();
            $this.parent().removeClass('remove-line');
        }
        else if (currentSelection == 'rotation_mode') {
            $this.parent().siblings().addClass('remove-line');
            $this.siblings().show();
            $this.parent().removeClass('remove-line');
        }
    }

    function on_off_sms(type) {
        const $this = $('#sms_api_'+ type +'_setting_enabled');
        const isCheck = $this.is(':checked');

        if (isCheck) {
            $this.val('1');
            $('.sms_api_'+ type +'_setting_enabled').show();
            $this.attr('checked', true);
        }else{
            $this.val('0');
            $this.attr('checked', false);
            $('.sms_api_'+ type +'_setting_enabled').hide();
        }
    }
</script>