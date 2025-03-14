<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseAbnormalWithdrawal" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="collapseAbnormalWithdrawal" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/payment_management/view_withdrawal_abnormal'); ?>" method="get">
                <div class="row">
                    <!-- Date -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?= lang('cs.abnormal.payment.datetime'); ?>
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="true" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>

                    <!-- username -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label" for="username"><?=lang('pay.username')?></label>
                        <input id="username" type="text" name="username"  value="<?php echo $conditions['username']; ?>"  class="form-control input-sm"/>
                    </div>

                    <!-- status -->
                   <div class="form-group col-md-3 col-lg-3">
                       <label for="by_status" class="control-label"><?=lang('excess.withdrawal.status');?> </label>
                       <?=form_dropdown('by_status', $status_list, $conditions['by_status'], 'class="form-control input-sm iovation_report_status by_status"'); ?>                        
                   </div>

                    <!-- operator -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label  class="control-label"><?=lang('cs.abnormal.payment.operator');?></label>
                        <select name="update_by" id="update_by" class="form-control input-sm">
                            <option value=""><?=lang('sys.vu05');?></option>
                            <?php if (!empty($user_group)) { ?>
                                <?php foreach ($user_group as $user) {?>
                                    <option value="<?=$user['userId']?>" <?=(($conditions['update_by'] == $user['userId']) ? ' selected="selected"' : '')?>><?=$user['username']?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm btn-linkwater">
                            <button type="submit" class="btn btn-sm btn-portage"><?=lang("lang.search")?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="glyphicon glyphicon-exclamation-sign"></i><?=lang("Excess Withdrawal Requests List")?>
            <div class="pull-right">
                <?php if($this->CI->permissions->checkPermissions('adjust_withdrawal_abnormal')) {?>
                    <a href="javascript:void(0)" id="set_read" class="btn btn-info btn-xs hide" title="<?=lang('Mark as Read');?>" onclick="setWithdrawToRead('1')">
                        <i class="glyphicon glyphicon-eye-open" style="color:white;" ></i> <?php echo lang('Mark as Read'); ?>
                    </a>
                    <a href="javascript:void(0)" id="set_unread" class="btn btn-info btn-xs hide" title="<?=lang('Mark as Unread');?>" onclick="setWithdrawToRead('2')">
                        <i class="glyphicon glyphicon-eye-close" style="color:white;"></i> <?php echo lang('Mark as Unread'); ?>
                    </a>
                <?php } ?>
            </div>
        </h4>
    </div>
        <div class="panel-body" id="chat_panel_body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed table-bordered" id="result_table">
                    <thead>
                        <tr>
                            <th style="padding: 8px" class="th_chk_multiple"><input type="checkbox" id="checkWhite" onclick="checkAll(this.id)"/></th>
                            <th><?=lang("excess.withdrawal.status")?></th> <!-- read/unread -->
                            <th><?=lang('lang.status')?></th> <!-- order status -->
                            <th><?=lang("Withdraw Code")?></th>
                            <th><?=lang("pay.username")?></th>
                            <th style="min-width:45px;"><?=lang("pay.reqtime")?></th>
                            <th style="min-width:45px;"><?=lang("pay.proctime")?></th>
                            <th style="min-width:45px;"><?=lang("pay.paidtime")?></th>
                            <th><?=lang('pay.playerlev')?></th>
                            <th><?=lang("Tag")?></th>
                            <th><?=lang('pay.withamt')?></th>
                            <th><?=lang("excess.withdrawal.created_at")?></th>
                            <th><?=lang("excess.withdrawal.update_at")?></th>
                            <th><?=lang("excess.withdrawal.operator")?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    <div class="panel-footer"></div>
</div>

<div class="row">
    <div class="modal fade" id="batchProcessModal" style="margin-top:130px !important;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title batch-process-title"><?= lang('Batch Process Summary')?></h4>
                </div>
                <div class="modal-body">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                            <span class="progressbar-text"><?= lang('Processing....') ?></span>
                        </div>
                    </div>
                    <table class="table table-striped" id="batchProcessTable">
                        <thead>
                            <tr>
                                <th width="30"><?= lang('lang.status') ?></th>
                                <th width="50"><?= lang('ID') ?></th>
                                <th><?= lang('Remarks') ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    var success_trans = 0;
    var fail_trans = 0;
    var totalTransation = 0;
    var totalCompleteTrans = 0;

    $(document).ready(function(){
        var dataTable = $('#result_table').DataTable({
            stateSave: true,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
                <?php if( $export_report_permission ){ ?>
                ,{
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();
                       var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/excessWithdrawalRequestsList'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                {
                    sortable: false,
                    targets: [0]
                },
            ],
            order: [[5, 'desc']],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/excessWithdrawalRequestsList", data, function(data) {
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                        $('#set_read').addClass('hide');
                        $('#set_unread').addClass('hide');
                    }
                    else {
                        dataTable.buttons().enable();
                        $('#set_read').removeClass('hide');
                        $('#set_unread').removeClass('hide');
                    }
                }, 'json');
            }
        });

        $('#btnResetFields').click(function() {
            $(".by_status").val("2");
            $("#username").val("");
            $("#update_by").val("");
            $('.dateInput').data('daterangepicker').setStartDate(moment().startOf('day').format('Y-MM-DD HH:mm:ss'));
            $('.dateInput').data('daterangepicker').setEndDate(moment().endOf('day').format('Y-MM-DD HH:mm:ss'));
            dateInputAssignToStartAndEnd($('#search_withdrawal_date'));
        });

        $('#batchProcessModal').on('hidden.bs.modal', function () {
            window.location.reload();
        });
    });

    function setWithdrawToRead(status) {

        var emptySelectionMessage = "<?= lang('select.withdraw.process') ?>";
        if (!$('.checkWhite:checked').length) {
            alert(emptySelectionMessage);
            return false;
        }

        if(!confirm('<?=lang('sys.sure');?>')) {
            return false;
        }

        var modalTitle = "<?= lang('lang.batch.process.summary') ?>";
        if(status == '1'){
            modalTitle = "<?= lang('Mark as Read') ?>";
        }else if(status == '2'){
            modalTitle = "<?= lang('Mark as Unread') ?>";
        }

        $('.checkWhite:checked').each(function(i, obj) {
            var abnormalOrder = $(this).val();
            var withdrawcode = $(this).data('withdrawcode');
            $('.batch-process-title').text(modalTitle);
            $('#batchProcessModal').modal('show');

            setTimeout(
                function () {
                    makeBatchOrder(abnormalOrder,status,withdrawcode);
            }, 3000);
        });
    }

    function makeBatchOrder(abnormalOrder,status,withdrawcode) {
        $.ajax({
            'url' : base_url +'payment_management/setWithdrawalStatusToRead/'+status,
            'type' : 'POST',
            'data': {abnormalOrder: abnormalOrder},
            'cache' : false,
            'dataType' : "json"
        }).done(function(data) {
            if(data['success']){
                appendToBatchProcessSummary('Success', withdrawcode, data['message']);
                return true;
            } else {
                appendToBatchProcessSummary('Failed', withdrawcode, data['message']);
                return false;
            }
        }).fail(function(){
            appendToBatchProcessSummary('Failed', withdrawcode, '<?php echo lang("Process Failed");?>');
            return false;
        });
    }

    function appendToBatchProcessSummary(status, id, remarks) {
        $('#batchProcessTable').append('<tr><td>'+status+'</td><td>'+id+'</td><td>'+remarks+'</td></tr>');

        if (status == 'Failed') {
            fail_trans++;
        } else {
            success_trans++;
        }

        totalCompleteTrans++;

        if (totalCompleteTrans == totalTransation) {
            completeProcess();
        }
    }

    function completeProcess() {
        $( ".progress-bar" ).removeClass('active');
        $( ".progress-bar" ).addClass('progress-bar-warning');
        $(".progressbar-text").text("<?= lang('Done!') ?>");
    }

    function openWithdrawalRequestList(id) {
        var $this = $('#'+id);
        var detail_btn = $this.val();
        var withdrawCode = $this.data('withdrawcode');
        var dwStatus = $this.data('dwstatus');
        var playerId = $this.data('playerid');
        var startDate = $this.data('createdon');
        var endDate = '<?=$this->utils->getTodayForMysql()?> 23:59:59';
        var detailModal = $this.data('detail_modal');

        if (withdrawCode && dwStatus) {

            setCookie("view_detail", '1', 1);
            setCookie("detail_modal", detailModal, 1);

            window.open( base_url 
                + 'payment_management/viewWithdrawalRequestList?dwStatus=' + dwStatus 
                + '&withdraw_code=' + withdrawCode
                + '&withdrawal_date_from=' + startDate
                + '&withdrawal_date_to=' + endDate
                + '&search_status=' + dwStatus
                + '&enable_date=1'
                );
        }
    }

    //设置cookie
    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + "; " + expires;
    }

    function checkAll(id) {
        var list = document.getElementsByClassName(id);
        var all = document.getElementById(id);

        if (all.checked) {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 1;
            }
            $("#delete_form").attr("onsubmit","return confirmDelete();");

        } else {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 0;
            }

            $("#delete_form").attr("onsubmit","");
        }
    }

    function uncheckAll(id) {
        var list = document.getElementById(id).dataset.checkedAllFor;
        var all = document.getElementById(list);

        var item = document.getElementById(id);
        var allitems = document.getElementsByClassName(list);
        var cnt = 0;

        if (item.checked) {
            for (i = 0; i < allitems.length; i++) {
                if (allitems[i].checked) {
                    cnt++;
                }
            }

            if (cnt == allitems.length) {
                all.checked = 1;
            }
        } else {
            all.checked = 0;
        }
    }
</script>