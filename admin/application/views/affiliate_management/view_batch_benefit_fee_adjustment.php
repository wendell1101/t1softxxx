<div class="container-fluid">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title">
                <?php echo lang('Batch Benefit Fee Adjustment'); ?>
            </h4>
        </div>
        <div class="panel-body">
            <form class="form-horizontal" action="<?php echo site_url('/affiliate_management/post_batch_benefit_fee_adjustment');?>"
                method="post" onsubmit="return submitForm();" accept-charset="utf-8" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="col-md-12"><?=lang('hint.batch_benefit_fee_adjustment')?></label>
                </div>
                <div class="form-group">
                    <label class="col-md-3"><?=lang('Year Month')?></label>
                    <div class="col-md-9 form-inline">
                        <div class="">
                            <?php echo form_dropdown('year_month', $year_month_list, null, 'class="form-control input-sm"'); ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3"><?=lang('Upload File')?></label>
                    <div class="col-md-9 form-inline">
                        <div class="">
                            <input type="file" name="batch_benefit_fee_adjustment_csv_file" class="form-control input-sm" required="required" accept=".csv">
                        </div>
                        <span class="help-block" style="color: red;"><?=lang('Note: Upload file format must be CSV')?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3"><?=lang('pay.reason')?></label>
                    <div class="col-md-9">
                        <textarea id="reason" name="reason" class="form-control" rows="5" required="required"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-3 col-md-9">
                        <div class="error alert alert-danger hide">
                            <strong><?=lang('Error'); ?>!</strong> <?=lang('con.d02'); ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-3 col-md-9">
                        <div role="toolbar" class="text-right">
                            <button type='reset' class="btn btn-linkwater"><?=lang('lang.reset')?></button>
                            <button type="submit" class="btn btn_submit btn-portage" onclick="return confirm('<?=lang('confirm.request')?>')"><?=lang('lang.submit')?></button>
                        </div>
                    </div> <!-- EOF .col-md-offset-3.col-md-3 -->
                </div>

            </form>

        </div>
        <div class="panel-footer"></div>
    </div>
</div> <!-- EOF .container-fluid -->

<style type="text/css">
</style>

<script type="text/javascript">
$(function() {

    $('a[data-target]').click(function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var url = $(this).attr('href');
        $(target).load(url);
    });

});
</script>