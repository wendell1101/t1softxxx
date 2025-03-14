<!-- ============ Cashback ================== -->
<div id="ah-rebate" role="tabpanel" class="tab-pane active">
    <div id="rebate-box" class="report table-responsive">
        <table id="rebateResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
    </div>
</div>
<script type="text/javascript">
    var rebateTB;

    function rebateHistory(){
        var table_container = $('#rebateResultTable');

        if(rebateTB !== undefined){
            rebateTB.page.len($('#pageLength').val());
            rebateTB.ajax.reload();
            return false;
        }

        var columns = [];
        columns.push({
            "name": "receivedOn",
            "title": "<?=lang('pay.time');?>",
            "visible": true,
            "orderable": true
        });
        columns.push({
            "name": "amount",
            "title": "<?=lang('pay.amt');?>",
            "visible": true,
            "orderable": false
        });
        <?php if ($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) : ?>
        columns.push({
            "name": "cashback_type",
            "title": "<?=lang('Cashback Type');?>",
            "visible": true,
            "orderable": false
        });
        <?php endif; ?>
        <?php if ($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) : ?>
        columns.push({
            "name": "invited_player_id",
            "title": "<?=lang('Referred Player for Cashback');?>",
            "visible": true,
            "orderable": false
        });
        <?php endif; ?>
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

        rebateTB = table_container.DataTable($.extend({}, dataTable_options, {
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            columnDefs: [{  // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets: -1
            }],
            order: [[0, 'desc']],
            ajax: {
                url: '/api/getRebateTransaction',
                type: 'post',
                data: function(d){
                    d.extra_search = [
                        {
                            'name': 'dateRangeValueStart',
                            'value': $('#sdate').val(),
                        },
                        {
                            'name': 'dateRangeValueEnd',
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
</script>