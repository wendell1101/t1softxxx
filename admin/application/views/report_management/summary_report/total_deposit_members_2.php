<ol class="breadcrumb">
	<?php if ($day): ?>
		<li><a href="/report_management/summary_report_2"><?=lang('report.s08')?></a></li>
		<li><a href="/report_management/total_deposit_members_2"><?=lang('report.sum22')?></a></li>
		<li><a href="/report_management/total_deposit_members_2/<?=$year?>"><?=$year?></a></li>
		<li><a href="/report_management/total_deposit_members_2/<?=$year?>/<?=$month?>"><?=$month?></a></li>
		<li class="active"><?=$day?></li>
	<?php elseif ($month): ?>
		<li><a href="/report_management/summary_report_2"><?=lang('report.s08')?></a></li>
		<li><a href="/report_management/total_deposit_members_2"><?=lang('report.sum22')?></a></li>
		<li><a href="/report_management/total_deposit_members_2/<?=$year?>"><?=$year?></a></li>
		<li class="active"><?=$month?></li>
	<?php elseif ($year): ?>
		<li><a href="/report_management/summary_report_2"><?=lang('report.s08')?></a></li>
		<li><?=lang('report.sum22')?></li>
		<li class="active"><?=$year?></li>
	<?php else: ?>
		<li class="active"><?=lang('report.s08')?></li>
	<?php endif ?>
</ol>

<div class="panel panel-primary">
	<div class="panel-heading custom-ph">
		<h4 class="panel-title">
			<?=implode('-', array_filter( [$year, $month, $day] ) ) . ' ' . lang('report.sum22')?>
		</h4>
	</div>
	<div class="panel-body">
		<table class="table table-condensed table-bordered table-hover" id="summary-report-total-deposit-memeber">
			<thead>
				<tr>
					<th data-data="username"><?=lang('Username')?></th>
                    <th data-data="deposit_count"><?=lang('Deposit Count')?></th>
                    <th data-data="deposit_amount"><?=lang('report.sum09')?></th>
                    <th data-data="withdrawal_amount"><?=lang('report.sum10')?></th>
                    <th data-data="profit_amount"><?=lang('Deposit - Withdraw')?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div class="panel-footer"></div>
</div>

<script type="text/javascript">

	var username = function (data, type, row) {
		return '<a href="/player_management/player/' + data + '">' + data + '</a>';
	}

    var currencyFormat = function (data, type, row) {
        return '<b>' + numeral(data).format('0,0.00') + '</b>';
    }

    var integerFormat = function (data, type, row) {
        return '<b>' + numeral(data).format('0,0') + '</b>';
    }

    $(document).ready(function(){
        $('#summary-report-total-deposit-memeber').DataTable({
        	columnDefs: [
        	    {
                    render: username,
                    className: 'text-right',
                    targets: 0,
                },{
                    render: integerFormat,
                    className: 'text-right',
                    targets: 1,
                },{
                    render: currencyFormat,
                    className: 'text-right',
                    targets: 2,
                },{
                    render: currencyFormat,
                    className: 'text-right',
                    targets: 3,
                },{
                    render: currencyFormat,
                    className: 'text-right',
                    targets: 4,
                }
			],
            ajax:
				<?php if ($day): ?>
					'/api/total_deposit_members_2/<?=$year?>/<?=$month?>/<?=$day?>',
				<?php elseif ($month): ?>
					'/api/total_deposit_members_2/<?=$year?>/<?=$month?>',
				<?php else: ?>
					'/api/total_deposit_members_2/<?=$year?>',
				<?php endif ?>
        });
    });
</script>
