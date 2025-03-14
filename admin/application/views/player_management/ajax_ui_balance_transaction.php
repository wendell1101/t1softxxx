<div id="transaction_panel" data-file-info="ajax_ui_transaction.php" data-datatable-selector="#transaction-table">
    <div class="form-group">
        <fieldset>
            <div class="form-group">
                <legend>
                    <label class="control-label"><?=lang('player.ut02')?></label>
                </legend>
                <label class="checkbox-inline"><input id="check_all_transaction" type="checkbox"  value="" checked><?=lang('Select All')?></label>
                <label class="checkbox-inline"><input type="checkbox" name="transaction_type[]" value="<?=Transactions::DEPOSIT?>" checked><?=lang('transaction.transaction.type.' . Transactions::DEPOSIT)?></label>
                <label class="checkbox-inline"><input type="checkbox" name="transaction_type[]" value="<?=Transactions::WITHDRAWAL?>" checked><?=lang('transaction.transaction.type.' . Transactions::WITHDRAWAL)?></label>
                <label class="checkbox-inline"><input type="checkbox" name="transaction_type[]" value="<?=Transactions::MANUAL_ADD_BALANCE?>" checked><?=lang('transaction.transaction.type.' . Transactions::MANUAL_ADD_BALANCE)?></label>
                <label class="checkbox-inline"><input type="checkbox" name="transaction_type[]" value="<?=Transactions::MANUAL_SUBTRACT_BALANCE?>" checked><?=lang('transaction.transaction.type.' . Transactions::MANUAL_SUBTRACT_BALANCE)?></label>
            </div>
        </fieldset>
    </div>
    <div class="form-inline">
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="true" autocomplete="off" />
        <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart"/>
        <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd"/>
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search');?>"/>
    </div>
    <hr/>
    <div class="clearfix">
        <table id="transaction-table" class="table table-bordered">
            <thead>
                <tr>
                    <th><?=lang('pay.reqtime');?></th> <!-- #1 Requested Time -->
                    <th><?=lang('pay.procsson');?></th> <!-- #2 Processed On -->
                    <th><?=lang('player.ut02');?></th> <!-- #3 Transaction Type -->
                    <th><?=lang('pay.from.procssby');?></th> <!--  #4 From (Processed By) -->
                    <th data-col="amount"><?=lang('pay.amt');?></th> <!-- #5 Amount -->
                    <th><?=lang('player.ut06');?></th> <!-- #6 Before Balance -->
                    <th><?=lang('player.ut07');?></th> <!-- #7 After Balance -->
                    <th><?=lang('pay.payment_account_flag');?></th> <!-- #8 Bank/Payment Type -->
                    <th><?=lang('Request ID');?></th> <!-- #9 Request ID -->
                    <th><?=lang('External ID');?></th> <!-- #10 External ID (in case player deposit over AMB) -->
                    <th><?=lang('Internal Note');?></th> <!-- #11 Internal Note -->
                </tr>
            </thead>
            <tfoot>
                <tr><?php
                        /// TypeError: Cannot set property ‘nTf’ of undefined
                        // Make sure that number of th elements in the table footer matches number of th elements in the table header.
                    ?>
                    <?php if ($this->utils->isEnabledFeature('enable_tag_column_on_transaction')& false): ?>
                        <th colspan="3"></th>
                    <?php else: ?>
                        <th colspan="3"></th>
                    <?php endif; ?>

                    <th><?=lang('Total'); ?></th>
                    <th id="amout_sum"></th>

                    <?php if ($this->utils->isEnabledFeature('enable_adjustment_category')& false): ?>
                        <th colspan="6"></th>
                    <?php else: ?>
                        <th colspan="6"></th>
                    <?php endif; ?>
                </tr>
            </tfoot>
       </table>
    </div>
</div>

<style type="text/css">
    #transaction_panel .checkbox-inline {
        margin: 0 10px 0 0;
    }
