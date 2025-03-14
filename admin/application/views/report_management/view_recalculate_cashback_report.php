<form class="form-horizontal" id="search-form" method="get" role="form">
    <div class="panel panel-primary hidden">

        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-search"></i> <?php echo lang("lang.search"); ?>
                <span class="pull-right">
                    <a data-toggle="collapse" href="#collapseRecalculateCashbackReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
                </span>
            </h4>
        </div>

        <div id="collapseRecalculateCashbackReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
            <div class="panel-body">
                <div class="row">
                    <!-- Date -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Date'); ?></label>
                        <input id="search_cashback_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off">
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>">
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>">
                    </div>
                    <div class="col-md-3 col-lg-3 hide">
                        <label class="control-label"><?php echo lang('Enabled date'); ?></label>
                        <input type="checkbox" data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>"  name="enable_date" id="enable_date" data-size='mini' value='true' <?php echo $conditions['enable_date'] ? 'checked="checked"' : ''; ?>>
                    </div>
                    <!-- Player Username -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Player Username'); ?></label>
                        <input type="text" name="by_username" id="by_username" class="form-control input-sm" placeholder='<?php echo lang('Enter Username'); ?>'
                        value="<?php echo $conditions['by_username']; ?>"/>
                    </div>
                    <!-- Player Level -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('VIP Level'); ?></label>
                        <?php echo form_dropdown('by_player_level', $vipgrouplist, $conditions['by_player_level'], 'id="by_player_level" class="form-control input-sm"') ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Cashback Amount <='); ?></label>
                        <input type="text" name="by_amount_less_than" id="by_amount_less_than" value="<?=$conditions['by_amount_less_than']?>" class="form-control input-sm number_only" placeholder='<?php echo lang('Enter Amount'); ?>'/>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Cashback Amount >='); ?></label>
                        <input type="text" name="by_amount_greater_than" id="by_amount_greater_than" value="<?=$conditions['by_amount_greater_than']?>" class="form-control input-sm number_only" placeholder='<?php echo lang('Enter Amount'); ?>'/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 p-t-15 text-right">
                        <input class="btn btn-sm btn_payall m-r-30 <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-danger'?>" type="button" id="syncRecalculateCashbackReport" value="<?php echo lang('Sync Report'); ?>"/>
                        <input type="button" id="btnResetFields" value="<?php echo lang('Reset'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-danger'?>">
                        <input class="btn btn-sm btn-primary" type="submit" value="<?php echo lang('lang.search'); ?>" />
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt"><i class="icon-bullhorn"></i> <?php echo lang('Recalculate Cashback Report'); ?></h4>
    </div>

    <!-- result table -->
    <div class="panel-body">
        <div id="logList" class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="report_table" style="width:100%;">
                <thead>
                    <tr>
                        <th><?php echo lang('Date'); ?></th>
                        <th><?php echo lang('Player Username'); ?></th>
                        <th><?php echo lang('a_header.affiliate'); ?></th>
                        <th><?php echo lang('VIP Level'); ?></th>
                        <th><?php echo lang('Amount'); ?></th>
                        <th><?php echo lang('Bet Amount'); ?></th>
                        <th><?php echo lang('Original Bet Amount'); ?></th>
                        <th><?php echo lang('Paid'); ?></th>
                        <th><?php echo lang('Game Platform'); ?></th>
                        <th><?php echo lang('Game Type'); ?></th>
                        <th><?php echo lang('Game'); ?></th>
                        <th><?php echo lang('Updated at'); ?></th>
                        <th><?php echo lang('Paid date'); ?></th>
                        <th><?php echo lang('Paid amount'); ?></th>
                        <th><?php echo lang('Withdraw Condition amount'); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th colspan="2"><?= lang('Subtotal') ?></th>
                        <td colspan="2"></td>
                        <th class="total amount">&mdash;</td>
                   </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <!--end of result table -->

<!--    <div class="panel-footer"></div>-->
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
    <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
    </form>
<?php }?>


