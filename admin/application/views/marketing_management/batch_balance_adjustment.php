<div class="container-fluid">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title">
                <?php echo lang('Batch Balance Adjustment'); ?>
            </h4>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="heading0">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse0"
                                        aria-expanded="true" aria-controls="collapse0">
                                        <?=lang('pay.mainwalltbal')?>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapse0" class="panel-collapse collapse in" role="tabpanel"
                                aria-labelledby="heading0">
                                <div class="list-group">
                                    <a  data-target="#balance-adjustment-form"
                                        href="/marketing_management/manually_batch_bonus"
                                        title="<?=lang('transaction.transaction.type.' . Transactions::ADD_BONUS)?>"
                                        class="list-group-item"><i class="fa fa-star"></i>
                                        <?=lang('Batch Add Bonus')?>
                                        <span class="loading hide"><i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                                    </a>
                                    <a  data-target="#balance-adjustment-form"
                                        href="/marketing_management/manually_batch_subtract_bonus"
                                        title="<?=lang('transaction.transaction.type.' . Transactions::SUBTRACT_BONUS)?>"
                                        class="list-group-item"><i class="fa fa-minus"></i>
                                        <?=lang('Batch Subtract Bonus')?>
                                        <span class="loading hide"><i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                                    </a>
                                    <a data-target="#balance-adjustment-form"
                                        href="/payment_management/adjust_balance_form/<?=implode('/', array(0, Transactions::MANUAL_ADD_BALANCE))?>"
                                        title="<?=lang('transaction.transaction.type.' . Transactions::MANUAL_ADD_BALANCE)?>"
                                        class="list-group-item"><i class="fa fa-plus"></i>
                                        <?=lang('transaction.transaction.type.' . Transactions::MANUAL_ADD_BALANCE)?>
                                        <span class="loading hide"><i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                                    </a>
                                    <?php if($this->utils->getConfig('enabled_adjust_balance_form_with_amounts')):?>
                                    <a data-target="#balance-adjustment-form"
                                        href="/payment_management/adjust_amounts_balance_form/<?=implode('/', array(0, Transactions::MANUAL_ADD_BALANCE))?>"
                                        title="<?=lang('transaction.transaction.type.' . Transactions::MANUAL_ADD_BALANCE)?>"
                                        class="list-group-item"><i class="fa fa-plus"></i>
                                        <?=lang('Manual Add amounts to Balance')?>
                                        <span class="loading hide"><i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                                    </a>
                                    <?php endif; // EOF if($this->utils->getConfig('enabled_adjust_balance_form_with_amounts'))...?>
                                    <a data-target="#balance-adjustment-form"
                                        href="/payment_management/adjust_balance_form/<?=implode('/', array(0, Transactions::MANUAL_SUBTRACT_BALANCE))?>"
                                        title="<?=lang('transaction.transaction.type.' . Transactions::MANUAL_SUBTRACT_BALANCE)?>"
                                        class="list-group-item"><i class="fa fa-minus"></i>
                                        <?=lang('transaction.transaction.type.' . Transactions::MANUAL_SUBTRACT_BALANCE)?>
                                        <span class="loading hide"><i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                                    </a>
                                    <?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
                                    <a  data-target="#balance-adjustment-form"
                                        href="/marketing_management/manually_batch_add_cashback_bonus"
                                        title="<?=lang('transaction.transaction.type.' . Transactions::AUTO_ADD_CASHBACK_TO_BALANCE)?>"
                                        class="list-group-item"><i class="fa fa-plus"></i>
                                        <?=lang('transaction.transaction.type.30')?>
                                        <span class="loading hide"><i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                                    </a>
                                    <?php endif;?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6" id="balance-adjustment-form"></div>

            </div>

        </div>
        <div class="panel-footer"></div>
    </div>
</div> <!-- EOF .container-fluid -->

<style type="text/css">
.extra-tag {
    background-color: #e4e4e4;
    color: #222 !important;
    border: 1px solid #aaa !important;
    border-radius: 3px;
    margin: 2px;
    padding: 1px;
    line-height: 2em;
    padding-right: 4px;
    padding-left: 4px;
}
</style>

<script type="text/javascript">
$(function() {

    $('a[data-target]').click(function(e) {
        e.preventDefault();
        var theTarget$El = $(e.target);
        var target = $(this).data('target');
        var url = $(this).attr('href');
        theTarget$El.find('.loading').removeClass('hide'); // show loading icon
        $(target).load(url, function( responseText, textStatus, jqXHR){ // completeCB
            theTarget$El.find('.loading').addClass('hide'); // hide loading icon
        });
    });

});
$(document).ready(function() {
    $('#view_batch_balance_adjustment').addClass('active');
});
</script>