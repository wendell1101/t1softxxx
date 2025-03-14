<!--main-->
<form class="" id="search-form">
<input type="hidden" name="triggered_by" value="batch_remove_tags"/>
<?php

foreach ($player_tag_ids as $key => $player_tag_id) {?>
	<input type="hidden" name="player_tag_ids[]" value="<?php echo $player_tag_id;?>"/>
<?php } ?>
<?php foreach ($player_tag_to_remove as $key => $player_tag_to_removeitem) {?>
	<input type="hidden" name="player_tag_to_remove[]" value="<?php echo $player_tag_to_removeitem;?>"/>
<?php } ?>

</form>
<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h4 class="panel-title custom-pt pull-left">
					<i class="icon-price-tags"></i> <?=lang('Player tags to remove');?>
				</h4>
				<span class="clearfix"></span>
			</div>

			<div class="panel-body" id="player_panel_body">
				<form action="<?=site_url('player_management/batch_remove_playertag_ids')?>" id="taggedlist" method="post" role="form">
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
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style=" margin-top: 1rem;">
                    <a href="/player_management/taggedlist" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-default' ?>"><?=lang('lang.cancel');?></a>
                    <input type="submit" value="<?=lang('lang.submit');?>" id="batchremovetags" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-primary' ?>">

                    </div>
                </div>
			</div>
		</div>
	</div>
</div>
<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) {?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
    <input name='json_search' type="hidden">
    </form>
<?php }?>
<form id="_batch_remove_playertag_ids_queue_form" action="<?=site_url('player_management/batch_remove_playertag_ids'); ?>" method="POST">
	<input name='json_search' type="hidden">
</form>

<script type="text/javascript">
    $(document).ready(function(){


    	var hiddenColumns = [];
    	var elem = $('#myTable thead tr th');

    	var flagColIndex = elem.filter(function(index){
	        if ($(this).hasClass('hidden-col')) {
	            hiddenColumns.push(index);
	        }
	    }).index();

        var dataTable = $('#myTable').DataTable({
        	autoWidth: false,
			searching: false,

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
					className:'<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : '' ?>',
                    postfixButtons: [ 'colvisRestore' ]
                },
                <?php if ($this->permissions->checkPermissions('export_tagged_players')) : ?>
                        {
                            text: "<?php echo lang('CSV Export'); ?>",
                            className:'<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn btn-sm btn-portage' : 'btn btn-sm btn-primary' ?>',
                            exportOptions: {
                                columns: [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ]
                            },
                            action: function ( e, dt, node, config ) {
                                var d = {'extra_search':$('#taggedlist').serializeArray(), 'export_format': 'csv', 'export_type': 'queue', 'draw':1, 'length':-1, 'start':0};
								console.log(d);
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
			"pageLength": 50,
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
			}
        });

		$('#search-form').submit( function(e) {
			e.preventDefault();
	        dataTable.ajax.reload();
		});

		var batchremovetags = document.getElementById('batchremovetags');

		batchremovetags.addEventListener('click', function() {
			var message = '<?="Are you sure you want to delete selected tags?"?>';
			var status = confirm(message);
			if (status == true) {
				var d = {'extra_search':$('#taggedlist').serializeArray()};
				$("#_batch_remove_playertag_ids_queue_form [name=json_search]").val(JSON.stringify(d));
				$('#_batch_remove_playertag_ids_queue_form').submit();
			}
		}, false);


    });


</script>