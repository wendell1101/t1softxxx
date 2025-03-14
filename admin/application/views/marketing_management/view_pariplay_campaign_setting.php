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

    .loadingoverlay {
        position: fixed;
        top: 0;
        z-index: 100;
        width: 100%;
        height: 100%;
        display: none;
        background: rgba(0,0,0,0.6);
    }

    .cv-spinner {
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px #ddd solid;
        border-top: 4px #2e93e6 solid;
        border-radius: 50%;
        animation: sp-anime 0.8s infinite linear;
    }

    @keyframes sp-anime {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(359deg);
        }
    }

    .required label {
        font-weight: bold;
    }
    .required label:after {
        color: #e32;
        content: ' *';
        display:inline;
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading custom-ph">
                <h3 class="panel-title custom-pt"><i class="fa fa-gamepad"></i> <?=lang('Pariplay Campaign Bonus List')?></h3>
            </div>

            <div class="panel-body">
                <table class="table table-striped table-bordered table-hover dataTable no-footer dtr-column collapsed" id="table" style="width: 100%;" role="grid" aria-describedby="my_table_info">
                    <thead>
                        <tr role="row">
                            <th><?=lang('Id');?></th>
                            <th><?=lang('Trigger');?></th>
                            <th><?=lang('sys.name');?></th>
                            <th><?=lang('Created at');?></th>
                            <th><?=lang('Activate on');?></th>
                            <th><?=lang('Expired on');?></th>
                            <th><?=lang('Minimum amount');?></th>
                            <th><?=lang('Max amount');?></th>
                            <th><?=lang('Percentage');?></th>
                            <th><?=lang('Turnover');?></th>
                            <th><?=lang('Expiry days after awarding');?></th>
                            <!-- <th><?=lang('Action');?></th> -->
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
            <div id="modalloadingoverlay" class="loadingoverlay"> 
                <div class="cv-spinner">
                    <span class="spinner"></span>
                </div>
            </div>
            <div class="modal-header">
                <h4 class="modal-title text-xs-center"><?=lang('Create Pariplay Campaign Bonus')?> </h4>
            </div>
            <div class="modal-body">
                <form id="main-form" role="form" method="POST" action="<?=BASEURL . 'pariplay_bonus_service_api/create'?>">
                    <input type="hidden" name="_token" value="">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group required">
                                <label class="control-label"><?php echo lang("Activate on & Expired on") ?></label>
                                <input id="campaign_date" class="form-control input-sm dateInput" data-time="true" data-start="#from" data-end="#to" data-future="true" data-enabledmindate="true" data-disableranges="true">
                                <input type="hidden" id="from" name="from" value="<?=$conditions['from'];?>" />
                                <input type="hidden" id="to" name="to" value="<?=$conditions['to'];?>" />
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group required">
                                <label class="control-label"><?=lang('Turnover')?></label>
                                <div>
                                    <input type="number" class="form-control input-sm" name="bonus_turnover" value="" min="0" oninput="format(this, 7)"required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group required">
                                <label class="control-label"><?=lang('sys.name')?></label>
                                <div>
                                    <input type="text" class="form-control input-sm" name="bonus_campaign_name" value="" maxlength="60" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group required">
                                <label class="control-label"><?=lang('Trigger')?></label>
                                <div>
                                    <input type="text" class="form-control input-sm" name="bonus_trigger" value="" maxlength="60" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group required">
                                <label class="control-label"><?=lang('Minimum amount')?></label>
                                <div>
                                    <input type="number" class="form-control input-sm" name="bonus_min_amount" value="" min="0" oninput="format(this, 7)" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group required">
                                <label class="control-label"><?=lang('Max amount')?></label>
                                <div>
                                    <input type="number" class="form-control input-sm" name="bonus_max_amount" value="" min="0" oninput="format(this, 7)"required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?=lang('Percentage')?></label>
                                <div>
                                    <input type="number" class="form-control input-sm" name="bonus_percentage" value="" min="0"  oninput="format(this, 2)">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?=lang('Expiry days after awarding')?></label>
                                <div>
                                    <input type="number" class="form-control input-sm" name="bonus_edaa" value="" min="0"  oninput="format(this, 1)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <button type="submit" class="btn btn-info btn-block"><?=lang('Create')?></button>
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
    $(document).ready(function(){
        reset();
        var baseUrl = '<?=base_url(); ?>';
        var text_blocked = '<span class="help-block" style="color:#ff6666;">Blocked</span>',
            text_allowed = '<span class="help-block" style="color:#66cc66;">Allowed</span>';
        var table = $('#table').DataTable({
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l> <'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            columnDefs: [
                // {
                //     "targets" : 0,
                //     "visible" : false,
                //     "searchable": false,
                // }
            ],
            createdRow: function( row, data, dataIndex ) {
                $(row).addClass( 'tr_cursor' );
            },
            buttons: [
                {
                    text: "<i class='glyphicon glyphicon-plus-sign'></i> <?php echo lang('Add bonus campaign'); ?>",
                    className: "add-domain btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-primary'?>",
                    action: function ( e, dt, node, config ) {
                        $('#add-modal').modal({
                            show: 'true'
                        }); 
                    }
                }
            ],
            ajax : {
                url     : baseUrl + 'pariplay_bonus_service_api/list',
                type    : 'GET',
                async   : true
            },

            order : [[ 4, "desc" ]],
            columns : [
                { data : 'bonus_id' },
                { data : 'bonus_trigger' },
                { data : 'bonus_campaign_name' },
                { data : 'bonus_created_at' },
                { data : 'bonus_activate_on'},
                { data : 'bonus_expired_on' },
                { data : 'bonus_min_amount' },
                { data : 'bonus_max_amount' },
                { data : 'bonus_percentage' },
                { data : 'bonus_turnover' },
                { data : 'bonus_edaa' },
                // {
                //     data : 'bonus_id',
                //     render : function(data) {
                //         return '<button class="btn btn-sm btn-primary edit-domain"><?=lang('lang.edit')?></button>';
                //     }
                // },
            ]
        });

        $('#add-modal').on('hidden.bs.modal', function () {
            reset();
        });

        $("#myModal").on('shown.bs.modal', function () {
            reset();
        });

        function reset(){
            $('#add-modal form')[0].reset();
            var date_from = "<?= $this->utils->adjustDateTimeStr($this->utils->getDatetimeNow(), '+30 minutes') ?>";
            var date_to = "<?= date("Y-m-d") . ' 23:59:59'; ?>";
            $("#campaign_date").daterangepicker({
                parentEl: "#add-modal  .modal",
                timePicker: true,
                timePickerSeconds: true,
                timePicker24Hour: true,
                startDate: date_from,
                endDate: date_to,
                autoUpdateInput: true,
                minDate: date_from,
                locale: {
                      format: 'YYYY-MM-DD HH:mm:ss'
                }
            },function(start, end, label) {
                $('#from').val(start.format('YYYY-MM-DD HH:mm:ss'));
                $('#to').val(end.format('YYYY-MM-DD HH:mm:ss'));
            });
            $('#from').val(date_from);
            $('#to').val(date_to);
        }
    });

    function format(input, requireLenght){
        if(input.value < 0) input.value=Math.abs(input.value);
        if(input.value.length > requireLenght) input.value = input.value.slice(0, requireLenght);
    }

    $(document).on("submit", "#main-form", function(e){
        e.preventDefault();
        var from = $("#from").val();
        var to = $("#to").val();
        var edit = $(this).data("edit");

        var minutesToAdd = 15;
        var currentDate = new Date();
        var adjustedDate = new Date(currentDate.getTime() + minutesToAdd * 60000);
        var fromDate = new Date(from);
        var toDate = new Date(to);
        if(fromDate <= adjustedDate && !edit) {
            alert("Start date should be ahead of 15 mins.");
            return false;
        }
        var hours = parseInt(Math.abs(toDate - fromDate) / (1000 * 60 * 60) % 24);
        var days = parseInt((toDate - fromDate) / (1000 * 60 * 60 * 24));
        if(days == 0 ){
            if(hours < 1){
                alert("Date time range must atleast 1 hour.");
                return false;
            }
        }

        var url = $(this).attr('action');
        var data = {
            bonus_campaign_name:$("input[name='bonus_campaign_name']").val(),
            bonus_trigger:$("input[name='bonus_trigger']").val(),
            bonus_min_amount:$("input[name='bonus_min_amount']").val(),
            bonus_max_amount:$("input[name='bonus_max_amount']").val(),
            bonus_percentage:$("input[name='bonus_percentage']").val(),
            bonus_turnover:$("input[name='bonus_turnover']").val(),
            bonus_edaa:$("input[name='bonus_edaa']").val(),
            bonus_activate_on:from,
            bonus_expired_on:to
        };
        $("#modalloadingoverlay").fadeIn();
        $.ajax({
            type: "POST",
            url: url,
            dataType: 'json',
            data: JSON.stringify(data),
            success: function(data){
                console.log(data);
                if(data.success == true){
                    alert("<?php echo lang('Update success!'); ?>");
                    location.reload();
                } else {
                    var errorMsg = "<?php echo lang('Try again!'); ?>";
                    if(typeof(data.result.error.message) != "undefined" && data.result.error.message !== null) {
                        errorMsg = data.result.error.message;
                    }
                    alert(errorMsg);
                    $("#modalloadingoverlay").fadeOut();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                alert("Failed. Something went wrong.");
                location.reload();
            }
        });
    });
</script>