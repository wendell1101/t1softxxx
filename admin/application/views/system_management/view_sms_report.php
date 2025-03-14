<style type="text/css">
    #timezone, input, select{
        font-weight: bold;
    }
    #timezone{
        height: 36px;
    }

    .select2-container--default .select2-selection--single{
        padding-top: 2px;
        height: 35px;
        font-size: 1.2em;
        position: relative;
        border-radius: 0;
        font-size:12px;
    }
    .removeLink {
        text-decoration: none;
        color : #222222;
    }
    .removeLink:hover {
        text-decoration:none;
        color : #222222;
        cursor:text;
    }
</style>
<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseGamesReport" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
            <?php include __DIR__ . "/../includes/report_tools.php"?>
        </h4>
    </div>

    <div id="collapseGamesReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="form-filter" class="form-horizontal" method="GET" onsubmit="return validateForm();">
                <div class="row">
                    <div class="col-md-4 col-lg-4">
                        <label class="control-label" for="group_by"><?=lang('Date')?> </label>
                        <input class="form-control dateInput" id="datetime_range" data-start="#datetime_from" data-end="#datetime_to" data-time="true"/>
                        <input type="hidden" id="datetime_from" name="datetime_from" value="<?=$conditions['datetime_from'];?>"/>
                        <input type="hidden" id="datetime_to" name="datetime_to" value="<?=$conditions['datetime_to'];?>"/>
                     </div>
                    <div class="col-md-2 col-lg-2">
                        <label class="control-label" for="mobile"><?=lang('Mobile')?> </label>
                        <input type="text" name="mobile" id="mobile" class="form-control input-sm"
                            value='<?php echo $conditions["mobile"]; ?>'/>
                    </div>
                    <div class="col-md-2 col-lg-2">
                        <label class="control-label" for="flag"><?=lang('Flag')?> </label>
                        <select name="flag" id="flag" class="form-control input-sm">
                            <option value=""><?=lang('All')?></option>
                            <option value="1" <?=$conditions['flag']==1 ? 'selected="selected"' : ''?>>Normal</option>
                            <option value="2" <?=$conditions['flag']==2 ? 'selected="selected"' : ''?>>Error</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-2">
                        <label class="control-label" style="margin-top: 11px;"></label><br>
                        <button class="btn btn-sm btn-portage"><?=lang('Search')?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-dice"></i> <?=lang('View SMS Report')?> </h4>
    </div>
    <div class="panel-body">
        <table class="table table-bordered table-hover" id="myTable">
            <thead>
                <tr>
                    <th><?=lang('Date')?></th>
                    <th><?=lang('ID')?></th>
                    <th><?=lang('API')?></th>
                    <th><?=lang('Mobile')?></th>
                    <th><?=lang('Status Code')?></th>
                    <th><?=lang('Status Text')?></th>
                    <th><?=lang('Flag')?></th>
                    <th><?=lang('Content')?></th>
                </tr>
            </thead>
        </table>
    </div>
    <div class="panel-footer"></div>
</div>

<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
    <input name='json_search' type="hidden">
</form>

<script type="text/javascript">

    function validateForm(){
        return true;
    }


    $(document).ready(function(){
        $('#view_sms_report').addClass('active');

        var dataTable = $('#myTable').DataTable({
            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>

            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
                <?php
                //always hide
                if (false && $export_report_permission) {?>
                ,{
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-primary',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#form-filter').serializeArray();
                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue', 'draw':1, 'length':-1, 'start':0};

                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/export_response_result_list'));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();
                    }
                }
                <?php } ?>
            ],
            order: [ 0, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                var formData = $('#form-filter').serializeArray();
                data.extra_search = formData;
                //data.is_export = true;
                $.post(base_url + "api/sms_report_list", data, function(data) {
                    callback(data);

                    var info = dataTable.page.info();
                    if (info.page != 0 && info.page > (info.pages-1) ) {
                        dataTable.page('first').draw();
                        dataTable.ajax.reload();
                    }
                }, 'json');
            }
        });
    });
</script>
