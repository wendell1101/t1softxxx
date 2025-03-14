<style>
    .cursor-pointer {
        cursor: pointer;
    }
</style>
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
				</div>
	            <!-- buttons -->
				<div class="row">
	            	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style=" margin-top: 1rem;">
						<div class="pull-right">
							<input type="submit" value="<?=lang('lang.search');?>" id="search_main" class="btn btn-sm btn-linkwater">
							<a href="/player_management/taggedlist" class="btn btn-sm btn-scooter"><?=lang('lang.reset');?></a>
						</div>
						<div class="pull-left">
							<!-- <input type="button" value="<?=lang('Update All Player Tag Through Csv');?>" class="btn btn-sm btn-linkwater" data-toggle="modal" data-target="#csv_update_modal"> -->
							<input type="button" value="<?=lang('Bulk Tag Upload via CSV');?>" class="btn btn-sm btn-linkwater" data-toggle="modal" data-target="#csv_batch_modal">
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal -->
<!-- <div class="modal fade" id="csv_update_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                  </button>
                <form class="upload-form" action="<?=base_url('/player_management/uploadUpdateTaggedList')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                    <div class="file-field">
                        <div class="btn btn-primary">
                            <span>Choose file</span>
                            <input id="csv_tag_file" type="file" accept=".csv" name="csv_tag_file" required>
                        </div>
                        <div class="file-path-wrapper">
                            <span class="span-default" >Upload your file</span>
                        </div>
                        <button type="submit" class="btn btn-primary submit-file">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> -->

<div class="modal fade" id="csv_batch_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
			<form class="upload-form" action="<?=base_url('/player_management/uploadUpdateTaggedList')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel"><?=lang('Batch Tag Upload via CSV');?></h4>
				</div>
				<div class="modal-body">
					<div class="file-field">
						<div class="btn btn-primary">
							<span>Choose file</span>
							<input id="csv_tag_file" type="file" accept=".csv" name="csv_tag_file" required onchange="return isValidFileInCSV(this)" >
						</div>
						<div class="file-path-wrapper">
							<span class="span-default" >Upload your file</span>
						</div>
						<a id="sample-file"  href="<?= '/resources/sample_csv/sample_batch_tag_upload.csv' ?>" class="btn btn-primary btn-lg pull-right panel-button" title="<?=lang('Download Sample CSV File')?>" style="margin-right:1%"><img src="<?=$this->utils->imageUrl('csv.png')?>"/></a>
					</div>
					<span class="help-block" style="color: red;"><?=lang('cms.notes')?> : <?= $csv_note ?></span>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary submit-file"><?=lang('lang.submit')?></button>
				</div>
			</form>
        </div>
    </div>
</div>
<script type="text/javascript">

function isValidFileInCSV(field) {

	var value = field.value;
	var res = value.split('.').pop();

	var oFile = document.getElementById(field.id).files[0];

	if( res != 'csv' ){
		$('#' + field.id).val('');
		return alert('<?=lang('Please enter valid File')?>');
	}
}

	$(document).ready(function(){
		// $('#csv_update_modal').on('hidden.bs.modal', function () {
		//     $("#csv_tag_file").val(null);
		//     $(".file-path-wrapper span").text('Upload your file');
		//     $(".upload-form .file-field .file-path-wrapper span").removeClass("span-select").addClass("span-default");
		// });

		// $('#csv_tag_file').change(function(e){
  //           let fileName = e.target.files[0].name;
  //           $(".file-path-wrapper span").text(fileName);
  //           $(".upload-form .file-field .file-path-wrapper span").removeClass("span-default").addClass("span-select");
  //       });

        $('#csv_batch_modal').on('hidden.bs.modal', function () {
		    $("#csv_tag_file").val(null);
		    $(".file-path-wrapper span").text('Upload your file');
		    $(".upload-form .file-field .file-path-wrapper span").removeClass("span-select").addClass("span-default");
		});

		$('#csv_tag_file').change(function(e){
            let fileName = e.target.files[0].name;
            $(".file-path-wrapper span").text(fileName);
            $(".upload-form .file-field .file-path-wrapper span").removeClass("span-default").addClass("span-select");
        });
	});
</script>

