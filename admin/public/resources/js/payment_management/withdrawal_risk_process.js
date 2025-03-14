
/**
 * WithdrawalRiskProcess Class
 *
 */
var WithdrawalRiskProcess = WithdrawalRiskProcess || {};

WithdrawalRiskProcess.initialize = function (options) {
    var _this = this;
    _this.URIs = {};
    _this.URIs.addDetail = options.base_url + 'api/dispatch_withdrawal_definition_detail';
    _this.URIs.editDetail = options.base_url + 'api/dispatch_withdrawal_definition_detail/{$id}';
    _this.URIs.getDetailById = options.base_url + 'api/dispatch_withdrawal_definition_detail/{$id}';
    _this.URIs.list = options.base_url + 'api/dispatch_withdrawal_definition_list';
    _this.URIs.dispatchOrderlist = options.base_url + 'api/dispatchOrderlist';
    _this.URIs.deleteDetailById = options.base_url + 'api/delete_dispatch_withdrawal_definition/{$id}';


    _this.withdrawalDefinitionDefault = {};
    _this.withdrawalDefinitionDefault.id = 0;
    _this.withdrawalDefinitionDefault.status = 1;
    _this.withdrawalDefinitionDefault.name = 'Withdrawal Definition at ' + moment().format('YYYY-MM-DD HH:mm:ss');
    _this.withdrawalDefinitionDefault.dispatch_order = 100;
    _this.withdrawalDefinitionDefault.eligible2dwStatus = '';
    _this.withdrawalDefinitionDefault.extra = '{"class": "Customized_definition_default"}';

    _this.langs = {};
    _this.langs.addNewWithdrawalDefinition = options.langs.addNewWithdrawalDefinition;
    _this.langs.viewWithdrawalDefinition = options.langs.viewWithdrawalDefinition;
    _this.langs.whatCannotBeEmpty = options.langs.whatCannotBeEmpty;

    _this = $.extend(true, {}, _this, options);

    return _this;
};

