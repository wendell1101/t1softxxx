<style>
    .fa {  font-size:initial;  }
    .iframe { border:0; width:0; height:0; }

    table.dataTable tbody tr.selected {
        color: white !important;
        background-color: #7db3d9 !important;
    }

    .tr_cursor{
        cursor: pointer;
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading custom-ph">
                <h3 class="panel-title custom-pt"><i class="fa fa-flag"></i> <?=lang('Player Center API Domains')?></h3>
            </div>
            <div class="panel-body">
                <table class="table table-striped table-bordered table-hover dataTable no-footer dtr-column collapsed" id="table" style="width: 100%;" role="grid" aria-describedby="my_table_info">
                    <thead>
                        <tr role="row">
                            <th id="row-id" style="display: none;"><?=lang('Id');?></th>
                            <th><?=lang('Domain');?></th>
                            <th><?=lang('Remarks');?></th>
                            <th><?=lang('Status');?></th>
                            <th><?=lang('system.word60');?></th>
                            <th><?=lang('cms.updatedby');?></th>
                            <th><?=lang('player_transfer_request.created_at');?></th>
                            <th><?=lang('Updated at');?></th>
                            <th><?=lang('lang.action');?></th>
                        </tr>
                    </thead>

                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal HTML Markup -->
<div id="add-modal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title text-xs-center"><?=lang('Add Player Center API Domains')?> </h4>
            </div>
            <div class="modal-body">
                <form id="main-form" role="form" method="POST" action="<?=BASEURL . 'system_management/add_player_center_api_domain'?>">
                    <input type="hidden" name="_token" value="">
                    <div class="form-group">
                        <label class="control-label"><?=lang('Domain')?></label>
                        <div>
                            <input type="text" class="form-control input-sm" name="domain" value="" maxlength="60" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?=lang('Remarks')?></label>
                        <div>
                            <input type="note" class="form-control input-sm" name="note" maxlength="100">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?=lang('Status')?></label>
                        <div>
                            <select id="status" name="status" class="form-control input-sm">
                                <option selected="selected" value="1"><?=lang('Allowed')?></option>
                                <option value="2"><?=lang('Blocked')?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <button type="submit" class="btn btn-info btn-block"><?=lang('Add')?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden" />
    </form>
<?php }?>

