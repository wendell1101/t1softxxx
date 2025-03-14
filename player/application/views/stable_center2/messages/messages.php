<div id="messages" class="panel">
    <div class="panel-heading">
        <?php if($this->utils->getConfig('show_message_list_title_in_mobile')):?>
        <h1 class="msg-list-title"><?=lang('Message List')?></h1>
        <?php endif;?>
        <div class="msg-tool-bar">
            <?php if(!$this->utils->isEnabledFeature('disabled_player_send_message')): ?>
                <a type="button" class="btn form-control msg-btn">
                    <img src="<?=base_url() . $this->utils->getPlayerCenterTemplate(FALSE)?>/img/icons/icon_compose.png">
                    <span class="tip-top"><?php echo lang("Compose Message") ?></span>
                </a>
            <?php endif ?>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body">
        <div id="messages-list">
            <table class="table table-striped table-hover table-ellipsis dt-responsive display nowrap">
                <tbody>
                <?php if (!empty($chat)) :?>
                    <?php foreach ($chat as $key => $value) {?>
                        <tr class="message-content" data-message-id="<?=$value['messageId']?>" data-message-subject="<?=$value['subject']?>" data-broadcast-id="<?=$value['broadcastId']?>">
                            <td id="msg-title-<?=$value['messageId']?>" class="msg-title">
                                <div class="msg-text"><?=$value['subject']?></div>
                                <div class="flags">
                                    <?php if(in_array($value['status'], [Internal_message::STATUS_ADMIN_NEW])): ?>
                                        <span class="flag-entry flag-new"><?=lang('cs.flag-new')?></span>
                                    <?php else: ?>
                                        <?php if (isset($value['admin_unread_count']) && $value['admin_unread_count']): ?>
                                            <span class="flag-entry flag-unread"><?=lang('cs.flag-unread')?></span>
                                        <?php endif ?>
                                    <?php endif ?>
                                </div>
                            </td>
                            <td class="msg-content">
                                <div class="msg-text">
                                    <?=trim(strip_tags(html_entity_decode($value['detail'])))?>
                                </div>
                            </td>
                            <td class="msg-date"><?=$value['date']?></td>
                        </tr>
                    <?php }?>
                <?php endif;?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Compose Message Modal -->
<div class="modal fade message-modal" id="composeMsg" tabindex="-1" role="dialog" aria-labelledby="composeMsgModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="composeMsgModal"><?php echo lang("Compose Message") ?></h4>
            </div>
            <div class="modal-body">
                <form role="form">
                    <div class="form-group">
                        <input type="text" class="form-control" id="subjectTitle" placeholder="<?=lang('Subject title')?>">
                        <textarea class="form-control" placeholder="<?=lang('Your message')?>" id="text-new-msg" maxlength="300"></textarea>
                        <button type="submit" class="btn form-control sendMsg"><?php echo lang("Send") ?></button>
                        <button type="button" class="btn form-control" data-dismiss="modal"><?php echo lang("lang.cancel") ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include VIEWPATH . '/resources/third_party/DataTable.php'; ?>
<script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/player-message.js')?>"></script>

<script type="text/javascript">
    $(function(){
        PlayerMessages.init();

        $('#messages-list table').DataTable($.extend({}, dataTable_options, {
            "columns": [
                {
                    "title": "<?=lang("Subject")?>",
                    "class": "msg-title",
                    "width": "30%",
                    "visible": true,
                    "orderable": false,
                    "responsivePriority": 2
                },
                {
                    "title": "<?=lang("Message")?>",
                    "class": "msg-content",
                    "width": "45%",
                    "visible": true,
                    "orderable": false,
                    "responsivePriority": 3
                },
                {
                    "title": "<?=lang("Date/Time")?>",
                    "class": "msg-date",
                    "width": "25%",
                    "visible": true,
                    "orderable": true,
                    "responsivePriority": 1
                }
            ],
            serverSide: false,
            order: [[2, 'desc']]
            <?php if ($this->utils->getConfig('hide_player_center_history_list_controls_when_no_data')) : ?>
            // OGP-21311: drawCallback not working, use fnDrawCallback instead
            , fnDrawCallback: function() {
                var wrapper = $(this).parents('.dataTables_wrapper');
                var status = $(wrapper).find('.dt-row:last');
                if ($(this).find('tbody td.dataTables_empty').length > 0) {
                    $(status).hide();
                }
                else {
                    $(status).show();
                }
            }
            <?php endif; ?>
        }));

        _export_sbe_t1t.on('run.t1t.smartbackend', function(){
            $('#messages-list').on('click', 'table tr', function(){
                var self = this;
                var message_id = $(this).data('message-id');
                var broadcast_id = $(this).data('broadcast-id');

                if(!message_id && !broadcast_id){
                    return false;
                }

                _export_sbe_t1t.player_message.show(message_id, broadcast_id, null, function(){
                    // $('.flags', self).remove();
                    // window.location.reload();
                });
            });
        });
    });
</script>