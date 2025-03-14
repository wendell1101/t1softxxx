var VIPSETTING_SYNC = VIPSETTING_SYNC || {};
VIPSETTING_SYNC.init = function(options){
    var _this = this;
    _this.detect_console_log();
    _this.safelog('load VIPSETTING_SYNC');
    _this.WARNING_CASE_LIST = {};
    _this.WARNING_CASE_LIST.ADJUST_PLAYERLEVEL = 'adjust_playerlevel';
    _this.WARNING_CASE_LIST.UPDATE_VIP_GROUP = 'update_vip_group';
    _this.WARNING_CASE_LIST.UPDATE_VIP_LEVEL = 'update_vip_level';

    _this.Event_CASE_LIST = {};
    _this.Event_CASE_LIST.ADJUSTPLAYERLEVEL = 'adjustPlayerLevel';
    _this.Event_CASE_LIST.INCREASEVIPGROUPLEVEL = 'increaseVipGroupLevel';
    _this.Event_CASE_LIST.NEWVIPGROUP = 'newVipGroup';
    _this.Event_CASE_LIST.OVERRIDEVIPGROUP = 'overrideVipGroup';
    _this.Event_CASE_LIST.OVERRIDEVIPLEVEL = 'overrideVipLevel';
    _this.Event_CASE_LIST.MOVEVIPLEVEL = 'moveVipLevel';
    _this.Event_CASE_LIST.REFRESHLEVELCOUNTOFVIPGROUP = 'refreshLevelCountOfVipGroup';
    _this.Event_CASE_LIST.SOFTDELETEVIPLEVEL = 'softDeleteVipLevel';


    _this.options = {};

    _this.options.urls = {};
    _this.options.urls.check_vip_group_before_sync = "/vipsetting_management/check_vip_group_before_sync/{$vipSettingId}/{$dryRun}/all/2";
    _this.options.urls.sync_vip_group = "/vipsetting_management/sync_vip_group/{$vipSettingId}/{$dryRunMode}";
    _this.options.urls.getPlayerIdsInHighestLevelByVipSettingId = "/vipsetting_management/getPlayerIdsInHighestLevelByVipSettingId/{$vipSettingId}";

    _this.options = $.extend(true, {}, _this.options, options);

    return _this;
} // EOF init()

VIPSETTING_SYNC.onReady = function(){
    var _this = this;

    _this.initEvents();
} // EOF onReady()

VIPSETTING_SYNC.onReadyInView = function(view_filename){
    var _this = this;

    _this.initEvents4Common();
    switch(view_filename){
        default:
            break;

        case 'view_vip_setting_list':
            _this.initEvents4view_vip_setting_list();
            break;

        case 'view_vip_setting_rules':
            _this.initEvents4view_vip_setting_rules();
            break;
        case 'view_other_functions':
            _this.initEvents4view_other_functions();
            break;
    }
} // EOF onReadyInView()


VIPSETTING_SYNC.assignLangList2Options = function (theLangList) {
    var _this = this;
    _this.options.lang = $.extend(true, _this.options.lang, theLangList);
}