<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h4 class="panel-title custom-pt pull-left">
					<i class="icon-price-tags"></i> <?=lang('player.tl10');?>
				</h4>
				<span class="clearfix"></span>
			</div>

			<div class="panel-body" id="player_panel_body">
				<form action="<?=site_url('player_management/taggedlistToRemoveResult')?>" id="taggedlist" method="post" role="form">
					<div class="row">
						<div class="col-md-7 col-xs-12 col-sm-12">
						</div>
						<div class="col-md-3 col-xs-12 col-sm-12">
							<?php echo form_multiselect('tagsToRemove[]', is_array($tags) ? $tags : [], [], ' class="form-control input-sm chosen-select" id="should_have_tags" data-placeholder="Tags to remove" ') ?>
						</div>
						<div class="col-md-2 col-xs-12 col-sm-12">
							<input id="batchremovetags" type="button" value="<?=lang('Batch Remove Tags');?>" class="btn btn-sm btn-linkwater>" >
						</div>
					</div>
					<span class="clearfix"></span>
					<br>
					<div class="row">
						<div class="table-responsive">
							<table class="table table-striped table-hover" style="margin: 0px 0 0 0; width: 100%;" id="myTable">
								<thead>
									<tr>
										<th style="padding:8px"><input type="checkbox" id="checkWhite" onclick="checkAll(this.id)"/></th>
										<th data-th="1" ><?=lang('player.01'); # 1 ?> </th>
										<th data-th="2"><?=lang('sys.vu19'); # 2 ?></th>
										<th data-th="3"><?=lang('VIP Level'); # 3 ?></th>
										<th data-th="7"><?=lang('player.41'); # 7 ?></th>
										<th data-th="8"><?=lang('tagged_players.tagged_at'); # 8 ?></th>
										<th data-th="11" class="hidden-col"><?=lang('player.42');  # 11 ?></th>
										<th data-th="12" class="hidden-col"><?=lang('player.43');  # 12 ?></th>
										<th data-th="13"><?=lang('lang.status');  # 13 ?></th>
										<th data-th="14"><?=lang('tagged_players.account_status_last_update');  # 14 ?></th>
										<!-- <th data-th="15"><?=lang('Deleted At');  # 14 ?></th> -->
									</tr>
								</thead>
							</table>
						</div><!--/table-responsive-->
					</div><!--/row -->
				</form>
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
                                    $("#_export_excel_queue_form").attr('action', site_url('/export_data/playertaggedlist'));
                                    $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                    $("#_export_excel_queue_form").submit();
                                <?php else : ?>
	                                $.post(site_url('/export_data/playertaggedlist'), d, function(data){
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
				$.post(base_url + "api/playertaggedlist", data, function(data) {
					callback(data);
					if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
					    dataTable.buttons().disable();
					}
					else {
						dataTable.buttons().enable();
					}
				},'json');
			},

        	// drawCallback: function () {
         //        if ($('#myTable').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
         //            $('#myTable').DataTable().buttons().disable();
         //        }
         //        else {
         //            $('#myTable').DataTable().buttons().enable();
         //        }
         //    }
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

		var batchremovetags = document.getElementById('batchremovetags');

		batchremovetags.addEventListener('click', function() {
		//	var message = '<?="Are you sure you want to delete selected tags?"?>';
		//	var status = confirm(message);
		//	if (status == true) {
				var d = {'extra_search':$('#taggedlist').serializeArray()};
				//console.log(d);return;
				$("#_batch_remove_playertag_ids_queue_form [name=json_search]").val(JSON.stringify(d));
				$('#_batch_remove_playertag_ids_queue_form').submit();
		//	}
		}, false);
    });

    function viewPlayerTaggedhistory(tagId, playerId) {
        console.log(tagId, playerId);
        var dst_url = '/player_management/player_tagged_history/' + tagId + '/' + playerId;
        open_modal_by_class('playerTagId_' + tagId, dst_url, '<?= lang('Tagged Player History'); ?>');
    }

    function open_modal_by_class(name, dst_url, title) {
    let main_selector = '.' + name;

    let label_selector = '#label_' + name;
    $(label_selector).html(title);

    let body_selector = main_selector + ' .modal-body';
    let target = $(body_selector);
    let $dfd = $.Deferred();
    let $dfd4dst_url = $.Deferred();
    let $dfd4modal_shown = $.Deferred();

    target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(dst_url, function( response, status, xhr ) { // complete
        $dfd4dst_url.resolve({});
    });

    $(main_selector).modal('show');
    $(main_selector).on('shown.bs.modal', function(e){
        // console.log("I want this to appear after the modal has opened!");
        $dfd4modal_shown.resolve({});
    });

    $.when( $dfd4dst_url.promise(), $dfd4modal_shown.promise() )
        .done(function(res4dst_url, res4modal_shown) {
            $dfd.resolve({
                dst_url : res4dst_url,
                modal_shown : res4modal_shown
            });
    });

    return $dfd.promise();
}
</script>