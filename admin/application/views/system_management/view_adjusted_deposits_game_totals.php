<?php
    $is_export_excel_on_queue = $this->utils->isEnabledFeature('export_excel_on_queue');

?><div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseUserLogsReport" class="btn btn-info btn-xs <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseUserLogsReport" class="panel-collapse <?= $this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" class="form-horizontal" action="<?= site_url('/system_management/view_adjusted_deposits_game_totals'); ?>" method="get">
                <div class="row">
                    <div class="col-md-4">
						<label class="control-label width-100-percentage">
                            <span><?=lang('lang.date');?></span>
                            <span class="pull-right date_mode_radio_wrapper">
                                <input type="radio" name="date_mode" value="<?=Player_basic_amount_list::DATE_MODE_CREATED?>" <?php if (isset($conditions['date_mode']) && $conditions['date_mode'] == Player_basic_amount_list::DATE_MODE_CREATED) {echo 'checked';}?> > <?=lang('Added');?>
                            </span>
                            <span class="pull-right date_mode_radio_wrapper">
                                <input type="radio" name="date_mode" value="<?=Player_basic_amount_list::DATE_MODE_UPDATED?>" <?php if (isset($conditions['date_mode']) && $conditions['date_mode'] == Player_basic_amount_list::DATE_MODE_UPDATED) {echo 'checked';}?> > <?=lang('Updated');?>
                            </span>
                        </label>
						<div class="input-group">
						<input type="text" class="form-control input-sm dateInput" data-start="#start_date" data-end="#end_date" data-time="true"/>
						<input type="hidden" name="start_date" id="start_date" value="<?=(isset($conditions['start_date']) ? $conditions['start_date'] : '')?>">
						<input type="hidden" name="end_date" id="end_date" value="<?=(isset($conditions['end_date']) ? $conditions['end_date'] : '')?>">
                                <span class="input-group-addon input-sm">
                                    <input type="checkbox" name="checkbox_is_enabled_date"
                                    <?=empty($conditions['is_enabled_date']) ? '' : 'checked="checked"'; ?>
                                    />
                                    <input type="hidden" name="is_enabled_date" value="<?=$conditions['is_enabled_date']?>">
                                </span>
						</div>
					</div>
                    <div class="col-md-3">
                        <label class="control-label"><?=lang('report.log02');?></label>
                        <input type="text" name="username" class="form-control input-sm" value="<?= ($this->input->get('username') ? : '')?>"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label class="control-label"><?=lang('Bet Amount');?> &gt;=</label>
                        <input type="text" name="bet_amount_greater_equal" class="form-control input-sm" value="<?= ($this->input->get('bet_amount_greater_equal') ? : '')?>"/>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label"><?=lang('Bet Amount');?> &lt;=</label>
                        <input type="text" name="bet_amount_less_equal" class="form-control input-sm" value="<?= ($this->input->get('bet_amount_less_equal') ? : '')?>"/>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label"><?=lang('Deposit Amount');?> &gt;= </label>
                        <input type="text" name="deposit_amount_greater_equal" class="form-control input-sm" value="<?= ($this->input->get('deposit_amount_greater_equal') ? : '')?>"/>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label"><?=lang('Deposit Amount');?> &lt;=</label>
                        <input type="text" name="deposit_amount_less_equal" class="form-control input-sm" value="<?= ($this->input->get('deposit_amount_less_equal') ? : '')?>"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1 col-md-offset-11" style="padding-top: 20px;">
                        <button type="submit" class="btn btn-primary pull-right" id="btn-submit"><i class="fa fa-search"></i> <?=lang("lang.search")?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ////// -->

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-drawer"></i> <?=lang('Adjusted Deposits / Game Totals');?>
            <?php if($this->permissions->checkPermissions('modified_adjusted_deposits_game_totals')) : ?>
                <a href="javascript:void(0);" class="btn btn-primary pull-right panel-button btn-xs btn_add_new " style="margin-right: 0; margin-top: -1px;" >
                    <i class="glyphicon glyphicon-plus" data-placement="top"  data-toggle='tooltip'></i>&nbsp;<?=lang('Add');?>
                </a>
            <?php endif; ?>
        </h4>
    </div>
    <div class="panel-body" style="overflow-x: scroll;">

        <table class="table table-hover table-condensed table-bordered" id="myTable">
            <thead>
                <tr>
                    <?php if($this->permissions->checkPermissions('modified_adjusted_deposits_game_totals')) : ?>
                        <th><?=lang('sys.action');?></th>
                    <?php endif; ?>

                    <th><?=lang('sys.createdon');?></th>
                    <th><?=lang('sys.updatedon');?></th>
                    <th><?=lang('Player Username');?></th>
                    <th><?=lang('Bet Amount'); ?></th>
                    <th><?=lang('Deposit Amount');?></th>

                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade in" id="mainModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?=site_url('system_management/sync_adjusted_deposits_game_totals')?>" method="post" role="form" id="form_adjusted_deposits_game_totals">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="mainModalLabel"></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" class="clear-fields" name="data_id" id="data_id" value="">
                            <div class="form-group hide">
                                <label for="player_username_text"><?=lang("Player Username")?> </label>
                                <input type="text" class="form-control clear-fields" id="player_username_text" readonly="true">
                                <span class="help-block"></span>
                            </div>
                            <div class="form-group">
                                <label for="player_username"><?=lang("Player Username")?> </label>
                                <input type="hidden" class="clear-fields" name="player_username">
                                <style>
                                    .select2-selection__rendered{color:#008CBA;}
                                </style>
                                <select class="from-username form-control" id="player_username_select" style="width:100%;" ></select> <!-- multiple="multiple" -->
                                <button style="position:relative;bottom:30px;right:2px;" type="button" id="clear-member-selection" class="btn btn-default btn-xs pull-right hide" >
                                    <fa class="glyphicon glyphicon-remove"></fa><?=lang('lang.clear.selections')?>
                                </button>
                                <span class="help-block player-username-help-block" ></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="total_bet_amount" data-toggle='tooltip' title="<?=lang("Replace the old Amount")?>">
                                    <?=lang("Bet Amount")?>
                                    &nbsp;
                                    <i class="fa fa-info-circle"></i>
                                </label>
                                <input type="number" class="form-control clear-fields" id="total_bet_amount" name="total_bet_amount">
                                <span class="help-block"></span>
                            </div>
                        </div><div class="col-md-6">
                            <div class="form-group">
                                <label for="total_deposit_amount" data-toggle='tooltip' title="<?=lang("Replace the old Amount")?>">
                                    <?=lang("Deposit Amount")?>
                                    &nbsp;
                                    <i class="fa fa-info-circle"></i>
                                </label>
                                <input type="number" class="form-control clear-fields" id="total_deposit_amount" name="total_deposit_amount">
                                <span class="help-block"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="sync-result-block">
                                    <span class="help-block"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row csv_file_batch_sync_row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="csv_file_batch_sync"><?=lang("The CSV File")?> </label>
                                <input type="file" class="form-control clear-fields" id="csv_file_batch_sync" name="csv_file_batch_sync">
                                <span class="help-block"></span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <span class="help-msg"><?=sprintf(lang('Only Support CSV File, <a href="%s">click here</a> to download sample'), '/sample_base_amounts.csv') // the sample CSV file. ?></span>
                        </div>
                    </div> <!-- EOF .csv_file_batch_sync_row -->
                </div> <!-- EOF .modal-body -->
                <div class="modal-footer" >
                    <div class="modal_footer_wrapper">
                        <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                        <button type="button" class="btn btn-scooter btn_sync_submit" > <?=lang('lang.submit')?></button>
                    </div>
                </div> <!-- EOF .modal-footer -->
            </form>
        </div>
    </div>
</div>

<div class="modal fade in" id="syncResultModal" tabindex="-1" role="dialog" aria-labelledby="syncResultModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?=site_url('system_management/delete_adjusted_deposits_game_totals')?>" method="post" role="form" id="form_delete_confirm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="syncResultModalLabel"></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <span class="help-msg"><?=lang("Are you sure Delete the data ?")?></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" >
                    <div class="modal_footer_wrapper">
                        <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('Close');?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade in" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?=site_url('system_management/delete_adjusted_deposits_game_totals')?>" method="post" role="form" id="form_delete_confirm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="deleteConfirmModalLabel"></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <span class="help-msg"><?=lang("Are you sure Delete the data ?")?></span>
                            <input type="hidden" class="clear-fields" name="data_id"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" >
                    <div class="modal_footer_wrapper">
                    <button type="button" class="btn btn-danger btn_do_delete" > <?=lang('Delete')?></button>
                        <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('Cancel');?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade in" id="bulkResultModal" tabindex="-1" role="dialog" aria-labelledby="bulkResultModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="bulkResultModalLabel"></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <span class="processed_rows_field"><?=lang("Processed rows")?> : </span>
                            <span class="processed_rows_amount"><?=lang("N/A")?></span>
                            <span class="total_rows_field"><?=lang("Total Rows")?> : </span>
                            <span class="total_rows_amount"><?=lang("N/A")?></span>
                            <span class="status_field"><?=lang("Status")?> : </span>
                            <span class="status_value"><?=lang("N/A")?></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="progress">
                                <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                                    <span class="sr-only">100% Complete</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <span class="total_players_field"><?=lang("Total Players")?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="total_players_amount">0</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <span class="success_number_field"><?=lang("Success Number")?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="success_number_amount">0</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <span class="failure_number_field"><?=lang("Failure Number")?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="failure_number_amount">0</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <span class="status_link_tip"><?=lang("system.adjusted_deposits_game_totals.status_link")?>:</span>
                            <a class="status_link_href" href=""><?=lang("N/A")?></a>
                        </div>
                    </div>
                </div> <!-- EOF .modal-body -->
                <div class="modal-footer" >
                    <div class="modal_footer_wrapper">
                        <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('Close');?></button>
                    </div>
                </div> <!-- EOF .modal-footer -->
            </form>
        </div>
    </div>
</div>

<?php if($is_export_excel_on_queue) : ?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php endif; // EOF if($is_export_excel_on_queue) ?>

<style>
    .width-100-percentage {
        width:100%
    }
    .date_mode_radio_wrapper {
        margin-left: 4px;
    }

    .help-block,.help-msg {
        color: #F04124;
    }
    #bulkResultModal .progress {
        height: 28px;
    }
    #bulkResultModal .progress .progress-bar span {
        position: unset;
    }
