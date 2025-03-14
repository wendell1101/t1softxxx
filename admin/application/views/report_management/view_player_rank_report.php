<style>
	.viewDetailsBtn {
		margin: 0 1rem;
	}
</style>

<!-- enabled_player_score && viewRankReport -->
<div class="panel panel-primary hidden">
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fa fa-search"></i> <?=lang("lang.search")?>
			<span class="pull-right">
				<a data-toggle="collapse" href="#collapsePlayerGradeReport" class="btn btn-xs btn-primary"></a>
			</span>
		</h4>
	</div>
	<div id="collapsePlayerGradeReport" class="panel-collapse">
		<div class="panel-body">
			<form id="form-filter" class="form-horizontal" method="post">
				<div class="row">
					<div class="col-md-3">
						<label for="rank_key" class="control-label"><?=lang('player_rank_report.rank_name')?></label>
						<select name="rankKey"  class="form-control">
							<?php if(!empty($getAllRankKey) && is_array($getAllRankKey)): ?>
								<?php foreach($getAllRankKey as $key => $value['rank_key']): ?>
									<option value="<?= $value['rank_key'] ?>"><?= $value['rank_key'] ?></option>
								<?php endforeach; ?>
							<?else:?>
								<option value="">N/A</option>
							<?php endif;?>
						</select>
					</div>
					<div class="col-md-3">
						<label for="username" class="control-label"><?=lang('Player Username')?></label>
						<input type="text" name="username" class="form-control" />
					</div>
					<div class="col-md-2">
						<label for="rank" class="control-label"><?=lang('player_rank_report.exact_rank')?></label>
						<input type="number" name="rank" class="form-control" />
					</div>
					<div class="col-md-2">
						<label for="rank" class="control-label"><?=lang('player_rank_report.rank_bigger_than')?></label>
						<input type="number" name="rankGreaterThan" class="form-control" />
					</div>
					<div class="col-md-2">
						<label for="rank" class="control-label"><?=lang('player_rank_report.rank_less_than')?></label>
						<input type="number" name="rankLessThan" class="form-control" />
					</div>
				</div>

				<div class="row">
					<div class="col-md-12" style="padding-top: 20px">
						<input class="btn btn-sm btn-linkwater" type="reset" value="Reset">
						<input type="submit"
							value="<?=lang('lang.search')?>"
							id="search_main" class="btn btn-sm btn-portage">
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h4 class="panel-title"><i class="fa fa-list"></i> <?=lang('player_rank_report.title')?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-bordered table-hover" id="myTable">
				<thead>
					<tr>
						<th><?=lang('player_rank_report.rank')?>
						</th>
						<th><?=lang('Player Username')?>
						</th>
						<th><?=lang('Total Score')?>
						</th>
						<th><?=lang('Last Updated On')?>
						</th>
						<th><?=lang('player_rank_report.rank_name')?>
						</th>
						<th><?=lang('lang.detail')?>
						</th>
					</tr>
				</thead>
			</table>
		</div>

	</div>
	<div class="panel-footer"></div>
</div>

<div class="modal fade" id="modal_scoredetail" tabindex="-1" role="dialog" data-backdrop="false" data-keyboard="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">

			</div>
			<div class="modal-body">
				<table class="table table-bordered table-hover">
					<thead>
						<tr>
							<th><?=lang('Type')?>
							</th>
							<th><?=lang('Total')?>
							</th>
							<th><?=lang('Score')?>
							</th>
						</tr>
					</thead>
					<tbody>
						<!-- deposit -->
						<tr id="deposit-details">
							<td><?=lang('Deposit')?>
							</td>
							<td class="item-total">0</td>
							<td class="item-score">0</td>
						</tr>
						<!-- bet -->
						<tr id="bet-details">
							<td><?=lang('Bet')?>
							</td>
							<td class="item-total">0</td>
							<td class="item-score">0</td>
						</tr>
						<!-- win -->
						<tr id="win-details">
							<td><?=lang('Win')?>
							</td>
							<td class="item-total">0</td>
							<td class="item-score">0</td>
						</tr>
						<!-- referrer -->
						<tr id="fr-details">
							<td><?=lang('player.fr01')?>
							</td>
							<td class="item-total">0</td>
							<td class="item-score">0</td>
						</tr>
						<!-- adjustment -->
						<tr id="adjustment-details">
							<td><?=lang('Manual Adjustment')?>
							</td>
							<td class="item-total"> - </td>
							<td class="item-score">0</td>
						</tr>
					</tbody>
					<tfoot>
						<tr id="total-score">
							<th><?=lang('Total Score')?></th>
							<td> - </td>
							<td class="item-score">0</td>
						</tr>
					</tfoot>
				</table>

			</div>
			<div class="modal-footer">
				<button type="button" id="close_btn" class="btn btn-primary scoredetail_btn no"><?=lang('Close')?></button>
			</div>
		</div>
	</div>
