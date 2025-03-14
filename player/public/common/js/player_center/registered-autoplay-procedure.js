var RegisteredAutoPlayProcedure = {
    /** tutorial params */
    is_tutorial_done : 0,

    /** vip params */
    playerId : "",
    joinVipUrl : "",
    vip_count : "",
    is_vip_show_done : "",
    is_vip_callback : 0,

    /** priority player params */
    is_join_priority: 0,
    is_join_show_done : "",

    /** tutorial params */
    enable_registered_show_popup : 0,
    is_registered_popup_success_done : "",

    hide_registered_modal: false,

    queueList: ['setIsTutorialDone', 'registeredPopUp', 'chooseVipGroup', 'joinPriorityPopUp'],

    is_init : false,
    init : function() {
        var _this = this;

        if (_this.hide_registered_modal) {
            _this.removeItemOfQueueListByValue('registeredPopUp');
        }

        if( $("#registered-modal").length == 0){
            _this.removeItemOfQueueListByValue('registeredPopUp');
        }
        if( $('#vip-group-modal').length == 0){
            _this.removeItemOfQueueListByValue('chooseVipGroup');
        }

        if (!this.is_init) {
            /// remove the item of queueList when done of execute().
            if (this.is_vip_show_done) {
                this.queueList.splice($.inArray('chooseVipGroup', this.queueList), 1);
            }

            if (this.is_tutorial_done) {
                this.queueList.splice($.inArray('setIsTutorialDone', this.queueList), 1);
            }

            if (!this.enable_registered_show_popup || this.is_registered_popup_success_done) {
                this.queueList.splice($.inArray('registeredPopUp', this.queueList), 1);
            }

            if (this.is_join_show_done == true // the action had shown, so remove
                || this.is_join_show_done === null // not yet enabled, directly remove.
            ) {
                this.queueList.splice($.inArray('joinPriorityPopUp', this.queueList), 1);
            }

            this.is_init = true;
        }
    },

    removeItemOfQueueListByValue: function(_val = 'registeredPopUp'){
        var _this = this;
        var index = _this.queueList.indexOf(_val);
        if (index !== -1) {
            _this.queueList.splice(index, 1);
        }
        return _this.queueList;
    },

    execute : function() {
        var _this = this;
        this.init();

        var action = this.queueList.shift(), // take a item
            endPlay = this.queueList.length - 1;

        switch (action) {
            case 'chooseVipGroup':
                chooseVipGroup();
                break;
            case 'registeredPopUp':
                registeredSuccesePopUp();
                break;
            case 'setIsTutorialDone':
                setIsTutorialDone();
                break;
            case 'joinPriorityPopUp':
                _this.joinPriorityPopUp();
                break;
            default: return;
        }
    },
    complete : function (callback) {
        this.execute();
    },
    onReady: function(){
        var _this = this;

        // events
        $(document).on('hide.bs.modal', "#join-priority-modal", function (e) {
            _this.onHideCallback4joinPriorityPopUp(e);
        }).on('hidden.bs.modal', "#join-priority-modal", function (e) {
            _this.onHiddenCallback4joinPriorityPopUp(e);
        });

    } // EOF onReady()
} // EOF RegisteredAutoPlayProcedure

function registeredSuccesePopUp() {
    $("#registered-modal").modal('show');
    $("#registered-modal").on('hidden.bs.modal', function (e) {
        setTimeout(RegisteredAutoPlayProcedure.complete(), 2000);
    });
    $.post(base_url + "player_center/setIsRegisterPopUpDone");
}

function chooseVipGroup () {
    $('#vip-group-modal').modal('show');
    $("#vip-group-modal").on('hidden.bs.modal', function (e) {
        if (!RegisteredAutoPlayProcedure.is_vip_callback) {
            setTimeout(RegisteredAutoPlayProcedure.complete(), 2000);
        }
    });
    $.post(base_url + "player_center/setIsVIPShowDone");
}

RegisteredAutoPlayProcedure.joinPriorityPopUp = function(){
    // ref. from #registered-modal
    $('#join-priority-modal').modal('show');

} // EOF RegisteredAutoPlayProcedure.joinPriorityPopUp ...
//
RegisteredAutoPlayProcedure.onHideCallback4joinPriorityPopUp = function(e){
    var _this = this;
    var target$El = $(e.currentTarget);
    var _data = {};
    _data['tickPriority'] = target$El.find('input:checkbox[name="is_join_show_done"]:checked').length;
    $.post(base_url + "player_center/setJoinPriorityShowDone", _data, function(data,  textStatus,  jqXHR){
    });
} // EOF onHideCallback4joinPriorityPopUp
//
RegisteredAutoPlayProcedure.onHiddenCallback4joinPriorityPopUp = function(e){
    var _this = this;
    if (!_this.is_join_priority) {
        setTimeout(_this.complete(), 2000); // _this.complete() aka. RegisteredAutoPlayProcedure.complete()
    }
    _this.is_join_priority = 1; // update to done
} // EOF onHiddenCallback4joinPriorityPopUp

function setIsTutorialDone() {
    $.post(base_url + "player_center/setIsTutorialDone");
    setTimeout(RegisteredAutoPlayProcedure.complete(), 1000);
}

$(document).on("click", "#vip-group-modal .btn_join , .img_join", function () {
    var vipId = $(this).data("id"),
        groupName = $(this).data("gname"),
        levelName = $(this).data("lname"),
        playerId = RegisteredAutoPlayProcedure.playerId;
        joinVipUrl = RegisteredAutoPlayProcedure.joinVipUrl;
        level = $(this).data("level");
        data = {
            newPlayerLevel: level,
            groupName: groupName,
            levelName: levelName,
            vipId: vipId,
            playerId: playerId
        };

    $.ajax({
        url: RegisteredAutoPlayProcedure.joinVipUrl,
        type: 'POST',
        data: data,
        dataType: "json",
        cache: false,
    }).done(function (data) {
        if (data.status == "success") {
            RegisteredAutoPlayProcedure.is_vip_callback = 1;
            $('#vip-group-modal').modal('hide');
            $('#vip-group-modal').on('hidden.bs.modal', function () {
                load_alert_message(data.status, data.msg, function() {
                    setTimeout(RegisteredAutoPlayProcedure.complete(), 2000);
                });
            });
            loadPlayerVipGroupDetails();
        }
        if (data.status == "error") {
            load_alert_message(data.status, data.msg);
        }
    }).fail(function (jqXHR, textStatus) {
        if (jqXHR.status >= 300 && jqXHR.status < 500) {
            location.reload();
        } else {
            load_alert_message("error", textStatus);
        }
    });
});

$( document ).ready(function() {
    RegisteredAutoPlayProcedure.onReady();
});