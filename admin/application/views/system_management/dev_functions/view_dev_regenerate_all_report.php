<form action="<?=site_url('system_management/regenerate_all_report'); ?>" method="POST">
    <div class="panel panel-primary panel_main">
        <div class="panel-heading">
            <h4 class="panel-title">
                <?=lang('Regenerate All Report') ?>
            </h4>
        </div>
        <div id="regenerate_all_report" class="panel-collapse collapse in ">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('Date');?>
                    </div>
                    <div class="col-md-6">
                        <input class="form-control input-sm dateInput" data-start="#regenerate_all_report_by_date_from" data-end="#regenerate_all_report_by_date_to" data-time="false"/>
                        <input type="hidden" id="regenerate_all_report_by_date_from" name="by_date_from" value="<?=date('Y-m-d', strtotime('-1 day'));?>" />
                        <input type="hidden" id="regenerate_all_report_by_date_to" name="by_date_to"  value="<?=date('Y-m-d', strtotime('-1 day'))?>"/>
                        <?php if(!empty($lock_rebuild_reports_range)):?>
                            <strong>
                                <span class="text-info small" id="lock-reports-info">
                                    <?=sprintf(lang("Regenarate All Report has lock - should not be equal or older  %s  "),$lock_rebuild_reports_range['cutoff_day']); ?>
                                </span>
                            </strong>
                        <?php endif;?>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
            </div>
        </div>
    </div>
</form>
