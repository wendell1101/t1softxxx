
<!-- previewDefinitionOrder LIST Start -->
<div class="modal fade" id="previewDefinitionOrderModal" tabindex="-1" role="dialog" aria-labelledby="previewDefinitionOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="previewDefinitionOrderModalLabel"><?=lang('Preview Definition Order List')?></h4>
            </div>
            <div class="modal-body previewDefinitionOrderModalBody">
                <div class="h5"><?=lang('The Definition List Sorted By Dispatch Order and for Referenced to Risk Process.')?></div>
                <table class="table table-bordered table-striped table-hover" id="dispatch_withdrawal_definition_order_preview" >
                    <thead>
                        <tr>
                            <th><?= lang('ID'); ?></th>
                            <th><?= lang('cms.title'); ?></th>
                            <th><?= lang('Dispatch Order'); ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.close');?></button>

                <form id="previewDefinitionOrder-search-form">
                    <input type="hidden" name="status" value="1">
                </form>

            </div>
        </div>
    </div>
</div>
<!-- previewDefinitionOrder LIST End -->


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

<!-- Delete WithdrawalDefinition Modal Start -->
<div class="modal fade" id="deleteWithdrawalDefinitionModal" tabindex="-1" role="dialog" aria-labelledby="deletePromoruleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="deleteWithdrawalDefinitionModalLabel"><?=lang('cms.deleteWithdrawalDefinition')?></h4>
            </div>
            <div class="modal-body deleteWithdrawalDefinitionModalBody">
                <?=lang('cms.deleteWithdrawalDefinitionModalMsg')?>
            </div>
            <div class="modal-footer">
                <input type="hidden" class="deleteWithdrawalDefinitionId">
                <button type="button" class="btn btn-primary" id="deleteWithdrawalDefinitionDetail" data-dismiss="modal"><?=lang('Confirm')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Delete WithdrawalDefinition Modal End -->

<form id="withdrawalDefinition_detail_form">
    <div id="withdrawalDefinition_detail" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?=lang('New Withdrawal Definition');?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group required">
                                <label for="name" class="control-label"><?=lang('cms.title');?><span class="text-danger"></span></label>
                                <input type="text" name="name" class="form-control input-sm">
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group required">
                                <label for="name" class="control-label"><?=lang('Dispatch Order');?><span class="text-danger"></span></label>
                                <input type="text" name="dispatch_order" class="form-control input-sm">
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group required">
                                <label title="<?=lang('The Goal State after Reach')?>">
                                    <?=lang('Target State')?> <i class="fa fa-info-circle"></i>
                                </label>
                                <span class="help-block m-b-0">
                                <?=form_dropdown('eligible2dwStatus', $eligible2dwStatus4Options, '')?>
                                </span>
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                            </div>
                        </div>

                        <div class="col-md-6 hide">
                            <div class="form-group ">
                                <label><?=lang('Extra System of Target Status')?></label>
                                <span class="help-block m-b-0">
                                    <?php // @todo OGP-18088 foreach wd-external_system_id ?>
                                    <select name="eligible2external_system_id" class="selectpicker" data-width="auto">
                                        <option value="1"><?=lang('eligible2external_system_id')?></option>
                                        <option value="2"><?=lang('eligible2external_system_id')?></option>
                                    </select>
                                </span>
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group required">
                                <label><?=lang('lang.status')?></label>
                                <span class="help-block m-b-0">
                                    <label class="radio-inline"><input type="radio" name="status" value="1" checked="checked"><?=lang('Active')?></label>
                                    <label class="radio-inline"><input type="radio" name="status" value="0"><?=lang('Inactive')?></label>
                                </span>
                                <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                            </div>
                        </div>


                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label for="extra">
                                <?=lang('sys.ga.extrainfo');?>
                            </label>
                            <div id="extra" style="height:200px;overflow:auto;"></div>
                            <span class="text-danger help-block m-b-0 invalid-prompt hide"></span>
                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id" value="">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <button type="button" class="btn btn-scooter" id="saveWithdrawalDefinitionDetail"><?=lang('lang.save');?></button>
                </div>
            </div>
        </div>
    </div> <!-- EOF #withdrawalDefinition_detail -->
</form> <!-- EOF #withdrawalDefinition_detail_form -->



<div class="panel panel-primary hide">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i><?=lang('Search')?><span class="pull-right">
                <a data-toggle="collapse" href="#collapseWithdrawalRiskProcessSearch" class="btn btn-xs btn-primary" aria-expanded="false"></a>
            </span>
        </h4>
    </div>
    <div class="panel-body" id="collapseWithdrawalRiskProcessSearch">
    TEST BODY
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang('Withdrawal Risk Process List')?>
            <span class="pull-right">
                <a href="javascript:void(0);" class="btn btn-xs btn-primary addWithdrawalRiskDefinition" >
                    <i class="glyphicon glyphicon-plus"></i> <?=lang('Add Definition')?>
                </a>

                <a href="javascript:void(0);" class="btn btn-xs btn-primary previewDefinitionOrder" >
                    <i class="glyphicon glyphicon-sort-by-order"></i> <?=lang('Preview Definition Order')?>
                </a>
            </span>
        </h4>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body" >
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped table-hover" id="dispatch_withdrawal_definition_list" >
                        <thead>
                            <tr>
                                <th><?= lang('ID'); ?></th>
                                <th><?= lang('Dispatch Order'); ?></th>
                                <th><?= lang('cms.title'); ?></th>
                                <th title="<?=lang('The Goal State after Reach')?>" ><?= lang('Target State'); ?>&nbsp;<i class="fa fa-info-circle"></i> </th>
                                <th><?= lang('lang.status'); ?></th>
                                <th><?= lang('Created At'); ?></th>
                                <th><?= lang('Updated At'); ?></th>
                                <th><?= lang('lang.action'); ?></th>
                            </tr>
                        </thead>
                    </table>

                </div>
            </div>
        </div>

    </div>
</div>

<style type="text/css">
/** // for $.button('loading'); */
 @keyframes spinner-border {
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
}


/* for select option disabled */

#withdrawalDefinition_detail .dropdown-menu > .disabled > a {
	color: #fcc;
}

</style>
<script type="text/javascript">

$(document).ready(function() {
    $('#withdrawal_risk_process_list').addClass('active');

    var withdrawalRiskProcess = WithdrawalRiskProcess.initialize({
        'defaultItemsPerPage': <?=$this->utils->getDefaultItemsPerPage()?>,
        'base_url':"<?=base_url()?>",
        langs: {
            addNewWithdrawalDefinition: '<?=lang('cms.addNewWithdrawalDefinition');?>',
            viewWithdrawalDefinition: '<?=lang('cms.viewWithdrawalDefinition');?>',
            whatCannotBeEmpty: '<?=lang('%s cannot be empty');?>',
            invalidJSON: '<?=lang('Invalid JSON')?>',
            onlyAllowDigits: '<?=lang('Only allow digits')?>',

            cmsTitle: '<?=lang('cms.title');?>',
            dispatchOrder: '<?=lang('Dispatch Order');?>',
            targetState: '<?=lang('Target State');?>',
            extrainfo: '<?=lang('sys.ga.extrainfo');?>',
        }
    });

    withdrawalRiskProcess.onReady();
});
</script>