WithdrawalRiskProcess.onReady = function () {
    var _this = this;

    _this.dispatchWithdrawalDefinition_dataTable = $('#dispatch_withdrawal_definition_list').DataTable({
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


    // Init ACE editor for JSON
    _this.definition_extra = ace.edit("extra");
    _this.definition_extra.setTheme("ace/theme/tomorrow");
    _this.definition_extra.session.setMode("ace/mode/json");

    /* Apply the tooltips */
    $('th[title]').tooltip({
        "container": 'body'
    });
    $('label[title]').tooltip({
        "container": 'body'
    });

    _this._registerEvents();

}; // EOF onReady

WithdrawalRiskProcess._registerEvents = function () {
    var _this = this;

    $('body').on('click', '.previewDefinitionOrder', function (e) {
        return _this.clicked_previewDefinitionOrder(e);
    });


    $('body').on('click', '.addWithdrawalRiskDefinition', function (e) {
        return _this.clicked_addWithdrawalDefinition(e);
    });

    $('body').on('click', '.editWithdrawalDefinition', function (e) {
        return _this.clicked_editWithdrawalDefinition(e);
    });
    $('body').on('click', '.deleteWithdrawalDefinition', function (e) {
        return _this.clicked_deleteWithdrawalDefinition(e);
    });
    $('body').on('click', '#saveWithdrawalDefinitionDetail', function (e) {
        return _this.clicked_saveWithdrawalDefinitionDetail(e);
    });
    $('body').on('click', '#deleteWithdrawalDefinitionDetail', function (e) {
        return _this.clicked_deleteWithdrawalDefinitionDetail(e);
    });
    $('body').on('change', 'select[name="eligible2dwStatus"]', function (e) {
        // console.log('aa', e, $('select[name="eligible2dwStatus"]').val());
        return _this.clicked_changeEligible2dwStatus(e);
    });


    $('#withdrawalDefinition_detail')
        .on('show.bs.modal', function (e) {
            _this.show_withdrawalDefinition_detail(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_withdrawalDefinition_detail(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_withdrawalDefinition_detail(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_withdrawalDefinition_detail(e);
        });
    $('#deleteWithdrawalDefinitionModal')
        .on('show.bs.modal', function (e) {
            _this.show_deleteWithdrawalDefinitionModal(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_deleteWithdrawalDefinitionModal(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_deleteWithdrawalDefinitionModal(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_deleteWithdrawalDefinitionModal(e);
        });
    $('#previewDefinitionOrderModal')
        .on('show.bs.modal', function (e) {
            _this.show_previewDefinitionOrderModal(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_previewDefinitionOrderModal(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_previewDefinitionOrderModal(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_previewDefinitionOrderModal(e);
        });
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
};


// ======================== handle events ========================
WithdrawalRiskProcess.show_withdrawalDefinition_detail = function (e) {
    var _this = this;
    _this.validationReset();
};
WithdrawalRiskProcess.shown_withdrawalDefinition_detail = function (e) {
    var _this = this;

    _this.form_serialize = $('#withdrawalDefinition_detail_form').serializeArray();
    // console.log('a', _this.form_serialize);
};
WithdrawalRiskProcess.hide_withdrawalDefinition_detail = function (e) {
    // console.log('ccc', e);
};
WithdrawalRiskProcess.hidden_withdrawalDefinition_detail = function (e) {
    // console.log('ddd', e);
};

// for #deleteWithdrawalDefinitionModal
WithdrawalRiskProcess.show_deleteWithdrawalDefinitionModal = function (e) {
    // console.log('aaa2', e);
};
WithdrawalRiskProcess.shown_deleteWithdrawalDefinitionModal = function (e) {
    // console.log('aaa3', e);
};
WithdrawalRiskProcess.hide_deleteWithdrawalDefinitionModal = function (e) {
    // console.log('aaa4', e);
};
WithdrawalRiskProcess.hidden_deleteWithdrawalDefinitionModal = function (e) {
    // console.log('aaa5', e);
};

// for #previewDefinitionOrderModal
WithdrawalRiskProcess.show_previewDefinitionOrderModal = function (e) {
    var _this = this;
    var tableId = 'dispatch_withdrawal_definition_order_preview';
    if ($('#dispatch_withdrawal_definition_order_preview_wrapper').length == 0) {
        _this.previewDefinitionOrderModal_dataTable = $('#dispatch_withdrawal_definition_order_preview').DataTable({
            autoWidth: false,
            searching: true,
            pageLength: _this.defaultItemsPerPage,
            "dom": "<'panel-body' <'pull-right'f><'pull-right progress-container'>l>t<'panel-body'<'pull-right'p>i>",
            "columnDefs": [{
                className: 'control',
                orderable: false,
                targets: 0
            }],
            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                //
                var searchForm$El = $('#previewDefinitionOrder-search-form');
                data.extra_search = searchForm$El.serializeArray();
                var ajax = $.post(_this.URIs.dispatchOrderlist, data, function (data) {
                    // console.log(data);
                    data.extra_search = searchForm$El.serializeArray(); // for definition_id
                    callback(data);
                    if (_this.previewDefinitionOrderModal_dataTable.rows({ selected: true }).indexes().length === 0) {
                        _this.previewDefinitionOrderModal_dataTable.buttons().disable();
                    }
                    else {
                        _this.previewDefinitionOrderModal_dataTable.buttons().enable();
                    }
                }, 'json');

                // ajax.always(function () {
                //     // for $.button('loading');
                //     var selectorStrList = [];
                //     selectorStrList.push('.editWithdrawalCondition');
                //     $(selectorStrList.join(',')).data('loading-text', '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                // });
            },
        });

        initBarLoader(tableId); // for .progress-container'
    } // EOF if($('#dispatch_withdrawal_definition_order_preview_wrapper').length == 0)



};
WithdrawalRiskProcess.shown_previewDefinitionOrderModal = function (e) {
    var _this = this;
    _this.previewDefinitionOrderModal_dataTable.ajax.reload(null, false); // user paging is not reset on reload
};
WithdrawalRiskProcess.hide_previewDefinitionOrderModal = function (e) {

};
WithdrawalRiskProcess.hidden_previewDefinitionOrderModal = function (e) {

};

WithdrawalRiskProcess.show_somethingWrongModal = function (e) {

};
WithdrawalRiskProcess.shown_somethingWrongModal = function (e) {

};
WithdrawalRiskProcess.hide_somethingWrongModal = function (e) {
    location.reload();
};
WithdrawalRiskProcess.hidden_somethingWrongModal = function (e) {

};

WithdrawalRiskProcess.clicked_addWithdrawalDefinition = function (e) {
    var _this = this;
    _this.reset_withdrawalDefinitionDetail();
    _this.apply_withdrawalDefinitionDetail(_this.withdrawalDefinitionDefault);
    $('#withdrawalDefinition_detail').find('.modal-title').html(_this.langs.addNewWithdrawalDefinition);
    $('#withdrawalDefinition_detail').modal('show');
};
WithdrawalRiskProcess.clicked_editWithdrawalDefinition = function (e) {
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var targetBtn$El = target$El.closest('td').find('.editWithdrawalDefinition');

    // console.log('target$El', target$El);
    var id = target$El.closest('.editWithdrawalDefinition').find('[data-detail-id]').data('detail-id');
    // var tr = target$El.closest('[role="row"]');
    // console.log( 'tr.data', _this.dispatchWithdrawalDefinition_dataTable.row( tr ).data() );
    var theUri = _this.URIs.getDetailById;
    theUri = theUri.replace(/{\$id}/gi, id);
    var jqXHR = $.ajax({
        type: 'GET', // for get a detail
        url: theUri,
        data: {},
        beforeSend: function () {
            targetBtn$El.closest('[data-toggle="tooltip"]').tooltip('hide');
            targetBtn$El.button('loading');
            _this.reset_withdrawalDefinitionDetail();
        },
        complete: function () {
            targetBtn$El.button('reset');
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.apply_withdrawalDefinitionDetail(data);
        $('#withdrawalDefinition_detail').find('.modal-title').html(_this.langs.viewWithdrawalDefinition);
        $('#withdrawalDefinition_detail').modal('show');
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        if (errorThrown == 'Forbidden') {
            $('#somethingWrongModal').modal('show');
        }
    });

};

WithdrawalRiskProcess.clicked_deleteWithdrawalDefinitionDetail = function (e) {
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var targetBtn$El = $('#deleteWithdrawalDefinitionDetail');
    var id = target$El.closest('div').find('.deleteWithdrawalDefinitionId').val();

    var theUri = _this.URIs.deleteDetailById;
    if (id > 0) { // for delete
        theUri = theUri.replace(/{\$id}/gi, id);
    }

    var jqXHR = $.ajax({
        type: 'POST', // for add a detail
        url: theUri,
        // data: theData,
        beforeSend: function () {
            targetBtn$El.button('loading');
        },
        complete: function () {
            targetBtn$El.button('reset');
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.dispatchWithdrawalDefinition_dataTable.ajax.reload(null, false); // user paging is not reset on reload
        $('#deleteWithdrawalDefinitionModal').modal('hide');
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        if (errorThrown == 'Forbidden') {
            $('#somethingWrongModal').modal('show');
        }
    });
};

WithdrawalRiskProcess.clicked_saveWithdrawalDefinitionDetail = function (e) {
    var _this = this;
    var theUri = _this.URIs.addDetail;
    var target$El = _this.getTarget$El(e);

    var serializeData = $('#withdrawalDefinition_detail_form').serializeArray();
    var extraInfo = _this.definition_extra.getValue();
    serializeData.push({ name: "extra", value: extraInfo });

    var id = target$El.closest('#withdrawalDefinition_detail_form').find('[name="id"]').val();
    if (id > 0) { // for update
        theUri = _this.URIs.editDetail;
        theUri = theUri.replace(/{\$id}/gi, id);
        serializeData.push({ name: "id", value: id }); // for update
    }


    // var theData = JSON.stringify( serializeData );
    var theData = _this.getFormData(serializeData);

    // if (!_this.isJsonString(_this.definition_extra.getValue())) {
    //     // @todo OGP-18088 驗證 json
    //     $('#extraInvalidJson').removeClass('hide');
    // } else {
    //     $('#extraInvalidJson').addClass('hide');
    // }
    var theButton$El = $('#saveWithdrawalDefinitionDetail');

    var jqXHR = $.ajax({
        type: 'POST', // for add/update a detail
        url: theUri,
        data: theData,
        beforeSend: function () {
            _this.validationReset();
            var valid = _this.validation();
            // _this.validationExtraJson(); // @todo

            if (valid) {
                theButton$El.button('loading');
            }
            return valid;
        },
        complete: function () {
            theButton$El.button('reset');
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.dispatchWithdrawalDefinition_dataTable.ajax.reload(null, false); // user paging is not reset on reload
        $('#withdrawalDefinition_detail').modal('hide');
        _this.validationReset();
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        if (errorThrown == 'Forbidden') {
            $('#somethingWrongModal').modal('show');
        }
    });

};

WithdrawalRiskProcess.clicked_deleteWithdrawalDefinition = function (e) {
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var id = target$El.closest('tr').find('[data-detail-id]').data('detail-id');
    // var targetBtn$El = target$El.closest('tr').find('.deleteWithdrawalDefinition');

    // console.log('clicked_deleteWithdrawalDefinition.target', target$El);
    // console.log('clicked_deleteWithdrawalDefinition.id', id);
    $('.deleteWithdrawalDefinitionId').val(id);
    $('#deleteWithdrawalDefinitionModal').modal('show');

};

// helper
WithdrawalRiskProcess.isJsonString = function (str) {
    if (str == '') return true;
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

/**
 * reference to https://stackoverflow.com/a/11339012
 * @param {array} unindexed_array
 */
WithdrawalRiskProcess.getFormData = function (unindexed_array) {

    var indexed_array = {};

    $.map(unindexed_array, function (n, i) {
        indexed_array[n['name']] = n['value'];
    });

    return indexed_array;
}

WithdrawalRiskProcess.reset_withdrawalDefinitionDetail = function () {
    var _this = this;
    var detail$El = $('#withdrawalDefinition_detail');
    detail$El.find('input[name="id"]').val(''); // for add
    detail$El.find('input[name="dispatch_order"]').val('0');
    detail$El.find('input[name="status"]').prop('checked', false);
    detail$El.find('input[name="status"][value=' + _this.withdrawalDefinitionDefault.status + ']').prop('checked', true);
    // clear the Not exist option.
    detail$El.find('select[name="eligible2dwStatus"]').find('option[disabled]').remove();
    detail$El.find('select[name="eligible2dwStatus"]').selectpicker('refresh');
}

WithdrawalRiskProcess.apply_withdrawalDefinitionDetail = function (data) {
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

} // EOF WithdrawalRiskProcess.apply_withdrawalDefinitionDetail

WithdrawalRiskProcess.clicked_changeEligible2dwStatus = function (e) {
    var _this = this;
    var detail$El = $('#withdrawalDefinition_detail');

    var target$El = _this.getTarget$El(e);
    var disabled4external_system_id = true;
    if (target$El.val() == 'withdrawalAPI') { // will payProc
        disabled4external_system_id = false;
    } else {

    }
    detail$El.find('select[name="eligible2external_system_id"]')
        .prop('disabled', disabled4external_system_id)
        .selectpicker('refresh');

}

WithdrawalRiskProcess.clicked_previewDefinitionOrder = function (e) {
    var _this = this;
    $('#previewDefinitionOrderModal').modal('show');
};

WithdrawalRiskProcess.validation = function (focusSelectorStr) {
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

                var valid = _this.validationExtraJson();
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
} // EOF WithdrawalRiskProcess.validation


WithdrawalRiskProcess.validationReset = function (focusSelectorStr) {
    var _this = this;
    var detail$El = $('#withdrawalDefinition_detail');
    if (typeof (focusSelectorStr) === 'undefined') {
        focusSelectorStr = 'input:text,select,div#extra';
    }
    detail$El.find(focusSelectorStr).each(function () {
        var curr$El = $(this);

        var invalid_prompt$El = curr$El.parent().find('.invalid-prompt');
        if (invalid_prompt$El.length == 0) { // for "Target State".
            invalid_prompt$El = curr$El.closest('.required').find('.invalid-prompt');
        }

        invalid_prompt$El.addClass('hide').html('');
    });
} // EOF WithdrawalRiskProcess.validationReset

WithdrawalRiskProcess.validationIsNumberOnly = function (the$El, tipMsg) {
    var _this = this;
    var returnBool = false;

    if (!isNaN(the$El.val())) {
        returnBool = true;
    }
    if (!returnBool) {
        _this.validationDisplayInvalidPrompt(the$El.parent().find('.invalid-prompt'), tipMsg);
    }
    return returnBool;
} // EOF WithdrawalRiskProcess.validationIsNumberOnly


WithdrawalRiskProcess.validationIsEmptyString = function (the$El, tipMsg, validNotEmptyCB) {
    var _this = this;
    var returnBool = false;

    if (typeof (validNotEmptyCB) === 'undefined') {
        validNotEmptyCB = function (curr$El) {
            var currVal = curr$El.val();
            var ck_length = 0
            if (!$.isEmptyObject(currVal)) {
                ck_length = currVal.trim().length;
            }
            return ck_length > 0;
        }
    }
    if (validNotEmptyCB(the$El)) {
        returnBool = true;
    }
    if (!returnBool) {

        var invalid_prompt$El = the$El.parent().find('.invalid-prompt');
        if (invalid_prompt$El.length == 0) { // for "Target State".
            invalid_prompt$El = the$El.closest('.required').find('.invalid-prompt');
        }

        _this.validationDisplayInvalidPrompt(invalid_prompt$El, tipMsg);
    }


    // if (typeof (tipMsg) === 'undefined') {
    //     tipMsg = '';
    // }
    // if (tipMsg.length > 0) {
    //     var invalidPrompt$El = the$El.parent().find('.invalid-prompt');
    //     var invalidPrompt = invalidPrompt$El.text().trim();
    //     if (invalidPrompt.length > 0) {
    //         invalidPrompt = invalidPrompt + '<br>';
    //     }
    //     invalidPrompt$El.html(invalidPrompt + tipMsg);
    //     invalidPrompt$El.removeClass('hide');
    // }

    return returnBool;
} // EOF WithdrawalRiskProcess.validationIsEmptyString

WithdrawalRiskProcess.validationDisplayInvalidPrompt = function (invalidPrompt$El, tipMsg) {
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
} // EOF WithdrawalRiskProcess.validationDisplayInvalidPrompt

// WithdrawalRiskProcess.validationExtraEmpty = function () {
//     var validationResult = false;
//     var extraVal = _this.definition_extra.getValue().trim();
//     if (extraVal.length > 0) {
//         validationResult = true;
//     }
//     return validationResult;
// }

WithdrawalRiskProcess.validationExtraJson = function () {
    var _this = this;
    var validationResult = false;
    if (!_this.isJsonString(_this.definition_extra.getValue())) {
        // OGP-18088 驗證 json
        // $('#extraInvalidJson').removeClass('hide');
        validationResult = false;
    } else {
        // $('#extraInvalidJson').addClass('hide');
        validationResult = true;
    }
    return validationResult;
} // EOF WithdrawalRiskProcess.validationExtraJson


// ======================== Utils ========================
WithdrawalRiskProcess.getTarget$El = function (e) {
    var _this = this;
    return $(e.target);
}