VIPSETTING_SYNC.initEvents4somethingWrongModal = function(){
    var _this = this;

    $('#somethingWrongModal')
        .on('show.bs.modal', function (e) {
            _this.show_somethingWrongModal(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_somethingWrongModal(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_somethingWrongModal(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_somethingWrongModal(e);
        });

    $('body').on('click', '.btn_dismiss', function(e){
        _this.clicked_btn_dismiss(e);
    });
}
VIPSETTING_SYNC.show_somethingWrongModal = function (e) {
    var _this = this;
    $('#somethingWrongModalBody').html(_this.options.lang['con.pym01']);
};
VIPSETTING_SYNC.shown_somethingWrongModal = function (e) {

};
VIPSETTING_SYNC.hide_somethingWrongModal = function (e) {
    location.reload();
};
VIPSETTING_SYNC.hidden_somethingWrongModal = function (e) {

};

VIPSETTING_SYNC.initEvents4Common = function(){
    var _this = this;

    _this.initEvents4somethingWrongModal();

    $('#checkVipGroupLevelsInEOCDModal')
        .on('show.bs.modal', function (e) {
            _this.show_checkVipGroupLevelsInEOCD(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_checkVipGroupLevelsInEOCD(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_checkVipGroupLevelsInEOCD(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_checkVipGroupLevelsInEOCD(e);
        });

    $('body').on('click', '#checkVipGroupLevelsInEOCDModal .btn_continue_in_ng', function(e){
        _this.clicked_btn_continue_in_ng(e);
    });
    $('body').on('click', '#checkVipGroupLevelsInEOCDModal .btn_continue_in_ok', function(e){
        _this.clicked_btn_continue_in_ok(e);
    });
    $('body').on('click', '#checkVipGroupLevelsInEOCDModal .btn_dismiss', function(e){
        _this.clicked_btn_dismiss(e);
    });
    $('body').on('click', '#checkVipGroupLevelsInEOCDModal .btn_close', function(e){
        _this.clicked_btn_close(e);
    });
    $('body').on('click', '#checkVipGroupLevelsInEOCDModal .btn_refresh', function(e){
        _this.clicked_btn_refresh(e);
    });

    $('#promptVipGroupLevelHasPlayersModal')
        .on('show.bs.modal', function (e) {
            _this.show_promptVipGroupLevelHasPlayersModal(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_promptVipGroupLevelHasPlayersModal(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_promptVipGroupLevelHasPlayersModal(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_promptVipGroupLevelHasPlayersModal(e);
        });
    $('body').on('click', '#promptVipGroupLevelHasPlayersModal .btn_next', function(e){
        _this.clicked_btn_next4promptVipGroupLevelHasPlayersModal(e);
    });
    $('body').on('click', '#promptVipGroupLevelHasPlayersModal .btn_close', function(e){
        _this.clicked_btn_close4promptVipGroupLevelHasPlayersModal(e);
    });

} // EOF initEvents4Common
VIPSETTING_SYNC.initEvents4view_vip_setting_list = function(){
    var _this = this;

    // TODO, remove
    // $('body').on('click', '.btn_vip_group', function(e){
    //     _this.clicked_btn_vip_group(e);
    // });

    $('body').on('click', '.btn_add_vip_group', function(e){
        // _this.clicked_btn_incgrplvlcnt(e);
        _this.clicked_btn_add_vip_group(e);
    });



}
VIPSETTING_SYNC.initEvents4view_vip_setting_rules = function(){
    var _this = this;

    $('body').on('click', '.btn_incgrplvlcnt', function(e){
        _this.clicked_btn_incgrplvlcnt(e);
    });
    //
    $('body').on('click', '.btn_decgrplvlcnt', function(e){
        _this.clicked_btn_decgrplvlcnt(e);
    });

    $('body').on('click', '.btn_decgrplvlcnt_prompt_exists_players', function(e){
        _this.clicked_btn_decgrplvlcnt_prompt_exists_players(e);
    });

}// EOF initEvents4view_vip_setting_rules
/// for admin/application/views/system_management/view_other_functions.php
VIPSETTING_SYNC.initEvents4view_other_functions = function(){
    var _this = this;

    $('body').on('click', '.btn_preview_in_vipsettingid', function(e){

        _this.clicked_btn_btn_preview_in_vipsettingid(e);
    });
} // EOF initEvents4view_other_functions

VIPSETTING_SYNC.initEvents = function(){
    var _this = this;

    _this.initEvents4Common();

    $('body').on('click', '.btn_vip_group', function(e){
        _this.clicked_btn_vip_group(e);
    });

} // EOF initEvents()

VIPSETTING_SYNC.clicked_btn_continue_in_ng = function(e){
    var _this = this;
    var target$El = $(e.target);
    var _vipSettingId = target$El.closest('div').find('input.vipsettingid').val();
    var _dryRunMode = target$El.closest('div').find('input.dryrun4continue').val();
    var _data = target$El.closest('div').find('input._data').val();

    _this._do_sync_vip_group(_vipSettingId, _dryRunMode, _data);
}
VIPSETTING_SYNC.clicked_btn_continue_in_ok = function(e){
    var _this = this;
    var target$El = $(e.target);
    var _vipSettingId = target$El.closest('div').find('input.vipsettingid').val();
    var _dryRunMode = target$El.closest('div').find('input.dryrun4continue').val();
    var _data = target$El.closest('div').find('input._data').val();

    _this._do_sync_vip_group(_vipSettingId, _dryRunMode, _data);
}
VIPSETTING_SYNC.clicked_btn_dismiss = function(e){
    var _this = this;
    var target$El = $(e.target);
    $('#checkVipGroupLevelsInEOCDModal').modal('hide');
}
VIPSETTING_SYNC.clicked_btn_close = function(e){
    var _this = this;
    var target$El = $(e.target);
    $('#checkVipGroupLevelsInEOCDModal').modal('hide');
}
VIPSETTING_SYNC.clicked_btn_refresh = function(e){
    var _this = this;
    window.location.reload(true);
}

VIPSETTING_SYNC.clicked_btn_next4promptVipGroupLevelHasPlayersModal = function(e){
    var _this = this;
    var target$El = $(e.target);
    window.location.href = target$El.data('next_uri');
    $('#promptVipGroupLevelHasPlayersModal').modal('hide');
}
VIPSETTING_SYNC.clicked_btn_close4promptVipGroupLevelHasPlayersModal = function(e){
    var _this = this;
    var target$El = $(e.target);
    $('#promptVipGroupLevelHasPlayersModal').modal('hide');
}

VIPSETTING_SYNC._do_sync_vip_group = function(_vipSettingId, _dryRunMode, _data = '{}'){
    var _this = this;
    var postData = {};
    postData.vipSettingId = _vipSettingId;
    postData.dryRunMode = _dryRunMode;
    postData.data = _data;
    var theUri = _this.options.urls.sync_vip_group;
    var method_type = 'POST';
    var _ajax = _this._do_Uri(function(jqXHR, settings){ // beforeSendCB
        $('#checkVipGroupLevelsInEOCDModal').find('.loading').show();
    }, function(jqXHR, textStatus){ // completeCB
        $('#checkVipGroupLevelsInEOCDModal').find('.loading').hide();
    }, theUri, postData, method_type);

    _ajax.done(function (data, textStatus, jqXHR) {
        _this.do_render_syncVipGroupLevelsInEOCD(data);
    });
}
VIPSETTING_SYNC.do_render_syncVipGroupLevelsInEOCD = function(data){
    var _this = this;

    _this.safelog('do_render_syncVipGroupLevelsInEOCD.101.data:', data);

    if( ! $.isEmptyObject(data.result) ){
        var _selectorStr ='.report_result';
        $( '<div>'+ data.message+ '</div>' ).appendTo( _selectorStr );
    }
    _this.do_render4disabled_button('.btn_continue_in_ng,.btn_continue_in_ok');

    var _triggerFrom = $('#checkVipGroupLevelsInEOCDModal').find('input.triggerFrom').val();
    switch(_triggerFrom){
        case 'btn_decgrplvlcnt':
        case 'btn_incgrplvlcnt':
        case 'btn_add_vip_group':
            $('.btn_refresh').removeClass('hide');
            _this.do_render4disabled_button('.btn_refresh', true);
            break;
    }
}// EOF do_render_syncVipGroupLevelsInEOCD

VIPSETTING_SYNC.do_render4disabled_button = function(selectorStr, do_enable = false){
    if(do_enable){
        // to enable
        $( selectorStr ).attr('disabled', '').prop("disabled", false);
    }else{
        // to disable
        $( selectorStr ).attr('disabled', 'disabled').prop("disabled", true);
    }

}



VIPSETTING_SYNC.clicked_btn_vip_group = function(e){
    var _this = this;
    var target$El = $(e.target);
    if( typeof(target$El.data('vipsettingid') ) === 'undefined'){
        target$El = $(e.target).closest('[data-vipsettingid]');
    }

    var _vipsettingid = target$El.data('vipsettingid');

    $('#checkVipGroupLevelsInEOCDModal').find('input.vipsettingid').val(_vipsettingid);
    $('#checkVipGroupLevelsInEOCDModal').modal('show');

} // EOF clicked_btn_vip_group(e)


VIPSETTING_SYNC.clicked_btn_add_vip_group = function(e){
    var _this = this;
    var target$El = $(e.target);
    var _vipsettingid = 0;
    // ready to sync with the below options
    $('#checkVipGroupLevelsInEOCDModal').find('input.vipsettingid').val(_vipsettingid);
    $('#checkVipGroupLevelsInEOCDModal').find('input.dryrun').val(_this.options.DRY_RUN_MODE_LIST.IN_ADD_GROUP);
    $('#checkVipGroupLevelsInEOCDModal').find('input.dryrun4continue').val(_this.options.DRY_RUN_MODE_LIST.IN_DISABLED_ADD_GROUP);
    $('#checkVipGroupLevelsInEOCDModal').find('input.triggerFrom').val('btn_add_vip_group');

    var _groupLevelCount = $('input[id="groupLevelCount"]').val();
    var _groupDescription = $('input[id="groupDescription"]').val();
    var _groupName = $('input[name="groupName"][id="groupName"]').val();
    var _createdBy = 0; // handle in check_vip_group_before_sync() and sync_vip_group()
    var _createdOn = 0; // handle in check_vip_group_before_sync() and sync_vip_group()
    var _can_be_self_join_in = 0;
    if( $('#add_vip_group_form [name="can_be_self_join_in"]:checked').length > 0 ){
        _can_be_self_join_in = 1;
    }
    var _jsonWrapper = {};
    _jsonWrapper.groupLevelCount = _groupLevelCount;
    _jsonWrapper.groupDescription = _groupDescription;
    _jsonWrapper.groupName = _groupName;
    _jsonWrapper.createdBy = _createdBy;
    _jsonWrapper.createdOn = _createdOn;
    _jsonWrapper.can_be_self_join_in = _can_be_self_join_in;

    var jsonPretty = JSON.stringify(_jsonWrapper);
    // $("#groupName").val(jsonPretty);
    // console.log('jsonPretty:',jsonPretty);


    var errors = 0;
    $("#groupNameView, #groupLevelCount ,#groupDescription").map(function(){
        if( !$(this).val() ) {
            $(this).parents('.i_required').addClass('has-error');
            errors++;
        } else if ($(this).val()) {
            $(this).parents('.i_required').removeClass('has-error');
        }
    });
    if($('#groupLevelCount').val() < 1){
        $('#groupLevelCount').parents('.i_required').addClass('has-error');
        errors++;
    }

    if(errors == 0){
        $('#checkVipGroupLevelsInEOCDModal').find('input._data').val(jsonPretty);
        $('#checkVipGroupLevelsInEOCDModal').modal('show'); // show_checkVipGroupLevelsInEOCD
    }
}
VIPSETTING_SYNC.clicked_btn_incgrplvlcnt = function(e){
    var _this = this;
    var target$El = $(e.target);
    if( typeof(target$El.data('vipsettingid') ) === 'undefined'){
        target$El = $(e.target).closest('[data-vipsettingid]');
    }

    var _vipsettingid = target$El.data('vipsettingid');

    // ready to sync with the below options
    $('#checkVipGroupLevelsInEOCDModal').find('input.vipsettingid').val(_vipsettingid);
    $('#checkVipGroupLevelsInEOCDModal').find('input.dryrun').val(_this.options.DRY_RUN_MODE_LIST.IN_INCREASED_LEVELS);
    $('#checkVipGroupLevelsInEOCDModal').find('input.dryrun4continue').val(_this.options.DRY_RUN_MODE_LIST.IN_DISABLED_INCREASED_LEVELS);
    $('#checkVipGroupLevelsInEOCDModal').find('input.triggerFrom').val('btn_incgrplvlcnt');

    $('#checkVipGroupLevelsInEOCDModal').modal('show');
} // EOF clicked_btn_incgrplvlcnt(e)

VIPSETTING_SYNC.clicked_btn_decgrplvlcnt = function(e){
    var _this = this;
    var target$El = $(e.target);
    if( typeof(target$El.data('vipsettingid') ) === 'undefined'){
        target$El = $(e.target).closest('[data-vipsettingid]');
    }

    var _vipsettingid = target$El.data('vipsettingid');
    var _ajax = _this.do_getPlayerIdsInHighestLevelByVipSettingId(_vipsettingid);
    _ajax.done(function (data, textStatus, jqXHR) {
        var _player_list = [];
        if( 'success' in data){
            if(data.success == true && 'result' in data){
                _player_list = data.result;
            }
        }
        var _player_amount = 0;
        if(_player_list.length > 0){
            _player_amount = _player_list.length;
        }
        if(_player_amount > 0){
            // tips, The player(s) had exist in the level.
            var _code = _this.options.CODE_DECREASEVIPGROUPLEVEL_IN_LEVEL_EXIST_PLAYER;
            var _message = '';
            if(_player_amount == 1){
                _message = _this.options.lang.playerInLevel;
            }else if(_player_amount > 1){
                _message = _this.options.lang.playerInLevelWithPluralNumber;
                _message = _message.replace(/%s/g, _player_amount);
            }

            $('#promptVipGroupLevelHasPlayersModal').modal('show');
            _this.do_renderByCode_promptVipGroupLevelHasPlayersModal(_code);
            $('#promptVipGroupLevelHasPlayersModal').find('.msg4promptVipGroupLevelHasPlayersModal').html(_message);
        }else{
            // directly show sync preview modal popup
            _this.showModal_checkVipGroupLevelsInEOCDModal(_vipsettingid);
        }
    });
} // EOF clicked_btn_decgrplvlcnt(e)
//
VIPSETTING_SYNC.do_getPlayerIdsInHighestLevelByVipSettingId = function(_vipSettingId){
    var _this = this;
    var postData = {};
    postData.vipSettingId = _vipSettingId;
    // postData.dryRunMode = _dryRunMode;
    // postData.data = _data;
    var theUri = _this.options.urls.getPlayerIdsInHighestLevelByVipSettingId;// sync_vip_group;
    var method_type = 'GET';
    var _ajax = _this._do_Uri(function(jqXHR, settings){ // beforeSendCB
        $('.btn_decgrplvlcnt').button('loading')
        // $('#promptVipGroupLevelOfMDBHasHasPlayersModal').find('.loading').show();
    }, function(jqXHR, textStatus){ // completeCB
        $('.btn_decgrplvlcnt').button('reset')
        // $('#promptVipGroupLevelOfMDBHasHasPlayersModal').find('.loading').hide();
    }, theUri, postData, method_type);

    _ajax.done(function (data, textStatus, jqXHR) {
        // console.log('427.data:', data);
    });
    return _ajax;
} // EOF do_getPlayerIdsInHighestLevelByVipSettingId()
//
VIPSETTING_SYNC.showModal_checkVipGroupLevelsInEOCDModal = function(_vipsettingid){
    var _this = this;

    // ready to sync with the below options
    $('#checkVipGroupLevelsInEOCDModal').find('input.vipsettingid').val(_vipsettingid);
    $('#checkVipGroupLevelsInEOCDModal').find('input.dryrun').val(_this.options.DRY_RUN_MODE_LIST.IN_DECREASED_LEVELS);
    $('#checkVipGroupLevelsInEOCDModal').find('input.dryrun4continue').val(_this.options.DRY_RUN_MODE_LIST.IN_DISABLED_DECREASED_LEVELS);
    $('#checkVipGroupLevelsInEOCDModal').find('input.triggerFrom').val('btn_decgrplvlcnt');
    $('#checkVipGroupLevelsInEOCDModal').modal('show');
} // EOF showModal_checkVipGroupLevelsInEOCDModal()


VIPSETTING_SYNC.clicked_btn_decgrplvlcnt_prompt_exists_players = function(e){
    var _this = this;
    var target$El = $(e.target);
    $('#promptVipGroupLevelHasPlayersModal').find('input.ajax-uri').val( target$El.data('href') );
    $('#promptVipGroupLevelHasPlayersModal').modal('show');
}

VIPSETTING_SYNC.clicked_btn_btn_preview_in_vipsettingid = function(e){
    var _this = this;
    var target$El = $(e.target);

    var _vipsettingid = target$El.closest('.row').find('select[name="vipsettingid"]').val();

    if(_vipsettingid > 0){// not NONE
        $('#checkVipGroupLevelsInEOCDModal').find('input.vipsettingid').val(_vipsettingid);
        $('#checkVipGroupLevelsInEOCDModal').find('input.dryrun').val(_this.options.DRY_RUN_MODE_LIST.IN_NORMAL);
        $('#checkVipGroupLevelsInEOCDModal').find('input.dryrun4continue').val(_this.options.DRY_RUN_MODE_LIST.IN_DISABLED);
        $('#checkVipGroupLevelsInEOCDModal').find('input.triggerFrom').val('btn_preview_in_vipsettingid');
        $('#checkVipGroupLevelsInEOCDModal').modal('show');
    }else{
        var alertStr = '';
        alertStr += _this.options.lang['inVipGroupLevels'];
        alertStr += "\r\n";
        alertStr += _this.options.lang['con.vsm12'];

        alert(alertStr);
    }
}


VIPSETTING_SYNC.getOuterHtmlAndReplaceAll = function (outerHtml = '', regexList = []) {
    var _outerHtml = '';
    _outerHtml = outerHtml;
    if (regexList.length > 0) {
        regexList.forEach(function (currRegex, indexNumber) {
            // assign playerpromo_id into the tpl
            var regex = currRegex['regex']; // var regex = /\$\{playerpromo_id\}/gi;
            _outerHtml = _outerHtml.replaceAll(regex, currRegex['replaceTo']);// currVal.playerpromo_id);
        });
    }
    return _outerHtml;
}
VIPSETTING_SYNC.getTplHtmlWithOuterHtmlAndReplaceAll = function (selectorStr, regexList) {

    var _outerHtml = '';
    if (typeof (selectorStr) !== 'undefined') {
        _outerHtml = $(selectorStr).html();
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



VIPSETTING_SYNC.extractData4call_method_listOfDryRun = function(data){
    var _this = this;

    var extracted = {};

    var dryRunResults = {};
    if('result' in data && ! $.isEmptyObject(data.result) ){
        if('__dryRun' in data.result && ! $.isEmptyObject(data.result.__dryRun) ){
            dryRunResults = data.result.__dryRun;
        }
    }

    Object.keys(dryRunResults).forEach( function (_currency_key) {
        // dryRunResults[_currency_key].result.dryRun.call_method_list
        if('result' in dryRunResults[_currency_key]
            && ! $.isEmptyObject(dryRunResults[_currency_key].result)
        ){
            if('dryRun' in dryRunResults[_currency_key].result
                && ! $.isEmptyObject(dryRunResults[_currency_key].result.dryRun)
            ){
                if('call_method_list' in dryRunResults[_currency_key].result.dryRun
                    && typeof(dryRunResults[_currency_key].result.dryRun.call_method_list) !== 'undefined'
                ){
                    extracted[_currency_key] = dryRunResults[_currency_key].result.dryRun.call_method_list;
                }
            }
        }
    }); // EOF  Object.keys(dryRunResults).forEach(...
    return extracted;
}

VIPSETTING_SYNC.getEventListFromCallMethodList = function(call_method_list){
    var _this = this;

    var event_list = {};
    Object.keys(call_method_list).forEach( function (_currency_key) {
        var _list = _this.getEventListFromCallMethodListInCurrency(call_method_list[_currency_key])
        if( ! $.isEmptyObject(_list) ){
            event_list[_currency_key] = _list;
        }
    }); // EOF  Object.keys(call_method_list).forEach(...

    var filted_event_list = {};
    Object.keys(event_list).forEach( function (_currency_key) {
        var filted_eventList = _this.filterDuplicatesInEventList(event_list[_currency_key]);
        filted_event_list[_currency_key] = filted_eventList;
    }); // EOF  Object.keys(event_list).forEach(...

    return filted_event_list;
}
VIPSETTING_SYNC.getEventListFromCallMethodListInCurrency = function(call_method_list_of_currency){
    var _this = this;
    var eventList = [];
    call_method_list_of_currency.forEach(function(call_method, indexNumber, _list){
        var _event = _this.getEventFromCallMethod(call_method);
        if( ! $.isEmptyObject(_event) ){
            eventList.push(_event);
        }
    });
    return eventList;
}

VIPSETTING_SYNC.getEventFromCallMethod = function(call_method){
    var _this = this;
    var _event = {};
    if( 'event' in call_method
        && typeof( call_method.event ) !== 'undefined'
        && ! $.isEmptyObject(call_method.event)
    ){
        _event = call_method.event;
    }
    return _event;
}
VIPSETTING_SYNC.filterDuplicatesInEventList = function(eventList){
    var _this = this;

    var _rltList = [];
    eventList.forEach(function (_event, indexNumber) { // the source event list
        var _filtered_list = _rltList.filter(function(_event_rlt, _indexNumber_rlt, _list){
            var is_met = true;
            is_met = is_met && _event_rlt['token'] == _event['token'];
            return is_met;
        });
        var is_exists = null; // default
        if( $.isEmptyObject(_filtered_list) ){
            is_exists = false;
        }else{
            // ignore for only one
            is_exists = true;
        }
        if(!is_exists){
            _rltList.push(_event);
        }
    });

    return _rltList;
}



// EOCD aka. Each Other Currency Database
VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD = function(data){
    var _this = this;
    _this.safelog('do_render_checkVipGroupLevelsInEOCD.89.data:', data);

    var result_in_others = [];
    if( typeof(data.result) !== 'undefined'){
        $.each( data.result, function( indexStr, currEle ){
            if( indexStr.indexOf('__') !== -1){
                // ignore for source and dryrun related
            }else{
                result_in_others[indexStr] = currEle;
            }
        });
    }

    _this.safelog('do_render_checkVipGroupLevelsInEOCD.102.result_in_others:', result_in_others);

    var __sourceDB = undefined;
    if(!$.isEmptyObject(data.result) && !$.isEmptyObject(data.result.__sourceDB)){
        __sourceDB = data.result.__sourceDB;
    }
    var __source = undefined;
    if(!$.isEmptyObject(data.result) && !$.isEmptyObject(data.result.__source)){
        __source = data.result.__source;
    }

    // _this.do_render4disabled_button(_selectorStr, true;
    // $("#checkVipGroupLevelsInEOCDModal .report_intro_details").html(''); // reset

    _this.do_render_checkVipGroupLevelsInEOCD4Currency(result_in_others, __sourceDB, __source);

    _this.do_render_checkVipGroupLevelsInEOCD4Group(result_in_others, __sourceDB, __source);

    _this.do_render_checkVipGroupLevelsInEOCD4Levels(result_in_others, __sourceDB, __source);

    // var _call_method_list = _this.extractData4call_method_listOfDryRun(data);
    // _this.safelog('do_render_checkVipGroupLevelsInEOCD.357._call_method_list:', _call_method_list);

    _this.do_render_checkVipGroupLevelsInEOCD4Warnings(data);

    _this.do_render_checkVipGroupLevelsInEOCD4ResultIntro();

} // EOF do_render_checkVipGroupLevelsInEOCD

VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4Currency = function(result_in_others, __sourceDB, __source){
    var _this = this;
    // _this.safelog('do_render_checkVipGroupLevelsInEOCD.118.result_in_others:', result_in_others);

    var outerHtml_in_report_intro_details_list = [];

    Object.keys(result_in_others).forEach( function (indexStr) {
        // var currEle = result_in_others[key];
        var regexList = [];
        var nIndex = -1;

        nIndex++; // # 0
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{currency_key\}/gi; // ${currency_key};
        regexList[nIndex]['replaceTo'] = indexStr;

        var outerHtml = _this.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-currency_database', regexList);
        outerHtml_in_report_intro_details_list.push(outerHtml);
    }); // EOF  Object.keys(result_in_others).forEach(...

    var outerHtml_in_report_intro_details = outerHtml_in_report_intro_details_list.join('<!-- hr -->');
    // _this.safelog('do_render_checkVipGroupLevelsInEOCD.134.outerHtml_in_report_intro_details:', outerHtml_in_report_intro_details);
    // _this.safelog('do_render_checkVipGroupLevelsInEOCD.135.outerHtml_in_report_intro_details_list:', outerHtml_in_report_intro_details_list);

    // $('#checkVipGroupLevelsInEOCDModal').find('.report_intro_details').replaceWith( outerHtml_in_report_intro_details );
    $(outerHtml_in_report_intro_details ).appendTo( "#checkVipGroupLevelsInEOCDModal .report_intro_details" );

} // EOF do_render_checkVipGroupLevelsInEOCD4Currency

VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4Group = function(result_in_others, __sourceDB, __source){
    var _this = this;
    _this.safelog('do_render_checkVipGroupLevelsInEOCD4Group.475.result_in_others:', result_in_others);
    _this.safelog('do_render_checkVipGroupLevelsInEOCD4Group.476.__sourceDB:', __sourceDB);
    _this.safelog('do_render_checkVipGroupLevelsInEOCD4Group.477.__source:', __source);
    Object.keys(result_in_others).forEach( function (indexStr) {
        var currEle = result_in_others[indexStr];
        if( typeof(currEle.result) !== 'undefined'
            && 'vipsetting' in currEle.result
        ){
            var _vipsetting = currEle.result.vipsetting;
            // _this.safelog('do_render_checkVipGroupLevelsInEOCD4Group.151.indexStr:', indexStr, '_vipsetting:', _vipsetting);
            _this.do_render_checkVipGroupLevelsInEOCD4OneGroup(_vipsetting, '.currency_database[data-currency_key="'+ indexStr+ '"]');
        }else{
            /// disable for new VIP group UI issue.
            // _this.do_render_checkVipGroupLevelsInEOCD4EmptyGroup(__source, '.currency_database[data-currency_key="'+ indexStr+ '"]');
        }
    }); // EOF  Object.keys(result_in_others).forEach(...

} // EOF do_render_checkVipGroupLevelsInEOCD4Group

VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4EmptyGroup = function(__source, appendToSelectorStr){
    var _this = this;

    var regexList = [];
    // var nIndex = -1;
    // //
    // nIndex++; // # 0
    // regexList[nIndex] = {};
    // regexList[nIndex]['regex'] = /\$\{vipSettingId\}/gi; // ${vipSettingId};
    // regexList[nIndex]['replaceTo'] = _vipsetting.vipSettingId;

    var outerHtml = _this.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-empty_group_container', regexList);

    if( typeof(appendToSelectorStr) !== 'undefined' ){
        $( outerHtml ).appendTo( appendToSelectorStr );
    }

    return outerHtml;
}

VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4OneGroupLite = function(_vipSettingId, _groupName, appendToSelectorStr){
    var _this = this;

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{group_name\}/gi; // ${group_name};
    regexList[nIndex]['replaceTo'] = _groupName;

    nIndex++; // # 1
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{vipSettingId\}/gi; // ${vipSettingId};
    regexList[nIndex]['replaceTo'] = _vipSettingId;

    nIndex++; // # 2
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{group_result_intro\}/gi; // ${group_result_intro};
    regexList[nIndex]['replaceTo'] = ''; // TODO, wait form ajax / Warnings

    var outerHtml = _this.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-group_levels_container', regexList);

    if( typeof(appendToSelectorStr) !== 'undefined' ){
        $( outerHtml ).appendTo( appendToSelectorStr );
    }

    return outerHtml;
}
VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4OneGroup = function(_vipsetting, appendToSelectorStr){
    var _this = this;

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{group_name\}/gi; // ${group_name};
    if( typeof(_vipsetting.__lang_groupName) !== 'undefined' ){
        regexList[nIndex]['replaceTo'] = _vipsetting.__lang_groupName;
    }else{
        regexList[nIndex]['replaceTo'] = _vipsetting.groupName;
    }

    nIndex++; // # 1
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{vipSettingId\}/gi; // ${vipSettingId};
    regexList[nIndex]['replaceTo'] = _vipsetting.vipSettingId;

    nIndex++; // # 2
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{group_result_intro\}/gi; // ${group_result_intro};
    regexList[nIndex]['replaceTo'] = ''; // TODO, wait form ajax / Warnings

    var outerHtml = _this.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-group_levels_container', regexList);

    if( typeof(appendToSelectorStr) !== 'undefined' ){
        $( outerHtml ).appendTo( appendToSelectorStr );
    }

    return outerHtml;

} // EOF do_render_checkVipGroupLevelsInEOCD4OneGroup

VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4Levels = function(result_in_others, __sourceDB, __source){
    var _this = this;

    // for tpl-level_list_row
    Object.keys(result_in_others).forEach( function (indexStr) {
        var currEle = result_in_others[indexStr]; // indexStr : others currency

        var _vipsettingcashbackrule_list = [];

        if( typeof(currEle.result) !== 'undefined'
            && 'vipsettingcashbackrule' in currEle.result
        ){
            _vipsettingcashbackrule_list = currEle.result.vipsettingcashbackrule;
            // continue
        }else{
            /// disabled by default in _vipsettingcashbackrule_list
            // return false; // break;
        }

        var _vipSettingId = _this.safeGetVipSettingIdFromSource(__source);

        var _appendToSelectorStr = '.currency_database[data-currency_key="'+ indexStr+ '"]'
                                    + ' '
                                    + '.group_levels_container[data-vipsettingid="'+ _vipSettingId+ '"]'
                                    + ' '
                                    + '.level_list_container';

        if(_vipsettingcashbackrule_list.length > 0 ){
            $.each( _vipsettingcashbackrule_list, function( indexNum, _vipsettingcashbackrule ){

                _vipsettingcashbackrule.deleted_in_other = _vipsettingcashbackrule.deleted;
                _this.safelog('713._vipsettingcashbackrule:', _vipsettingcashbackrule, 'will do_render_checkVipGroupLevelsInEOCD4OneLevel');
                _this.do_render_checkVipGroupLevelsInEOCD4OneLevel(_vipsettingcashbackrule, _appendToSelectorStr );
            }); // EOF $.each( _vipsettingcashbackrule_list, function(...
        }else{
            // empty level
            _this.do_render_checkVipGroupLevelsInEOCD4EmptyLevel(__source, _appendToSelectorStr );
        }
    }); // EOF  Object.keys(result_in_others).forEach(...

} // EOF do_render_checkVipGroupLevelsInEOCD4Levels

VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4EmptyLevel = function(__source, appendToSelectorStr){
    var _this = this;

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{vipSettingId\}/gi; // ${vipSettingId};
    var _vipSettingId = _this.safeGetVipSettingIdFromSource(__source);
    regexList[nIndex]['replaceTo'] = _vipSettingId;

    var outerHtml = _this.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-empty_level_row', regexList);

    if( typeof(appendToSelectorStr) !== 'undefined' ){
        $( outerHtml ).appendTo( appendToSelectorStr );
    }

    return outerHtml;
}

VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4OneLevel = function(_vipsettingcashbackrule, appendToSelectorStr){
    var _this = this;

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{vipLevelName\}/gi; // ${vipLevelName};
    if( typeof(_vipsettingcashbackrule.__lang_vipLevelName) !== 'undefined' ){
        regexList[nIndex]['replaceTo'] = _vipsettingcashbackrule.__lang_vipLevelName;
    }else{
        regexList[nIndex]['replaceTo'] = _vipsettingcashbackrule.vipLevelName;
    }

    nIndex++; // # 1
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{vipsettingcashbackruleId\}/gi; // ${vipsettingcashbackruleId};
    regexList[nIndex]['replaceTo'] = _vipsettingcashbackrule.vipsettingcashbackruleId;

    nIndex++; // # 2
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{vipSettingId\}/gi; // ${vipSettingId};
    regexList[nIndex]['replaceTo'] = _vipsettingcashbackrule.vipSettingId;

    var _replaceTo = '_undefined';
    if( typeof(_vipsettingcashbackrule.deleted) !== 'undefined'){
        _replaceTo = _vipsettingcashbackrule.deleted;
    }
    nIndex++; // # 3
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{deleted\}/gi; // ${deleted};
    regexList[nIndex]['replaceTo'] = _replaceTo;

    _this.safelog('781._vipsettingcashbackrule.caseStr:', _vipsettingcashbackrule.caseStr);
    var _replaceTo = '_undefined';
    if( typeof(_vipsettingcashbackrule.caseStr) !== 'undefined'){
        // from renderVipLevelDivByToken()
        _replaceTo = _vipsettingcashbackrule.caseStr;
    }
    nIndex++; // # 4
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{caseStr\}/gi; // ${caseStr};
    regexList[nIndex]['replaceTo'] = _replaceTo;

    var _replaceTo = '_undefined';
    if( typeof(_vipsettingcashbackrule.deleted_in_other) !== 'undefined'){
        _replaceTo = _vipsettingcashbackrule.deleted_in_other;
    }
    nIndex++; // # 5
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{deleted_in_other\}/gi; // ${deleted_in_other};
    regexList[nIndex]['replaceTo'] = _replaceTo;

    _this.safelog('781.801.regexList:', regexList);
    var outerHtml = _this.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-level_list_row', regexList);
    _this.safelog('781.803.outerHtml:', outerHtml);

    if( typeof(appendToSelectorStr) !== 'undefined' ){
        $( outerHtml ).appendTo( appendToSelectorStr );
    }

    return outerHtml;

} // EOF do_render_checkVipGroupLevelsInEOCD4OneLevel

VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4ResultIntro = function(data){
    var _this = this;

    var is_warnings_empty = _this.is_empty_with_selectStr('.group_result_intro li[data-is_warning="1"]');
    var is_overridevipgroup_empty = _this.is_empty_with_selectStr('.group_result_intro li[data-case_str="'+ _this.Event_CASE_LIST.OVERRIDEVIPGROUP+ '"]');
    var is_overrideviplevel_empty = _this.is_empty_with_selectStr('.group_result_intro li[data-case_str="'+ _this.Event_CASE_LIST.OVERRIDEVIPLEVEL+ '"]');
    var is_moveviplevel_empty = _this.is_empty_with_selectStr('.group_result_intro li[data-case_str="'+ _this.Event_CASE_LIST.MOVEVIPLEVEL+ '"]');
    var is_adjustplayerlevel_empty = _this.is_empty_with_selectStr('.group_result_intro li[data-case_str="'+ _this.Event_CASE_LIST.ADJUSTPLAYERLEVEL+ '"]');
    var is_softdeleteviplevel_empty = _this.is_empty_with_selectStr('.group_result_intro li[data-case_str="'+ _this.Event_CASE_LIST.SOFTDELETEVIPLEVEL+ '"]');

    if( ! is_adjustplayerlevel_empty ){
        // 有些玩家會被移動到預設的等級。
        _this.getResultIntro(_this.Event_CASE_LIST.ADJUSTPLAYERLEVEL, false);
    }
    if( ! is_overrideviplevel_empty ){
        // 等級將會被覆蓋
        _this.getResultIntro(_this.Event_CASE_LIST.OVERRIDEVIPLEVEL, false);
    }
    if( ! is_softdeleteviplevel_empty ){
        // 等級將會被刪除
        _this.getResultIntro(_this.Event_CASE_LIST.SOFTDELETEVIPLEVEL, false);
    }
    if( ! is_overridevipgroup_empty ){
        // 群組將會被覆蓋
        _this.getResultIntro(_this.Event_CASE_LIST.OVERRIDEVIPGROUP, false);
    }
    if( ! is_moveviplevel_empty ){
        // 等級會被搬移，需要檢查原始群組等級。
        _this.getResultIntro(_this.Event_CASE_LIST.MOVEVIPLEVEL, false);
    }



    // if( $('.group_result_intro li[data-is_warning="1"]').length > 0 ){
    if( is_warnings_empty ){
        $('.btn_continue_in_ok').removeClass('hide'); // show OK btn
    }else{
        $('.btn_continue_in_ng').removeClass('hide'); // show NG btn
    }
}


VIPSETTING_SYNC.do_render_checkVipGroupLevelsInEOCD4Warnings = function(data){
    var _this = this;

    var _call_method_list = _this.extractData4call_method_listOfDryRun(data);
    _this.safelog('do_render_checkVipGroupLevelsInEOCD4Warnings.610._call_method_list:', _call_method_list);

    var _event_list = _this.getEventListFromCallMethodList(_call_method_list);
    _this.safelog('do_render_checkVipGroupLevelsInEOCD4Warnings.964._event_list:', _event_list);
    Object.keys( _event_list).forEach( function (currencyKey) {
        var _event_list_of_currency = _event_list[currencyKey];
        _event_list_of_currency.forEach(function(_event_of_currency, indexNumber){
            _this.renderWarningOfCurrencyWithEvent(_event_of_currency, currencyKey);
        }); // EOF _event_list_of_currency.forEach(...
    });// EOF Object.keys( _event_list).forEach( function (currencyKey) {...

}

VIPSETTING_SYNC.renderWarningOfCurrencyWithEvent = function(_event_of_currency, _currencyKey){
    var _this = this;
    _this.safelog('renderWarningOfCurrencyWithEvent.777._event_of_currency:', _event_of_currency);
    var _caseStr = _event_of_currency.caseStr;
    switch(_caseStr){
        default:
            break;
        // Ignore Warning by is_warning=0
        case _this.Event_CASE_LIST.SOFTDELETEVIPLEVEL:
            _this.renderWarningOfCurrency4softDeleteVipLevelEventVer2(_event_of_currency, _currencyKey);
            break;
        case _this.Event_CASE_LIST.ADJUSTPLAYERLEVEL:
            _this.renderWarningOfCurrency4adjustPlayerlevelEventVer2(_event_of_currency, _currencyKey);
            break;

        case _this.Event_CASE_LIST.OVERRIDEVIPGROUP:
            _this.renderWarningOfCurrency4overrideVipGroupEventVer2(_event_of_currency, _currencyKey);
            break;

        case _this.Event_CASE_LIST.OVERRIDEVIPLEVEL:
            _this.renderWarningOfCurrency4overrideVipLevelEventVer2(_event_of_currency, _currencyKey);
            break;
        case _this.Event_CASE_LIST.MOVEVIPLEVEL:
            _this.renderWarningOfCurrency4moveVipLevelEventVer2(_event_of_currency, _currencyKey);
            break;

        case _this.Event_CASE_LIST.REFRESHLEVELCOUNTOFVIPGROUP: // ignore Warning
            _this.renderWarningOfCurrency4refreshLevelCountOfVipGroupEventVer2(_event_of_currency, _currencyKey);
            break;
        case _this.Event_CASE_LIST.INCREASEVIPGROUPLEVEL: // ignore Warning
            _this.renderWarningOfCurrency4increaseVipGroupLevelEventVer2(_event_of_currency, _currencyKey);
            break;
        case _this.Event_CASE_LIST.NEWVIPGROUP: // ignore Warning
            _this.renderWarningOfCurrency4newVipGroupEventVer2(_event_of_currency, _currencyKey);
            break;
    }
}

VIPSETTING_SYNC.renderVipGroupDivByToken = function(_currency, _vipsettingId, _vipGroupName){
    var _this = this;
    var _length4vipsettingidOfCurrency = $('[data-currency_key="'+ _currency+ '"]')
                                            .find('.group_levels_container[data-vipsettingid="'+ _vipsettingId+ '"]')
                                            .length;
    _this.safelog('renderVipGroupDivByToken.734._length4vipsettingidOfCurrency:', _length4vipsettingidOfCurrency);
    if( _length4vipsettingidOfCurrency == 0 ){ // for group div
        _this.do_render_checkVipGroupLevelsInEOCD4OneGroupLite(_vipsettingId, _vipGroupName, '.currency_database[data-currency_key="'+ _currency+ '"]');
        // _this.do_render_checkVipGroupLevelsInEOCD4OneGroup(_vipsettingId, '.currency_database[data-currency_key="'+ _currency+ '"]');
    }
}
VIPSETTING_SYNC.renderVipLevelDivByToken = function(_currency, _vipsettingId, _vipsettingcashbackruleId, _vipLevelName, _event){
    var _this = this;


    var _vipsettingcashbackruleidOfCurrency$El = $('[data-currency_key="'+ _currency+ '"]')
                    .find('.group_levels_container[data-vipsettingid="'+ _vipsettingId+ '"]')
                    .find('[data-vipsettingcashbackruleid="'+ _vipsettingcashbackruleId+ '"]');

    var _length = _vipsettingcashbackruleidOfCurrency$El.length;

    var _vipsettingcashbackrule = {};
    _vipsettingcashbackrule.vipLevelName = _vipLevelName; // event.affected_level_name;
    _vipsettingcashbackrule.vipsettingcashbackruleId = _vipsettingcashbackruleId;
    _vipsettingcashbackrule.vipSettingId = _vipsettingId;

    if( typeof(_event.caseStr) !== 'undefined'){
        // caseStr from dryrun mode
        _vipsettingcashbackrule.caseStr = _event.caseStr;
    }
    _this.safelog('939._vipsettingcashbackrule.caseStr:', _vipsettingcashbackrule.caseStr, '_event:', _event);

    if( typeof(_event.affected_level_deleted) !== 'undefined'){
        // affected_level_deleted from dryrun mode
        _vipsettingcashbackrule.deleted = _event.affected_level_deleted;
    }


    if( _length == 0 ){ // for level div
        var _appendToSelectorStr = '.currency_database[data-currency_key="'+ _currency+ '"]'
                                    + ' '
                                    + '.group_levels_container[data-vipsettingid="'+ _vipsettingId+ '"]'
                                    + ' '
                                    + '.level_list_container';
        _this.safelog('939._vipsettingcashbackrule:', _vipsettingcashbackrule, 'will do_render_checkVipGroupLevelsInEOCD4OneLevel', '_event:', _event);
        _this.do_render_checkVipGroupLevelsInEOCD4OneLevel(_vipsettingcashbackrule, _appendToSelectorStr);
    }else{
        _this.safelog('864._vipsettingcashbackruleId:', _vipsettingcashbackruleId);
    }
    // step1. get levels without deleted form source DB.
    // step2. But dryrun result has the deleted levels of other DBs.
}

//
VIPSETTING_SYNC.renderWarningOfCurrency4softDeleteVipLevelEventVer2 = function(event, _currency){
    var _this = this; // caseStr, softDeleteVipLevel
    var _vipsettingcashbackruleId = event.affected_vipsettingcashbackruleId;
    var _vipsettingId = event.affected_vipSettingId;
    var _lang_msg = _this.options.lang.warning4SoftDeleteVipLevelEvent; // 'The level, "${affected_level_name}" will be deleted.';

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_level_name\}/gi; // ${affected_level_name};
    regexList[nIndex]['replaceTo'] = event.affected_level_name;

    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);

    var _htmlStr = $('<div>').append(
        $('<li>').attr('data-is_warning', event.is_warning )
                .attr('data-token', event.token )
                .attr('data-case_str', event.caseStr )
                .html(_msg)
    ).html(); // like outerHTML via jquery

    _this.renderVipGroupDivByToken(_currency, _vipsettingId, event.affected_group_name);
    _this.errorlog('985.will renderVipLevelDivByToken._vipsettingcashbackruleId:', _vipsettingcashbackruleId, 'event:', event, '_currency:', _currency);
    _this.renderVipLevelDivByToken(_currency, _vipsettingId, _vipsettingcashbackruleId, event.affected_level_name, event);

    if( $('[data-token="'+ event.token+ '"]').length == 0){
        _this.get$El4group_result_introByLevel(_vipsettingcashbackruleId, _currency).append( _htmlStr );
    }
}

VIPSETTING_SYNC.renderWarningOfCurrency4adjustPlayerlevelEventVer2 = function(event, _currency){
    var _this = this; // caseStr, adjustPlayerLevel
    var _vipsettingcashbackruleId = event.affected_vipsettingcashbackruleId;
    var _vipsettingId = event.affected_vipSettingId;

    var _lang_msg = _this.options.lang.warning4AdjustPlayerlevel; // 'The ${playerIds_count} player(s)  will updated from the level,"${affected_level_name}" to default level.';

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{playerIds_count\}/gi; // ${playerIds_count};
    regexList[nIndex]['replaceTo'] = event.playerIds_count;

    nIndex++; // # 1
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_level_name\}/gi; // ${affected_level_name};
    regexList[nIndex]['replaceTo'] = event.affected_level_name;

    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);

    var _htmlStr = $('<div>').append(
        // the conents
        $('<li>').attr('data-token', event.token )
            .attr('data-is_warning', event.is_warning )
            .attr('data-case_str', event.caseStr )
            .html(_msg)
    ).html(); // like outerHTML via jquery

    _this.renderVipGroupDivByToken(_currency, _vipsettingId, event.affected_group_name);
    _this.safelog('1024.will renderVipLevelDivByToken._vipsettingcashbackruleId:', _vipsettingcashbackruleId, 'event:', event, '_currency:', _currency);
    _this.renderVipLevelDivByToken(_currency, _vipsettingId, _vipsettingcashbackruleId, event.affected_level_name, event);

    if( $('[data-token="'+ event.token+ '"]').length == 0){
        _this.get$El4group_result_introByLevel(_vipsettingcashbackruleId, _currency).append( _htmlStr );
    }

}


VIPSETTING_SYNC.renderWarningOfCurrency4newVipGroupEventVer2 = function(event, _currency){
    var _this = this; // caseStr: newVipGroup
    var _vipsettingid = event.affected_vipSettingId;
    var affected_group_name = event.affected_group_name;
    var _lang_msg = _this.options.lang.warning4NewVipGroupEvent; // TODO

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_group_name\}/gi; // ${affected_group_name};
    regexList[nIndex]['replaceTo'] = affected_group_name;

    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);
    _this.safelog('renderWarningOfCurrency4newVipGroupEventVer2.847.event', event);
    _this.safelog('renderWarningOfCurrency4newVipGroupEventVer2.847._currency', _currency, '_vipsettingid', _vipsettingid, 'affected_group_name:', affected_group_name);


    _this.renderVipGroupDivByToken(_currency, _vipsettingid, affected_group_name);

    var _htmlStr = $('<div>').append(
        $('<li>').attr('data-is_warning', event.is_warning )
                .attr('data-token', event.token )
                .attr('data-case_str', event.caseStr )
                .html(_msg)
    ).html(); // like outerHTML via jquery

    if( $('[data-token="'+ event.token+ '"]').length == 0){
        _this.get$El4group_result_intro(_vipsettingid, _currency).append( _htmlStr );
    }
}
VIPSETTING_SYNC.renderWarningOfCurrency4increaseVipGroupLevelEventVer2 = function(event, _currency){
    var _this = this; // caseStr: increaseVipGroupLevel
    var _vipsettingid = event.affected_vipSettingId;
    var _vipsettingcashbackruleId = event.affected_vipsettingcashbackruleId;
    var affected_group_name = event.affected_group_name;
    var affected_level_name = event.affected_level_name;
    var _lang_msg = _this.options.lang.warning4IncreaseVipGroupLevelEvent; // TODO

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_group_name\}/gi; // ${affected_group_name};
    regexList[nIndex]['replaceTo'] = affected_group_name;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_level_name\}/gi; // ${affected_level_name};
    regexList[nIndex]['replaceTo'] = affected_level_name;

    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);

    var _group_name = event.affected_group_name;
    _this.renderVipGroupDivByToken(_currency, _vipsettingid, _group_name);
    _this.safelog('1091.will renderVipLevelDivByToken._vipsettingcashbackruleId:', _vipsettingcashbackruleId, 'event:', event, '_currency:', _currency);
    _this.renderVipLevelDivByToken(_currency, _vipsettingid, _vipsettingcashbackruleId, event.affected_level_name, event);

    var _htmlStr = $('<div>').append(
        $('<li>').attr('data-is_warning', event.is_warning )
                .attr('data-token', event.token )
                .attr('data-case_str', event.caseStr )
                .html(_msg)
    ).html(); // like outerHTML via jquery

    if( $('[data-token="'+ event.token+ '"]').length == 0){
        _this.get$El4group_result_intro(_vipsettingid, _currency).append( _htmlStr );
    }
}
VIPSETTING_SYNC.renderWarningOfCurrency4refreshLevelCountOfVipGroupEventVer2 = function(event, _currency){
    var _this = this; // caseStr: refreshLevelCountOfVipGroup
    var _vipsettingid = event.affected_vipSettingId;
    var affected_group_name = event.affected_group_name;
    var _lang_msg = _this.options.lang.warning4RefreshLevelCountOfVipGroupEvent; // TODO

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_group_name\}/gi; // ${affected_group_name};
    regexList[nIndex]['replaceTo'] = affected_group_name;

    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);

    var _group_name = event.affected_group_name;
    _this.renderVipGroupDivByToken(_currency, _vipsettingid, _group_name);

    var _htmlStr = $('<div>').append(
        $('<li>').attr('data-is_warning', event.is_warning )
                .attr('data-token', event.token )
                .attr('data-case_str', event.caseStr )
                .html(_msg)
    ).html(); // like outerHTML via jquery

    if( $('[data-token="'+ event.token+ '"]').length == 0){
        _this.get$El4group_result_intro(_vipsettingid, _currency).append( _htmlStr );
    }
}

