<style>
#summaryReportTable{
	overflow-x: scroll;
    width: 100%;
}
</style>
<div class="panel panel-primary">
	<div class="panel-heading custom-ph">
		<h4 class="panel-title">
			<i class="icon-pie-chart"></i>
			<?=lang('report.s08')?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row form-group">
			<div class="col-md-6">
				<label class="control-label"><?=lang('report.sum02')?></label>
	            <input class="form-control dateInput" data-start="#date_from" data-end="#date_to" data-time="false"/>
	            <input type="hidden" id="date_from" name="date_from" data-time="false"/>
	            <input type="hidden" id="date_to" name="date_to" data-time="false"/>
	        </div>
			<div class="col-md-6 monthly-report-box">
				<label class="control-label"><?=lang('Month Only')?> : </label>
	            <input type="checkbox" name="month_only" id="month_only" <?=$month_only=='true' ? 'checked' : ''?>/>
	        </div>
	    </div>
		<div class="row form-group">
			<div class="col-md-2">
				<input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-portage btn-sm">
			</div>
	    </div>
		<div>
			<table class="table table-condensed table-bordered table-hover" id="summary-report" data-searching="false" data-ordering="false" data-page-length="100" style="width:100%">
				<thead>
					<tr>
						<th data-orderable="true" data-data="common_date"><?=lang('report.sum02')?></th>
						<?php 
							$summary_report_fields = [
								'new_players' => 'report.sum05',
								'total_players' => 'report.sum06',
								'first_deposit' => 'report.sum07',
								'second_deposit' => 'report.sum08',
								'total_deposit_players' => 'report.sum22',
								'total_deposit' => 'report.sum09',
								'total_withdraw' => 'report.sum10',
								'total_bonus' => 'report.sum14',
								'total_cashback' => 'report.sum15',
								'percentage_of_bonus_cashback_bet' => 'report.percentage_of_bonus_cashback_bet',
								'total_transaction_fee' => 'report.sum16',
								'total_player_fee' => 'report.sum21',
								'withdraw_fee_from_player' => 'Withdraw Fee from player',
								'withdraw_fee_from_operator' => 'Withdraw Fee from operator',
								'bank_cash_amount' => 'report.sum18',
								'total_bet' => 'Total Bet',
								'total_win' => 'Total Win',
								'total_loss' => 'Total Loss',
								'payout' => 'report.Payout',
								'deposit_member' => 'Deposit Member',
								'active_member' => 'Active Member',
								'retention' => 'Retention',
								'ret_dp' => 'ret_dp',
								'ggr' => 'ggr',
							];
						
							foreach ($summary_report_fields as $key => $value) { 
						?>
							<?php if (!$enable_roles_report || in_array($key,$fields)){ ?>
								<?php if ($key == 'percentage_of_bonus_cashback_bet') { ?>
									<th data-orderable="false" data-data="<?=$key?>" title="<?=lang('report.percentage_of_bonus_cashback_bet.desc')?>" id="<?=lang($value)?>"><?=lang($value)?><i class="fa fa-info-circle"></i></th>
								<?php } else if ($key == 'retention') { ?>
									<th data-orderable="false" data-data="<?=$key?>" title="<?=lang('report.retention_formula')?>" id="<?=lang($value)?>"><?=lang($value)?><i class="fa fa-exclamation-circle"></i></th>
								<?php } else if ($key == 'ret_dp') { ?>
									<th data-orderable="false" data-data="<?=$key?>" title="<?=lang('report.ret_dp_formula')?>" id="<?=lang($value)?>"><?=lang($value)?><i class="fa fa-exclamation-circle"></i></th>
								<?php } else if ($key == 'ggr') { ?>
									<th data-orderable="false" data-data="<?=$key?>" title="<?=lang('report.ggr_formula')?>" id="<?=lang($value)?>"><?=lang($value)?><i class="fa fa-exclamation-circle"></i></th>
								<?php } else { ?>
									<th data-orderable="false" data-data="<?=$key?>"><?=lang($value)?></th>
								<?php } ?> 
							<?php 
							}
						}
						?>
						
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td ><?=lang('Total')?></td>

						<?php foreach ($summary_report_fields as $key => $value) { ?>
						<?php 	if (!$enable_roles_report || in_array($key,$fields)){ ?>
							<td ><span class="<?=$key?>"></span></td>
						<?php 
								}
							}
						?>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
	<div class="panel-footer"></div>
