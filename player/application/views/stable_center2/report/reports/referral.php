<!-- ============ Friend Referral Status ================== -->
<div id="ah-referral" role="tabpanel" class="tab-pane active">
    <div id="refer-box" class="report table-responsive">
        <table id="referResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
    </div>
</div>
<script type="text/javascript">
    var referTB;

    function referralFriend(){
        var table_container = $('#referResultTable');

        if(referTB !== undefined){
            referTB.page.len($('#pageLength').val());
            referTB.ajax.reload();
            return false;
        }

        var columns = [];
        columns.push({
            "name": "createdOn",
            "data": "createdOn",
            "title": "<?=lang('player.ufr02');?>",
            "visible": true,
            "orderable": true

        });
        columns.push({
            "name": "username",
            "data": "username",
            "title": "<?=lang('lang.player');?>",
            "visible": true,
            "orderable": false
        });
        // if(!$this->utils->isEnabledFeature('hidden_bet_amount_col_on_the_referral_report'))
        // columns.push({
        //     "name": "betting_amount",
        //     "data": "betting_amount",
        //     "title": "lang('player.ui31');",
        //     "visible": true,
        //     "orderable": false
        // });

        <?php if(!$this->utils->isEnabledFeature('hidden_bonus_col_on_the_referral_report')): ?>
        columns.push({
            "name": "amount",
            "data": "amount",
            "title": "<?=lang('lang.bonus'); ?>",
            "visible": true,
            "orderable": false
        });
        <?php endif ?>
        <?php if($this->utils->getConfig('enabled_friend_referral_promoapp_list')): ?>
        columns.push({
            "name": "status",
            "data": "status",
            "title": "<?=lang('lang.status'); ?>",
            "visible": true,
            "orderable": false
        });
        <?php endif ?>
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

        referTB = table_container.DataTable($.extend({}, dataTable_options, {
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            columnDefs: [ {  // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets:   -1
            }],
            order: [[0, 'desc']],
            ajax: {
                url: '/api/getReferralFriend',
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