VIPSETTING_SYNC.renderWarningOfCurrency4moveVipLevelEventVer2 = function(event, _currency){
    var _this = this; // caseStr: refreshLevelCountOfVipGroup
    var from_vipsettingid = event.from_vipSettingId;
    var from_group_name = event.from_group_name;
    var from_vipsettingcashbackruleId = event.from_vipsettingcashbackruleId;
    var from_level_name = event.from_level_name;

    var to_vipsettingid = event.to_vipSettingId;
    var to_group_name = event.to_group_name;
    var to_vipsettingcashbackruleId = event.to_vipsettingcashbackruleId;
    var to_level_name = event.to_level_name;

    var _lang_msg = _this.options.lang.warning4MoveVipLevelEvent;

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{from_group_name\}/gi; // ${from_group_name};
    regexList[nIndex]['replaceTo'] = from_group_name;

    nIndex++; // # 1
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{from_level_name\}/gi; // ${from_level_name};
    regexList[nIndex]['replaceTo'] = from_level_name;

    nIndex++; // # 2
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{to_group_name\}/gi; // ${to_group_name};
    regexList[nIndex]['replaceTo'] = to_group_name;

    nIndex++; // # 3
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{to_level_name\}/gi; // ${to_level_name};
    regexList[nIndex]['replaceTo'] = to_level_name;


    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);

    event.affected_level_deleted = event.from_level_deleted; // override
    _this.renderVipGroupDivByToken(_currency, from_vipsettingid, from_group_name);
    _this.safelog('1178.will renderVipLevelDivByToken.from_vipsettingcashbackruleId:', from_vipsettingcashbackruleId, 'event:', event, '_currency:', _currency);
    _this.renderVipLevelDivByToken(_currency, from_vipsettingid, from_vipsettingcashbackruleId, from_level_name, event);

    event.affected_level_deleted = event.to_level_deleted; // override
    _this.renderVipGroupDivByToken(_currency, to_vipsettingid, to_group_name);
    _this.safelog('1183.will renderVipLevelDivByToken.to_vipsettingcashbackruleId:', to_vipsettingcashbackruleId, 'event:', event, '_currency:', _currency);
    _this.renderVipLevelDivByToken(_currency, to_vipsettingid, to_vipsettingcashbackruleId, to_level_name, event);

    var _htmlStr = $('<div>').append(
        $('<li>').attr('data-is_warning', event.is_warning )
                .attr('data-token', event.token )
                .attr('data-case_str', event.caseStr )
                .html(_msg)
    ).html(); // like outerHTML via jquery

    if( $('[data-token="'+ event.token+ '"]').length == 0){
        _this.get$El4group_result_intro(from_vipsettingid, _currency).append( _htmlStr );
    }
}
VIPSETTING_SYNC.renderWarningOfCurrency4overrideVipLevelEventVer2 = function(event, _currency){
    var _this = this; // caseStr: refreshLevelCountOfVipGroup
    var _vipsettingid = event.affected_vipSettingId;
    var affected_group_name = event.affected_group_name;
    var _vipsettingcashbackruleId = event.affected_vipsettingcashbackruleId;
    var affected_level_name = event.affected_level_name;

    var _lang_msg = _this.options.lang.warning4OverrideVipLevelEvent;

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_group_name\}/gi; // ${affected_group_name};
    regexList[nIndex]['replaceTo'] = affected_group_name;

    nIndex++; // # 1
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_level_name\}/gi; // ${affected_level_name};
    regexList[nIndex]['replaceTo'] = affected_level_name;

    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);

    var _group_name = event.affected_group_name;
    _this.renderVipGroupDivByToken(_currency, _vipsettingid, _group_name);
    _this.safelog('1218.will renderVipLevelDivByToken._vipsettingcashbackruleId:', _vipsettingcashbackruleId, 'event:', event, '_currency:', _currency);
    _this.renderVipLevelDivByToken(_currency, _vipsettingid, _vipsettingcashbackruleId, event.affected_level_name, event);

    var _htmlStr = $('<div>').append(
        $('<li>').attr('data-is_warning', event.is_warning )
                .attr('data-token', event.token )
                .attr('data-case_str', event.caseStr )
                .html(_msg)
    ).html(); // like outerHTML via jquery

    if( $('[data-token="'+ event.token+ '"]').length == 0){
        _this.get$El4group_result_intro(_vipsettingid, _currency).append( _htmlStr );
    }
}

