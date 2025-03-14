<?=$this->load->view("resources/third_party/bootstrap-tagsinput")?>
<div >
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title"><span style="font-weight: bold;"><?=$platform_name?></span></h4>
    </div>
    <div class="panel-body">

        
            <form class="form-horizontal" action="/payment_management/adjust_points_balance_post/<?=implode('/', array($transaction_type, $player_id))?>" method="post" onsubmit="return submitForm();">

            <?=$double_submit_hidden_field?>
            <div class="form-group">
                <label class="col-md-3"><?=lang('pay.transactionType')?></label>
                <div class="col-md-9">
                    <span class="bg-danger"><?=lang('transaction.point_transaction.type.' . $transaction_type)?></span>
                </div>
            </div>
            <?php if ($transaction_type == Transactions::AUTO_ADD_CASHBACK_TO_BALANCE): ?>
            <div class="form-group">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <label>
                        <input type="checkbox" name="generate_withdrawal_condition" value="1" checked>
                        <span class=""><?=lang('Generate withdrawal condition')?></span>
                    </label>
                </div>
            </div>
            <?php endif ?>

            <div class="form-group">
                <label class="col-md-3"><?=lang('pay.amt')?></label>
                <div class="col-md-9">
                    <input type="number" class="form-control" name="amount" step='any' min="0.01" required="required">
                </div>
            </div>

            <div class="form-group">
                <label class="col-md-3"><?=lang('pay.reason')?></label>
                <div class="col-md-9">
                    <textarea name="reason" class="form-control" rows="5" required="required"></textarea>
                    <!--<div class="checkbox" id="show_in_front_end">
                        <label>
                            <input type="checkbox" value="1" name="show_in_front_end"> <?=lang('pay.showtoplayr')?>
                        </label>
                    </div>-->
                </div>
            </div>
                

            <div class="form-group">
                <div class="col-md-offset-3 col-md-9">
                    <div role="toolbar" class="text-right">
                        <button type='reset' class="btn <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-default' ?>"><?=lang('lang.reset')?></button>
                        <button type="submit" class="btn btn_submit <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary' ?>"><?=lang('lang.submit')?></button>
                    </div>
                </div>
            </div>

        </form>
    </div> <!-- EOF .panel-body -->
    <div class="panel-footer hidden"></div>
</div>


</div>
<script type="text/javascript">

$('.dateInput').each( function() {
    initDateInput($(this));
});

function submitForm(){
    if(confirm('<?=lang('balanceadjustment.confirm.adjustbalance')?>')){
        $('.btn_submit').prop('disabled',true);
        return true;

    }
    return false;
}

$(document).ready(function(){
    $(document).find('#tag-options').on('change', function(){
        var option = $(this).find('option:selected');
        //alert(JSON.stringify(option));
        if ( option.val() ){
            $(document).find('#manual_subtract_balance_tag_id').tagsinput('add', {
                                                        id: option.val()
                                                        , text: option.text()
                                                        , 'extra-class':'extra-tag'
                                                    });
        }
        $(this).val('');
    });

    $("textarea[name='reason']").on('keyup', function(e) {
        var reason = this.value;
        if (reason.length > 120) {
            $("textarea[name='reason']").val(reason.substring(0, 120));
        }
    })
});
</script>
