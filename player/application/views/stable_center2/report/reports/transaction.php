<!-- ============ Promo History ================== -->
<div id="ah-transaction" role="tabpanel" class="tab-pane active">
    <div id="transaction-box" class="report table-responsive">
        <table id="transactionResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
    </div>
</div>
<script type="text/javascript">
    var transactionTB;

    function transactionHistory() {
        var table_container = $('#transactionResultTable');

        if (transactionTB !== undefined) {
            transactionTB.page.len($('#pageLength').val());
            transactionTB.ajax.reload();
            return false;
        }

        var columns = [];
        columns.push({
            "name": "dateProcessed",
            "title": "<?= lang('player.ut01') ?>",
            "visible": true,
            "orderable": true,
            "data": 0,
        });
        columns.push({
            "name": "transaction_type",
            "title": "<?= lang('player.ut02') ?>",
            "visible": true,
            "orderable": false,
            "data": 1,
        });
        columns.push({
            "name": "amount",
            "title": "<?= lang('Transaction Amount') ?>",
            "visible": true,
            "orderable": false,
            "data": 2,
        });
        columns.push({
            "name": "before_balance",
            "title": "<?= lang('player.ut06') ?>",
            "visible": true,
            "orderable": false,
            "data": 3,
        });
        columns.push({
            "name": "after_balance",
            "title": "<?= lang('player.ut07') ?>",
            "visible": true,
            "orderable": false,
            "data": 4,
        });
        columns.push({
            "name": "promoTypeName",
            "title": "<?= lang('cms.promoCat') ?>",
            "visible": true,
            "orderable": false,
            "data": 6,
        });
        columns.push({
            "name": "secure_id",
            "title": "<?= lang('Request ID') ?>",
            "visible": true,
            "orderable": false,
            "data": 8,
        });

        transactionTB = table_container.DataTable($.extend({}, dataTable_options, {
            "responsive": false,
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            columnDefs: [{ // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets: -1
            }],
            order: [
                [0, 'desc']
            ],
            ajax: {
                url: '/api/playerCenterTransactionHistory',
                type: 'post',
                data: function(d) {
                    d.extra_search = [{
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
                ,
                fnDrawCallback: function() {
                    var wrapper = $(this).parents('.dataTables_wrapper');
                    var status = $(wrapper).find('.dt-row:last');
                    if ($(this).find('tbody td.dataTables_empty').length > 0) {
                        $(status).hide();
                    } else {
                        $(status).show();
                    }
                }
            <?php endif; ?>
        }));
    }
</script>