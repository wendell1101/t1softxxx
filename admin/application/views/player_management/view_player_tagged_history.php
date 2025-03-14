<!--main-->
<div class="row">
    <div class="table-responsive">
        <table class="table table-striped table-hover" style="margin: 0px 0 0 0; width: 100%;" id="myTable1_<?= $tag_id; ?>">
            <thead>
                <tr>
                    <th data-th="1" ><?=lang('player.01'); # 1 ?> </th>
                    <th data-th="2"><?=lang('VIP Level'); # 2 ?></th>
                    <th data-th="3"><?=lang('player.41'); # 3 ?></th>
                    <th data-th="4"><?=lang('Update Status'); # 4 ?></th>
                    <th data-th="5"><?=lang('Updated At');  # 5 ?></th>
                    <th data-th="6"><?=lang('Tag Status');  # 6 ?></th>
                    <th data-th="7"><?=lang('Deleted At');  # 7 ?></th>
                </tr>
            </thead>
        </table>						
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
    	var elem = $('#myTable1_<?= $tag_id; ?> thead tr th');
    	var search_tag = '<?=$search_tag?>';
    	var search_reg_date = '<?=$search_reg_date?>';

    	var flagColIndex = elem.filter(function(index){
	        if ($(this).hasClass('hidden-col')) {
	            hiddenColumns.push(index);
	        }
	    }).index();

        var dataTable = $('#myTable1_<?= $tag_id; ?>').DataTable({
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
                        var d = {'extra_search':$('#search-form').serializeArray().concat({name: "username", value: "<?= $username; ?>"}), 'export_format': 'csv', 'export_type': 'queue', 'draw':1, 'length':-1, 'start':0};
                        // utils.safelog(d);

                        <?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) : ?>
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/playertaggedHistory'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                        <?php else : ?>
                            $.post(site_url('/export_data/playertaggedHistory'), d, function(data){
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
				data.extra_search = $('#search-form').serializeArray().concat({
                    name: "username", value: "<?= $username; ?>"
                });
                console.log(data.extra_search);
				$.post(base_url + "api/playertaggedHistory", data, function(data) {
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