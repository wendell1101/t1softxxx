<?php
    $is_export_excel_on_queue = $this->utils->isEnabledFeature('export_excel_on_queue');

?><form id="setup_tag_detected_form">
    <div id="setup_tag_detected" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?=lang('Setup the Detected Tag of player');?></h5>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <?=lang('The updated setting will take effect in later detections.');?>
                            </div>
                        </div>

                    </div><!-- EOF class="row" -->
                    <div class="row">
                        <div class="col-md-12">
                            <div>
                                <label class="control-label"><?php echo lang('The Tag added when detected'); ?></label>
                                <span class="result_txt"></span>
                            </div>
                            <div class="form-group">
                                <select name="detected_tag" id="detected_tag" class="form-control input-sm">
                                    <option value="">-<?=lang('lang.select');?>-</option>
                                    <?php foreach ($tags as $tag) { ?>
                                        <?php if($conditions['detected_tag'] == $tag['tagId']): ?>
                                            <option value="<?=$tag['tagId']?>" selected ><?=$tag['tagName']?></option>
                                        <?php else: ?>
                                            <option value="<?=$tag['tagId']?>"><?=$tag['tagName']?></option>
                                        <?php endif;?>
                                    <?php } // EOF foreach ($tags as $tag) {... ?>
                                </select>
                            </div>
                        </div>
                    </div><!-- EOF class="row" -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <button type="button" class="btn btn-scooter" id="updateDetectedTagIdInViewPlayerLoginViaSameIp"><?=lang('lang.save');?></button>
                </div>
            </div>
        </div>
    </div> <!-- EOF #setup_tag_detected -->
</form> <!-- EOF #setup_tag_detected_form -->

<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseManyPlayerLoginViaSameIpReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div> <!-- EOF class="panel-heading" -->
    <div id="collapseManyPlayerLoginViaSameIpReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/report_management/viewPlayerLoginViaSameIp'); ?>" method="get">
                <div class="row">
                    <!-- Date Time, created_at -->
                    <div class="col-md-3">
                        <label class="control-label"><?= lang('view_player_login_via_same_ip.create_at'); ?></label>
                        <div class="input-group">
                            <input id="created_at_search_date" class="form-control input-sm dateInput" data-start="#created_at_date_from" data-end="#created_at_date_to" data-time="true"/>
                            <span class="input-group-addon input-sm">
                                <input type="hidden" name="created_at_enabled_date" value="<?=$conditions['created_at_enabled_date']?>" >
                                <input type="checkbox" id="checkbox_created_at_enabled_date" <?=empty($conditions['created_at_enabled_date']) ? '' : 'checked="checked"'; ?>>
                            </span>
                        </div>
                        <input type="hidden" id="created_at_date_from" name="created_at_date_from" value="<?=$conditions['created_at_date_from']?>"/>
                        <input type="hidden" id="created_at_date_to" name="created_at_date_to" value="<?=$conditions['created_at_date_to']?>"/>
                    </div>
                    <!-- EOF Date Time, created_at -->

                    <!-- Date Time, logged_in_at -->
                    <div class="col-md-3">
                        <label class="control-label"><?= lang('view_player_login_via_same_ip.logged_in_at'); ?></label>
                        <div class="input-group">
                            <input id="logged_in_at_search_date" class="form-control input-sm dateInput" data-start="#logged_in_at_date_from" data-end="#logged_in_at_date_to" data-time="true"/>
                            <span class="input-group-addon input-sm">
                                <input type="hidden" name="logged_in_at_enabled_date" value="<?=$conditions['logged_in_at_enabled_date']?>" >
                                <input type="checkbox" id="checkbox_logged_in_at_enabled_date" value="1" <?=empty($conditions['logged_in_at_enabled_date']) ? '' : 'checked="checked"'; ?>>
                            </span>
                        </div>
                        <input type="hidden" id="logged_in_at_date_from" name="logged_in_at_date_from" value="<?=$conditions['logged_in_at_date_from']?>"/>
                        <input type="hidden" id="logged_in_at_date_to" name="logged_in_at_date_to" value="<?=$conditions['logged_in_at_date_to']?>"/>
                    </div>
                    <!-- EOF Date Time, logged_in_at -->

                    <!-- username -->
                    <div class="col-md-3">
                        <label for="username" class="control-label"><?=lang('Player Username')?></label>
                        <input type="radio" id="search_by_exact" name="search_by" value="2" <?php echo $conditions['search_by']  == '2' ? 'checked="checked"' : '' ?> >
                        <label for="search_by_exact" class="control-label"><?=lang('Exact')?></label>
                        <input type="radio" id="search_by_similar" name="search_by" value="1" <?php echo $conditions['search_by']  == '1' ? 'checked="checked"' : '' ?>>
                        <label for="search_by_similar" class="control-label"><?=lang('Similar')?></label>
                        <input type="text" name="username" class="form-control" value="<?=$conditions['username']?>"/>
                    </div>
                    <!-- EOF username -->
                </div>

                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-9">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-danger'?>">
                            <button type="submit" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>"><?=lang("lang.search")?></button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div> <!-- EOF id="collapseManyPlayerLoginViaSameIpReport" -->
