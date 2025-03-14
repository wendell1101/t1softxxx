<style>
    .sub_title {
        border-radius: 0;
        position: relative;
        display: block;
        padding: 10px 15px;
        color: #008cba;
        background-color: transparent;
    }
    .checkbox_align {
        vertical-align: top;
        margin-right: 5px !important;
    }
</style>
<script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('bootstrap-html5sortable/1.0.0/jquery.sortable.min.js');?>"></script>
<link rel="stylesheet" type="text/css" href="<?=$this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css')?>" />
<script type="text/javascript" src="<?=$this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js');?>"></script>

<div id="registration_main_content">
<div class="panel panel-primary">
    <div class="panel-heading">
        <h1 class="panel-title"><i class="icon-cog"></i> <?=lang('pay.financial_account_setting');?></h1>
    </div>

    <div class="panel-body" id="player_panel_body">
        <div class="col-md-12">
            <ul class="nav nav-tabs">
                <li id="bank_account"><a href="#" onclick="changeFinancialAccountSetting('1');" data-toggle="tab"><?=lang('pay.payment_type_bank');?></a></li>
                <li id="ewallet"><a href="#" onclick="changeFinancialAccountSetting('2');" data-toggle="tab"><?=lang('pay.payment_type_ewallet');?></a></li>
                <li id="crypto"><a href="#" onclick="changeFinancialAccountSetting('3');" data-toggle="tab"><?=lang('pay.payment_type_crypto');?></a></li>
                <li id="others"><a href="#" onclick="changeFinancialAccountSetting('0');" data-toggle="tab"><?=lang('financial_account.others');?></a></li>
            </ul>

            <div id="nav_content" style="width: 100%; height: auto; float: left; border: 1px solid lightgray; border-top: none; padding-bottom: 10px">

            </div>
        </div>
    </div>

    <div class="panel-footer">
    </div>
</div>
</div>

