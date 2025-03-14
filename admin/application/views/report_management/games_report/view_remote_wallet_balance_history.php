
<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-dice"></i> <?=lang('Remote wallet balance history')?> </h4>
    </div>
    <div class="panel-body" >
        <div id="collapseGamesReport" class="panel-collapse">
            <div class="panel-body">
                <div class="row">
                    <!-- Date -->
                    <div class="col-md-4 col-lg-4">
                        <label class="control-label"><?=lang('Date')?> </label>
                        <input class="form-control dateInput input-sm" id="datetime_range" data-start="#datetime_from" data-end="#datetime_to" data-time="true"/>
                        <input type="hidden" id="datetime_from" name="datetime_from" value="<?=$conditions['datetime_from'];?>"/>
                        <input type="hidden" id="datetime_to" name="datetime_to" value="<?=$conditions['datetime_to'];?>"/>
                    </div>                
                    <!-- Player Username -->
                    <div class="col-md-2">
                        <label class="control-label" for="username"><?=lang('Player Username')?> </label>
                        <input type="text" name="username" id="username" class="form-control input-sm"
                            value='<?php echo $conditions["username"]; ?>'/>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="by_game_platform_id"><?=lang('Provider')?> </label>
                        <select name="by_game_platform_id" id="by_game_platform_id" class="form-control input-sm">
                            <option value=""><?=lang('lang.all') . ' ' . lang('cms.gameprovider')?></option>
                            <?php foreach ($game_platforms as $game_platform): ?>
                                <option value="<?=$game_platform['id']?>"><?=$game_platform['system_code']?></option>
                            <?php endforeach?>
                        </select> 
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1 pull-left" style="text-align:center;padding-top:24px;">
                        <input type="submit" value="<?=lang('lang.search')?>" id="btn-submit-remote-view" id="search_main" class="btn col-md-12 btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix">
            <table id="remote_wallet_balance_history_table" class="table table-bordered">
                <thead>
                    <th><?=lang('Action');?></th>
                    <th><?=lang('Player Username');?></th>
                    <th><?=lang('Date');?></th>
                    <th><?=lang('Amount');?></th>
                    <th><?=lang('Before Balance') ?></th>
                    <th><?=lang('After Balance') ?></th>
                    <th><?=lang('Game Platform') ?></th>
                    <th><?=lang('External Unique ID') ?></th>
                    <th><?=lang('Status') ?></th>
                    <th><?=lang('Query Status') ?></th>
                    <th><?=lang('Reason') ?></th>
                    <th><?=lang('Fix Flag') ?></th>
                </thead>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) {?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    // var dataTable;
    $(document).ready(function(){
         var dataTable = $('#remote_wallet_balance_history_table').DataTable({
            dom: "<'row'<'col-md-12'<'pull-right'B><'pull-right progress-container'>l<'dt-information-summary2 text-info pull-left' i>>><'table-responsive't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
            autoWidth: false,
            searching: false,
            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: ['btn-linkwater']
                },
                {
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {

                        // var form_params=$('#search-form_new').serializeArray();
                        var form_params= [
                            {
                                'name':'player_id',
                                'value':''
                            },
                            {
                                'name':'date_from',
                                'value':$('#datetime_from').val()
                            },
                            {
                                'name':'date_to',
                                'value':$('#datetime_to').val()
                            },
                            {
                                'name': 'by_game_platform_id',
                                'value': $('#by_game_platform_id').val()
                            },
                            {
                                'name': 'player_username',
                                'value': $('#username').val()
                            }
                        ];

                        console.log(form_params);

                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                                'draw':1, 'length':-1, 'start':0};
                         // console.log(d);

                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/remote_wallet_balance_history/'+ ''));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();
                    }
                }
            ],
            order: [
                [4, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = [
                    {
                        'name':'date_from',
                        'value':$('#datetime_from').val()
                    },
                    {
                        'name':'date_to',
                        'value':$('#datetime_to').val()
                    },
                    {
                        'name': 'by_game_platform_id',
                        'value': $('#by_game_platform_id').val()
                    },
                    {
                        'name': 'player_username',
                        'value': $('#username').val()
                    }
                ];

                $.post(base_url + 'api/remoteWalletBalanceHistory/' + '', data, function(data) {
                    callback(data);
                },'json');
            }
        });

        $('#btn-submit-remote-view').click( function() {
            dateTimeFrom = $('#datetime_from').val();
            partsFrom = dateTimeFrom.split(/[- :]/);
            monthFrom = partsFrom[1];
            yearFrom = partsFrom[0];

            dateTimeTo = $('#datetime_to').val();
            partsTo = dateTimeTo.split(/[- :]/);
            monthTo = partsTo[1];
            yearTo = partsTo[0];

            if (monthFrom == monthTo && yearFrom == yearTo) {
                
            } else {
                alert("Month should be the same");
                return false;
            }
            dataTable.ajax.reload();
        });


        $(document).on('click', '.btn-remote-wallet-query-status', function () {
            id = $(this).data('id');
            var notify=_pubutils.notifyLoading("<?=lang('Loading');?>...");
            var extra = {
                date: $('#datetime_from').val(),
            };
            //call
            $.post('/payment_management/query_remote_wallet_transaction_status/'+id, extra, function(data){
                _pubutils.safelog(data);

                var err="";

                if(data) {
                    if(data['success']) {
                        var msg=null;
                        if(data['status_message']) {
                            msg=data['status_message'];
                        } else {
                            msg="<?=lang('Unknown Status');?>";
                        }

                        _pubutils.notifySuccess(msg);
                    } else {
                        err=data['error_message'];
                        _pubutils.notifyErr(err);
                    }
                } else {
                    err="<?=lang('Query Status Failed');?>";
                    _pubutils.notifyErr(err);
                }
            }).fail(function(){
                err="<?=lang('Failed');?>";
                _pubutils.notifyErr(err);
            }).always(function(){
                _pubutils.closeNotify(notify);
                dataTable.ajax.reload();
            });

        });

        $(document).on('click', '.btn-remote-wallet-auto-fix', function () {
            if(!confirm("<?=lang('Are you sure')?>?")){
                return;
            }

            id = $(this).data('id');
            var notify=_pubutils.notifyLoading("<?=lang('Loading');?>...");
            var extra = {
                date: $('#datetime_from').val(),
            };

            //call
            $.post('/payment_management/auto_fix_remote_wallet_transaction/'+id, function(data){

                _pubutils.safelog(data);

                var err="";

                if(data){
                    if(data['success']){

                        var msg="<?=lang('Successful');?>";
                        if(data['status_message']){
                            msg=data['status_message'];
                        }

                        _pubutils.notifySuccess(msg);

                    }else{
                        err=data['error_message'];
                        _pubutils.notifyErr(err);
                    }
                }else{
                    err="<?=lang('Failed');?>";
                    _pubutils.notifyErr(err);
                }

            }).always(function(){
                _pubutils.closeNotify(notify);
                dataTable.ajax.reload();
            });
        });
    });
    

    
</script>