VIPSETTING_SYNC.renderWarningOfCurrency4overrideVipGroupEventVer2 = function(event, _currency){
    var _this = this; // caseStr: overrideVipGroup
    var _vipsettingid = event.affected_vipSettingId;
    var affected_group_name = event.affected_group_name;
    var _lang_msg = _this.options.lang.warning4OverrideVipGroupEvent; // 'The Group,"${affected_group_name}" will be overrided.';


    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{affected_group_name\}/gi; // ${affected_group_name};
    regexList[nIndex]['replaceTo'] = affected_group_name;

    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);

    var _group_name = event.affected_group_name;
    _this.renderVipGroupDivByToken(_currency, _vipsettingid, _group_name);

    var _htmlStr = $('<div>').append(
        $('<li>').attr('data-is_warning', event.is_warning )
                .attr('data-token', event.token )
                .attr('data-case_str', event.caseStr )
                .html(_msg)
    ).html(); // like outerHTML via jquery

    if( $('[data-token="'+ event.token+ '"]').length == 0){
        _this.get$El4group_result_intro(_vipsettingid, _currency).append( _htmlStr );
    }

}
VIPSETTING_SYNC.get$El4report_result = function(){
    var _this = this;
    var reportResult$El = $('.report_result');
    return reportResult$El;
}
VIPSETTING_SYNC.get$El4group_result_intro = function(_vipsettingid, _currencyKey){
    var _this = this;
    // var currencyDiv$El = _this.get$El4currency_database(_currencyKey);
    var groupLevelsDiv$El = _this.get$El4group(_vipsettingid, _currencyKey).closest('.group_levels_container');
    // var groupLevelsDiv$El = currencyDiv$El.find('.group_levels_container[data-vipsettingid='+ _vipsettingid+ ']');
    _this.safelog('El4group_result_intro1062.groupLevelsDiv:', groupLevelsDiv$El);
    var groupResultIntro$El = groupLevelsDiv$El.find('.group_result_intro');
    return groupResultIntro$El;
}
VIPSETTING_SYNC.get$El4group_result_introByLevel = function(_vipsettingcashbackruleid, _currencyKey){
    var _this = this;
    var groupLevelsDiv$El = _this.get$El4level(_vipsettingcashbackruleid, _currencyKey).closest('.group_levels_container');
    var groupResultIntro$El = groupLevelsDiv$El.find('.group_result_intro');
    return groupResultIntro$El;
}
VIPSETTING_SYNC.get$El4currency_database = function(_currencyKey){
    var currencyDiv$El = $('.currency_database[data-currency_key="'+ _currencyKey+ '"]');
    return currencyDiv$El;
}
VIPSETTING_SYNC.get$El4group = function(_vipsettingid, _currencyKey){
    var _this = this;

    var currencyDiv$El = $('.currency_database[data-currency_key="'+ _currencyKey+ '"]');

    var selectorStr = '';
    selectorStr += '.group_levels_container[data-vipsettingid="'+ _vipsettingid+ '"]';

    var groupLevelsContainerDiv$El = currencyDiv$El.find(selectorStr);
    return groupLevelsContainerDiv$El;
}
VIPSETTING_SYNC.get$El4level = function(vipsettingcashbackruleid, _currencyKey, _vipsettingid = 0){
    var _this = this;
    var currencyDiv$El = $('.currency_database[data-currency_key="'+ _currencyKey+ '"]');

    var selectorStr = '';
    if(_vipsettingid > 0){
        selectorStr += '.row[data-vipsettingid="'+ _vipsettingid+ '"]';
        selectorStr += ' ';
        selectorStr += '.group_levels_container div.group_levels_container[data-vipsettingid="'+ _vipsettingid+ '"] div[data-vipsettingcashbackruleid="'+ vipsettingcashbackruleid+ '"]';
    }else{
        selectorStr += '.group_levels_container div[data-vipsettingcashbackruleid="'+ vipsettingcashbackruleid+ '"]';
    }
    var groupLevelsContainerDiv$El = currencyDiv$El.find(selectorStr);
    return groupLevelsContainerDiv$El;
}