<script type="text/javascript">
    var message = {
        successBlocked   : '<?= lang('Success Blocked Domains'); ?>',
        successUnBlocked   : '<?= lang('Success Unblocked Domains'); ?>',
        successDelete   : '<?= lang('Success Delete Domains'); ?>',
        empty           : '<?= lang('Select Domain'); ?>',
        failedUpdate           : '<?= lang('save.failed'); ?>',

    };

    $('#add-modal').on('hidden.bs.modal', function () {
        $('#add-modal form')[0].reset();
    });

    function emptyMessage() {
        clearNotify();
        $.notify( message.empty ,{type: 'warning'});
    }

    function errorFailedMessage() {
        $.notify( message.failedUpdate ,{type: 'danger'});
    }

    function clearNotify() {
        $.notifyClose('all');
    }

    $(document).ready(function(){
        var baseUrl = '<?=base_url(); ?>';
        var text_blocked = '<span class="help-block" style="color:#ff6666;">Blocked</span>',
            text_allowed = '<span class="help-block" style="color:#66cc66;">Allowed</span>';
        var table = $('#table').DataTable({
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l> <'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            columnDefs: [
                {
                    "targets" : 0,
                    "visible" : false,
                    "searchable": false,
                }
            ],
            createdRow: function( row, data, dataIndex ) {
                $(row).addClass( 'tr_cursor' );
            },
            buttons: [
                {
                    text: "<i class='glyphicon glyphicon-plus-sign'></i> <?php echo lang('Add Player Center API Domain'); ?>",
                    className: "add-domain btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-primary'?>",
                    action: function ( e, dt, node, config ) {
                        let title  = '<?= lang('Add Player Center API Domains'); ?>';
                        let addtext  = '<?= lang('Add'); ?>';
                        $('#main-form').attr('action', '/system_management/add_player_center_api_domain');
                        $("#add-modal h4.modal-title").text(title);
                        $('#main-form input[name=domain').attr('readonly', false);
                        $("#main-form button").html(addtext);
                        $('#add-modal').modal({
                            show: 'true'
                        }); 
                    }
                },
                {
                    text: "<?php echo lang('lang.selectall'); ?>",
                    action: function ( e, dt, node, config ) {
                        table.rows().every(function(){
                            $(this.node()).addClass('selected');
                        });
                    }
                },
                {
                    text: "<?php echo lang('lang.clear.selections'); ?>",
                    action: function ( e, dt, node, config ) {
                        table.rows().every(function(){
                            $(this.node()).removeClass('selected');
                        });
                    }
                },
                {
                    text: "<i class='glyphicon glyphicon-trash'></i> <?php echo lang('Delete Domain'); ?>",
                    className: "delete-domain btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-chestnutrose' : 'btn-danger'?>",
                    action: function ( e, dt, node, config ) {
                        var ids = $.map(table.rows('.selected').data(), function (item) {
                            return item.id
                        });
                        if(ids.length > 0){
                            if (confirm('Do you want to delete the selected domains?')) {
                                $.post(baseUrl + 'system_management/deletePlayerCenterDomains', {domainIds:ids}).done(function(success){
                                    if(success == true){
                                        $.notify( message.successDelete ,{type: 'success'});
                                        table.ajax.reload();
                                    } else {
                                        errorFailedMessage();
                                    }
                                });
                            }
                        } else {
                            emptyMessage();
                        }
                    }
                },
                {
                    text: "<i class='glyphicon glyphicon-ban-circle'></i> <?php echo lang('Block Domain'); ?>",
                    className: "block-domain btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-burntsienna' : 'btn-warning'?>",
                    action: function ( e, dt, node, config ) {
                        var ids = $.map(table.rows('.selected').data(), function (item) {
                            return item.id
                        });
                        if(ids.length > 0){
                            if (confirm('Do you want to block the selected domains?')) {
                                $.post(baseUrl + 'system_management/blockPlayerCenterDomains', {domainIds:ids}).done(function(success){
                                    if(success == true){
                                        $.notify( message.successBlocked ,{type: 'success'});
                                        table.ajax.reload();
                                    } else {
                                        errorFailedMessage();
                                    }
                                });
                            }
                        } else {
                            emptyMessage();
                        }
                    }
                },
                {
                    text: "<i class='glyphicon glyphicon-check'></i> <?php echo lang('Allow Domain'); ?>",
                    className: "allow-domain btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-emerald' : 'btn-success'?>",
                    action: function ( e, dt, node, config ) {
                        var ids = $.map(table.rows('.selected').data(), function (item) {
                            return item.id
                        });
                        if(ids.length > 0){
                            if (confirm('Do you want to unblock the selected domains?')) {
                                $.post(baseUrl + 'system_management/unBlockPlayerCenterDomains', {domainIds:ids}).done(function(success){
                                    if(success == true){
                                        $.notify( message.successUnBlocked ,{type: 'success'});
                                        table.ajax.reload();
                                    } else {
                                        errorFailedMessage();
                                    }
                                });
                            }
                        } else {
                            emptyMessage();
                        }
                    }
                },
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                },
                <?php if( $this->permissions->checkPermissions('player_center_api_domains') ){ ?>
                {
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        var d = {};
                        $.post(site_url('/export_data/export_player_center_api_domains'), d, function(data){
                            if(data && data.success){
                                $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" class="frame"></iframe>');
                            }else{
                                alert('export failed');
                            }
                        });
                    }
                }
                <?php } ?>
            ],
            ajax : {
                url     : baseUrl + 'system_management/player_center_api_domain_list',
                type    : 'GET',
                async   : true
            },
            order : [[ 7, "desc" ]],
            columns : [
                {
                    data : 'id',
                    "visible" : false
                },
                { data : 'domain' },
                { data : 'note' },
                {
                    data : 'status',
                    render : function(data) {
                        var flag = '';
                        flag = data == 1 ? text_allowed : text_blocked;
                        return flag;
                    }
                },
                { data : 'created_by'},
                { data : 'updated_by' },
                { data : 'created_at' },
                { data : 'updated_at' },
                {
                    data : 'id',
                    render : function(data) {
                        return '<button class="btn btn-sm btn-primary edit-domain"><?=lang('lang.edit')?></button>';
                    }
                },
            ]
        });
        $("#row-id").show();
        $('#table tbody').on('click', 'tr', function () {
            $(this).toggleClass('selected');
        });

        $('#table tbody').on('click', '.edit-domain', function () {
            let title  = '<?= lang('Edit Player Center API Domain'); ?>';
            let editText  = '<?= lang('cashier.60'); ?>';
            var rowData = table.row($(this).parents('tr')).data();

            $('#main-form').attr('action', '/system_management/add_player_center_api_domain/'+rowData.id);
            $('#main-form input[name=domain').attr('readonly', true);
            $('#main-form input[name=domain]').val(rowData.domain);
            $('#main-form input[name=note]').val(rowData.note);
            $('#main-form select[name=status]').val(rowData.status);
            $("#main-form button").html(editText);
            $("#add-modal h4.modal-title").text(title);

            table.rows().every(function(){
                $(this.node()).removeClass('selected');
            });

            $('#add-modal').modal({
                show: 'true'
            });
        });

        $('#add-modal').on('hidden.bs.modal', function () {
            table.rows().every(function(){
                $(this.node()).removeClass('selected');
            });
        });
    });


    
</script>


