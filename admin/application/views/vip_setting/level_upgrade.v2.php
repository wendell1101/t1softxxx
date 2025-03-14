<link href="<?=site_url().'resources/third_party/bower_components/bootstrap-toggle/css/bootstrap-toggle.min.css'?>" rel="stylesheet">
<link href="<?=site_url().'resources/third_party/bower_components/toastr/toastr.min.css'?>" rel="stylesheet">
<link href="<?=site_url().'resources/third_party/animate/3.6.0/animate.css'?>" rel="stylesheet">

<script type="text/javascript" src="<?=site_url().'resources/third_party/bower_components/bootstrap-toggle/js/bootstrap-toggle.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/third_party/bower_components/toastr/toastr.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/third_party/bower_components/jquery-mask-plugin/src/jquery.mask.js'?>"></script>

<style type="text/css">

.inline {
    display: inline;
}

.text-align-right {
    text-align:right;
}

.cursor-pointer {
        cursor: pointer;
}

#bet_amount_settings-modal .row {
    padding: 4px;
}

.inline.inline-option-wrapper {
    border: groove 1px #333;
    margin: 2px;
    padding: 2px;
    max-width: 33%; /* cloned form .col-md-4 */
    width: auto;
}

.inline.check-toggle {
    margin: 2px;
    padding: 2px;
    max-width: 8.333%;
    width: auto;
}
.help-block.notes {
    padding-left: 12px;
    padding-right: 12px;
}

.row.option-group-wrapper {
    margin-left: 4px;
}

fieldset.option-group-border.text-left {
    margin: 4px;
    padding: 4px;
}

.row.option-group-wrapper {
    margin: 0;
    padding: 0px;
}

fieldset.option-group-border.bet_amount .inline:nth-child(4n):before {
    content: "\a";
    white-space: pre;
}

fieldset.option-group-border.bet_amount .inline {
    height:4em;
}


#bet_amount_settings-modal .row.row-total_bet_amount {
    border-bottom: solid 1px #aaa;
    padding-bottom: 8px;
}
.row.row-total_bet_amount,.row.row-default_bet_amount {
    margin: 8px;
}
.row.row-total_bet_amount .col label, .row.row-default_bet_amount .col label{
    margin-top: 8px;
}

.well.formula-container {
    height:auto;
}

