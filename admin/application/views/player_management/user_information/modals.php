<!-- mainModal Start -->
    <script type="text/javascript">
        function modal(load, title) {
            $('#mainModalLabel').html(title);
            $('#mainModal .modal-body').html('<center><img src="' + imgloader + '"></center>').load(load);
            $('#mainModal').modal('show');
        }
    </script>
    <div class="modal fade in" id="mainModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="mainModalLabel"></h4>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>
<!-- mainModal End -->
<!-- mainModalV2 Start -->
    <script type="text/javascript">
        /**
         * Show the popup window
         *
         * @param string load The URI for load, or HTML contents.
         * @param string title The title of the popup.
         * @param string modal_id The id prop. of the the popup element.
         * @param string load_source The param, load soure type, ex: "html" Or "load".
         *
         * ref. to https://getbootstrap.com/docs/3.3/javascript/#modals
         * @param string backdrop 	Includes a modal-backdrop element. Alternatively, specify static for a backdrop which doesn't close the modal on click.
         * @param callable showCallback The event, show.bs.modal
         * @param callable shownCallback The event, shown.bs.modal
         * @param callable hideCallback The event, hide.bs.modal
         * @param callable hiddenCallback The event, hidden.bs.modal
         * @param callable loadedCallback The event, loaded.bs.modal
         *
         */
        function modalV2( load // #1
                        , title // #2
                        , modal_id // #3
                        , load_source // #4
                        , backdrop // #5
                        , showCallback // #6
                        , shownCallback // #7
                        , hideCallback // #8
                        , hiddenCallback // #9
                        , loadedCallback // #10
        ) {

            var self = this;
            self.getTplHtmlWithOuterHtmlAndReplaceAll = function (selectorStr, regexList) {
                var _self = this;

                var _outerHtml = '';
                if (typeof (selectorStr) !== 'undefined') {
                    _outerHtml = $(selectorStr).html(); // _self.outerHtml(selectorStr);
                }

                if (typeof (regexList) === 'undefined') {
                    regexList = [];
                }

                if (regexList.length > 0) {
                    regexList.forEach(function (currRegex, indexNumber) {
                        // assign playerpromo_id into the tpl
                        var regex = currRegex['regex']; // var regex = /\$\{playerpromo_id\}/gi;
                        _outerHtml = _outerHtml.replaceAll(regex, currRegex['replaceTo']);// currVal.playerpromo_id);
                    });
                }
                return _outerHtml;
            } // getTplHtmlWithOuterHtmlAndReplaceAll

            self.uuidv4 = function() {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                    var r = (Math.random() * 16) | 0,
                    v = c == 'x' ? r : (r & 0x3) | 0x8;
                    return v.toString(16);
                });
            }


            var nIndex = -1;
            var regexList = [];

            nIndex++; // #0
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{main_modal_id\}/gi; // ${main_modal_id};
            if(typeof(modal_id) === 'undefined' ){
                modal_id = self.uuidv4();
                regexList[nIndex]['replaceTo'] = modal_id;
            }else{
                regexList[nIndex]['replaceTo'] = modal_id;
            }
            nIndex++; // #1
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{title\}/gi; // ${title};
            regexList[nIndex]['replaceTo'] = title;


            nIndex++; // #2
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{backdrop\}/gi; // ${backdrop};
            var _backdrop = 'true';
            if( backdrop == false
                || backdrop == 0
                || backdrop == 'false'
            ){ // convert to string type
                _backdrop = 'false';
            }
            regexList[nIndex]['replaceTo'] = _backdrop; // string type for data-backdrop attr.

            var body$El = $('body');
            var theHtml = self.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-main-modal', regexList);

            if($('#'+ modal_id).length > 0){
                $('#'+ modal_id).detach();
            }
            body$El .append(theHtml);

            $('#'+ modal_id).on('show.bs.modal', function(){
                if( typeof(showCallback) !== 'undefined' ){
                    var cloned_arguments = Array.prototype.slice.call(arguments);
                    showCallback.apply(self, cloned_arguments);
                }
            }).on('shown.bs.modal', function(){
                if( typeof(shownCallback) !== 'undefined' ){
                    var cloned_arguments = Array.prototype.slice.call(arguments);
                    shownCallback.apply(self, cloned_arguments);
                }
            }).on('hide.bs.modal', function(){
                if( typeof(hideCallback) !== 'undefined' ){
                    var cloned_arguments = Array.prototype.slice.call(arguments);
                    hideCallback.apply(self, cloned_arguments);
                }
            }).on('hidden.bs.modal', function(){
                if( typeof(hiddenCallback) !== 'undefined' ){
                    var cloned_arguments = Array.prototype.slice.call(arguments);
                    hiddenCallback.apply(self, cloned_arguments);
                }
            }).on('loaded.bs.modal', function(){
                if( typeof(loadedCallback) !== 'undefined' ){
                    var cloned_arguments = Array.prototype.slice.call(arguments);
                    loadedCallback.apply(self, cloned_arguments);
                }
            });

            // var body$El = $('body');
            // var theTplSelector = '#tpl-main-modal';
            // var theApplyData = {};
            // theApplyData['.main_modal_id'] = modal_id;
            // var theHtml = _this.getHtmlAfterAppliedWithTpl(theTplSelector, theApplyData);
            // body$El .append(theHtml);

            $('#'+ modal_id+ 'Label').html(title);
            if( typeof(load_source ) === 'undefined'){
                load_source = 'html';
            }
            switch(load_source){
                case 'load':
                    $('#'+ modal_id+ ' .modal-body').html('<center><img src="' + imgloader + '"></center>').load(load);
                    break;
                default:
                case 'html':
                    $('#'+ modal_id+ ' .modal-body').html(load);
                    break;
            }

            $('#'+ modal_id).modal('show');


        } // EOF modalV2
    </script>
    <script type="text/template" id="tpl-main-modal">
        <div class="modal fade in" id="${main_modal_id}" tabindex="-1" role="dialog"  data-backdrop="${backdrop}" aria-labelledby="${main_modal_id}Label">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="${main_modal_id}Label">${title}</h4>
                    </div>
                    <div class="modal-body"></div>
                </div>
            </div>
        </div>
    </script>
