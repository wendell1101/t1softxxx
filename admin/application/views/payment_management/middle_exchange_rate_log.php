

<!-- somethingWrong Modal Start -->
<div class="modal fade" id="somethingWrongModal" tabindex="-1" role="dialog" aria-labelledby="somethingWrongModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="somethingWrongModalLabel"></h4>
            </div>
            <div class="modal-body somethingWrongModalBody">
                <?=lang('con.pym01')?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=lang('Confirm')?></button>
            </div>
        </div>
    </div>
</div>
<!-- somethingWrong Modal End -->




<div class="panel panel-primary ">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang('Multi-currencies Middle Conversion Exchange Rate Setting')?><span class="pull-right">
                <a data-toggle="collapse" href="#collapseRateSetting" class="btn btn-xs btn-primary" aria-expanded="false"></a>
            </span>
        </h4>
    </div>
    <div class="panel-body" id="collapseRateSetting">

        <div class="container-fluid">
            <form class="form-horizontal" action="/payment_management/update_middle_exchange_rate" method="post" role="form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="middle_exchange_rate_log_rate" class="control-label"><?=lang('Middle Conversion Exchange Rate');?>: </label>
                            <input type="number" step="0.1" min="0" max="100" name="rate" class="form-control input-sm" id="middle_exchange_rate_log_rate" placeholder="" value="<?=set_value('rate', $rate)?>" required>
                            <?php echo form_error('middle_exchange_rate_log_rate', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                        </div> <!-- EOF .form-group -->
                    </div> <!-- EOF .col-md-3 -->

                    <div class="col-md-9">
                        <br/><br/>
                        <span class="" style="color:#ff6666;"><?=lang('Middle Conversion Exchange Rate Note');?> </span>
                    </div> <!-- EOF .col-md-9 -->
                </div> <!-- EOF .row -->

                <div class="row">
                    <div class="col-md-3 col-md-push-9">
                        <input type="submit" value="<?=lang('merl.save');?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info' ?>"/>
                    </div>
                </div> <!-- EOF .row -->
            </form>
        </div> <!-- EOF .container -->
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang('Multi-currencies Middle Conversion Exchange Rate Log')?>
        </h4>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body" >
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped table-hover" id="middle_exchange_rate_log" >
                        <thead>
                            <tr>
                                <th><?= lang('merl.updated_at'); ?></th>
                                <th><?= lang('Middle Exchange Rate'); ?></th>
                                <th><?= lang('merl.updated_by'); ?></th>
                                <th><?= lang('lang.status'); ?></th>
                            </tr>
                        </thead>
                    </table> <!-- EOF #middle_exchange_rate_log -->

                </div>
            </div>
        </div>

    </div>
</div>

<style type="text/css">
/** // for $.button('loading'); */
/* @keyframes spinner-border {
    to { transform: rotate(360deg); }
}
.spinner-border{
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: .25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    -webkit-animation: spinner-border .75s linear infinite;
    animation: spinner-border .75s linear infinite;
}
.spinner-border-sm{
    height: 1rem;
    width: 1rem;
    border-width: .2em;
} */



</style>
<script type="text/javascript">

$(document).ready(function() {
    // for sidenbar
    $('#middle_conversion_exchange_rate_log').addClass('active');

    var middleExchangeRateLog = MiddleExchangeRateLog.initialize({
        'defaultItemsPerPage': <?=$this->utils->getDefaultItemsPerPage()?>,
        'base_url':"<?=base_url()?>",
        langs: {
            // addNewWithdrawalDefinition: '<?=lang('cms.addNewWithdrawalDefinition');?>',
            // viewWithdrawalDefinition: '<?=lang('cms.viewWithdrawalDefinition');?>',
            // whatCannotBeEmpty: '<?=lang('%s cannot be empty');?>',
            // invalidJSON: '<?=lang('Invalid JSON')?>',
            // onlyAllowDigits: '<?=lang('Only allow digits')?>',

            // cmsTitle: '<?=lang('cms.title');?>',
            // dispatchOrder: '<?=lang('Dispatch Order');?>',
            // targetState: '<?=lang('Target State');?>',
            // extrainfo: '<?=lang('sys.ga.extrainfo');?>',
        }
    });

    middleExchangeRateLog.onReady();
});
</script>