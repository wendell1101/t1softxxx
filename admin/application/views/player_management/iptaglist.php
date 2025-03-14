<?=$this->load->view("resources/third_party/bootstrap-colorpicker")?>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-search"></i> <?=lang("lang.search")?> </h4>
    </div>
    <div class="panel-body">
        <form class="col-md-12" id="search-form">
            <!-- inputs, Tag Name, Tag Color, Description, Tag IP -->
            <div class="form-group col-md-2">
                <label class="control-label"><?=lang('player.it02');?> : </label>
                <input type="text" name="name" class="form-control input-sm"/>
            </div>
            <div class="form-group col-md-2">
                <label class="control-label"><?=lang('player.it06');?> : </label>
                <div class="input-group colorpicker-component" sbe-ui-toogle="colorpicker" data-format="hex">
                <input type="text" name="color" class="form-control input-sm" placeholder="<?=lang('player.it06');?>" value="<?php set_value('color', '#000000')?>" >
                <span class="input-group-addon"><i></i></span>
            </div>
            </div>
            <div class="form-group col-md-3">
                <label class="control-label"><?=lang('player.it04');?> : </label>
                <input type="text" name="description" class="form-control input-sm"/>
            </div>
            <div class="form-group col-md-2">
                <label class="control-label"><?=lang('player.it03');?> : </label>
                <input type="text" name="ip" class="form-control input-sm"/>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-12 text-right">
                <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-linkwater btn-sm">
                <button type="button" class="btn pull-right btn-portage search_btn btn-sm"><i class="fa fa-search"></i> <?=lang('lang.search');?></button>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title pull-left">
            <i class="icon-profile"></i>
            &nbsp;
            <?=lang('player.it01');?>
        </h4>

        <button class="btn pull-right btn-xs btn-info addiptag-btn" >
            <i class="fa fa-plus-circle"></i> <?=lang('player.it07');?>
        </button>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body" >
        <div class="table-responsive">
            <table class="table table-hover table-condensed table-bordered" id="myTable" style="width: 100%;">
                <button class="btn btn-danger btn-sm btn-action delete_selected" data-toggle="tooltip" data-placement="top" title="<?=lang('cms.deletesel');?>" style="margin-top: 15px">
                    <i class="glyphicon glyphicon-trash" style="color:white;"></i>
                </button>
                <thead>
                    <tr>
    					<th><?=lang('Select All');?> <input type="checkbox" class="selecte_all" > </th> <!-- // # 1, Select All -->
    					<th><?=lang('player.it02');?></th> <!-- // # 2, Tag name -->
                        <th><?=lang('player.it03');?></th> <!-- // # 3, Tag IP -->
                        <th><?=lang('player.it04');?></th> <!-- // # 4, Description -->
    					<th><?=lang('player.it06');?></th> <!-- // # 5, Tag Color -->
                        <th><?=lang('sys.createdby');?></th> <!-- // # 6, Created By -->
    					<th><?=lang('player.it05');?></th> <!-- // # 7, Action -->

                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
     $(document).ready(function(){
        var dataTable = $('#myTable').DataTable({
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),

            // order: [[5, 'desc']],
            searching: true,

            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'text-center'r><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",

            buttons: [
                {
                    extend: 'colvis',
					className:'<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : '' ?>',
                    postfixButtons: [ 'colvisRestore' ]
                },
                <?php if( $this->permissions->checkPermissions('export_iptaglist') ){ ?>
                {
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {

                        var form_params = $('#search-form').serializeArray();

                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};

                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/ipTagList'));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();
                    }
                }
                <?php } ?>
            ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/ipTagList", data, function(data) {
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                        // $('[data-toggle=confirmation]').confirmation({
                        //     rootSelector: '[data-toggle=confirmation]',
                        //   // other options
                        // });
                    }
                }, 'json');
            },
            columnDefs: [
                { orderable: false
                , className: 'text-center'
                , render: function(data, type, row) {
                    return '<input type="checkbox" class="checkWhite" data-ip_tag_list_id="' + data + '" name="tag[]" value="' + data + '"/>';
                }, targets: [ 0 ] } // for checkbox field, "ip_tag_list.id".
                , { render: function(data, type, row) {
                    var _color = data;
                    var _invertColorBW = ip_tag_list.invertColor(_color, true);
                    return '<span style="background-color: ' + _color + '; color:'+_invertColorBW+';">' + _color + '</span>';
                }, targets: [ 4 ] } // for ip_tag_list.color
                , { orderable: false
                , className: 'text-center'
                ,render: function(data, type, row) {

                    var nIndex = -1;
                    var regexList = [];

                    nIndex++; // #1 ip_tag_id
                    regexList[nIndex] = {};
                    regexList[nIndex]['regex'] = /\$\{ip_tag_id\}/gi; // ${ip_tag_id}
                    regexList[nIndex]['replaceTo'] = data;

                    var action_html = ip_tag_list.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-ip_tag_action_wrapper', regexList);
                    return action_html;
                }, targets: [ 6 ] } // for action
            ]
        });

        dataTable.on( 'draw', function (e, settings) {
            // console.log('in draw.e', e, 'settings', settings);
        });

        var _options = {};
        _options.dataTable = dataTable;
        _options.langs = {};
        _options.langs.IP_Tag_Add = "<?=lang('Add IP Tag');?>";
        _options.langs.IP_Tag_Edit = "<?=lang('Edit IP Tag');?>";
        var _ip_tag_list = ip_tag_list.initial(_options);
        _ip_tag_list.onReady();

        $('#btnResetFields').click(function() {
            $('[name="name"]').val("");
            $('[name="color"]').val("");
            $('[name="description"]').val("");
            $('[name="ip"]').val("");
            $('.input-group-addon > i').removeAttr('style');
        });
    });