<!-- mainModalV2 End -->
<!-- ajaxManuallyDowngradeLevel Tip Start -->
<div class="modal fade" id="ajaxManuallyDowngradeLevelModal" tabindex="-1" role="dialog" aria-labelledby="ajaxManuallyDowngradeLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog ajaxManuallyDowngradeLevelModalDialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="ajaxManuallyDowngradeLevelModalLabel"><?=lang('Check Downgrade Condition')?></h4>
            </div>
            <div class="modal-body ajaxManuallyDowngradeLevelModalBody">
                <div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
                            <div class="container-fluid">
                                <div class="check_schedule_title row">
                                    <?=lang('Check the schedule.player downgrading is still on process')?>
                                </div> <!-- EOF .check_schedule_title -->
                                <div class="check_schedule_body container-fluid">
                                    <div class="keepgrade row">
                                        <div class="keepgrade_level col-md-offset-1 col-md-6">
                                            <?=lang('Current VIP Level Name')?>
                                        </div>
                                        <div class="keepgrade_level_value col-md-5">
                                            <?=lang('Loading')?><!-- new member 777 - VIP 3 -->
                                        </div>

                                        <!-- for vip_level_maintain_settings = 0 -->
                                        <div class="guaranteed_period_number-title col-md-offset-1 col-md-6 col-disable-vip_level_maintain_settings hide">
                                            <?=lang('Guaranteed Period Number')?>
                                        </div>
                                        <div class="guaranteed_period_number col-md-5 col-disable-vip_level_maintain_settings hide">
                                            <?=lang('Loading')?> <!-- 3 -->
                                        </div>

                                        <!-- for vip_level_maintain_settings = 0 -->
                                        <div class="guaranteed_period_total_deposit-title col-md-offset-1 col-md-6 col-disable-vip_level_maintain_settings hide">
                                            <?=lang('Guaranteed Period Total Deposit')?>
                                        </div>
                                        <div class="guaranteed_period_total_deposit col-md-5 col-disable-vip_level_maintain_settings hide">
                                            <?=lang('Loading')?> <!-- &#62; 123,456,789.00 -->
                                        </div>



                                        <!-- for vip_level_maintain_settings = 1 -->
                                        <div class="enable_down_maintain-title col-md-offset-1 col-md-6 col-vip_level_maintain_settings hide">
                                            <?=lang('Level Maintain Enable')?>
                                        </div>
                                        <div class="enable_down_maintain col-md-5 col-vip_level_maintain_settings hide">
                                            <?=lang('Loading')?> <!-- &#62;= 3,456,789.00 -->
                                        </div>


                                        <!-- for vip_level_maintain_settings = 1 -->
                                        <div class="down_maintain_period_time-title col-md-offset-1 col-md-6 col-vip_level_maintain_settings hide">
                                            <?=lang('Guaranteed Level Maintain Time')?>
                                        </div>
                                        <div class="down_maintain_period_time col-md-5 col-vip_level_maintain_settings hide">
                                            <?=lang('Loading')?> <!-- &#62; 123,456,789.00 -->
                                        </div>

                                        <!-- for vip_level_maintain_settings = 1 -->
                                        <div class="down_maintain_condition_bet col-md-offset-1 col-md-6 col-vip_level_maintain_settings hide">
                                            <?=lang('Keepgrade Needs Bets Amount')?>
                                        </div>
                                        <div class="down_maintain_condition_bet_amount col-md-5 col-vip_level_maintain_settings hide">
                                            <?=lang('Loading')?> <!-- &#62;= 3,456,789.00 -->
                                        </div>

                                        <!-- for vip_level_maintain_settings = 1 -->
                                        <div class="down_maintain_condition_deposit col-md-offset-1 col-md-6 col-vip_level_maintain_settings hide">
                                            <?=lang('Deposit Amount')?>
                                        </div>
                                        <div class="down_maintain_condition_deposit_amount col-md-5 col-vip_level_maintain_settings hide">
                                            <?=lang('Loading')?> <!-- &#62;= 3,456,789.00 -->
                                        </div>
                                    </div> <!-- EOF .keepgrade -->
                                    <div class="modified row">
                                        <div class="current_level col-md-offset-1 col-md-6">
                                            <?=lang('Current VIP Level Name')?>
                                        </div>
                                        <div class="current_level_value col-md-5">
                                            <?=lang('Loading')?> <!-- new member 777 - VIP 3 -->
                                        </div>
                                    </div><!-- EOF .modified -->
                                    <div class="modified current_bet-row row">
                                        <div class="current_bet col-md-offset-1 col-md-6">
                                            <?=lang('Current VIP Bets Amount')?>
                                        </div>
                                        <div class="current_bet_amount col-md-5">
                                            <?=lang('Loading')?> <!-- 3,456,789.00 -->
                                        </div>
                                    </div><!-- EOF .modified -->
                                    <div class="modified current_deposit-row row">
                                        <div class="current_deposit col-md-offset-1 col-md-6">
                                        <?=lang('Deposit Amount')?>
                                        </div>
                                        <div class="current_deposit_amount col-md-5">
                                            <?=lang('Loading')?> <!-- 3,456,789.00 -->
                                        </div>
                                    </div><!-- EOF .modified -->
                                    <div class="current_cronjob_moment row">
                                        <div class="cronjob_period col-md-offset-1 col-md-6">
                                            <?=lang('Period')?>
                                        </div>
                                        <div class="cronjob_period_value col-md-5">
                                            <?=lang('Loading')?> <!-- 3,456,789.00 -->
                                        </div>
                                    </div>
                                </div><!-- EOF .check_schedule_body -->
                            </div>
						</div>
					</div>
				</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.close');?></button>
            </div>
        </div>
    </div>
