<?php include __DIR__.'/../includes/big_wallet_details.php'; ?>
<?php include __DIR__.'/../includes/popup_promorules_info.php'; ?>

<div class="container-fluid" id="iframe_transaction_list" >
    <!-- Sort Option -->
    <form class="form-horizontal" id="form-filter" method="post">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary hidden">

                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <i class="fa fa-search"></i> <?=lang("lang.search")?>
                            <span class="pull-right">
                                <a data-toggle="collapse" href="#collapseTransactionList" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
                            </span>
                        </h4>
                    </div>
                    <div id="collapseTransactionList" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="control-label"><?=lang('pay.transperd')?></label>
                                    <input id="reportrange" class="form-control input-sm dateInput" data-time="true" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" autocomplete="off"/>
                                    <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart" value="<?=$this->session->userdata('dateRangeValueEnd')?>"/>
                                    <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd" value="<?=$this->session->userdata('dateRangeValueEnd')?>"/>
                                </div>
                                <div class="col-md-2">
                                    <label for="username" class="control-label">
                                        <?=lang('Username'); ?>:
                                        <input type="radio" name="search_by" value="1" checked="checked"/> <?=lang('Similar');?>
                                        <input type="radio" name="search_by" value="2"/> <?=lang('Exact'); ?>
                                    </label>
                                    <input type="text" name="memberUsername"  id="memberUsername"  class="form-control input-sm" placeholder="<?=lang('member.username'); ?>"/>
                                    <?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
                                        <label>
                                            <input type="checkbox" name="no_affiliate" value="true" onchange="$('#belongAff').prop('disabled', this.checked);" />
                                            <?=lang('affiliate.no.affiliate.only')?>
                                        </label>
                                        <label>
                                            <input type="checkbox" name="no_agent" value="true"/>
                                            <?=lang('No agent only')?>
                                        </label>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('pay.transid'); ?></label>
                                    <input type="number" name="transaction_id"  id="transaction_id"  class="form-control input-sm" placeholder="<?=lang('pay.transid'); ?>" value="<?=$transaction_id?>"/>
                                </div>
                                <!-- <div class="col-md-2">
                                    <label class="control-label"><?=lang('cms.promoCat')?></label>
                                    <select class="form-control input-sm" name="promo_category">
                                        <option value=""><?=lang('lang.selectall')?></option>
                                        <?php foreach ($promo_category_list as $promo_category): ?>
                                            <option value="<?=$promo_category['promotypeId']?>" <?=set_select('promo_category', $promo_category['promotypeId'], isset($input['promo_category']) && $input['promo_category'] == $promo_category['promotypeId'])?>><?=lang($promo_category['promoTypeName'])?></option>
                                        <?php endforeach?>
                                    </select>
                                </div> -->
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('player.ut10')?></label>
                                    <select class="form-control input-sm" name="flag">
                                        <option value=""><?=lang('lang.selectall')?></option>
                                        <option value="1" <?=set_select('flag', 1, isset($input['flag']) && $input['flag'] == 1)?>><?=lang('transaction.flag.1')?></option>
                                        <option value="2" <?=set_select('flag', 2, isset($input['flag']) && $input['flag'] == 2)?>><?=lang('transaction.flag.2')?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('player.80')?></label>
                                    <div>
                                        <select class="form-control input-sm" name="from_type" id="from_type">
                                            <option value=""><?=lang('lang.selectall')?></option>
                                            <option value="1" <?=set_select('from_type', 1, isset($input['from_type']) && $input['from_type'] == 1)?>><?=lang('transaction.from.to.type.1')?></option>
                                            <option value="2" <?=set_select('from_type', 2, isset($input['from_type']) && $input['from_type'] == 2)?>><?=lang('transaction.from.to.type.2')?></option>
                                            <option value="3" <?=set_select('from_type', 3, isset($input['from_type']) && $input['from_type'] == 3)?>><?=lang('transaction.from.to.type.3')?></option>
                                        </select>
                                        <br>
                                        <input type="text" name="fromUsername" style="display:none;" id="fromUsername"  class="form-control input-sm" placeholder="<?=lang('reg.03')?>"/>
                                        <span class="help-block" ></span>
                                        <input type="hidden" name="fromUsernameId"  id="fromUsernameId" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('player.81')?></label>
                                    <div>
                                        <select class="form-control input-sm" name="to_type" id="to_type">
                                            <option value=""><?=lang('lang.selectall')?></option>
                                            <option value="1" <?=set_select('from_type', 1, isset($input['from_type']) && $input['from_type'] == 1)?>><?=lang('transaction.from.to.type.1')?></option>
                                            <option value="2" <?=set_select('from_type', 2, isset($input['from_type']) && $input['from_type'] == 2)?>><?=lang('transaction.from.to.type.2')?></option>
                                            <option value="3" <?=set_select('from_type', 3, isset($input['from_type']) && $input['from_type'] == 3)?>><?=lang('transaction.from.to.type.3')?></option>
                                        </select>
                                        <br>
                                       <input type="text" name="toUsername" style="display:none;"  id="toUsername"  class="form-control input-sm" placeholder="<?=lang('reg.03')?>"/>
                                       <span class="help-block" ></span>
                                       <input type="hidden" name="toUsernameId" id="toUsernameId" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('Transaction Amount')?> >= </label>
                                    <input type="number" class="form-control input-sm" name="amountStart" min="0" maxlength="10" oninput="maxLengthCheck(this)" id="amountStart" value="<?=isset($input['amountStart']) ? $input['amountStart'] : '';?>"/>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('Transaction Amount')?> <= </label>
                                    <input type="number" class="form-control input-sm" name="amountEnd" id="amountEnd" min="0" maxlength="10"  oninput="maxLengthCheck(this)" value="<?=isset($input['amountEnd']) ? $input['amountEnd'] : '';?>"/>
                                    <span id="from-to-amount-range" class="help-block" style="color:#F04124;"></span>
                                </div>
                                <?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
                                    <div class="col-md-2">
                                        <label class="control-label"><?=lang('Belongs To Affiliate'); ?></label>
                                        <input type="text" name="belongAff"  id="belongAff"  class="form-control input-sm" placeholder="<?=lang('Belongs To Affiliate'); ?>"/>
                                        <label>
                                            <input type="checkbox" name="aff_include_all_downlines" value="true"/>
                                            <?=lang('Include All Downlines Affiliate')?>
                                        </label>
                                    </div>
                                <?php endif; ?>
                                <?php if ($this->utils->isEnabledFeature('enable_adjustment_category')&& false): ?>
                                    <div class="col-md-2">
                                        <label class="control-label"><?=lang('Adjustment Category');?></label>
                                        <div class="">
                                            <select class="form-control" name="adjustment_category_id"style="height: 36px; font-size: 12px;">
                                            <option value=""><?=lang("None");?></option>
                                            <?php if(!empty($adjustment_category_list)): ?>
                                                <?php foreach ($adjustment_category_list as $key => $value): ?>
                                                    <option value="<?=$value['id'];?>" <?=set_select('category_name', $value['category_name'], isset($input['category_name']) && $input['id'] == $value['category_name'])?>><?=lang($value['category_name'])?></option>
                                                <?php endforeach; ?>
                                            <?php endif;?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endif;?>
                                <?php if ($this->permissions->checkPermissions('friend_referral_player') && false): ?>
                                    <div class="col-md-2">
                                        <label class="control-label" for="referrer"><?=lang('pay.referrer')?></label>
                                        <input id="referrer" type="text" name="referrer"  value="<?=$referrer?>"  class="form-control input-sm"/>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class='row'>
                                <?php if ($this->utils->isEnabledFeature('enable_tag_column_on_transaction') && false): ?>
                                    <div class="col-md-2">
                                        <label class="control-label" class="control-label" style="font-size:12px;"><?=lang('con.plm72');?></label>
                                        <select class="form-control input-sm" name="tag_list">
                                            <option value=""><?=lang('lang.selectall')?></option>
                                            <?php foreach ($tag_list as $tag): ?>
                                                <option value="<?=$tag['tagId']?>" ><?=lang($tag['tagName'])?></option>
                                            <?php endforeach?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                <fieldset style="padding:0px 10px 10px 15px;">
                                    <legend>
                                    <label class="control-label"><?=lang('player.ut02')?></label>
                                    </legend>
                                    <div class="col-md-12">
                                        <label class="checkbox-inline" for="checkall">
                                            <input id="checkall" name="transaction_type_all" value="checkall" class="checkall" type="checkbox" onclick="checkAll(this.id)" checked>
                                            <?=lang('player.ui02')?>
                                        </label>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="checkbox-inline" for="transaction_type_1">
                                            <input id="transaction_type_1" name="transaction_type" value="1" class="checkall trans-check" type="checkbox" checked="checked">
                                            <?=lang('transaction.transaction.type.1')?>
                                        </label>

                                        <label class="checkbox-inline" for="transaction_type_2">
                                            <input id="transaction_type_2" name="transaction_type" value="2" class="checkall trans-check" type="checkbox" checked="checked">
                                            <?=lang('transaction.transaction.type.2')?>
                                        </label>

                                        <label class="checkbox-inline" for="transaction_type_7">
                                            <input id="transaction_type_7" name="transaction_type" value="7" class="checkall trans-check" type="checkbox" checked="checked">
                                            <?=lang('transaction.transaction.type.7')?>
                                        </label>

                                        <label class="checkbox-inline" for="transaction_type_8">
                                            <input id="transaction_type_8" name="transaction_type" value="8" class="checkall trans-check" type="checkbox" checked="checked">
                                            <?=lang('transaction.transaction.type.8')?>
                                        </label>
                                        <label class="checkbox-inline" for="transaction_type_9">
                                            <input id="transaction_type_9" name="transaction_type" value="9" class="checkall trans-check" type="checkbox" checked="checked">
                                            <?=lang('transaction.transaction.type.9')?>
                                        </label>
                                        <label class="checkbox-inline" for="transaction_type_13">
                                            <input id="transaction_type_13" name="transaction_type" value="13" class="checkall trans-check" type="checkbox" checked="checked">
                                            <?=lang('transaction.transaction.type.13')?>
                                        </label>
                                    </div>
                                </fieldset>
                                </div>
                            </div>


                        </div>
                        <div class="panel-footer text-right">
                            <input class="btn btn-sm btn-linkwater" id="btn-reset" type="reset" value="<?=lang('lang.reset');?>">
                            <input class="btn btn-sm btn-portage" type="submit" value="<?=lang("lang.search");?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!--end of Sort Information-->

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title"><i class="fa fa-list-alt"></i> <?=lang('pay.balance_transactions')?></h4>
        </div>
        <div class="table-responsive" style="padding:10px;">
            <table id="transaction-table" class="table table-bordered table-hover" cellspacing="0" width="100%" >
                <thead>
                    <tr>
                        <th><?=lang('pay.reqtime');?></th> <!-- #1 Requested Time -->
                        <th><?=lang('pay.procsson');?></th> <!-- #2 Processed On -->
                        <th><?=lang('player.ut02');?></th> <!-- #3 Transaction Type -->
                        <th><?=lang('pay.from.procssby');?></th> <!--  #4 From (Processed By) -->
                        <th><?=lang('pay.amt');?></th> <!-- #5 Amount -->
                        <th><?=lang('player.ut06');?></th> <!-- #6 Before Balance -->
                        <th><?=lang('player.ut07');?></th> <!-- #7 After Balance -->
                        <th><?=lang('pay.payment_account_flag');?></th> <!-- #8 Bank/Payment Type -->
                        <?php if($this->utils->getConfig('enabled_rename_transation_id')) : ?>
                            <th><?=lang('pay.transid'); ?></th> <!-- #9 Request ID -->
                        <?php else: ?>
                            <th><?=lang('Request ID');?></th> <!-- #9 Request ID -->
                        <?php endif; ?>
                        <th><?=lang('External ID');?></th> <!-- #10 External ID (in case player deposit over AMB) -->
                        <th><?=lang('Order ID');?></th> <!-- #11 Order ID -->
                        <th><?=lang('Promo Rule');?></th> <!-- #12 Promo Rule -->
                        <th><?=lang('Action Log');?></th> <!-- #13 Action Log -->
                        <th><?=lang('Internal Note');?></th> <!-- #14 Internal Note -->
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th id="transaction-ft-col" style="text-align:right;">
                            <div class="row" id="summary"></div>
                            <div id="bank-summary" style="display: none;">
                                <h4 class="page-header"><?=lang('role.80')?></h4>
                                <div class="row"></div>
                            </div>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="panel-body"></div>
        <div class="panel-footer"></div>
    </div>