VIPSETTING_SYNC.show_checkVipGroupLevelsInEOCD = function(e){
    var _this = this;

    var _selectorStr = '.btn_continue_in_ng,.btn_continue_in_ok';
    _this.do_render4disabled_button(_selectorStr, true); // revert btn
    $('#checkVipGroupLevelsInEOCDModal').find('.modal-footer .btn').addClass('hide'); // hide all
    $('.btn_dismiss').removeClass('hide'); // show Cancel button

    $("#checkVipGroupLevelsInEOCDModal .report_intro_details").html(''); // reset contents

    _this.get$El4report_result().html(''); // reset report_result

    // reset the buttons
    $('.btn_continue_in_ng').addClass('hide');
    $('.btn_continue_in_ok').addClass('hide');
    $('.btn_refresh').addClass('hide');

    // loading by ajax check_vip_group_before_sync
    var postData = {};
    postData.vipSettingId = $(e.target).find('input.vipsettingid').val();
    postData.dryRun = $(e.target).find('input.dryrun').val();
    postData.data = $(e.target).find('input._data').val();
    var theUri = _this.options.urls.check_vip_group_before_sync;
    var method_type = 'POST';
    _this.safelog('1114.theUri:', theUri);
    _this.safelog('1115.postData:', postData);
    var _ajax = _this._do_Uri(function(jqXHR, settings){ // beforeSendCB
        $('#checkVipGroupLevelsInEOCDModal').find('.loading').show();
    }, function(jqXHR, textStatus){ // completeCB
        $('#checkVipGroupLevelsInEOCDModal').find('.loading').hide();
    }, theUri, postData, method_type);

    _ajax.done(function (data, textStatus, jqXHR) {
        _this.do_render_checkVipGroupLevelsInEOCD(data);
    });


} // EOF show_checkVipGroupLevelsInEOCD(e)

