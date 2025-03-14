<!-- ============ creditmode List ================== -->
<div id="ah-credit_mode" role="tabpanel" class="tab-pane">
    <div id="creditmode-box" class="report table-responsive">
        <table id="creditmodeResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
    </div>
</div>
<script type="text/javascript">
    var creditmodeTB;

    function creditmodeRequestHistory() {
        var table_container = $('#creditmodeResultTable');

        if(creditmodeTB !== undefined){
            creditmodeTB.page.len($('#pageLength').val());
            creditmodeTB.ajax.reload();
            return false;
        }

        var columns = [];
        columns.push({
            "title": "<?=lang('Credit Time')?>",
            "visible": true,
            "orderable": true,
        });
        columns.push({
            "title": "<?=lang('By')?>",
            "visible": true,
            "orderable": false,
        });
        
        columns.push({
            "title": "<?=lang('Amount')?>",
            "visible": true,
            "orderable": false,
        });
        columns.push({
            "title": "<?=lang('Wallet')?>",
            "visible": true,
            "orderable": false,
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

        creditmodeTB = table_container.DataTable($.extend({}, dataTable_options, {
            "autoWidth": false,
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            columnDefs: [ {  // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets:   -1
            }],
            order: [[0, 'desc']],
            ajax: {
                url: '/api/creditModeWalletTransactions',
                type: 'post',
                data: function ( d ) {
                    d.extra_search = [
                        {
                            'name':'dateRangeValueStart',
                            'value': $('#sdate').val(),
                        },
                        {
                            'name':'dateRangeValueEnd',
                            'value':  $('#edate').val(),
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