<!--BATCH SEND MAIL BOX START-->
<?php $types = $this->utils->getConfig('send_batch_email_type');?>
<div id="batch-send-mail-box" class="modal fade bs-example-modal-md" data-backdrop="static" data-keyboard="false"
    role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header panel-heading">
                <h3 id="myModalLabel"><?=lang('lang.send.batch.sendgrid-mail')?>
                </h3>
            </div>
            <div id="result-content" class="modal-body" style="display: none;">
                <div class="fail_report_container">
                </div>
            </div>
            <div id="send-batch-mail-content" class="modal-body ">
                <div class="row">
                    <div class="col-md-12">
                        <div class="help-block" id="conf-msg-ask"></div>
                        <div class="form-group">
                            <label class="control-label"><?=lang('lang.select.players')?></label>
                            <div style=" max-height:100px;overflow-y:auto;">
                                <style>
                                    .select2-selection__rendered {
                                        color: #008CBA;
                                    }
                                </style>
                                <select class="from-username form-control" id="player_username_batch_mail" multiple="true"
                                    style="width:100%;"></select>
                                <button style="position:relative;bottom:30px;right:2px;" type="button"
                                    id="clear-member-selection-batchmail" class="btn btn-default btn-xs pull-right">
                                    <fa class="glyphicon glyphicon-remove"></fa><?=lang('lang.clear.selections')?>
                                </button>
                                <span class="help-block player-username-help-block" style="color:#F04124"></span>
                                <input type="checkbox" name="send_to_all_player" id="send_to_all_player">
                                <label class="control-label" for="send_to_all_player"><?=lang('lang.send.all-players')?></label>
                            </div>
                        </div>
                            <div class="form-group">
                                <input type="file" class="form-control" name="batch_mail_csv" id="batch_mail_csv" accept=".csv" required/>
                                        <button type="button" style="position:relative;bottom:30px;right:2px;" id="batch_mail_csv_clear" title="" class="btn btn-default btn-xs pull-right" ><fa class="glyphicon glyphicon-remove"></fa><?=lang('lang.clear.selections')?></button>
                                <div class="help-block">
                                    <strong><?=lang("csv_required")?></strong>&nbsp;&nbsp;
                                    (<a href="<?= '/resources/sample_csv/sample_player_send_message.csv' ?>"><span><?= lang('Download Sample File') ?></span></a>)
                                </div>
                                <input type="hidden" id="batch_mail_csv_data" name="batch_mail_csv_data">
                            </div>

                        <div class="sendBatchTab">
                            <ul id="sendBatchTab-header" class="nav nav-tabs">
                                <?php
                                foreach ($types as $type) {
                                    switch ($type) {
                                        case 'SMTP': ?>
                                            <li <?php echo array_search('SMTP', $types) == 0 ? 'class="active"' : ''; ?>
                                                id="batch-smtp">
                                                <a href="#smtp" data-toggle="tab"> <?=lang("SMTP")?>
                                                </a>
                                            </li>
                                            <?php  break; ?>
                                        <?php case 'SENDGRID': ?>
                                            <li <?php echo array_search('SENDGRID', $types) == 0 ? 'class="active"' : ''; ?>
                                                id="batch-sendgrid-template">
                                                <a href="#template" data-toggle="tab"> <?=lang("Sendgrid API")?>
                                                </a>
                                            </li>
                                            <?php  break;
                                    } //EOF switch
                                } // EOF foreach?>
                            </ul>
                            <div class="tab-content">
                                <?php
                                foreach ($types as $type) {
                                    switch ($type) {
                                        case 'SMTP': ?>
                                        <div class="tab-pane fade <?php echo array_search('SMTP', $types) == 0 ? 'in active ' : ''; ?>" id="smtp">
                                            <div class="form-group">
                                                <label class="control-label"><?=lang("Subject")?></label>
                                                <input type="text" class="form-control" id="batch_mail_subject"
                                                    name='batch_mail_subject' placeholder="<?=lang("Subject")?>"></input>
                                                <span class="help-block" style="color:#F04124"></span>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label"><?=lang("cu.7")?></label>
                                                <textarea class="form-control" id="batch_mail_message_body"
                                                    name="batch_mail_message_body" rows="3"></textarea>
                                                <span class="help-block" style="color:#F04124"></span>
                                            </div>
                                        </div>
                                        <?php  break; ?>
                                        <?php case 'SENDGRID': ?>
                                        <div class="tab-pane fade <?php echo array_search('SENDGRID', $types) == 0 ? 'in active ' : ''; ?>"
                                            id="template">
                                            <div class="form-group" id='template-input'>
                                                <label class="control-label"><?=lang("Template ID")?></label>
                                                <input type="text" class="form-control" id="sendgrid_template_id"
                                                    name='sendgrid_template_id' placeholder="Template ID"></input>
                                                <span class="help-block" style="color:#F04124"></span>
                                            </div>
                                            <div class="form-group" id='template-select' style="display: none;">
                                                <label for="sendgrid_template_id_list"><?=lang("Template ID")?></label>
                                                <select class="form-control" id="sendgrid_template_id_list">
                                                </select>
                                                <span class="help-block" style="color:#F04124"></span>
                                            </div>
                                        </div>
                                        <?php  break;
                                    } //EOF switch
                                } // EOF foreach?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div style="height:70px;position:relative;">
                    <button type="button" class="btn btn-default" id="cancel-send-batch" data-dismiss="modal"><?=lang('lang.close');?></button>
                    <button type="button" id="send-batch-mail" class="btn btn-success">
                        <?=lang('Batch Send Email')?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--BATCH SEND MAIL BOX END-->

