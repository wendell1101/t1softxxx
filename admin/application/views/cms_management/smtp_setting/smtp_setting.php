<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt"><i class="icon-cog" id="icon"></i >&nbsp;<?=lang('smtp.setting.title')?></h4>
    </div>
    <div class="panel-body">
        <form id="smtp_form" action="/cms_management/post_smtp_setting" method="post" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-sm-3">mail_smtp_server</label>
                <div class="col-sm-4">
                    <input name="mail_smtp_server" type="text" class="form-control" value="<?=$mail_smtp_server?>" required="required"/>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">mail_smtp_port</label>
                <div class="col-sm-4">
                    <input name="mail_smtp_port" type="text" class="form-control" value="<?=$mail_smtp_port?>" required="required"/>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">mail_smtp_auth</label>
                <div class="col-sm-4">
                    <input name="mail_smtp_auth" type="hidden" value="0"/>
                    <input name="mail_smtp_auth" type="checkbox" value="1" <?=$mail_smtp_auth == 0 ? '' : 'checked="checked"'?>/>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">mail_smtp_secure</label>
                <div class="col-sm-4">
                    <input name="mail_smtp_secure" type="text" class="form-control" value="<?=$mail_smtp_secure?>" required="required"/>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">mail_smtp_username</label>
                <div class="col-sm-4">
                    <input name="mail_smtp_username" type="text" class="form-control" value="<?=$mail_smtp_username?>" required="required"/>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">mail_smtp_password</label>
                <div class="col-sm-4">
                    <input type="password" class="form-control" value="<?=$mail_smtp_password?>" id="mail_smtp_password" required="required"/>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">mail_from</label>
                <div class="col-sm-4">
                    <input name="mail_from" type="text" class="form-control" value="<?=$mail_from?>" required="required"/>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">mail_from_email</label>
                <div class="col-sm-4">
                    <input name="mail_from_email" type="text" class="form-control" value="<?=$mail_from_email?>" required="required"/>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">disable_smtp_ssl_verify</label>
                <div class="col-sm-4">
                    <input name="disable_smtp_ssl_verify" type="hidden" value="0"/>
                    <input name="disable_smtp_ssl_verify" type="checkbox" value="1" <?=$disable_smtp_ssl_verify == 0 ? '' : 'checked="checked"'?>/>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-4 form-inline">
                    <button id="smtp_send" type="submit" name="action" value="save" class="btn btn-scooter"><?=lang('lang.save')?></button>
                    <div class="input-group">
                        <input type="email" class="form-control" id="email" name="email" value="<?=$email?>" placeholder="<?=$email?>"/>
                        <span class="input-group-btn">
                            <button type="submit" name="action" value="test" class="btn btn-default" onclick="return sendTest()">Test</button>
                        </span>
                    </div>
                </div>
            </div>
        </form>

        <div id="conf-modal" class="modal fade bs-example-modal-md" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header panel-heading">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h3 id="myModalLabel"><?=lang('sys.pay.conf.title');?></h3>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="help-block" id="conf-msg">
                                    <?=lang('Are you sure you want to change your smtp password');?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-linkwater" id="cancel-action" data-dismiss="modal"><?=lang('pay.bt.cancel');?></button>
                        <button type="button" class="btn btn-scooter" id="confirm-action"><?=lang('pay.bt.yes');?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    $(document).ready(function() {

        //Cancels confirmation
        $("#cancel-action").click(function () {
            $("#smtp_send").prop('disabled', false);
        });

        //Agreed to Confirmation
        $("#confirm-action").click(function () {
            var input = $("<input>")
               .attr("type", "hidden")
               .attr("name", "action").val("save");
            $("#mail_smtp_password").attr("name", "mail_smtp_password");
            $('#smtp_form').append(input);
            $('#smtp_form').submit();
        });

        $("#mail_smtp_password").change(function() {
            //to validate same with old password
            $("#smtp_send").on('click', function () {
                $(this).prop('disabled', true);
                $('#conf-modal').modal('show');
            });
        });
    });

    function sendTest() {
        return $('#email').val() != '';
    }
</script>
