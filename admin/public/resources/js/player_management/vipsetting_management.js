//general
var base_url = "/";
var imgloader = "/resources/images/ajax-loader.gif";

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

function get_vipgroupsetting_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "vipsetting_management/get_vipgroupsetting_pages/" + segment;

    var div = document.getElementById("tag_table");

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

function sortVipGroup(sort) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "vipsetting_management/sortVipGroup/" + sort;

    var div = document.getElementById("tag_table");

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

function searchVipGroup() {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var search = document.getElementById('search').value;

    url = base_url + "vipsetting_management/searchVipGroupList/" + search;

    var div = document.getElementById("tag_table");

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
// ----------------------------------------------------------------------------------------------------------------------------- //

// sidebar.php
$(document).ready(function() {
    var url = document.location.pathname;
    var res = url.split("/");
    for (i = 0; i < res.length; i++) {
        switch (res[i]) {
            case 'vipGroupSettingList':
            case 'editVipGroupLevel':
            case 'viewVipGroupRules':
                $("a#view_vipsetting_list").addClass("active");
                break;

            default:
                break;
        }
    }
});
// end of sidebar.php

// ----------------------------------------------------------------------------------------------------------------------------- //

//--------DOCUMENT READY---------
//---------------
$(document).ready(function() {
    PlayerManagementProcess.initialize();
});
//player management module
var PlayerManagementProcess = {

    initialize : function(theOptions) {
        // console.log("initialized now!");
        var _this = this;

        _this.enable_separate_accumulation_in_setting = 0; // refrernced by $enable_separate_accumulation_in_setting
        _this.ACCUMULATION_MODE_DISABLE = 0; // refrernced by Group_level::ACCUMULATION_MODE_DISABLE
        _this.ACCUMULATION_MODE_FROM_REGISTRATION = 1; // refrernced by Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
        _this.ACCUMULATION_MODE_LAST_UPGEADE = 2; // refrernced by Group_level::ACCUMULATION_MODE_LAST_UPGEADE
        _this.ACCUMULATION_MODE_LAST_DOWNGRADE = 3; // refrernced by Group_level::ACCUMULATION_MODE_LAST_DOWNGRADE
        _this.ACCUMULATION_MODE_LAST_CHANGED_GEADE = 4; // refrernced by Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
        //_this.ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS = 5; // refrernced by Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS
        _this.ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET = 6; // refrernced by Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
        _this.UPGRADE_ONLY = 1; // refrernced by Group_level::UPGRADE_ONLY
        _this.UPGRADE_DOWNGRADE = 2; // refrernced by Group_level::UPGRADE_DOWNGRADE
        _this.DOWNGRADE_ONLY = 3; // refrernced by Group_level::DOWNGRADE_ONLY

        _this.IS_CONFIRM_TO_DO_WAITING = 'waiting';
        _this.IS_CONFIRM_TO_DO_CONFIRMED = 'confirmed';
        _this.IS_CONFIRM_TO_DO_CANCELLED = 'cancelled';


        /// Please replace theLangs by the function, "apply_lang()".
        _this.theLangs = {};

        if( typeof(theOptions) !== 'undefined'){
            _this = $.extend(true, _this, theOptions);
        }

        //numeric only
        $("#groupLevelCount").numeric();

        //tooltip
        $('body').tooltip({
            selector: '[data-toggle="tooltip"]'
        });

        //for add vip group panel
        var is_addPanelVisible = false;

        //for ranking level edit form
        var is_editPanelVisible = false;

        if(!is_addPanelVisible){
            $('.add_vip_group_sec').hide();
        }else{
            $('.add_vip_group_sec').show();
        }

        if(!is_editPanelVisible){
            $('.edit_vip_group_sec').hide();
        }else{
            $('.edit_vip_group_sec').show();
        }

        if(!is_addPanelVisible && !is_editPanelVisible){
            $('#vip_add_edit_panel').hide();
        } else {
            $('#vip_add_edit_panel').show();
        }

        //show hide add vip group panel
        $("#add_vip_group").click(function () {
            if(!is_addPanelVisible){
                is_addPanelVisible = true;
                $('#add_vip_group_glyhicon').removeClass('glyphicon glyphicon-plus-sign');
                $('#add_vip_group_glyhicon').addClass('glyphicon glyphicon-minus-sign');
                $('#vip_add_edit_panel').show();
                $('.add_vip_group_sec').show();
                $('.edit_vip_group_sec').hide();
            }else{
                is_addPanelVisible = false;
                $('#add_vip_group_glyhicon').removeClass('glyphicon glyphicon-minus-sign');
                $('#add_vip_group_glyhicon').addClass('glyphicon glyphicon-plus-sign');
                $('#vip_add_edit_panel').hide();
                $('.add_vip_group_sec').hide();
            }
        });

        //show hide edit vip group panel
        $(".editVipGroupSettingBtn").click(function () {
            is_editPanelVisible = true;
            $('.add_vip_group_sec').hide();
            $('#vip_add_edit_panel').show();
            $('.edit_vip_group_sec').show();
        });

        //cancel add vip group
        $(".addvip-cancel-btn").click(function () {
            is_addPanelVisible = false;
            $('#vip_add_edit_panel').hide();
            $('.add_vip_group_sec').hide();
        });

        //cancel add vip group
        $(".editvip-cancel-btn").click(function () {
            is_editPanelVisible = false;
            $('#vip_add_edit_panel').hide();
            $('.edit_vip_group_sec').hide();
        });

        $(".number_only").keydown(function (e) {
            var code = e.keyCode || e.which;
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(code, [46, 8, 9, 27, 13, 110]) !== -1 ||
                 // Allow: Ctrl+A
            ( e.ctrlKey === true) || ( e.metaKey === true) ||
                 // Allow: home, end, left, right, down, up
                (code >= 35 && code <= 40)) {
                     // let it happen, don't do anything
                     return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (code < 48 || code > 57)) && (code < 96 || code > 105)) {
                e.preventDefault();
            }
        });

        $(".amount_only").keydown(function (e) {
            var code = e.keyCode || e.which;
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(code, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                 // Allow: Ctrl+A
            ( e.ctrlKey === true) || ( e.metaKey === true) ||
                 // Allow: home, end, left, right, down, up
                (code >= 35 && code <= 40)) {
                     // let it happen, don't do anything
                     return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (code < 48 || code > 57)) && (code < 96 || code > 105)) {
                e.preventDefault();
            }
        });

        $(".letters_only").keydown(function (e) {
            var code = e.keyCode || e.which;
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(code, [46, 8, 9, 27, 32, 13, 110, 190]) !== -1 ||
                 // Allow: Ctrl+A
            ( e.ctrlKey === true) || ( e.metaKey === true) ||
                 // Allow: home, end, left, right, down, up
                (code >= 35 && code <= 40)) {
                     // let it happen, don't do anything
                     return;
            }
            // Ensure that it is a number and stop the keypress
            if (e.ctrlKey === true || code < 65 || code > 90) {
                e.preventDefault();
            }
        });

        $(".letters_numbers_only").keydown(function (e) {
            var code = e.keyCode || e.which;
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(code, [46, 8, 9, 27, 32, 13, 110, 190]) !== -1 ||
                 // Allow: Ctrl+A
            ( e.ctrlKey === true) || ( e.metaKey === true) ||
                 // Allow: home, end, left, right, down, up
                (code >= 35 && code <= 40)) {
                     // let it happen, don't do anything
                     return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (code < 48 || code > 57)) && (code < 96 || code > 105) && (e.ctrlKey === true || code < 65 || code > 90)) {
                e.preventDefault();
            }
        });

        $(".usernames_only").keydown(function (e) {
            var code = e.keyCode || e.which;
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(code, [46, 8, 9, 27, 13]) !== -1 ||
                 // Allow: Ctrl+A
            ( e.ctrlKey === true) || ( e.metaKey === true) ||
                 // Allow: home, end, left, right, down, up
                (code >= 35 && code <= 40) ||
                 // Allow: underscores
                (e.shiftKey && code == 189)) {
                     // let it happen, don't do anything
                     return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (code < 48 || code > 57)) && (code < 96 || code > 105) && (e.ctrlKey === true || code < 65 || code > 90)) {
                e.preventDefault();
            }
        });

        $(".emails_only").keydown(function (e) {
            var code = e.keyCode || e.which;
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(code, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                 // Allow: Ctrl+A
            ( e.ctrlKey === true) || ( e.metaKey === true) ||
                 // Allow: home, end, left, right, down, up
                (code >= 35 && code <= 40) ||
                 //Allow: Shift+2
                (e.shiftKey && code == 50)) {
                     // let it happen, don't do anything
                     return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (code < 48 || code > 57)) && (code < 96 || code > 105) && (e.ctrlKey === true || code < 65 || code > 90)) {
                e.preventDefault();
            }
        });



        $("body").on('change', '#upgradeOnly', function(e){
            _this.changed_upgradeOnly(e);
        });

        // '[name="enableLevelDown"]:checkbox'
        if( typeof(_this.enableLevelDown$El) !== 'undefined' ){
            $("body").on('change', _this.enableLevelDown$El.selector, function(e){
                _this.changed_enableLevelDown(e);
            });
        }

        // '[name="enableDownMaintain"]:checkbox'
        if( typeof(_this.enableDownMaintain$El) !== 'undefined' ){
            $("body").on('change', _this.enableDownMaintain$El.selector, function(e){
                _this.changed_enableDownMaintain(e);
            });
        }

        $("body").on('click', '#confirm_level_maintain_mode_modal .confirm_btn', function(e){
            _this.clicked_confirm_in_level_maintain_mode_modal(e);
        });

        $("body").on('click', '#confirm_level_downgrade_mode_modal .confirm_btn', function(e){
            _this.clicked_confirm_in_level_downgrade_mode_modal(e);
        });

        $("body").on('click', '#confirm_level_maintain_mode_modal .cancel_btn', function(e){
            _this.clicked_cancel_in_level_maintain_mode_modal(e);
        });

        $("body").on('click', '#confirm_level_downgrade_mode_modal .cancel_btn', function(e){
            _this.clicked_cancel_in_level_downgrade_mode_modal(e);
        });

        $("body").on('shown.bs.modal', '#confirm_level_maintain_mode_modal', function(e){
            _this.shown_confirm_level_maintain_mode_modal(e);
        });

        $("body").on('hidden.bs.modal', '#confirm_level_maintain_mode_modal', function(e){
            _this.hidden_confirm_level_maintain_mode_modal(e);
        });

        $("body").on('shown.bs.modal', '#confirm_level_downgrade_mode_modal', function(e){
            _this.shown_confirm_level_downgrade_mode_modal(e);
        });

        $("body").on('hidden.bs.modal', '#confirm_level_downgrade_mode_modal', function(e){
            _this.hidden_confirm_level_downgrade_mode_modal(e);
        });

    }, // EOF initialize:function(theOptions)

    apply_options :function(theOptions){
        var _this = this;
        _this = $.extend(true, _this, theOptions);
    },

    apply_lang :function(theLANG){
        var _this = this;
        _this.theLANG = $.extend(true, _this.theLANG, theLANG);
    },

    changed_enableLevelDown:function(e){
        var _this = this;
        var is_both_on = _this.detect_enableLevelDown_enableDownMaintain_both_checked();
        if(!_this.allow_both_vip_downgrade_and_maintain_switch_on && is_both_on){
            $('#confirm_level_downgrade_mode_modal').modal('show');
        }
    },

    changed_enableDownMaintain:function(e){
        var _this = this;
        var is_both_on = _this.detect_enableLevelDown_enableDownMaintain_both_checked();
        if(!_this.allow_both_vip_downgrade_and_maintain_switch_on && is_both_on){
            $('#confirm_level_maintain_mode_modal').modal('show');
        }
    },

    /**
     * Detect the onoffswitch checkbox, enableLevelDown and enableLevelDown are both on?
     * @returns If its true, that means those checkbox all had checked.
     */
    detect_enableLevelDown_enableDownMaintain_both_checked:function(){
        var _this = this;
        return _this.enableLevelDown$El.is(':checked')
            && _this.enableDownMaintain$El.is(':checked');
    },

    clicked_confirm_in_level_maintain_mode_modal:function(e){
        var _this = this;
        var target$El = $(e.target);
        target$El.closest('.modal-content').find('[name="isConfirmToDo"]').val(_this.IS_CONFIRM_TO_DO_CONFIRMED);

        _this.onoffswitchToOn(_this.enableDownMaintain$El);
        _this.onoffswitchToOff(_this.enableLevelDown$El);

        target$El.closest('.modal').modal('hide');
    },
    clicked_confirm_in_level_downgrade_mode_modal:function(e){
        var _this = this;
        var target$El = $(e.target);
        target$El.closest('.modal-content').find('[name="isConfirmToDo"]').val(_this.IS_CONFIRM_TO_DO_CONFIRMED);

        _this.onoffswitchToOn(_this.enableLevelDown$El);
        _this.onoffswitchToOff(_this.enableDownMaintain$El);

        target$El.closest('.modal').modal('hide');
    },

    clicked_cancel_in_level_maintain_mode_modal:function(e){
        var _this = this;
        var target$El = $(e.target);
        target$El.closest('.modal-content').find('[name="isConfirmToDo"]').val(_this.IS_CONFIRM_TO_DO_CANCELLED);
        target$El.closest('.modal').modal('hide');
    },

    clicked_cancel_in_level_downgrade_mode_modal:function(e){
        var _this = this;
        var target$El = $(e.target);
        target$El.closest('.modal-content').find('[name="isConfirmToDo"]').val(_this.IS_CONFIRM_TO_DO_CANCELLED);
        target$El.closest('.modal').modal('hide');
    },

    onoffswitchToOn:function(onoffswitch$El){
        if( ! onoffswitch$El.is(':checked') ){ // in off
            onoffswitch$El.trigger('click');
            onoffswitch$El.trigger('change');
        }
    },
    onoffswitchToOff:function(onoffswitch$El){
        if( onoffswitch$El.is(':checked') ){ // in on
            onoffswitch$El.trigger('click');
            onoffswitch$El.trigger('change');
        }
    },

    shown_confirm_level_maintain_mode_modal:function(e){
        var _this = this;
        var target$El = $(e.target);
        // reset
        target$El.find('.modal-content').find('[name="isConfirmToDo"]').val(_this.IS_CONFIRM_TO_DO_WAITING);
    },
    hidden_confirm_level_maintain_mode_modal:function(e){
        var _this = this;
        var target$El = $(e.target);
        var isConfirmToDo = target$El.find('.modal-content').find('[name="isConfirmToDo"]').val();
        if( isConfirmToDo == _this.IS_CONFIRM_TO_DO_CANCELLED
            || isConfirmToDo == _this.IS_CONFIRM_TO_DO_WAITING
        ){
            // revert to level_downgrade_mode
            _this.onoffswitchToOn(_this.enableLevelDown$El);
            _this.onoffswitchToOff(_this.enableDownMaintain$El);
        }
    },

    shown_confirm_level_downgrade_mode_modal:function(e){
        var _this = this;
        var target$El = $(e.target);
        // reset
        target$El.find('.modal-content').find('[name="isConfirmToDo"]').val(_this.IS_CONFIRM_TO_DO_WAITING);
    },
    hidden_confirm_level_downgrade_mode_modal:function(e){
        var _this = this;
        var target$El = $(e.target);
        var isConfirmToDo = target$El.find('.modal-content').find('[name="isConfirmToDo"]').val();
        if( isConfirmToDo == _this.IS_CONFIRM_TO_DO_CANCELLED
            || isConfirmToDo == _this.IS_CONFIRM_TO_DO_WAITING
        ){
            // revert to level_maintain_mode
            _this.onoffswitchToOn(_this.enableDownMaintain$El);
            _this.onoffswitchToOff(_this.enableLevelDown$El);
        }
    },

    changed_upgradeOnly:function(e){
        var _this = this;
        var target$El = $(e.target);

        if(target$El.val() ){
            $('.level-upgrade').find('i').removeClass('fa-square-o').addClass('fa-check-square');
            $('.level-upgrade').find('.upgrade-label').addClass('level_upgrade');
        }
        else {
            $('.level-upgrade').find('i').removeClass('fa-check-square').addClass('fa-square-o');
            $('.level-upgrade').find('.upgrade-label').removeClass('level_upgrade');
        }




    }, // EOF changed_upgradeOnly





    getVIPGroupDetails : function(vipsettingId,currentLang) {
        // console.log('test'+vipsettingId);
        is_editPanelVisible = true;
        $('.add_vip_group_sec').hide();
        $('.edit_vip_group_sec').show();
        $.ajax({
            'url' : base_url + 'player_management/getVIPGroupDetails/' + vipsettingId,
            'type' : 'GET',
            'dataType' : "json",
            'success' : function(data){
                $('#editVipGroupId').val(vipsettingId);
                $('#editGroupLevelCount').val(data[0].groupLevelCount);
                if (data[0].groupName.toLowerCase().indexOf("_json:") >= 0){
                    var langConvert = jQuery.parseJSON(data[0].groupName.substring(6));
                    $('#editGroupNameView').val(langConvert[currentLang]);
                    $('#editGroupName').val(data[0].groupName);
                } else {
                    $('#editGroupNameView').val(data[0].groupName);
                    $('#editGroupName').val(data[0].groupName);
                }
                $('#editGroupDescription').val(data[0].groupDescription);
                $('#can_be_self_join_in').prop('checked', data[0].can_be_self_join_in == '1');
                $('#editImage').val(data[0].image);
            }
        },'json');
        return false;
    },
}; // EOF PlayerManagementProcess

