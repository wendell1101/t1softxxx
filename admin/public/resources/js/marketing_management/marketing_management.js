var base_url = "/";
var imgloader = "/resources/images/ajax-loader.gif";

function GetXmlHttpObject() {
    if (window.XMLHttpRequest) {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        return new XMLHttpRequest();
        // ref. to https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/Using_XMLHttpRequest
    }
    if (window.ActiveXObject) {
        // code for IE6, IE5
        return new ActiveXObject("Microsoft.XMLHTTP");
        // ref. to https://support.microsoft.com/en-us/topic/dynamic-page-updates-using-xmlhttp-3c16d126-ce9a-6986-3d5d-35bc4f519507
    }
    return null;
}

// sidebar.php
$(document).ready(function() {
    var url = document.location.pathname;
    var res = url.split("/");
    // console.log('test:'+res);
    for (i = 0; i < res.length; i++) {
        // console.log('res-i', res[i]);
        switch (res[i]) {
            // OGP-15145 - view_marketing_setting does not exist.  Discarding all related code.
            // case 'marketingSettings':
            // case 'friend_referral_settings':
            // case 'promoTypeManager':
            // case 'promoCancellationSetup':
            // case 'duplicatePlayerAccountCheckerSetting':
            // case 'viewRegistrationSettings':
            //     $("a#view_marketing_settings").addClass("active");
            //     break;
            case 'viewPromoDepositManager':
                $("a#view_deposit_promo_list").addClass("active");
                break;

            case 'promoApplicationList':
                $("a#view_promoapp_list").addClass("active");
                break;

            case 'referralPromoApplicationList':
                $("a#view_referral_promoapp_list").addClass("active");
                break;

            case 'hugebetPromoApplicationList':
                $("a#view_hugebet_referral_promoapp_list").addClass("active");
                break;

            case 'promoCancellationList':
                $("a#view_promocancel_list").addClass("active");
                break;

            case 'promoPlayerList':
                $("a#view_promoplayer_list").addClass("active");
                break;
            case 'addNewPromo':
            case 'promoRuleManager':
                $("a#view_promorules_settings").addClass("active");
                break;

            case 'manage_cashback_request' :
                $("a#view_cashback_request_list").addClass("active");
                break;

            case 'viewGameLogs':
                $("a#view_game_logs").addClass("active");
                break;

            case 'shoppingCenterItemList':
                $("a#view_shopping_center_list").addClass("active");
                break;

            case 'shoppingClaimRequestList':
                $("a#view_shopper_request_list").addClass("active");
                break;

            case 'shoppingPointExpiration':
                $("a#view_shop_point_expiration").addClass("active");
                break;

            case 'bonusGameSettings':
                $("a#bonus_game_settings").addClass("active");
                break;

            case 'ole777_wager_sync':
                $("a#ole777_wager_sync").addClass("active");
                break;

            case 'cashbackPayoutSetting' :
                // console.log('cashbackPayoutSetting hit');
                $('a#cashbackSettings').addClass("active");
                break;
            case 'viewGameLogsExportHourly':
                $("a#view_game_logs_export_hourly").addClass("active");
                break;

            default:
                break;
        }
    }
}); /// EOF $(document).ready(function() {...


//cashback
$(document).ready(function() {
    // $('#periodTypeMonthly').show();
    // $('.earnings-table-data-monthly').show();

    // $('#periodType').val(0);

    // $('#setTimeHolder').hide();
    // //$('#saveSetting').attr('disabled', true);
    // $('#periodType').change( function() {
    //     var val = $(this).val();
    //     // console.log("val: "+val);

    //    if(val == 0){
    //         $('#setTimeHolder').hide();
    //         $('#saveSetting').attr('disabled', true);
    //     }else{
    //         $('#setTimeHolder').show();
    //         $('#saveSetting').attr('disabled', false);
    //     }

    // });

    // $('#gameType').attr('disabled', false);
    // $('#progressiveType').attr('disabled', false);
    // $('#brandedGame').attr('disabled', false);
    // $('#activeGame').attr('disabled', false);

    // $('#gameProvider').change( function() {
    //     var val = $(this).val();
    //     if (val == 1) {
    //         $('#gameType').attr('disabled', false);
    //         $('#progressiveType').attr('disabled', false);
    //         $('#brandedGame').attr('disabled', false);
    //         $('#activeGame').attr('disabled', false);
    //     } else if (val == 2) {

    //         $('#gameType').attr('disabled', true);
    //         $('#progressiveType').attr('disabled', true);
    //         $('#brandedGame').attr('disabled', true);
    //         $('#activeGame').attr('disabled', true);
    //         $('#gameType').val("");
    //         $('#progressiveType').val("");
    //         $('#brandedGame').val("");
    //         $('#activeGame').val("");

    //     }
    // });
}); /// EOF $(document).ready(function() {...

