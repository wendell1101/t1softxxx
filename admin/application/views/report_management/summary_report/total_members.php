<ol class="breadcrumb">
	<?php if ($day): ?>
		<li><a href="/report_management/summary_report"><?=lang('Summary Report')?></a></li>
		<li><a href="/report_management/total_members"><?=lang('report.sum06')?></a></li>
		<li><a href="/report_management/total_members/<?=$year?>"><?=$year?></a></li>
		<li><a href="/report_management/total_members/<?=$year?>/<?=$month?>"><?=$month?></a></li>
		<li class="active"><?=$day?></li>
	<?php elseif ($month): ?>
		<li><a href="/report_management/summary_report"><?=lang('Summary Report')?></a></li>
		<li><a href="/report_management/total_members"><?=lang('report.sum06')?></a></li>
		<li><a href="/report_management/total_members/<?=$year?>"><?=$year?></a></li>
		<li class="active"><?=$month?></li>
	<?php elseif ($year): ?>
		<li><a href="/report_management/summary_report"><?=lang('Summary Report')?></a></li>
		<li><?=lang('report.sum06')?></li>
		<li class="active"><?=$year?></li>
	<?php else: ?>
		<li class="active"><?=lang('Summary Report')?></li>
	<?php endif ?>
</ol>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i><?=lang('lang.search')?><span class="pull-right">
                <a data-toggle="collapse" href="#collapseNewMembersReport" class="btn btn-xs btn-primary"></a>
            </span>
        </h4>
    </div>
    <div id="collapseNewMembersReport" class="panel-collapse ">
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
                </div>
                <div class="row">
                    <div class="col-md-1" style="padding-top: 20px">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-sm btn-portage">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- <div class="panel panel-primary">
	<div class="panel-heading custom-ph">
		<h4 class="panel-title">
			<?=implode('-', array_filter(array($year,$month,$day)))?>
			<?=lang('report.sum06')?>
		</h4>
	</div>
	<div class="panel-body">
		<table class="table table-condensed table-bordered table-hover" id="summary-report" style="width:100%">
			<thead>
				<tr>
					<th data-data="username"><?=lang('Username')?></th>
					<th data-data="tagName"><?=lang('Player Tag')?></th>
					<th data-data="createdOn"><?=lang('report.sum02')?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div class="panel-footer"></div>
</div> -->

<div class="panel panel-primary">
	<div class="panel-heading custom-ph">
		<h4 class="panel-title">
			<?php if (isset($dateFrom) && isset($dateTo) && !empty($dateFrom) && !empty($dateTo)): ?>
				<?= "{$dateFrom} to {$dateTo}" ?>
			<?php else: ?>
				<?=implode('-', array_filter(array($year,$month,$day)))?>
			<?php endif ?>
			<?=lang('report.sum06')?>
		</h4>
	</div>
	<div class="panel-body">
		<table class="table table-condensed table-bordered table-hover" id="summary-report" style="width:100%">
			<thead>
				<tr>
					<th data-data="username"><?=lang('Username')?></th>
					<th data-data="player_tags"><?=lang('Player Tag')?></th>
					<th data-data="createdOn"><?=lang('report.sum02')?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div class="panel-footer"></div>
</div>

<!-- <script type="text/javascript">

	var username = function (data, type, row) {
		return '<a href="/player_management/player/' + data + '">' + data + '</a>';
	}

    $(document).ready(function(){

    	$('#report_date').daterangepicker({
		    startDate: moment().startOf('month'),
		    endDate: moment().endOf('month'),
		});

		$('#report_date_from').val(moment().startOf('month').format('YYYY-MM-DD'))
		$('#report_date_to').val(moment().endOf('month').format('YYYY-MM-DD'))

        var dataTable = $('#summary-report').DataTable( {

        	columnDefs: [{
				render: username,
				targets: 0,
			}],

            ajax:
				<?php if ($day): ?>
					'/api/total_members/<?=$year?>/<?=$month?>/<?=$day?>',
				<?php elseif ($month): ?>
					'/api/total_members/<?=$year?>/<?=$month?>',
				<?php else: ?>
					'/api/total_members/<?=$year?>',
				<?php endif ?>
        } );

        $('#search-form').submit(function(e) {
        	e.preventDefault();
            dataTable.ajax.reload();
        })
    });
</script> -->

<script type="text/javascript" src="<?=site_url().'resources/datatables/buttons.html5.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/dataTables.buttons.min.js'?>"></script>

<script type="text/javascript">

	var username = function (data, type, row) {
		return '<a href="/player_management/player/' + data + '">' + data + '</a>';
	}

    $(document).ready(function(){

		var ajax_uri = <?php if ($day): ?>
					'/api/total_members/<?=$year?>/<?=$month?>/<?=$day?>';
				<?php elseif ($month): ?>
					'/api/total_members/<?=$year?>/<?=$month?>';
				<?php elseif ($year): ?>
					'/api/total_members/<?=$year?>';
				<?php else: ?>
					'/api/total_members/null/null/null/<?=$dateFrom?>/<?=$dateTo?>';
				<?php endif ?>;

		var post_data = {};

		<?php if(!empty($selected_tags)) {
			$tags = json_encode($selected_tags);
		?>
			var post_data = {"tags" : '<?=$tags?>'}
		<?php } ?>


    	$('#report_date').daterangepicker({
		    startDate: moment().startOf('month'),
		    endDate: moment().endOf('month'),
		});

		$('#report_date_from').val(moment().startOf('month').format('YYYY-MM-DD'))
		$('#report_date_to').val(moment().endOf('month').format('YYYY-MM-DD'))

		<?php $d = new DateTime(); ?>
        var dataTable = $('#summary-report').DataTable( {

            <?php if( $this->permissions->checkPermissions('export_deposit_report') ){ ?>
            dom: "<'panel-body' <'pull-right'B><'pull-right hide'f><'pull-right progress-container'>l>" +
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
                    filename:  '<?=lang('report.sum06').' '. $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999)?>'
                }            ],
			<?php } ?>

        	columnDefs: [{
				render: username,
				targets: 0,
			}],
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(ajax_uri, post_data, function(data) {
                    callback(data);
                }, 'json');
            }
        } );

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
</script>
