<!-- ============ Shop History ================== -->
<!-- ============ OGP-25193 ================== -->
<div id="ah-shop-history" role="tabpanel" class="tab-pane active">
    <div id="shop-history-box" class="report table-responsive">
        <table id="shopHistoryResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
    </div>
</div>
<script type="text/javascript">
    var shopHistoryTB;

    function shopHistory(){
        var table_container = $('#shopHistoryResultTable');

        if(shopHistoryTB !== undefined){
            shopHistoryTB.page.len($('#pageLength').val());
            shopHistoryTB.ajax.reload();
            return false;
        }

        var columns = [];
        columns.push({
            "name": "request_time",
            "title": "<?=lang('pay.time');?>",
            "visible": true,
            "orderable": true
        });
        columns.push({
            "name": "title",
            "title": "<?=lang('Item Title');?>",
            "visible": true,
            "orderable": true
        });
        columns.push({
            "name": "required_points",
            "title": "<?=lang('pay.reqrpoint');?>",
            "visible": true,
            "orderable": false
        });
        columns.push({
            "name": "request_status",
            "title": "<?=lang('lang.display_status');?>",
            "visible": true,
            "orderable": true
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

        shopHistoryTB = table_container.DataTable($.extend({}, dataTable_options, {
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            columnDefs: [{  // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets: -1
            }],
            order: [[0, 'desc']],
            ajax: {
                url: '/api/shopHistory',
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