</style>
<!-- Level Upgrade Setting -->
<div id="levelUpModal_v2" class="modal fade" role="dialog">

    <div class="modal-dialog modal-fs">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <div class="modal-title h4">
                    <?= lang('VIP Setting Form'); ?>
                    <span class="h6 text-danger">
                        Ver.2
                    </span>
                </div>
            </div>
            <div class="modal-body custom-height-modal">

                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-12">
                            <fieldset style=""> <!-- padding:20px;margin-bottom: 5px; -->
                                <legend>
                                    <div class="h5">
                                        <strong>
                                            <?= lang('Add Setting') ?>
                                        </strong>
                                    </div>
                                </legend>
                                <form class="" id="settingForm">
                                    <input type="hidden" id="upgradeId" value="">

                                    <div class="row">
                                        <div class="col col-md-2 text-align-right">
                                            <label for="settingName">
                                                <?= lang('Setting Name'); ?>
                                            </label>
                                        </div>
                                        <div class="col col-md-8">
                                            <input type="text" class="form-control" id="settingName" >
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col col-md-2 text-align-right">
                                            <label for="description">
                                                <?= lang('sys.description'); ?>
                                            </label>
                                        </div>
                                        <div class="col col-md-8">
                                            <input type="text" class="form-control" id="description" >
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col col-md-2 text-align-right">
                                            <label for="levelUpgrade">
                                                <?= lang('Upgrade Setting'); ?>
                                            </label>
                                        </div>
                                        <div class="col col-md-8">
                                            <select class="form-control" id="levelUpgrade">
                                                <option value=""><?= lang('Select Upgrade Setting'); ?></option>
                                                <option value="1"><?= lang('Upgrade Only'); ?></option>
                                                <!-- <option value="2"><?= lang('Upgrade and Downgrade') ?></option> -->
                                                <option value="3"><?= lang('Downgrade Only') ?></option>
                                            </select>
                                        </div>
                                    </div>



    <?php if ( ! empty($enable_separate_accumulation_in_setting) ) : ?>
        <!-- /// separate accumulation in setting -->

                                    <div class="row row-options">
                                        <div class="col col-md-2 text-align-right">
                                            <label>
                                                <?= lang('cms.options'); ?>
                                            </label>
                                        </div>
                                        <div class="col col-md-8">
                                            <div class="row row-fieldset_bet_amount">
                                                <div class="col col-md-12">
                                                    <fieldset class="fieldset_bet_amount">
                                                        <legend class="">
                                                            <div class="h5 fieldset_bet_amount-container container-fluid">
                                                                <div class="row">
                                                                    <div class="col-md-9">
                                                                        <label for="enable_bet_amount" class="h5">
                                                                            <input id="enable_bet_amount" name="enable_bet_amount" type="checkbox" class="option" value="1">
                                                                            <?= lang('Bet Amount'); ?>
                                                                        </label>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="btn btn-primary btn-xs bet_amount_settings_more disabled">
                                                                            <?=lang('more')?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div> <!-- EOF .fieldset_bet_amount-container -->
                                                        </legend>
                                                        <div class="row">
                                                            <div class="col-sm-offset-1 col-sm-3">
                                                                <label for="accumulation0option_bet_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_bet_amount" value="0" id="accumulation0option_bet_amount">
                                                                    <?=lang('No Accumulation')?>
                                                                </label>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label for="accumulation1option_bet_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_bet_amount" value="1" id="accumulation1option_bet_amount">
                                                                    <?=lang('Accumulation since the Date of Registration')?>
                                                                </label>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label for="accumulation4option_bet_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_bet_amount" value="4" id="accumulation4option_bet_amount">
                                                                    <?=lang('Accumulation since the Last Change Period')?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </fieldset> <!-- / .fieldset_bet_amount -->
                                                </div>
                                            </div> <!-- EOF .row-fieldset_bet_amount -->

                                            <div class="row row-fieldset_deposit_amount">
                                                <div class="col col-md-12">
                                                    <fieldset class="fieldset_deposit_amount">
                                                        <legend class="">
                                                            <label for="enable_deposit_amount" class="h5 padding-left15px padding-right15px">
                                                                <input id="enable_deposit_amount" name="enable_deposit_amount" type="checkbox" class="option" value="2">
                                                                <?= lang('Deposit Amount'); ?>
                                                            </label>
                                                        </legend>
                                                        <div class="row">
                                                            <div class="col-sm-offset-1 col-sm-3">
                                                                <label for="accumulation0option_deposit_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_deposit_amount" value="0" id="accumulation0option_deposit_amount">
                                                                    <?=lang('No Accumulation')?>
                                                                </label>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label for="accumulation1option_deposit_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_deposit_amount" value="1" id="accumulation1option_deposit_amount">
                                                                    <?=lang('Accumulation since the Date of Registration')?>
                                                                </label>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label for="accumulation4option_deposit_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_deposit_amount" value="4" id="accumulation4option_deposit_amount">
                                                                    <?=lang('Accumulation since the Last Change Period')?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </fieldset> <!-- / .fieldset_deposit_amount -->
                                                </div>
                                            </div> <!-- EOF .row-fieldset_deposit_amount -->


                                            <div class="row row-fieldset_loss_amount">
                                                <div class="col col-md-12">
                                                    <fieldset class="fieldset_loss_amount">
                                                        <legend class="">
                                                            <label for="enable_loss_amount" class="h5 padding-left15px padding-right15px">
                                                                <input id="enable_loss_amount" name="enable_loss_amount" type="checkbox" class="option" value="3">
                                                                <?= lang('Loss Amount'); ?>
                                                            </label>
                                                        </legend>
                                                        <div class="row">
                                                            <div class="col-sm-offset-1 col-sm-3">
                                                                <label for="accumulation0option_loss_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_loss_amount" value="0" id="accumulation0option_loss_amount">
                                                                    <?=lang('No Accumulation')?>
                                                                </label>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label for="accumulation1option_loss_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_loss_amount" value="1" id="accumulation1option_loss_amount">
                                                                    <?=lang('Accumulation since the Date of Registration')?>
                                                                </label>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label for="accumulation4option_loss_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_loss_amount" value="4" id="accumulation4option_loss_amount">
                                                                    <?=lang('Accumulation since the Last Change Period')?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </fieldset> <!-- / .fieldset_loss_amount -->
                                                </div>
                                            </div> <!-- EOF .row-fieldset_loss_amount -->


                                            <div class="row row-fieldset_win_amount">
                                                <div class="col col-md-12">
                                                    <fieldset class="fieldset_win_amount">
                                                        <legend class="">
                                                            <label for="enable_win_amount" class="h5 padding-left15px padding-right15px">
                                                                <input id="enable_win_amount" name="enable_win_amount" type="checkbox" class="option" value="4">
                                                                <?= lang('Win Amount'); ?>
                                                            </label>
                                                        </legend>
                                                        <div class="row">
                                                            <div class="col-sm-offset-1 col-sm-3">
                                                                <label for="accumulation0option_win_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_win_amount" value="0" id="accumulation0option_win_amount">
                                                                    <?=lang('No Accumulation')?>
                                                                </label>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label for="accumulation1option_win_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_win_amount" value="1" id="accumulation1option_win_amount">
                                                                    <?=lang('Accumulation since the Date of Registration')?>
                                                                </label>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label for="accumulation4option_win_amount"  class="cursor-pointer">
                                                                    <input type="radio" name="accumulation_win_amount" value="4" id="accumulation4option_win_amount">
                                                                    <?=lang('Accumulation since the Last Change Period')?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </fieldset> <!-- / .fieldset_win_amount -->
                                                </div>
                                            </div> <!-- EOF .row-fieldset_win_amount -->

                                            <input type="hidden" name="accumulation" value="0"> <!-- reference to accumulation_xxx -->
                                        </div>
                                    </div> <!-- EOF .row-options -->

    <?php else: ?>
    <!-- /// common accumulation in setting -->
                                    <div class="row row-options">
                                        <div class="col col-md-2 text-align-right">
                                            <label>
                                                <?= lang('cms.options'); ?>
                                            </label>
                                        </div>
                                        <div class="col col-md-7">
                                            <div class="row">
                                                <div class="col col-md-3">
                                                    <label class="checkbox-inline">
                                                        <input type="checkbox" class="option" value="1">
                                                        <?= lang('Bet Amount'); ?>
                                                    </label>
                                                    <div class="btn btn-primary btn-xs bet_amount_settings_more disabled">
                                                        <?=lang('more')?>
                                                    </div>
                                                </div>
                                                <div class="col col-md-3">
                                                    <label class="checkbox-inline">
                                                        <input type="checkbox" class="option" value="2">
                                                        <?= lang('Deposit Amount'); ?>
                                                    </label>
                                                </div>
                                                <div class="col col-md-3">
                                                    <label class="checkbox-inline">
                                                        <input type="checkbox" class="option" value="3">
                                                        <?= lang('Loss Amount'); ?>
                                                    </label>
                                                </div>
                                                <div class="col col-md-3">
                                                    <label class="checkbox-inline">
                                                        <input type="checkbox" class="option" value="4">
                                                        <?= lang('Win Amount'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div> <!-- EOF .row-options -->


                                    <div class="row row-accumulation">
                                        <div class="col col-md-2 text-align-right">
                                            <label>
                                                <?= lang('cms.accumulation'); ?>
                                            </label>
                                        </div>
                                        <div class="col col-md-7">
                                            <div class="row">
                                                <div class="col col-md-2">
                                                    <label for="accumulation0option"  class="cursor-pointer">
                                                        <input type="radio" checked name="accumulation" value="0" id="accumulation0option">
                                                        <?=lang('No')?>
                                                    </label>
                                                </div>
                                                <div class="col col-md-2">
                                                    <label for="accumulation1option" class="cursor-pointer">
                                                        <input type="radio" name="accumulation" value="1" id="accumulation1option">
                                                        <?=lang('Yes')?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="row row-computation hide">
                                                <label class="col-sm-2 col-md-offset-2">
                                                    <?= lang('Computation'); ?>
                                                </label>
                                                <div class="col-sm-3 ">
                                                    <label for="accumulationFromRegistration">
                                                        <input class="radio-inline" type="radio" id="accumulationFromRegistration" name="accumulationFrom" value="<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>" checked> <?=lang('Registration Date')?>
                                                    </label>
                                                </div>
                                                <div class="col-sm-3 ">
                                                    <label for="accumulationFromChangedGeade">
                                                        <input class="radio-inline" type="radio" id="accumulationFromChangedGeade" name="accumulationFrom" value="<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>"> <?=lang('Last Change Period')?>
                                                    </label>
                                                </div>
                                            </div> <!-- / .row -->
                                        </div>
                                    </div> <!-- EOF .row-accumulation -->

    <?php endif;  // EOF if ( ! empty($enable_separate_accumulation_in_setting) ) ?>
                                    <hr/>

                                    <div class="form-group">
                                        <div class="col-sm-offset-0 col-sm-12">
                                        <?=lang('Formula')?>
                                        </div>
                                        <div class="col-sm-offset-0 col-sm-12">
                                            <div class="well well-sm formula-container">
                                                    <div class="row">
                                                        <div class="help-block notes text-center">
                                                        </div>
                                                    </div>
                                                    <input name="formula" type="hidden">
                                                    <input name="bet_settings" type="hidden">
                                                    <input name="accumulation_settings" type="hidden">
                                            </div> <!-- EOF .formula-container -->
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary' ?>" id="saveSettingBtn"><i class="fa"></i> <?= lang('Save Setting'); ?></button>
                                    <button type="button" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-primary' ?>" id="showList"><i class="fa fa-caret-square-o-right" aria-hidden="true"></i> <?= lang('Show List'); ?></button>
                                </form>
                            </fieldset>
                        </div>
                    </div>


                    <div class="row hide" id="listContainer">
                        <div class="col-xs-12">
                            <fieldset style="padding:20px">
                                <legend>
                                    <h5><strong> Setting List </strong></h5>
                                </legend>

                                <table id="settingTbl" class="table table-hover" data-page-length='5'>
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th><?= lang('Setting Name'); ?></th>
                                        <th><?= lang('sys.description'); ?></th>
                                        <th><?= lang('Formula'); ?></th>
                                        <th><?= lang('lang.status'); ?></th>
                                        <th><?= lang('lang.action'); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </fieldset>
                        </div>
                    </div> <!-- EOF #listContainer -->

                </div> <!-- EOF .container -->



            </div> <!-- EOF .modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-default' ?>" data-dismiss="modal"><?= lang('lang.close'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" style="margin-top:130px !important;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title"><?= lang('Delete Setting') ?></h4>
            </div>
            <input type="hidden" id="hiddenId">
            <div class="modal-body">
                <?= lang('sys.gd4'); ?>  <span style="color:#ff6666" id="name"></span>?
            </div>
            <div class="modal-footer">
                <a data-dismiss="modal" class="btn btn-default"><?= lang('lang.no'); ?></a>
                <a class="btn btn-primary" id="deleteBtn"><i class="fa"></i> <?= lang('lang.yes'); ?></a>
            </div>
        </div>
    </div>
</div>


<template class="inline-pre-conjunction" data-params="${option} ${checked} ${langAndOr}:LANG.AND_OR">
    <div class="inline check-toggle col-md-1">
        <input id="toggle-${option}" class="conjunction" ${checked} type="checkbox" data-onstyle="success" data-offstyle="info" data-toggle="tooltip" data-placement="top" title="${langAndOr}">
    </div>
</template><!-- EOF .inline-pre-conjunction -->

<template class="inline-accumulation-name" data-params="${name} ${lang_accumulation}:_this.theLANG.Accumulation ">
    <span class="accumulation"> ${lang_accumulation} </span> ${name}
</template> <!-- EOF .inline-accumulation-name -->

<template class="inline-option" data-params="${option} ${name} ${option_1} ${option_2} ${option_3} ${option_4} ${amount}">
    <div class="inline ${option} inline-option-wrapper col-md-4 ">
        <label class="inline-name" style="font-weight: bold;">${name}</label>
        <select class="condition" id="operator-${option}" data-toggle="tooltip" data-placement="top" >
            <option value="1" ${option_1}> &ge; </option>
            <option value="2" ${option_2}> &le; </option>
            <option value="3" ${option_3}> &gt; </option>
            <option value="4" ${option_4}> &lt; </option>
        </select>
        <input type="text" class="custom-input" id="amount-${option}" data-toggle="tooltip" data-placement="top"  value="${amount}">
    </div>
</template> <!-- EOF .inline-option -->


<template class="inline-pre-conjunction-type" data-params="${type} ${id} ${checked} ${langAndOr}:LANG.AND_OR">
    <div class="inline check-toggle col-md-1">
        <input id="toggle-${type}-${id}" class="conjunction" ${checked} type="checkbox" data-onstyle="success" data-offstyle="info" data-toggle="tooltip" data-placement="top" title="${langAndOr}">
    </div>
</template><!-- EOF .inline-pre-conjunction -->

<template class="inline-option-type" data-params="${type} ${optionId} ${id} ${name} ${option_1} ${option_2} ${option_3} ${option_4} ${amount}">
    <div class="inline ${type} ${optionId} inline-option-wrapper col-md-4 ">
        <label class="inline-name" style="font-weight: bold;">${name}</label>
        <select class="condition" id="operator-${type}-${id}" data-toggle="tooltip" data-placement="top" >
            <option value="1" ${option_1}> &ge; </option>
            <option value="2" ${option_2}> &le; </option>
            <option value="3" ${option_3}> &gt; </option>
            <option value="4" ${option_4}> &lt; </option>
        </select>
        <input type="text" class="custom-input" id="amount-${type}-${id}" data-toggle="tooltip" data-placement="top"  value="${amount}" size="6">
    </div>
</template> <!-- EOF .inline-option-type -->

<template class="inline-amount-detail" data-params=" ${option_list} ">
    <div class="inline bet-amount-detail">
    ${option_list}
    </div>
</template> <!-- EOF .inline-bet-amount-detail -->

<script type="text/javascript">

    var checkedRows = [];
    var baseUrl = '<?php echo base_url(); ?>';
    // var enable_accumulation_computation = 1;
    var default_accumulationFrom = <?=Group_level::ACCUMULATION_MODE_DISABLE ?>;
    var ACCUMULATION_MODE_FROM_REGISTRATION = <?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION ?>;
    var enable_separate_accumulation_in_setting = <?=empty($enable_separate_accumulation_in_setting)? 0:1 ?>;

    var AMOUNT_MSG = {
        BET         : '<?= lang('Bet Amount'); ?>',
        DEPOSIT     : '<?= lang('Deposit Amount'); ?>',
        LOSS        : '<?= lang('Loss Amount'); ?>',
        WIN         : '<?= lang('Win Amount'); ?>'
    };

    var LANG =  {
        DELETE          : '<?= lang('lang.delete'); ?>',
        EDIT            : '<?= lang('lang.edit'); ?>',
        DISABLE         : '<?= lang('Disable'); ?>',
        ENABLE          : '<?= lang('Enable'); ?>',
        GREATER_LESS    : '<?= lang('Select greater than or equal to'); ?>',
        AND_OR          : '<?= lang('Select and or'); ?>',
        ENTER_AMOUNT    : '<?= lang('Enter Amount'); ?>',
        HIDE_LIST       : '<?= lang('Hide List'); ?>',
        SHOW_LIST       : '<?= lang('Show List'); ?>'
    };

    // var options = {
    //     "positionClass": "toast-top-center",
    //     closeButton         : true,
    //     timeOut             : 1000,
    //     preventDuplicates   : true
    // };

    // var htmlInput = '<input type="text">';
    // var $listContainer = $('#listContainer');
    // var optionKey = ['bet_amount', 'deposit_amount', 'win_amount', 'loss_amount'];
</script>