</style>
<script type="text/javascript">
    $(document).ready(function () {
        // apply tooltip
        $('[data-toggle="tooltip"]').tooltip();

        $('#view_adjusted_deposits_game_totals').addClass('active');

    });

    var adjusted_deposits_game_totals = Adjusted_deposits_game_totals.initialize({
        // options
        isEnabledFeature:{
            export_excel_on_queue: <?= $is_export_excel_on_queue? 'true': 'false' ?>
        },
        permissions:{
            export_adjusted_deposits_game_totals: <?=$this->permissions->checkPermissions('export_adjusted_deposits_game_totals')? 'true': 'false' ?>
        },
        use_new_sbe_color:<?= empty($this->utils->getConfig('use_new_sbe_color'))? 0: 1 ?>,
        sync_result_code_invalid_bet_amount : <?=Player_basic_amount_list::SYNC_RESULT_CODE_INVALID_BET_AMOUNT?>,
        sync_result_code_invalid_deposit_amount : <?=Player_basic_amount_list::SYNC_RESULT_CODE_INVALID_DEPOSIT_AMOUNT?>,
        sync_result_code_username_not_exist : <?=Player_basic_amount_list::SYNC_RESULT_CODE_USERNAME_NOT_EXIST?>,
        sync_result_code_unknown_error : <?=Player_basic_amount_list::SYNC_RESULT_CODE_UNKNOWN_ERROR?>

    }, {
        // langs
        modal_title: "<?=lang('Adjusted Deposits / Game Totals')?>"
        , csv_export: "<?=lang('CSV Export')?>"
        , na: "<?=lang('N/A')?>"
        , processing: "<?=lang('Processing')?>"
        , is_required: "<?=lang('lang.is.required')?>"
        , invalid_amount: "<?=lang('system.adjusted_deposits_game_totals.invalid_amount')?>"
    });

    $(document).ready(function(){
        adjusted_deposits_game_totals.onReady();


    });
</script>