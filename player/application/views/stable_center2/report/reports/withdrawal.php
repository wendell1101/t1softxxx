<!-- ============ Withdrawal List ================== -->
<div id="ah-withdrawal" role="tabpanel" class="tab-pane">
    <div id="withdrawal-box" class="report table-responsive">
        <table id="withdrawalResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
    </div>
</div>
<script type="text/javascript">
    var withdrawalTB;

    function withdrawalHistory() {
        var table_container = $('#withdrawalResultTable');
        var enable_withdrawl_fee_from_player = '<?=$this->utils->getConfig('enable_withdrawl_fee_from_player')?>';
        var enabled_player_cancel_pending_withdraw = '<?=$this->utils->getConfig('enabled_player_cancel_pending_withdraw')?>';

        if(withdrawalTB !== undefined){
            withdrawalTB.page.len($('#pageLength').val());
            withdrawalTB.ajax.reload();
            return false;
        }

        var columns = [];
        var i = 0;

        columns.push({
            "name": "dwDateTime",
            "title": "<?=lang('pay.reqtime')?>",
            "data": i++,
            "visible": true,
            "orderable": true
        });
        columns.push({
            "name": "transactionCode",
            "title": "<?=lang('Withdrawal Code')?>",
            "data": i++,
            "visible": true,
            "orderable": false
        });
        columns.push({
            "name": "Status",
            "title": "<?=lang('Status')?>",
            "data": i++,
            "visible": true,
            "orderable": false
        });
        columns.push({
            "name": "amount",
            "title": "<?=lang('Amount')?>",
            "data": i++,
            "visible": true,
            "orderable": false
        });

        if (enabled_player_cancel_pending_withdraw) {
            columns.push({
                "name": "cancelWithdrawal",
                "title": "<?=lang('Action')?>",
                "data": i++,
                "visible": true,
                "orderable": false
            });
        }

        if(enable_withdrawl_fee_from_player){
            columns.push({
                "name": "amount",
                "title": "<?=lang('fee.withdraw')?>",
                "data": i++,
                "visible": true,
                "orderable": false
            });
        }

        columns.push({
            "name": "Notes",
            "title": "<?=lang('Notes')?>",
            "data": i,
            "visible": true,
            "orderable": false
            <?php
            $player_center_withdrawal_page = $this->utils->getOperatorSettingJson('player_center_withdrawal_page');
            if(empty($player_center_withdrawal_page)){
                $player_center_withdrawal_page =[];
            }
            $player_center_hide_time_in_remark = in_array('player_center_hide_time_in_remark', $player_center_withdrawal_page);
            if ( ! empty($player_center_hide_time_in_remark ) ) : ?>
            ,"render": function ( data, type, row ) {
                return data.replace(/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]\s*:?\s*/g, '');
            }
            <?php endif; ?>
        });
        columns.push({ // for the responsive extention to display control row button
            "title": "&nbsp",
            "data": 1,
            "visible": true,
            "orderable": false,
            "render": function(){
                return '&nbsp';
            },
            "responsivePriority": 1
        });

        withdrawalTB = table_container.DataTable($.extend({}, dataTable_options, {
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            columnDefs: [ {  // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets:   -1
            }],
            order: [[0, 'desc']],
            ajax: {
                url: '/api/WithdrawWalletTransaction',
                type: 'post',
                data: function ( d ) {
                    d.extra_search = [
                        {
                            'name':'dateRangeValueStart',
                            'value': $('#sdate').val(),
                        },
                        {
                            'name':'dateRangeValueEnd',
                            'value': $('#edate').val(),
                        },
                    ];
                },
            }
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
    }

    function cancel_withdraw(walletAccountId,playerId){
        if(!confirm('<?=lang('Are you sure you want to cancel this withdrawal?')?>')) {
            return;
        }

        var notesType = 104;
        var dwStatus = 'request';
        var showDeclinedReason = false;
        $.ajax({
            'url' : '/api/cancelWithdrawalByPlayer/'+walletAccountId+'/'+playerId,
            'type' : 'post',
            'data' : {'notesType':notesType,'dwStatus':dwStatus,'showDeclinedReason':showDeclinedReason},
            'success' : function(data){
                _export_sbe_t1t.utils.safelog(data);

                if(data && data['success']){
                    alert(data['message']);
                    run();
                    return true;
                }else{
                    alert(data['message']);
                    run();
                    return false;
                }
            }
        },'json').fail(function(){
            alert("<?php echo lang("Declined Withdrawal Failed");?>");
            return false;
        });
    }
</script>