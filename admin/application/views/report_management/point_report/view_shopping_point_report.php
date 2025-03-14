<style>
    .font-bold{
        font-weight: bold;
    }
</style>
<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseShoppinPointReport" class="btn btn-xs btn-primary <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseShoppinPointReport" class="panel-collapse <?= $this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="form-filter" class="form-horizontal" method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label"><?=lang('report.sum02')?></label>
                        <input class="form-control dateInput input-sm" data-start="#date_from" data-end="#date_to" data-time="true"/>
                        <input type="hidden" id="date_from" name="date_from" value="<?=$conditions['date_from'];?>"/>
                        <input type="hidden" id="date_to" name="date_to" value="<?=$conditions['date_to'];?>"/>
                    </div>
                    <div class="col-md-3">
                        <label for="username" class="control-label">
                            <input type="radio" name="search_by" value="1" checked <?=$conditions['search_by'] == '1' ? 'checked="checked"' : '' ?> /> <?=lang('Similar');?>
                            <?=lang('Username'); ?>
                            <input type="radio" name="search_by" value="2" <?=$conditions['search_by'] == '2' ? 'checked="checked"' : ''?> /> <?=lang('Exact'); ?>
                            <?=lang('Username'); ?>
                        </label>
                        <input type="text" name="username" id="username" class="form-control input-sm" value="<?= $conditions['username'];?>" <?= ($conditions['group_by'] != 'player_id' && $conditions['group_by'] != '') ? 'disabled' : ''   ?> />
                    </div>
                    <div class="col-md-4">
                        <label class="control-label" for="playerlevel"><?=lang('report.pr03')?></label>
                        <select name="playerlevel" id="playerlevel" class="form-control input-sm">
                            <option value=""><?=lang('lang.selectall')?></option>
                            <?php foreach ($allLevels as $key => $value) { ?>
                                <?php if($conditions['playerlevel'] == $value['vipsettingcashbackruleId'] ): ?>
                                    <option value="<?=$value['vipsettingcashbackruleId']?>" selected ><?=lang($value['groupName']) . ' ' . lang($value['vipLevel'])?></option>
                                <?php else: ?>
                                    <option value="<?=$value['vipsettingcashbackruleId']?>"><?=lang($value['groupName']) . ' ' . lang($value['vipLevel'])?></option>
                                <?php endif;?>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <label for="point_type" class="control-label"><?=lang('Point Type')?></label>
                        <select name="point_type" id="point_type" class="form-control input-sm">
                            <option value="1" <?php if($conditions['point_type'] == 1) echo 'selected'; ?>><?=lang('All')?></option>
                            <option value="2" <?php if($conditions['point_type'] == 2) echo 'selected'; ?>><?=lang('Manual')?></option>
                            <option value="3" <?php if($conditions['point_type'] == 3) echo 'selected'; ?>><?=lang('Automatic')?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="depamt2"><?=lang('Transaction Amount') . " >="?></label>
                        <input type="text" name="depamt2" id="depamt2" class="form-control number_only input-sm" value="<?= $conditions['depamt2'];?>"/>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="depamt1"><?=lang('Transaction Amount') . " <="?></label>
                        <input type="text" name="depamt1" id="depamt1" class="form-control number_only input-sm" value="<?= $conditions['depamt1'];?>"/>
                    </div>
                    <div class="col-md-2">
                        <label for="affiliate_agent" class="control-label"><?=lang('Under Affiliate/Agent Status')?></label>
                        <select name="affiliate_agent" id="affiliate_agent" class="form-control input-sm">
                            <option value=""><?=lang('lang.selectall')?></option>
                            <option value="2" <?php if($conditions['affiliate_agent'] == 2) echo 'selected'; ?>><?=lang('Under Affiliate Only')?></option>
                            <option value="3" <?php if($conditions['affiliate_agent'] == 3) echo 'selected'; ?>><?=lang('Under Agent Only')?></option>
                            <option value="4" <?php if($conditions['affiliate_agent'] == 4) echo 'selected'; ?>><?=lang('Under Affiliate or Agent')?></option>
                            <option value="1" <?php if($conditions['affiliate_agent'] == 1) echo 'selected'; ?>><?=lang('Not under any Affiliate or Agent')?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label"><?=lang('Player Tag')?>:</label>
                        <select name="tag_list[]" id="tag_list" multiple="multiple" class="form-control input-md">
                            <?php if (!empty($tags)): ?>
                                <option value="notag" id="notag" <?=is_array($selected_tags) && in_array('notag', $selected_tags) ? "selected" : "" ?>><?=lang('player.tp12')?></option>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?=$tag['tagId']?>" <?=is_array($selected_tags) && in_array($tag['tagId'], $selected_tags) ? "selected" : "" ?> ><?=$tag['tagName']?></option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                    </div>
                </div>
                <!-- <div class="row">
                </div> -->
                <div class="row">
                    <div class="col-md-2 col-md-offset-10" style="padding-top: 20px">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-portage btn-sm pull-right">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-users"></i> <?=lang('report.s11')?> </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="myTable">
                <thead>
                    <tr>
                        <th id="th-trans-time" ><?=lang('Transaction Date And Time')?></th>
                        <th id="th-username" ><?=lang('Player Username')?></th>
                        <th id="th-player-tag" ><?=lang('Player Tag')?></th>
                        <th id="th-vip-level" ><?=lang('VIP Level')?></th>
                        <th id="th-trans-type" ><?=lang('Transaction Type')?></th>
                        <th id="th-amount" ><?=lang('Amount')?></th>
                        <th id="th-conversion-rate" ><?=lang('Conversion Rate')?></th>
                        <th id="th-points" ><?=lang('Points')?></th>
                        <th id="th-point-type" ><?=lang('Point Type')?></th>
                        <th id="th-action" ><?=lang('Action Log')?></th>
                        <th id="th-remarks" ><?=lang('Remarks')?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?=lang('Total')?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th class="total-point"></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
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

        $('#tag_list').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonClass: 'btn btn-sm btn-default',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Tags');?>';
                } else {
                    var labels = [];
                    options.each(function() {
                        if ($(this).attr('label') !== undefined) {
                            labels.push($(this).attr('label'));
                        }
                        else {
                            labels.push($(this).html());
                        }
                    });
                    return labels.join(', ') + '';
                }
            }
        });

        var PLAYER_REPORT_DT_CONFIG   =  <?php echo json_encode($this->config->item('player_report_dt_config'))?>,
            tableColumns              = [],
            textRightTargets          = [],
            hiddenColsTargets         = [],
            disableColsTargets        = [],
            defaultExportCols         = [],
            textRightTargetsIndexes   = [],
            hiddenColsTargetsIndexes  = [],
            disableColsTargetsIndexes = [],
            j = 0;

        $( "#myTable" ).find('th').each(function( index ) {
            if ($(this).attr('id') !== undefined){
                var id = $(this).attr('id').replace('th-', "");
                tableColumns[id] = index;
            }
        });

        Object.keys(tableColumns).forEach(function(key, index) {
            if (textRightTargets.indexOf(key) > -1) {
                textRightTargetsIndexes.push(tableColumns[key]);
            }
            if (hiddenColsTargets.indexOf(key) > -1) {
                hiddenColsTargetsIndexes.push(tableColumns[key]);
            }
            if (disableColsTargets.indexOf(key) > -1) {
                disableColsTargetsIndexes .push(tableColumns[key]);
            }
            j++
        }, tableColumns);

        var dataTable = $('#myTable').DataTable({
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: 50,
            autoWidth: false,
            searching: false,
            dom: "<'panel-body' <'pull-right'B><'#export_select_columns.pull-left'><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i><'dataTable-instance't><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            columnDefs: [
                { className: 'text-right font-bold', targets: textRightTargetsIndexes },
                { visible: false, targets:  hiddenColsTargetsIndexes },
                { orderable: false, targets: disableColsTargetsIndexes }
            ],
            colsNamesAliases:[],
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: "btn-linkwater"
                },
                <?php if ($export_report_permission) :?>
                    {
                        text: "<?php echo lang('CSV Export'); ?>",
                        className:"btn btn-sm btn-portage export-all-columns",
                        action: function ( e, dt, node, config ) {
                            var form_params=$('#form-filter').serializeArray();
                            var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,'draw':1, 'length':-1, 'start':0};
                            utils.safelog(d);

                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/shopping_point_report'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                        }
                    }
                <?php endif; ?>
            ],
            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            "initComplete": function(settings){
                $('#myTable thead th').each(function () {
                    return false;
                });

                /* Apply the tooltips */
                $('#myTable thead th[title]').tooltip({
                    "container": 'body'
                });
            },
            ajax: function (data, callback, settings) {
                data.extra_search = $('#form-filter').serializeArray();
                $.post(base_url + "api/shoppingPointReport", data, function(data) {

                    //add to datatable property
                    dataTable.init().colsNamesAliases = data.cols_names_aliases;
                    $('.total-point').html(data['total_point']);
                    callback(data);
                }, 'json');
            }
        });

        dataTable.on( 'draw', function () {
            $("#myTable_wrapper .dataTable-instance").floatingScroll("init");
        });

        // $('.export_excel').click(function(){
        //     var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0};

        //     $.post(site_url('/export_data/shopping_point_report'), d, function(data){
        //         //create iframe and set link
        //         if(data && data.success){
        //             $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
        //         }else{
        //             alert('export failed');
        //         }
        //     });
        // });
    });

    function addCommas(nStr){
        nStr += '';
        var x = nStr.split('.');
        var x1 = x[0];
        var x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    }
</script>
