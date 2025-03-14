<!--main-->
<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseTaggedList" class="btn btn-info btn-xs <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseTaggedList" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
			<form class="" id="search-form">
				<div class="row">
					<!-- date of tag -->
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label" for="search_date"><?=lang('tagged_players.date_of_tag');?></label>
						<div class="input-group">
		                    <input id="search_date" class="form-control input-sm dateInput user-success" data-time="true" data-start="#date_from" data-end="#date_to">
		                    <input type="hidden" id="date_from" name="date_from" value="<?= $date_from ?>">
		                    <input type="hidden" id="date_to" name="date_to" value="<?= $date_to ?>">
		                    <span class="input-group-addon input-sm">
                                <input type="checkbox" name="search_reg_date" id="search_reg_date" class="user-success">
		                    </span>
		                </div>
		            </div>
		            <!-- date of last update -->
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label" for="search_last_update"><?=lang('tagged_players.date_of_last_update');?></label>
						<div class="input-group">
		                    <input id="search_last_update" class="form-control input-sm dateInput user-success" data-time="true" data-start="#last_update_from" data-end="#last_update_to">
		                    <input type="hidden" id="last_update_from" name="last_update_from" value="<?= $last_update_from ?>">
		                    <input type="hidden" id="last_update_to" name="last_update_to" value="<?= $last_update_to ?>">
		                    <span class="input-group-addon input-sm">
                                <input type="checkbox" name="search_last_update_date" id="search_last_update_date" class="user-success">
		                    </span>
		                </div>
		            </div>
	            	<!-- username -->
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
							<label class="control-label" for="username"><?=lang('Username');?></label>
							<input id="username" name="username" class="form-control input-sm user-success">
		            </div>

					<!-- select vip level -->
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label" for="username"><?=lang('VIP Level');?></label>
                        <select name="vip_level" id="search_vip_level" class="form-control input-sm">
                            <?php foreach ($allLevels as $key => $value) {?>
                            <option value="<?=$key?>"><?=$value?></option>
						<?php }
						?>
						</select>
					</div>
				</div>

				<div class="row">
		            <!-- select tags -->
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
							<label class="control-label" for="tag"><?=lang('tagged_players.select_tags');?></label>
							<?php echo form_multiselect('selected_tags[]', is_array($tags) ? $tags : [], [], ' class="form-control input-sm chosen-select" id="selected_tags" data-placeholder="" data-untoggle="checkbox" data-target=""') ?>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label" for="update_status"><?=lang('Update Status');?></label>
                        <select name="update_status" id="update_status" class="form-control input-sm">
                        	<option value=""><?=lang('Select All');?></option>
                            <option value="add"><?=lang('Added');?></option>
                            <option value="remove"><?=lang('Removed');?></option>
						</select>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label" for="tag_status"><?=lang('Tag Status');?></label>
                        <select name="tag_status" id="tag_status" class="form-control input-sm">
                        	<option value=""><?=lang('Select All');?></option>
                            <option value="active"><?=lang('Active');?></option>
                            <option value="deleted"><?=lang('Deleted');?></option>
						</select>
					</div>
				</div>
	            <!-- buttons -->
				<div class="row">
	            	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style=" margin-top: 1rem;">
						<div class="pull-right">
							<input type="submit" value="<?=lang('lang.search');?>" id="search_main" class="btn btn-sm btn-linkwater">
							<a href="/player_management/player_tag_history" class="btn btn-sm btn-scooter"><?=lang('lang.reset');?></a>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h4 class="panel-title custom-pt pull-left">
					<i class="icon-price-tags"></i> <?=lang('Tagged Players History List');?>
				</h4>
				<span class="clearfix"></span>
			</div>

			<div class="panel-body" id="player_panel_body">
					<span class="clearfix"></span>
					<br>
					<div class="row">
						<div class="table-responsive">
							<table class="table table-striped table-hover" style="margin: 0px 0 0 0; width: 100%;" id="myTable">
								<thead>
									<tr>
										<th data-th="1" ><?=lang('player.01'); # 1 ?> </th>
										<th data-th="2"><?=lang('sys.vu19'); # 2 ?></th>
										<th data-th="3"><?=lang('VIP Level'); # 3 ?></th>
										<th data-th="4"><?=lang('player.41'); # 4 ?></th>
										<th data-th="5"><?=lang('Update Status'); # 5 ?></th>
										<th data-th="6"><?=lang('Updated At');  # 6 ?></th>
										<th data-th="7"><?=lang('Tag Status');  # 7 ?></th>
									</tr>
								</thead>
							</table>
						</div><!--/table-responsive-->
					</div><!--/row -->
			</div><!--/panel-body -->
			<div class="panel-footer">
			</div>
		</div>
	</div>
	<div class="col-md-7" id="player_details" style="display: none;">
	</div>
