
<div class="container-fluid">

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title"><span style="font-weight: bold;"><?=lang('pay.mainwallt');?></span></h4>
        </div>
        <div class="panel-body">
                <form class="form-horizontal" action="<?php echo site_url('/marketing_management/post_manually_batch_subtract_bonus');?>"
                    method="post" onsubmit="return submitForm();" accept-charset="utf-8" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="col-md-12"><?=lang('hint.batch_subtract_bonus')?></label>
                </div>
                <div class="form-group">
                    <label class="col-md-3"><?=lang('pay.transactionType')?></label>
                    <div class="col-md-9">
                        <span class="bg-danger"><?=lang('Batch Subtract Bonus');?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3"><?=lang('Upload File')?></label>
                    <div class="col-md-9 form-inline">
                        <div >
                            <input type="file" name="batch_subtract_bonus_csv_file" class="form-control input-sm" required="required" accept=".csv"/>
                        </div>
                        <span class="help-block" style="color: red;"><?=lang('cms.notes')?> : <?= $csv_note ?></span>
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
                            <button type='reset' class="btn <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-default' ?>"><?=lang('lang.reset')?></button>
                            <button type="submit" class="btn btn_submit <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary' ?>" onclick="return confirm('<?=lang('confirm.request')?>')"><?=lang('lang.submit')?></button>
                        </div>
                    </div> <!-- EOF .col-md-offset-3.col-md-9 -->
                </div>
            </form>
        </div>
        <div class="panel-footer hidden"></div>
    </div>
</div> <!-- EOF .container-fluid -->
<script type="text/javascript">

var reasonFld = $('#reason');
var errorMsg = $('.error');

function submitForm(){
    if(reasonFld.val() == '') {
        errorMsg.removeClass('hide');
        return false;
    }
    errorMsg.addClass('hide');
    $('.btn_submit').prop('disabled',true);
    return true;
}

$(document).ready(function(){
    $('#view_batch_balance_adjustment').addClass('active');
});

</script>
