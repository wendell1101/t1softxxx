<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h3 class="panel-title custom-pt"><i class="icon-sphere"></i> <?=lang('sys.dm1');?>
				</h3>
			</div>

			<div class="panel-body" id="list_panel_body">
				<div class="col-md-7 table-responsive">
					<div class="alert alert-info">
						<?=lang('tip.what_domain_do');?>
					</div>
					<table class="table table-striped table-bordered" id="domainList" style="width:100%;">
						<thead>
							<tr>
								<th></th>
								<th>#</th>
								<th><?=lang('sys.dm2');?>
								</th>
								<th><?= lang('Visibility'); ?>
								</th>
								<th><?=lang('sys.dm3');?>
								</th>
								<th><?=lang('sys.dm4');?>
								</th>
								<th><?=lang('sys.dm5');?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php
                                    $count = 0;

                                    foreach ($domain as $key => $value) {
                                        $count++; ?>
							<tr>
								<td></td>
								<td><?=$count?>
								</td>
								<td><?=$value['domainName']?>
								</td>
								<td>
									<?php if ($value['show_to_affiliate'] == 0): ?>
									<span class="text-danger"><?php echo lang('Hidden'); ?></span>
									<?php elseif ($value['show_to_affiliate'] == 1): ?>
									<span class="text-success"><?php echo lang('Visible to All'); ?></span>
									<?php else: ?>
									<a
										href="/affiliate_management/domain_affiliates/<?=$value['domainId']?>"><?php echo lang('Visible to'); ?>
										<?=$value['affiliates']?>
										<?php echo lang('Affiliates'); ?></a>
									<?php endif ?>
								</td>
								<td><?=($value['status'] == 0) ? lang('sys.dm9') : lang('sys.dm10')?>
								</td>
								<td><?=$value['notes']?>
								</td>
								<td>
									<a href="<?=BASEURL . 'affiliate_management/editDomain/' . $value['domainId']?>"
										data-toggle="tooltip"
										title="<?=lang('lang.edit'); ?>"
										class="edit" onclick=""><span class="glyphicon glyphicon-pencil"></span></a>
									<a href="<?=BASEURL . 'affiliate_management/deleteDomain/' . $value['domainId']?>"
										data-toggle="tooltip"
										title="<?=lang('lang.delete'); ?>"
										class="delete"><span class="glyphicon glyphicon-trash"></span></a>

									<?php if ($value['status'] == 0) {?>
									<a href="#" data-toggle="tooltip"
										title="<?=lang('lang.deactivate');?>"
										class="deactivate"
										onclick="deactivateDomain('<?=$value['domainId']?>','<?=$value['domainName']?>');"><span
											class="glyphicon glyphicon-remove-circle"></span></a>
									<?php } else {?>
									<a href="#" data-toggle="tooltip"
										title="<?=lang('lang.activate');?>"
										class="activate"
										onclick="activateDomain('<?=$value['domainId']?>','<?=$value['domainName']?>');"><span
											class="glyphicon glyphicon-ok-sign"></span></a>
									<?php } ?>
								</td>
							</tr>
							<?php
                                    } ?>
						</tbody>
					</table>
				</div>
				<hr style="margin-top:5px;" />
				<div class="col-md-5">
					<form class="form-horizontal" method="POST"
						action="<?=BASEURL . 'affiliate_management/addDomain'?>"
						accept-charset="utf-8" enctype="multipart/form-data">
						<div class="form-group">
							<div class="col-md-10 col-md-offset-1">
								<label for="domain" class="control-label"><?=lang('sys.dm6');?>
								</label>
								<input type="text" name="domain" id="domain"
									value="<?=set_value('domain')?>"
									class="form-control input-sm">
								<?php echo form_error('domain', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
							<div class="col-md-10 col-md-offset-1">
								<label for="notes" class="control-label"><?= lang('Domain Visibility'); ?>:
								</label>
								<div class="radio">
									<label>
										<input type="radio" name="show_to_affiliate" id="show_to_affiliate1" value="0">
										<?=lang('Hidden to all affiliates')?>
									</label>
								</div>
								<div class="radio">
									<label>
										<input type="radio" name="show_to_affiliate" id="show_to_affiliate2" value="1"
											checked>
										<?=lang('Visible to all affiliates')?>
									</label>
								</div>
								<div class="radio">
									<label>
										<input type="radio" name="show_to_affiliate" id="show_to_affiliate3" value="2">
										<?=lang('Visible to affiliates')?>
									</label>
								</div>
								<input type="file" name="usernames" class="form-control" accept=".csv" required
									disabled />
								<span class="help-block"><?=lang('Note: Upload file format must be CSV')?></span>
								<a id="sample-file"  href="<?= '/resources/sample_csv/sample_batch_add_visible_to_affiliate.csv' ?>" style="font-size:12px;" class="text-info" class="text-info" title="<?=lang('download_sample')?>" ><?=lang('download_sample')?></a>
							</div>
							<div class="col-md-10 col-md-offset-1">
								<label for="notes" class="control-label"><?=lang('sys.dm7');?>
								</label>
								<textarea name="notes" id="notes" class="form-control"
									style="resize: none; height: 100px;"><?=set_value('notes')?></textarea>
								<?php echo form_error('notes', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
							<div class="col-md-10 col-md-offset-1" style="padding-top:15px;">
								<button type="submit" class="btn btn-linkwater"><i class="fa fa-plus"></i> <?=lang('sys.dm8');?>
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="panel-footer"></div>
		</div>
	</div>
</div>
<?php if($this->config->item('enable_dedicated_additional_domain_list')):?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h3 class="panel-title custom-pt"><i class="icon-sphere"></i> <?=lang('aff.dedicatedAndAdditionalDomains');?>
				</h3>
			</div>
			<div class="panel-body">
				<div class="col-md-7 table-responsive">
					<table class="table table-striped table-bordered" id="DATable" style="width:100%;">
						<thead>
							<tr>
								<th><?=lang('Affiliate Username');?>
								</th>
								<?php if($this->config->item('show_tag_in_dedicated_additional_domain_list')):?>
								<th><?=lang('aff.al25');?>
								</th>
								<?php endif; ?>
								<th><?=lang('sys.dm2');?>
								</th>
								<th><?=lang('aff.ai27');?>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td></td>
								<?php if($this->config->item('show_tag_in_dedicated_additional_domain_list')):?>
								<td></td>
								<?php endif; ?>
								<td></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="panel-footer"></div>
		</div>
	</div>
</div>

<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) {?>
    <!-- <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form> -->
	<form id="_export_csv_form" class="hidden" method="POST" target="_blank">
		<input name='json_search' id = "json_csv_search" type="hidden">
	</form>
<?php }?>

<script type="text/javascript">
	var show_tag_in_dedicated_additional_domain_list = "<?=$this->config->item('show_tag_in_dedicated_additional_domain_list')?>";
	$(document).ready(function() {
		$('#domainList').DataTable({
			dom: "<'panel-body' <'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
			"responsive": {
				details: {
					type: 'column'
				}
			},
			"columnDefs": [{
				className: 'control',
				orderable: false,
				targets: 0
			}],
			"order": [1, 'asc']
		});

		$('input[name="show_to_affiliate"]').change(function() {
			var disabled = $('input[name="show_to_affiliate"]:checked').val() != 2;
			$('input[name="usernames"]').prop('disabled', disabled);
		});

		// ==============Dedicated and Additional Domains Table====================

		var jsonObjForExportToCSV = {data:[], header_data:[]};

		var DATable = $('#DATable').DataTable({
			"dom":"<'panel-body' <'pull-right'B><'pull-right'f>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            "responsive":
            {
                details: {
                    type: 'column'
                }
            },
            "columnDefs": [ {
                className: 'control',
                orderable: false,
                targets:   0
            } ],
			buttons: [
				{
					text: "<?php echo lang('Refresh'); ?>",
                    className:'btn btn-sm btn-linkwater',
					action: function ( e, dt, node, config ) {
					    DATable.buttons().disable();
						DATable.clear().ajax.reload();
					}
                },{
                        text: "<?php echo lang('CSV Export'); ?>",
                        className:'btn btn-sm btn-portage _export_csv_btn',
                        action: function ( e, dt, node, config ) {

                            // filted not used condition for remove name attr. while on submit.

                            // var d = {'export_format': 'csv', 'export_type': export_type,
                            //     'draw':1, 'length':-1, 'start':0};

                            // $("#_export_excel_queue_form").attr('action', site_url('/export_data/dedicated_additional_domains_report'));
                            // $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            // $("#_export_excel_queue_form").submit();
							$(this).attr('disabled', 'disabled');
							$.ajax({
								url:  site_url('/export_data/dedicated_additional_domains_report'),
								type: 'POST',
								data: {json_search: $("#json_csv_search").val() }
								}).done(function(data) {

								if(data && data.success){
								$('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
									$('._export_csv_btn').removeAttr("disabled");
								}else{
								$('._export_csv_btn').removeAttr("disabled");
								alert('export failed');
								}
								}).fail(function(){
									$('._export_csv_btn').removeAttr("disabled");
									alert('export failed');
							});
                        }
                }
			],
			"processing": false,
			"serverSide": false,
            ajax: function (data, callback, settings) {
                $.post(base_url + "affiliate_management/getDomainInUsed", data, function(data) {
					//$("#json_csv_search").val(JSON.stringify(data));

					generateCSVData(data);
					DATable.rows.add(data).draw();
					DATable.buttons().enable();

				},'json');
            }

        });

		function generateCSVData(data) {


			var rowHeaders = (show_tag_in_dedicated_additional_domain_list != "1") ? ["<?=lang('Affiliate Username');?>","<?=lang('sys.dm2');?>","<?=lang('aff.ai27');?>"] : ["<?=lang('Affiliate Username');?>","<?=lang('aff.al25');?>","<?=lang('sys.dm2');?>","<?=lang('aff.ai27');?>"];

			jsonObjForExportToCSV["header_data"] = rowHeaders;


			jsonArr = [];

			for(var i=0; i<data.length; i++){

				var jsonData = {};

				var dta = data[i];

				var aff_name = dta[0];
				var domain_name = dta[1];
				var updated_on = dta[2];

				jsonData = {
					'affiliate_username' : stripHtml(aff_name),
					'domain_name' : domain_name,
					'update_on' : updated_on,
				}

				if(show_tag_in_dedicated_additional_domain_list == "1") {
					var aff_tag = dta[1];
					domain_name = dta[2];
					updated_on = dta[3];
	
					jsonData = {
						'affiliate_username' : stripHtml(aff_name),
						'aff_tag' :stripHtml(aff_tag),
						'domain_name' : domain_name,
						'update_on' : updated_on,
					}
				}
	
				jsonArr.push(jsonData);
			}

			jsonObjForExportToCSV["data"] = jsonArr;


			$("#json_csv_search").val(JSON.stringify(jsonObjForExportToCSV));
		}

	});
	function stripHtml(html)
	{
		let tmp = document.createElement("DIV");
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || "";
	}
</script>
<?php endif?>