</div>
<div class="modal fade" id="myModal" tabindex="-1" role="document" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document" style="max-width:300px;margin: 30px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?php echo lang('Export Specific Columns') ?></h4>
            </div>
            <div class="modal-body" id="checkboxes-export-selected-columns">
            ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="export-selected-columns" ><?php echo lang('CSV Export'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?=site_url().'resources/datatables/dataTables.buttons.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/jszip.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/buttons.html5.min.js'?>"></script>
<script type="text/javascript">
	var not_visible_target = '';

    <?php if(!empty($this->utils->getConfig('summary_report_columnDefs'))) : ?>
        <?php if(!empty($this->utils->getConfig('summary_report_columnDefs')['not_visible_summary_report'])) : ?>
            not_visible_target = JSON.parse("<?= json_encode($this->utils->getConfig('summary_report_columnDefs')['not_visible_summary_report']) ?>" ) ;
        <?php endif; ?>
    <?php endif; ?>

	var enable_total_shows_results_in_summary_report = '<?= $this->config->item('enable_total_shows_results_in_summary_report') ? 'true' : 'false' ?>';

	var dateFormat = function (data, type, row) {
			return data;
	};

	var dateUrlFormat = function(date) {
		if(date.length == 6) return date;
		return moment(date).format('YYYY/MM/DD');
	}

	var newPlayer = function (data, type, row) {
		return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/report_management/new_members/' + dateUrlFormat(row.slug) + '" target="_blank"><b>' + numeral(data).format('0,0') + '</b></a>');
	};

	var totalPlayer = function (data, type, row) {
		return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/report_management/total_members/' + dateUrlFormat(row.slug) + '" target="_blank"><b>' + numeral(data).format('0,0') + '</b></a>');
	};

	var firstDeposit = function (data, type, row) {
		return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/report_management/first_deposit/' + dateUrlFormat(row.slug) + '" target="_blank"><b>' + numeral(data).format('0,0') + '</b></a>');
	};

	var secondDeposit = function (data, type, row) {
		return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/report_management/second_deposit/' + dateUrlFormat(row.slug) + '" target="_blank"><b>' + numeral(data).format('0,0') + '</b></a>');
	};

    var totalDepositPlayer = function (data, type, row) {
        return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/report_management/total_deposit_members_2/' + dateUrlFormat(row.slug) + '" target="_blank"><b>' + numeral(data).format('0,0') + '</b></a>');
    };

	var totalDeposit = function (data, type, row) {
		// return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/" target="_blank"><b>' + numeral(data).format('0,0.00') + '</b></a>');
		return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00') + '</b>';
	};

	var totalWithdraw = function (data, type, row) {
		// return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/" target="_blank"><b>' + numeral(data).format('0,0.00') + '</b></a>');
		return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00') + '</b>';
	};

	var totalBonus = function (data, type, row) {
		// return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/" target="_blank"><b>' + numeral(data).format('0,0.00') + '</b></a>');
		return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00') + '</b>';
	};

	var totalCashback = function (data, type, row) {
		// return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/" target="_blank"><b>' + numeral(data).format('0,0.00') + '</b></a>');
		return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00') + '</b>';
	};

	var totalFee = function (data, type, row) {
		// return data == 0 ? '<i class="text-muted">N/A</i>' : ('<a href="/" target="_blank"><b>' + numeral(data).format('0,0.00') + '</b></a>');
		return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00') + '</b>';
	};

    var totalPlayerFee = function (data, type, row) {
        return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00') + '</b>';
    };

	var currencyFormat = function (data, type, row) {
		return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00') + '</b>';
	};

	var integerFormat = function (data, type, row) {
		return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0') + '</b>';
	};

	var percentage_of_bonus_cashback_bet = function (data, type, row) {
		return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00%') + '</b>';
	};

	var depositMember = function (data) {
		return data == 0 ? 0 : '<b>' + numeral(data).format('0,0') + '</b>';
	};

	var activeMember = function (data) {
		return data == 0 ? 0 : '<b>' + numeral(data).format('0,0') + '</b>';
	};

    var retentionFormat = function (data) {
        return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00%') + '</b>';
	};

	var retdpFormat = function (data) {
        return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00%') + '</b>';
	};

    var ggrFormat = function (data) {
        return data == 0 ? '<i class="text-muted">N/A</i>' : '<b>' + numeral(data).format('0,0.00%') + '</b>';
	};

	var withdrawFeeFromPlayer = function (data) {
		return data == 0 ? 0.00 : '<b>' + numeral(data).format('0,0.00') + '</b>';
	};

	var withdrawFeeFromOperator = function (data) {
		return data == 0 ? 0.00 : '<b>' + numeral(data).format('0,0.00') + '</b>';
	};

	var currentColumns = [];
	var selectedColumns = [];
    var  PER_COLUMN_CSV_EXPORTER = (function() {
		var table;
		var prefix = 0;
        function render(){
            var len = currentColumns.length,len2 =selectedColumns.length, checkboxes='';
            for(var i=0; i<len; i++){
                checkboxes += '<div class="checkbox">';

                if(len2 > 0){
                    if (selectedColumns.indexOf(currentColumns[i].alias) > -1 ) {
                        checkboxes += '<label><input type="checkbox" class="export-select-checkbox" data-index="'+i+'" checked value="'+currentColumns[i].alias+'">'+currentColumns[i].name+'</label>';
                    } else {
                        checkboxes += '<label><input type="checkbox" class="export-select-checkbox" data-index="'+i+'" value="'+currentColumns[i].alias+'">'+currentColumns[i].name+'</label>';
                    }
                } else {
                    checkboxes += '<label><input type="checkbox" class="export-select-checkbox" data-index="'+i+'" value="'+currentColumns[i].alias+'">'+currentColumns[i].name+'</label>';
                }
                checkboxes += '</div>';
            }
            $('#checkboxes-export-selected-columns').html(checkboxes);
        }

        function attachExportCheckboxesEvent(){
			<?php $d = new DateTime();?>
			var filename = '<?=lang('Summary Report').' '. $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999)?>'
			prefix = Math.floor(Math.random() * 10000);
			var cols = [];
			var reportTh = document.querySelectorAll('#summary-report tr th');
			reportTh.forEach(function(item,val){
				cols.push(item.dataset.data);
			});
            $('.export-select-checkbox').each(function(index, value) {
                $(this).click(function(){
					var exist = selectedColumns.indexOf($(this).data('index'));
					var index = cols.indexOf($(this).val());
                    if (exist > -1) {
						selectedColumns.splice(index, 1);
					}else{
                        selectedColumns.push(index);
					}
					selectedColumns = selectedColumns.sort(function (a, b) {
						return a> b ? 1 : -1;
					});
                })
			});

			table.button().add({
					extend: 'csvHtml5',
					className:'hidden export-select-'+prefix,
					exportOptions: {
						columns: selectedColumns
					},
					text: 'hidden_export-select',
					filename: filename
				})
		}
		$('#export-selected-columns').click(function(){
			$('.export-select-'+prefix).trigger('click');
		})
		$('#myModal').on('hidden.bs.modal', function(){
			selectedColumns = [];
		})

        return {
            openModal:function(columns,selected) {
				currentColumns= columns;
				table = $('#summary-report').DataTable();
                $('#myModal').modal('show');
                render();
                attachExportCheckboxesEvent();
			}
        }
	}());

    $(document).ready(function(){
    	$('#summary_report_2').addClass("active");

    	$('#report_date').daterangepicker({
		    startDate: moment().startOf('month'),
		    endDate: moment().endOf('month')
		});
		$('#date_from').val(('<?= $dateFrom?>' == "") ? moment().startOf('month').format('YYYY-MM-DD') : '<?= $dateFrom?>');
		$('#date_to').val(('<?= $dateTo?>' == "") ? moment().endOf('month').format('YYYY-MM-DD') : '<?= $dateTo?>');
		$('.dateInput').data('daterangepicker').setStartDate('<?=date("Y-m-d", strtotime($dateFrom))?>');
		$('.dateInput').data('daterangepicker').setEndDate('<?=date("Y-m-d", strtotime($dateTo))?>');
		<?php $d = new DateTime(); ?>
		var filenames = '<?=lang('Summary Report').' '. $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999)?>';
		var columns = []
		var reportTh = document.querySelectorAll('#summary-report tr th');
		reportTh.forEach(function(item,val){
			columns.push({alias: item.dataset.data, name: item.innerText});
		});
        var dataTable = $('#summary-report').DataTable({
			<?php if( ! empty($enable_freeze_top_in_list) ): ?>
            scrollY:        1000,
            scrollX:        true,
            deferRender:    true,
            scroller:       true,
            scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

			<?php if($this->utils->isEnabledFeature('column_visibility_report')): ?>
                stateSave: true,
            <?php else: ?>
                stateSave: false,
            <?php endif; ?>

            <?php if( $this->permissions->checkPermissions('export_summary_report_2') ){ ?>
            dom: "<'panel-body' <'pull-right'B><'#export_select_columns.pull-left'><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i><'#summaryReportTable't><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
				{
                    extend: 'colvis',
					postfixButtons: [ 'colvisRestore' ],
					className: "btn-linkwater"
				},
				{
					text: "<?php echo lang('Export Specific Columns') ?>",
					className:"btn btn-sm btn-scooter",
					action: function ( e, dt, node, config ) {
						var  selected = [];
						PER_COLUMN_CSV_EXPORTER.openModal(columns,selected);

					}
				},
				{
					className: "btn btn-sm btn-portage",
                    text: '<?=lang('CSV Export')?>',
					action: function ( e, dt, node, config ) {
						$('.export_all').trigger('click');
						$.ajax({
						  url: '/api/report_summary2/null/null/null/true',
						  type: 'POST',
						  data: {
								filename : filenames
			                }
						});
					}
				},
                {
					extend: 'csvHtml5',
					className:'hidden export_all',
                    text: 'hidden_' + '<?=lang('CSV Export')?>',
                    filename: filenames
				}
            ],
            <?php } ?>
        	columnDefs: [
			{
				render: dateFormat,
				className: 'text-right',
				targets: 0
			}, 
			<?php

				$i  = 1;
				$renderKey = [
					'new_players' => 'newPlayer',
					'total_players' => 'totalPlayer',
					'first_deposit' => 'firstDeposit',
					'second_deposit' => 'secondDeposit',
					'total_deposit_players' => 'totalDepositPlayer',
					'total_deposit' => 'totalDeposit',
					'total_withdraw' => 'totalWithdraw',
					'total_bonus' => 'totalBonus',
					'total_cashback' => 'totalCashback',
					'percentage_of_bonus_cashback_bet' => 'percentage_of_bonus_cashback_bet',
					'total_transaction_fee' => 'totalFee',
					'total_player_fee' => 'totalPlayerFee',
					'withdraw_fee_from_player' => 'withdrawFeeFromPlayer',
					'withdraw_fee_from_operator' => 'withdrawFeeFromOperator',
					'bank_cash_amount' => 'currencyFormat',
					'total_bet' => 'currencyFormat',
					'total_win' => 'currencyFormat',
					'total_loss' => 'currencyFormat',
					'payout' => 'currencyFormat',
					'deposit_member' => 'depositMember',
					'active_member' => 'activeMember',
					'retention' => 'retentionFormat',
					'ret_dp' => 'retdpFormat',
					'ggr' => 'ggrFormat',
				];

				foreach ($summary_report_fields as $key => $lang) {
					if (!$enable_roles_report || in_array($key,$fields)){
						echo "{
							render: ".$renderKey[$key].",
							className: 'text-right',
							targets: ".$i++."
						},";
					}
				}
			?>
				{
				visible:false ,
				targets: not_visible_target
				}
			],
			order: [[0,'desc'] ],
			"initComplete": function(settings){
                $('#summary-report thead th').each(function () {
                    var $td = $(this);
                    if($td.attr("id") == 'percentage_of_bonus_cashback_bet'){
                        $td.attr('title', '<?=lang("report.percentage_of_bonus_cashback_bet.desc")?>');
                    }
				});

                /* Apply the tooltips */
                $('#summary-report thead th[title]').tooltip({
                    "container": 'body'
                });
            },
			"drawCallback": function( settings){ // aka. table.on( 'draw', function () {...
				<?php if( ! empty($enable_freeze_top_in_list) ): ?>
					var _scrollBodyHeight = window.innerHeight;
					_scrollBodyHeight -= $('.navbar-fixed-top').height();
					_scrollBodyHeight -= $('.dataTables_scrollHead').height();
					_scrollBodyHeight -= $('.dataTables_scrollFoot').height();
					_scrollBodyHeight -= $('#myTable_paginate').closest('.panel-body').height();
					_scrollBodyHeight -= 44;// buffer
					$('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});
				<?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
			},
            ajax: function (data, callback, settings) {

				$.post('/api/report_summary2/<?=date("Y-m-d", strtotime($dateFrom))?>/<?=date("Y-m-d", strtotime($dateTo))?>/<?=$month_only?>',
						null, function(data) {
					var new_players=0, total_players=0,
					first_deposit=0, second_deposit=0, total_deposit_players=0,
					total_deposit=0, total_withdraw=0, total_bonus=0,
					total_cashback=0, total_transaction_fee=0, total_player_fee=0,bank_cash_amount=0,
					total_bet=0, total_win=0, total_loss=0, payout=0
                    , retention=0, retention_denominator =0
                    , ret_dp=0, ret_dp_denominator=0
                    , ggr=0
                    , percentage_of_bonus_cashback_bet=0, deposit_member=0, active_member=0, withdraw_fee_from_player=0, withdraw_fee_from_operator=0;

						$.each(data.data, function(i, v){
							// console.log(v);
							// var arr_results = [v['new_players'], v[5], v[7], v[8], v[10], v[11], v[12], v[13], v[14], v[15], v[1], v[2], v[3], v[4]];
							// console.log(arr_results);
							for (var x = 0; x < v.length; x++) {
								// Remove html tags from string
								// arr_results[x] = arr_results[x].replace(/<\/?[^>]+(>|$)/g, "");
								// Remove comma from string

								arr_results[x] = arr_results[x].replace(/,/g , "");

								if (isNaN(arr_results[x])) {
									arr_results[x]  = 0;
								}
							}
							new_players+= parseFloat(v['new_players']);
							// total_players+= parseFloat(v['total_players']);
							first_deposit+= parseFloat(v['first_deposit']);
							second_deposit+= parseFloat(v['second_deposit']);
                            total_deposit_players+= parseFloat(v['total_deposit_players']);
							total_deposit+= parseFloat(v['total_deposit']);
							total_withdraw+= parseFloat(v['total_withdraw']);
							total_bonus+= parseFloat(v['total_bonus']);
							total_cashback+= parseFloat(v['total_cashback']);
							total_transaction_fee+= parseFloat(v['total_transaction_fee']);
                            total_player_fee+= parseFloat(v['total_player_fee']);
							bank_cash_amount+= parseFloat(v['bank_cash_amount']);
							total_bet+= parseFloat(v['total_bet']);
							total_win+= parseFloat(v['total_win']);
							total_loss+= parseFloat(v['total_loss']);
							payout+= parseFloat(v['payout']);
							percentage_of_bonus_cashback_bet = total_bet ? ((total_bonus+total_cashback) / total_bet) * 100 : 0;
							deposit_member+= parseFloat(v['deposit_member']);
							active_member+= parseFloat(v['active_member']);
							withdraw_fee_from_player+= parseFloat(v['withdraw_fee_from_player']);
							withdraw_fee_from_operator+= parseFloat(v['withdraw_fee_from_operator']);
                            retention+= parseFloat(v['retention']);
                            if( v['retention'] > 0 ){
                                retention_denominator++;
                            }
                            ret_dp+= parseFloat(v['ret_dp']);
                            if( v['ret_dp'] > 0 ){
                                ret_dp_denominator++;
                            }
						});

                        if(retention_denominator > 0){
                            retention = (retention / retention_denominator) * 100;
                        }

                        if(ret_dp_denominator > 0){
                            ret_dp = (ret_dp / ret_dp_denominator) * 100;
                        }

                        if(total_bet > 0){
                            ggr = (payout / total_bet) * 100;
                        }else{
                            ggr = 0;
                        }

						if(enable_total_shows_results_in_summary_report && new_players > 0 ){
							$('.new_players').html('<a href="/report_management/new_members/null/null/null/' + '<?=date("Y-m-d", strtotime($dateFrom))?>/<?=date("Y-m-d", strtotime($dateTo))?>' + '" target="_blank"><b>'+numeral(parseFloat(new_players).toFixed(<?=$currency_decimals?>)).format("0,0.00")+'</b></a>');
						}else{
							$('.new_players').text(numeral(parseFloat(new_players).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
						}

                        // $('.total_players').text(numeral(parseFloat(total_players).toFixed(2)).format("0,0.00"));
                        if(enable_total_shows_results_in_summary_report && first_deposit > 0 ){
							$('.first_deposit').html('<a href="/report_management/first_deposit/null/null/null/' + '<?=date("Y-m-d", strtotime($dateFrom))?>/<?=date("Y-m-d", strtotime($dateTo))?>' + '" target="_blank"><b>'+numeral(parseFloat(first_deposit).toFixed(<?=$currency_decimals?>)).format("0,0.00")+'</b></a>');
						}else{
							$('.first_deposit').text(numeral(parseFloat(first_deposit).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
						}

						if(enable_total_shows_results_in_summary_report && second_deposit > 0 ){
							$('.second_deposit').html('<a href="/report_management/second_deposit/null/null/null/' + '<?=date("Y-m-d", strtotime($dateFrom))?>/<?=date("Y-m-d", strtotime($dateTo))?>' + '" target="_blank"><b>'+numeral(parseFloat(second_deposit).toFixed(<?=$currency_decimals?>)).format("0,0.00")+'</b></a>');
						}else{
							$('.second_deposit').text(numeral(parseFloat(second_deposit).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
						}

                        $('.total_deposit_players').text(numeral(parseFloat(total_deposit_players).toFixed(<?=$currency_decimals?>)).format("0,0"));
                        $('.total_deposit').text(numeral(parseFloat(total_deposit).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.total_withdraw').text(numeral(parseFloat(total_withdraw).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.total_bonus').text(numeral(parseFloat(total_bonus).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.total_cashback').text(numeral(parseFloat(total_cashback).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.total_transaction_fee').text(numeral(parseFloat(total_transaction_fee).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.total_player_fee').text(numeral(parseFloat(total_player_fee).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.bank_cash_amount').text(numeral(parseFloat(bank_cash_amount).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.total_bet').text(numeral(parseFloat(total_bet).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.total_win').text(numeral(parseFloat(total_win).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.total_loss').text(numeral(parseFloat(total_loss).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.payout').text(numeral(parseFloat(payout).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.percentage_of_bonus_cashback_bet').text(numeral(parseFloat(percentage_of_bonus_cashback_bet).toFixed(<?=$currency_decimals?>)).format("0,0.00")+'%');
                        $('.deposit_member').text(numeral(parseFloat(deposit_member).toFixed(<?=$currency_decimals?>)).format("0,0"));
                        $('.active_member').text(numeral(parseFloat(active_member).toFixed(<?=$currency_decimals?>)).format("0,0"));
                        $('.withdraw_fee_from_player').text(numeral(parseFloat(withdraw_fee_from_player).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.withdraw_fee_from_operator').text(numeral(parseFloat(withdraw_fee_from_operator).toFixed(<?=$currency_decimals?>)).format("0,0.00"));
                        $('.retention').text(numeral(parseFloat(retention).toFixed(<?=$currency_decimals?>)).format("0,0.00")+"%");
                        $('.ret_dp').text(numeral(parseFloat(ret_dp).toFixed(<?=$currency_decimals?>)).format("0,0.00")+"%");
                        $('.ggr').text(numeral(parseFloat(ggr).toFixed(<?=$currency_decimals?>)).format("0,0.00")+"%");

                        <?php if( $this->utils->getConfig('enabled_count_distinct_total_active_members') ){ ?>
							if (typeof data.distinct_active_members != 'undefined') {
								$('.active_member').text(numeral(parseFloat(data.distinct_active_members).toFixed(<?=$currency_decimals?>)).format("0,0"));
							}
						<?php } ?>

						<?php if( $this->utils->getConfig('enabled_count_distinct_deposit_members') ){ ?>
							if (data?.distinct_deposit_members?.count_deposit_member) {
								$('.deposit_member').text(numeral(parseFloat(data.distinct_deposit_members.count_deposit_member).toFixed(<?=$currency_decimals?>)).format("0,0"));
							}
						<?php } ?>

						callback(data);

						var isCheck_month = $("#month_only").is(":checked");
						if (isCheck_month) {
							$("#retention").attr('title', "<?=lang("report.retention_formula.month")?>");
							$("#ret_dp").attr('title', "<?=lang("report.ret_dp_formula.month")?>");
                            $("#ggr").attr('title', "<?=lang("report.ggr_formula.month")?>");
						}
					}
				)

			}
        } );
	});
	$(document).on("click",".buttons-colvis",function(){
		var columns = [];
		$("#summary-report th").each(function(index){
			var text = $(this).text();
			if($(this).data('data') == 'percentage_of_bonus_cashback_bet') {
				columns.push('<?=trim(lang('report.percentage_of_bonus_cashback_bet'))?>');
			} else {
				columns.push(text);
			}
		});
		$(".buttons-columnVisibility").each(function(){
			var text = $(this).text();
			if(columns.indexOf(text.trim()) > -1 || text =='<?=trim(lang('report.percentage_of_bonus_cashback_bet'))?>') {
				$(this).addClass('active');
			} else {
				$(this).removeClass('active');
			}
		});
	});

	$(document).on("click",".buttons-columnVisibility",function(){
		var active = $(this).hasClass('active');
		if(active){
			$(this).removeClass('active');
		} else{
			$(this).addClass('active');
		}
	});
	$(document).on("click",".buttons-colvisRestore",function(){
		$('.buttons-columnVisibility').addClass('active');
	});
    $("#search_main").click(function() {
    	var url = "<?php echo site_url('/report_management/summary_report_2'); ?>/" + moment($('input[name="date_from"]').val()).format('YYYY-MM-DD') + "/" + moment($('input[name="date_to"]').val()).format('YYYY-MM-DD')+"/"+($("#month_only").is(":checked") ? "true" : "false");
        window.location.href = url;
    });
</script>
