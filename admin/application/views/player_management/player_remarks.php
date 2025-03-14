<?=$this->load->view("resources/third_party/bootstrap-colorpicker")?>
<!-- search column -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseViewPlayerRemarks" class="btn btn-xs btn-info"></a>
            </span>
        </h4>
    </div>
    <div id="collapseViewPlayerRemarks" class="panel-collapse collapse in">
        <div class="panel-body">
            <form class="form-horizontal" id="search-form">
                <div class = "row">
                    <div class="col-md-4">
                        <label class="control-label" for="search_date"><?=lang('report.sum02');?></label>
                        <input id="search_date" class="form-control input-sm dateInput" data-start="#date_from" data-end="#date_to" data-time="true"/>
                        <input type="hidden" id="date_from" name="date_from" value="<?php if (isset($date_from)){ echo $date_from;} ?>" />
                        <input type="hidden" id="date_to" name="date_to"  value="<?php if (isset($date_to)){ echo $date_to;} ?>"/>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label" for="playerUsername"><?=lang('Player Name');?> </label>
                        <input type="text" name="playerUsername" id="playerUsername" class="form-control input-sm" value="<?php if (isset($playerUsername)){ echo $playerUsername;} ?>" />
                    </div>
                    <div class="col-md-3 hidden">
                        <label class="control-label" for="tag_remark"><?=lang('Category');?> </label>
                        <select name="tag_remark" id="tag_remark" class="form-control input-sm">
                            <option value=""><?=lang('lang.all');?></option>                        
                            <?php foreach ($all_remark as $remark_detail) {?>
                            <option value="<?=$remark_detail['remarkId']?>"><?=$remark_detail['tagRemarks']?></option>
						<?php }?>
						</select>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label" for="operator"><?=lang('Operator');?> </label>
                        <select name="operator" id="operator" class="form-control input-sm">
                            <option value=""><?=lang('lang.all');?></option>                        
                            <?php foreach ($operator as $user) {?>
                            <option value="<?=$user['userId']?>"><?=$user['username']?></option>
						<?php }?>
						</select>
                    </div>
                </div>
            </form>
        </div>
        <div class="panel-body text-right">
            <input type="button" id="btnResetFields" value="<?php echo lang('Reset'); ?>" class="btn btn-sm btn-linkwater">
            <button type="button" class="btn btn-sm btn-portage" id="search-message"><?=lang('lang.search');?></button>
        </div>
    </div>
</div>
<!-- player_remarks_table -->
<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt">
            <i class="icon-price-tags"></i> <?=lang('Player Remarks');?>
        </h4>
    </div>
    <form>
        <div class="panel-body" id="chat_panel_body">
            <div class="table-responsive">
                <table class="table table-hover" style="width:100%;" id="myTable">
                    <thead>
                    <tr>
                        <!-- <th style="padding: 8px" class="th_chk_multiple"><input type="checkbox" id="checkWhite" onclick="checkAll(this.id)"/></th> -->
                        <th><?=lang('PlayerName');?></th>
                        <th><?=lang('Date');?></th>
                        <th><?=lang('Message');?></th>
                        <th><?=lang('Operator');?></th>
                        <th><?=lang('Category');?></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </form>
    <div class="panel-body">
    </div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    $(document).ready(function(){
        var dataTable = $('#myTable').DataTable({
            "responsive":
            {
                details: {
                    type: 'column'
                }
            },
            autoWidth: false,
            searching: false,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                  extend: 'colvis',
                  postfixButtons: [ 'colvisRestore' ]
                },
                {   
                        text: "<?php echo lang('CSV Export'); ?>",
                        className:'btn btn-sm btn-portage',
                        action: function ( e, dt, node, config ) {
                            var form_params=$('#search-form').serializeArray();
                            var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                                'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/export_player_remarks_report/null/true'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();

                        }
                    }
            ],
            columnDefs: [
                { className: 'text-right', targets: [] },
                { className: 'text-center', targets: [0,1,2,3,4] },
            ],
            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
				data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/player_remarks_list_report", data, function(data) {
                    callback(data);
                },'json');
            }
        });

        $('#search-message').click( function() {
             //---Set Value for hidden data for search deletion upon search click
            $('input[name="date_from"]').val($('input[name="date_from"]').val());
            $('input[name="date_to"]').val($('input[name="date_to"]').val());
            $('input[name="playerUsername"]').val($('input[name="playerUsername"]').val());
            $('input[name="tag_remark"]').val($('input[name="tag_remark"]').val());
            dataTable.ajax.reload();
        });

            var dateFrom = $("#date_from").val();
            var dateTo = $("#date_to").val();

        $('#btnResetFields').click(function(){

            $("#date_from").val("");
            $("#date_to").val("");
            $("#playerUsername").val("");
            $("#playerUsername").val("");
            $("#tag_remark").val("");
            $("#search_date").val("");
            $("#operator").val("");

            console.log('reset',[dateFrom, dateTo]);

            var search_date = $('#search_date');
            search_date.data('daterangepicker').setStartDate(dateFrom);
            search_date.data('daterangepicker').setEndDate(dateTo);
            $(search_date.data('start')).val(dateFrom);
            $(search_date.data('end')).val(dateTo);

        });
    });
</script>