</div>
<style type="text/css">

.modified.row>div,
.keepgrade.row>div,
.current_cronjob_moment.row>div {
    text-align: right;
}

.check_schedule_title.row {
    font-size: 1.2em;
    text-align: center;
    font-size: 26px;
}

.keepgrade.row,
.modified.row,
.current_cronjob_moment.row {
    margin-top: 8px;
}

.current_level_value,
.keepgrade_level_value {
    font-weight: bold;
}

.guaranteed_period_number-title.col-disable-vip_level_maintain_settings,
.guaranteed_period_number.col-disable-vip_level_maintain_settings,
.enable_down_maintain-title.col-vip_level_maintain_settings,
.enable_down_maintain.col-vip_level_maintain_settings {
    margin-top: 8px;
}

</style>
<!-- ajaxManuallyDowngradeLevelModal Tip End -->

<!-- for append to .above-content and .below-content -->
<script type="text/template" id="tpl-col-md-offset1-6-5">
    <div class="content-row row">
        <div class="col-md-offset-1 col-md-6 col-content-1 amount-name-col">
            <!-- Current VIP Bets Amount -->
        </div>
        <div class="col-md-5 col-content-2 amount-value-col">
            <!-- >= 999,999,999.00 -->
        </div>
    </div> <!-- EOF .content-row -->