<script type="text/template" id="tpl-cashback-detail-row-list">
    <div class="container-fluid cashback-detail-row-container">
        <div class="row amount-row"> <!-- common_cashback_multiple_range_rules.rule_id_246.resultsByTier -->
            <div class="col-sm-4 text-align-right">
                <?=lang('Amount')?>
            </div>

            <div class="col-sm-5">
                ${amount}
            </div>
        </div>
        <div class="row percentage-row">
            <div class="col-sm-4 text-align-right">
                <?=lang('Percentage')?>
            </div>

            <div class="col-sm-5">
                ${percentage}
            </div>
        </div>
        <div class="row deduction-bet-row">
            <div class="col-sm-4 text-align-right">
                <?=lang('Deduction Bet')?>
            </div>

            <div class="col-sm-5">
                ${deduction}
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="tpl-bet-detail-row-list">
    <div class="container-fluid bet-detail-row-container">
        <div class="row game_description-row">
            <div class="col-sm-4 text-align-right">
                <?=lang('Game Name')?> <!-- game_description_id -->
            </div>

            <div class="col-sm-5">
                ${game_description_of_platform}
                <!-- KY棋牌 > Poker > Golden Flower Beginner’s Room -->
                <!-- game_description_id -->
            </div>
        </div>
        <div class="row bet-amount">
            <div class="col-sm-4 text-align-right">
                <?=lang('Amount')?>
            </div>

            <div class="col-sm-5">
                ${betting_total} <!-- betting_total -->
            </div>
        </div>
    </div>
</script>

<!-- cashbackAmount Modal Start -->
<div class="modal fade" id="cashbackAmountDetail" tabindex="-1" role="dialog" aria-labelledby="cashbackAmountDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="cashbackAmountModalLabel"><?=lang('Recalculate Cashback Amount Detail')?></h4>
            </div>
            <div class="modal-body cashbackAmountModalBody">

                <div class="container-fluid container-loader">
                    <div class="row" style="background-color: #eee;">
                        <div class="col-md-offset-4 col-md-4">
                            <div class="loader"></div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <div class="row cashback-detail-row">
                        <div class="col-sm-12 text-align-center">
                            <b>
                                <?=lang('Recalculate Cashback Detail')?> <!-- common_cashback_multiple_range_rules -->
                            </b>
                        </div>
                    </div>
                    <div class="container-fluid cashback-detail-row-container">
                        <div class="row amount-row"> <!-- common_cashback_multiple_range_rules.rule_id_246.resultsByTier -->
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Amount')?>
                            </div>

                            <div class="col-sm-5">
                                5
                            </div>
                        </div>
                        <div class="row percentage-row">
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Percentage')?>
                            </div>

                            <div class="col-sm-5">
                                0.590
                            </div>
                        </div>
                        <div class="row deduction-bet-row">
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Deduction Bet')?>
                            </div>

                            <div class="col-sm-5">
                                714.285
                            </div>
                        </div>
                    </div> <!-- EOF .cashback-detail-row-container -->
                </div>

                <div class="container-fluid">
                    <div class="row bet-detail-row">
                        <div class="col-sm-12 text-align-center">
                            <b>
                                <?=lang('Bet Detail')?> <!-- total_player_game_hour -->
                            </b>
                        </div>
                    </div>

                    <div class="container-fluid bet-detail-row-container">
                        <div class="row game_description-row">
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Game Name')?> <!-- game_description_id -->
                            </div>

                            <div class="col-sm-5">
                                KY棋牌 > Poker > Golden Flower Beginner’s Room <!-- game_description_id -->
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Amount')?>
                            </div>

                            <div class="col-sm-5">
                                5 <!-- betting_total -->
                            </div>
                        </div>
                    </div> <!-- EOF .bet-detail-row-container -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=lang('Confirm')?></button>
            </div>
        </div>
    </div>
</div>
<!-- cashbackAmount Modal End -->