<script>
    $(document).ready(function() {

        var modal = $('#batch-send-mail-box');
        var sendToAllPlayer = $('#send_to_all_player');
        var playerSelect = $("#player_username_batch_mail", modal);
        var playerCSVInput  = $("input[name='batch_mail_csv']", modal);
        var clearSelection = $('#clear-member-selection-batchmail');
        var templateTab = $("#batch-sendgrid-template");

        sendToAllPlayer.click(function() {
            if (sendToAllPlayer.prop("checked")) {
                disablePlayerSelection2();
            } else {
                enablePlayerSelection2();
            }
        });

        playerCSVInput.change(function(){
            var v = $(this).val();
            if(v != ''){
                playerSelect.removeAttr("required");

                var res = v.split('.').pop();

                if(res != 'csv'){
                    playerCSVInput.val('');
                    alert('<?=lang('notify.invalid.file')?>');

                    return false;
                }
                appendCSVdata();
                disablePlayerSelection2();
                $('#send_to_all_player').prop("disabled", true);

            }else{
                enablePlayerSelection2();
                $('#send_to_all_player').prop("disabled", false);
            }
        });

        $("#batch_mail_csv_clear").click(function() {
            playerCSVInput.val('').trigger("change");
            $('#batch_mail_csv_data').val('');
        });

        $('#batch-send-mail-box').on('show.bs.modal', function() {

            $("#sendgrid_template_id_list").empty();
            $('#send-batch-mail').prop("disabled", true);
            $.getJSON('<?=site_url('player_management/getTemplateIdListFromSendGrid')?>',
                function(data){
                    if(data.status == 'success'){
                        var templates = data.msg.templates;
                        if(templates && templates.length>0) {
                            $('#template-input').hide();
                            $('#template-select').show();
                            for(var i=0; i < templates.length; i++){
                                var item = templates[i];
                                var option = new Option(item['name']+' - '+ item['id'], item['id']);
                                $("#sendgrid_template_id_list").append(option);
                            }
                            $('#sendgrid_template_id').val('');
                        }
                    } else {
                        $('#template-input').show();
                        $('#template-select').hide();
                    }
                }
            ).done(function(){
                $('#send-batch-mail').prop("disabled", false);
            }).fail(function(){
                $('#template-input').show();
                $('#template-select').hide();
            });
        });

        function disablePlayerSelection2() {
            playerSelect.attr('disabled', true);
            clearSelection.trigger('click').attr('disabled', true);
            $(".player-username-help-block").html('');
        }

        function enablePlayerSelection2() {
            playerSelect.attr('disabled', false);
            clearSelection.attr('disabled', false);
        }

    });
    function appendCSVdata(){
        let csvElement = document.getElementById('batch_mail_csv');
        let csvInput = csvElement.files[0];
        let reader = new FileReader();
        var csvContent = '';
        reader.onload = function (e) {
            csvContent = e.target.result;
            csvContent = csvContent.replace(/\r?\n|\r/g, ",");
            document.getElementById('batch_mail_csv_data').value = csvContent;
        };
        reader.readAsText(csvInput);
    }

</script>