VIPSETTING_SYNC.shown_checkVipGroupLevelsInEOCD = function(e){
    var _this = this;

} // EOF shown_checkVipGroupLevelsInEOCD(e)

VIPSETTING_SYNC.hide_checkVipGroupLevelsInEOCD = function(e){
    var _this = this;

} // EOF hide_checkVipGroupLevelsInEOCD(e)

VIPSETTING_SYNC.hidden_checkVipGroupLevelsInEOCD = function(e){
    var _this = this;

} // EOF hidden_checkVipGroupLevelsInEOCD(e)



VIPSETTING_SYNC.show_promptVipGroupLevelHasPlayersModal = function(e){
    var _this = this;

    // reset
    var _msg$El = $('#promptVipGroupLevelHasPlayersModal').find('.msg4promptVipGroupLevelHasPlayersModal');
    _msg$El.html( _msg$El.data('default_msg') );
    $('#promptVipGroupLevelHasPlayersModal').find('.btn_next').data('next_uri', '');
    $('#promptVipGroupLevelHasPlayersModal').find('.btn_next').addClass('hide');
    $('#promptVipGroupLevelHasPlayersModal').find('.btn_close').addClass('hide');

    // loading by ajax of "input.ajax-uri"
    var postData = {};
    var theUri = $('#promptVipGroupLevelHasPlayersModal').find('input.ajax-uri').val();
    var method_type = 'POST';
    _this.safelog('1438.theUri:', theUri);
    _this.safelog('1439.postData:', postData);
    if(theUri.length > 0){
        var _ajax = _this._do_Uri(function(jqXHR, settings){ // beforeSendCB
            $('#promptVipGroupLevelHasPlayersModal').find('.loading').show();
        }, function(jqXHR, textStatus){ // completeCB
            $('#promptVipGroupLevelHasPlayersModal').find('.loading').hide();
        }, theUri, postData, method_type);
        _ajax.done(function (data, textStatus, jqXHR) {
            _this.do_render_promptVipGroupLevelHasPlayersModal(data);
        });
    }else{
        $('#promptVipGroupLevelHasPlayersModal').find('.loading').hide();
    }

} // EOF show_promptVipGroupLevelHasPlayersModal(e)

