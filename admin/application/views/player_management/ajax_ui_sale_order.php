<?php
    if(is_array($this->config->item('cryptocurrencies'))){
        $enabled_crypto = true;
    }else{
        $enabled_crypto = false;
    }

    if($this->config->item('enable_cpf_number')){
        $enable_cpf_number = true;
    }else{
        $enable_cpf_number = false;
    }
?>
<div id="deposit_list_panel" data-file-info="ajax_ui_sale_order.php" data-datatable-selector="#deposit-table">
    <div class="form-inline">
        <?php if ($this->permissions->checkPermissions('new_deposit')): ?>
            <a href="/payment_management/newDeposit" class="btn btn-scooter btn-sm btn-new_deposit" target="_blank">
                <i class="fa fa-plus"></i> <span class="hidden-xs"><?=lang('lang.newDeposit') ?></span>
            </a>
        <?php endif; ?>

        <select id="deposit-type" class="form-control input-sm">
            <option value="" selected="selected"><?=lang("All") ?></option>
            <option value="<?= Sale_order::VIEW_STATUS_REQUEST ?>"><?=lang('deposit_list.st.pending'); ?></option>
            <option value="<?= Sale_order::VIEW_STATUS_APPROVED ?>"><?=lang('deposit_list.st.approved'); ?></option>
            <option value="<?= Sale_order::VIEW_STATUS_DECLINED ?>"><?=lang('deposit_list.st.declined'); ?></option>
        </select>

        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="true" />
        <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart" />
        <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd" />
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search'); ?>" />
    </div>
    <hr />
    <div class="clearfix">
        <table id="deposit-table" class="table table-bordered">
            <thead>
                <tr>
                    <th><?=lang('lang.action'); // #1 ?></th>
                    <th><?=lang('lang.status'); // #2 ?></th>
                    <th><?=lang('deposit_list.order_id'); // #3 ?></th>
                    <th><?=lang('system.word38'); // #4 ?></th>
                    <th><?=lang('player.38');// OGP-28145?></th>
                    <?php if ($this->utils->getConfig('enable_split_player_username_and_affiliate')) { ?>
                        <th><?=lang("Affiliate") // #5 ?></th> <!-- 4 -->
                    <?php } ?>
                    <th><?=lang('pay.payment_account_flag'); // #6 ?></th>
                    <th id="default_sort_reqtime"><?=lang('pay.reqtime'); // #7 ?></th>
                    <th><?=lang('Deposit Datetime'); // #8 ?></th>
                    <th><?=lang('pay.spenttime'); // #9 ?></th>
                    <th><?=lang('sys.vu40'); // #10 ?></th>
                    <th class="hidden_aff_th"><?=lang("Affiliate") // #11 ?></th>
                    <th><?=lang('pay.playerlev'); // #12 ?></th>
                    <?php if($this->utils->getConfig('enabled_player_tag_in_deposit')) :?>
                        <th><?=lang("Tag") // #13 ?></th>
                    <?php endif; ?>
                    <?php if($enabled_crypto) :?>
                        <th><?=lang('Received crypto'); // #14 ?></th>
                    <?php endif; ?>
                    <th><?=lang('Deposit Amount'); // #15 ?></th>
                    <?php if($enable_cpf_number) :?>
                        <th><?=lang('financial_account.CPF_number'); // #16 ?></th>
                    <?php endif; ?>
                    <th><?=lang('transaction.transaction.type.3'); // #17 ?></th>
                    <th><?=lang('pay.collection_name'); // #18 ?></th>
                    <th><?=lang('deposit_list.ip'); // #19 ?></th>
                    <th id="default_sort_updatedon"><?=lang('pay.updatedon'); // #20 ?></th>
                    <th><?=lang('cms.timeoutAt'); // #21 ?></th>
                    <th><?=lang('pay.procsson'); // #22 ?></th>
                    <th><?=lang('pay.collection_account_name'); // #23 ?></th>
                    <th><?=lang('con.bnk20'); // #24 ?></th>
                    <th><?=lang('pay.deposit_payment_name'); // #25 ?></th>
                    <th><?=lang('pay.deposit_payment_account_name'); // #26 ?></th>
                    <th><?=lang('pay.deposit_payment_account_number'); // #27 ?></th>
                    <!-- <th><?=lang('pay.deposit_transaction_code'); // #28 by OGP-26797 ?></th> -->
                    <th><?=lang('cms.promotitle'); // #29 by OGP-26798 ?></th>
                    <th><?=lang('Promo Request ID'); // #30 ?></th>
                    <th><?=lang('pay.promobonus'); // #31 ?></th>
                    <th><?=lang('Paybus ID'); // #32 ?></th>
                    <th><?=lang('External ID'); // #33 ?></th>
                    <th><?=lang('Bank Order ID'); // #34 ?></th>
                    <?php if ($this->utils->isEnabledFeature('enable_deposit_datetime')) { ?>
                        <th><?=lang('Deposit Datetime From Player'); // #35 ?></th>
                    <?php } ?>
                    <th><?=lang('Mode of Deposit'); // #36 ?></th>
                    <th style="min-width:300px;"><?=lang('Player Deposit Note'); // #37 ?></th>
                    <th style="min-width:400px;"><?=lang('pay.procssby'); // #38 ?></th>
                    <th style="min-width:400px;"><?=lang('External Note'); // #39 ?></th>
                    <th style="min-width:400px;"><?=lang('Internal Note'); // #40 ?></th>
                    <th style="min-width:600px;"><?=lang('Action Log'); // #41 ?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script type="text/javascript">
    var enable_split_player_username_and_affiliate = '<?=$this->utils->getConfig('enable_split_player_username_and_affiliate')?>';

    $(document).ready( function() {
        if (enable_split_player_username_and_affiliate) {
            rowNum = $(".hidden_aff_th").index();
            $("#deposit-table thead th:eq("+rowNum+")").remove();
        }
    });

    function depositHistory() {
        var hidden_colvis = '';
        <?php if (!empty($this->utils->getConfig('hidden_colvis_for_deposit_list_player'))) : ?>
            var hidden_colvis_arr = JSON.parse("<?= json_encode($this->utils->getConfig('hidden_colvis_for_deposit_list_player')) ?>");
                hidden_colvis = formatHiddenColvisStr(hidden_colvis_arr);
        <?php endif; ?>

        var not_visible_target = '';
        var text_right = '';
        <?php if (!empty($this->utils->getConfig('deposit_list_columnDefs'))) : ?>
            <?php if (!empty($this->utils->getConfig('deposit_list_columnDefs')['not_visible_player_information'])) : ?>
                not_visible_target = JSON.parse("<?= json_encode($this->utils->getConfig('deposit_list_columnDefs')['not_visible_player_information']) ?>");
            <?php endif; ?>
            <?php if (!empty($this->utils->getConfig('deposit_list_columnDefs')['className_text-right_player_information'])) : ?>
                text_right = JSON.parse("<?= json_encode($this->utils->getConfig('deposit_list_columnDefs')['className_text-right_player_information']) ?>");
            <?php endif; ?>
        <?php endif; ?>

        var amtColSummary = 0,
            totalPerPage = 0,
            desc = $("#default_sort_reqtime").index();

        var deposit_type = $('#deposit-type');
        deposit_type.change(function(){
            if(deposit_type.val() == '<?= Sale_order::VIEW_STATUS_DECLINED ?>'){
                desc = $("#default_sort_updatedon").index();
            }
            dataTable.order( [ desc, 'desc' ] );
        }).trigger('submit');

        var dataTable = $('#deposit-table').DataTable({
            dom: "<'row'<'col-md-12'<'pull-right'B><'pull-right progress-container'>l<'dt-information-summary2 text-info pull-left' i>>><'table-responsive't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
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
                    postfixButtons: ['colvisRestore'],
                    className: ['btn-linkwater'],
                    columns: hidden_colvis
                }
            ],
            columnDefs: [
                {
                    sortable: false,
                    targets: [0]
                },
                {
                    visible: false,
                    targets: not_visible_target
                },
                {
                    className: 'text-right',
                    targets: text_right
                },
                <?php if ($this->utils->isEnabledFeature('close_aff_and_agent')): ?>
                    {
                        className: "noVis hidden",
                        targets: [8],
                    },
                <?php endif ?>
            ],
            order: [
                [desc, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: function(data, callback, settings) {
                data.extra_search = [
                    {
                        'name': 'enable_date',
                        'value': '1'
                    },
                    {
                        'name': 'deposit_date_from',
                        'value': $('#deposit_list_panel #dateRangeValueStart').val()
                    },
                    {
                        'name': 'deposit_date_to',
                        'value': $('#deposit_list_panel #dateRangeValueEnd').val()
                    },
                    {
                        'name': 'dwStatus',
                        'value': $('#deposit_list_panel #deposit-type').val()
                    },
                    {
                        'name': 'payment_account_id_all',
                        'value': 'true',
                    }
                ];

                $.post('/api/depositList/' + playerId, data, function(data) {
                    callback(data);
                }, 'json');
            },
        });

        $('#changeable_table #btn-submit').click(function() {
            dataTable.ajax.reload();
        });

        ATTACH_DATATABLE_BAR_LOADER.init('deposit-table');
    }

    function showDetialNotes(saleOrderId, noteType) {
        $.ajax({
            url: '/payment_management/getDepositDetialNotes/' + saleOrderId + '/' + noteType,
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    var title   = 'NO.' + data.secure_id,
                        content = '<div>' + data.noteSubTitle + '</div><br>' +
                                  '<textarea class="form-control" rows="15" readonly style="resize: none;">' + data.formatNotes.trim() + '</textarea>',
                        button  = '<center><button class="btn btn-sm btn-scooter" data-dismiss="modal" aria-label="Close"><?=lang('Close')?></button></center>';

                    confirm_modal(title, content, button);
                } else {
                    alert('<?= lang("Something is wrong, show notes detail failed") ?>');
                }
            },
        });
    }

    function formatHiddenColvisStr(hidden_colvis_arr){
        var format_hidden_colvis_str = "";
        var hidden_array= [];

        $.each(hidden_colvis_arr ,function (k,v) {
            v = v+1;
            format_hidden_colvis_str = ':not(:nth-child('+ v +'))';
            hidden_array.push(format_hidden_colvis_str);
        });

        return hidden_array.join("");;
    }

</script>
