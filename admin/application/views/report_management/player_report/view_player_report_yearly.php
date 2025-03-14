<form class="form-horizontal" action="<?=BASEURL . 'report_management/searchPlayerReport'?>" method="post" role="form" name="myForm">
	<!--main-->
	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-primary
              " style="margin-bottom:10px;">
				<div class="panel-heading">
					<h4 class="panel-title">
						<i class="icon-search" id="hide_main_up"></i> <?=lang('lang.search');?>
						<a href="#main"
              class="btn btn-default btn-sm pull-right hide_sortby">
							<i class="glyphicon glyphicon-chevron-up hide_sortby_up"></i>
						</a>
						<div class="clearfix"></div>
					</h4>
				</div>
				<div class="panel-body sortby_panel_body">
                    <?php
$period = $this->session->userdata('period');
$start_date = $this->session->userdata('start_date');
$end_date = $this->session->userdata('end_date');
$date_range_value = $this->session->userdata('date_range_value');

$depamt1 = $this->session->userdata('depamt1');
$depamt2 = $this->session->userdata('depamt2');

$widamt1 = $this->session->userdata('widamt1');
$widamt2 = $this->session->userdata('widamt2');

$status = $this->session->userdata('report_player_status');
$playerlevel = $this->session->userdata('report_player_level');
$username = $this->session->userdata('report_player_username');
?>
					<div class="form-group" style="margin-bottom:0;">
						<div class="col-md-3">
							<label class="control-label" for="period"><?=lang('report.sum01');?></label>
							<select class="form-control input-sm" name="period" onchange="checkPeriod(this)">
								<option value=""><?=lang('report.pr24');?></option>
	                            <option value="daily" <?=($period == 'daily') ? 'selected' : ''?> ><?=lang('report.pr25');?></option>
	                            <option value="weekly" <?=($period == 'weekly') ? 'selected' : ''?> ><?=lang('report.pr26');?></option>
	                            <option value="monthly" <?=($period == 'monthly') ? 'selected' : ''?> ><?=lang('report.pr27');?></option>
	                            <option value="yearly" <?=($period == 'yearly') ? 'selected' : ''?> ><?=lang('report.pr28');?></option>
	                        </select>
						</div>
						<!-- <div class="col-md-4">
							<label class="control-label" for="start_date"><?=lang('report.sum03');?>: </label>
							<input type="date" name="start_date" id="start_date" class="form-control input-sm" <?=($start_date == null) ? 'disabled' : ''?> value="<?=(!empty($start_date)) ? $start_date : ''?>">
						</div>
						<div class="col-md-4">
							<label class="control-label" for="end_date"><?=lang('report.sum04');?>: </label>
							<input type="date" name="end_date" id="end_date" class="form-control input-sm" <?=($end_date == null) ? 'disabled' : ''?> value="<?=(!empty($end_date)) ? $end_date : ''?>">
						</div> -->
						<div class="col-md-3">
							<label class="control-label" for="period"><?=lang('report.sum02');?></label>
                            <input type="text" id="reportrange" class="form-control input-sm dateInput" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd"/>
                            <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart" value="<?=$start_date == '' ? '' : $start_date;?>" />
                            <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd" value="<?=$end_date == '' ? '' : $end_date;?>" />
	                    </div>
						<div class="col-md-3">
							<label class="control-label" for="depamt1"><?=lang('report.pr31') . " <=";?></label>
							<input type="text" name="depamt1" id="depamt1" class="form-control input-sm number_only" value="<?=(!empty($depamt1)) ? $depamt1 : ''?>"/>
						</div>
						<div class="col-md-3">
							<label class="control-label" for="depamt2"><?=lang('report.pr31') . " >=";?></label>
							<input type="text" name="depamt2" id="depamt2" class="form-control input-sm number_only" value="<?=(!empty($depamt2)) ? $depamt2 : ''?>"/>
						</div>
					</div>
					<div class="form-group" style="margin-bottom:0;">
						<div class="col-md-3">
							<label class="control-label" for="widamt1"><?=lang('report.pr32') . " <=";?></label>
							<input type="text" name="widamt1" id="widamt1" class="form-control input-sm number_only" value="<?=(!empty($widamt1)) ? $widamt1 : ''?>"/>
						</div>
						<div class="col-md-3">
							<label class="control-label" for="widamt2"><?=lang('report.pr32') . " >=";?></label>
							<input type="text" name="widamt2" id="widamt2" class="form-control input-sm number_only" value="<?=(!empty($widamt2)) ? $widamt2 : ''?>"/>
						</div>
						<div class="col-md-3">
							<label class="control-label" for="status"><?=lang('lang.status');?></label>
							<select class="form-control input-sm" name="status">
								<option value="0" <?=($status == '0') ? 'selected' : ''?>><?=lang('player.14');?></option>
	                            <option value="1" <?=($status == '1') ? 'selected' : ''?>><?=lang('player.15');?></option>
	                        </select>
						</div>
						<div class="col-md-3">
							<label class="control-label" for="playerlevel"><?=lang('report.pr03');?></label>
							<select name="playerlevel" id="playerlevel" class="form-control input-sm">
								<option value=""><?=lang('player.08');?></option>
		                        <?php foreach ($allLevels as $key => $value) {?>
		                            <option value="<?=$value['vipsettingcashbackruleId']?>" <?=($playerlevel == $value['vipsettingcashbackruleId']) ? 'selected' : ''?>><?=lang($value['groupName']) . ' ' . lang($value['vipLevel'])?></option>
		                        <?php }