// general
function GetXmlHttpObject() {
    if (window.XMLHttpRequest) {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        return new XMLHttpRequest();
    }
    if (window.ActiveXObject) {
        // code for IE6, IE5
        return new ActiveXObject("Microsoft.XMLHTTP");
    }
    return null;
}
// end of general

function get_user_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "user_management/get_user_pages/" + segment;

    var div = document.getElementById("user-container");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_skip_pages(totalpage) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var page = document.getElementById('page_num').value;
    var segment = 5;

    if (page == 1 || page > totalpage) {
        segment = '';
    } else if (page > 2) {
        segment = segment * (page - 1);
    }

    if (page == null) {
        return;
    }

    url = base_url + "user_management/get_user_pages/" + segment;

    var div = document.getElementById("user-container");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

$(".alert-message").alert();
window.setTimeout(function() {
    $(".alert-message").alert('close');
}, 2000);

$(function() {
    $('span#description').popover({
        html: true
    });
});

function sortBy(sortName) {
    var xmlhttp = GetXmlHttpObject();
    var order = {
        username: ""
    };

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    order = $('span#sort_' + sortName).text();

    if (order == 'ASC') {
        $('span#sort_' + sortName).innerHTML = 'DESC';
    } else {
        $('span#sort_' + sortName).innerHTML = 'ASC';
    }

    url = base_url + "user_management/sortUsersBy/" + sortName + "/" + order;

    var div = document.getElementById("list_panel_body");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

// -----------------------------------------------------------------------------------------------------------------------------
// KAISER DAPAR
// -----------------------------------------------------------------------------------------------------------------------------

// GLOBAL VARIABLES
//var base_url    = 'http://' + window.location.hostname + '/';
//var imgloader   = 'http://' + window.location.hostname + '/resources/images/ajax-loader.gif';

$(document).ready(function() {

    // variables
    var url = document.location.pathname;
    var urlSegment = url.split('/');

    $.each(urlSegment, function(index, value) {
        if (value) {
            $('a#' + value).addClass('active');
        }
    });

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    // $(".chosen-select").chosen({
    //     disable_search: true,
    // });

    // $("#summernote").summernote();

    $('input[data-toggle="checkbox"]').click(function() {

        var element = $(this);
        var target = element.data('target');

        $(target).prop('checked', this.checked).prop('selected', this.checked).trigger('chosen:updated');

    });

    $('[data-untoggle="checkbox"]').on('change', function() {

        var element = $(this);
        var target = element.data('target');

        if (!this.checked) {
            $(target).prop('checked', false).prop('selected', false);
        }

    });

    $('#form-promoHtmlDescription').submit(function(e) {
        var code = $('#summernote').code();
        $('#promoHtmlDescription').val(code);
        return true;
    });

    // $('#promoStartTimestamp').daterangepicker({
    //     startDate: moment(),
    //     endDate: moment(),
    //     format: 'MMMM DD, YYYY',
    //     showWeekNumbers: true,
    //     singleDatePicker: true,
    //     showDropdowns: true,
    // }, setEndDate);

    // $('#promoPeriodTimestamp').daterangepicker({
    //     format: 'MMMM DD, YYYY',
    //     showWeekNumbers: true,
    //     showDropdowns: true,
    // });

    // $('#promoPeriodTimestamp').on('apply.daterangepicker', function (event, picker) {
    //     $('#promoStartTimestamp').val(picker.startDate.format('YYYY-MM-DD'));
    //     $('#promoEndTimestamp').val(picker.endDate.format('YYYY-MM-DD'));
    // });

    // $('#promoConditionPeriodTimestamp').daterangepicker({
    //     format: 'MMMM DD, YYYY',
    //     showWeekNumbers: true,
    //     showDropdowns: true,
    // });

    // $('#promoConditionPeriodTimestamp').on('apply.daterangepicker', function (event, picker) {
    //     $('#promoConditionStartTimestamp').val(picker.startDate.format('YYYY-MM-DD'));
    //     $('#promoConditionEndTimestamp').val(picker.endDate.format('YYYY-MM-DD'));
    // });

    // $('.daterange').daterangepicker({
    //     format: 'MMMM DD, YYYY',
    //     showWeekNumbers: true,
    //     showDropdowns: true,
    // });

    // $('#promoConditionType').change( function() {
    //     var val = $(this).val();
    //     $('#promoNth').val(null);
    //     if (val == 0) {
    //         $('#promoNth').removeAttr('disabled');
    //         $('#promoNth').show();
    //     } else if (val == 1) {
    //         $('#promoNth').attr('disabled', 'disabled');
    //         $('#promoNth').hide();
    //     }
    // });

    // $('#promoRequiredType').change( function() {
    //     var val = $(this).val();
    //     switch (val) {
    //         case '0':
    //             $('.promoRequiredType').text('Deposit');
    //             break;
    //         case '1':
    //             $('.promoRequiredType').text('Bet');
    //             break;
    //     }
    // });

    // $('#promoPeriod').change( function() {
    //     var val = $(this).val();
    //     switch (val) {
    //         case '0':
    //             $('.promoPeriod').text('Daily');
    //             break;
    //         case '1':
    //             $('.promoPeriod').text('Weekly');
    //             break;
    //         case '2':
    //             $('.promoPeriod').text('Monthly');
    //             break;
    //         case '3':
    //             $('.promoPeriod').text('Yearly');
    //             break;
    //         case '4':
    //             $('.promoPeriod').text('');
    //             break;
    //     }
    // });



    // $('#promoPeriod,#promoLength,#promoStartTimestamp').change(setEndDate);


    $(".number_only").keydown(function(e) {
        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            // Allow: Ctrl+A
            (e.keyCode == 65 && e.ctrlKey === true) ||
            // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)) {
            // let it happen, don't do anything
            return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });


}); /// EOF $(document).ready(function() {...

function setEndDate(start, end) {
    var date = $('#promoStartTimestamp').val();
    var start = moment(date, 'MMMM DD, YYYY');

    var promoPeriod = $('#promoPeriod option:selected').text();
    var promoLength = $('#promoLength').val();

    if (date.trim().length == 0) {
        $('#promoEndTimestamp').val('Please select a Start Date');
    } else if (!start.isValid()) {
        $('#promoEndTimestamp').val('Invalid Date');
    } else if (promoLength == 0) {
        $('#promoEndTimestamp').val('Never');
    } else {
        $('#promoEndTimestamp').val(start.add(promoLength, promoPeriod).format('MMMM DD, YYYY'));
    }
}

function checkSearchGameLogs(value) {
    var checkbox = document.getElementById(value);

    if (checkbox.checked) {
        $('#' + value + "_search").show();
    } else {
        $('#' + value + "_search").hide();
    }
}

function initializedCheckAll(id) {
    var cnt = 0;
    var list = document.getElementsByClassName(id);
    var check = document.getElementById(id);

    for (i = 0; i < list.length; i++) {
        if (list[i].checked)
            cnt++;
    }

    if (id == 'visible' && cnt == 0) {
        // document.getElementById('required').disabled = 1;
    }

    if (cnt == list.length) { check.checked = 1; }
}

function checkAll(type) {
    var list = document.getElementsByClassName(type);
    var list1 = document.getElementsByClassName('required');
    var check = document.getElementById(type);

    if (check.checked) {
        for (i = 0; i < list.length; i++) {
            if (type == 'visible') {
                list[i].disabled = 0;
            }

            list[i].checked = 1;
        }

        if (type == 'visible') {
            document.getElementById('required').disabled = 0;

            for (i = 0; i < list1.length; i++) {
                list1[i].disabled = 0;
            }
        }
    } else {
        check.checked;

        for (i = 0; i < list.length; i++) {
            list[i].checked = 0;
        }

        if (type == 'visible') {
            var get = document.getElementById('31_visible');
            if (get != null)
                get.disabled = 1;

            document.getElementById('required').disabled = 1;

            for (i = 0; i < list1.length; i++) {
                list1[i].disabled = 1;
            }
        }
    }
}

function uncheckAllPlayer(id) {
    var list = document.getElementById(id).className;
    // var all = $(list); // document.getElementById(list);

    var item = document.getElementById(id);
    var allitems = document.getElementsByClassName(list);
    var cnt = 0;

    if (item.checked) {
        var res = id.split("_");

        // if(res[0] == '3' && res[1] == 'visible') {
        //     var get1 = document.getElementById('31_visible');
        //     var get2 = document.getElementById('31_required');

        //     get1.disabled = 0;
        //     get2.disabled = 0;
        // }

        var get = document.getElementById(res[0] + '_required');
        if (get != null) {
            get.disabled = 0;
        }

        for (i = 0; i < allitems.length; i++) {
            if (allitems[i].checked) {
                cnt++;
            }
        }

        if (cnt == allitems.length) {
            $("#visible").prop('checked', true);
            $("#required").prop('disabled', false);
            //           document.getElementById('required').disabled = 0;
        }
    } else {
        $("#visible").prop('checked', false);
        // all.checked = 0;

        var res = id.split("_");

        // if(res[0] == '3' && res[1] == 'visible') {
        //     var get1 = document.getElementById('31_visible');
        //     var get2 = document.getElementById('31_required');

        //     get1.checked = 0;
        //     get1.disabled = 1;

        //     get2.checked = 0;
        //     get2.disabled = 1;
        // }

        if (res[1] == 'visible') {
            document.getElementById('required').disabled = 1;
            var get = document.getElementById(res[0] + '_required');
            if (get != null) {
                get.checked = 0;
                get.disabled = 1;
            }
        }
    }
}

function uncheckAll(id) {
    var list = document.getElementById(id).className;
    // var all = document.getElementById(list);

    var item = document.getElementById(id);
    var allitems = document.getElementsByClassName(list);
    var cnt = 0;

    if (item.checked) {
        var res = id.split("_");

        var get = document.getElementById(res[0] + '_required');
        if (get != null) {
            get.disabled = 0;
        }

        for (i = 0; i < allitems.length; i++) {
            if (allitems[i].checked) {
                cnt++;
            }
        }

        if (cnt == allitems.length) {
            $("#visible").prop('checked', true);
            // all.checked = 1;
            document.getElementById('required').disabled = 0;
        }
    } else {
        // all.checked = 0;
        $("#visible").prop('checked', false);

        var res = id.split("_");

        if (res[1] == 'visible') {
            document.getElementById('required').disabled = 1;
            var get = document.getElementById(res[0] + '_required');
            if (get != null) {
                get.checked = 0;
                get.disabled = 1;
            }
        }
    }
}

function toggleFieldVisibility(field) {

    var isVisible = $('#' + field + '_visible:checked').val();

    if (field == 8 && isVisible) {
        // TODO: If Password show, "Find password by security question" will active automatically
        // TODO: If Email show, "Find password by email" will active automatically
        // If Mobile show, "Find password by SMS" will active automatically
        $('#password_recovery_option_2').prop('checked', true);
    }

    if ( ! isVisible) {
        $('#' + field + '_edit').prop('checked', false);
        $('#' + field + '_required').prop('checked', false);
    }
    $('#' + field + '_edit').prop('disabled', ! isVisible);
    $('#' + field + '_required').prop('disabled', ! isVisible);
}

var marketing_management = marketing_management||{};
marketing_management.initialize = function( options ){
    var _this = this;
    _this = $.extend(true, {}, _this, options);

    return _this;
}; // EOF initialize

marketing_management.viewRegistrationSettings = {};
marketing_management.viewRegistrationSettings.orignal_data = {};
marketing_management.viewRegistrationSettings._enable_restrict_username_more_options = undefined;
marketing_management.viewRegistrationSettings._username_requirement_mode_number_only = 0;
marketing_management.viewRegistrationSettings._username_requirement_mode_letters_only = 1;
marketing_management.viewRegistrationSettings._username_requirement_mode_numbers_and_letters_only = 2;
marketing_management.viewRegistrationSettings.initEvents = function(){
    var _this = this; // _this means viewRegistrationSettings class

    $('body').on('change', '[name="restrict_username"]', function(e){
        _this.changed_restrict_username(e);
    });

    $('body').on('reset', '#updateRegistrationSettingsForm', function(e){
        // var _event = {};
        // _event.target = '[name="restrict_username"]';
        setTimeout(function(){
            _this.reset_updateRegistrationSettingsForm(e);
        }, 1);
    });// onsubmit="return validatePlayerRegForm()"

    // $('[name="restrict_username"]').trigger('change'); // to fire changed_restrict_username()
}; // EOF viewRegistrationSettings.initEvents()


marketing_management.viewRegistrationSettings.updateRadioTo_in_username_requirement_mode = function(_value){
    var _this = this; // _this means viewRegistrationSettings

    var _target$El = $('[name="username_requirement_mode"]');
    _this._updateRadioTo(_value, _target$El);
} // EOF viewRegistrationSettings.updateRadioTo_in_username_requirement_mode()
/**
 * update the radio inputs to specified value
 * @param string _to_value the value string.
 * @param {$('[name]')} _target$El The jQuery selected element, thats' selector must be by name attribute.
 */
marketing_management.viewRegistrationSettings._updateRadioTo = function(_to_value, _target$El){
    var _this = this; // _this means viewRegistrationSettings


    if( _target$El.filter(':checked').val() != _to_value ){

        _target$El.filter('[value="'+ _to_value+ '"]').trigger('click');
    }
} // EOF viewRegistrationSettings._updateRadioTo()

marketing_management.viewRegistrationSettings.updateOnOffSwitchTo_in_restrict_username =  function(_update_to){
    var _this = this; // _this means viewRegistrationSettings

    var _target$El = $('[name="restrict_username"]:checkbox'); // "1" OR false
    _this._updateOnOffSwitchTo(_update_to, _target$El);
} // EOF viewRegistrationSettings.updateOnOffSwitchTo_in_restrict_username()

marketing_management.viewRegistrationSettings.updateOnOffSwitchTo_in_username_case_insensitiv = function(_update_to){
    var _this = this; // _this means viewRegistrationSettings

    var _target$El = $('[name="username_case_insensitive"]:checkbox');
    _this._updateOnOffSwitchTo(_update_to, _target$El);
} // EOF updateCheckboxTo_in_username_case_insensitive
/**
 * Update onoffswitch style checkbox to specified value
 *
 * @param boolean _to The specified value string.
 * @param {$('[name]:checkbox')} _target$El The jQuery selected element, thats' selector must be by name attribute.
 */
marketing_management.viewRegistrationSettings._updateOnOffSwitchTo = function(_to, _target$El){
    var _this =  this;

    if( (_to == true || !!parseInt(_to)== true )
        &&  _target$El.is(':checked') !== true
    ){
        _target$El.trigger('click');
    }else if( _to == false &&  _target$El.not(':checked').length ==0 ){
        _target$El.trigger('click');
    }
} // EOF _updateOnOffSwitchTo

marketing_management.viewRegistrationSettings.reset_updateRegistrationSettingsForm = function(e){
    var _this = this; // _this means viewRegistrationSettings

    if( ! $.isEmptyObject(_this.orignal_data) ){
        var _restrict_username_enabled = _this.orignal_data.restrict_username_enabled;
        if(_restrict_username_enabled == 1){
            _restrict_username_enabled = true;
        }else{
            _restrict_username_enabled = false;
        }

        _this.updateOnOffSwitchTo_in_restrict_username(_restrict_username_enabled);
        $('[name="restrict_username"]').trigger('change');

        if(_restrict_username_enabled == true){
            var _target_in_username_case_insensitive = _this.orignal_data.username_case_insensitive;
            var _target_in_username_requirement_mode = _this.orignal_data.username_requirement_mode;

            // sync to UI
            _this.updateOnOffSwitchTo_in_username_case_insensitiv(_target_in_username_case_insensitive);
            _this.updateRadioTo_in_username_requirement_mode(_target_in_username_requirement_mode);
        }

    }

} // EOF viewRegistrationSettings.reset_updateRegistrationSettingsForm()


marketing_management.viewRegistrationSettings.changed_restrict_username = function(e){
    var _this = this; // _this means viewRegistrationSettings

    var _target$El = $(e.target);
    var _update_to = undefined;
    if( ! _this._enable_restrict_username_more_options ){
        _update_to = false;
    }else if( _target$El.is(':checked') ){
        _update_to = true;
    }else{
        _update_to = false;
    }


    var _target_in_username_case_insensitive = true;
    var _target_in_username_requirement_mode = _this._username_requirement_mode_numbers_and_letters_only;
    if( ! $.isEmptyObject(_this.orignal_data) ){
        _target_in_username_case_insensitive = _this.orignal_data.username_case_insensitive;
        _target_in_username_requirement_mode = _this.orignal_data.username_requirement_mode;
    }
    if(_update_to == false){
        // hide
        $('.restrict_username_row').addClass('hide');

        /// Disable for not overwrite into the database.
        // // If the Restrict Username switch is OFF,
        // // Username Requirement default is Numbers and Letters only
        // // and
        // // Case Insensitive default is ON
        // _target_in_username_requirement_mode = _this._username_requirement_mode_numbers_and_letters_only;
        // _target_in_username_case_insensitive = true;
    }else{
        // show
        $('.restrict_username_row').removeClass('hide');

        // Revert form DB
        // _this.orignal_data = JSON.parse($('#orignal_data').html());
        if( ! $.isEmptyObject(_this.orignal_data) ){
            _target_in_username_case_insensitive = _this.orignal_data.username_case_insensitive;
            _target_in_username_requirement_mode = _this.orignal_data.username_requirement_mode;
        }
    }
    // sync to UI
    _this.updateOnOffSwitchTo_in_username_case_insensitiv(_target_in_username_case_insensitive);
    _this.updateRadioTo_in_username_requirement_mode(_target_in_username_requirement_mode);

} // EOF changed_restrict_username()
marketing_management.viewRegistrationSettings.onreadystatechange = function(){
    var _this = this; // _this means viewRegistrationSettings

    $('[name="restrict_username"]').trigger('change'); // to fire changed_restrict_username()

}; // EOF onreadystatechange()

marketing_management.promoApplicationList = {};

marketing_management.promoApplicationList.initEvents = function(){
    var _this = this; // _this means promoApplicationList class
    _this.dashboardStatClassList = {};
    _this.dashboardStatClassList.request = '.panel_'+ _this.TRANS_STATUS.TRANS_STATUS_REQUEST;
    _this.dashboardStatClassList.approved = '.panel_'+ _this.TRANS_STATUS.TRANS_STATUS_APPROVED;
    _this.dashboardStatClassList.finished_withdraw_condition = '.panel_'+ _this.TRANS_STATUS.TRANS_STATUS_FINISHED_WITHDRAW_CONDITION;
    _this.dashboardStatClassList.declined = '.panel_'+ _this.TRANS_STATUS.TRANS_STATUS_DECLINED;

} // EOF promoApplicationList.initEvents()

marketing_management.promoApplicationList.refreshDashboardStat = function(force_refresh){
    var _this = this; // aka. promoApplicationList

    var _ajax = _this.callCountAllStatusOfPromoApplication(function(){ // beforeCallback
        _this.loadDashboardStatNumberByClass('.dashboard-stat'+ _this.dashboardStatClassList.request);
        _this.loadDashboardStatNumberByClass('.dashboard-stat'+ _this.dashboardStatClassList.approved);
        _this.loadDashboardStatNumberByClass('.dashboard-stat'+ _this.dashboardStatClassList.finished_withdraw_condition);
        _this.loadDashboardStatNumberByClass('.dashboard-stat'+ _this.dashboardStatClassList.declined);
    }, function (xhr, textStatus) { // completeCallback
    }, force_refresh);
    _ajax.done(function(_data, _textStatus, _jqXHR){
        if(_data.status == 'success'){
            // console.error('promoApplicationList.updateDashboardStatNumber.data:', _data.data);

            if(! $.isEmptyObject(_data.data) ){
                Object.keys(_data.data).forEach(function(_key) {
                    switch(_key+ ""){ // convert to string type
                        case _this.TRANS_STATUS.TRANS_STATUS_REQUEST+ "":
                            _this.updateDashboardStatNumberByClass(_data.data[_key], '.dashboard-stat'+ _this.dashboardStatClassList.request);
                        break;
                        case _this.TRANS_STATUS.TRANS_STATUS_APPROVED+ "":
                            _this.updateDashboardStatNumberByClass(_data.data[_key], '.dashboard-stat'+ _this.dashboardStatClassList.approved);
                        break;
                        case _this.TRANS_STATUS.TRANS_STATUS_FINISHED_WITHDRAW_CONDITION+ "":
                            _this.updateDashboardStatNumberByClass(_data.data[_key], '.dashboard-stat'+ _this.dashboardStatClassList.finished_withdraw_condition);
                        break;
                        case _this.TRANS_STATUS.TRANS_STATUS_DECLINED+ "":
                            _this.updateDashboardStatNumberByClass(_data.data[_key], '.dashboard-stat'+ _this.dashboardStatClassList.declined);
                        break;

                        default:
                        break;
                    }
                }); // EOF Object.keys(_data.data).forEach(function(_key) {...
            } // EOF if(! $.isEmptyObject(_data.data) ){...
        } // EOF if(_data.status == 'success'){...
    }); // EOF _ajax.done(function(_data, _textStatus, _jqXHR){...

}
marketing_management.promoApplicationList.updateDashboardStatNumberByClass = function(_number, _classStr){
    var _number$El = $('.dashboard-stat'+ _classStr).find('.details > .number');
    _number$El.find('span.badge_number').html(_number);

    _number$El.find('span.badge_number').removeClass('hide'); // show
    _number$El.find('span.badge_loading').addClass('hide'); // hide
}
marketing_management.promoApplicationList.loadDashboardStatNumberByClass = function(_classStr){
    var _number$El = $('.dashboard-stat'+ _classStr).find('.details > .number');
    _number$El.find('span.badge_number').addClass('hide'); // hide
    _number$El.find('span.badge_loading').removeClass('hide'); // show
}

marketing_management.promoApplicationList.callCountAllStatusOfPromoApplication = function(beforeCallback, completeCallback, force_refresh){
    var date_from = undefined;
    var date_to = undefined;

    var _this = this;
    var date = new Date(), y = date.getFullYear(), m = date.getMonth();
    var firstDay = new Date(y, m, 1);
    var lastDay = new Date(y, m + 1, 0);
    if( typeof(date_from) === 'undefined'){
        date_from = moment(firstDay).format('YYYY-MM-DD');
    }
    if( typeof(date_to) === 'undefined'){
        date_to = moment(lastDay).format('YYYY-MM-DD');
    }
    /// CASOPA = countAllStatusOfPromoApplication
    var _uri = _this.url4CASOPA;
    var _ajax = $.ajax({
        url: _uri,
        type: 'POST',
        data: { 'date_from': date_from // '2023-10-01' // Date, No time
            , 'date_to': date_to // '2023-10-31' // Date, No time
            , 'force_refresh': force_refresh
        },
        beforeSend: function (xhr, settings) {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof(beforeCallback) !== 'undefined'){
                beforeCallback.apply(_this, cloned_arguments);
            }
        },
        complete: function (xhr, textStatus) {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof(completeCallback) !== 'undefined'){
                completeCallback.apply(_this, cloned_arguments);
            }
        }
    });
    return _ajax;
} // EOF promoApplicationList.callCountAllStatusOfPromoApplication()