</script>
<script type="text/template" id="tpl-col-md-2-5-5">
    <div class="content-row row">
        <div class="col-md-2 col-content-1">
            <!-- AND / OR -->
        </div>
        <div class="col-md-5 col-content-2 amount-name-col">
            <!-- Current VIP Bets Amount -->
        </div>
        <div class="col-md-5 col-content-3 amount-value-col">
            <!-- >= 999,999,999.00 -->
        </div>
    </div> <!-- EOF .content-row -->
</script>


<!-- ajaxManuallyDowngradeLevel Tip Start -->
<div class="modal fade" id="ajaxManuallyUpgradeLevelModal" tabindex="-1" role="dialog" aria-labelledby="ajaxManuallyUpgradeLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog ajaxManuallyUpgradeLevelModalDialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="ajaxManuallyUpgradeLevelModalLabel"><?=lang('Check Upgrade Condition')?></h4>
            </div>
            <div class="modal-body ajaxManuallyUpgradeLevelModalBody">
                <div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
                            <div class="">
                                <div class="check_schedule_title row">
                                    <?=lang('Upgrade Condition not met')?>
                                </div> <!-- EOF .check_schedule_title -->
                                <div class="check_schedule_body ">
                                    <div class="next_level_name-row row">
                                        <div class="next_level_name-col next_level_name col-md-offset-1 col-md-6">
                                            <?=lang('Next VIP Level Name')?>
                                        </div>
                                        <div class="next_level_name-col col-md-5">
                                            <span class="next_level_name_value">
                                                <?=lang('Loading')?>
                                            </span><!-- Default Player Group - Silver VIP 1 -->
                                            <span class="text-danger skipped_level hide">
                                                <?=lang('Skipped Level')?>
                                            </span>
                                        </div>
                                    </div> <!-- EOF .next_level_name-row -->
                                    <div class="above-content">
                                        <!-- <div class="upgrade_needs_bet col-md-offset-1 col-md-6">
                                        <?=lang('Upgrade Needs Bets Amount')?>
                                        </div>
                                        <div class="upgrade_needs_bet_amount col-md-5">
                                            <?=lang('Loading')?> <! -- &#62;= 3,456,789.00 -- >
                                        </div>

                                        <div class="upgrade_needs_deposit col-md-offset-1 col-md-6">
                                            <?=lang('Deposit Amount')?>
                                        </div>
                                        <div class="upgrade_needs_deposit_amount col-md-5">
                                            <?=lang('Loading')?> <! -- &#62;= 3,456,789.00 -- >
                                        </div> -->
                                    </div>
                                    <div class="current_level-row row">
                                        <div class="current_level-col current_level col-md-offset-1 col-md-6">
                                            <?=lang('Current VIP Level Name')?>
                                        </div>
                                        <div class="current_level-col current_level_value col-md-5">
                                            <?=lang('Loading')?> <!-- new member 777 - VIP 3 -->
                                        </div>
                                    </div><!-- EOF .current_level-row -->
                                    <div class="below-content">
                                        <!-- <div class="current_bet col-md-offset-1 col-md-6">
                                        <?=lang('Current VIP Bets Amount')?>
                                        </div>
                                        <div class="current_bet_amount col-md-5">
                                            <?=lang('Loading')?> <! -- 3,456,789.00 -- >
                                        </div>

                                        <div class="current_deposit col-md-offset-1 col-md-6">
                                        <?=lang('Deposit Amount')?>
                                        </div>
                                        <div class="current_deposit_amount col-md-5">
                                            <?=lang('Loading')?> <! -- 3,456,789.00 -- >
                                        </div> -->
                                    </div>

                                    <div class="current_cronjob_moment row">
                                        <div class="cronjob_period col-md-offset-1 col-md-6">
                                            <?=lang('Period')?>
                                        </div>
                                        <div class="cronjob_period_value col-md-5">
                                            <?=lang('Loading')?> <!-- 3,456,789.00 -->
                                        </div>
                                    </div>
                                </div><!-- EOF .check_schedule_body -->
                            </div>
						</div>
					</div>
				</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.close');?></button>
            </div>
        </div>
    </div>
