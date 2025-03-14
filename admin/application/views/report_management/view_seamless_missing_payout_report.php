<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapsePaymentReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="collapsePaymentReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/report_management/viewSeamlessMissingPayoutReport'); ?>" method="get">
                <div class="row">
                    <!-- Date -->
                    <div class="form-group col-md-2 col-lg-2">
                        <label class="control-label">
                            <?= lang('Transaction Date'); ?>:
                        </label>
                        <input id="search_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>
                    <!-- username -->
                    <div class="form-group col-md-2 col-lg-2">
                    <label class="control-label">
                            <?= lang('report.username'); ?>
                        </label>
                        <input type="text" name="by_username" id="by_username" value="<?= $conditions['by_username']; ?>" class="form-control input-sm group-reset" />
                    </div>
                   
                   <!-- status -->
                   <div class="form-group col-md-2 col-lg-2">
                       <label for="status" class="control-label"><?=lang('Status');?> </label>
                       <?=form_dropdown('by_status', $status_list, $conditions['by_status'], 'class="form-control input-sm iovation_report_status group-reset"'); ?>                        
                   </div>

                   <!-- game platforms -->
                    <div class="col-md-2">
                        <label class="control-label" for="by_game_platform_id"><?=lang('player.ui29');?> </label>
                        <select class="form-control input-sm" name="by_game_platform_id" id="by_game_platform_id">
                            <option value="" ><?=lang('lang.selectall');?></option>
                            <?php foreach ($game_platforms as $game_platform) {?>
                                <option value="<?=$game_platform['id']?>" <?php echo $conditions['by_game_platform_id']==$game_platform['id'] ? 'selected="selected"' : '' ; ?>><?=$game_platform['system_code'];?></option>
                            <?php }?>
                        </select>
                    </div>
                    
                </div>

                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-danger'?>">
                            <button type="submit" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>"><?=lang("lang.search")?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-newspaper"></i>
            <?=lang("Seamless Games Missing Payout Report")?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="result_table">
                <thead>
                    <tr>
                        <th><?=lang("Action")?></th>                                      
                        <th><?=lang("Transaction Date")?></th>
                        <th><?=lang("Username")?></th>
                        <th><?=lang("Platform ID")?></th>
                        <th><?=lang("System Code")?></th>
                        <th><?=lang("Round")?></th>
                        <th><?=lang("Transaction ID")?></th>                        
                        <th><?=lang("Amount")?></th>
                        <th><?=lang("Transaction Type")?></th>
                        <th><?=lang("Transaction Status")?></th>
                        <th><?=lang("External Unique ID")?></th>                                                
                        <th><?=lang("Is Fixed")?></th>
                        <th><?=lang("Fixed By")?></th>
                        <th><?=lang("Notes")?></th>
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

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
    <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    var LANG = {        
        AUTOFIX_CONFIRM_MESSAGE : "<?=lang('Are you sure you want to auto fix missing payout.');?>",        
    };

    function notify(type, msg) {
        $.notify({
            message: msg
        }, {
            type: type
        });
    }

    function autoFix(id){
        // alert('Feature not available yet.');
        // return;
        var r = confirm(LANG.AUTOFIX_CONFIRM_MESSAGE);
        if (r == true) {            
            var type = 'POST';
            var params = {
                id: id,
            };
            var url='<?php echo site_url('api/seamless_transaction_auto_fix') ?>';
            executeAction(url, type, params);
        } else {
        
        }        
    }

    function queryStatus(id){
        var r = confirm(LANG.AUTOFIX_CONFIRM_MESSAGE);
        if (r == true) {            
            var type = 'POST';
            var params = {
                id: id,
            };
            var url='<?php echo site_url('api/seamless_transaction_query_status') ?>';
            executeAction(url, type, params);
            setTimeout(function() {
                location.reload();
            }, 3000); 
        } else {
        
        }        
    }

    function executeAction(url, type, params) {

        if (type == 'POST') { 

            $.ajax({
                method: type,
                url: url,
                data: params,
                dataType: "json"
            })
            .done(function(data) {
                // console.log('querystatussuccess: ', data);
                notify('success',data.msg );            
            }).fail(function(data) {
                // console.log('querystatuserror: ', data);
                notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );            
            });


        } else {

            $.ajax({
                method: type,
                url: url,
                dataType: "json"
            })
            .done(function(data) {
                notify('success',data.msg );            
            }).fail(function() {
                notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );
            });
        }
        

    }//end executeAction

    $(document).ready(function(){
        var hide_targets=<?=json_encode($hide_cols); ?>;

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
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();
                       var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/seamlessMissingPayoutReport'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
            ],
            columnDefs: [
                { className: 'text-right', targets: [] },
                { className: 'text-center', targets: [2,3,7] },
                { "visible": false, "targets": hide_targets }
            ],
            order: [ 0, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/seamlessMissingPayoutReport", data, function(data) {
                    $.each(data.data, function(i, v){
                        /*sub = v[10].replace(/<(?:.|\n|)*?>/gm, '');
                        convertedSub = sub.replace(',', '');
                        if(Number.parseFloat(convertedSub)){
                            subTotal+= Number.parseFloat(convertedSub);
                        }*/
                    });
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
            }
        });

        var date_today = new moment().format('YYYY-MM-DD');

        $('#btnResetFields').click(function() {
            $('.group-reset').val('');
            $("#search_date").val(date_today + " to " + date_today);
        });

    	$('#seamless_game_missing_payout_report').addClass("active");

    });
</script>