</div>

<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) {?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
    <input name='json_search' type="hidden">
    </form>
<?php }?>
<form id="_batch_remove_playertag_ids_queue_form" action="<?=site_url('player_management/taggedlistToRemoveResult'); ?>" method="POST">
	<input name='json_search' type="hidden">
</form>
<script type="text/javascript">
    $(document).ready(function(){

    	var hiddenColumns = [];
    	var elem = $('#myTable thead tr th');
    	var search_tag = '<?=$search_tag?>';
    	var search_reg_date = '<?=$search_reg_date?>';

    	var flagColIndex = elem.filter(function(index){
	        if ($(this).hasClass('hidden-col')) {
	            hiddenColumns.push(index);
	        }
	    }).index();

        var dataTable = $('#myTable').DataTable({
        	autoWidth: false,
			searching: false,
        	<?php if ($this->utils->isEnabledFeature('column_visibility_report')) { ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
        	 dom: "<'panel-body nopadding' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'text-center'><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
             columnDefs: [
					{
						sortable: false,
						targets: [ 0 ],
						orderable: false
					}
             	],
        	 buttons: [
                {
					extend: 'colvis',
					className:'btn-linkwater',
                    postfixButtons: [ 'colvisRestore' ]
                },
                <?php if ($this->permissions->checkPermissions('export_tagged_players')) : ?>
                        {
                            text: "<?php echo lang('CSV Export'); ?>",
                            className:'btn btn-sm btn-portage',
                            exportOptions: {
                                columns: [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ]
                            },
                            action: function ( e, dt, node, config ) {
                                var d = {'extra_search':$('#search-form').serializeArray(), 'export_format': 'csv', 'export_type': 'queue', 'draw':1, 'length':-1, 'start':0};
                                // utils.safelog(d);

                                <?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) : ?>
                                    $("#_export_excel_queue_form").attr('action', site_url('/export_data/playertaggedlistHistory'));
                                    $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                    $("#_export_excel_queue_form").submit();
                                <?php else : ?>
	                                $.post(site_url('/export_data/playertaggedlistHistory'), d, function(data){
	                                    // utils.safelog(data);

	                                    //create iframe and set link
	                                    if(data && data.success){
	                                        $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
	                                    }else{
	                                        alert('export failed');
	                                    }
	                                });
	                            <?php endif; ?>
                            }
                        }
                <?php endif; ?>

            ],
        	"order": [ 5, 'desc' ],
        	// SERVER-SIDE PROCESSING
			processing: true,
			serverSide: true,

			ajax: function (data, callback, settings) {
				data.extra_search = $('#search-form').serializeArray();
				$.post(base_url + "api/playertaggedlistHistory", data, function(data) {
					callback(data);
					if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
					    dataTable.buttons().disable();
					}
					else {
						dataTable.buttons().enable();
					}
				},'json');
			},
        });

        $(".chosen-select").select2({
            disable_search: true,
			width: '100%',
        });


        if(search_reg_date == 'true'){
            $('#search_reg_date').prop('checked', true);
        }else{
            $('#search_reg_date').prop('checked', false);
        }

        if(!!search_tag){
            $(".chosen-select").val([search_tag]).trigger('change');
        }

        $('#search-form').submit( function(e) {
			e.preventDefault();
	        dataTable.ajax.reload();
		});

    });
</script>