</div>
<style type="text/css">

.check_schedule_body .row>div {
    text-align: right;
}

.next_level_name-row,
.current_level-row  {
    margin: 8px auto;
}

.next_level_name_value,
.current_level_value {
    font-weight: bold;
}

.content-row .amount-name-col {
    border: #ecececd1 1px solid;
}

.font-size-06em {
    font-size: 0.6em;
}

.check_schedule_body .row>div {
    text-align: left;
}

</style>
<!-- ajaxManuallyUpgradeLevelModal Tip End -->


<!-- simpleModal Start -->
    <script type="text/javascript">
        function success_modal(title, content) {
            var button = '<button class="btn btn-sm btn-scooter" data-dismiss="modal" aria-label="Close"><?=lang('OK')?></button>';

            $('#simpleModal-header').removeClass();
            $('#simpleModal-header').addClass("modal-header modal-header-success");

            showSimpleModal(title, content, button)
        }

        function success_modal_custom_button(title, content, button) {
            $('#simpleModal-header').removeClass();
            $('#simpleModal-header').addClass("modal-header modal-header-success");

            showSimpleModal(title, content, button)
        }

        function error_modal(title, content) {
            var button = '<button class="btn btn-sm btn-scooter" data-dismiss="modal" aria-label="Close"><?=lang('OK')?></button>';

            $('#simpleModal-header').removeClass();
            $('#simpleModal-header').addClass("modal-header modal-header-danger");

            showSimpleModal(title, content, button)
        }

        function confirm_modal(title, content, button) {
            $('#simpleModal-header').removeClass();
            $('#simpleModal-header').addClass("modal-header modal-header-info");

            showSimpleModal(title, content, button)
        }

        function showSimpleModal(title, content, button) {
            $('#simpleModalLabel').html(title);
            $('#simpleModal .modal-body').html(content);
            $('#simpleModal .modal-footer').html(button);
            $('#simpleModal').modal('show');
        }
    </script>
    <div class="modal fade in" id="simpleModal" tabindex="-1" role="dialog" aria-labelledby="simpleModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" id="simpleModal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="simpleModalLabel"></h4>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>
<!-- simpleModal End -->


<!-- Basic Info -->
    <div class="modal fade in" id="comm_pref_notes" tabindex="-1" role="dialog" aria-labelledby="label_comm_pref_notes">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="label_comm_pref_notes"><?=lang('Notes')?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <textarea name="comm_pref_notes" id="comm_pref_notes_content" class="form-control input-sm" rows="7" placeholder="<?=lang('Add notes')?>..."></textarea>
                        <span class="help-block text-danger"></span>
                    </div>
                    <button data-value="" data-key="" class="btn btn-scooter pull-right" id="comm_pref_submit"><?=lang('lang.submit')?></button>
                    <div class="clearfix"></div>
                </div>

            </div>
        </div>
    </div>
