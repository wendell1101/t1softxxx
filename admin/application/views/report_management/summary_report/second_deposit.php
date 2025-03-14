<style type="text/css">
	.addTotalAmount{
		color: red;
		font-size: 14px;
	}
	.amountTotal{
		color: blue;
	}
</style>
<ol class="breadcrumb">
	<?php if ($day): ?>
		<li><a href="/report_management/summary_report"><?=lang('Summary Report')?></a></li>
		<li><a href="/report_management/second_deposit"><?= lang('report.sum08'); ?></a></li>
		<li><a href="/report_management/second_deposit/<?=$year?>"><?=$year?></a></li>
		<li><a href="/report_management/second_deposit/<?=$year?>/<?=$month?>"><?=$month?></a></li>
		<li class="active"><?=$day?></li>
	<?php elseif ($month): ?>
		<li><a href="/report_management/summary_report"><?=lang('Summary Report')?></a></li>
		<li><a href="/report_management/second_deposit"><?= lang('report.sum08'); ?></a></li>
		<li><a href="/report_management/second_deposit/<?=$year?>"><?=$year?></a></li>
		<li class="active"><?=$month?></li>
	<?php elseif ($year): ?>
		<li><a href="/report_management/summary_report"><?=lang('Summary Report')?></a></li>
		<li><?= lang('report.sum08'); ?></li>
		<li class="active"><?=$year?></li>
	<?php else: ?>
		<li class="active"><?=lang('Summary Report')?></li>
	<?php endif ?>
</ol>

