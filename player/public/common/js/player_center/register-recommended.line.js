var LineRegister = LineRegister || {};

LineRegister.callbackAfterOnReady = function () {
    // todo something after onReady().
};

LineRegister.onReady = function () {
    var _this = this;

    _this.formActionDataList = {};
    _this.formActionDataList['normal'] = 'post_register_player-action';
    _this.formActionDataList['line'] = 'post_register_line-action';

    _this.registrationForm$El = $('#registration_form');

    var selectStrList = [];
    selectStrList.push('.registration-tabs .normal-reg');
    selectStrList.push('.registration-tabs .line-reg');
    $('body').on('click', selectStrList.join(','), function (e) {
        // console.log('WILL clicked_registrationTabs');
        _this.clicked_registrationTabs(e);
    });

    if (typeof (_this.callbackAfterOnReady) == 'function') {
        _this.callbackAfterOnReady();
    }
};


LineRegister.clicked_registrationTabs = function (e) {
    var _this = this;
    // console.log('clicked_registrationTabs');
    var theTarget$El = $(e.target);
    var theTabs$El = theTarget$El.closest('.registration-tabs');
    var theTargetNav$El = theTarget$El.closest('.nav-item');

    // switch to normal/line-reg
    theTabs$El.find('.nav-item').removeClass('active');
    theTargetNav$El.addClass('active');

    // update line_reg  vslue
    var line_reg = 0;
    if (theTargetNav$El.find('.line-reg').length > 0) {
        line_reg = 1;
    }
    $('input[name="line_reg"]').val(line_reg);


    var actionURI = '';
    switch ($('input[name="line_reg"]').val()) {
        default:
        case '0':
            actionURI = _this.registrationForm$El.data(_this.formActionDataList['normal']);
            break;
        case '1':
            actionURI = _this.registrationForm$El.data(_this.formActionDataList['line']);
            break;

    }
    _this.registrationForm$El.prop('action', actionURI);
}