<!-- Basic Info -->

<!-- Responsible Gaming -->
    <div class="modal fade bs-example-modal-md" id="rsp_cancel_modal" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header panel-heading">
                    <h3 class="rsp_cancel_title"><?=lang('responsible_gaming.manual.canceled')?></h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label" ><?=lang("Reason")?></label>
                                <textarea class="form-control rsp_cancel_reason" rows="3" maxlength="300"></textarea>
                                <span class="help-block text-danger"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater rsp-reset-reason"><?=lang('lang.reset');?></button>
                    <button type="button" class="btn btn-linkwater rsp-cancel-send" data-dismiss="modal"><?=lang('lang.close');?></button>
                    <button type="button" class="btn btn-scooter btn-submit"><?=lang('cu.9')?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-md" id="rsp_expire_modal" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header panel-heading">
                    <h3 class="rsp_expire_title"><?=lang('responsible_gaming.manual.expired')?></h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label" ><?=lang("Reason")?></label>
                                <textarea class="form-control rsp_expire_reason" rows="3" maxlength="300"></textarea>
                                <span class="help-block text-danger"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater rsp-reset-reason"><?=lang('lang.reset');?></button>
                    <button type="button" class="btn btn-linkwater rsp-expire-send" data-dismiss="modal"><?=lang('lang.close');?></button>
                    <button type="button" class="btn btn-scooter btn-submit"><?=lang('cu.9')?></button>
                </div>
            </div>
        </div>
    </div>
<!-- Responsible Gaming -->


<!-- Withdrawal Condition -->
    <div id="conf-modal" class="modal fade bs-example-modal-md" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header panel-heading">
                    <h3 id="myModalLabel"><?=lang('sys.pay.conf.title');?></h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="help-block" id="conf-msg-ask"></div>
                            <div class="form-group">
                                <label class="control-label" id="conf-msg-reason" ></label>
                                <textarea class="form-control" id="reason-to-cancel"rows="3"></textarea>
                                <div class="help-block text-danger"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="conf-cancel-action" class="btn btn-default" data-dismiss="modal"><?=lang('pay.bt.cancel');?></button>
                    <button type="button" id="conf-yes-action" class="btn btn-scooter"><?=lang('pay.bt.yes');?></button>
                </div>
            </div>
        </div>
    </div>
<!-- Withdrawal Condition -->


<!-- Transfer Condition -->
    <!--WALLET INFO MODAL START-->
    <div class="modal fade bs-example-modal-md" id="wallet_info_modal" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header panel-heading wallet_info_header">
                    <h3 class="wallet_info_title"><?=lang('lang.details')?></h3>
                </div>
                <div class="modal-body wallet_info_body">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" class="transfer_condition_id">
                            <input type="hidden" class="wallet_info">
                            <table class="table table-hover table-bordered table-condensed wallet_info_table">
                                <thead>
                                    <tr>
                                        <th><?=lang('Wallet');?></th>
                                    </tr>
                                </thead>
                                <tbody class="wallet_body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer wallet_info_footer">
                    <button type="button" class="btn btn-default close_wallet_info" data-dismiss="modal"><?=lang('lang.close');?></button>
                </div>
            </div>
        </div>
    </div>
    <!--WALLET INFO MODAL END-->

    <!--WALLET INFO HISTORY_MODAL START-->
    <div class="modal fade bs-example-modal-md" id="wallet_info_history_modal" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header panel-heading wallet_info_history_header">
                    <h3 class="wallet_info_history_title"><?=lang('lang.details')?></h3>
                </div>
                <div class="modal-body wallet_info_history_body">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" class="transfer_condition_id">
                            <input type="hidden" class="wallet_info_history">
                            <table class="table table-hover table-bordered table-condensed wallet_info_history_table">
                                <thead>
                                <tr>
                                    <th><?=lang('Wallet');?></th>
                                </tr>
                                </thead>
                                <tbody class="wallet_history_body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer wallet_info_history_footer">
                    <button type="button" class="btn btn-default close_wallet_info_history" data-dismiss="modal"><?=lang('lang.close');?></button>
                </div>
            </div>
        </div>
    </div>
    <!--WALLET INFO HISTORY_MODAL END-->

    <!--TRANSFER CONDITION CANCEL MODAL START-->
    <div class="modal fade bs-example-modal-md" id="cancel_tc_modal" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header panel-heading">
                    <h3 class="tc_cancel_title"><?=lang('sys.pay.conf.title')?></h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="help-block tc-conf-msg-ask"></div>
                            <div class="form-group">
                                <label class="control-label" ><?=lang("pay.reason")?></label>
                                <textarea class="form-control" id="tc_cancel_reason" rows="3" maxlength="300"></textarea>
                                <span class="help-block text-danger"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" >
                    <button type="button" class="btn btn-default tc-reset-reason"><?=lang('lang.reset');?></button>
                    <button type="button" class="btn btn-default tc-cancel-send" data-dismiss="modal"><?=lang('lang.close');?></button>
                    <button type="button" class="btn btn-scooter btn-submit" id="transfer_condition-submit_btn"><?=lang('cu.9')?></button>
                </div>
            </div>
        </div>
    </div>
    <!--TRANSFER CONDITION CANCEL MODAL END-->
