<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#viewAdjustmentScoreReport" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="viewAdjustmentScoreReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/report_management/viewAdjustmentScoreReport'); ?>" method="get">
                <div class="row">
                    <!-- Date -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?= lang('score_history.datetime'); ?>:
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="true" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>
                    <!-- username -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <input type="radio" name="search_by" value="1" checked <?=$conditions['search_by'] == '1' ? 'checked="checked"' : '' ?> /> <?=lang('Similar');?>
                            <?=lang('Username'); ?>
                            <input type="radio" name="search_by" value="2" <?=$conditions['search_by'] == '2' ? 'checked="checked"' : ''?> /> <?=lang('Exact'); ?>
                            <?=lang('Username'); ?>
                        </label>
                        <input type="text" name="by_username" id="by_username" value="<?= $conditions['by_username']; ?>" class="form-control input-sm group-reset" />
                    </div>

                   <!-- transactions type -->
                   <div class="form-group col-md-3 col-lg-3">
                       <label for="by_score_type" class="control-label"><?=lang('score_history.type');?> </label>
                       <?=form_dropdown('by_score_type', $transaction_type, $conditions['by_score_type'], 'class="form-control input-sm iovation_report_status group-reset"'); ?>
                   </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm btn-linkwater">
                            <button type="submit" class="btn btn-sm btn-portage"><?=lang("lang.search")?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12" id="toggleView">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="icon-newspaper"></i>
                    <?=lang("score_history.title")?>
                    <?php if ($this->permissions->checkPermissions('execute_adjustment_score_report')): ?>
                    <button type="button" value="" id="add_scord" class="btn btn-primary pull-right btn-xs" style="margin-left:4px;margin-top:0px" onclick="manualAddSubtractScore('1')">
                        <i class="glyphicon glyphicon-plus" data-placement="bottom"></i>
                        <?=lang('score_history.add_scord_title')?>
                    </button>
                    <button type="button" value="" id="subtract_score" class="btn btn-primary pull-right btn-xs" style="margin-left:4px;margin-top:0px" onclick="manualAddSubtractScore('2')">
                        <i class="glyphicon glyphicon-minus" data-placement="bottom"></i>
                        <?=lang('score_history.subtract_score_title')?>
                    </button>
                    <?php endif ?>
                </h4>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-condensed" id="result_table">
                        <thead>
                            <tr>
                                <th style="min-width:110px;"><?=lang("score_history.datetime")?></th>
                                <th><?=lang("Username")?></th>
                                <th><?=lang("score_history.score")?></th>
                                <th><?=lang("score_history.before_score")?></th>
                                <th><?=lang("score_history.after_score")?></th>
                                <th><?=lang("score_history.type")?></th>
                                <th style="min-width:150px;"><?=lang("score_history.note")?></th>
                                <th style="min-width:150px;"><?=lang("score_history.action_log")?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- add subtract score -->
    <div class="col-md-5" id="manual_add_subtract_score" style="display: none;">
        <div class="panel panel-info panel-primary">
            <div class="panel-heading custom-ph">
                <h4 class="panel-title">
                    <i class="icon-pencil"></i> <span id="add-edit-panel-title"></span>
                    <a href="#close" class="btn pull-right panel-button btn-info btn-xs" id="closeDetails" ><span class="glyphicon glyphicon-remove"></span></a>
                </h4>

                <div class="clearfix"></div>
            </div>
            <div class="panel panel-body" id="details_panel_body">
                <form method="post" id="post_player_score" role="form" action="/report_management/adjust_player_score_post">
                    <?=$double_submit_hidden_field?>
                    <input type="hidden" name="manual_add_subtract_score_type" value="" id="manual_add_subtract_score_type">

                    <?php if ($this->utils->getConfig('enabled_batch_adjust_player_score')): ?>
                    <!--multiple all players -->
                    <div class="form-group required">
                        <label for="all_players"><?=lang('Username')?></label>
                        <select name="all_players[]" id="all_players" multiple="multiple" class="form-control input-sm">
                            <?php if (!empty($all_players)): ?>
                                <?php foreach ($all_players as $player): ?>
                                    <option value="<?=$player['playerId']?>"><?=$player['username']?></option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                    </div>
                    <?php else:?>
                    <!-- signel -->
                    <div class="form-group required">
                        <label for="username" class="control-label"><?=lang('Username')?></label>
                        <input name="username" id="username" class="form-control" type="text" value="<?=set_value('username')?>" required="required"/>
                        <span class="control-label hide" id="username_exist_failed"><?=lang('notify.68')?></span>
                    </div>

                    <?php endif ?>

                    <!-- score -->
                    <div class="form-group required">
                        <label><?=lang('score_history.score')?></label>
                        <input type="number" class="form-control" name="score" id="score" step='0.01' min="0.01" required="required">
                    </div>

                    <!-- note -->
                    <div class="form-group required">
                        <label><?=lang('score_history.note')?></label>
                        <textarea name="adjustment_reason" class="form-control" id="adjustment_reason" rows="5" required="required" style="resize:none;"></textarea>
                    </div>

                    <center><button type="submit" class="btn btn_submit btn-portage text-right"><?=lang('lang.submit')?></button></center>
                </form>
            </div>
        </div>
    </div>
    <!-- add score -->
    <div class="panel-footer"></div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
    <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
    </form>