?>
	                        </select>
						</div>
					</div>
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="username"><?=lang('report.pr01');?></label>
							<input type="text" name="username" id="username" class="form-control input-sm" value="<?=(!empty($username)) ? $username : ''?>"/>
						</div>
						<div class="col-md-1" style="text-align:center;padding-top:24px;">
							<input type="submit" value="<?=lang('lang.search');?>" id="search_main"class="btn col-md-12 btn-info btn-sm">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--end of main-->
</form>

<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 class="panel-title pull-left"><i class="glyphicon glyphicon-list-alt"></i> <?=lang('report.s09');?> </h4>
				<div class="pull-right">
                    <?php if ($export_report_permission) {?>
                        <a href="<?=BASEURL . 'report_management/exportPlayerReportToExcel'?>" >
                            <span data-toggle="tooltip" title="<?=lang('lang.exporttitle');?>" class="btn btn-xs btn-success" data-placement="top"><?=lang('lang.export');?>
                            </span>
                        </a>
                    <?php }
?>
                </div>
				<div class="clearfix"></div>
			</div>

			<div class="panel panel-body" id="playerreport_panel_body">
				<div class="col-md-12">
					<table class="table table-striped table-hover" style="margin: 0px 0 0 0; width: 100%;" id="playerTable">
						<thead>
							<tr>
								<th></th>
								<th class="input-sm"><?=lang('report.pr23');?></th>
								<th class="input-sm"><?=lang('report.pr30');?></th>
								<th class="input-sm"><?=lang('report.pr29');?></th>
								<th class="input-sm"><?=lang('report.pr15');?></th>
								<th class="input-sm"><?=lang('report.pr16');?></th>
								<th class="input-sm"><?=lang('report.pr17');?></th>
								<th class="input-sm"><?=lang('report.pr18');?></th>
								<th class="input-sm"><?=lang('report.pr19');?></th>
								<th class="input-sm"><?=lang('report.pr20');?></th>
								<th class="input-sm"><?=lang('report.pr21');?></th>
								<th class="input-sm"><?=lang('report.pr22');?></th>
							</tr>
						</thead>

						<tbody>
							<?php if (!empty($player_report)) {
	?>
								<?php
foreach ($player_report as $key => $value) {
		?>
									<tr>
										<td></td>
										<td class="input-sm">
											<a href="<?=BASEURL . 'report_management/viewPlayerReportMonthly/' . urlencode($value['first_date']) . "/" . urlencode($value['last_date'])?>">
												<?=$value['date']?>
											</a>
										</td>
										<td class="input-sm"><?=$value['total_player']?></td>
										<td class="input-sm"><?=$value['registered_player']?></td>
										<td class="input-sm"><?=$value['total_deposit_bonus']?></td>
										<td class="input-sm"><?=$value['total_cashback_bonus']?></td>
										<td class="input-sm"><?=$value['total_referral_bonus']?></td>
										<td class="input-sm"><?=$value['total_bonus']?></td>
										<td class="input-sm"><?=$value['total_first_deposit']?></td>
										<td class="input-sm"><?=$value['total_second_deposit']?></td>
										<td class="input-sm"><?=$value['total_deposit']?></td>
										<td class="input-sm"><?=$value['total_withdrawal']?></td>
									</tr>
							<?php
}
}
?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        $('#playerTable').DataTable( {
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            "columnDefs": [ {
                className: 'control',
                orderable: false,
                targets:   0
            } ],
            "order": [ 1, 'asc' ]
        } );
    });
</script>