VIPSETTING_SYNC.shown_promptVipGroupLevelHasPlayersModal = function(e){
    var _this = this;

} // EOF shown_promptVipGroupLevelHasPlayersModal(e)

VIPSETTING_SYNC.hide_promptVipGroupLevelHasPlayersModal = function(e){
    var _this = this;

} // EOF hide_promptVipGroupLevelHasPlayersModal(e)

VIPSETTING_SYNC.hidden_promptVipGroupLevelHasPlayersModal = function(e){
    var _this = this;

} // EOF hidden_promptVipGroupLevelHasPlayersModal(e)

VIPSETTING_SYNC.do_render_promptVipGroupLevelHasPlayersModal = function(data){
    var _this = this;
    /// OK
    // {
    //     "success": true,
    //     "code": 351,
    //     "message": "Decrease Vip Group Level completed.",
    //     "result": {
    //         "count": 0,
    //         "next_uri": "\/vipsetting_management\/viewVipGroupRules\/18"
    //     }
    // }
    /// NG
    // {
    //     "success": false,
    //     "code": 353,
    //     "message": "Decrease Vip Group Level Not Yet completed.",
    //     "result": {
    //         "count": 2
    //     }
    // }
    // $code = 343; // Level has exists players.
    // $code = 351; // decrease level completed
    // $code = 353; // decrease level NG
    if( typeof(data.message) !== 'undefined'){
        $('#promptVipGroupLevelHasPlayersModal').find('.msg4promptVipGroupLevelHasPlayersModal').html(data.message);
    }
    if( typeof(data.code) !== 'undefined'){

        var _next_uri = '';
        if( typeof(data.result) !==  'undefined'
            && 'next_uri' in  data.result
        ){
            _next_uri = data.result.next_uri;
        }

        _this.do_renderByCode_promptVipGroupLevelHasPlayersModal(data.code, _next_uri);
    }
} // EOF do_render_promptVipGroupLevelHasPlayersModal
//
VIPSETTING_SYNC.do_renderByCode_promptVipGroupLevelHasPlayersModal = function(code, next_uri){
    var _this = this;
    switch(code+ ""){
        case _this.options.CODE_DECREASEVIPGROUPLEVEL.CODE_DECREASEVIPGROUPLEVEL_IN_LEVEL_EXIST_PLAYER: // Level has exists players.
        // tips, @.message
        $('#promptVipGroupLevelHasPlayersModal').find('.btn_close').removeClass('hide');
        break;

        case  _this.options.CODE_DECREASEVIPGROUPLEVEL.CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_COMPLETED:// decrease level completed.
        // tips, @.message
        // reload to @.result.next_uri
        if( typeof(next_uri) !== 'undefined' && next_uri.length > 0){
            $('#promptVipGroupLevelHasPlayersModal').find('.btn_next').data('next_uri', next_uri);
            $('#promptVipGroupLevelHasPlayersModal').find('.btn_next').removeClass('hide');
        }
        break;

        case  _this.options.CODE_DECREASEVIPGROUPLEVEL.CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_NO_GOOD:// decrease level NG
        // tips, @.message
        $('#promptVipGroupLevelHasPlayersModal').find('.btn_close').removeClass('hide');
        break;
    }
}