</style>
<script type="text/javascript">
    $(document).ready(function() {
        initDateInputComboExtendAttr($('.dateInput'));
        $("#check_all_transaction").click(function () {
            $('#transaction_panel input:checkbox').not(this).prop('checked', this.checked);
        });

        $("#transaction_panel input:checkbox").click(function () {
            var numberOfCheckedTransactionType = $('input[name^=transaction_type]:checked').length;
            var totalNumberOfTransactionType = $('input[name^=transaction_type]').length;
            if(numberOfCheckedTransactionType == totalNumberOfTransactionType){
                $("#check_all_transaction").prop('checked', true);
            } else {
                $("#check_all_transaction").prop('checked', false);
            }
        });
    });

    function balanceTransactionHistory(player_id) {

        <?php $col_config = $this->utils->getConfig('balance_transactions_columnDefs'); ?>
            var hidden_cols = [];
        <?php if(!empty($col_config['not_visible_player_information'])) : ?>
            var not_visible_cols = JSON.parse("<?= json_encode($col_config['not_visible_player_information']) ?>" ) ;
        <?php else: ?>
            var not_visible_cols = [];
        <?php endif; ?>

        <?php if(!empty($col_config['className_text-right_player_information'])) : ?>
            var text_right_cols = JSON.parse("<?= json_encode($col_config['className_text-right_player_information']) ?>" ) ;
        <?php else: ?>
            var text_right_cols = [4, 5, 6, 8];
        <?php endif; ?>


        var dataTable = $('#transaction-table').DataTable({
            dom: "<'row'<'col-md-12'<'pull-right'B><'pull-right progress-container'>l<'dt-information-summary2 text-info pull-left' i>>><'table-responsive't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
            searching: false,
            responsive: false,
            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            buttons: [{
                extend: 'colvis',
                postfixButtons: ['colvisRestore'],
                className: ['btn-linkwater']
            }],
            columnDefs: [
                {
                    className: 'text-right',
                    targets: text_right_cols
                },
                {
                    visible: false,
                    targets: not_visible_cols
                }
            ],
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                $transaction_type = [];
                var check_all_transaction = $('#check_all_transaction:checked').length;
                $('input[name^=transaction_type]:checked').each(function(){
                    $transaction_type.push(parseInt($(this).val()));
                });

                if(check_all_transaction > 0){
                    $transaction_type = [];
                    // Not all, trace to "Transaction Type in player's info page via transactionTypeFilter"
                    $('#changeable_table input[name^=transaction_type]').each(function(){
                        $transaction_type.push(parseInt($(this).val()));
                    });
                }
                data.extra_search = [{
                    'name': 'dateRangeValueStart',
                    'value': $('#dateRangeValueStart').val()
                },
                {
                    'name': 'dateRangeValueEnd',
                    'value': $('#dateRangeValueEnd').val()
                },
                {
                    'name': 'transactionTypeFilter',
                    'value': $transaction_type.toString()
                },
                {
                    'name': 'is_player_transaction_history',
                    'value': 'TRUE'
                }];


                var numberOfCheckedTransactionType = $('input[name^=transaction_type]:checked').length;
                if(numberOfCheckedTransactionType > 0){
                    $.post('/api/balanceTransactionHistory/' + player_id, data, function (data) {
                        callback(data);
                    }, 'json');
                } else {
                    alert("<?=lang('Choose Transaction type')?>");
                }
            },
            footerCallback: function (hrow, data, start, end, display) {
                var amount_col = $('th[data-col="amount"]').index();

                var sum = 0;
                for (var i in data) {
                    var row = data[i];
                    var amount_text = row[amount_col].replace(/,|<[^>]+>/g, '');
                    var amount = parseFloat(amount_text);
                    sum += amount;
                }

                $('#amout_sum').text(
                    numeral(sum).format('11,111.23')
                );
            }
        });

        var dateInput = $('#changeable_table #reportrange.dateInput');
        var isTime = dateInput.data('time');

        // -- Use reset to current day upon cancel/reset in daterange instead of emptying the value
        dateInput.on('cancel.daterangepicker', function(ev, picker) {
            // -- if start date was empty, add a default one
            if($.trim($(dateInput.data('start')).val()) == ''){
                var startEl = $(dateInput.data('start'));
                    start = startEl.val();
                    start = start ? moment(start, 'YYYY-MM-DD HH:mm:ss') : moment().startOf('day');
                    startEl.val(isTime ? start.format('YYYY-MM-DD HH:mm:ss') : start.startOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setStartDate(start);
            }

            // -- if end date was empty, add a default one
            if($.trim($(dateInput.data('end')).val()) == ''){
                var endEl = $(dateInput.data('end'));
                    end = endEl.val();
                    end = end ? moment(end, 'YYYY-MM-DD HH:mm:ss') : moment().endOf('day');
                    endEl.val(isTime ? end.format('YYYY-MM-DD HH:mm:ss') : end.endOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setEndDate(end);
            }

            dateInput.val($(dateInput.data('start')).val() + ' to ' + $(dateInput.data('end')).val());
        });

        $('#changeable_table #reportrange.dateInput').each( function() {
            $(this).keypress(function(e){
                e.preventDefault();
                return;
            });
        });

        $('#changeable_table #btn-submit').click(function () {
            // -- Check if date is empty
            if($.trim($('#changeable_table #reportrange.dateInput').val()) == ''){
                alert('<?=lang("require_date_range_label")?>');
                return;
            }

            dataTable.ajax.reload();
        });

        ATTACH_DATATABLE_BAR_LOADER.init('transaction-table');
    }
</script>