<script type="text/javascript">
    function renderSelectAll(){
        var selectedAll=$(".chk_row").length==$(".chk_row:checked").length;

        $(".select_all").prop('checked', selectedAll);
    }

    function changeSelectRow(){

        renderSelectAll();

    }

    $(document).ready(function(){
        var d = new Date();
        var month = d.getMonth()+1;
        var day = d.getDate();

        var datetoday = d.getFullYear() + '-' +
        (month<10 ? '0' : '') + month + '-' +
        (day<10 ? '0' : '') + day;

        $("#syncRecalculateCashbackReport").click(function(){
            //confirm
            if(confirm("<?php echo lang('Do you want sync period recalculate cashback report?'); ?>")){
                $("#search-form").attr("method", "POST")
                    .attr("action", "<?php echo site_url('/vipsetting_management/sync_recalculate_cashback_report'); ?>")
                    .submit();
            }
        });

        $('#btnResetFields').click(function() {
            $("#by_date_from").val(datetoday);
            $("#by_date_from").val(datetoday);
            $("#enable_date").prop('checked', true);
            $("#by_username").val("");
            $("#by_player_level").val("");
            $("#by_amount_less_than").val("");
            $("#by_amount_greater_than").val("");
        });

        $(".select_all").change(function(){
            //
            $(".chk_row").prop("checked", $(this).prop("checked"));
        });

        $('#search-form input[type="text"], #search-form input[type="number"]').keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').trigger('submit');
            }
        });

        var view_recalculate_detail = true;
        $('body').on('click', 'div.btn[data-appoint_id]', function(e){
            reportManagement.clicked_cashbackAmountDetail(e, view_recalculate_detail);
        });

        $('#report_table').DataTable({
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            autoWidth: false,
            searching: false,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
                <?php if ($export_report_permission) {?>
                ,{
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        var d = {'extra_search': $('#search-form').serializeArray(), 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};
                        <?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/recalculate_cashback_report'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                        <?php }?>
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                { sortable: false, targets: [ 1 ] },
                { className: 'text-right', targets: [ 3,4,5,12, 13, 14 ] }
            ],
            "order": [ 0, 'desc' ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/recalculate_cashback_report", data, function(data) {
                    callback(data);
                    // $('#total_amount').text(data.summary[0].total_amount);
                    if ( $('#report_table').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
                        $('#report_table').DataTable().buttons().disable();
                    }
                    else {
                        $('#report_table').DataTable().buttons().enable();
                    }
                },'json');
            },
            // OGP-22311: build subtotal for columns 'amount' and 'paid' in footer
            footerCallback: function (tfoot, data, start, end, display) {
                var api = this.api();
                var sum = { amount: 0, paid: 0 };
                for (var i in data) {
                    var r = data[i];
                    // column 4: amount, column 13: paid amount
                    var r_amount = numeral(r[4]).value();//, r_paid = numeral(r[13]).value();

                    var amount_cell = r[4];
                    if( ! $.isNumeric(amount_cell) ){ // for parse contains the thousands separator. e.q. "2,132.34"
                        r_amount = numeral(amount_cell).value();
                    }
                    if( $(amount_cell).text() != '') { // for parse contains html tags, '<div class="btn btn-xs btn-toolbar" data-appoint_id="566596"><span class="glyphicon glyphicon-list-alt"></span></div>&nbsp;<i class="text-success">140.40</i>'
                        r_amount = numeral($(amount_cell).text()).value();
                    }
                    // console.log('amount', r_amount); // , 'paid', r_paid);
                    sum.amount += r_amount;
                }
                var tfooter = $(tfoot);
                $(tfooter).find('.total.amount' ).text(numeral(sum.amount   ).format('0.000'));
            }
        });
    });

</script>

<style>
.text-align-center {
    text-align:center;
}
.text-align-right {
    text-align:right;
}

.cashbackAmountModalBody>.container-fluid .row:nth-child(even){
  background-color: #efefef;
}


.bet-detail-row-container,.cashback-detail-row-container {
    border-style: solid;
    border-width: 1px;
    border-color: #ccc;
}


/* LOADER 1 */

.cashbackAmountModalBody .loader {
  margin: 4em auto;
  font-size: 24px;
  width: 1em;
  height: 1em;
  border-radius: 50%;
  position: relative;
  text-indent: -9999em;
  -webkit-animation: load3 1.1s infinite ease;
  animation: load3 1.1s infinite ease;
}
@-webkit-keyframes load3 {
  0%,
  100% {
    box-shadow: 0em -2.6em 0em 0em #ffffff, 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.5), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.7);
  }
  12.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.7), 1.8em -1.8em 0 0em #ffffff, 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.5);
  }
  25% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.5), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.7), 2.5em 0em 0 0em #ffffff, 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  37.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.5), 2.5em 0em 0 0em rgba(255, 255, 255, 0.7), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  50% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.5), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.7), 0em 2.5em 0 0em #ffffff, -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  62.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.5), 0em 2.5em 0 0em rgba(255, 255, 255, 0.7), -1.8em 1.8em 0 0em #ffffff, -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  75% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.5), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.7), -2.6em 0em 0 0em #ffffff, -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  87.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.5), -2.6em 0em 0 0em rgba(255, 255, 255, 0.7), -1.8em -1.8em 0 0em #ffffff;
  }
}
@keyframes load3 {
  0%,
  100% {
    box-shadow: 0em -2.6em 0em 0em #ffffff, 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.5), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.7);
  }
  12.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.7), 1.8em -1.8em 0 0em #ffffff, 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.5);
  }
  25% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.5), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.7), 2.5em 0em 0 0em #ffffff, 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  37.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.5), 2.5em 0em 0 0em rgba(255, 255, 255, 0.7), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  50% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.5), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.7), 0em 2.5em 0 0em #ffffff, -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  62.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.5), 0em 2.5em 0 0em rgba(255, 255, 255, 0.7), -1.8em 1.8em 0 0em #ffffff, -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  75% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.5), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.7), -2.6em 0em 0 0em #ffffff, -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  87.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.5), -2.6em 0em 0 0em rgba(255, 255, 255, 0.7), -1.8em -1.8em 0 0em #ffffff;
  }
}

/* EOF LOADER 1 */

</style>