</div>
<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>
<script type="text/javascript">
	$(document).ready(function() {
		var dataTable = $('#myTable').DataTable({
			autoWidth: false,
			searching: false,
			sort: false,
			dom: "<'panel-body' <'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
			columnDefs: [{
				className: 'text-right',
				targets: []
			}],
			buttons: [{
					extend: 'colvis',
					postfixButtons: ['colvisRestore'],
					className: 'btn-linkwater',
				}
				<?php if ($export_report_permission) : ?>
				, {
					text: "<?php echo lang('CSV Export'); ?>",
					className: 'btn btn-sm btn-portage export-all-columns export_excel',
					action: function(e, dt, node, config) {

						var form_params = $('#form-filter').serializeArray();
						var d = {
							'extra_search': form_params,
							'export_format': 'csv',
							'export_type': export_type,
							'draw': 1,
							'length': -1,
							'start': 0
						};
						utils.safelog(d);
						$("#_export_excel_queue_form").attr('action', site_url(
							'/export_data/rank_report'));
						$("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
						$("#_export_excel_queue_form").submit();
					}
				}
				<?php endif; ?>
			],
			order: [
				[0, "asc"]
			],
			processing: true,
			serverSide: true,
			ajax: function(data, callback, settings) {
				data.extra_search = $('#form-filter').serializeArray();
				$.post(base_url + "api/playerRankReports", data, function(data) {
					callback(data);
				}, 'json');
			},
		});

		$('#form-filter').submit(function(e) {
			e.preventDefault();
			dataTable.ajax.reload();
		});

		$('.export_excel').click(function() {
			var d = {
				'extra_search': $('#form-filter').serializeArray(),
				'draw': 1,
				'length': -1,
				'start': 0
			};
			$.post(site_url('/export_data/rank_report'), d, function(data) {
				if (data && data.success) {
					$('body').append('<iframe src="' + data.link +
						'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>'
					);
				} else {
					alert('export failed');
				}
			});
		});
	});

	function viewScoreDetails(playerId, username, totalScore) {
		var modal = $('#modal_scoredetail').modal({
			"show": false
		});
		$("#close_btn").on('click', function() {
			modal.modal('hide');
		});

		fetch(base_url + "api/getScoreDetails/" + playerId)
			.then(function(response) {
				return response.json();
			})
			.then(function(detailJson) {
				modal.modal('show');
				$('.modal-header').text('Score Details : ' + username);
				
				if(detailJson.status == 'success'){

					var details = detailJson.details;
					var scoreDetails = details.action_log? JSON.parse(details.action_log): false;
					$('#deposit-details .item-total').text(scoreDetails.total_deposit||0);
					$('#deposit-details .item-score').text(scoreDetails.deposit_score||0);

					$('#bet-details .item-total').text(scoreDetails.total_bet||0);
					$('#bet-details .item-score').text(scoreDetails.bet_score||0);

					$('#win-details .item-total').text(scoreDetails.total_win||0);
					$('#win-details .item-score').text(scoreDetails.win_score||0);

					$('#fr-details .item-total').text(scoreDetails.total_referral||0);
					$('#fr-details .item-score').text(scoreDetails.referral_score||0);

					// $('#adjustment-details .item-total').text(details.manual_score||0);
					$('#adjustment-details .item-score').text(details.manual_score||0);
					$('#total-score .item-score').text(totalScore||0);
				} else {
					$('#deposit-details .item-total').text(0);
					$('#deposit-details .item-score').text(0);

					$('#bet-details .item-total').text(0);
					$('#bet-details .item-score').text(0);

					$('#win-details .item-total').text(0);
					$('#win-details .item-score').text(0);

					$('#fr-details .item-total').text(0);
					$('#fr-details .item-score').text(0);

					// $('#adjustment-details .item-total').text(0);
					$('#adjustment-details .item-score').text(0);
					$('#total-score .item-score').text(0);
				}

			});
	}
</script>