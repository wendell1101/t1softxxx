<div data-file-info="ajax_ui_cancelled_transfer_condition.php" data-datatable-selector="#cancelled-transfer-condition-table">
    <div class="form-inline">
        <?php //if($this->permissions->checkPermissions('cancel_member_transfer_condition')): ?>
        <!--            <button type="button" value="" title="--><?//=lang('recover.tc')?><!--" id="cancelTcItems" class="btn btn-scooter btn-sm">-->
        <!--                <i class="glyphicon glyphicon-repeat" data-placement="bottom" ></i>-->
        <!--                --><?//=lang('recover.tc');?>
        <!--            </button>-->
        <?php //endif; ?>
        <div><?=lang('pay.startedAt');?></div>
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="true"/>
        <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart"/>
        <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd"/>
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search');?>"/>
    </div>
    <hr/>
    <div class="clearfix">
        <table id="cancelled-transfer-condition-table" class="table table-bordered">
            <thead>
            <th><?=lang('pay.promoname');?></th>
            <th><?=lang('tc.totalBetAmount');?></th>
            <th><?=lang('pay.totalPlayerBet');?></th>
            <th><?=lang('pay.startedAt');?></th>
            <th><?=lang('pay.bt.updatedon');?></th>
            <th><?=lang('pay.completedAt');?></th>
            <th><?=lang('cms.disallow_wallet_transfer_in');?></th>
            <th><?=lang('cms.disallow_wallet_transfer_out');?></th>
            <th><?=lang('lang.status');?></th>
            </thead>
        </table>
    </div>
</div>

<script type="text/javascript">
    var title = '<?= lang('recover.tc') ?>';
    var checkedTcRows = [];
    var wallet_info_history_modal = $('#wallet_info_history_modal');

    function cancelledTransferCondition() {
        console.log('cancelledTransferCondition line 80');
        var dataTable = $('#cancelled-transfer-condition-table').DataTable({
            dom: "<'row'<'col-md-12'<'pull-left progress-container'><'pull-right'B><'pull-right'l>>><'table-responsive't><'row'<'col-md-12'<'dt-information-summary2 text-info pull-left' i><'pull-right'p>>>",
            autoWidth: false,
            searching: false,
            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: ['btn-linkwater']
                }
            ],
            columnDefs: [
                {
                    "targets": 0,
                    "orderable": false
                }
            ],
            order: [
                [3, 'desc']
            ],
            ajax: function (data, callback, settings) {
                data.extra_search = [
                    {
                        'name':'dateRangeValueStart',
                        'value':$('#dateRangeValueStart').val()
                    },
                    {
                        'name':'dateRangeValueEnd',
                        'value':$('#dateRangeValueEnd').val()
                    }
                ];

                $.post('/api/cancelled_transfer_condition/' + playerId, data, function(data) {
                    callback(data);
                },'json');
            },
            drawCallback : function() {
                $(".chk-tc-item").on('click', function(){
                    var id = $(this).val();
                    if( $(this).is(":checked") ) {
                        checkedTcRows.push(id);
                    } else {
                        var index = checkedTcRows.indexOf(id);
                        if (index > -1) {
                            checkedTcRows.splice(index, 1);
                        }
                    }
                });
                historyAttachEventsListener();
            }
        });

        $('#changeable_table #btn-submit').click( function() {
            dataTable.ajax.reload();
        });

        ATTACH_DATATABLE_BAR_LOADER.init('cancelled-transfer-condition-table');

        //$('#cancelTcItems').on('click', function() {
        //    if(checkedTcRows.length > 0) {
        //        var content = '<?//= lang('conf.recover.tc'); ?>//';
        //        var button = '';
        //        button += '<button class="btn btn-sm btn-linkwater" data-dismiss="modal" aria-label="Close"><?//=lang('lang.no')?>//</button>'
        //        button += '<button class="btn btn-sm btn-scooter" id="recoverTcBtn" onclick="batch_recover_transfer_condition();"><?//=lang('lang.yes')?>//</button>';
        //
        //        confirm_modal(title, content, button);
        //    } else {
        //        var content = '<?//=lang("no.selected.cancelled.tc")?>//';
        //        error_modal(title, content);
        //    }
        //});
    }

    function getTransferConditionWalletInfoHistory(detail_wallets){
        var tbody = '';
        $.each(detail_wallets, function(k, v){
            tbody += '<tr>';
            tbody += '<td>' + (k+1) + '. ' + v + '</td>';
            tbody += '</tr>';
        });

        $('.wallet_history_body').html(tbody);
    }


    function historyAttachEventsListener(){
        $('.check_disallow_transfer_in_wallet_history , .check_disallow_transfer_out_wallet_history').click(function(){
            getTransferConditionWalletInfoHistory($(this).data('wallet'));
            wallet_info_history_modal.modal('show');
        });

        $('.close_wallet_info_history').click(function(){
            $('.wallet_history_body').html('');
        });
    }

    //function batch_recover_transfer_condition() {
    //    $('#recoverTcBtn').attr("disabled", true);
    //    $.post('/player_management/batchRecoverTransferCondition', {transfer_condition_ids : checkedTcRows}, function(){
    //        success_modal(title, '<?//= lang('tc.success.recovered'); ?>//');
    //        $('#cancelled-transfer-condition-table').DataTable().ajax.reload();
    //    });
    //}
</script>