VIPSETTING_SYNC._do_Uri = function (beforeSendCB, completeCB, theUri, postData, method_type = 'POST'){
    var _this = this;

    if( typeof(beforeSendCB) === 'undefined'){
        beforeSendCB = function(jqXHR, settings){};
    }
    if( typeof(completeCB) === 'undefined'){
        completeCB = function(jqXHR, textStatus){};
    }

    if( typeof(theUri) === 'undefined'){
        theUri = _this.urls.check_vip_group_before_sync;
    }
    if( typeof(postData) === 'undefined'){
        postData = {};
    }

    // replace to real value by postData
    $.each(postData, function(indexStr, currEle ){
        var myExp = new RegExp('{\\$'+ indexStr+ '}', 'gi');
        theUri = theUri.replace(myExp, currEle);
    });
    //
    // replace others to "_undefined"
    var myExp = new RegExp('{\\$[^}]+}', 'gi');
    theUri = theUri.replace(myExp, '_undefined');

    if(method_type == 'GET'){
        postData = {}; // disable data in GET method
    }

    _this.safelog('1274._do_Uri.theUri:', theUri);
    _this.safelog('1275._do_Uri.postData:', postData);

    var jqXHR = $.ajax({
        type: method_type,
        url: theUri,
        data: postData,
        beforeSend: function (jqXHR, settings) {
            // targetBtn$El.button('loading');
            beforeSendCB.apply(_this, arguments);
        },
        complete: function (jqXHR, textStatus) {
            // targetBtn$El.button('reset');
            completeCB.apply(_this, arguments);
        }
    });
    // jqXHR.done(function (data, textStatus, jqXHR) {
    //
    // });

    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        if ( errorThrown == 'Forbidden'
            || errorThrown == 'Unauthorized'
        ) {
            $('#somethingWrongModal').modal('show');
        }
    });

    return jqXHR;
}


VIPSETTING_SYNC.getResultIntro = function(case_str, is_more_than_one){
    var _this = this;

    var _lang_msg = '';
    switch(case_str){
        case _this.Event_CASE_LIST.OVERRIDEVIPGROUP:
            _lang_msg += _this.options.lang.resultIntro4OverrideVipGroup;
            break;
        case _this.Event_CASE_LIST.OVERRIDEVIPLEVEL:
            _lang_msg += _this.options.lang.resultIntro4OverrideVipLevel;
            break;
        case _this.Event_CASE_LIST.MOVEVIPLEVEL:
            _lang_msg += _this.options.lang.resultIntro4MoveVipLevel;
            break;
        case _this.Event_CASE_LIST.ADJUSTPLAYERLEVEL:
            _lang_msg += _this.options.lang.resultIntro4AdjustPlayerlevel;
            break;
        case _this.Event_CASE_LIST.SOFTDELETEVIPLEVEL:
            _lang_msg += _this.options.lang.resultIntro4SoftDeleteVipLevel;
            break;
    }

    var regexList = [];
    var nIndex = -1;

    nIndex++; // # 0
    regexList[nIndex] = {};
    regexList[nIndex]['regex'] = /\$\{plural\}/gi; // ${plural};
    regexList[nIndex]['replaceTo'] = '';
    // regexList[nIndex]['replaceTo'] = 's';

    var _msg = _this.getOuterHtmlAndReplaceAll(_lang_msg, regexList);


    var _htmlStr = $('<div>').append(
        $('<li>').html(_msg)
    ).html(); // like outerHTML via jquery

    _this.get$El4report_result().append( _htmlStr );

}

VIPSETTING_SYNC.is_empty_with_selectStr = function(selectStr){
    var is_empty = false;
    if( $(selectStr).length > 0 ){
        is_empty = false;
    }else{
        is_empty = true;
    }
    return is_empty;
}

VIPSETTING_SYNC.detect_console_log = function () {
    var _this = this;
    // detect dbg=1 in get params for self.safelog output.
    var query = window.location.search.substring(1);
    var qs = _this.parse_query_string(query);
    if ('dbg' in qs
        && typeof (qs.dbg) !== 'undefined'
        && qs.dbg
    ) {
        _this.debugLog = true;
    } else {
        _this.debugLog = false;
    }
}
//
/**
 * Get the value from the GET parameters
 * Ref. to https://stackoverflow.com/a/979995
 *
 * @param {string} query
 *
 * @code
 * <code>
 *  var query_string = "a=1&b=3&c=m2-m3-m4-m5";
 *  var parsed_qs = parse_query_string(query_string);
 *  console.log(parsed_qs.c);
 * </code>
 */
VIPSETTING_SYNC.parse_query_string = function (query) {
    var vars = query.split("&");
    var query_string = {};
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        var key = decodeURIComponent(pair[0]);
        var value = decodeURIComponent(pair[1]);
        // If first entry with this name
        if (typeof query_string[key] === "undefined") {
            query_string[key] = decodeURIComponent(value);
            // If second entry with this name
        } else if (typeof query_string[key] === "string") {
            var arr = [query_string[key], decodeURIComponent(value)];
            query_string[key] = arr;
            // If third or later entry with this name
        } else {
            query_string[key].push(decodeURIComponent(value));
        }
    }
    return query_string;
}; // EOF parse_query_string
//
VIPSETTING_SYNC.safelog = function (msg) {
    var _this = this;

    if (typeof (safelog) !== 'undefined') {
        safelog.apply(window, msg); // for applied
    } else {
        //check exists console
        if (_this.debugLog
            && typeof (console) !== 'undefined'
        ) {
            console.log.apply(console, Array.prototype.slice.call(arguments));
        }
    }
}; // EOF safelog
VIPSETTING_SYNC.errorlog = function (msg) {
    var _this = this;

    if (typeof (safelog) !== 'undefined') {
        safelog.apply(window, msg); // for applied
    } else {
        //check exists console
        if (_this.debugLog
            && typeof (console) !== 'undefined'
        ) {
            console.error.apply(console, Array.prototype.slice.call(arguments));
        }
    }
}; // EOF errorlog


VIPSETTING_SYNC.safeGetVipSettingIdFromSource = function(__source){
    var _this = this;
    var _vipSettingId = 0; // get curr vipSettingId because its empty level
    if( typeof(__source.vipsetting) !== 'undefined' ){
        if( typeof(__source.vipsetting[0]) !== 'undefined'
            && 'vipSettingId' in __source.vipsetting[0]
        ){
            _vipSettingId = __source.vipsetting[0].vipSettingId; // get curr vipSettingId because its empty level
        }
    }
    return _vipSettingId;
}
