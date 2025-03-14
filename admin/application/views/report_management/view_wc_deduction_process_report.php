<form class="form-horizontal" id="search-form" method="get" role="form">
    <div class="panel panel-primary hidden">

        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-search"></i> <?php echo lang("lang.search"); ?>
                <span class="pull-right">
                    <a data-toggle="collapse" href="#collapseWcDeductionProcessReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
                </span>
            </h4>
        </div>

        <div id="collapseWcDeductionProcessReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
            <div class="panel-body">
                <div class="row">
                    <!-- Date -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Date'); ?></label>
                        <input id="search_wc_deduction_process_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off">
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>">
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>">
                    </div>
                    <div class="col-md-3 col-lg-3 hide">
                        <label class="control-label"><?php echo lang('Enabled date'); ?></label>
                        <input type="checkbox" data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>"  name="enable_date" id="enable_date" data-size='mini' value='true' <?php echo $conditions['enable_date'] ? 'checked="checked"' : ''; ?>>
                    </div>
                    <!-- Player Username -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('wc_dudection_process.username'); ?></label>
                        <input type="text" name="by_username" id="by_username" class="form-control input-sm" placeholder='<?php echo lang('Enter Username'); ?>'
                        value="<?php echo $conditions['by_username']; ?>"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 p-t-15 text-right">
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
        <h4 class="panel-title custom-pt"><i class="icon-bullhorn"></i> <?php echo lang('wc_dudection_process.title'); ?></h4>
    </div>

    <!-- result table -->
    <div class="panel-body">
        <div id="logList" class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="report_table" style="width:100%;">
                <thead>
                    <tr>
                        <th><?php echo lang('wc_dudection_process.cashback_date'); ?></th>
                        <th><?php echo lang('wc_dudection_process.username'); ?></th>
                        <th><?php echo lang('wc_dudection_process.wc_id'); ?></th>
                        <th><?php echo lang('cms.promoname'); ?></th>
                        <th><?php echo lang('pay.startedAt'); ?></th>
                        <th><?php echo lang('pay.withdrawalAmountCondition'); ?></th>
                        <th><?php echo lang('wc_dudection_process.before_deduct_amount'); ?></th>
                        <th><?php echo lang('wc_dudection_process.after_deduct_amount'); ?></th>
                        <th><?php echo lang('wc_dudection_process.deduct_amount'); ?></th>
                        <th><?php echo lang('Game Platform'); ?></th>
                        <th><?php echo lang('Game Type'); ?></th>
                        <th><?php echo lang('wc_dudection_process.game'); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th colspan="3"><?= lang('wc_dudection_process.total_deduct_amount') ?></th>
                        <td colspan="5"></td>
                        <th class="total deduct_amount">&mdash;</td>
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


<script type="text/javascript">
    $(document).ready(function(){
        var d = new Date();
        var month = d.getMonth()+1;
        var day = d.getDate();

        var datetoday = d.getFullYear() + '-' +
        (month<10 ? '0' : '') + month + '-' +
        (day<10 ? '0' : '') + day;

        $('#btnResetFields').click(function() {
            $("#by_date_from").val(datetoday);
            $("#by_date_to").val(datetoday);
            $("#enable_date").prop('checked', true);
            $("#by_username").val("");
            $("#search_wc_deduction_process_date").val($("#by_date_from").val() +" to "+ $("#by_date_from").val());
        });

        $('#search-form input[type="text"]').keypress(function (e) {
           if (e.which == 13) {
               $('#search-form').trigger('submit');
           }
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
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/withdraw_condition_deduction_report'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                        <?php }?>
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                { sortable: false, targets: [ 1 ] }
            ],
            "order": [ 0, 'desc' ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/wc_deduction_process_report", data, function(data) {
                    callback(data);

                    if ( $('#report_table').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
                        $('#report_table').DataTable().buttons().disable();
                    }
                    else {
                        $('#report_table').DataTable().buttons().enable();
                    }
                },'json');
            },
            // OGP-25302: build subtotal for columns 'deduct amount' in footer
            footerCallback: function (tfoot, data, start, end, display) {
                var api = this.api();
                var sum = { amount: 0, paid: 0 };
                for (var i in data) {
                    var r = data[i];
                    var r_amount = numeral(r[8]).value(); // column 8: deduct amount
                    var amount_cell = r[8];

                    if(!$.isNumeric(amount_cell)){ // for parse contains the thousands separator. e.q. "2,132.34"
                        r_amount = numeral(amount_cell).value();
                    }
                    if($(amount_cell).text() != ''){ // for parse contains html tags, '<div class="btn btn-xs btn-toolbar" data-appoint_id="566596"><span class="glyphicon glyphicon-list-alt"></span></div>&nbsp;<i class="text-success">140.40</i>'
                        r_amount = numeral($(amount_cell).text()).value();
                    }

                    sum.amount += r_amount;
                }
                var tfooter = $(tfoot);
                $(tfooter).find('.total.deduct_amount' ).text(numeral(sum.amount).format('0.00'));
            }
        });
    });

</script>