</script>


<script type="text/template" id="tpl-ip_tag_action_wrapper">
    <!-- param, ip_tag_id -->
    <div class="ip_tag_action_wrapper" data-id="${ip_tag_id}">
        <button class="btn btn-xs btn-zircon editiptag-btn">
            <span class="glyphicon glyphicon-edit" data-toggle="tooltip" title="Edit IP Tag" data-placement="top">
            </span>
        </button>

        <button class="btn btn-xs btn-zircon deliptag-btn">
            <span class="glyphicon glyphicon-trash" data-toggle="tooltip" title="Delete IP Tag" data-placement="top">
            </span>
        </button>
    </div>
</script>


<script type="text/template" id="tpl-will_delete_wrapper">
    <!-- param, ip_tag_id, ip_tag_ip, ip_tag_name -->
    <div class="row will_delete_wrapper">
        <div class="col-md-6 will_delete_name">${ip_tag_name}</div>
        <div class="col-md-6 will_delete_ip">${ip_tag_ip}</div>
        <input type="hidden" name="ip_tag_id[]" value="${ip_tag_id}">
    </div>
</script>

<div class="modal fade" id="edit_ip_tag_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
			<form class="edit-form" action="<?=base_url('/player_management/edit_iptag')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel"><?=lang('IP Tag Edit');?></h4>
				</div>
				<div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group required field_input_wrapper">
                                <label for="tagName" class="control-label"><?=lang('player.it02');?> : </label>
                                <input type="text" id="tagName" name="name" class="form-control input-sm" placeholder="<?=lang('player.it02');?>" >
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group required field_input_wrapper">
                                <label for="tagIp" class="control-label"><?=lang('player.it03');?> : </label>
                                <input type="text" id="tagIp" name="ip" class="form-control input-sm" placeholder="<?=lang('player.it03');?>" >
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group required field_input_wrapper">
                                <label for="tagColor" class="control-label"><?=lang('player.it06');?> : </label>
                                <div class="input-group required colorpicker-component" sbe-ui-toogle="colorpicker" data-format="hex">
                                    <input type="text" id="tagColor" name="color" class="form-control input-sm" placeholder="<?=lang('player.it06');?>" value="<?php set_value('color', '#000000')?>" required>
                                    <span class="input-group-addon"><i></i></span>
                                </div>
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group field_input_wrapper">
                                <label for="tagDescription" class="control-label"><?=lang('player.it04');?><span class="text-danger"></span></label>
                                <input type="text" id="tagDescription" name="description" class="form-control input-sm" placeholder="<?=lang('Enter description');?>">
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                            </div>
                        </div>
                    </div>
				</div> <!-- EOF .modal-body -->
				<div class="modal-footer">
                    <input type="hidden" name="ip_tag_list_id">
					<input type="input" class="btn btn-primary submit-btn" value="<?=lang('lang.submit')?>">
				</div>
			</form>
        </div>
    </div>
</div>


<div class="modal fade" id="delete_ip_tag_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
			<form class="delete-form" action="<?=base_url('/player_management/delete_iptag')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel"><?=lang('IP Tag Delete Confirmation');?></h4>
				</div>
				<div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="tagColor" class="control-label">
                                <?=lang('Are You Sure?');?>
                            </label>
                        </div>
                    </div>
                    <div class="row will_delete_list">
                        <div class="col-md-10 col-md-offset-2 will_delete_list_wrapper">
                            <!-- // generate by tpl-will_delete_wrapper
                            <div class="row will_delete_wrapper">
                                <div class="col-md-6 will_delete_name">aaa1</div>
                                <div class="col-md-6 will_delete_ip">192.168.10.1</div>
                                <input type="hidden" name="ip_tag_id[]" class="will_delete_id">
                            </div>
                            <div class="row">
                                <div class="col-md-6">aaa2</div>
                                <div class="col-md-6">192.168.10.2</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">aaa2</div>
                                <div class="col-md-6">192.168.10.2</div>
                            </div>
                            -->
                        </div>
                    </div>

				</div> <!-- EOF .modal-body -->
				<div class="modal-footer">
					<input type="button" class="btn btn-primary submit-btn" value="<?=lang('lang.submit')?>">
				</div>
			</form>
        </div>
    </div>
</div>
<style>
    .will_deletes_list {
        max-height: 150px;
        overflow-y: scroll;
    }
</style>