<!-- Transfer Condition -->


<!-- Player's Log - Duplicate Account List -->
    <script type="text/javascript">
        $(document).ready(function() {
            $('#all_tag_list').on('change', function(){
                if( $(this).val() == "" ){
                    $('#addTagModal-submit-btn').attr('disabled', 'disabled');
                } else {
                    $('#addTagModal-submit-btn').removeAttr('disabled');
                }
            });
        });
    </script>
    <div class="modal fade" id="addTagModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document" style="width: 25%">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel"><?=lang('sys.batchAddTag')?></h4>
                </div>
                <form id="add_batch_tags" action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="subject_player_id" value="" />
                        <input type="hidden" name="playerIDs" value="" />

                        <div class="row">
                            <div class="col-md-12">
                                <div class="well"  id="add_staticsites_form">
                                    <select class="form-control" name="tagId" id="all_tag_list">
                                        <option value="" selected="selected"><?=lang('player.ui72')?></option>
                                        <option value="0"><?=lang('player.tp12')?></option>
                                        <?php if(is_array($tags)): ?>
                                            <?php foreach( $tags as $idx => $val ): ?>
                                                <option value="<?=$val['tagId']?>"><?=$val['tagName']?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                       <div class="form-group">
                            <button class="btn btn-sm btn-default" data-dismiss="modal" aria-label="Close"><?=lang('Cancel')?></button>
                            <button onclick="saveTags();" id="addTagModal-submit-btn" class="btn btn-sm btn-scooter" disabled="disabled"><?=lang("lang.save")?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addLinkAcctModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document" style="width: 25%">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel"><?=lang('sys.linkAcct')?></h4>
                </div>
                <form id="link_batch_accts" action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="linkAcctsplayerIDs" value="" />
                        <span id="linkAcctsplayerUsernames" style="word-wrap: break-word;"></span>
                        <input type="hidden" name="linkAcctsplayerUserId" value="" />
                        <br/>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-sm btn-default" data-dismiss="modal" aria-label="Close"><?=lang('Cancel')?></button>
                        <button onclick="saveLinkAccounts();" class="btn btn-sm btn-scooter"><?=lang("lang.save")?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<!-- Player's Log - Duplicate Account List -->

<!-- Linked Account Edit Remarks Modal Start -->
<?php $this->load->view('player_management/linked_account/linked_account_modals'); ?>
<!-- Linked Account Edit Remarks Modal End -->


<?php include VIEWPATH . '/includes/messages.php'; ?>
<?php include VIEWPATH . '/includes/sms.php'; ?>
<?php include VIEWPATH . '/includes/popup_promorules_info.php';?>
<?php include VIEWPATH . "/includes/big_wallet_details.php";?>
<?php include VIEWPATH . "/report_management/duplicate_account_modal.php";?>
