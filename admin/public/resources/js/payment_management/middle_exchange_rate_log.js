
/**
 * MiddleExchangeRateLog Class
 *
 */
var MiddleExchangeRateLog = MiddleExchangeRateLog || {};

MiddleExchangeRateLog.initialize = function (options) {
    var _this = this;
    _this.URIs = {};
    // _this.URIs.addDetail = options.base_url + 'api/middle_exchange_rate_log';
    _this.URIs.list = options.base_url + 'api/middle_exchange_rate_log_list';

    _this.middleExchangeRateLogDefault = {};
    _this.middleExchangeRateLogDefault.id = 0;
    _this.middleExchangeRateLogDefault.status = 1;
    _this.middleExchangeRateLogDefault.rate = 0;
    // _this.middleExchangeRateLog.extra = '{"class": "Customized_definition_default"}';

    _this.langs = {};
    // _this.langs.addNewWithdrawalDefinition = options.langs.addNewWithdrawalDefinition;
    // _this.langs.viewWithdrawalDefinition = options.langs.viewWithdrawalDefinition;
    // _this.langs.whatCannotBeEmpty = options.langs.whatCannotBeEmpty;

    _this = $.extend(true, {}, _this, options);

    return _this;
};

MiddleExchangeRateLog.onReady = function () {
    var _this = this;

    _this.middle_exchange_rate_log_dataTable = $('#middle_exchange_rate_log').DataTable({
        // dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
        autoWidth: false,
        searching: true,
        pageLength: _this.defaultItemsPerPage,
        // "responsive": {
        //     details: {
        //         type: 'column'
        //     }
        // },
        "dom": "<'panel-body' <'pull-right'f><'pull-right progress-container'>l>t<'panel-body'<'pull-right'p>i>",
        // buttons: [{
        //     extend: 'colvis',
        //     postfixButtons: ['colvisRestore']
        // }],
        //
        // "columnDefs": [
        //     { sortable: false, targets: [0], orderable: false },
        //     { sortable: false, targets: [2], orderable: false },
        // ],
        // "order": [1, 'asc'],

        // SERVER-SIDE PROCESSING
        processing: true,
        serverSide: true,
        ajax: function (data, callback, settings) {
            data.extra_search = $('#search-form').serializeArray();
            var ajax = $.post(_this.URIs.list, data, function (data) {
                // console.log(data);
                callback(data);
                if (_this.dispatchWithdrawalDefinition_dataTable.rows({ selected: true }).indexes().length === 0) {
                    _this.dispatchWithdrawalDefinition_dataTable.buttons().disable();
                }
                else {
                    _this.dispatchWithdrawalDefinition_dataTable.buttons().enable();
                }
            }, 'json');
            ajax.always(function () {
                // for $.button('loading');
                var selectorStrList = [];
                selectorStrList.push('.editWithdrawalDefinition');
                $(selectorStrList.join(',')).data('loading-text', '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            });
        },

    }); // EOF _this.dispatchWithdrawalDefinition_dataTable


    // // Init ACE editor for JSON
    // _this.definition_extra = ace.edit("extra");
    // _this.definition_extra.setTheme("ace/theme/tomorrow");
    // _this.definition_extra.session.setMode("ace/mode/json");

    /* Apply the tooltips */
    $('th[title]').tooltip({
        "container": 'body'
    });
    $('label[title]').tooltip({
        "container": 'body'
    });

    _this._registerEvents();

}; // EOF onReady

MiddleExchangeRateLog._registerEvents = function () {
    var _this = this;

    $('body').on('click', '.addWithdrawalRiskDefinition', function (e) {
        return _this.clicked_addWithdrawalDefinition(e);
    });


    $('#somethingWrongModal')
        // .on('show.bs.modal', function (e) {
        //     _this.show_somethingWrongModal(e);
        // })
        // .on('shown.bs.modal', function (e) {
        //     _this.shown_somethingWrongModal(e);
        // })
        // .on('hide.bs.modal', function (e) {
        //     _this.hide_somethingWrongModal(e);
        // })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_somethingWrongModal(e);
        });
};


// ======================== handle events ========================



MiddleExchangeRateLog.clicked_addWithdrawalDefinition = function (e) {
    var _this = this;
    _this.reset_withdrawalDefinitionDetail();
    _this.apply_withdrawalDefinitionDetail(_this.middleExchangeRateLogDefault);
    $('#withdrawalDefinition_detail').find('.modal-title').html(_this.langs.addNewWithdrawalDefinition);
    $('#withdrawalDefinition_detail').modal('show');
};




MiddleExchangeRateLog.reset_withdrawalDefinitionDetail = function () {
    var _this = this;
    var detail$El = $('#withdrawalDefinition_detail');
    detail$El.find('input[name="id"]').val(''); // for add
    detail$El.find('input[name="dispatch_order"]').val('0');
    detail$El.find('input[name="status"]').prop('checked', false);
    detail$El.find('input[name="status"][value=' + _this.middleExchangeRateLogDefault.status + ']').prop('checked', true);
    // clear the Not exist option.
    detail$El.find('select[name="eligible2dwStatus"]').find('option[disabled]').remove();
    detail$El.find('select[name="eligible2dwStatus"]').selectpicker('refresh');
}

MiddleExchangeRateLog.apply_withdrawalDefinitionDetail = function (data) {
    var _this = this;
    // console.log('apply_withdrawalDefinitionDetail.data:', data);
    var detail$El = $('#withdrawalDefinition_detail');

    if (data.id > 0) {
        detail$El.find('input[name="id"]').val(data.id); // for update
    } else {
        detail$El.find('input[name="id"]').val(''); // for add
    }

    detail$El.find('input[name="dispatch_order"]').val(data.dispatch_order);

    detail$El.find('input[name="name"]').val(data.name);

    detail$El.find('input[name="status"]').prop('checked', false);
    detail$El.find('input[name="status"][value=' + data.status + ']').prop('checked', true);

    // @todo OGP-18088, eligible2dwStatus + eligible2external_system_id
    // extra
    _this.definition_extra.setValue(JSON.stringify(JSON.parse(data.extra), null, 4));

    detail$El.find('input[name="dispatch_order"]').val(data.dispatch_order);

    detail$El.find('select[name="eligible2external_system_id"]').prop('disabled', true);
    detail$El.find('select[name="eligible2external_system_id"]').selectpicker('refresh');

    var eligible2dwStatus$El = detail$El.find('select[name="eligible2dwStatus"]');
    if (data.id > 0) { // for update
        if (eligible2dwStatus$El.find('option[value="' + data.eligible2dwStatus + '"]').length > 0) {
            eligible2dwStatus$El.selectpicker('val', data.eligible2dwStatus);
        } else {
            // OGP-19103 To handle the target stage does Not exist.
            // append the Not exist option.
            eligible2dwStatus$El.append('<option value="' + data.eligible2dwStatus + '">' + data.lang4eligible2dwStatus + '</option>');
            eligible2dwStatus$El.val(data.eligible2dwStatus);
            // setup selected and disabled.
            eligible2dwStatus$El.val(data.eligible2dwStatus);
            eligible2dwStatus$El.find('option[value="' + data.eligible2dwStatus + '"]').prop('disabled', true);
            // refresh UI
            eligible2dwStatus$El.selectpicker('refresh');
        }
    } else { // for add
        eligible2dwStatus$El.selectpicker('val', '');
    }

} // EOF MiddleExchangeRateLog.apply_withdrawalDefinitionDetail





MiddleExchangeRateLog.validation = function (focusSelectorStr) {
    var _this = this;
    var detail$El = $('#withdrawalDefinition_detail');
    var result = true;
    if (typeof (focusSelectorStr) === 'undefined') {
        focusSelectorStr = 'input:text,select,div#extra';
    }
    detail$El.find(focusSelectorStr).each(function () {
        var name = $(this).prop('name');
        switch (name) {
            case 'name': // input
                var tipMsg = _this.langs.whatCannotBeEmpty.replace(/%s/g, _this.langs.cmsTitle);
                var valid = _this.validationIsEmptyString($(this), tipMsg);
                result = result && valid;
                break;
            case 'dispatch_order': // input
                var tipMsg = _this.langs.whatCannotBeEmpty.replace(/%s/g, _this.langs.dispatchOrder);
                var valid = _this.validationIsEmptyString($(this), tipMsg);
                result = result && valid;

                var tipMsg = _this.langs.onlyAllowDigits;
                var valid = _this.validationIsNumberOnly($(this), tipMsg);
                result = result && valid;
                break;
            case 'eligible2dwStatus': // select
                var tipMsg = _this.langs.whatCannotBeEmpty.replace(/%s/g, _this.langs.targetState);
                var valid = _this.validationIsEmptyString($(this), tipMsg);
                result = result && valid;
                break;
            default:
                break;
        } // EOF switch (name) {...
        var idVal = $(this).prop('id');
        switch (idVal) {
            case 'extra':
                var tipMsg = _this.langs.whatCannotBeEmpty.replace(/%s/g, _this.langs.extrainfo);
                var valid = _this.validationIsEmptyString($(this), tipMsg, function (curr$El) { // validNotEmptyCB
                    var extraVal = _this.definition_extra.getValue().trim();
                    return extraVal.length > 0;
                });
                result = result && valid;

                // var valid = _this.validationExtraJson();
                // console.log('valid:', valid);
                if (!valid) {
                    var tipMsg = _this.langs.invalidJSON;
                    _this.validationDisplayInvalidPrompt($(this).parent().find('.invalid-prompt'), tipMsg);
                }
                result = result && valid;

                break;
        }
    }); // EOF detail$El.find('input:text,select').each(function () {...

    return result;
} // EOF MiddleExchangeRateLog.validation


MiddleExchangeRateLog.validationReset = function (focusSelectorStr) {
    var _this = this;
    // var detail$El = $('#withdrawalDefinition_detail');
    // if (typeof (focusSelectorStr) === 'undefined') {
    //     focusSelectorStr = 'input:text,select,div#extra';
    // }
    // detail$El.find(focusSelectorStr).each(function () {
    //     var curr$El = $(this);

    //     var invalid_prompt$El = curr$El.parent().find('.invalid-prompt');
    //     if (invalid_prompt$El.length == 0) { // for "Target State".
    //         invalid_prompt$El = curr$El.closest('.required').find('.invalid-prompt');
    //     }

    //     invalid_prompt$El.addClass('hide').html('');
    // });
} // EOF MiddleExchangeRateLog.validationReset




MiddleExchangeRateLog.validationDisplayInvalidPrompt = function (invalidPrompt$El, tipMsg) {
    var _this = this;
    if (typeof (tipMsg) === 'undefined') {
        tipMsg = '';
    }
    if (tipMsg.length > 0) {
        var invalidPrompt = invalidPrompt$El.text().trim();
        if (invalidPrompt.length > 0) {
            invalidPrompt = invalidPrompt + '<br>';
        }
        invalidPrompt$El.html(invalidPrompt + tipMsg);
        invalidPrompt$El.removeClass('hide');
    }
} // EOF MiddleExchangeRateLog.validationDisplayInvalidPrompt




// ======================== Utils ========================
MiddleExchangeRateLog.getTarget$El = function (e) {
    var _this = this;
    return $(e.target);
}

