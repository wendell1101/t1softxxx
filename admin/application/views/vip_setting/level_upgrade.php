<link href="<?=site_url().'resources/third_party/bower_components/bootstrap-toggle/css/bootstrap-toggle.min.css'?>" rel="stylesheet">
<link href="<?=site_url().'resources/third_party/bower_components/toastr/toastr.min.css'?>" rel="stylesheet">
<!-- <link href="<?=site_url().'resources/third_party/animate/3.6.0/animate.css'?>" rel="stylesheet"> -->

<script type="text/javascript" src="<?=site_url().'resources/third_party/bower_components/bootstrap-toggle/js/bootstrap-toggle.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/third_party/bower_components/toastr/toastr.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/third_party/bower_components/jquery-mask-plugin/src/jquery.mask.js'?>"></script>
<style>
    @media all and (-webkit-min-device-pixel-ratio:0) and (min-resolution: .001dpcm) {
        select.condition
        {
            -webkit-appearance: none;
            appearance: none;
            padding : 2px 5px 2px 5px;
            box-shadow: none !important;
        }
    }
    .inline { display:inline; }
    .custom-input { width: 60px; }
    .well {
        border-radius: 5px;
        height : 55px;
        padding-left: 2px !important;
    }
    @media screen and (min-width: 992px) {
        .modal-lg {
            width: 950px; /* New width for large modal */
        }
        @-moz-document url-prefix() {
            .modal-lg {
                width: 970px; /* Firefox New width for large modal */
            }
        }
    }
    .popover-title { border-radius: 5px 5px 0 0; text-align: center; }
    .popover {
        background-color: #fff;
        max-width: 100%;
        font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
        font-size: 12px;
        line-height: 1;
        border: 1px solid #ccc;
        border-radius: 6px;
        -webkit-box-shadow: 0 5px 10px rgba(0,0,0,.2);
        box-shadow: 0 5px 10px rgba(0,0,0,.2);
        line-break: auto;
        z-index: 1010; /* A value higher than 1010 that solves the problem */
        position: fixed;
    }
    .popover-content {background-color: white; color:#545454;}
    .toast-top-center { margin-top : 80px; }
    #settingTbl_wrapper {
        overflow-y: hidden;
        overflow-x: hidden;
    }

    .sub-legend {
        width: unset !important;
        font-size: 13px;
    }

    .width100 {
        width: 100%;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .margin0px-important {
        margin: 0px!important;
    }
    .margin-left15px-important {
        margin-left: 15px !important;
    }
    .margin-bottom0px {
        margin-bottom: 0;
    }
    .padding-left15px {
        padding-left: 15px;
    }

    .padding-right15px {
        padding-right: 15px;
    }
    .text-align-right {
        text-align:right;
    }
    .font-size-12px {
        font-size:12px;
    }
    label[for*="accumulation"].disabled {
        color: rgba(3, 3, 3, 0.3);
    }
</style>
<!-- Level Upgrade Setting -->
<div id="levelUpModal" class="modal fade " role="dialog">

    <div class="modal-dialog modal-fs">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?= lang('VIP Setting Form'); ?></h4>
            </div>
            <div class="modal-body custom-height-modal">
                <div class="row">
                    <div class="col-xs-12">
                        <fieldset style="padding:20px;margin-bottom: 5px;">
                            <legend>
                                <h5><strong> <?= lang('Add Setting') ?> </strong></h5>
                            </legend>
                            <form class="form-horizontal" id="settingForm">
                                <input type="hidden" id="upgradeId" value="">
                                <div class="form-group">
                                    <label for="settingName" class="col-sm-2 text-align-right"><?= lang('Setting Name'); ?> </label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control" id="settingName" >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description" class="col-sm-2 text-align-right"><?= lang('sys.description') ?></label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control" id="description">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="levelUpgrade" class="col-sm-2 text-align-right"><?= lang('Upgrade Setting') ?></label>
                                    <div class="col-sm-7">
                                        <select class="form-control" id="levelUpgrade">
                                            <option value=""><?= lang('Select Upgrade Setting'); ?></option>
                                            <option value="1"><?= lang('Upgrade Only'); ?></option>
                                            <!-- <option value="2"><?= lang('Upgrade and Downgrade') ?></option> -->
                                            <option value="3"><?= lang('Downgrade Only') ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group enable_separate_accumulation_in_setting-group hide">
                                    <div class="row padding-left15px padding-right15px">

                                        <div class="col-sm-2 text-align-right font-size-12px col-separate_options">
                                            <i class="glyphicon glyphicon-info-sign text-danger hide" data-toggle="tooltip" data-placement="auto" data-html="true" data-title="<?=lang('The formatting is out of date.')?>"></i>
                                            <?= lang('cms.options'); ?>
                                        </div>

                                        <div class="col-sm-7">

                                            <fieldset class="fieldset_bet_amount">
                                                <legend class="">
                                                    <label for="enable_bet_amount" class="h5 padding-left15px padding-right15px">
                                                        <input id="enable_bet_amount" name="enable_bet_amount" type="checkbox" class="option" value="1">
                                                        <?= lang('Bet Amount'); ?>
                                                    </label>
                                                </legend>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option_bet_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_bet_amount" value="<?=Group_level::ACCUMULATION_MODE_DISABLE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option_bet_amount">
                                                            <?=lang('Accumulation since set up/down period')?>
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option_bet_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_bet_amount" value="<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option_bet_amount">
                                                            <?=lang('Accumulation since the Date of Registration')?>
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option_bet_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_bet_amount" value="<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option_bet_amount">
                                                            <input type="hidden" name="accumulation_bet_amount_reset_mode">
                                                            <?=lang('Accumulation since the Last Change Period')?>
                                                            <i class="glyphicon glyphicon-info-sign text-danger illegal-setting hide" data-toggle="tooltip" data-placement="auto" data-html="true" data-title="<?=lang('The formatting is out of date.')?>"></i>
                                                        </label>
                                                    </div>

                                                    <? if( $switch_to_accumulation_reset_ui ): ?>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET?>option_bet_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_bet_amount" value="<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET?>option_bet_amount">
                                                            <input type="hidden" name="accumulation_bet_amount_reset_mode">
                                                            <?=lang('Accumulation since the Last Change Period with Reset If Met')?>
                                                        </label>
                                                    </div>
                                                    <? endif; // EOF if( $switch_to_accumulation_reset_ui ): ?>
                                                </div>
                                            </fieldset> <!-- / .fieldset_bet_amount -->

                                            <fieldset class="fieldset_deposit_amount">
                                                <legend class="">
                                                    <label for="enable_deposit_amount" class="h5 padding-left15px padding-right15px">
                                                        <input id="enable_deposit_amount" name="enable_deposit_amount" type="checkbox" class="option" value="2">
                                                        <?= lang('Deposit Amount'); ?>
                                                    </label>
                                                </legend>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option_deposit_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_deposit_amount" value="<?=Group_level::ACCUMULATION_MODE_DISABLE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option_deposit_amount">
                                                            <?=lang('Accumulation since set up/down period')?>
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option_deposit_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_deposit_amount" value="<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option_deposit_amount">
                                                            <?=lang('Accumulation since the Date of Registration')?>
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option_deposit_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_deposit_amount" value="<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option_deposit_amount">
                                                            <input type="hidden" name="accumulation_deposit_amount_reset_mode">
                                                            <?=lang('Accumulation since the Last Change Period')?>
                                                            <i class="glyphicon glyphicon-info-sign text-danger illegal-setting hide" data-toggle="tooltip" data-placement="auto" data-html="true" data-title="<?=lang('The formatting is out of date.')?>"></i>
                                                        </label>
                                                    </div>
                                                    <? if( $switch_to_accumulation_reset_ui ): ?>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET?>option_deposit_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_deposit_amount" value="<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET?>option_deposit_amount">
                                                            <input type="hidden" name="accumulation_deposit_amount_reset_mode"> <!-- @todo will clear accumulation_deposit_amount_reset_mode in setting popup -->
                                                            <?=lang('Accumulation since the Last Change Period with Reset If Met')?>
                                                        </label>
                                                    </div>
                                                    <? endif; // EOF if( $switch_to_accumulation_reset_ui ): ?>
                                                </div>
                                            </fieldset> <!-- / .fieldset_deposit_amount -->

                                            <fieldset class="fieldset_loss_amount">
                                                <legend class="">
                                                    <label for="enable_loss_amount" class="h5 padding-left15px padding-right15px">
                                                        <input id="enable_loss_amount" name="enable_loss_amount" type="checkbox" class="option" value="3">
                                                        <?= lang('Loss Amount'); ?>
                                                    </label>
                                                </legend>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option_loss_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_loss_amount" value="<?=Group_level::ACCUMULATION_MODE_DISABLE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option_loss_amount">
                                                            <?=lang('Accumulation since set up/down period')?>
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option_loss_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_loss_amount" value="<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option_loss_amount">
                                                            <?=lang('Accumulation since the Date of Registration')?>
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option_loss_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_loss_amount" value="<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option_loss_amount">
                                                            <input type="hidden" name="accumulation_loss_amount_reset_mode">
                                                            <?=lang('Accumulation since the Last Change Period')?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </fieldset> <!-- / .fieldset_loss_amount -->

                                            <fieldset class="fieldset_win_amount">
                                                <legend class="">
                                                    <label for="enable_win_amount" class="h5 padding-left15px padding-right15px">
                                                        <input id="enable_win_amount" name="enable_win_amount" type="checkbox" class="option" value="4">
                                                        <?= lang('Win Amount'); ?>
                                                    </label>
                                                </legend>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                    <label for="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option_win_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_win_amount" value="<?=Group_level::ACCUMULATION_MODE_DISABLE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option_win_amount">
                                                            <?=lang('Accumulation since set up/down period')?>
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option_win_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_win_amount" value="<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option_win_amount">
                                                            <?=lang('Accumulation since the Date of Registration')?>
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option_win_amount"  class="cursor-pointer">
                                                            <input type="radio" name="accumulation_win_amount" value="<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option_win_amount">
                                                            <input type="hidden" name="accumulation_win_amount_reset_mode">
                                                            <?=lang('Accumulation since the Last Change Period')?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </fieldset> <!-- / .fieldset_win_amount -->
                                            <input type="hidden" name="accumulation" value="0"> <!-- reference to accumulation_xxx -->
                                        </div>
                                    </div>
                                </div> <!-- EOF .enable_separate_accumulation_in_setting-group -->
<?php // else: // else of enable_separate_accumulation_in_setting ?>
                                <div class="form-group disable_separate_accumulation_in_setting-group hide">
                                    <label for="inputPassword3" class="col-sm-2 text-align-right"><?= lang('cms.options'); ?></label>
                                    <div class="col-sm-8">
                                        <label class="checkbox-inline"><input type="checkbox" class="option" value="1"><?= lang('Bet Amount'); ?></label>
                                        <label class="checkbox-inline"><input type="checkbox" class="option" value="2"><?= lang('Deposit Amount'); ?></label>
                                        <label class="checkbox-inline"><input type="checkbox" class="option" value="3"><?= lang('Loss Amount'); ?></label>
                                        <label class="checkbox-inline"><input type="checkbox" class="option" value="4"><?= lang('Win Amount'); ?></label>
                                    </div>
                                </div> <!-- EOF .disable_separate_accumulation_in_setting-group -->

                                <div class="form-group row margin-bottom0px disable_separate_accumulation_in_setting-group hide">
                                    <div class="col-sm-2 text-align-right">
                                        <label class="control-label">
                                            <?= lang('cms.accumulation'); ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option"  class="width100 cursor-pointer">
                                            <input type="radio" name="accumulation" value="<?=Group_level::ACCUMULATION_MODE_DISABLE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_DISABLE?>option">
                                            <?=lang('Set Up/Down Period')?>
                                        </label>
                                    </div>
                                    <div class="col-sm-2 ">
                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option" class="width100 cursor-pointer">
                                            <input type="radio" name="accumulation" value="<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION?>option" checked> <?=lang('Registration Date')?>
                                        </label>
                                    </div>
                                    <div class="col-sm-2 ">
                                        <label for="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option" class="width100 cursor-pointer">
                                            <input type="radio" name="accumulation" value="<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>" id="accumulation<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE?>option" > <?=lang('Last Change Period')?>
                                        </label>
                                    </div>

                                </div><!-- EOF .disable_separate_accumulation_in_setting-group -->

                                <hr/>
                                <div class="form-group">
                                    <div class="col-sm-offset-0 col-sm-12">
                                        <div class="well well-sm formula-container">
                                            <div class="row ">
                                                <div class="col-lg-12 text-center">
                                                    <div class="help-block notes">
                                                    </div>
                                                </div>
                                            </div>
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
                </div>

            </div>
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

<script type="text/javascript">

    var checkedRows = []; // for options of the common accumulation. ex: "bet amount", "deposit amount", "loss amount" and "win amount".
    var baseUrl = '<?php echo base_url(); ?>';
    // var enable_accumulation_computation = 1;
    var default_accumulationFrom = <?=Group_level::ACCUMULATION_MODE_DISABLE ?>;
    var ACCUMULATION_MODE_DISABLE = <?=Group_level::ACCUMULATION_MODE_DISABLE ?>;
    var ACCUMULATION_MODE_FROM_REGISTRATION = <?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION ?>;
    var ACCUMULATION_MODE_LAST_CHANGED_GEADE = <?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE ?>;
    var ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS = <?= Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS ?>;
    var ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET = <?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET ?>;

    var enable_separate_accumulation_in_setting = <?=empty($enable_separate_accumulation_in_setting)? 0:1 ?>;
    var enable_accumulation_reset_ui = <?= empty($enable_accumulation_reset_ui)? 'false': 'true' ?>;
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

    var options = {
        "positionClass": "toast-top-center",
        closeButton         : true,
        timeOut             : 1000,
        preventDuplicates   : true
    };

    var htmlInput = '<input type="text">';
    var $listContainer = $('#listContainer');
    var optionKey = ['bet_amount', 'deposit_amount', 'win_amount', 'loss_amount'];

    $(document).ready(function(){

        // // after animated  by animate.css
        // function animateCSS(nodeElement, animationName, callback) {
        //     var handleAnimationEnd = function () {
        //         nodeElement.classList.remove('animated', animationName);
        //         nodeElement.removeEventListener('animationend', handleAnimationEnd);
        //         if (typeof callback === 'function') callback();
        //     }
        //     nodeElement.addEventListener('animationend', handleAnimationEnd);
        //     nodeElement.classList.add('animated', animationName);
        // } // EOF animateCSS



        $(document).on('show.bs.modal', '.modal', function (event) {
            var zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);

            setTimeout(function() {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);

        });
        $(document)
            .on('show.bs.modal', '#levelUpModal', function (e) { // on show
                resetFormModal();
            }).on('shown.bs.modal', '#levelUpModal', function (e) { // after show

            }).on('hide.bs.modal', '#levelUpModal', function (e) { // on hidden

            }).on('hidden.bs.modal', '#levelUpModal', function (e) { // after hidden

            });

        var settingTbl_DataTable = $('#settingTbl').DataTable({
            ajax : {
                url     : baseUrl + 'vipsetting_management/upgradeLevelSetting',
                type    : 'POST',
                async   : true
            },
            "order": [[ 0, "desc" ]],
            searching: true,
            lengthChange: true,
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            columns : [
                { data : 'upgrade_id' , visible : false },
                { data : 'setting_name' },
                { data : 'description' },
                {
                    data : 'formula',
                    render : function(data, type, row) {
                        var formula = jQuery.parseJSON(data);
                        var formulaHtml = '';
                        var operator = '', amount = '';
                        var arr = '';
                        var formulaKey = Object.keys(formula);
                        for(var i in formulaKey) {
                            if(optionKey.indexOf(formulaKey[i]) >= 0) {
                                arr = formula[formulaKey[i]];
                                operator = arr[0];
                                amount = arr[1];
                                if (parseInt(row.accumulation) == 1) {
                                    formulaHtml += '<?=lang('cms.accumulation');?> ';
                                }
                                formulaHtml += optionNameByKey(formulaKey[i]) + ' ' + operator + ' ' + amount + ' ';
                            } else {
                                formulaHtml += formula[formulaKey[i]] + ' ';  // conjunction (or and)
                            }
                        }

                        // Accumulation Computation
                        var theLANG_AC = '';
                        switch(row.accumulation){
                            default:
                            case '0':
                                break;
                            case '1':
                                theLANG_AC = '<?= lang('Accumulation Computation From Registration Date'); ?>';
                                formulaHtml += '<br>';
                                formulaHtml += theLANG_AC;
                                break;
                            case '2':
                                theLANG_AC = '<?= lang('Accumulation Computation From Last Upgeade Period'); ?>';
                                formulaHtml += '<br>';
                                formulaHtml += theLANG_AC;
                                break;
                            case '3':
                                theLANG_AC = '<?= lang('Accumulation Computation From Last Downgrade Period'); ?>';
                                formulaHtml += '<br>';
                                formulaHtml += theLANG_AC;
                                break;
                            case '4':
                                theLANG_AC = '<?= lang('Accumulation Computation From Last Changed Period'); ?>';
                                formulaHtml += '<br>';
                                formulaHtml += theLANG_AC;
                                break;
                        }

                        var title = '';
                        if(row.level_upgrade == 1) {
                            title = '<?= lang('Upgrade Only'); ?>';
                        } else if (row.level_upgrade == 2) {
                            title = '<?= lang('Upgrade and Downgrade'); ?>';
                        } else if (row.level_upgrade == 3) {
                            title = '<?= lang('Downgrade Only'); ?>';
                        }

                        return '<button type="button" title="'+title+'" data-placement="top" data-toggle="popover" data-trigger="focus" ' +
                            'data-content="'+formulaHtml+'" class="red-tooltip <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn btn-sm btn-linkwater' : '' ?>"><?= lang("Preview"); ?></button>';
                    }
                },
                {
                    data : 'status',
                    render : function(data) {
                        var status = '';
                        if(data == 1) {
                            status = `<span style="color:<?=$this->utils->getConfig('use_new_sbe_color') ? "#3C8795" : "#66cc66" ?>;font-weight:bold;"><?= lang("lang.active"); ?></span>`;
                        } else {
                            status = '<span style="color:#ff6666;font-weight:bold;"><?= lang("lang.inactive"); ?></span>';
                        }
                        return status;
                    }
                },
                {
                    data : 'upgrade_id',
                    render : function(data, type, row) {
                        var glypIcon = '', title = '', color = '';
                        if(row.status == 1) {
                            glypIcon = 'glyphicon-ban-circle';
                            title = LANG.DISABLE;
                            color = '#D1374A';
                        } else {
                            glypIcon = 'glyphicon-ok-sign';
                            title = LANG.ENABLE;
                        }
                        return '<a data-toggle="tooltip" class="deleteSetting" data-id="'+data+'" data-name="'+row.setting_name+'" data-original-title="'+LANG.DELETE+'"><span class="glyphicon glyphicon glyphicon-trash"  style="color:'+color+'"></span> </a>  ' +
                            '<a data-toggle="tooltip" class="enableDisable" data-id="'+data+'" data-original-title="'+title+'" data-status="'+row.status+'"><span class="glyphicon '+glypIcon+'" style="color:'+color+'"></span></a> ' +
                            '<a data-toggle="tooltip" class="updateSetting" data-id="'+data+'" data-original-title="'+LANG.EDIT+'"><span class="glyphicon glyphicon-edit"></span> </a>';
                    }
                }
            ],
            drawCallback : function(data) {

                $('[data-toggle="popover"]').popover({
                    html : true
                });
            },
            rowCallback : function(row,data) {
                if(data.status == 2) {
                    row.className = 'info';
                }
                /// moved to "click .updateSetting in #settingTbl".
                // $('.updateSetting', row).off('click').on('click', function(){
                //     addFormData(data);
                // });
            }
        }); // EOF  $('#settingTbl').DataTable({...
        settingTbl_DataTable.page.len( <?=$this->utils->getDefaultItemsPerPage()?> ).draw(); // for lengthChange

        // click .updateSetting in #settingTbl for edit setting
        $('#settingTbl').on('click', '.updateSetting', function (e) {
            var target$El = $(e.target);
            var tr$El = target$El.closest('tr');
            var data = settingTbl_DataTable.row( tr$El ).data();
            resetFormModal(); // for checkedRows = [];
            addFormData(data);
        });
        // click .enableDisable in #settingTbl
        $('#settingTbl').on('click', '.enableDisable', function(e){

            var status =  $(this).attr('data-status');
            $.post( baseUrl + 'vipsetting_management/enableDisableSetting',
                {
                    id : $(this).attr('data-id'),
                    status : status
                }, function(){
                    if(status == 1) {
                        toastr.success('<?= lang('Successfully disable setting'); ?>', '', options);
                    } else {
                        toastr.success('<?= lang('Successfully enable setting'); ?>', '', options);
                    }

                    settingTbl_DataTable.ajax.reload(null,false);
                    loadUpDownGradeSetting();
                }
            ); /// EOF $.post(...
        });// EOF $('#settingTbl').on('click', '.enableDisable', function(e){...

        // click .deleteSetting in #settingTbl
        $('#settingTbl').on('click', '.deleteSetting', function(e){
            $('#name').text($(this).attr('data-name'));
            $('#hiddenId').val($(this).attr('data-id'));
            $('#deleteModal').modal('show');

        });

        $('#showList').on('click', function(){
            if($listContainer.hasClass('hide')) {
                $listContainer.removeClass('hide');
                $(this).html('<i class="fa fa-caret-square-o-down" aria-hidden="true"></i> ' + LANG.HIDE_LIST);
            } else {
                $listContainer.addClass('hide');
                $(this).html('<i class="fa fa-caret-square-o-right" aria-hidden="true"></i> ' + LANG.SHOW_LIST);
            }
        });

        $('#saveSettingBtn').on('click', function(){
            if(validateFields()) {
                toastr.error('<?= lang('player.mp14'); ?>', '', options);
                return false;
            }



            var conjunction = [];
            var formula = {};
            var $id = $('#levelUpModal #upgradeId').val();


            var isSettingInCommonAccumulation = false;
            var isSettingInSeparateAccumulation = false;
            if( $('.disable_separate_accumulation_in_setting-group:visible').length > 0 ){
                // the setting in common accumulation(CA)
                isSettingInCommonAccumulation = true;
            }else if( $('label[for="accumulation0option"]:visible').length > 0 ){
                // the setting in common accumulation(CA)
                isSettingInCommonAccumulation = true;
            }else if( $('.enable_separate_accumulation_in_setting-group:visible').length > 0 ){
                // the setting in separate accumulation(SA)
                isSettingInSeparateAccumulation = true;
            }else if ( $('input[name*="enable_"][name*="_amount"]:visible').length > 0){
                // the setting in separate accumulation(SA)
                isSettingInSeparateAccumulation = true;
            }



            $('#levelUpModal .help-block .conjunction').each(function(){
                var $this = $(this);
                if($this.is(':checked')) {
                    conjunction.push('and');
                } else {
                    conjunction.push('or');
                }
            });

            var arrayLenth = checkedRows.length;
            if(arrayLenth >= 1) {
                for( var i=0; i < arrayLenth; i++) {
                    if(checkedRows[i]) {
                        var x = checkedRows[i];
                        var name = jsonKey(x);
                        var operator = $('#levelUpModal #operator-' + x).val();
                        var amount = $('#levelUpModal #amount-' + x).val();
                        formula[name] = [ operator, amount];
                    }
                }
            }
            var accumulation = null;
            var accumulationFrom = null;
            if(isSettingInCommonAccumulation){
                accumulationFrom = default_accumulationFrom; // default, disable accumulationFrom

                if(  $('input[type="radio"][name="accumulation"]:visible').length > 0 ){
                    accumulation = $('input[type="radio"][name="accumulation"]:checked').val();
                }
                // display for setup, hide for disable.
                if( $('input[type="radio"][name="accumulationFrom"]:visible').length > 0 ){  // used in SB
                    accumulationFrom = $('input[type="radio"][name="accumulationFrom"]:checked').val();
                }else{

                }
            }

            var data = {
                settingName : $('#settingName').val(),
                description : $('#description').val(),
                levelUpgrade : $('#levelUpgrade').val(),
                'accumulationFrom': accumulationFrom,
                formula : formula,
                'accumulation': accumulation,
                conjunction : conjunction
            };
            if($id) {
                data.upgrade_id = $id;
            }

            if(isSettingInSeparateAccumulation){
                // catch the checked xxx_amount and selected accumulation.
                $('fieldset[class*="fieldset_"]:has(.option:checked) [name*="accumulation"]:checked').each(function(indexNumber, currEl){
                    var curr$El = $(currEl);
                    var optionName = curr$El.prop('name');
                    optionName = optionName.split('accumulation_').join(''); // remove prefix,"accumulation_"
                    data['accumulation_'+optionName] = curr$El.val();
                });
            } // EOF if( enable_separate_accumulation_in_setting ){...

            if(isSettingInSeparateAccumulation && false){
                // handle for Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS:
                // and Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET:
                // accumulation_bet_amount_reset_mode, accumulation_deposit_amount_reset_mode
                $('.enable_separate_accumulation_in_setting-group input[name*="_reset_mode"][value]').each(function(indexNumber, currEl){
                    var curr$El = $(currEl);
                    var inputName = curr$El.prop('name');
                    // mixed behavior 983, Sync accumulation_bet_amount and accumulation_bet_amount_reset_mode
                    /// 在 SA 下
                    // 若兩者不一樣，accumulation_bet_amount_reset_mode 必須為 if met / always
                    // 且 accumulation_bet_amount 原來是 LAST_CHANGED_GEADE 後來改為 No Accumulation 或 the Date of Registration
                    // 則會以  if met / always 為主。給 accumulation_bet_amount POST 回去更新設定值。
                    var _reset_mode = curr$El.val();
                    /// remove ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS
                    // if( [ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET, ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS].indexOf( parseInt(_reset_mode) ) != -1  // in_array()
                    if( [ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET ].indexOf( parseInt(_reset_mode) ) != -1  // in_array()
                    ){
                        var optionName = inputName.replace('accumulation_','').replace('_reset_mode',''); // bet_amount/deposit_amount
                        var data_accumulation_option = data['accumulation_'+optionName];
                        var changed_accumulation_option = $('fieldset[class*="fieldset_"]:has(.option:checked) [name*="accumulation_'+ optionName+ '"]:checked').val();

                        /// mixed behavior 983
                        if(data_accumulation_option != _reset_mode
                            && [ ACCUMULATION_MODE_DISABLE, ACCUMULATION_MODE_FROM_REGISTRATION].indexOf( parseInt(changed_accumulation_option)) != -1 // in_array()
                        ){
                            // accumulation_bet 為 No ACCUMULATION 或 註冊日 （有變動）才覆蓋
                            data['accumulation_'+optionName] = changed_accumulation_option;
                        }else if(data_accumulation_option != _reset_mode
                            && [ ACCUMULATION_MODE_LAST_CHANGED_GEADE].indexOf( parseInt(changed_accumulation_option)) != -1 // in_array()
                        ){
                            data['accumulation_'+optionName] = _reset_mode;
                        }
                    }
                });
            } // EOF if(isSettingInSeparateAccumulation){...


            $(this).find('i').addClass('fa-refresh fa-spin');
            setTimeout(function(){

                $.post( baseUrl + 'vipsetting_management/saveUpgradeSetting', data, function(data){
                    if($id) {
                        toastr.success('<?= lang('Successfully Update Setting'); ?>', '', options);
                    } else {
                        toastr.success('<?= lang('Successfully Save Setting'); ?>', '', options);
                    }
                    $('#settingTbl').DataTable().ajax.reload(null,false);
                    loadUpDownGradeSetting();
                    resetFormModal();
                    $('#saveSettingBtn').find('i').removeClass('fa-refresh fa-spin');
                });
            },200); // EOF setTimeout(function(){...
        }); // EOF $('#saveSettingBtn').on('click', function(){...


        $('#deleteBtn').on('click', function(){
            $('#deleteBtn').find('i').addClass('fa-refresh fa-spin');
            setTimeout(function(){
                $.post( baseUrl + 'vipsetting_management/deleteUpgradeLevelSetting', { id : $('#hiddenId').val() }, function(){
                    toastr.success('<?= lang('Upgrade Setting Deleted'); ?>', '', options);
                    $('#settingTbl').DataTable().ajax.reload(null,false);
                    loadUpDownGradeSetting();
                    $('#deleteModal').modal('hide');
                    $('#deleteBtn').find('i').removeClass('fa-refresh fa-spin');
                });
            }, 200)
        });


        $('body').on('change', '#levelUpModal .disable_separate_accumulation_in_setting-group input[type="radio"][name="accumulation"]',function(e){
            changed_accumulation_radio_CA(e);
        });
        $('body').on('change', '#levelUpModal .disable_separate_accumulation_in_setting-group .option:checkbox',function(e){

            // for save
            var isCheck = $(this).is(":checked");
            if(isCheck) {
                checkedRows.push($(this).val());
            } else {
                // remove the item form checkedRows
                var index = checkedRows.indexOf($(this).val());
                if (index > -1) {
                    checkedRows.splice(index, 1);
                }
            }

            changed_option_checkbox_CA(e);
        });

        $('body').on('change', '#levelUpModal .enable_separate_accumulation_in_setting-group input[type="radio"][name="accumulation"]',function(e){
            changed_accumulation_radio_SA(e);
        });
        $('body').on('change', '#levelUpModal .enable_separate_accumulation_in_setting-group .option:checkbox',function(e){

            // for save
            var isCheck = $(this).is(":checked");
            if(isCheck) {
                checkedRows.push($(this).val());
            } else {
                // remove the item form checkedRows
                var index = checkedRows.indexOf($(this).val());
                if (index > -1) {
                    checkedRows.splice(index, 1);
                }
            }


            changed_option_checkbox_SA(e);
        });


        $('.option').change(function(e) {
            return ; // disable by changed_accumulation_radio_XX and changed_option_checkbox_XX
        }); // EOF $('.option').change(function() {...

        $(document).on('reset', '#levelUpModal form', function(e){

            setTimeout(function(){ // Patch for reset failed, at the moment, the ".option" elememts is changing...
                // sync accumulationNoption_XXX_amount
                $('.option').each(function(indexNumber, currEl){
                    var curr$El = $(currEl);
                    var currOptionName = jsonKey(curr$El.val());
                    toggleAccumulationNoption(currOptionName);
                });

                $('.col-separate_options .text-danger').addClass('hide');
            },0);

        });

        // 調整個項目的 accumulation 同步 formula 介面
        var selectorStrList = [];
        selectorStrList.push('input[type="radio"][name*="accumulation\_"]'); // for separate accumulation
        $('#levelUpModal').on('change', selectorStrList.join(','), function(e){
            var target$El = $(e.target);
            var option$El = target$El.closest('fieldset').find('.option');
            var optionName = target$El.prop('name');
            optionName = optionName.split('accumulation_').join(''); // remove prefix,"accumulation_"

            applyAccumulationInFormula(optionName);

        }); // EOF $('#levelUpModal').on('change', selectorStrList.join(','), function(e){...

        $('#levelUpModal').on('change', 'input[type="radio"][name="accumulation"]', function(e){
            // for common accumulation
            var accumulation$El = $(this);
            var target$El = $(e.target);
            if( target$El.find('input:radio[name="accumulation"]').length > 0 ){ // for click on label
                accumulation$El = target$El.find('input:radio:checked[name="accumulation"]');
            }
            var accumulation = accumulation$El.val();

            $('#levelUpModal .help-block > div > label').each(function(){ // .formula-container

                var _lang_accumulation = '<?=lang('cms.accumulation')?>';
                if (parseInt(accumulation) > 0) {
                   var org_text = $(this).text();
                   if(org_text.indexOf(_lang_accumulation) === -1){
                        $(this).text(_lang_accumulation+ ' '+ org_text); // Accumulation Computation
                   }
                } else {
                    if ($(this).parent().hasClass('1')) {
                        $(this).text(AMOUNT_MSG.BET);
                    }
                    if ($(this).parent().hasClass('2')) {
                        $(this).text(AMOUNT_MSG.DEPOSIT);
                    }
                    if ($(this).parent().hasClass('3')) {
                        $(this).text(AMOUNT_MSG.LOSS);
                    }
                    if ($(this).parent().hasClass('4')) {
                        $(this).text(AMOUNT_MSG.WIN);
                    }
                }
            });

            // if(accumulation == 1 ){ // selected "Yes"
            //     displayComputation(true);
            // }else{ // selected "No"
            //     displayComputation(false);
            // }
        }); // EOF $('#levelUpModal input[type="radio"][name="accumulation"]').on('change', function(){

        /**
         * Display OR hidden the computation row
         * @param {boolean} enforceShow To display the computation row if true, else hidden.
         */
        // var displayComputation = function(enforceShow){
        //     if( ! enable_accumulation_computation ){
        //         enforceShow = false;
        //     }
        //     var computation$El = $('.row-computation');
        //     if(enforceShow === true){
        //         // to/keep display
        //         if( computation$El.hasClass('hide') ){ // hide to display
        //             computation$El.removeClass('hide');
        //             animateCSS(computation$El[0], 'fadeIn', function(){
        //                 computation$El.removeClass('fadeIn');
        //             });
        //         } // EOF if( computation$El.hasClass('hide') )
        //     }else{
        //         // to/keep hidden
        //         if( ! computation$El.hasClass('hide') ){ // not hide, display to hide
        //             animateCSS(computation$El[0], 'fadeOut', function(){
        //                 computation$El.addClass('hide');
        //                 computation$El.removeClass('fadeOut');
        //             });
        //         } // EOF if( ! computation$El.hasClass('hide') )
        //     } // EOF if(enforceShow === true)
        // } // EOF displayComputation

    }); // EOF $(document).ready(function(){...


    /**
     * Apply Accumulation Info into the Formula div By the Option Name.
     *
     * @param string optionName The Option Name contains, "bet_amount", "disposit_amount", "win_amount" and "loss_amount".
     */
    function applyAccumulationInFormula(optionName){
        var accumulationVal = $('input[type="radio"][name="accumulation_'+optionName+'"]:checked').val();
        var label$El = $('#levelUpModal .help-block > div.'+ jsonValByKey(optionName) ).find('label'); // in .formula-container
        if(label$El.find('.accumulation').length == 0){
            label$El.prepend('<span class="accumulation">'); // initial
        }

        if( accumulationVal == 1){
            label$El.find('.accumulation').text('<?=lang('cms.accumulation')?> ');
        }else{
            label$El.find('.accumulation').text('');
        }
    } // EOF applyAccumulationInFormula

    /**
     * To dis/enable The Accumulation N options,(0,1,4) By the Option Name
     * @param string optionName The Option Name contains, "bet_amount", "disposit_amount", "win_amount" and "loss_amount".
     * @param string forceStatus Ex: enable, disable.
     */
    function toggleAccumulationNoption(optionName, forceStatus){
        var isToEnable = null;
        switch(forceStatus){
            case 'enable':
                isToEnable = true;
            break;

            case 'disable':
                isToEnable = false;
            break;

            default:
                var jsonVal = jsonValByKey(optionName);
                var theChecked$El = $('input.option[value="'+ jsonVal+ '"]:checked');

                if(theChecked$El.length > 0) { // checked then enable
                    isToEnable = true;
                }else{ // non-checked should be disabled.
                    isToEnable = false;
                }
            break;
        }

        if( isToEnable ){
            enableAccumulationNoption(optionName);
        }else{
            disableAccumulationNoption(optionName);
        }
    } // EOF toggleAccumulationNoption()

    /**
     * To disable The Accumulation N options,(0,1,4) By the Option Name
     *
     * @param string optionName The Option Name contains, "bet_amount", "disposit_amount", "win_amount" and "loss_amount".
     */
    function disableAccumulationNoption(optionName){
        var selectorList = [];
        selectorList.push('[name="accumulation_'+ optionName+ '"]');
        var theRadio$El = $(selectorList.join(','));
        theRadio$El.prop('disabled', true);

        theRadio$El.closest("label").addClass('disabled').removeClass('cursor-pointer');

    } // EOF disableAccumulationNoption()

    /**
     * To enable The Accumulation N options,(0,1,4) By the Option Name
     *
     * @param string optionName The Option Name contains, "bet_amount", "disposit_amount", "win_amount" and "loss_amount".
     */
    function enableAccumulationNoption(optionName){
        var selectorList = [];
        selectorList.push('[name="accumulation_'+ optionName+ '"]');
        var theRadio$El = $(selectorList.join(','));
        theRadio$El.prop('disabled', false);

        theRadio$El.closest("label").removeClass('disabled').addClass('cursor-pointer');
    } // EOF enableAccumulationNoption()

    function toggleAndSelectAccumulationByOption(optionName, optionChecked, accumulationVal){
        var jsonVal = jsonValByKey(optionName);
        if( typeof(optionChecked) === 'undefined'){
            optionChecked = false;
        }
        if( typeof(accumulationVal) === 'undefined'){
            accumulationVal = 0; // No accumulation
        }

        // handle accumulation reset mode
        var _reset_mode = null;
        /// remove ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS
        // if( accumulationVal == ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS
        //     || accumulationVal == ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
        // ){
        if( accumulationVal == ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
        ){
            var accumulation_input_name = 'accumulation_'+ optionName;
            var illegal_setting$El = $('[name="'+ accumulation_input_name+ '"]').closest('label').find('.illegal-setting');
            _reset_mode = accumulationVal;
            if( ! enable_accumulation_reset_ui ){
                // override accumulationVal
                accumulationVal = ACCUMULATION_MODE_LAST_CHANGED_GEADE;

                illegal_setting$El.removeClass('hide'); // show
            }
        }

        // accumulation_bet_amount_reset_mode
        if(_reset_mode !== null){
            $('input[name="accumulation_'+ optionName+ '_reset_mode"]').val(_reset_mode);
        }else{
            $('input[name="accumulation_'+ optionName+ '_reset_mode"]').val('').removeAttr('value');
        }
        // console.log('955.will toggleAccumulationNoption.optionName:', optionName); // bet_amount
        $('.option[value="'+ jsonVal+ '"]').prop('checked', optionChecked);
        toggleAccumulationNoption(optionName);

        $('input[name="accumulation_'+ optionName+ '"][value="'+ accumulationVal+ '"]').prop('checked', true);
        applyAccumulationInFormula(optionName);
    } // EOF toggleAndSelectAccumulationByOption


    function resetFormModal() {

        // for default UI
        if(enable_separate_accumulation_in_setting){
            // separate accumulation
            switchSeparateAccumulationOfAddSetting(true);
        }else{
            // common accumulation
            switchSeparateAccumulationOfAddSetting(false);
        }

        $('#levelUpModal input[type="hidden"][id="upgradeId"]').val('');

        var doTriggerChange = false;
        accumulationSetChecked('0', doTriggerChange);

        var computation$El = $('.row-computation');
        computation$El.removeClass('fadeOut fadeIn animated').addClass('hide');

        $('#levelUpModal .help-block').html('');
        checkedRows = [];

        $('#levelUpModal').find('form').trigger('reset'); // will trigger  $(document).on('reset', '#levelUpModal form', function(e){...

    }

    function getGetOrdinal(n) {
        var s=["th","st","nd","rd"],
            v=n%100;
        return n+(s[(v-20)%10]||s[v]||s[0]);
    }

    function activateToolTip() {
        $('[data-toggle="tooltip"]').tooltip();
    }


    function loadFormula(option, optionLength, isCheck, selectOption, value, andor, selectAccumulation) {
        var $option = $('.' + option);
        var formulaHtml = '';
        var createAndOr = '';
        var formula = '';

        var name = optionName(option);
        if (selectAccumulation !== undefined) {
            if (selectAccumulation == 1) {
                name = '<span class="accumulation"><?=lang('cms.accumulation')?> </span>' + name;
            }
        }
        formulaHtml += '<div class="inline '+ option +'">';
        formulaHtml += '  <label style="font-weight: bold;">' + name + '</label>';
        formulaHtml += '  <select class="condition" id="operator-'+ option +'" data-toggle="tooltip" data-placement="top" >';
        var option_1 = '';
        var option_2 = '';
        var option_3 = '';
        var option_4 = '';
        switch(selectOption) {
            case 1 : option_1 = 'selected="selected"'; break;
            case 2 : option_2 = 'selected="selected"'; break;
            case 3 : option_3 = 'selected="selected"'; break;
            case 4 : option_4 = 'selected="selected"'; break;
        }
        formulaHtml += '    <option value="1" ' + option_1 + '> >= </option>';
        formulaHtml += '    <option value="2" ' + option_2 + '> <= </option>';
        formulaHtml += '    <option value="3" ' + option_3 + '> > </option>';
        formulaHtml += '    <option value="4" ' + option_4 + '> < </option>';
        formulaHtml += '  </select>';
        var amount = 0;
        if ( typeof(value) !== 'undefined') {
            amount = value;
        }
        formulaHtml += '  <input type="text" class="custom-input" id="amount-'+ option +'" data-toggle="tooltip" data-placement="top"  value="'+amount+'">';
        formulaHtml += '</div>';

        createAndOr += '<div class="inline check-toggle">';
        var checked = '';
        if (andor == 'and') {
            checked = 'checked';
        }
        createAndOr += '    <input id="toggle-'+option+'" class="conjunction" ' + checked + ' type="checkbox" data-onstyle="success" data-offstyle="info" data-toggle="tooltip" data-placement="top" title="'+LANG.AND_OR+'">';
        createAndOr += '</div>';

        if(isCheck) {
            if(optionLength == 1) {
                formula = formulaHtml;
            } else if(optionLength >= 2) {
                formula = createAndOr + formulaHtml;
            }
        } else {
            if($option.next('.check-toggle').length) {
                $option.next('.check-toggle').remove();
            } else {
                $option.prev('.check-toggle').remove();
            }
            $option.remove();
        }

        $('#levelUpModal .help-block').append(formula);
    }

    function optionName(optionVal) {
        var optionName = '';
        if(optionVal == 1) {
            optionName = AMOUNT_MSG.BET;
        } else if(optionVal == 2) {
            optionName = AMOUNT_MSG.DEPOSIT;
        } else if(optionVal == 3) {
            optionName = AMOUNT_MSG.LOSS;
        } else if(optionVal == 4) {
            optionName = AMOUNT_MSG.WIN;
        }
        return optionName;
    }

    function optionNameByKey(key) {
        var optionName = '';
        if(key == 'bet_amount') {
            optionName = AMOUNT_MSG.BET;
        } else if(key == 'deposit_amount') {
            optionName = AMOUNT_MSG.DEPOSIT;
        } else if(key == 'loss_amount') {
            optionName = AMOUNT_MSG.LOSS;
        } else if(key == 'win_amount') {
            optionName = AMOUNT_MSG.WIN;
        }
        return optionName;
    }

    function loadBootstrapToggle() {
        $('#levelUpModal #toggle-1, #levelUpModal #toggle-2,#levelUpModal #toggle-3,#levelUpModal #toggle-4').bootstrapToggle({
            on: 'And', off: 'Or', size: "mini"
        });
    }

    function validateFields() {
        var isEmpty = false;
        var $s = $('#levelUpModal #settingName').val();
        var $d = $('#levelUpModal #description').val();
        var $l = $('#levelUpModal #levelUpgrade').val();

        //  Patch for the issue, Save the existing setting as empty ".option" , click "Save Setting" after remove all ".option ".
        var checkedRowsLength =$('.option:checked').length;
        if($s =='' || $d=='' || $l=='' || checkedRowsLength <= 0) {
            isEmpty = true;
        }
        return isEmpty;
    }

    function jsonKey(x) {
        var key = 0;
        if(x == 1) {
            key = 'bet_amount';
        } else if(x == 2) {
            key = 'deposit_amount';
        } else if(x == 3) {
            key = 'loss_amount';
        } else if(x == 4) {
            key = 'win_amount';
        }
        return key;
    }

    function jsonValByKey(key) {
        var val = 0;
        switch(key) {
            case 'bet_amount': val = 1; break;
            case 'deposit_amount': val = 2; break;
            case 'loss_amount': val = 3; break;
            case 'win_amount': val = 4; break;
        }
        return val;
    }

    function operatorValByKey(key) {
        var val = 0;
        switch(key) {
            case '>=': val = 1; break;
            case '<=': val = 2; break;
            case '>': val = 3; break;
            case '<': val = 4; break;
        }
        return val;
    }

    /**
     * Set a Radio input o f accumulation to checked.
     *
     * @param {string} setValue2Checked depend on radio inputs. i.e. "1" or "0", default Zero.
     * @param {boolean} doTriggerChange if true will trigger change event.
     */
    function accumulationSetChecked(setValue2Checked, doTriggerChange){
         var _accumulation = null;
        if( typeof(setValue2Checked) === 'undefined'){
            _accumulation = 0;
        }else{
            _accumulation = setValue2Checked;
        }
        if( typeof(doTriggerChange) === 'undefined'){
            doTriggerChange = false;
        }else{
            doTriggerChange = !!doTriggerChange;
        }

        var accumulation$El = $('#levelUpModal input[type="radio"][name="accumulation"][value="'+_accumulation+'"]');
        accumulation$El.prop("checked", true);

        if(doTriggerChange){
            accumulation$El.trigger('change'); // will trigger
        }

     } // EOF accumulationSetChecked

    function addFormData(data) {

        $('#levelUpModal #upgradeId').val(data.upgrade_id);
        $('#levelUpModal #settingName').val(data.setting_name);
        $('#levelUpModal #description').val(data.description);
        $('#levelUpModal #levelUpgrade').val(data.level_upgrade);
        $('#levelUpModal .help-block').empty();
        $('#levelUpModal .option[type="checkbox"]').prop('checked', false);
        // checkedRows = [];
        var formula = $.parseJSON(data.formula);
        var has_SAS = false; // SAS = separate_accumulation_settings
        var separate_accumulation_settings = {};
        if(data.separate_accumulation_settings !== null ){
            if(data.separate_accumulation_settings.length > 0){
                separate_accumulation_settings = $.parseJSON(data.separate_accumulation_settings);
            }
        }
        // detect separate_accumulation_settings
        if( ! $.isEmptyObject(separate_accumulation_settings) ){
            has_SAS = true; // SAS = separate_accumulation_settings
        }

        if( ! has_SAS ){ // SAS = separate_accumulation_settings
            // for common accumulation settings
            return addFormData4CA(data);
            // switchSeparateAccumulationOfAddSetting(false);


            // var accumulation = parseInt(data.accumulation);
            // var _accumulation = accumulation;
        }else{
            return addFormData4SA(data);
            // for separate_accumulation_settings
            // switchSeparateAccumulationOfAddSetting(true);
            // @todo OGP-24373, separate accumulation assign

        } // EOF if( ! has_SAS ){...


        var formulaKey = Object.keys(formula);
        for (var i in formulaKey) {
            i = parseInt(i);
            if(optionKey.indexOf(formulaKey[i]) >= 0) {
                operator = formula[formulaKey[i]][0];
                amount = formula[formulaKey[i]][1];
                var value = jsonValByKey(formulaKey[i]);
                $('#levelUpModal .option[type="checkbox"][value="'+value+'"]').prop('checked', true);
                checkedRows.push(value);
                var andor = '';
                if ( typeof(formulaKey[i - 1]) !== 'undefined') {
                    andor = formula[formulaKey[i - 1]];
                }

                loadFormula(value, checkedRows.length, true, operatorValByKey(operator), amount, andor, accumulation);

            } // EOF if(optionKey.indexOf(formulaKey[i]) >= 0) {
        }// EOF for (var i in formulaKey) {...


        /// Patch the issue,
        // To sync the UI for the case with Accumulation and "Last Change Period".
        if( ! has_SAS ){ // SAS = separate_accumulation_settings
            if( accumulation >= ACCUMULATION_MODE_FROM_REGISTRATION ){  // will assign Accumulation and "Accumulation Computation"
                var _accumulation = ACCUMULATION_MODE_FROM_REGISTRATION;
                var accumulationFrom = accumulation;
                $('input:radio[name="accumulationFrom"][value="'+accumulationFrom+'"]').prop('checked', true);
            }

            var doTriggerChange = true;
            accumulationSetChecked(_accumulation, doTriggerChange);
        }

        if(enable_separate_accumulation_in_setting){
            for (var i in optionKey ){ // reset Accumulation UI
                var optionName = optionKey[i];
                var optionVal = false;
                var accumulationVal = 0;
                toggleAndSelectAccumulationByOption(optionName, optionVal, accumulationVal); // reset
            }

            if(has_SAS){ // SAS = separate_accumulation_settings

                // to execute toggleAndSelectAccumulationByOption() in data.separate_accumulation_settings for each optionKey
                for (var i in optionKey ){
                    var accumulationVal = data.accumulation;
                    if( optionKey[i] in separate_accumulation_settings ){
                        var optionName = optionKey[i];
                        if( typeof(separate_accumulation_settings[optionName]) !== 'undefined'
                            && 'accumulation' in separate_accumulation_settings[optionName]
                        ){
                            var isCheckedCurrOption = formulaKey.indexOf(optionName) > -1;
                            accumulationVal = separate_accumulation_settings[optionName]['accumulation'];
                        }
                        toggleAndSelectAccumulationByOption(optionName, isCheckedCurrOption, accumulationVal);
                    }
                }// EOF for (var i in optionKey) {...
            }else{

                var accumulationVal = data.accumulation;
                var optionVal = true;
                // var formulaKey = Object.keys(formula);
                for (var i in formulaKey) {
                    i = parseInt(i);
                    if(optionKey.indexOf(formulaKey[i]) >= 0) {
                        var optionName = formulaKey[i];
                        toggleAndSelectAccumulationByOption(optionName, optionVal, accumulationVal);
                    }
                }

            }// EOF if(has_SAS){...

            if( ! has_SAS ){ // SAS = separate_accumulation_settings
                $('.col-separate_options .text-danger').removeClass('hide');
            }else{
                $('.col-separate_options .text-danger').addClass('hide');
            }

        } // EOF if(enable_separate_accumulation_in_setting){

        activateToolTip();
        loadBootstrapToggle();
    } // EOF addFormData

    function addFormData4CA(data){
        var formula = $.parseJSON(data.formula);
        var separate_accumulation_settings = {};
        if(data.separate_accumulation_settings !== null ){
            if(data.separate_accumulation_settings.length > 0){
                separate_accumulation_settings = $.parseJSON(data.separate_accumulation_settings);
            }
        }
        var accumulation = parseInt(data.accumulation);
        // var _accumulation = accumulation;

        var has_SAS = false; // SAS = separate_accumulation_settings
        // detect separate_accumulation_settings
        if( ! $.isEmptyObject(separate_accumulation_settings) ){
            has_SAS = true; // SAS = separate_accumulation_settings
        }
        // for common accumulation settings
        switchSeparateAccumulationOfAddSetting(false);

        var formulaKey = Object.keys(formula);
        for (var i in formulaKey) {
            i = parseInt(i);
            if(optionKey.indexOf(formulaKey[i]) >= 0) {
                operator = formula[formulaKey[i]][0];
                amount = formula[formulaKey[i]][1];
                var value = jsonValByKey(formulaKey[i]);
                $('#levelUpModal .option[type="checkbox"][value="'+value+'"]').prop('checked', true);
                checkedRows.push(value);
                var andor = '';
                if ( typeof(formulaKey[i - 1]) !== 'undefined') {
                    andor = formula[formulaKey[i - 1]];
                }
                loadFormula(value, checkedRows.length, true, operatorValByKey(operator), amount, andor, accumulation);
            } // EOF if(optionKey.indexOf(formulaKey[i]) >= 0) {
        }// EOF for (var i in formulaKey) {...

        /// Patch the issue,
        // To sync the UI for the case with Accumulation and "Last Change Period".
        // /// disable for always be CA,
        // if( ! has_SAS ){ // SAS = separate_accumulation_settings
        if( accumulation >= ACCUMULATION_MODE_FROM_REGISTRATION ){  // will assign Accumulation and "Accumulation Computation"
            var accumulationFrom = accumulation;
            $('input:radio[name="accumulationFrom"][value="'+accumulationFrom+'"]').prop('checked', true);
        }
        var doTriggerChange = true;
        accumulationSetChecked(accumulation, doTriggerChange);
        // }

        activateToolTip();
        loadBootstrapToggle();
    } // EOF addFormData4CA

    /**
     *
     * @param hasSAS, SAS = separate_accumulation_settings
     */
    function switchSeparateAccumulationOfAddSetting(switchToSAS){
        if( typeof(switchToSAS) === 'undefined' ){
            switchToSAS = false; // common accumulation
        }
        if(switchToSAS){
            /// SA = separate accumulation
            $('.enable_separate_accumulation_in_setting-group').removeClass('hide');
            $('.disable_separate_accumulation_in_setting-group').addClass('hide');

            $('.enable_separate_accumulation_in_setting-group').find('.illegal-setting').addClass('hide');
        }else{
            /// CA = common accumulation
            $('.enable_separate_accumulation_in_setting-group').addClass('hide');
            $('.disable_separate_accumulation_in_setting-group').removeClass('hide');
        }
    }

    function changed_accumulation_radio_CA(e){
        // will trigger, $('#levelUpModal').on('change', 'input[type="radio"][name="accumulation"]', function(e){
    }

    function changed_option_checkbox_CA(e){
        var target$El = $(e.target);

        var isCheck = target$El.is(":checked");
        var value = target$El.val();
        var accumulation = $('#levelUpModal input[type="radio"][name="accumulation"]:checked').val();
        var all_selected_options$Els = $('#levelUpModal .disable_separate_accumulation_in_setting-group .option:checked');

        loadFormula(value, all_selected_options$Els.length, isCheck, 0, 0, 'and', parseInt(accumulation));

        activateToolTip();
        loadBootstrapToggle();
    }

    function addFormData4SA(data){

        var formula = $.parseJSON(data.formula);
        var separate_accumulation_settings = {};
        if(data.separate_accumulation_settings !== null ){
            if(data.separate_accumulation_settings.length > 0){
                separate_accumulation_settings = $.parseJSON(data.separate_accumulation_settings);
            }
        }
        var accumulation = parseInt(data.accumulation);

        var has_SAS = false; // SAS = separate_accumulation_settings
        // detect separate_accumulation_settings
        if( ! $.isEmptyObject(separate_accumulation_settings) ){
            has_SAS = true; // SAS = separate_accumulation_settings
        }

        switchSeparateAccumulationOfAddSetting(true);

        // ===
        // var formulaKey = Object.keys(formula);
        // for (var i in formulaKey) { // checkedRows.push() for save
        //     if(optionKey.indexOf(formulaKey[i]) >= 0) {
        //         checkedRows.push(value);
        //     }
        // }
        // get the amount items of formula list in _checkedRows
        var _checkedRows = [];
        var formulaKey = Object.keys(formula);
        for (var i in formulaKey) {
            i = parseInt(i);
            if(optionKey.indexOf(formulaKey[i]) >= 0) {
                operator = formula[formulaKey[i]][0];
                amount = formula[formulaKey[i]][1];
                var value = jsonValByKey(formulaKey[i]);
                _checkedRows.push(value);
                $('#levelUpModal .option[type="checkbox"][value="'+value+'"]').prop('checked', true);
                var andor = '';
                if ( typeof(formulaKey[i - 1]) !== 'undefined') {
                    andor = formula[formulaKey[i - 1]];
                }

                var accumulationVal = accumulation;
                var optionName = formulaKey[i];
                /// Uncaught TypeError: Cannot read properties of undefined (reading 'accumulation')
                // if( typeof(separate_accumulation_settings[optionName]['accumulation']) !== 'undefined' ){
                if( typeof(separate_accumulation_settings[optionName]) !== 'undefined'
                    && 'accumulation' in separate_accumulation_settings[optionName]
                ){
                    // var isCheckedCurrOption = formulaKey.indexOf(optionName) > -1;
                    accumulationVal = separate_accumulation_settings[optionName]['accumulation'];
                }

                loadFormula(value, _checkedRows.length, true, operatorValByKey(operator), amount, andor, accumulationVal);
            } // EOF if(optionKey.indexOf(formulaKey[i]) >= 0) {

        }// EOF for (var i in formulaKey) {...
        checkedRows = _checkedRows; // for save
        // ===

        var formulaKey = Object.keys(formula);

        // to execute toggleAndSelectAccumulationByOption() in data.separate_accumulation_settings for each optionKey
        for (var i in optionKey ){
            var optionName = optionKey[i];
            var accumulationVal = 0;
            var isCheckedCurrOption = formulaKey.indexOf(optionName) > -1;
            // handle reset the Accumulation in separate_accumulation_settings
            if( optionKey[i] in separate_accumulation_settings ){
                if( typeof(separate_accumulation_settings[optionName]) !== 'undefined'
                    && 'accumulation' in separate_accumulation_settings[optionName]
                ){
                    accumulationVal = separate_accumulation_settings[optionName]['accumulation'];
                }
            }

            console.log('12429.will toggleAndSelectAccumulationByOption.optionName:', optionName
                , 'isCheckedCurrOption:', isCheckedCurrOption
                , 'accumulationVal:', accumulationVal
                , 'separate_accumulation_settings:', separate_accumulation_settings);
            toggleAndSelectAccumulationByOption(optionName, isCheckedCurrOption, accumulationVal);
        }// EOF for (var i in optionKey) {...

        activateToolTip();
        loadBootstrapToggle();
    } // EOF addFormData4SA

    function changed_accumulation_radio_SA(e){
        // will trigger selectorStrList.push('input[type="radio"][name*="accumulation\_"]');
    }

    function changed_option_checkbox_SA(e){
        var target$El = $(e.target);
        var isCheck = target$El.is(":checked");
        var value = target$El.val();

        var accumulationVal = target$El.closest('fieldset').find('input[name*="accumulation"]:checked').val();
        if( typeof(accumulationVal) === 'undefined'){
            accumulationVal = 0; // No accumulation
        }

        var checkedRowsLength = $('.enable_separate_accumulation_in_setting-group .option:checked').length;
        loadFormula(value, checkedRowsLength, isCheck, 0, 0, 'and', parseInt(accumulationVal));

        var optionName = jsonKey(target$El.val());

        toggleAndSelectAccumulationByOption(optionName, isCheck, accumulationVal);

        activateToolTip();
        loadBootstrapToggle();
    }

</script>