<!-- Modals -->
<div class="modal fade in" id="mainModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="mainModalLabel"></h4>
            </div>
            <div data-dbg="53" class="modal-body"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var maxNumberOfAccountOnTierSetting = '<?= $max_number_of_account_on_tier_setting = $this->config->item('max_number_of_account_on_tier_setting')?>';
    $(document).ready(function () {
        //submenu
        $('#collapseSubmenu').addClass('in');
        $('#view_payment_settings').addClass('active');
        $('#viewPlayerCenterFinancialAccountSettings').addClass('active');
        changeFinancialAccountSetting(<?=$type?>);
    });

    function changeFinancialAccountSetting(type) {
        var xmlhttp = GetXmlHttpObject();

        if (xmlhttp == null) {
            alert("Browser does not support HTTP Request");
            return;
        }

        var div = document.getElementById("nav_content");
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4) {
                div.innerHTML = xmlhttp.responseText;
                $('.tab').removeClass('active');
                $(".switch_checkbox").bootstrapSwitch();

                if(type == 1) {
                    $('#bank_account').addClass('active');
                }else if (type == 2) {
                    $('#ewallet').addClass('active');
                }else if (type == 3){
                    $('#crypto').addClass('active');
                }else if (type == 0){
                    $('#others').addClass('active');
                    generalAccountLimitSetting();
                }
            }

            if (xmlhttp.readyState != 4) {
                div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/><?=lang('text.loading')?></td></tr></table>';
            }
        }

        url = base_url + "payment_management/changeFinancialAccountSetting/" + type;
        xmlhttp.open("GET", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.send();
    }
    function generalAccountLimitSetting() {
        initAccountLimitSetting('deposit');
        initAccountLimitSetting('withdraw');
        renderAccountLimitTable('deposit');
        renderAccountLimitTable('withdraw');
        $('.limit_type_count').on('click' ,function(){
            if($(this).is(":checked")) {
                $(this).next('.max_account_number').removeAttr('readonly').removeAttr('disabled').prop('required', true);
            }
        });

        $('.limit_type_tier').on('click' ,function(){
            if($(this).is(':checked')) {
                $(this).closest('.bank_group').find('.max_account_number').attr({readonly:'readonly',disabled:'disabled'}).removeAttr('required');
            }
        });
    }
    function toggleFieldVisibility(field) {
        var isVisible = $('#' + field + '_visible:checked').val();
        if (!isVisible) {
            $('#' + field + '_required').prop('checked', false);
            $('#' + field + '_required').bootstrapSwitch('state', false);
            if(field =='name'){
                $('#name_edit').prop('checked', false);
                $('#name_edit').attr("disabled", true);
            }
        }
        else {
            $('#name_edit').attr("disabled", false);
        }
        $('#' + field + '_required').prop('disabled', !isVisible);
        $('#' + field + '_required').bootstrapSwitch('disabled', !isVisible);
    }

    function toggleDepositBank() {
        var isEnable = $('#enable_deposit_bank:checked').val();
        if (!isEnable) {
            $('.deposit_bank').prop('checked', false);
            $('#max_deposit_account_number').val('');
            initAccountLimitSetting('Deposit');
        }
        $('.deposit_bank').prop('disabled', !isEnable);
        initAccountLimitSetting('deposit')
    }

    function toggleAccountLimit(field) {
        var isEnable = $('#' + field + '_account_limit:checked').val();
        if (!isEnable) {
            // $('#max_' + field + '_account_number').val('');
        }
        else{
            if(!$('#financial_account_' + field + '_account_limit_type_tier').is(':checked')){
                $('#financial_account_' + field + '_account_limit_type_count').prop('checked',true);
                if(!$('#max_' + field + '_account_number').val()){
                    $('#max_' + field + '_account_number').val(2);
                } else {

                }
                $('#max_' + field + '_account_number').prop('required', 'required');
            }
        }
        $('#max_' + field + '_account_number').prop('disabled', !isEnable);
    }

    function initAccountLimitSetting(field) {
        var isEnable = $('#' + field + '_account_limit:checked').val();
        toggleAccountLimit(field);
        if (isEnable) {
            if($('#financial_account_' + field + '_account_limit_type_tier').is(':checked')){
                $('#max_' + field + '_account_number').prop('disabled',true).prop('readonly',true);
            }
            $('input[name="financial_account_' + field + '_account_limit_type"]').removeAttr('disabled');
            $('input[name="financial_account_' + field + '_account_limit_type"]').prop('required','required');
            $('select[name="financial_account_' + field + '_account_limit_range_conditions"]').removeAttr('disabled');
            $('select[name="financial_account_' + field + '_account_limit_range_conditions"]').prop('required','required');
        }else {
            $('input[name="financial_account_' + field + '_account_limit_type"]').prop('disabled',!isEnable).prop('readonly',!isEnable);
            $('select[name="financial_account_' + field + '_account_limit_range_conditions"]').prop('disabled',!isEnable).prop('readonly','readonly');
        }
    }

    function checkMinMaxValue() {
        var min = $('#length_min').val();
        var max = $('#length_max').val();

        if(parseInt(min) > parseInt(max)){
            alert('<?=lang('financial_account.minmax.prompt')?>');
        }
        else{
            $('.financial_account_setting_form').submit();
        }
    }

    /**
     * render the account limit setting tier level
     * @param dwtype: string; 'deposit' or 'withdraw'
     */
    function renderAccountLimitTable(dwtype){

        var mode = dwtype=='deposit' ? '0' : '1';
        var nextLevelRangeFrom = 0;
        var LastLevelRangeTo = 0;
        var maximumNumberAccountSetting = [];
        var lastSetting = [];
        var modiTable = '';
        var tierTable = $("#"+dwtype+"RangeTable").DataTable({
            "responsive": false,
            "bLengthChange": false,
            "bInfo": false,
            "bFilter": false,
            "ordering": false,
            "paging": false,
            'ajax':{
                "url": '/payment_management/getMaximumNumberAccountSetting/'+ mode,
                "dataSrc":function (response) {
                        if(response.success == true) {
                            maximumNumberAccountSetting = JSON.parse(response.MaximumNumberAccountSetting);
                            lastSetting = JSON.parse(response.MaximumNumberAccountSetting);
                            let i = 0;
                            maximumNumberAccountSetting.map(function(item){
                                return item.index = i++;
                            });

                            if(maximumNumberAccountSetting.length >= 6 || maximumNumberAccountSetting[maximumNumberAccountSetting.length-1].rangeTo == 'Infinity' || maximumNumberAccountSetting[maximumNumberAccountSetting.length-1].noOfAccountsAllowed >= maxNumberOfAccountOnTierSetting) {
                                $('#addTierBtn_' + dwtype).css('display','none');
                            } else {
                                $('#addTierBtn_' + dwtype).css('display','block');
                            }
                            return maximumNumberAccountSetting;
                        } else {
                            return false;
                        }
                    }
            },
            "columns": [
                {
                    render: function (data, type, obj, meta) {
                                if(obj.index == 0) {
                                    return 0;
                                } else {
                                    nextLevelRangeFrom = LastLevelRangeTo;
                                    return nextLevelRangeFrom;
                                }
                            }
                },
                {   data: "rangeTo",
                    render: function (data, type, obj, meta) {
                                LastLevelRangeTo = data;
                                return  data;
                            }
                },
                {   data: "noOfAccountsAllowed",
                    render: function (data, type, obj, meta) {
                                return  data;
                    }
                },
                {   data: "index",
                    render: function (data, type, obj, meta) {
                            var el = '<span class="editTierItem" data-index="'+ data +'" onclick="openEditModal('+ mode +','+ data +')"> <i class="glyphicon glyphicon-edit" style="color:black !important;"></i> </span>';
                            if(data>0) {
                                el+='<span class="deleteTierItem" data-index="' + data + '"> <i class="glyphicon glyphicon-trash" style="color:black !important;"></i> </span>';
                            }
                            return el;
                            }
                }
            ]
        });
        tierTable.on('click', '.deleteTierItem', function(){

            if(confirm("Are you sure to delete this setting?")){

                var itemIndex = $(this).data('index');
                var settingDeferred = $.Deferred();
                var newSetting = function() {
                    if(maximumNumberAccountSetting){
                        settingDeferred.resolve(
                                maximumNumberAccountSetting.filter(function(item) {
                                    return item.index!=itemIndex;
                                })
                        );
                    }else{
                        settingDeferred.rejected(false);
                    }
                    return settingDeferred.promise();
                }

                newSetting().done(function (newSetting) {
                    if(settingDeferred.state() == 'resolved') {

                        $.post('/payment_management/updateMaximumNumberAccountSetting/' + mode,
                            {
                                "setting": newSetting,
                                "lastSetting": lastSetting
                            },
                            function(res) {
                                tierTable.ajax.reload();
                            },'json'
                        );
                    }
                });
                tierTable
                        .row( $(this).parents('tr') )
                        .remove()
                        .draw();
            }
        });
    }
    $('#mainModal').on('hidden.bs.modal', function (){
        var reloadTable = bank_type==0 ? $("#depositRangeTable") : $("#withdrawRangeTable");
        reloadTable.DataTable().ajax.reload();
    });
    function modal(load, title) {
	    var target = $('#mainModal .modal-body');
	    $('#mainModalLabel').html(title);
        target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(load);
        $('#mainModal').modal({backdrop: 'static'}).modal('show');
	    return false;
	}
    function openEditModal(mode, itemIndex) {
        var url = "/payment_management/openEditMaximumNumberAccountSettingModal/" + mode + "/" + itemIndex;
        var title = '<?= lang('financial_account.edit_tier_modal_title');?>';
        modal(url, title);
    }
    function setToInt(element) {
        var val = element.value;
        element.value = parseInt(val);
    }
    function updateTierItem(bank_type, targetIndex) {
        this.bank_type = bank_type;
        this.targetIndex = targetIndex;
        var getUrl = '/payment_management/getMaximumNumberAccountSetting/' + bank_type;
        var updateUrl = '/payment_management/updateMaximumNumberAccountSetting/' + bank_type;
        var nextLevelRangeFrom = 0;
        var LastLevelRangeTo = 0;
        var lastRangeFrom = 0;
        var lastNumOfAccount = 0;
        var targetRangeFrom = 0;
        var targetNumOfAccount = 0;
        var maximumNumberAccountSetting = [];
        var lastSetting = [];
        var isInfinity = $('#setInfinity').is(":checked");

        $('#setInfinity').click(function(){
            isInfinity = $('#setInfinity').is(":checked");
            if(isInfinity) {
                $('#proccessingRangeTo').prop('disabled',true);
            } else {
                $('#proccessingRangeTo').removeAttr('disabled');
            }
        });

        this.tierSettingsTable = $("#tierSettingsTable").DataTable({
            "responsive": false,
            "bLengthChange": false,
            "bInfo": false,
            "bFilter": false,
            "ordering": false,
            "paging": false,
            ajax:{
                "url": getUrl,
                "dataSrc":function (response) {
                        if(response.success == true) {
                            maximumNumberAccountSetting = JSON.parse(response.MaximumNumberAccountSetting);
                            lastSetting = JSON.parse(response.MaximumNumberAccountSetting);
                            var i = 0;
                            maximumNumberAccountSetting.map(function(item){
                                return item.index = i++;
                            });
                            if (targetIndex != 'ADD') {
                                if(maximumNumberAccountSetting[targetIndex].rangeTo != 'Infinity') {
                                    targetRangeFrom = maximumNumberAccountSetting[targetIndex].rangeTo;
                                    $('#proccessingRangeTo').val(parseInt(targetRangeFrom));
                                } else {
                                    $('#setInfinity').prop('checked', true);
                                    $('#proccessingRangeTo').prop('disabled', true);
                                }
                                targetNumOfAccount = maximumNumberAccountSetting[targetIndex].noOfAccountsAllowed;
                                lastRangeFrom = (targetIndex == 0) ? 0 : maximumNumberAccountSetting[targetIndex-1].rangeTo;
                                $('.last_level_rangefrom').val(parseInt(lastRangeFrom));
                                $('#numOfAccount').val(parseInt(targetNumOfAccount));
                            } else {
                                lastRangeFrom = maximumNumberAccountSetting[maximumNumberAccountSetting.length-1].rangeTo;
                                lastNumOfAccount = maximumNumberAccountSetting[maximumNumberAccountSetting.length-1].noOfAccountsAllowed;
                                $('.last_level_rangefrom').val(lastRangeFrom);
                                $('#numOfAccount').val(parseInt(lastNumOfAccount)+1);
                                $('#proccessingRangeTo').val(parseInt(lastRangeFrom)+1);
                            }
                            $('.proccessing').css('display', 'block');

                            return maximumNumberAccountSetting;
                        } else {

                            $('.proccessing').css('display', 'none');
                            return false;
                        }
                    }
            },
            "columns": [
                {
                    render: function (data, type, obj, meta) {
                                if(obj.index == 0) {
                                    return 0;
                                } else {
                                    nextLevelRangeFrom = LastLevelRangeTo;
                                    return nextLevelRangeFrom;
                                }
                            }
                },
                {   data: "rangeTo",
                    render: function (data, type, obj, meta) {
                                LastLevelRangeTo = data;
                                return  data;
                            }
                },
                {   data: "noOfAccountsAllowed",
                    render: function (data, type, obj, meta) {
                                return  data;
                            }
                }
            ]
        });
        this.edit = function () {
            targetIndex = parseInt(targetIndex);
            var is_last_item = (maximumNumberAccountSetting.length == (targetIndex+1)) ? true : false;
            var newRangeTo = $('#proccessingRangeTo').val();
            var newNumOfAccount = parseInt($('#numOfAccount').val());
            var nextRangeTo = 0;
            var nextNumOfAccount = 0;
            var errors = 0;

            isInfinity = $('#setInfinity').is(":checked");
            if(!is_last_item){
                nextRangeTo = maximumNumberAccountSetting[targetIndex+1].rangeTo;
                nextNumOfAccount = maximumNumberAccountSetting[targetIndex+1].noOfAccountsAllowed;
            } else {
                nextRangeTo = lastRangeFrom;
                nextNumOfAccount = maximumNumberAccountSetting[targetIndex].noOfAccountsAllowed;
            }
            lastNumOfAccount = (targetIndex == 0) ? 1 : maximumNumberAccountSetting[targetIndex-1].noOfAccountsAllowed;
            isInfinity = $('#setInfinity').is(":checked");
            if(!isInfinity) {
                newRangeTo = parseInt(newRangeTo);
            }
            if(!isInfinity &&
                (parseInt(lastRangeFrom)>=newRangeTo ||(parseInt(nextRangeTo)<=newRangeTo && !is_last_item) || !newRangeTo)
                || (isInfinity && !is_last_item)) {
                $('#proccessingRangeTo_error').css('display','block');
                errors+=1;
            } else {
                $('#proccessingRangeTo_error').css('display','none');
            }
            if(!newNumOfAccount
                || (targetIndex!=0 && (lastNumOfAccount >= newNumOfAccount))
                || ((nextNumOfAccount <= newNumOfAccount) && !is_last_item)
                || (newNumOfAccount<=0)) {
                $('#numOfAccount_error').css('display','block');
                $('#numOfAccount_error').text('<?=lang('financial_account.edit_tier_error_hint');?>');
                errors+=1;
            }else if(newNumOfAccount>maxNumberOfAccountOnTierSetting){
                $('#numOfAccount_error').css('display','block');
                $('#numOfAccount_error').text('<?=sprintf(lang('financial_account.edit_tier_maximum_account_hint'), $max_number_of_account_on_tier_setting)?>');
                errors+=1;
            }else {
                $('#numOfAccount_error').css('display','none');
            }

            if(errors>0) {
                return false;
            } else {
                //update settings
                maximumNumberAccountSetting[targetIndex].rangeTo = isInfinity? 'Infinity' : newRangeTo;
                maximumNumberAccountSetting[targetIndex].noOfAccountsAllowed = newNumOfAccount;

                $.post( updateUrl,
                    {
                        "setting": maximumNumberAccountSetting,
                        "lastSetting": lastSetting
                    },
                    function(res) {
                        if(res.success) {
                            $('#addSettingProcess').css('display','none');
                            $('#submitUpdateProcess').css('display', 'block');
                            $('#responseMsg').text('<?=lang('financial_account.tier_saved');?>');
                        } else {
                            $('#addSettingProcess').css('display','none');
                            $('#submitUpdateProcess').css('display', 'block');
                            $('#responseMsg').text(res.error_msg);
                        }
                    },'json'
                );
            }
        }
        this.add = function () {
            var errors = 0;
            var newRangeTo = $('#proccessingRangeTo').val();
            var newNumOfAccount = parseInt($('#numOfAccount').val());

            isInfinity = $('#setInfinity').is(":checked");
            if(!isInfinity) {
                newRangeTo = parseInt(newRangeTo);
            }
            if(!isInfinity && (!newRangeTo || parseInt(lastRangeFrom)>=newRangeTo)) {
                $('#proccessingRangeTo_error').css('display','block');
                errors+=1;
            } else {
                $('#proccessingRangeTo_error').css('display','none');
            }
            if(parseInt(lastNumOfAccount) >= newNumOfAccount
                || !newNumOfAccount) {
                $('#numOfAccount_error').css('display','block');
                $('#numOfAccount_error').text('<?=lang('financial_account.edit_tier_error_hint');?>');
                errors+=1;
            }else if(newNumOfAccount>maxNumberOfAccountOnTierSetting){
                $('#numOfAccount_error').css('display','block');
                $('#numOfAccount_error').text('<?=sprintf(lang('financial_account.edit_tier_maximum_account_hint'), $max_number_of_account_on_tier_setting)?>');
                errors+=1;
            }else {
                $('#numOfAccount_error').css('display','none');
            }

            if(errors>0) {
                return false;
            } else {
                //update settings
                var newTierItem = {
                    "rangeTo": isInfinity ? 'Infinity' : newRangeTo,
                    "noOfAccountsAllowed": newNumOfAccount
                };

                var newSetting = maximumNumberAccountSetting.push(newTierItem);
                $.post('/payment_management/updateMaximumNumberAccountSetting/' + bank_type,
                    {
                        "setting": maximumNumberAccountSetting,
                        "lastSetting": lastSetting
                    },
                    function(res) {
                        if(res.success) {
                            $('#addSettingProcess').css('display','none');
                            $('#submitUpdateProcess').css('display', 'block');
                            $('#responseMsg').text('<?=lang('financial_account.tier_saved');?>');
                        } else {
                            $('#addSettingProcess').css('display','none');
                            $('#submitUpdateProcess').css('display', 'block');
                            $('#responseMsg').text(res.error_msg);
                        }
                    },'json'
                );
            }
        }
    }
</script>