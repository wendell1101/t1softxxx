var view_player_login_via_same_ip = view_player_login_via_same_ip || {};

view_player_login_via_same_ip.detected_tag_id_key = '';
view_player_login_via_same_ip.default_conditions = {};
view_player_login_via_same_ip.uri_list = {};
view_player_login_via_same_ip.uri_list.updateDetectedTagIdInViewPlayerLoginViaSameIp = '/api/updateDetectedTagIdInViewPlayerLoginViaSameIp';

view_player_login_via_same_ip.onReady = function () {
    var _this = this;

    var date_today = new moment().format('YYYY-MM-DD');

    $('body').on('click', '#btnResetFields', function (e) {
        $('input[name="by_username"]').val('');
        // $('#include_all_downlines').prop('checked', false);
        $("#search_payment_date").val(date_today + " to " + date_today);
    });

    $('body').on('change', '[id="checkbox_logged_in_at_enabled_date"]', function (e) {
        var thisEl = this;
        if (thisEl.checked) {
            $('#logged_in_at_search_date').prop('disabled', false);
            $('#logged_in_at_date_from').prop('disabled', false);
            $('#logged_in_at_date_to').prop('disabled', false);
            $('[name="logged_in_at_enabled_date"]').val(1);
        } else {
            $('#logged_in_at_search_date').prop('disabled', true);
            $('#logged_in_at_date_from').prop('disabled', true);
            $('#logged_in_at_date_to').prop('disabled', true);
            $('[name="logged_in_at_enabled_date"]').val(0);
        }
    })
    $('[id="checkbox_logged_in_at_enabled_date"]').trigger('change');

    $('body').on('change', '[id="checkbox_created_at_enabled_date"]', function (e) {
        var thisEl = this;
        if (thisEl.checked) {
            $('#created_at_search_date').prop('disabled', false);
            $('#created_at_date_from').prop('disabled', false);
            $('#created_at_date_to').prop('disabled', false);
            $('[name="created_at_enabled_date"]').val(1);
        } else {
            $('#created_at_search_date').prop('disabled', true);
            $('#created_at_date_from').prop('disabled', true);
            $('#created_at_date_to').prop('disabled', true);
            $('[name="created_at_enabled_date"]').val(0);
        }
    });
    $('[id="checkbox_created_at_enabled_date"]').trigger('change');

    $('body').on('click', '#btnResetFields', function (e) {
        return _this.clicked_btnResetFields(e);
    });

    $('body').on('click', '.setupTagDetected', function (e) {
        return _this.clicked_setupTagDetected(e);
    });


    $('body').on('click', '#updateDetectedTagIdInViewPlayerLoginViaSameIp', function (e) {
        // updateDetectedTagIdInViewPlayerLoginViaSameIp
        return _this.clicked_updateDetectedTagIdInViewPlayerLoginViaSameIp(e);
    });

    $('body').on('change', 'select[name="detected_tag"]', function () {
        console.log('elect[name="detected_tag".change')
        var defaultSelectedVal = $('[name="detected_tag"]>option[selected]').val();
        if ($('[name="detected_tag"]').val() != defaultSelectedVal) {
            _this.reset_result_txt();
        }
    });

};// EOF changeableTable.onReady()

/**
 * The Delegate Handle while click the Reset Button
 * It will trigger the method, reset_search_form().
 * @param event e The event object.
 */
view_player_login_via_same_ip.clicked_btnResetFields = function (e) {
    var _this = this;
    _this.reset_search_form();
}; // EOF clicked_btnResetFields

view_player_login_via_same_ip.clicked_setupTagDetected = function (e) {
    var _this = this;

    _this.reset_setup_tag_detected();

    $('#setup_tag_detected').modal('show');
}; // EOF clicked_setupTagDetected()


/**
 * Reset Form Script
 */