<div class="panel panel-primary">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i><?=lang('lang.search')?><span class="pull-right">
                <a data-toggle="collapse" href="#collapsePromotionReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?>"></a>
            </span>

		<script type="text/javascript" src="<?=site_url('resources/third_party/clipboard/clipboard.min.js?v=3.01.00.0020')?>"></script>
        </h4>
    </div>


    <div id="collapsePromotionReport" class="panel-collapse ">
        <div class="panel-body">
            <form action="" method="get">

                <div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<label class="control-label"><?=lang('Player Tag')?>:</label>
							<select name="tag_list[]" id="tag_list" multiple="multiple" class="form-control input-md">
								<?php if (!empty($tags)): ?>
									<option value="notag" id="notag" <?=is_array($selected_tags) && in_array('notag', $selected_tags) ? "selected" : "" ?>><?=lang('player.tp12')?></option>
									<?php foreach ($tags as $tag): ?>
										<option value="<?=$tag['tagId']?>" <?=is_array($selected_tags) && in_array($tag['tagId'], $selected_tags) ? "selected" : "" ?> ><?=$tag['tagName']?></option>
									<?php endforeach ?>
								<?php endif ?>
							</select>
						</div>
					</div>
                    <div class="col-md-2">
						<div class="form-group">
                            <label class="control-label" for="affiliate_username"><?= lang('Affiliate Username'); ?> </label>
                            <input id="affiliate_username" type="text" name="affiliate_username" class="form-control input-sm" value="<?= isset($affiliate_username) ? $affiliate_username : '' ?>"/>
                        </div>
					</div>
                </div>
                <div class="row">
                    <div class="col-md-1" style="padding-top: 20px">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>">
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
	<div class="panel-heading custom-ph">
		<h4 class="panel-title">
			<?php if (isset($dateFrom) && isset($dateTo) && !empty($dateFrom) && !empty($dateTo)): ?>
				<?= "{$dateFrom} to {$dateTo}" ?>
			<?php else: ?>
				<?=implode('-', array_filter(array($year,$month,$day)))?>
			<?php endif ?>
			<?= lang('report.sum08'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<table class="table table-condensed table-bordered table-hover" id="summary-report" style="width:100%">
			<thead>
				<tr>
					<th data-data="username"><?=lang('Username')?></th>
					<th data-data="aff_username"><?= lang('Affiliate Username') ?></th>
					<th data-data="date"><?=lang('Second deposit date')?></th>
					<th data-data="amount"><?=lang('Deposit Amount')?></th>
					<th data-data="transactionId"><?=lang('Transaction Number')?></th>
					<th data-data="player_tag"><?=lang('Player Tag')?></th>
				</tr>
			</thead>
			<tfoot class="summary-report-ft">
                <tr>
					<th class="summary-report-ft-col" ><?= lang('Sub-total') ?>:</th>
					<th></th>
					<th></th>
					<th class="summary-report-ft-col amount-col" >0</th>
					<th></th>
					<th></th>
                </tr>
            </tfoot>
		</table>
	</div>
	<div class="panel-footer"></div>
</div>
<script type="text/javascript" src="<?=site_url().'resources/datatables/dataTables.buttons.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/jszip.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/buttons.html5.min.js'?>"></script>
<script type="text/javascript">

	var username = function (data, type, row) {
		return '<a href="/player_management/player/' + data + '">' + data + '</a>';
	}

    $(document).ready(function(){

    	$('#report_date').daterangepicker({
		    startDate: moment().startOf('month'),
		    endDate: moment().endOf('month'),
		});

		$('#report_date_from').val(moment().startOf('month').format('YYYY-MM-DD'));
		$('#report_date_to').val(moment().endOf('month').format('YYYY-MM-DD'));
		<?php $d = new DateTime(); ?>

        var ajax_uri =
			<?php if ($day): ?>
				'/api/second_deposit/<?=$year?>/<?=$month?>/<?=$day?>'
			<?php elseif ($month): ?>
				'/api/second_deposit/<?=$year?>/<?=$month?>'
			<?php elseif ($year): ?>
				'/api/second_deposit/<?=$year?>';
			<?php else: ?>
				'/api/second_deposit/null/null/null/<?=$dateFrom?>/<?=$dateTo?>';
			<?php endif ?>
		;

		var post_data = {};

		<?php if(!empty($selected_tags)) {

			$tags = json_encode($selected_tags);

		?>

			var post_data = {"tags" : '<?=$tags?>'}

		<?php } ?>

        <?php if(!empty($affiliate_username)) { ?>
			post_data['affiliate_username'] = '<?=$affiliate_username?>';
		<?php } ?>

        var dataTable = $('#summary-report').DataTable( {

        	<?php if( $this->permissions->checkPermissions('export_deposit_report') ){ ?>
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l>" +
            "<'dt-information-summary1 text-info pull-left' i>t<'text-center'r>" +
            "<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: ':visible'
                    },
                    className:'btn btn-sm btn-primary',
                    text: '<?=lang('CSV Export')?>',
                    filename:  '<?=lang('report.sum08').' '. $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999)?>'
                }
            ],
            <?php } ?>

        	columnDefs: [{
				render: username,
				targets: 0,
			}],
			order: [[2, 'desc']],

            ajax: function (data, callback, settings) {
				$.post(ajax_uri, post_data, function(data) {
					console.log('second_deposit.data', data);
					callback(data);
				},'json').done(function(data, textStatus, jqXHR) {
					// calcSubtotal();
					console.log('second_deposit.data', data);
					var addTotalAmount = '<span class="addTotalAmount" style="margin-left:2rem">' + data.total.totalLang + '</span>';
					$('#summary-report_info').append(addTotalAmount);
                });
        	}
       });

		dataTable.on('page.dt', function() {
			calcSubtotal();
        });

        dataTable.on('draw', function() {
			calcSubtotal();
        });

        $('#search-form').submit(function(e) {
        	e.preventDefault();
            dataTable.ajax.reload();
        })

		$('#tag_list').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonClass: 'btn btn-sm btn-default',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Tags');?>';
                } else {
                    var labels = [];
                    options.each(function() {
                        if ($(this).attr('label') !== undefined) {
                            labels.push($(this).attr('label'));
                        }
                        else {
                            labels.push($(this).html());
                        }
                    });
                    return labels.join(', ') + '';
                }
            }
        });
    });

	function calcSubtotal(){
		var subtotal = 0.0;
		var values = [];
		$('#summary-report tbody tr td:nth-child(4)').each(function() {
			var val = parseFloat($(this).text().split(',').join(''));
			values.push(val);
			subtotal += val;
		});
		// console.log('values', values);

		$('.summary-report-ft-col.amount-col').text( numeral(subtotal).format('0,0.000') );
	}
</script>