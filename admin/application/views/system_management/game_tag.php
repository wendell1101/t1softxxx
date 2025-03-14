
<div class="row" id="user-container">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h3 class="panel-title custom-pt" >
					<i class="icon-list"></i>
					<?=lang('Game Tags');?>
					<button type="button" value="" id="add_game_tag" name="btnSubmit" class="btn btn-primary pull-right <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-xs' : 'btn-sm'?>" <?=$this->utils->getConfig('use_new_sbe_color') ? 'style="margin-top:0px;"' : ''?>>
						<i class="glyphicon glyphicon-plus" style="color:white;" data-placement="bottom" ></i>
						<?=lang('sys.gt22');?>
					</button>

					<button type="button" value="" id="btn-reload" name="btnSubmit" class="btn btn-primary pull-right <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-xs' : 'btn-sm'?>" <?=$this->utils->getConfig('use_new_sbe_color') ? 'style="margin-right:4px;margin-top:0px"' : ''?>>
						<i class="glyphicon glyphicon-refresh" style="color:white;" data-placement="bottom" ></i>
						<?=lang('Refresh');?>
					</button>
				</h3>
			</div>

			<div class="panel-body" id="list_panel_body">
				<form  autocomplete="on" id="my_form">
					<div class="table-responsive">
						<table class="table table-bordered table-hover dataTable" style="width:100%;" id="my_table" >
							<thead>
								<tr>
									<th><?=lang("Tag Name");?></th> <!-- Game Platform -->
									<th><?=lang("Tag Code");?></th> <!-- Game Type -->
									<th><?=lang("Created at");?></th> <!-- Language Code -->
									<th><?=lang("Updated at");?></th> <!-- Note -->
									<th><?=lang('sys.gt33');?></th> <!-- Action -->
								</tr>
							</thead>
						</table>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<!-- The modal -->
<div class="modal fade" id="game_tag_modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="modalLabel"><?php echo lang('Game Tag Details') ?></h4>
      </div>
      <form id="game_tag_form" action="/game_description/add_game_tag" method="post" role="form">
	      <div class="modal-body"> 
				<div class="form-group">
					<label for="game_tag" class="col-form-label"><?php echo lang('Game Tag Code') ?> :</label>
					<input type="text" class="form-control" id="game_tag" name="game_tag" required>
				</div>
				<?php if(!empty($languages)){ foreach ($languages as $key => $value) { ;?> 
				<div class="form-group">
					<label class="col-form-label"><?php echo ucfirst($value['word']) . " " . lang('Translation')?> :</label>
					<input type="text" class="form-control lang_translation" id="translation-<?= $value['key'] ?>" name="translation[<?= $value['key'] ?>]" <?php echo ($value['short_code'] == 'en')  ? "required" : "" ?>>
				</div>
				<?php } 
				}?>
	      </div>
	      <div class="modal-footer">
	      	<button type="submit" class="btn btn-primary btn-add pull-right"><?=lang("Add");?></button>
	      	<button type="button" class="btn btn-secondary pull-left" data-dismiss="modal"><?=lang("Close");?></button>
	      </div>
      </form>
    </div>
  </div>
</div>
<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
<input name='json_search' type="hidden">
</form>
<?php }?>
	<script type="text/javascript">
	var baseUrl = '<?php echo base_url(); ?>';
	$(document).ready(function(){
		$('#view_game_tags').addClass('active');
		$("#collapseSubmenuGameDescription").addClass("in");
    	$("a#view_game_description").addClass("active");
		// Initialize DataTable jQuery plugin on the main table
		var dataTable = $('#my_table').DataTable({
			dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
			autoWidth: false,

            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>

			buttons: [
				{ extend: 'colvis', postfixButtons: [ 'colvisRestore' ], className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>'},
				<?php

                    if( $this->permissions->checkPermissions('export_game_type') ){

                ?>
                        {

                            text: "<?php echo lang('CSV Export'); ?>",
                            className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                            action: function ( e, dt, node, config ) {
                                var d = {'extra_search':$('#search-form').serializeArray(), 'draw':1, 'length':-1, 'start':0};
                                // utils.safelog(d);

                                $.post(site_url('/export_data/getAllGameTags'), d, function(data){
                                    // utils.safelog(data);

                                    //create iframe and set link
                                    if(data && data.success){
                                        $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                                    }else{
                                        alert('export failed');
                                    }
                                });
                            }
                        }
                <?php
                    }
                ?>
			],
			"order": [[ 1, 'desc' ]],
			processing: true,
			serverSide: true,
			ajax: function (data, callback, settings) {
				data.extra_search = $('#search-form').serializeArray();
				$.post(base_url + "game_description/getAllGameTags", data, function(data) {
						callback(data);
						if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
						    dataTable.buttons().disable();
						}
						else {
							dataTable.buttons().enable();
						}
				}, 'json');
			},
		});

		$('#btn-reload').click( function() {
	        dataTable.ajax.reload();
	    });

	    $('#add_game_tag').click( function() {
	    	$('#game_tag_form').attr('action', '/game_description/add_game_tag');
	    	$('.btn-add').text("<?=lang("Add");?>");
	        $('#game_tag_modal').modal('show');
	    });

	    $('#game_tag').keypress(function( e ) {
		    if(e.which === 32) {
		        return false;
		    }
		});

		$('#game_tag_modal').on('hidden.bs.modal', function () {
			$('#game_tag_form')[0].reset();
		});
	});

	function edit_game_tag($id){
		$('#game_tag_form').attr('action', '/game_description/edit_game_tag/'+$id);
		$('.btn-add').text("<?=lang("Update");?>");
		$.get(baseUrl + 'game_description/get_game_tag_details/' + $id, function(data, status){
			$('#game_tag').val(data.tag_code);
			$.each( data.translation, function( index, value ){
			    $('#translation-'+index).val(value);
			});
			$('#game_tag_modal').modal('show');
		});
	}

	function delete_game_tag($id){
		if (confirm("<?=lang("Are you sure ?");?>") == true) {
			window.location = baseUrl + 'game_description/delete_game_tag/' + $id;
		} 
	}

	</script>

