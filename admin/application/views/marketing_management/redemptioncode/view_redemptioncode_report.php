<style>
	.viewDetailsBtn {
		margin: 0 1rem;
	}
</style>

<!-- view_redemptioncode_report -->
<div class="panel panel-primary">
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fa fa-search"></i> <?= lang("lang.search") ?>
			<span class="pull-right">
				<a data-toggle="collapse" href="#collapsePlayerGradeReport" class="btn btn-xs btn-primary"></a>
			</span>
		</h4>
	</div>
	<div id="collapsePlayerGradeReport" class="panel-collapse">
		<div class="panel-body">
			<form id="form-filter" class="form-horizontal" method="get" action="<?= site_url('/marketing_management/redemptionCodeList'); ?>">
				<div class="row">
					<div class="col-md-3">
						<label for="codeType" class="control-label"><?= lang('redemptionCode.categoryName') ?></label>
						<select name="codeType" class="form-control">
							<option value="All" <?= $conditions['codeType'] == 'All' ? 'selected' : '' ?>><?= lang('All') ?></option>

							<?php if (!empty($redemptionCodeCategorys) && is_array($redemptionCodeCategorys)) : ?>
								<?php foreach ($redemptionCodeCategorys as $key => $value) : ?>
									<option value="<?= $value['id'] ?>" <?= $conditions['codeType'] == $value['id'] ? 'selected' : '' ?>><?= $value['category_name'] ?></option>
								<?php endforeach; ?>
							<? else : ?>
								<option value="">N/A</option>
							<?php endif; ?>
						</select>
					</div>
					<div class="col-md-3">
						<label for="redemptionCode" class="control-label"><?= lang('redemptionCode.redemptionCode') ?></label>
						<input type="text" name="redemptionCode" class="form-control" value="<?= $conditions['redemptionCode']; ?>" />
					</div>
					<div class="col-md-3">
						<label for="bonus" class="control-label"><?= lang('redemptionCode.status') ?></label>

						<select name="codeStatus" id="codeStatus" class="form-control input-sm user-success">
							<?php if (!empty($codeStatus_options) && is_array($codeStatus_options)) : ?>
								<?php foreach ($codeStatus_options as $option_value => $option_lang) : ?>
									<option value="<?= $option_value ?>" <?= $conditions['codeStatus'] == $option_value ? 'selected' : '' ?>><?= $option_lang ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</div>
					<!-- <div class="col-md-4">
						<div class="form-group">
							<label class="control-label"><?= lang('redemptionCode.redemptionTime') ?>:</label>
							<div class="input-group">
								<input id="search_apply_date" class="form-control input-sm dateInput" data-start="#apply_date_from" data-end="#apply_date_to" data-time="true" />
								<span class="input-group-addon input-sm">
									<input type="checkbox" name="search_apply_date" id="search_apply_date" <?php echo $conditions['search_apply_date']  == 'on' ? 'checked="checked"' : '' ?> />
								</span>
								<input type="hidden" name="apply_date_from" id="apply_date_from" value="<?= $conditions['apply_date_from']; ?>" />
								<input type="hidden" name="apply_date_to" id="apply_date_to" value="<?= $conditions['apply_date_to']; ?>" />
							</div>
						</div>
					</div> -->
				</div>
				<div class="row">
					<div class="col-md-3">
						<label for="username" class="control-label"><?= lang('Player Username') ?></label>
						<input type="text" name="username" class="form-control" value="<?= $conditions['username']; ?>" />
					</div>
					<div class="col-md-3">
						<label for="player_included_tag" class="control-label"><?= lang('include_player') ?></label>
						<select name="tag_list_included[]" id="tag_list_included" multiple="multiple" class="form-control input-sm">
							<option value="notag" id="notag" <?= is_array($selected_include_tags) && in_array('notag', $selected_include_tags) ? "selected" : "" ?>><?= lang('player.tp12') ?></option>
							<?php if (!empty($player_tags)) : ?>
								<?php foreach ($player_tags as $tag) : ?>
									<option value="<?= $tag['tagId'] ?>" <?= is_array($selected_include_tags) && in_array($tag['tagId'], $selected_include_tags) ? "selected" : "" ?>><?= $tag['tagName'] ?></option>
								<?php endforeach ?>
							<?php endif ?>
						</select>
					</div>
					<div class="col-md-4">
						<div class="input-group">
							<label for="bonus" class="control-label"><?= lang('redemptionCode.bonus') ?></label>
							<div class="row">
								<div class="col-md-5">
									<select name="bonusRange" id="bonusRange" class="form-control input-sm user-success">
										<?php if (!empty($bonusRange_options) && is_array($bonusRange_options)) : ?>
											<?php foreach ($bonusRange_options as $option_value => $option_lang) : ?>
												<option value="<?= $option_value ?>" <?= $conditions['bonusRange'] == $option_value ? 'selected' : '' ?>><?= $option_lang ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</div>
								<div class="col-md-6">
									<input type="number" name="bonus" class="form-control" value="<?= $conditions['bonus']; ?>" />
								</div>
							</div>
						</div>
					</div>

				</div>
				<div class="row">
					<div class="col-md-12">

						<div class="col-md-4">
							<div class="form-group">
								<label class="control-label"><?= lang('redemptionCode.redemptionTime') ?>:</label>
								<div class="input-group">
									<input id="apply_date" class="form-control input-sm dateInput" data-start="#apply_date_from" data-end="#apply_date_to" data-time="true" />
									<span class="input-group-addon input-sm">
										<input type="checkbox" name="search_apply_date" id="search_apply_date" value="<?=$conditions['enable_apply_date']?>" <?php echo empty($conditions['enable_apply_date'])? '' : 'checked="checked"'?> />
										<input type="hidden" name="enable_apply_date" value='<?php echo $conditions['enable_apply_date']?>'>

									</span>
									<input type="hidden" name="apply_date_from" id="apply_date_from" value="<?= $conditions['apply_date_from']; ?>" />
									<input type="hidden" name="apply_date_to" id="apply_date_to" value="<?= $conditions['apply_date_to']; ?>" />
								</div>
							</div>
						</div>
						<div class="col-md-1"></div>
						<div class="col-md-4">
							<div class="form-group">
								<label class="control-label"><?= lang('redemptionCode.create_at') ?>:</label>
								<div class="input-group">
									<input id="create_date" class="form-control input-sm dateInput" data-start="#create_date_from" data-end="#create_date_to" data-time="true" />
									<span class="input-group-addon input-sm">
										<input type="checkbox" name="search_create_date" id="search_create_date" value="<?=$conditions['enable_create_date']?>" <?php echo empty($conditions['enable_create_date'])? '' : 'checked="checked"'?> />
										<input type="hidden" name="enable_create_date" value='<?php echo $conditions['enable_create_date']?>'>

									</span>
									<input type="hidden" name="create_date_from" id="create_date_from" value="<?= $conditions['create_date_from']; ?>" />
									<input type="hidden" name="create_date_to" id="create_date_to" value="<?= $conditions['create_date_to']; ?>" />
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12" style="padding-top: 20px">
						<input class="btn btn-sm btn-linkwater" type="reset" value="Reset">
						<input type="submit" value="<?= lang('lang.search') ?>" id="search_main" class="btn btn-sm btn-portage">
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h4 class="panel-title"><i class="fa fa-list"></i> <?= lang('redemptionCode.redemptionCodeList') ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-bordered table-hover" id="myTable">
				<thead>
					<tr>
						<th><?= lang('redemptionCode.id'); ?></th>
						<th><?= lang('redemptionCode.redemptionCodeName'); ?></th>
						<th><?= lang('redemptionCode.redemptionCode'); ?></th>
						<th><?= lang('redemptionCode.bonus'); ?></th>
						<th><?= lang('redemptionCode.withdraw_condition'); ?></th>
						<th><?= lang('redemptionCode.create_at'); ?></th>
						<th><?= lang('redemptionCode.apply_expire_time'); ?></th>
						<th><?= lang('Player Username'); ?></th>
						<th><?= lang('redemptionCode.redemptionTime'); ?></th>
						<th><?= lang('redemptionCode.status'); ?></th>
						<th><?= lang('redemptionCode.note'); ?></th>
						<!-- <th><?= lang('redemptionCode.action_logs'); ?></th> -->
						<!-- <th><?= lang('redemptionCode.actions'); ?></th> -->
					</tr>
				</thead>
			</table>
		</div>

	</div>
	<div class="panel-footer"></div>