</div>
<?php if($this->utils->isEnabledFeature('export_excel_on_queue')): ?>
        <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
            <input name='json_search' type="hidden">
        </form>
<?php endif; ?>

<script type="text/javascript">
    $(document).ready(function(){
        //check if all checked on filter transactions
        $('.trans-check').on('click',function(){
            if($('.trans-check:checked').length != $('.trans-check').length){
                $('#checkall').removeAttr('checked');
            }else{
                $('#checkall').click();
            }
        });

        <?php $col_config = $this->utils->getConfig('balance_transactions_columnDefs'); ?>
            var hidden_cols = [];
        <?php if(!empty($col_config['not_visible_balance_transactions_report'])) : ?>
            var not_visible_cols = JSON.parse("<?= json_encode($col_config['not_visible_balance_transactions_report']) ?>" ) ;
        <?php else: ?>
            var not_visible_cols = [];
        <?php endif; ?>

        <?php if(!empty($col_config['className_text-balance_transactions_report'])) : ?>
            var text_right_cols = JSON.parse("<?= json_encode($col_config['className_text-balance_transactions_report']) ?>" ) ;
        <?php else: ?>
            var text_right_cols = [ 4,5,6 ];
        <?php endif; ?>


        var amtColSummary = 0, totalPerPage=0;
        var dataTable = $('#transaction-table').DataTable({

            autoWidth: false,
            searching: false,
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
                <?php
                    $funcCode = 'export_report_balance_transactions'; // ( $from == "report" ) ? 'export_report_balance_transactions' : 'export_payment_transactions';
                    if( $this->permissions->checkPermissions($funcCode) ) :
                ?>
                ,{
                    text: '<?=lang("CSV Export"); ?>',
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {

                        var form_params=$('#form-filter').serializeArray();

                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                          'draw':1, 'length':-1, 'start':0};

                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/balance_transaction_details'));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();

                    }
                }
                <?php endif; ?>
            ],
            columnDefs: [
                { className: 'text-right', targets: text_right_cols },
                { visible: false, targets: not_visible_cols }
            ],
            order: [[0, 'desc']],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {

                data.extra_search = $('#form-filter').serializeArray();
                $.post(base_url + "api/balanceTransactionHistory", data, function(data) {
                    if (data.summary) {
                        $('#summary').html('');

                        var rows =data.summary, len = data.summary.length,footer='';
                        for(var i=0; i<len; i++){
                          footer += '<div class="col-xs-11" ><?=lang('system.word66')?> '+rows[i].transaction_name+':</div><div class="col-xs-1"><a  href="transaction_type_'+rows[i].transaction_type+'" class="link-to-trans" >'+rows[i].amount+'</a></div>'
                        }

                        $('#summary').append(footer);
                        //attach event listener
                        $('.link-to-trans').on('click', function(){
                            var id = $(this).attr('href');
                            $('input:checkbox.checkall').each(function() {
                                $(this).prop("checked", false);
                            });

                            $('#'+id).prop("checked", true);
                            dataTable.ajax.reload();
                            return false;
                        });
                    }

                    if (data.bank_summary) {
                        $('#bank-summary .row').html('');
                        $.each(data.bank_summary, function(key, value) {
                            if (key == ' - ') {
                                key = '<?=lang('lang.norecyet')?>';
                            }
                            $('#bank-summary .row').append('<div class="col-xs-11">'+key+':</div><div class="col-xs-1">'+value+'</div>');
                        });
                        $('#bank-summary').show();
                    } else {
                        $('#bank-summary').hide();
                    }

                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                      dataTable.buttons().enable();
                    }
                },'json')
            },
            "initComplete": function() {
                var colspanNum = $('#transaction-table thead tr th').length;
                columnVisibilityChange(colspanNum);
            }
        });

        //Send event to iframe parent so that container height will be adjusted
        dataTable.on( 'draw.dt', function () {
            window.parent.$('body').trigger('datatable_drawn');
        });

        $('#hide_main').click(function(){
            setTimeout(function(){
                window.parent.$('body').trigger('datatable_drawn');
            },300);
        });

      	var dateInput = $('#reportrange');
	    var isTime = dateInput.data('time');

	    dateInput.keypress(function(e){
        	e.preventDefault();
        	return false;
        });

	    $('.daterangepicker .cancelBtn ').text('Reset');//.css('display','none');

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

	    $('#form-filter').submit( function(e) {
	    	e.preventDefault();

	    	// -- Check if date is empty
	    	if($.trim($('#reportrange').val()) == ''){
	    		alert('<?=lang("require_date_range_label")?>');
	    		return;
	    	}

	    	dataTable.ajax.reload();
	    });

        $('#form-filter input[type="text"],#form-filter input[type="number"],#form-filter input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                $('#form-filter').trigger('submit');
            }
        });

        $('#btn-reset').on('click', function(e) {

            document.getElementById('form-filter').reset();
            $('#belongAff').prop('disabled', false);

            dateInput.trigger('cancel.daterangepicker');

            $('#form-filter').trigger('submit');

            $('.help-block').html('');
            asDirty=false; aeDirty=false;
            $('#toUsername, #fromUsername').hide();
            $('#fromUsernameId,#toUsernameId').val('');

            e.preventDefault();
        });

        var amountStart =0, amountEnd = 0,asDirty=false, aeDirty=false;


        $('#amountStart').blur(function(){
            amountStart = Number($(this).val());
            asDirty =true;
            if(asDirty && aeDirty){
                if(amountStart > amountEnd){
                    $('#from-to-amount-range').html("<?=lang('player.uab12')?>");
                }else{
                    $('#from-to-amount-range').html('');
              }
            }
        });

        $('#amountEnd').blur(function(){
            amountEnd = Number($(this).val());
            aeDirty=true;
            if(asDirty && aeDirty){
                if(amountEnd < amountStart){
                    $('#from-to-amount-range').html("<?=lang('player.uab11')?>");
                }else{
                    $('#from-to-amount-range').html('');
                }
            }
        });

        var currentUsernameType ={from_type:'',fromUsername:'', to_type: '',toUsername:''};

        $('#from_type').change(function(){
            var self =  $(this);

            if(self.val()) {
                $('#fromUsername').show();
                 // if merong value
                if($('#fromUsername').val()){
                    //validate if previous values is differrent to new from_type value
                    if( currentUsernameType.from_type != getUsergroup(self.val())){
                        validateUsername('fromUsername',$('#fromUsername').val(),self.val());
                    }
                }
            }else{
               $('#fromUsername').val('');
               $('#fromUsernameId').val('');
               $('#fromUsername').hide();
               $('#fromUsername').next('span').html('');
            }
        });

        $('#to_type').change(function(){
            var self =  $(this);

            if(self.val()) {
                $('#toUsername').show();
                // if merong value
                if($('#toUsername').val()){
                   //validate if previous values is differrent to new to_type value
                    if( currentUsernameType.to_type != getUsergroup(self.val())){
                        validateUsername('toUsername',$('#toUsername').val(),self.val());
                    }
                }
            }else{
                $('#toUsername').val('');
                $('#toUsernameId').val('');
                $('#toUsername').hide();
                $('#toUsername').next('span').html('');
            }
        });

        $('#fromUsername').blur(function(){
            var self =  $(this);
            if(self.val()){
               //Prevent repeat checking
                if( (currentUsernameType.fromUsername != self.val()) && (currentUsernameType.from_type != $('#from_type').val()) ){
                    validateUsername('fromUsername', self.val(), $('#from_type').val());
                }
            }else{
                self.next('span').html('');
            }
        });

        $('#toUsername').blur(function(){
            var self =  $(this);
            if(self.val()){
                //Prevent repeat checking
                if( (currentUsernameType.toUsername != self.val()) && (currentUsernameType.to_type != $('#to_type').val()) ){
                    validateUsername('toUsername',self.val(), $('#to_type').val());
                }
            }else{
              self.next('span').html('');
            }
        });

        // payment account list toggle
        $('#payment_account_toggle').hide();
        $('#payment_account_toggle_btn').click(function(){
            $('#payment_account_toggle').toggle();
            if($('#payment_account_toggle_btn span').attr('class') == 'fa fa-plus-circle'){
                $('#payment_account_toggle_btn span').attr('class', 'fa fa-minus-circle');
                $('#payment_account_toggle_btn span').html(' <?=lang("Collapse All")?>');
            }
            else{
                $('#payment_account_toggle_btn span').attr('class', 'fa fa-plus-circle');
                $('#payment_account_toggle_btn span').html(' <?=lang("Expand All")?>');
            }
        });

        function getUsergroup(type){
            if(type == '1'){
                return 'adminusers';
            }
            if(type == '2'){
                return 'player';
            }
            if(type == '3'){
                return 'afilliates';
            }
        }

        function validateUsername(field_id,username,userGroup){

            $('#'+field_id).next('span').html('<i>Checking...</i>').css({color:'#008CBA'});

            var  userType ='' ;
            if(userGroup == '1') {userGroup = 'adminusers'; userType = "<?=lang('transaction.from.to.type.1')?>" }
            if(userGroup == '2') {userGroup = 'player'; userType = "<?=lang('transaction.from.to.type.2')?>" }
            if(userGroup == '3') {userGroup = 'affiliates'; userType = "<?=lang('transaction.from.to.type.3')?>" }

            var data = {username:username, userGroup:userGroup};

            $.ajax({
                url : "<?php echo site_url('payment_management/checkUsernames') ?>",
                type : 'POST',
                data : data,
                dataType : "json",
                cache : false,
            }).done(function (data) {
                if (data.status == "success") {
                    var userId = data.userdata[0].id;
                    $('#'+field_id).next('span').html(data.msg+' &nbsp;&nbsp; (<?=lang("player.uab08")?>: <b>'+userId+'</b> )' ).css({color:'#43AC6A'});

                    $('#'+field_id+'Id').val(userId);//username id
                      //Update currentUsernameType obj for preventing repeat checking
                    if(field_id == 'fromUsername'){
                        currentUsernameType.fromUsername = data.userdata[0].username;
                        currentUsernameType.from_type = userGroup;
                    }else{
                        currentUsernameType.toUsername = data.userdata[0].username;
                        currentUsernameType.to_type = userGroup;
                    }
                }
                if (data.status == "notfound") {
                    $('#'+field_id).next('span').html(data.msg).css({color:'#F04124'});
                    $('#'+field_id+'Id').val('');
                    if(field_id == 'fromUsername'){
                        currentUsernameType.fromUsername = '';
                        currentUsernameType.from_type = '';
                    }else{
                        currentUsernameType.toUsername = '';
                        currentUsernameType.to_type = '';
                    }
                }
            }).fail(function (jqXHR, textStatus) {
                throw textStatus;
            });
        }
    });//doc end


    function maxLengthCheck(object) {
        if (object.value.length > object.maxLength)
            object.value = object.value.slice(0, object.maxLength)
    }

    function clearSelect(id){
        $("#"+id).val(null).trigger("change");
    }

    function checkAll(id) {
        var list = document.getElementsByClassName(id);
        var all = document.getElementById(id);

        if (all.checked) {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 1;
            }
        } else {
            all.checked;

            for (i = 0; i < list.length; i++) {
                list[i].checked = 0;
            }
        }
    }

    //jquery choosen
    $(".chosen-select").chosen({
        disable_search: true,
    });

    var selectedList = [];
    selectedList.push('.buttons-columnVisibility');
    selectedList.push('.buttons-colvisRestore');
    selectedList.push('.buttons-colvisGroup');
    $(document).on("click",selectedList.join(','),function(){
        var colspanNum = $('#transaction-table thead tr th').length;
        columnVisibilityChange(colspanNum);
    });

    function columnVisibilityChange(colspanNum) {
        colspanNum = colspanNum+1;
        $("#transaction-ft-col").attr('colspan',colspanNum);
    }
</script>