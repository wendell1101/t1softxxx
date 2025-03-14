<style type="text/css">
    #collapseCashbackReport input{
        font-weight:bold;
    }
</style>

<form class="form-horizontal" id="search-form" method="get" role="form">
    <div class="panel panel-primary hidden">
        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-search"></i> <?=$title; ?>
                <span class="pull-right">
                <a data-toggle="collapse" href="#collapseCashbackReport" class="btn btn-info btn-xs <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
                <?php include __DIR__ . "/../includes/report_tools.php" ?>
            </h4>
        </div>

        <div id="collapseCashbackReport" class="panel-collapse <?=$this->utils->getConfig('default_open_search_panel') ? '' : 'collapse in' ?>">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label" for="period"><?=lang('aff.ap07');?>:</label>
                        <input type="text" class="form-control input-sm dateInput" id = "filterDate"
                            data-start="#dateRangeValueStart"
                            data-end="#dateRangeValueEnd"
                            data-time="false"

                            data-restrict-max-range="<?=$this->utils->getConfig('affiliate_statistics2_report_date_range_restriction')?>"
                            data-restrict-range-label="<?=sprintf(lang("restrict_date_range_label"),$this->utils->getConfig('affiliate_statistics2_report_date_range_restriction'))?>"
                            data-override-on-apply="false"
                        />
                        <input type="hidden" id="dateRangeValueStart" name="by_date_from" value="<?=$conditions['by_date_from']; ?>" />
                        <input type="hidden" id="dateRangeValueEnd" name="by_date_to" value="<?=$conditions['by_date_to']; ?>" />
                    </div>
                    <div class="col-md-3 col-lg-3 hide ">
                        <label class="control-label"><?=lang('Enabled date'); ?></label><br>
                        <input type="checkbox" id="enable_date" data-off-text="<?=lang('off'); ?>" data-on-text="<?=lang('on'); ?>" name="enable_date" data-size='mini' value='true' <?=$conditions['enable_date'] ? 'checked="checked"' : ''; ?>>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('Affiliate status'); ?></label>
                        <div>
                            <select id="by_status" name="by_status" class="form-control input-sm">
                                <option value="" <?=empty($conditions['by_status']) ? 'selected' : ''; ?> >--  <?=lang('None'); ?> --</option>
                                <option value="0" <?=($conditions['by_status'] === '0') ? 'selected' : ''; ?> ><?=lang('Active Only'); ?></option>
                                <option value="1" <?=($conditions['by_status'] === '1') ? 'selected' : ''; ?> ><?=lang('Inactive only'); ?></option>