view_player_login_via_same_ip.reset_search_form = function () {
    var _this = this;
    var defaults = _this.default_conditions;

    // #created_at_search_date
    $('[name="created_at_date_from"]').val(defaults.created_at_date_from);
    $('[name="created_at_date_to"]').val(defaults.created_at_date_to);
    $('#created_at_search_date').data('daterangepicker').setStartDate(defaults.created_at_date_from);
    $('#created_at_search_date').data('daterangepicker').setEndDate(defaults.created_at_date_to);
    dateInputAssignToStartAndEnd($('#created_at_search_date')); // reset daterangepicker
    if (defaults.created_at_enabled_date == 1) {
        $('#checkbox_created_at_enabled_date:checkbox').attr('checked', 'checked').prop('checked', true);
    } else {
        $('#checkbox_created_at_enabled_date:checkbox').attr('checked', null).prop('checked', false);
    }
    $('[id="checkbox_created_at_enabled_date"]').trigger('change');

    // #logged_in_at_search_date
    $('[name="logged_in_at_date_from"]').val(defaults.logged_in_at_date_from);
    $('[name="logged_in_at_date_to"]').val(defaults.logged_in_at_date_to);
    $('#logged_in_at_search_date').data('daterangepicker').setStartDate(defaults.logged_in_at_date_from);
    $('#logged_in_at_search_date').data('daterangepicker').setEndDate(defaults.logged_in_at_date_to);
    dateInputAssignToStartAndEnd($('#logged_in_at_search_date')); // reset daterangepicker
    if (defaults.logged_in_at_enabled_date == 1) {
        $('#checkbox_logged_in_at_enabled_date:checkbox').attr('checked', 'checked').prop('checked', true);
    } else {
        $('#checkbox_logged_in_at_enabled_date:checkbox').attr('checked', null).prop('checked', false);
    }
    $('[id="checkbox_logged_in_at_enabled_date"]').trigger('change');

    // input[name="search_by"]
    $('input[name="search_by"][value="' + defaults.search_by + '"]').trigger('click')

    // input[name="username"]
    $('input[name="username"]').val(defaults.username);
} // EOF reset_search_form

view_player_login_via_same_ip.reset_setup_tag_detected = function () {
    var _this = this;
    _this.reset_result_txt();

    var defaultSelectedVal = $('[name="detected_tag"]>option[selected]').val();
    $('[name="detected_tag"]').val(defaultSelectedVal);

    $('#updateDetectedTagIdInViewPlayerLoginViaSameIp').button('reset');
};

view_player_login_via_same_ip.reset_result_txt = function () {
    $('#setup_tag_detected .result_txt').removeClass('text-danger');
    $('#setup_tag_detected .result_txt').html('');

};

view_player_login_via_same_ip.clicked_updateDetectedTagIdInViewPlayerLoginViaSameIp = function (e) {
    var _this = this;
    var uri = _this.uri_list.updateDetectedTagIdInViewPlayerLoginViaSameIp;
    // var regex = /\$\{id\}/gi; // ${id}
    // uri = uri.replaceAll(regex, appoint_id);
    var _data = {};
    _data[_this.detected_tag_id_key] = $('[name="detected_tag"]').val();
    var _ajax = $.ajax({
        "contentType": "application/json; charset=utf-8",
        "dataType": "json",
        "url": uri,
        "data": _data,
        "method": "GET",
        beforeSend: function (jqXHR, settings) {
            /// show loader
            $('#updateDetectedTagIdInViewPlayerLoginViaSameIp').button('loading');
        } // EOF beforeSend
    });
    _ajax.done(function (data, textStatus, jqXHR) {
        console.log('clicked_updateDetectedTagIdInViewPlayerLoginViaSameIp.done.arguments:', arguments);

        if (data.bool) {
            $('[name="detected_tag"] option').prop('selected', false).attr('selected', false);
            $('[name="detected_tag"] option[value="' + data.currect_tag_id + '"]').prop('selected', true).attr('selected', 'selected');

            $('#setup_tag_detected .result_txt').addClass('text-danger');
            $('#setup_tag_detected .result_txt').html(data.msg);// Update completed.
        }


    });
    _ajax.fail(function (jqXHR, textStatus, errorThrown) {
        // console.log('clicked_updateDetectedTagIdInViewPlayerLoginViaSameIp.fail.arguments:', arguments);
    }); // EOF _ajax.fail

    _ajax.always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
        /// revert loader
        $('#updateDetectedTagIdInViewPlayerLoginViaSameIp').button('reset');
        // console.log('clicked_cashbackAmountDetail.always.arguments:', arguments);
    }); // EOF _ajax.always
}; // EOF clicked_updateDetectedTagIdInViewPlayerLoginViaSameIp