<?php }?>
<script type="text/javascript" src="<?php echo $this->utils->thirdpartyUrl('jquery-validate/jquery.validate.min.js') ?>"></script>
<?php include __DIR__ . "/../includes/jquery_validate_lang.php"?>
<script type="text/javascript">

    function manualAddSubtractScore(type) {
        resetAdjustmentDetails();
        $('#toggleView').removeClass('col-md-12');
        $('#toggleView').addClass('col-md-7');

        $('#manual_add_subtract_score_type').val(type);

        $('#manual_add_subtract_score').css({
            display : "block"
        })

        if (type == '1') {
            $('#add-edit-panel-title').text('<?=lang('score_history.add_scord_title')?>');
        } else if (type == '2') {
            $('#add-edit-panel-title').text('<?=lang('score_history.subtract_score_title')?>');
        }

        // if ($('#toggleView').hasClass('col-md-5')) {
        //     $('table#myTable td#visible').hide();
        //     $('table#myTable th#visible').hide();
        // } else {
        //     $('table#myTable td#visible').show();
        //     $('table#myTable th#visible').show();
        // }
    }

    function closeDetails() {
        resetAdjustmentDetails();
        $('#toggleView').removeClass('col-md-7');
        $('#toggleView').addClass('col-md-12');

        if ($('#toggleView').hasClass('col-md-7')) {
            $('table#myTable td#visible').hide();
            $('table#myTable th#visible').hide();
        } else {
            $('table#myTable td#visible').show();
            $('table#myTable th#visible').show();
        }
        $('#manual_add_subtract_score').css({
            display : "none"
        });
    }

    function resetAdjustmentDetails(){
        $('#score').val('');
        $('#adjustment_reason').val('');
        $('#username').val('');
        $("#all_players").multiselect("clearSelection");
        $("#all_players").multiselect( 'refresh' );
    }

    $(document).ready(function(){

        $('#username').blur(function(){
            $.ajax({
                url: '/api/playerUsernameExist',
                type: "POST",
                dataType: 'json',
                data: {
                    username: function () {
                        return $('input[name="username"]').val();
                    }
                },
                success: function (response) {
                    if (response) {
                        console.log('11');
                        $('#username_exist_failed').addClass('hide');
                        $('.btn_submit').prop('disabled', false);
                        return "true";


                    } else {
                        console.log('22');
                        $('#username_exist_failed').removeClass('hide');
                        $('#username_exist_failed').css('color','red');
                        $('.btn_submit').prop('disabled', true);
                        return "false";

                    }
                }
            });
        });

        $("#closeDetails").on('click', function () {
            closeDetails();
        });

        $('#all_players').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonClass: 'btn btn-sm btn-default',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Player');?>';
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


        var dataTable = $('#result_table').DataTable({
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
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
                <?php if($export_report_permission){ ?>
                ,{
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();
                       var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/adjustmentScoreReport'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                { className: 'text-right', targets: [2,3,4] },
                // { visible: false, targets: [8] },
            ],
            order: [ 0, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/adjustmentScoreReport", data, function(data) {
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

        $('#btnResetFields').click(function() {
            $('.group-reset').val('');
            $('.dateInput').data('daterangepicker').setStartDate(moment().startOf('day').format('Y-MM-DD HH:mm:ss'));
            $('.dateInput').data('daterangepicker').setEndDate(moment().endOf('day').format('Y-MM-DD HH:mm:ss'));
            dateInputAssignToStartAndEnd($('#search_withdrawal_date'));
        });
    });


</script>