</div>

<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) { ?>
	<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
		<input name='json_search' type="hidden">
	</form>
<?php } ?>
<?php include('include/redemptioncode_modals.php'); ?>

<script type="text/javascript">
	$(document).ready(function() {
		$('#collapseSubmenu').addClass('in');
		$('#viewRedemptionCodeSettings').addClass('active');
		$('#redemptionCodeList').addClass('active');
		$('#viewRedemptionCodeSettings').on('click', function() {
			$('#viewRedemptionCodeSettings').toggleClass('active');
		});
		var dataTable = $('#myTable').DataTable({
			autoWidth: false,
			searching: false,
			sort: false,
			ordering: true,
			dom: "<'panel-body' <'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
			columnDefs: [{
				className: 'text-right',
				targets: []
			},{
                orderable: false,
                targets:   4
            }],
			buttons: [{
					extend: 'colvis',
					postfixButtons: ['colvisRestore'],
					className: 'btn-linkwater',
				}
				<?php if ($export_report_permission) : ?>, {
						text: "<?=lang('CSV Export')?>",
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
								'/export_data/redemptionCodeReport'));
							$("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
							$("#_export_excel_queue_form").submit();
						}
					}
				<?php endif; ?>
			],
			processing: true,
			serverSide: true,
			ajax: function(data, callback, settings) {
				data.extra_search = $('#form-filter').serializeArray();
				$.post(base_url + "api/getRedemptionCodeList", data, function(data) {
					callback(data);
					initEvent()
				}, 'json');
			},
		});
		// $('#search_main').click(() => {
		// 	$('#form-filter').trigger('submit');
		// });
		// $('#form-filter').submit(function(e) {
		// 	e.preventDefault();
		// 	dataTable.ajax.reload();
		// });

		var initEvent = function() {
		}

		$('.export_excel').click(function() {
			var d = {
				'extra_search': $('#form-filter').serializeArray(),
				'draw': 1,
				'length': -1,
				'start': 0
			};
			$.post(site_url('/export_data/redemptionCodeReport'), d, function(data) {
				if (data && data.success) {
					$('body').append('<iframe src="' + data.link +
						'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>'
					);
				} else {
					alert('export failed');
				}
			});
		});

		$('#tag_list_included').multiselect({
			enableFiltering: true,
			includeSelectAllOption: true,
			selectAllJustVisible: false,
			buttonWidth: '100%',
			buttonText: function(options, select) {
				if (options.length === 0) {
					return '<?= lang('Select Tags'); ?>';
				} else {
					var labels = [];
					options.each(function() {
						if ($(this).attr('label') !== undefined) {
							labels.push($(this).attr('label'));
						} else {
							labels.push($(this).html());
						}
					});
					return labels.join(', ') + '';
				}
			}
		});
		$('input[type="reset"]').click((e) => {
			e.preventDefault();
			$('#tag_list_included').val('').multiselect('refresh');
			$('input[name="codeType"]').val('');
			$('input[name="redemptionCode"]').val('');
			$('input[name="username"]').val('');
			$('input[name="bonus"]').val('');
			$('select[name="codeStatus"]').val('All');
			$('select[name="codeType"]').val('All');
			$('input[name="search_apply_date"]').prop('checked', false);
			$('input[name="apply_date_from"]').val('<?= $default_date_from ?>');
			$('input[name="apply_date_to"]').val('<?= $default_date_to ?>');
			$('#apply_date').val('<?= $default_date_from ?> to <?= $default_date_to ?>');
			$('input[name="search_create_date"]').prop('checked', false);
			$('input[name="create_date_from"]').val('<?= $default_date_from ?>');
			$('input[name="create_date_to"]').val('<?= $default_date_to ?>');
			$('#create_date').val('<?= $default_date_from ?> to <?= $default_date_to ?>');
		});

		$('#search_create_date').change(function(){
            if($(this).is(':checked')) {
                $(this).prop('checked', true);
                $('input[name="enable_create_date"]').val('1');

            }else{
                $(this).prop('checked', false);
                $('input[name="enable_create_date"]').val('0');
            }
        }).trigger('change');

		$('#search_apply_date').change(function(){
            if($(this).is(':checked')) {
                $(this).prop('checked', true);
                $('input[name="enable_apply_date"]').val('1');

            }else{
                $(this).prop('checked', false);
                $('input[name="enable_apply_date"]').val('0');
            }
        }).trigger('change');
		
	});
</script>