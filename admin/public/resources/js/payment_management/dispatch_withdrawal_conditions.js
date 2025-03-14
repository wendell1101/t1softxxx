
/**
 * DispatchWithdrawalConditions Class
 *
 */
var DispatchWithdrawalConditions = DispatchWithdrawalConditions || {};
DispatchWithdrawalConditions.initialize = function (options) {
    var _this = this;
    _this.base_url = '/';
    _this.defaultItemsPerPage = 12;

    _this.URIs = {};
    _this.URIs.list = options.base_url + 'api/dispatch_withdrawal_condition_list';
    _this.URIs.addDetail = options.base_url + 'api/dispatch_withdrawal_condition_detail';
    _this.URIs.editDetail = options.base_url + 'api/dispatch_withdrawal_condition_detail/{$id}';
    _this.URIs.getDetailById = options.base_url + 'api/dispatch_withdrawal_condition_detail/{$id}';
    _this.URIs.deleteDetailById = options.base_url + 'api/delete_dispatch_withdrawal_condition/{$id}';
    _this.URIs.get_game_tree_by_condition = options.base_url + 'api/get_game_tree_by_condition/{$id}';
    _this.URIs.withdrawalRiskProcessList = options.base_url + 'payment_management/withdrawal_risk_process_list';

    _this.langs = {};
    _this.langs.addNewWithdrawalCondition = options.langs.addNewWithdrawalCondition;
    _this.langs.viewWithdrawalCondition = options.langs.viewWithdrawalCondition;


    _this.isEnable2Off = '0';
    _this.isEnable2On = '1';

    _this.withdrawalConditionDefault = {};
    _this.withdrawalConditionDefault.id = 0;
    _this.withdrawalConditionDefault.status = 1;
    _this.withdrawalConditionDefault.name = 'New Condition';

    _this.withdrawalConditionDefault.includedGameType_isEnable = 0;

    // _this.withdrawalConditionDefault.includedPlayerTag_isEnable = 0;
    // _this.withdrawalConditionDefault.includedPlayerTag_list = '1,2';
    _this.withdrawalConditionDefault.excludedPlayerTag_isEnable = 0;
    _this.withdrawalConditionDefault.excludedPlayerTag_list = '3,4';

    _this.withdrawalConditionDefault.excludedPlayerLevels_isEnable = 0;
    _this.withdrawalConditionDefault.excludedPlayerLevel_list = '';

    /// ignore, same as calcAvailableBetOnly_isEnable
    // _this.withdrawalConditionDefault.availableBetOnly_isEnable = 0;

    _this.withdrawalConditionDefault.gameRevenuePercentage_isEnable = 0;
    _this.withdrawalConditionDefault.gameRevenuePercentage_rate = 12.3;
    _this.withdrawalConditionDefault.gameRevenuePercentage_symbol = -1;

    _this.withdrawalConditionDefault.todayWithdrawalCount_isEnable = 0;
    _this.withdrawalConditionDefault.todayWithdrawalCount_limit = 23;
    _this.withdrawalConditionDefault.todayWithdrawalCount_symbol = -2;

    _this.withdrawalConditionDefault.afterDepositWithdrawalCount_isEnable = 0;
    _this.withdrawalConditionDefault.afterDepositWithdrawalCount_limit = 23;
    _this.withdrawalConditionDefault.afterDepositWithdrawalCount_symbol = -2;

    _this.withdrawalConditionDefault.withdrawalAmount_isEnable = 0;
    _this.withdrawalConditionDefault.withdrawalAmount_limit = 34;
    _this.withdrawalConditionDefault.withdrawalAmount_symbol = 0;

    _this.withdrawalConditionDefault.winAndDepositRate_isEnable = 0;
    _this.withdrawalConditionDefault.winAndDepositRate_rate = 45;
    _this.withdrawalConditionDefault.winAndDepositRate_symbol = 1;

    _this.withdrawalConditionDefault.betAndWithdrawalRate_isEnable = 0;
    _this.withdrawalConditionDefault.betAndWithdrawalRate_rate = 100;
    _this.withdrawalConditionDefault.betAndWithdrawalRate_symbol = 1;

    _this.withdrawalConditionDefault.totalDepositCount_isEnable = 0;
    _this.withdrawalConditionDefault.totalDepositCount_limit = 56;
    _this.withdrawalConditionDefault.totalDepositCount_symbol = 0;


    _this.withdrawalConditionDefault.calcAvailableBetOnly_isEnable = 0;
    _this.withdrawalConditionDefault.calcEnabledGameOnly_isEnable = 0;
    _this.withdrawalConditionDefault.ignoreCanceledGameLogs_isEnable = 0;
    _this.withdrawalConditionDefault.calcPromoDepositOnly_isEnable = 0;
    _this.withdrawalConditionDefault.noDuplicateFirstNames_isEnable = 0;
    _this.withdrawalConditionDefault.noDuplicateLastNames_isEnable = 0;
    _this.withdrawalConditionDefault.noDuplicateAccounts_isEnable = 0;

    _this.withdrawalConditionDefault.noDepositWithPromo_isEnable = 0;
    _this.withdrawalConditionDefault.noAddBonusSinceTheLastWithdrawal_isEnable = 0;
    _this.withdrawalConditionDefault.thePlayerHadExistsInIovation_isEnable = 0;
    _this.withdrawalConditionDefault.theTotalBetGreaterOrEqualRequired_isEnable = 0;

    _this = $.extend(true, {}, _this, options);
    return _this;
};

