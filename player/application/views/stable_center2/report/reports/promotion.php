<!-- ============ Promo History ================== -->
<div id="ah-promotion" role="tabpanel" class="tab-pane active">
    <div id="promo-box" class="report table-responsive">
        <table id="promoResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
    </div>
</div>
<script type="text/javascript">
    var player_promo_status = JSON.parse('<?=json_encode($player_promo_status)?>');

    var promoTB;

    function promoHistory() {
        var table_container = $('#promoResultTable');

        if(promoTB !== undefined){
            promoTB.page.len($('#pageLength').val());
            promoTB.ajax.reload();
            return false;
        }

        var columns = [];
        columns.push({
            "name": "dateProcessed",
            "title": "<?=lang('pay.time')?>",
            "visible": true,
            "orderable": true,
        });
        columns.push({
            "name": "promoId",
            "title": "<?=lang('column.id')?>",
            "visible": true,
            "orderable": false
        });
        columns.push({
            "name": "promoName",
            "title": "<?=lang('lang.promo')?>",
            "visible": true,
            "orderable": false,
        });
        columns.push({
            "name": "transactionStatus",
            "title": "<?=lang('Status')?>",
            "visible": true,
            "orderable": false,
        });
        columns.push({
            "name": "bonusAmount",
            "title": "<?=lang('Bonus')?>",
            "visible": true,
            "orderable": false
        });
        <?php if($this->utils->getConfig('display_hide_date_on_promo_history_of_player_center')) :?>
            columns.push({
                "name": "hideDate",
                "title": "<?=lang('Expire Date');?>",
                "visible": true,
                "orderable": false
            });
        <?php endif; ?>
        <?php if ($this->utils->isEnabledFeature('show_player_promo_report_note')) : ?>
            columns.push({
                "name": "promonotes",
                "title": "<?=lang('Notes')?>",
                "visible": true,
                "orderable": false
            });
        <?php endif; ?>
        columns.push({ // for the responsive extention to display control row button
            "title": "&nbsp",
            "visible": true,
            "orderable": false,
            "render": function(){
                return '&nbsp';
            },
            "responsivePriority": 1
        });

        promoTB = table_container.DataTable($.extend({}, dataTable_options, {
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            columnDefs: [ {  // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets:   -1
            }],
            order: [[0, 'desc']],
            ajax: {
                url: '/api/getPlayerPromoHistoryWLimitById',
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
</script>