$(document).ready(function() {
    var offset = 200;
    var duration = 500;
    jQuery(window).scroll(function() {
        if (jQuery(this).scrollTop() > offset) {
            jQuery('.custom-scroll-top').fadeIn(duration);
        } else {
            jQuery('.custom-scroll-top').fadeOut(duration);
        }
    });

    $('.custom-scroll-top').on('click', function(event) {
        event.preventDefault();
        $('html, body').animate({scrollTop:0}, 'slow');
    });

    //modal Tags
    $("#tags").change(function() {
        $("#tags option:selected").each(function() {
            if ($(this).attr("value") == "Others") {
                $("#specify").show();
            } else {
                $("#specify").hide();
            }
        });
    }).change();
    //end modal Tags

});

function checkAll(id) {
    var list = document.getElementsByClassName(id);
    var all = document.getElementById(id);

    if (all.checked) {
        for (i = 0; i < list.length; i++) {
            list[i].checked = 1;
        }
    } else {
        all.checked;

        for (i = 0; i < list.length; i++) {
            list[i].checked = 0;
        }
    }
}

function uncheckAll(id) {
    var list = document.getElementById(id).className;
    var all = document.getElementById(list);

    var item = document.getElementById(id);
    var allitems = document.getElementsByClassName(list);
    var cnt = 0;

    if (item.checked) {
        for (i = 0; i < allitems.length; i++) {
            if (allitems[i].checked) {
                cnt++;
            }
        }

        if (cnt == allitems.length) {
            all.checked = 1;
        }
    } else {
        all.checked = 0;
    }
}