<!--                                <option value="2" --><?//=($conditions['by_status'] === '2') ? 'selected' : ''; ?><!-- >--><?//=lang('Deleted only'); ?><!--</option>-->
                                <option value="-1" <?=($conditions['by_status'] === '-1') ? 'selected' : ''; ?> ><?=lang('No empty affiliate'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('Affiliate Username'); ?></label>
                        <input type="text" name="by_affiliate_username" class="form-control input-sm" placeholder='<?=lang('Enter Username'); ?>'
                               value="<?=$conditions['by_affiliate_username']; ?>"/>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('Show game platform'); ?></label><br>
                        <input type="checkbox" id="show_game_platform" data-off-text="<?=lang('off'); ?>" data-on-text="<?=lang('on'); ?>" name="show_game_platform" data-size='mini' value='true' <?=$conditions['show_game_platform'] ? 'checked="checked"' : ''; ?>>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('Parent Affiliate'); ?></label>
                        <input type="text" name="parent_affiliate_username" class="form-control input-sm" placeholder='<?=lang('Enter Parent Affiliate'); ?>'
                               value="<?=$conditions['parent_affiliate_username']; ?>"/>
                    </div>
                    <div class="col-md-3 col-lg-3">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('Affiliate Tag');?> </label>
                    </div>
                </div>

                <div class="row">
                    <?php if(isset($tags) && !empty($tags)):?>
                        <?php foreach ($tags as $tag_id => $tag) {?>
                            <div class="col-md-2">
                                <label class="control-label">
                                    <input class="control-label afftags" data-size='mini' type="checkbox" name="tag_id[]" value="<?=$tag_id?>" <?=in_array($tag_id, $conditions['tag_id']) ? 'checked="checked"' : ''?>>
                                    <?=$tag['tagName']?>
                                </label>
                            </div>
                        <?php }?>
                    <?php endif;?>
                </div>

                <div class="row">
                    <div class="col-md-3 col-lg-3" style="padding: 10px;">
                        <input type="button" value="<?=lang('Reset'); ?>" class="btn btn-danger btn-sm" onclick="resetForm()">
                        <input class="btn btn-sm btn-primary" type="submit" value="<?=lang('Search'); ?>" />
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
<!--end of Sort Information-->


<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt"><i class="icon-bullhorn"></i> <?=lang('Report Result'); ?></h4>
    </div>
    <div class="panel-body">
        <!-- result table -->
        <div id="logList" class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="report_table" style="width:100%;">
                <thead>
                <tr>
                    <th><?=lang('Affiliate Username'); # 0 ?></th>
                    <th><?=lang('aff.aj05'); # 1 ?></th>
                    <th><?=lang('Parent Affiliate'); # 2 ?></th>
                    <th><?=lang('Affiliate Level'); # 3 ?></th>
                    <th><?=lang('Total Sub-affiliates'); # 4 ?></th>
                    <th><?=lang('Total registered players'); # 5 ?></th>
                    <th><?=lang('Total deposited players'); # 6 ?></th>
                    <th><?=lang('Total deposited players in Date Range'); # 7 ?></th>
                    <th><?=lang('Total Bet'); # 8 ?></th>
                    <th><?=lang('Total Win'); # 9 ?></th>
                    <th><?=lang('Total Loss'); # 10 ?></th>
                    <th><?=lang('Company Win/Loss'); # 11 ?></th>
                    <th><?=lang('Company Income'); # 12 ?></th>
                    <th><?=lang('Total Cashback'); # 13 ?></th>
                    <th><?=lang('Total Bonus'); # 14 ?></th>
                    <th><?=lang('Total Deposit'); # 15 ?></th>
                    <th><?=lang('Total Withdraw'); # 16 ?></th>
                    <th><?php echo lang('Platform Fee'); # 17 ?></th>
                    <th><?php echo lang('Bonus Fee'); # 18 ?></th>
                    <th><?php echo lang('Cashback Fee'); # 19 ?></th>
                    <th><?php echo lang('Transaction Fee'); # 20 ?></th>
                    <th><?php echo lang('Admin Fee'); # 21 ?></th>
                    <th><?php echo lang('Total Fee'); # 22 ?></th>
                    <th><?=lang('Cashback Revenue'); # 23 ?></th>

                </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                <tr id="subtotal">
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="text-right total-sub-affiliates">0</th>
                    <th class="text-right total-registered-players">0</th>
                    <th class="text-right total-deposited-players">0</th>
                    <th class="text-right total-deposited-players-in-date-range">0</th>
                    <th class="text-right total-bet">0.00</th>
                    <th class="text-right total-win">0.00</th>
                    <th class="text-right total-loss">0.00</th>
                    <th class="text-right company-win-loss">0.00</th>
                    <th class="text-right company-income">0.00</th>
                    <th class="text-right total-cashback">0.00</th>
                    <th class="text-right total-bonus">0.00</th>
                    <th class="text-right total-deposit">0.00</th>
                    <th class="text-right total-withdraw">0.00</th>

                    <th class="text-right platform-fee">0.00</th>
                    <th class="text-right bonus-fee">0.00</th>
                    <th class="text-right cashback-fee">0.00</th>
                    <th class="text-right transaction-fee">0.00</th>
                    <th class="text-right admin-fee">0.00</th>
                    <th class="text-right total-fee">0.00</th>
                    <th class="text-right cashback-revenue">0.00</th>
                </tr>
                <tr id="grandtotal">
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="text-right total-sub-affiliates">0</th>
                    <th class="text-right total-registered-players">0</th>
                    <th class="text-right total-deposited-players">0</th>
                    <th class="text-right total-deposited-players-in-date-range">0</th>
                    <th class="text-right total-bet">0.00</th>
                    <th class="text-right total-win">0.00</th>
                    <th class="text-right total-loss">0.00</th>
                    <th class="text-right company-win-loss">0.00</th>
                    <th class="text-right company-income">0.00</th>
                    <th class="text-right total-cashback">0.00</th>
                    <th class="text-right total-bonus">0.00</th>
                    <th class="text-right total-deposit">0.00</th>
                    <th class="text-right total-withdraw">0.00</th>

                    <th class="text-right platform-fee">0.00</th>
                    <th class="text-right bonus-fee">0.00</th>
                    <th class="text-right cashback-fee">0.00</th>
                    <th class="text-right transaction-fee">0.00</th>
                    <th class="text-right admin-fee">0.00</th>
                    <th class="text-right total-fee">0.00</th>
                    <th class="text-right cashback-revenue">0.00</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <!--end of result table -->
    <div class="panel-footer"></div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>
<script type="text/javascript">
    function getCurrentDate(d){
        var month = d.getMonth()+1;
        var day = d.getDate();
        var output = d.getFullYear() + '-' +
            ((''+month).length<2 ? '0' : '') + month + '-' +
            ((''+day).length<2 ? '0' : '') + day;
        return output;
    }

    function resetForm(){
        //get current date
        var date_from = new Date();
        //get 7 days before
        date_from.setDate(date_from.getDate() - 7);
        var from = getCurrentDate(date_from);
        var to = getCurrentDate(new Date());
        //set default value
        $('#dateRangeValueStart').val(from + " 00:00:00");
        $('#dateRangeValueEnd').val(to + " 23:59:59");
        $("#filterDate").data('daterangepicker').setStartDate(from);
        $("#filterDate").data('daterangepicker').setEndDate(to);
        $("input[name='by_affiliate_username']").val("");
        $("input[name='parent_affiliate_username']").val("");
        $("#by_status").val("0").change();
        $(".afftags").bootstrapSwitch('state', false);
        $("#enable_date").bootstrapSwitch('state', true);
        $("#show_game_platform").bootstrapSwitch('state', false);
        $("select option").removeAttr('selected');
    }

    function subtotal_deposited_player_specified_period(){
        var _total_deposited_player_specified_period = 0;
        // data-total_deposited_players
        $('.tdpsp[data-total_deposited_players]').each(function(){
            var curr$El = $(this);
            if(!!curr$El.data('total_deposited_players')){
                _total_deposited_player_specified_period += curr$El.data('total_deposited_players');
            }

        })
        $('#subtotal .total-deposited-players-in-date-range').html(numeral(_total_deposited_player_specified_period).format('0,0'));

        // grandtotal is in the queue of AjaxQueue.
        // $('#grandtotal .total-deposited-players-in-date-range').html(numeral(_total_deposited_player_specified_period).format('0,0'));


    }


    function build_total_deposited_players_uri ( affiliate_id
                                                , start_date
                                                , end_date
                                                , by_status
                                                , parentAffUsername
                                                , affTags
                                                , by_affiliate_username
    ){

        if( typeof(affiliate_id) === 'undefined'){
            affiliate_id = 0;
        }

        var _by_affiliate_username = $('#search-form [name="by_affiliate_username"]').val();
        if( typeof(by_affiliate_username) !== 'undefined'){
            _by_affiliate_username = by_affiliate_username;
        }

        var _start_date = $("#filterDate").data('daterangepicker').startDate.format('YYYY-MM-DD');
        var _end_date = $("#filterDate").data('daterangepicker').endDate.format('YYYY-MM-DD');
        if( typeof(start_date) !== 'undefined'){
            _start_date = start_date;
        }
        if( typeof(end_date) !== 'undefined'){
            _end_date = end_date;
        }

        var _by_status = $('#search-form [name="by_status"]').val();
        if( typeof(by_status) !== 'undefined'){
            _by_status = by_status;
        }
        if( _by_status === ''){ // ignore this condition
            _by_status = 'NULL';
        }



        var _parent_affiliate_username = $('#search-form [name="parent_affiliate_username"]').val()
        if(_parent_affiliate_username.trim() == '' ){
            _parent_affiliate_username = 'NULL';
        }
        if( typeof(parentAffUsername) !== 'undefined'){
            _parent_affiliate_username = parentAffUsername;
        }

        var _tag_id_list = [];
        var _tag_id_list_in_qstring = '0';
        $('#search-form [name="tag_id[]"]:checkbox:checked').each(function(){
            _tag_id_list.push($(this).val());
        });
        if(_tag_id_list.length > 0){
            _tag_id_list_in_qstring = _tag_id_list.join('_');
        }
        if( typeof(affTags) !== 'undefined'){
            _tag_id_list_in_qstring = affTags;
        }

        var total_deposited_players_uri = base_url+ 'affiliate_management/total_deposited_players/'+ affiliate_id+ '/'+ _start_date+ '/'+ _end_date;


        total_deposited_players_uri += '/'+ _by_status;
        total_deposited_players_uri += '/'+ _parent_affiliate_username;
        total_deposited_players_uri += '/'+ _tag_id_list_in_qstring;
        total_deposited_players_uri += '/'+ _by_affiliate_username;
        total_deposited_players_uri += '?timestamp='+ Math.floor(Date.now() / 1000);

        return total_deposited_players_uri;
    }

    $(document).ready(function(){
        $("input[type='checkbox']").bootstrapSwitch();

        /// for Performance Issue in the column, "Total deposited players in Date Range"
        $('.row>div:has(#enable_date)').addClass('hide');
        $("#enable_date").bootstrapSwitch('state', true);

        $('.bookmark-this').click(_pubutils.addBookmark);

        $('#search-form input[type="text"],#search-form input[type="number"],#search-form input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').trigger('submit');
            }
        });

        // admin/public/resources/js/ajax_queue.js
        var _options_in_ajax_queue = {};
        _options_in_ajax_queue.alwaysCallback_in_deferr = function(){
            // console.log('249.249.do doSubtotals() in .tdpsp');
        }
        _options_in_ajax_queue.langs = {};
        _options_in_ajax_queue.langs.abort = '<?=lang('Suspend')?>';
        var _queue = AjaxQueue.init(_options_in_ajax_queue);

        var dataTable = $('#report_table').DataTable({
            autoWidth: false,
            searching: false,
            rowCallback: function( row, data ) { // callback by each raw
                var curr_tdpsp$El = $('.tdpsp', row);
                var affiliate_id = curr_tdpsp$El.data('affiliate_id');
                var start_date = curr_tdpsp$El.data('start_date');
                var end_date = curr_tdpsp$El.data('end_date');

                var total_deposited_players_uri = build_total_deposited_players_uri(affiliate_id
                                                    , start_date
                                                    , end_date
                                                    // , by_status
                                                    // , parentAffUsername
                                                    // , affTags
                                                    // , by_affiliate_username
                                                );

                // Add the ajax into the _queue.
                var _options = {};
                _options.url= total_deposited_players_uri;
                _options.type= "GET";
                _options.contentType = "application/json";
                _options.beforeSend = function ( xhr ){
                    curr_tdpsp$El.html("<?=lang('Referesh')?>");
                };
                _options.doneCallback = function(){
                    var cloned_arguments = Array.prototype.slice.call(arguments);
                    // cloned_arguments = ( data, textStatus, jqXHR )
                    var _data = cloned_arguments[0];
                    var _total_deposited_players = _data[0];
                    curr_tdpsp$El.html( _total_deposited_players );
                    // data-total_deposited_players
                    curr_tdpsp$El.data('total_deposited_players', _total_deposited_players); // for subtotal
                    curr_tdpsp$El.attr('data-total_deposited_players', _total_deposited_players); // for subtotal

                    subtotal_deposited_player_specified_period(); // refresh subtotal
                };
                _options.failCallback = function(){
                    var cloned_arguments = Array.prototype.slice.call(arguments);
                    // cloned_arguments = ( jqXHR, textStatus, errorThrown )
                    var _textStatus = cloned_arguments[1];
                    curr_tdpsp$El.html(_textStatus);
                    curr_tdpsp$El.data('total_deposited_players', 0); // for subtotal
                };
                // console.log('OGP-27747.rowCallback will push_task_in_list');
                _queue.push_task_in_list(_options); // add the ajax task in the "_queue.list".

            }, // EOF rowCallback()
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
            stateSave: true,
            <?php } else { ?>
            stateSave: false,
            <?php } ?>
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ]
                }
                <?php if ($export_report_permission) {?>
                ,{
                    text: '<?=lang("lang.export_excel"); ?>',
                    className:'btn btn-sm btn-primary',
                    action: function ( e, dt, node, config ) {

                        var form_params=$('#search-form').serializeArray();
                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};
                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/affiliate_statistics2'));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                <?php if(!$this->utils->isEnabledFeature('enable_sortable_columns_affiliate_statistic')) { ?>
                { sortable: false, targets: [ 4,5,6,7,8,9,10,11,12,13,14,15 ] },
                <?php } ?>
                { className: 'text-right', targets: [ 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 ] },
                { visible: false, targets: [ 3,4 ] }
            ],
            "order": [ 0, 'asc' ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                var _ajax = $.post(base_url + "api/affiliate_statistics2", data, function(_data) {
                    // console.log( 'OGP-27747.ajax._ajax will callback');
                    if(!!_queue){
                        _queue.clear_list();
                    }
                    _queue = AjaxQueue.init(_options_in_ajax_queue);
                    callback(_data);
                },'json');

                function doSubtotals(data){

                    var total_sub_affiliates = 0,
                        total_registered_players = 0,
                        total_deposited_players = 0,
                        total_deposited_players_in_date_range = 0,
                        total_bet = 0,
                        total_win = 0,
                        total_loss = 0,
                        company_win_loss = 0,
                        company_income = 0,
                        total_cashback = 0,
                        total_bonus = 0,
                        total_deposit = 0,
                        total_withdraw = 0,

                        platform_fee = 0,
                        bonus_fee = 0,
                        cashback_fee = 0,
                        transaction_fee = 0,
                        admin_fee = 0,
                        total_fee = 0,
                        cashback_revenue = 0;

                    $.each(data.data, function(i, v) {
                        total_sub_affiliates += numeral(v[4]).value();
                        total_registered_players += numeral(v[5]).value();
                        total_deposited_players += numeral(v[6]).value();
                        total_deposited_players_in_date_range = 0; // v[7], subtotal in the queue of AjaxQueue.
                        total_bet += numeral(v[8]).value();
                        total_win += numeral(v[9]).value();
                        total_loss += numeral(v[10]).value();
                        company_win_loss += numeral($(v[11]).text()).value();
                        company_income += numeral($(v[12]).text()).value();
                        total_cashback += numeral(v[13]).value();
                        total_bonus += numeral(v[14]).value();
                        total_deposit += numeral(v[15]).value();
                        total_withdraw += numeral(v[16]).value();
                        platform_fee += numeral(v[17]).value();
                        bonus_fee += numeral(v[18]).value();
                        cashback_fee += numeral(v[19]).value();
                        transaction_fee += numeral(v[20]).value();
                        admin_fee += numeral(v[21]).value();
                        total_fee += numeral(v[22]).value();
                        cashback_revenue += numeral(v[23]).value();
                    });

                    $('#subtotal .total-sub-affiliates').text(parseInt(total_sub_affiliates));
                    $('#subtotal .total-registered-players').text(parseInt(total_registered_players));
                    $('#subtotal .total-deposited-players').text(parseInt(total_deposited_players));
                    $('#subtotal .total-deposited-players-in-date-range').text(parseInt(total_deposited_players_in_date_range)); // subtotal in the queue of AjaxQueue.

                    // OGP-22042 workaround
                    if ($('input#show_game_platform').prop('checked') == true) {
                        $('#subtotal .total-bet').html('&mdash;');
                        $('#subtotal .total-win').html('&mdash;');
                        $('#subtotal .total-loss').html('&mdash;');
                    }
                    else {
                        $('#subtotal .total-bet').text(numeral(total_bet).format('0,0.00'));
                        $('#subtotal .total-win').text(numeral(total_win).format('0,0.00'));
                        $('#subtotal .total-loss').text(numeral(total_loss).format('0,0.00'));
                    }

                    $('#subtotal .company-win-loss').text(numeral(company_win_loss).format('0,0.00')).removeClass('text-success text-danger');
                    if (company_win_loss > 0) {
                        $('#subtotal .company-win-loss').addClass('text-success');
                    } else if (company_win_loss < 0) {
                        $('#subtotal .company-win-loss').addClass('text-danger');
                    }

                    $('#subtotal .company-income').text(numeral(company_income).format('0,0.00')).removeClass('text-success text-danger');
                    if (company_income > 0) {
                        $('#subtotal .company-income').addClass('text-success');
                    } else if (company_income < 0) {
                        $('#subtotal .company-income').addClass('text-danger');
                    }

                    $('#subtotal .total-cashback').text(numeral(total_cashback).format('0,0.00'));
                    $('#subtotal .total-bonus').text(numeral(total_bonus).format('0,0.00'));
                    $('#subtotal .total-deposit').text(numeral(total_deposit).format('0,0.00'));
                    $('#subtotal .total-withdraw').text(numeral(total_withdraw).format('0,0.00'));

                    $('#subtotal .platform-fee').text(numeral(platform_fee).format('0,0.00'));
                    $('#subtotal .bonus-fee').text(numeral(bonus_fee).format('0,0.00'));
                    $('#subtotal .cashback-fee').text(numeral(cashback_fee).format('0,0.00'));
                    $('#subtotal .transaction-fee').text(numeral(transaction_fee).format('0,0.00'));
                    $('#subtotal .admin-fee').text(numeral(admin_fee).format('0,0.00'));
                    $('#subtotal .total-fee').text(numeral(total_fee).format('0,0.00'));
                    $('#subtotal .cashback-revenue').text(numeral(cashback_revenue).format('0,0.00'));

                    // $('#grandtotal .total-sub-affiliates').text(parseInt(data.summary.total_sub_affiliates));
                    // $('#grandtotal .total-registered-players').html(parseInt(data.summary.total_registered_players));
                    // $('#grandtotal .total-deposited-players').html(parseInt(data.summary.total_deposited_players));
                    $('#grandtotal .total-sub-affiliates').text(numeral(data.summary.total_sub_affiliates).format('0,0'));
                    $('#grandtotal .total-registered-players').html(numeral(data.summary.total_registered_players).format('0,0'));
                    $('#grandtotal .total-deposited-players').html(numeral(data.summary.total_deposited_players).format('0,0'));

                    $('#grandtotal .total-deposited-players-in-date-range').html(numeral(0).format('0,0')); // subtotal in the queue of AjaxQueue.

                    $('#grandtotal .total-bet').html(data.summary.total_bet);
                    $('#grandtotal .total-win').html(data.summary.total_win);
                    $('#grandtotal .total-loss').html(data.summary.total_loss);
                    $('#grandtotal .company-win-loss').html(data.summary.company_win_loss);
                    $('#grandtotal .company-income').html(data.summary.company_income);
                    $('#grandtotal .total-cashback').html(data.summary.total_cashback);
                    $('#grandtotal .total-bonus').html(data.summary.total_bonus);
                    $('#grandtotal .total-deposit').html(data.summary.total_deposit);
                    $('#grandtotal .total-withdraw').html(data.summary.total_withdraw);

                    $('#grandtotal .platform-fee').text(numeral(data.summary.platform_fee   ).format('0,0.00'));
                    $('#grandtotal .bonus-fee').text(numeral(data.summary.bonus_fee   ).format('0,0.00'));
                    $('#grandtotal .cashback-fee').text(numeral(data.summary.cashback_fee   ).format('0,0.00'));
                    $('#grandtotal .transaction-fee').text(numeral(data.summary.transaction_fee   ).format('0,0.00'));
                    $('#grandtotal .admin-fee').text(numeral(data.summary.admin_fee   ).format('0,0.00'));
                    $('#grandtotal .total-fee').text(numeral(data.summary.total_fee   ).format('0,0.00'));
                    $('#grandtotal .cashback-revenue').html(data.summary.cashback_revenue);

                    /// Add the ajax into the _queue.
                    var _curr_tdpsp$El = $('#grandtotal .total-deposited-players-in-date-range');
                    // var _start_date = $("#filterDate").data('daterangepicker').startDate.format('YYYY-MM-DD');
                    // var _end_date = $("#filterDate").data('daterangepicker').endDate.format('YYYY-MM-DD');
                    var total_deposited_players_uri = build_total_deposited_players_uri();
                    var _options = {};
                    // _options.url= base_url+ 'affiliate_management/total_deposited_players/0/'+ _start_date+ '/'+ _end_date;
                    _options.url= total_deposited_players_uri;
                    _options.type= "GET";
                    _options.contentType = "application/json";
                    _options.beforeSend = function ( xhr ){
                        _curr_tdpsp$El.html("<?=lang('Referesh')?>");
                    };
                    _options.doneCallback = function(){
                        var cloned_arguments = Array.prototype.slice.call(arguments);
                        // cloned_arguments = ( data, textStatus, jqXHR )
                        var _data = cloned_arguments[0];
                        var _total_deposited_players = _data[0];
                        _curr_tdpsp$El.html(numeral(_total_deposited_players).format('0,0'));
                    }
                    _options.failCallback = function(){
                        var cloned_arguments = Array.prototype.slice.call(arguments);
                        // cloned_arguments = ( jqXHR, textStatus, errorThrown )

                        var _textStatus = cloned_arguments[1];
                        _curr_tdpsp$El.html(_textStatus);
                    }
                    _queue.push_task_in_list(_options); // add the ajax task in the "_queue.list".

                    _queue.go_with_sync(); // To fire syncs one by one.


                    if ($('#report_table').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
                        $('#report_table').DataTable().buttons().disable();
                    }
                    else {
                        $('#report_table').DataTable().buttons().enable();
                    }

                }// EOF doSubtotals()

                _ajax.done(function(_data, _textStatus, _jqXHR){
                    // console.log( 'OGP-27747.done.will doSubtotals');
                    doSubtotals(_data);
                }); // EOF _ajax.done(function(_data, _textStatus, _jqXHR){...

            } // EOF ajax: function (data, callback, settings) {...
        }); // EOF $('#report_table').DataTable({...

        dataTable.on( 'draw', function () {
            // console.log( 'OGP-27747.Redraw occurred at: '+new Date().getTime() );
        } );
    });

</script>

