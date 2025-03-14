<div id="sms-message-box" class="modal fade bs-example-modal-md" data-backdrop="static" data-keyboard="false" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header panel-heading">
                <h3 class="type-sms"><?=lang('lang.send.sms')?></h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <textarea class="form-control" id="sms-content" rows="3" maxlength="300"></textarea>
                            <span class="help-block" style="color:#F04124"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" >
                <div style="height:70px; position:relative;">
                    <button type="button" id="reset-message" class="btn btn-default" onclick="resetFields()"><?=lang('lang.reset');?></button>
                    <button type="button" id="cancel-send" class="btn btn-default" data-dismiss="modal"><?=lang('lang.close');?></button>
                    <button type="button" id="send-sms" class="btn btn-primary" onclick="sendSMS()"><?=lang('cu.9')?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function showSMSbox() {
        new resetFields();
        $('#sms-message-box').modal('show');

        var double_submit_hidden_field = '<?=$double_submit_hidden_field?>';
        $('#sms-message-box').append(double_submit_hidden_field);
    }

    function resetFields(){
        $('#sms-content')
            .val("")
            .next('span').html('');
    }

    $('#cancel-send').click(function(){
      removeDoubleSubmitInput();
    });

    function removeDoubleSubmitInput(){
        $('#admin_double_submit_post').remove();
    }

    function sendSMS() {
        if ($.trim($('#sms-content').val()) == '') {
            $('#sms-content').next('span').html('<?=sprintf(lang('gen.error.required'), lang("cu.7"))?>');
            return;
        } else {
            $('#sms-content').next('span').html('');
        }

        $('#cancel-send').attr("disabled", true);
        $('#send-sms').attr("disabled", true);

        var data = {
            message: $('#sms-content').val(),
            playerIds: Array('<?=$player['playerId']?>'),
            label: 'userInformation_send_sms',
            admin_double_submit_post: $('#admin_double_submit_post').val()
        }

        $.ajax({
            url: '/player_management/sendBatchSmsMessage',
            type: 'POST',
            data: data,
            dataType: "json"
        }).done(function(data) {
            $('#sms-message-box').modal('hide');
            $('#send-sms').attr("disabled", false);
            $('#cancel-send').attr("disabled", false);

            if (data.status == "success") {
                $.notify({ message: data.msg }, {type: 'success', timer: 500, delay: 1000, spacing: 10, offset:{y:100,x:20} } );
            } else {
                $.notify({ message: data.msg }, {type: 'danger', timer: 500, delay: 1000, spacing: 10, offset:{y:100,x:20} } );
            }
            window.location.reload();
        }).fail(function(jqXHR, textStatus) {
            $('#sms-message-box').modal('hide');
            $('#send-sms').attr("disabled", false);
            $('#cancel-send').attr("disabled", false);
            $.notify({ message: textStatus }, {type: 'danger', timer: 500, delay: 1000, spacing: 10, offset:{y:100,x:20} } );
            window.location.reload();
        });
    }
</script>