</div> <!-- EOF class="panel panel-primary hidden" -->


<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-newspaper"></i>
            <?=lang("view_player_login_via_same_ip.report")?>

            <?php if( $this->permissions->checkPermissions('setup_tag_of_players_login_via_same_ip') ): ?>
            <a href="javascript:void(0);" class="btn  pull-right btn-xs btn-info setupTagDetected" id="setupTagDetected">
                <i class="fa fa-tags"></i> <?=lang('view_player_login_via_same_ip.setup_tag');?>
            </a>
            <?php endif; // EOF if( $this->permissions->checkPermissions('setup_tag_of_players_login_via_same_ip') ) ?>
        </h4>
    </div> <!-- EOF class="panel-heading" -->
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="result_table">
                <thead>
                    <tr>
                        <th><?=lang("IP")?></th>
                        <th><?=lang("view_player_login_via_same_ip.create_at")?></th>
                        <th><?=lang("Username")?></th>
                        <th><?=lang("view_player_login_via_same_ip.logged_in_at")?></th>
                        <th><?=lang("view_player_login_via_same_ip.login_result")?></th>
                        <!-- <th><?=lang("Action")?></th> -->
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<div id="conf-modal"  class="modal fade bs-example-modal-md"  data-backdrop="static"
data-keyboard="false"  tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header panel-heading">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="myModalLabel" ><?=lang('sys.ga.conf.title');?></h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="help-block" id="conf-msg">

                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="cancel-action" data-dismiss="modal"><?=lang('pay.bt.cancel');?></button>
                <button type="button" id="confirm-action" class="btn btn-primary"><?=lang('pay.bt.yes');?></button>
            </div>
        </div>
    </div>
</div>

<?php if($is_export_excel_on_queue) : ?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php endif; // EOF if($is_export_excel_on_queue) ?>

<script type="text/javascript">


    function notify(type, msg) {
        $.notify({
            message: msg
        }, {
            type: type
        });
    }


    $(document).ready(function(){


        var dataTable = $('#result_table').DataTable({
            stateSave: true,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
                ,{
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm _export_btn <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                    <?php if($is_export_excel_on_queue): ?>
                        // export via queue
                        var form_params=$('#search-form').serializeArray();
                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue', 'draw':1, 'length':-1, 'start':0};
                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/reportManyPlayerLoginViaSameIpLogsListViaQueue'));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();
                    <?php endif; // EOF if($is_export_excel_on_queue): ?>
                    <?php if( ! $is_export_excel_on_queue ): ?>
                        // directly export
                        var form_params=$('#search-form').serializeArray();
                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue', 'draw':1, 'length':-1, 'start':0};
                        var _ajax = $.ajax({
                            url:  site_url('/export_data/reportManyPlayerLoginViaSameIpLogsListdDirectly'),
                            type: 'POST',
                            data: d,
                            beforeSend: function (jqXHR, settings) {
                                /// show loader
                                $('._export_btn').button('loading');
                            } // EOF beforeSend
                        }).done(function(data) {
                            if(data && data.success){
                                $('.exported_csv_file').remove();
                                $('body').append('<iframe class="exported_csv_file" src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                            }else{
                                alert('export failed');
                            }
                        }).fail(function(){
                            alert('export failed');
                        });// EOF var _ajax = $.ajax({...
                        _ajax.always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
                            /// revert loader
                            $('._export_btn').button('reset');
                        }); // EOF _ajax.always
                    <?php endif; // EOF if( ! $is_export_excel_on_queue ): ?>
                    }// EOF action: function ( e, dt, node, config ) {...
                }
            ],
            columnDefs: [
                { className: 'text-right', targets: [] },
                { className: 'text-center', targets: [2,3] },
                { "visible": false, "targets": [] }// hide_targets }
            ],
            order: [ 0, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                var _post = $.post(base_url + "api/player_login_via_same_ip_list", data, function(data) {
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

        view_player_login_via_same_ip.default_conditions = <?=json_encode($conditions)?>;
        view_player_login_via_same_ip.detected_tag_id_key = '<?=Player_login_via_same_ip_logs::_operator_setting_name4detected_tag_id?>';
        view_player_login_via_same_ip.onReady();

    });
</script>