DispatchWithdrawalConditions.onReady = function () {
    var _this = this;
    _this.dataTable = $('#dispatch_withdrawal_condition_list').DataTable({
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
            data.extra_search = $('#search-form').serializeArray();
            var ajax = $.post(_this.URIs.list, data, function (data) {
                // console.log(data);
                data.extra_search = $('#search-form').serializeArray(); // for definition_id
                callback(data);
                if (_this.dataTable.rows({ selected: true }).indexes().length === 0) {
                    _this.dataTable.buttons().disable();
                }
                else {
                    _this.dataTable.buttons().enable();
                }
            }, 'json');

            ajax.always(function () {
                // for $.button('loading');
                var selectorStrList = [];
                selectorStrList.push('.editWithdrawalCondition');
                $(selectorStrList.join(',')).data('loading-text', '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            });
        },
    }); // EOF _this.dataTable

    _this._registerEvents();

} // EOF onReady

DispatchWithdrawalConditions._registerEvents = function () {
    var _this = this;

    $('body').on('click', '.deleteWithdrawalCondition', function (e) {
        return _this.clicked_deleteWithdrawalCondition(e);
    });
    $('body').on('click', '#deleteWithdrawalConditionDetail', function (e) {
        return _this.clicked_deleteWithdrawalConditionDetail(e); // @todo
    });

    $('body').on('click', '.editWithdrawalCondition', function (e) {
        return _this.clicked_editWithdrawalCondition(e);
    });

    $('body').on('click', '#saveWithdrawalConditionDetail', function (e) {
        return _this.clicked_saveWithdrawalConditionDetail(e);
    });


    $('body').on('click', '.addWithdrawalCondition', function (e) {
        return _this.clicked_addWithdrawalCondition(e);
    });

    $('body').on('click', '.backToWithdrawalRiskProcessList', function (e) {
        return _this.clicked_backToWithdrawalRiskProcessList(e);
    });

    // $('body').on('select_node.jstree', '#includedGameTypeTree', function (node, selected, e) {
    //     var selected_nodes = _this.includedGameTypeTree$El.jstree('get_checked');
    //     console.log('selected_nodes', selected_nodes);
    // });



    $('#withdrawalCondition_detail')
        .on('show.bs.modal', function (e) {
            _this.show_withdrawalCondition_detail(e); // @todo
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_withdrawalCondition_detail(e); // @todo
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_withdrawalCondition_detail(e); // @todo
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_withdrawalCondition_detail(e); // @todo
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

    /// other Conditions
    var selectorStrList = [];
    selectorStrList.push('#withdrawalCondition_detail div.btn-group:has(select[name="withdrawalAmount_symbol"])');
    selectorStrList.push('#withdrawalCondition_detail div.btn-group:has(select[name="todayWithdrawalCount_symbol"])');
    selectorStrList.push('#withdrawalCondition_detail div.btn-group:has(select[name="afterDepositWithdrawalCount_symbol"])');
    selectorStrList.push('#withdrawalCondition_detail div.btn-group:has(select[name="gameRevenuePercentage_symbol"])');
    $('body').on('rendered.bs.select', selectorStrList.join(','), function (e) {
        // $('body').on('shown.bs.select', selectorStrList.join(','), function (e) {
        var args = Array.prototype.slice.call(arguments); // clone arguments
        return _this.rendered_selectpicker.apply(_this, args); // call mothed by _this
    });


    _this.initial_MultiselectTags();
    _this.initial_MultiselectLevels();
    _this.initial_BootstrapSwitch();

}; // EOF _registerEvents()

// ======================== handle events ========================


DispatchWithdrawalConditions.rendered_selectpicker = function (e) {
    var _this = this;
    // console.log('rendered_selectpicker.args', arguments);
    var target$El = $(e.target);
    // console.log('rendered_selectpicker.target', target$El);
    target$El.closest('div.btn-group').find('.btn.dropdown-toggle').addClass('btn-xs');

};

DispatchWithdrawalConditions.clicked_backToWithdrawalRiskProcessList = function (e) {
    var _this = this;
    location.href = _this.URIs.withdrawalRiskProcessList
};

DispatchWithdrawalConditions.clicked_addWithdrawalCondition = function (e) {
    var _this = this;

    _this.apply_withdrawalConditionDetail(_this.withdrawalConditionDefault);

    $('#withdrawalCondition_detail').find('.modal-title').html(_this.langs.addNewWithdrawalCondition);
    $('#withdrawalCondition_detail').modal('show');
};

DispatchWithdrawalConditions.clicked_deleteWithdrawalCondition = function (e) {

    var _this = this;
    var target$El = _this.getTarget$El(e);
    var id = target$El.closest('tr').find('[data-detail-id]').data('detail-id');
    // var targetBtn$El = target$El.closest('tr').find('.deleteWithdrawalDefinition');

    // console.log('clicked_deleteWithdrawalCondition.target', target$El);
    // console.log('clicked_deleteWithdrawalCondition.id', id);
    $('.deleteWithdrawalConditionId').val(id);
    $('#deleteWithdrawalConditionModal').modal('show');

};
DispatchWithdrawalConditions.clicked_deleteWithdrawalConditionDetail = function (e) {
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var targetBtn$El = target$El.closest('td').find('.deleteWithdrawalCondition');

    var id = target$El.closest('div').find('.deleteWithdrawalConditionId').val();
    var theUri = _this.URIs.deleteDetailById;

    if (id > 0) { // for delete
        theUri = theUri.replace(/{\$id}/gi, id);
    }

    var jqXHR = $.ajax({
        type: 'POST', // for delete a detail
        url: theUri,
        data: {},
        beforeSend: function () {
            targetBtn$El.button('loading');
        },
        complete: function () {
            targetBtn$El.button('reset');
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.dataTable.ajax.reload(null, false); // user paging is not reset on reload
        $('#deleteWithdrawalConditionModal').modal('hide');
    });
}; // EOF clicked_deleteWithdrawalDefinitionDetail

DispatchWithdrawalConditions.clicked_editWithdrawalCondition = function (e) {
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var targetBtn$El = target$El.closest('td').find('.editWithdrawalCondition');
    var id = target$El.closest('.editWithdrawalCondition').find('[data-detail-id]').data('detail-id');
    var theUri = _this.URIs.getDetailById;
    theUri = theUri.replace(/{\$id}/gi, id);
    var jqXHR = $.ajax({
        type: 'GET', // for get a detail
        url: theUri,
        data: {},
        beforeSend: function () {
            targetBtn$El.closest('[data-toggle="tooltip"]').tooltip('hide');
            targetBtn$El.button('loading');
        },
        complete: function () {
            targetBtn$El.button('reset');
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.apply_withdrawalConditionDetail(data);
        $('#withdrawalCondition_detail').find('.modal-title').html(_this.langs.viewWithdrawalCondition);
        $('#withdrawalCondition_detail').modal('show');
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        if (errorThrown == 'Forbidden') {
            $('#somethingWrongModal').modal('show');
        }
    });
}; // EOF clicked_editWithdrawalCondition

DispatchWithdrawalConditions.clicked_saveWithdrawalConditionDetail = function (e) {
    var _this = this;
    var theUri = _this.URIs.addDetail;
    var target$El = _this.getTarget$El(e);

    var form$El = $('#withdrawalCondition_detail_form');

    var serializeData = form$El.serializeArray();
    // var extraInfo =  _this.definition_extra.getValue();
    // serializeData.push({name: "extra", value: extraInfo});

    // for dispatch_withdrawal_definition_id
    serializeData.push({ name: "dispatch_withdrawal_definition_id", value: $("input[name='definition_id']").val() });

    // Patch for  serializeArray() will ignore the settings, the checkbox had turn off.
    /// ref. to https://stackoverflow.com/q/24338177
    form$El.find('input[type="checkbox"]:not(:checked)').each(function () {
        serializeData.push({ name: this.name, value: _this.isEnable2Off });
    });


    var id = target$El.closest('#withdrawalCondition_detail_form').find('[name="id"]').val();
    if (id > 0) { // for update
        theUri = _this.URIs.editDetail;
        theUri = theUri.replace(/{\$id}/gi, id);
        serializeData.push({ name: "id", value: id }); // for update
    }
    // console.log('227.serializeData:', serializeData);
    // var theData = JSON.stringify( serializeData );
    var theData = _this.getFormData(serializeData);
    // console.log('227.theData:', theData);

    var jqXHR = $.ajax({
        type: 'POST', // for add/update a detail
        url: theUri,
        data: theData,
        beforeSend: function () {
            _this.validationReset();
            var valid = _this.validation();
            if (valid) {
                $('#saveWithdrawalConditionDetail').button('loading');
            }
            return valid;
        },
        complete: function () {
            $('#saveWithdrawalConditionDetail').button('reset');
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.dataTable.ajax.reload(null, false); // user paging is not reset on reload
        $('#withdrawalCondition_detail').modal('hide');
        _this.validationReset();
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        if (errorThrown == 'Forbidden') {
            $('#somethingWrongModal').modal('show');
        }
    });
}; // EOF clicked_saveWithdrawalConditionDetail


DispatchWithdrawalConditions.show_withdrawalCondition_detail = function (e) {
    var _this = this;
    _this.validationReset();
    // console.log('aaa22', e);
    // _this.withdrawalCondition_detail_form_serialize = $('#withdrawalCondition_detail_form').serializeArray();

};
DispatchWithdrawalConditions.shown_withdrawalCondition_detail = function (e) {
    // console.log('aaa33', e);
};
DispatchWithdrawalConditions.hide_withdrawalCondition_detail = function (e) {
    // console.log('aaa44', e);
};
DispatchWithdrawalConditions.hidden_withdrawalCondition_detail = function (e) {
    var _this = this;
    // console.log('_this.includedGameTypeTree$El.jstree:', _this.includedGameTypeTree$El.jstree);
    _this.includedGameTypeTree$El.jstree('destroy');
    setTimeout(function () {
        _this.on_destroyInJstree();
        // console.log('_this.includedGameTypeTree$El.jstree2:', _this.includedGameTypeTree$El.jstree);
    }, 10);

};

DispatchWithdrawalConditions.show_somethingWrongModal = function (e) {

};
DispatchWithdrawalConditions.shown_somethingWrongModal = function (e) {

};
DispatchWithdrawalConditions.hide_somethingWrongModal = function (e) {
    location.reload();
};
DispatchWithdrawalConditions.hidden_somethingWrongModal = function (e) {

};
// ======================== helper ========================

DispatchWithdrawalConditions.validationReset = function (focusSelectorStr) {
    var _this = this;
    var detail$El = $('#withdrawalCondition_detail');
    if (typeof (focusSelectorStr) === 'undefined') {
        focusSelectorStr = 'input[type="text"],#tag_list,#includedGameTypeTree';
    }
    detail$El.find(focusSelectorStr).each(function () {
        var curr$El = $(this);
        curr$El.closest('.row').find('.invalid-prompt').addClass('hide').html('');
    });
} // EOF DispatchWithdrawalConditions.validationReset

DispatchWithdrawalConditions.validation = function (focusSelectorStr) {
    var _this = this;
    var detail$El = $('#withdrawalCondition_detail');
    var result = true;
    if (typeof (focusSelectorStr) === 'undefined') {
        focusSelectorStr = 'input[type="text"],#tag_list,#includedGameTypeTree';
    }
    detail$El.find(focusSelectorStr).each(function () {
        var name = $(this).prop('name');
        switch (name) {
            case 'name': // input
                var tipMsg = _this.langs.whatCannotBeEmpty.replace(/%s/g, _this.langs.cmsTitle);
                var valid = _this.validationIsEmptyString($(this), tipMsg);
                result = result && valid;
                break;

            case 'totalDepositCount_limit': // input
            case 'betAndWithdrawalRate_rate': // input
            case 'winAndDepositRate_rate': // input
            case 'withdrawalAmount_limit': // input
            case 'todayWithdrawalCount_limit': // input
            case 'gameRevenuePercentage_rate': // input
                var tipMsg = _this.langs.default_html5_required_error_message.replace(/%s/g, '');
                var valid = _this.validationIsEmptyString($(this), tipMsg);
                result = result && valid;

                var tipMsg = _this.langs.onlyAllowDigits;
                var valid = _this.validationIsNumberOnly($(this), tipMsg);
                result = result && valid;
                break;

            default:
                break;
        } // EOF switch (name) {...

        var idVal = $(this).prop('id');
        switch (idVal) {
            case 'level_list': // select
                // excludedPlayerLevels_isEnable = 1 MUST be selected
                if ($('input:checkbox[name="excludedPlayerLevels_isEnable"]:checked').length > 0) {
                    if ($('select[id="level_list"] option:selected').length == 0) {
                        var tipMsg = _this.langs.whatCannotBeEmpty.replace(/%s/g, _this.langs.exceptionPlayerTags);
                        _this.validationDisplayInvalidPrompt($(this).closest('.row').find('.invalid-prompt'), tipMsg);
                        result = result && false; // not Loaded
                    }
                }
                break;

            case 'tag_list': // select
                // excludedPlayerTag_isEnable = 1 MUST be selected
                if ($('input:checkbox[name="excludedPlayerTag_isEnable"]:checked').length > 0) {
                    if ($('select[id="tag_list"] option:selected').length == 0) {
                        var tipMsg = _this.langs.whatCannotBeEmpty.replace(/%s/g, _this.langs.exceptionPlayerTags);
                        _this.validationDisplayInvalidPrompt($(this).closest('.row').find('.invalid-prompt'), tipMsg);
                        result = result && false; // not Loaded
                    }
                }
                break;
            case 'includedGameTypeTree': // jstree
                // console.log('_this.gameTypeTreeLoaded:', _this.gameTypeTreeLoaded);
                if (typeof (_this.gameTypeTreeLoaded) === 'undefined') {
                    // validation NG
                    // $('input[name="selected_game_tree"]')
                    var tipMsg = _this.langs.gameTypeTreeNotInitialized;
                    _this.validationDisplayInvalidPrompt($(this).closest('.row').find('.invalid-prompt'), tipMsg);

                    result = result && false; // not initialized
                } else if (_this.gameTypeTreeLoaded !== true) {
                    // validation NG
                    var tipMsg = _this.langs.gameTypeTreeNotLoaded;
                    _this.validationDisplayInvalidPrompt($(this).closest('.row').find('.invalid-prompt'), tipMsg);
                    result = result && false; // not Loaded
                } else {
                    // includedGameType_isEnable = 1 MUST be selected
                    if ($('input:checkbox[name="includedGameType_isEnable"]:checked').length > 0) {
                        if ($('input[name="selected_game_tree"]').val().length == 0) {
                            var tipMsg = _this.langs.whatCannotBeEmpty.replace(/%s/g, _this.langs.allowedGameType);
                            _this.validationDisplayInvalidPrompt($(this).closest('.row').find('.invalid-prompt'), tipMsg);
                            result = result && false; // not Loaded
                        }
                    }
                }
                break;
        } // EOF switch (idVal) {...

    }); // EOF detail$El.find('input:text,select').each(function () {...
    return result;
} // EOF DispatchWithdrawalConditions.validation

DispatchWithdrawalConditions.validationIsNumberOnly = function (the$El, tipMsg) {
    var _this = this;
    var returnBool = false;

    if (!isNaN(the$El.val())) {
        returnBool = true;
    }
    if (!returnBool) {
        _this.validationDisplayInvalidPrompt(the$El.closest('.row').find('.invalid-prompt'), tipMsg);
    }
    return returnBool;
}// EOF DispatchWithdrawalConditions.validationIsNumberOnly

DispatchWithdrawalConditions.validationIsEmptyString = function (the$El, tipMsg, validNotEmptyCB) {
    var _this = this;
    var returnBool = false;

    if (typeof (validNotEmptyCB) === 'undefined') {
        validNotEmptyCB = function (curr$El) {
            return curr$El.val().trim().length > 0;
        }
    }
    if (validNotEmptyCB(the$El)) {
        returnBool = true;
    }
    if (!returnBool) {
        // console.log('the$El', the$El.val().trim(), the$El.closest('.row').find('.invalid-prompt'));
        _this.validationDisplayInvalidPrompt(the$El.closest('.row').find('.invalid-prompt'), tipMsg);
    }


    return returnBool;
} // EOF DispatchWithdrawalConditions.validationIsEmptyString

DispatchWithdrawalConditions.validationDisplayInvalidPrompt = function (invalidPrompt$El, tipMsg) {
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
} // EOF DispatchWithdrawalConditions.validationDisplayInvalidPrompt

/**
 * reference to https://stackoverflow.com/a/11339012
 * @param {array} unindexed_array
 */
DispatchWithdrawalConditions.getFormData = function (unindexed_array) {
    var _this = this;
    var indexed_array = {};

    $.map(unindexed_array, function (n, i) {
        if (n['name'].lastIndexOf('[]') > -1) {
            // var iKey = 0;
            // Object.keys(indexed_array).forEach(function (key) {
            //     var pName = n['name'].replace('[]',''); // xxx[] => xxx
            //     if( key.lastIndexOf(pName) > -1 ){
            //         iKey++;
            //     }
            // })
            var iKey = _this.getExistsCountByNameInArray(indexed_array, n['name']);

            indexed_array[n['name'].replace('[]', '[' + iKey + ']')] = n['value'];
        } else {
            indexed_array[n['name']] = n['value'];
        }

    });

    return indexed_array;
};

DispatchWithdrawalConditions.getExistsCountByNameInArray = function (indexed_array, pName) {
    var iKey = 0;
    Object.keys(indexed_array).forEach(function (key) {
        var _pName = pName.replace('[]', ''); // xxx[] => xxx
        if (key.lastIndexOf(_pName) > -1) {
            iKey++;
        }
    })
    return iKey;
}

DispatchWithdrawalConditions.getTarget$El = function (e) {
    var _this = this;
    return $(e.target);
};
// ======================== Utils ========================


DispatchWithdrawalConditions.apply_withdrawalConditionDetail = function (data) {
    var _this = this;
    // console.log('apply_withdrawalDefinitionDetail.data:', data);
    var detail$El = $('#withdrawalCondition_detail');
    if (data.id > 0) {
        detail$El.find('input[name="id"]').val(data.id); // for update
    } else {
        detail$El.find('input[name="id"]').val(''); // for add
    }

    detail$El.find('input[name="status"]').prop('checked', false);
    detail$El.find('input[name="status"][value=' + data.status + ']').prop('checked', true);

    var conditionId = 0;
    if (data.id > 0) {
        conditionId = data.id;
    }
    _this.initial_IncludedGameTypeTreeWithId(conditionId);

    detail$El.find('input[name="name"]').val(data.name);

    // _this.withdrawalConditionDefault.includedGameType_isEnable = 0;
    _this.applyIsEnableInDetail('includedGameType_isEnable', data.includedGameType_isEnable);


    // _this.withdrawalConditionDefault.includedPlayerTag_isEnable = 0;
    // _this.withdrawalConditionDefault.includedPlayerTag_list = '1,2';
    _this.applyIsEnableInDetail('excludedPlayerTag_isEnable', data.excludedPlayerTag_isEnable);

    var select$El = $('select[name="excludedPlayerTag_list[]"]');
    var excludedPlayerTags = [];
    var selected = [];
    if (typeof (data.excludedPlayerTag_list) === 'string') {
        excludedPlayerTags = data.excludedPlayerTag_list.split(',');
    }
    select$El.find('option').each(function (indexNumber, currEl) {
        var currVal = $(currEl).val();
        if (excludedPlayerTags.indexOf(currVal) > -1) {
            selected.push(currVal);
        }
    });

    _this.multiselect_deselectAll(select$El);
    select$El.multiselect('select', selected);


    _this.applyIsEnableInDetail('excludedPlayerLevels_isEnable', data.excludedPlayerLevels_isEnable);

    var select4PlayerLevel$El = $('select[name="excludedPlayerLevel_list[]"]');
    var excludedPlayerLevels = [];
    var selected4PlayerLevel = [];
    if (typeof (data.excludedPlayerLevel_list) === 'string') {
        excludedPlayerLevels = data.excludedPlayerLevel_list.split(',');
    } else if (typeof (data.excludedPlayerLevel_list) === 'object') {
        excludedPlayerLevels = data.excludedPlayerLevel_list;
    }
    select4PlayerLevel$El.find('option').each(function (indexNumber, currEl) {
        var currVal = $(currEl).val();
        if (excludedPlayerLevels.indexOf(currVal) > -1) {
            selected4PlayerLevel.push(currVal);
        }
    });

    console.log('data.excludedPlayerLevel_list:', data.excludedPlayerLevel_list);
    console.log('selected4PlayerLevel:', selected4PlayerLevel);
    _this.multiselect_deselectAll(select4PlayerLevel$El);
    select4PlayerLevel$El.multiselect('select', selected4PlayerLevel);

    /// ignore , same as calcAvailableBetOnly_isEnable
    // // _this.withdrawalConditionDefault.availableBetOnly_isEnable = 0;
    // _this.applyIsEnableInDetail('availableBetOnly_isEnable', data.availableBetOnly_isEnable);

    // _this.withdrawalConditionDefault.winAndDepositRate_isEnable = 0;
    // _this.withdrawalConditionDefault.winAndDepositRate_rate = 45;
    // _this.withdrawalConditionDefault.winAndDepositRate_symbol = 1;
    _this.applyIsEnableInDetail('winAndDepositRate_isEnable', data.winAndDepositRate_isEnable);
    detail$El.find('input[name="winAndDepositRate_rate"]').val(data.winAndDepositRate_rate);
    detail$El.find('select[name="winAndDepositRate_symbol"]').selectpicker('val', data.winAndDepositRate_symbol);

    _this.applyIsEnableInDetail('betAndWithdrawalRate_isEnable', data.betAndWithdrawalRate_isEnable);
    detail$El.find('input[name="betAndWithdrawalRate_rate"]').val(data.betAndWithdrawalRate_rate);
    detail$El.find('select[name="betAndWithdrawalRate_symbol"]').selectpicker('val', data.betAndWithdrawalRate_symbol);

    // _this.withdrawalConditionDefault.totalDepositCount_isEnable = 0;
    // _this.withdrawalConditionDefault.totalDepositCount_limit = 56;
    // _this.withdrawalConditionDefault.totalDepositCount_symbol = 0;
    _this.applyIsEnableInDetail('totalDepositCount_isEnable', data.totalDepositCount_isEnable);
    detail$El.find('input[name="totalDepositCount_limit"]').val(data.totalDepositCount_limit);
    detail$El.find('select[name="totalDepositCount_symbol"]').selectpicker('val', data.totalDepositCount_symbol);

    // _this.withdrawalConditionDefault.calcAvailableBetOnly_isEnable = 0;
    // _this.withdrawalConditionDefault.calcEnabledGameOnly_isEnable = 0;
    // _this.withdrawalConditionDefault.ignoreCanceledGameLogs_isEnable = 0;
    // _this.withdrawalConditionDefault.calcPromoDepositOnly_isEnable = 0;
    $('input[name="calcAvailableBetOnly_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.calcAvailableBetOnly_isEnable) ? true : false));
    $('input[name="calcEnabledGameOnly_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.calcEnabledGameOnly_isEnable) ? true : false));
    $('input[name="ignoreCanceledGameLogs_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.ignoreCanceledGameLogs_isEnable) ? true : false));
    $('input[name="calcPromoDepositOnly_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.calcPromoDepositOnly_isEnable) ? true : false));
    $('input[name="noDuplicateFirstNames_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.noDuplicateFirstNames_isEnable) ? true : false));
    $('input[name="noDuplicateLastNames_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.noDuplicateLastNames_isEnable) ? true : false));
    $('input[name="noDuplicateAccounts_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.noDuplicateAccounts_isEnable) ? true : false));

    $('input[name="thePlayerHadExistsInIovation_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.thePlayerHadExistsInIovation_isEnable) ? true : false));
    $('input[name="theTotalBetGreaterOrEqualRequired_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.theTotalBetGreaterOrEqualRequired_isEnable) ? true : false));
    $('input[name="noDepositWithPromo_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.noDepositWithPromo_isEnable) ? true : false));
    $('input[name="noAddBonusSinceTheLastWithdrawal_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.noAddBonusSinceTheLastWithdrawal_isEnable) ? true : false));


    // _this.withdrawalConditionDefault.gameRevenuePercentage_isEnable = 0;
    // _this.withdrawalConditionDefault.gameRevenuePercentage_rate = 12.3;
    // _this.withdrawalConditionDefault.gameRevenuePercentage_symbol = -1;
    $('input[name="gameRevenuePercentage_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.gameRevenuePercentage_isEnable) ? true : false));
    // _this.applyIsEnableInDetail('gameRevenuePercentage_isEnable', data.gameRevenuePercentage_isEnable);
    detail$El.find('input[name="gameRevenuePercentage_rate"]').val(data.gameRevenuePercentage_rate);
    detail$El.find('select[name="gameRevenuePercentage_symbol"]').selectpicker('val', data.gameRevenuePercentage_symbol);

    // _this.withdrawalConditionDefault.todayWithdrawalCount_isEnable = 1;
    // _this.withdrawalConditionDefault.todayWithdrawalCount_limit = 23;
    // _this.withdrawalConditionDefault.todayWithdrawalCount_symbol = -2;
    $('input[name="todayWithdrawalCount_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.todayWithdrawalCount_isEnable) ? true : false));
    detail$El.find('input[name="todayWithdrawalCount_limit"]').val(data.todayWithdrawalCount_limit);
    detail$El.find('select[name="todayWithdrawalCount_symbol"]').selectpicker('val', data.todayWithdrawalCount_symbol);

    $('input[name="afterDepositWithdrawalCount_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.afterDepositWithdrawalCount_isEnable) ? true : false));
    detail$El.find('input[name="afterDepositWithdrawalCount_limit"]').val(data.afterDepositWithdrawalCount_limit);
    detail$El.find('select[name="afterDepositWithdrawalCount_symbol"]').selectpicker('val', data.afterDepositWithdrawalCount_symbol);

    // _this.withdrawalConditionDefault.withdrawalAmount_isEnable = 0;
    // _this.withdrawalConditionDefault.withdrawalAmount_limit = 34;
    // _this.withdrawalConditionDefault.withdrawalAmount_symbol = 0;
    $('input[name="withdrawalAmount_isEnable"]:checkbox').bootstrapSwitch('state', (!!parseInt(data.withdrawalAmount_isEnable) ? true : false));
    detail$El.find('input[name="withdrawalAmount_limit"]').val(data.withdrawalAmount_limit);
    detail$El.find('select[name="withdrawalAmount_symbol"]').selectpicker('val', data.withdrawalAmount_symbol);


}; // EOF apply_withdrawalConditionDetail

/**
 * reference to https://github.com/davidstutz/bootstrap-multiselect/issues/271#issuecomment-43328968
 */
DispatchWithdrawalConditions.multiselect_deselectAll = function ($el) {
    $('option', $el).each(function (element) {
        $el.multiselect('deselect', $(this).val());
    });
}

DispatchWithdrawalConditions.applyIsEnableInDetail = function (keyStr, isEnable) {
    var _this = this;
    var detail$El = $('#withdrawalCondition_detail');

    if (typeof (keyStr) === 'undefined') {
        keyStr = 'calcAvailableBetOnly_isEnable';// just for default.
    }
    if (typeof (isEnable) === 'undefined') {
        isEnable = 0;
    }

    var isChecked = false;
    if (isEnable == 1) {
        isChecked = true;
    }
    detail$El.find('input[name="' + keyStr + '"]').prop('checked', isChecked);
} // EOF applyIsEnableInDetail

DispatchWithdrawalConditions.initial_MultiselectLevels = function () {
    var _this = this;

    $('#level_list').multiselect({
        enableFiltering: true,
        includeSelectAllOption: true,
        selectAllJustVisible: false,
        buttonWidth: '100%',
        buttonText: function (options, select) {
            if (options.length === 0) {
                return _this.langs.selectLevels;
            }
            else {
                var labels = [];
                options.each(function () {
                    if ($(this).attr('label') !== undefined) {
                        labels.push($(this).attr('label'));
                    }
                    else {
                        labels.push($(this).html());
                    }
                });
                return labels.join(', ') + '';
            }
        }
    });
}

DispatchWithdrawalConditions.initial_MultiselectTags = function () {
    var _this = this;

    $('#tag_list').multiselect({
        enableFiltering: true,
        includeSelectAllOption: true,
        selectAllJustVisible: false,
        buttonWidth: '100%',
        buttonText: function (options, select) {
            if (options.length === 0) {
                return _this.langs.selectTags;
            }
            else {
                var labels = [];
                options.each(function () {
                    if ($(this).attr('label') !== undefined) {
                        labels.push($(this).attr('label'));
                    }
                    else {
                        labels.push($(this).html());
                    }
                });
                return labels.join(', ') + '';
            }
        }
    });
}

DispatchWithdrawalConditions.initial_BootstrapSwitch = function () {

    $("input[data-off-text]:checkbox").bootstrapSwitch('state', false);
    // .bootstrapSwitch('state') //read

}// EOF initial_BootstrapSwitch

DispatchWithdrawalConditions.initial_IncludedGameTypeTreeWithId = function (conditionId) {
    var _this = this;
    var theUri = _this.URIs.get_game_tree_by_condition;

    theUri = theUri.replace(/{\$id}/gi, conditionId);

    _this.includedGameTypeTree$El = $('#includedGameTypeTree');

    _this.includedGameTypeTree$El
        .bind("loaded.jstree", function (event, data) {
            // console.log('loaded.jstree.data', data);
            // console.log('loaded.jstree.inst', data.instance);
            // data.instance.refresh()
            _this.gameTypeTreeLoaded = true;
            _this.on_loadedInJstree(event, data);
        }).bind('check_node.jstree', function (node, selected, e) {
            // var selected_nodes = _this.includedGameTypeTree$El.jstree('get_checked');
            // console.log('check_node', selected_nodes);
            _this.changed_CheckNodeInJstree(node, selected, e);
        }).bind('uncheck_node.jstree', function (node, selected, e) {
            // var selected_nodes = _this.includedGameTypeTree$El.jstree('get_checked');
            // console.log('uncheck_node', selected_nodes);
            _this.changed_CheckNodeInJstree(node, selected, e);
        }).bind('init.jstree', function () {
            _this.gameTypeTreeLoaded = null;
            // console.log('init.jstree');
            // Not work
            // }).bind('will.destroy.jstree', function () {
            //     // }).bind('destroy.jstree', function () {
            //     _this.gameTypeTreeLoaded = null;
            //     console.log('destroy.jstree');
        }).bind('ready.jstree', function () {
            // _this.gameTypeTreeLoaded = null;
            // console.log('ready.jstree');
        }).jstree({
            core: {
                data: {
                    url: theUri,
                    dataType: "json"
                }
            },
            input_number: {
                "form_sel": '#promoform'
            },
            checkbox: {
                "tie_selection": false,
            },
            plugins: [
                "search", "checkbox"
            ]
        });


    // .on('select_node.jstree', '#includedGameTypeTree', function (node, selected, e) {

    // _this.includedGameTypeTree$El.on('changed.jstree', function (e, data) {
    //     var selected_nodes = _this.includedGameTypeTree$El.jstree('get_checked');
    //     console.log('selected_nodes', selected_nodes);
    // });

} // EOF initial_IncludedGameTypeTreeWithId

DispatchWithdrawalConditions.changed_CheckNodeInJstree = function (node, selected, e) {
    var _this = this;
    setTimeout(function () { // patch legacy issue
        var selected_nodes = _this.includedGameTypeTree$El.jstree('get_checked');
        // console.log('selected_nodes:', selected_nodes);
        $('input[name="selected_game_tree"]').val(selected_nodes.join(','));
    }, 100);

}

DispatchWithdrawalConditions.on_loadedInJstree = function (event, data) {
    var _this = this;
    var selected_nodes = _this.includedGameTypeTree$El.jstree('get_checked');
    $('input[name="selected_game_tree"]').val(selected_nodes.join(','));
    // console.log('on_loadedInJstree', selected_nodes);
}
DispatchWithdrawalConditions.on_destroyInJstree = function () {
    var _this = this;
    _this.gameTypeTreeLoaded = null;
    // console.log('destroy.jstree');
}