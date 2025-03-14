var WithdrawalRiskResults = WithdrawalRiskResults || {};
WithdrawalRiskResults.initialize = function (options) {
    var _this = this;
    _this.URIs = {};
    _this.URIs.autoRiskResultsList = options.base_url + 'api/dispatch_withdrawal_results_list/{$transCode}';
    _this.URIs.getResultsByTransCode = options.base_url + "api/getResultsByTransCode/{$transCode}";
    _this.URIs.processPreChecker = options.base_url + "api/processPreCheckerWithTransCode/{$transCode}";

    _this.aceList = {};
    _this.langs = {};
    _this.rerunAutoRiskAjax = null;
    _this.rerunAutoRiskTransCode = null;

    _this = $.extend(true, {}, _this, options);

    return _this;
}

WithdrawalRiskResults.onReady = function () {
    var _this = this;

    $('#autoRiskResultsModal')
        .on('show.bs.modal', function (e) {
            _this.show_autoRiskResultsModal(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_autoRiskResultsModal(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_autoRiskResultsModal(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_autoRiskResultsModal(e);
        });

    $('#reRunAutoRiskModal')
        .on('show.bs.modal', function (e) {
            _this.show_reRunAutoRiskModal(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_reRunAutoRiskModal(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_reRunAutoRiskModal(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_reRunAutoRiskModal(e);
        });

    $('body').on('click', '.previewAutoRiskResults', function (e) {
        return _this.clicked_previewAutoRiskResults(e);
    });

    $('body').on('click', '.reRunAutoRisk', function (e) {
        return _this.clicked_reRunAutoRisk(e);
    });

}; // EOF onReady

WithdrawalRiskResults.clicked_reRunAutoRisk = function (e) {
    var _this = this;

    var theTarget$El = $(e.target).closest('td').find('.reRunAutoRisk');
    var transCode = theTarget$El.data('trans-code');

    if (!theTarget$El.hasClass('disabled')) {
        _this.rerunAutoRiskTransCode = transCode;
        $('#reRunAutoRiskModal').modal('show'); // show_autoRiskResultsModal
    }
}



WithdrawalRiskResults.show_reRunAutoRiskModal = function (e) {
    var _this = this;

    var theBtn$El = $('.reRunAutoRisk[data-trans-code="' + _this.rerunAutoRiskTransCode + '"]');
    var transCode = theBtn$El.data('trans-code');

    _this.rerunAutoRiskAjax = _this.ajaxProcessPreChecker(transCode, function(jqXHR, settings){ // beforeSendCB
    console.log('theBtn$El',theBtn$El);
        theBtn$El.button('loading');

        // loading UI
        _this.showResponseProgressBar();
        _this.hideResponseMessage('');

    }, function(jqXHR, textStatus){ // completeCB
        // loading completed UI
        _this.hideResponseProgressBar();
    });
    _this.rerunAutoRiskAjax.done(function (data, textStatus, jqXHR) {
        _this.showResponseMessageWithQueueDone();
    });
    _this.rerunAutoRiskAjax.fail(function (jqXHR, textStatus, errorThrown) {
        _this.showResponseMessage(errorThrown);
    });
} // EOF show_reRunAutoRiskModal
WithdrawalRiskResults.shown_reRunAutoRiskModal = function (e) {
    var _this = this;
}
WithdrawalRiskResults.hide_reRunAutoRiskModal = function (e) {
    var _this = this;
}
WithdrawalRiskResults.hidden_reRunAutoRiskModal = function (e) {
    var _this = this;

    var theBtn$El = $('.reRunAutoRisk[data-trans-code="' + _this.rerunAutoRiskTransCode + '"]');
    theBtn$El.button('reset');

    // reset
    _this.rerunAutoRiskTransCode = null;

    _this.hideResponseMessage('');
    _this.showResponseProgressBar();

    _this.resetRerunAutoRiskAjax();
}
WithdrawalRiskResults.resetRerunAutoRiskAjax = function () {
    var _this = this;
    if (_this.rerunAutoRiskAjax !== null) {
        _this.rerunAutoRiskAjax.abort();
        _this.rerunAutoRiskAjax = null;
    }
}


WithdrawalRiskResults.clicked_previewAutoRiskResults = function (e) {
    var _this = this;

    var theTarget$El = $(e.target).closest('td').find('.previewAutoRiskResults');
    var transCode = theTarget$El.data('trans-code');
    $('#autoRiskResultsModal').find('input[name="transCode"]').val(transCode);
    $('#autoRiskResultsModal').modal('show');

}



WithdrawalRiskResults.show_autoRiskResultsModal = function (e) {
    // initial data table
    var definitionResultsIndex = 2;
    var _this = this;
    _this.aceList = {}; // reset
    var tableId = 'autoRiskResultsList';
    if ($('#' + tableId + '_wrapper').length == 0) {
        _this.autoRiskResultsModal_dataTable = $('#' + tableId).DataTable({
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
                var searchForm$El = $('#autoRiskResultsModal-search-form');
                var transCode = searchForm$El.find('[name="transCode"]').val();
                var serializeData = searchForm$El.serializeArray();
                // serializeData.push({ name: "transCode", value: transCode }); // for update
                data.extra_search = serializeData;
                var theUri = _this.URIs.autoRiskResultsList;
                theUri = theUri.replace(/{\$transCode}/gi, transCode);
                var ajax = $.post(theUri, data, function (doneData) {
                    doneData.extra_search = searchForm$El.serializeArray(); // for definition_id
                    callback(doneData);
                    if (_this.autoRiskResultsModal_dataTable.rows({ selected: true }).indexes().length === 0) {
                        _this.autoRiskResultsModal_dataTable.buttons().disable();
                    }
                    else {
                        _this.autoRiskResultsModal_dataTable.buttons().enable();
                    }
                }, 'json');

                // ajax.always(function () {
                //     // for $.button('loading');
                //     var selectorStrList = [];
                //     selectorStrList.push('.editWithdrawalCondition');
                //     $(selectorStrList.join(',')).data('loading-text', '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                // });
            },
            colReorder: true,
            columnDefs: [
                {
                    targets: definitionResultsIndex,
                    "data": null, // Use the full data source object for the renderer's source
                    render: function (data, type, row, meta) {
                        var results_id = row[0];
                        // for html format(, includes splitted_table )
                        if($(row[0]).text() != ''){
                            results_id = $(row[0]).text();
                        }
                        var jsonStr = row[definitionResultsIndex];
                        var json = JSON.parse(jsonStr);
                        var idString = 'editor_' + results_id;
                        _this.aceList[idString] = {};
                        _this.aceList[idString]['ace'] = null;
                        _this.aceList[idString]['jsonStr'] = jsonStr;
                        // console.log('columnDefs.data:', data, 'type:', type, 'row:', row, 'meta:', meta);

                        return '<div class="definition-results" id="' + idString + '">' + JSON.stringify(json, null, '\t') + '</div>';

                    }
                }
            ],

        });
        _this.autoRiskResultsModal_dataTable.on('draw', function (e, settings) {
            // console.log('column-reorder.draw');

            for (var anACEId in _this.aceList) {
                _this.initialACE(anACEId);
            }
        });

        initBarLoader(tableId); // for .progress-container'
    } // EOF if($('#dispatch_withdrawal_definition_order_preview_wrapper').length == 0)

}// EOF show_autoRiskResultsModal

WithdrawalRiskResults.showResponseMessageWithQueueDone = function (data) {
    var _this = this;

    var doHideResponseProgressBar = true;
    _this.showResponseMessage(_this.langs.runHasBeenFinish, doHideResponseProgressBar);
}

/**
 * To display Response Message of the Response division. (.reRunAutoRiskModalBody)
 * @param {string|null} message If null it's means No change else will apply to the Response division.
 * @param {boolean} doHideResponseProgressBar If true will hidhen the Progress Bar of Response division.
 */
WithdrawalRiskResults.showResponseMessage = function (message, doHideResponseProgressBar) {
    var _this = this;
    if (typeof (doHideResponseProgressBar) === 'undefined') {
        doHideResponseProgressBar = true;
    }

    if (typeof (message) === 'undefined') {
        message = null;
    }
    // null means Nochange
    if (message !== null) {
        $('.response-message').html(message);
    }
    if (doHideResponseProgressBar) {
        _this.hideResponseProgressBar();
    }
    $('.response-message').removeClass('hide');
}

/**
 * To hidden the Response Message of the Response division.
 * @param {string|null} message If null it's means No change else will apply to the Response division.
 */
WithdrawalRiskResults.hideResponseMessage = function (message) {

    if (typeof (message) === 'undefined') {
        message = null;
    }
    // null means Nochange
    if (message !== null) {
        $('.response-message').html(message);
    }

    $('.response-message').addClass('hide');
}

/**
 * To Show the Response Progress Bar
 */
WithdrawalRiskResults.showResponseProgressBar = function () {

    $('.response-progress-bar').removeClass('hide');
}

/**
 * To Hidden the Response Progress Bar
 */
WithdrawalRiskResults.hideResponseProgressBar = function () {

    $('.response-progress-bar').addClass('hide');
}

WithdrawalRiskResults.ajaxProcessPreChecker = function (transCode, beforeSendCB, completeCB) {
    var _this = this;
    var theUri = _this.URIs.processPreChecker;
    theUri = theUri.replace(/{\$transCode}/gi, transCode);

    if( typeof(beforeSendCB) === 'undefined'){
        beforeSendCB = function(jqXHR, settings){};
    }
    if( typeof(completeCB) === 'undefined'){
        completeCB = function(jqXHR, textStatus){};
    }

    var jqXHR = $.ajax({
        type: 'POST',
        url: theUri,
        // data: $.param(theData),
        beforeSend: function (jqXHR, settings) {
            // targetBtn$El.button('loading');
            beforeSendCB.apply(_this, arguments);
        },
        complete: function (jqXHR, textStatus) {
            // targetBtn$El.button('reset');
            completeCB.apply(_this, arguments);
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        var transCode$El = $('td .trans-code[data-trans-code="'+data.transCode+'"]');
        transCode$El.find('.reRunAutoRisk,.previewAutoRiskResults').remove(); // reset

        if(data.count > 0){
            transCode$El.append('<a class="btn btn-default btn-xs previewAutoRiskResults" data-trans-code="'+ data.transCode+ '"><span class="glyphicon glyphicon-indent-left"></span> '+ _this.langs.viewResults+ ' </a>')
        }else{
            var tokenAttr = '';
            if( typeof(data.token) !== 'undefined'){
                tokenAttr = 'data-token="'+ data.token+ '"';
            }
            transCode$El.append('<a class="btn btn-default btn-xs reRunAutoRisk" '+ tokenAttr+ ' data-trans-code="'+ data.transCode+ '"><span class="glyphicon glyphicon-indent-left"></span> <?=lang("Rerun")?> </a>')
        }
    });
    return jqXHR;
}

// Init ACE editor for JSON
WithdrawalRiskResults.initialACE = function (IdString) {
    var _this = this;
    if (_this.aceList[IdString]['ace'] === null) {
        _this.aceList[IdString]['ace'] = ace.edit(IdString);
        _this.aceList[IdString]['ace'].setTheme("ace/theme/tomorrow");
        _this.aceList[IdString]['ace'].session.setMode("ace/mode/json");

        var jsonStr = _this.aceList[IdString]['jsonStr'];

        _this.aceList[IdString]['ace'].setValue(JSON.stringify(JSON.parse(jsonStr), null, 4));

    }

    var autoRiskResultsList$El = $('#autoRiskResultsList');
    autoRiskResultsList$El.find('th:eq(0)').css('width', '9%');
    autoRiskResultsList$El.find('th:eq(1)').css('width', '10%');
    autoRiskResultsList$El.find('th:eq(3)').css('width', '9%');
    autoRiskResultsList$El.find('th:eq(4)').css('width', '9%');
    autoRiskResultsList$El.find('th:eq(5)').css('width', '10%');
    autoRiskResultsList$El.find('th:eq(6)').css('width', '17%');


}


WithdrawalRiskResults.shown_autoRiskResultsModal = function (e) {
    var _this = this;
    // reload, after initialized
    _this.autoRiskResultsModal_dataTable.ajax.reload(null, false); // user paging is not reset on reload

}
WithdrawalRiskResults.hide_autoRiskResultsModal = function (e) {
    var _this = this;
    _this.autoRiskResultsModal_dataTable.clear(); // clear data of dataTable
}
WithdrawalRiskResults.hidden_autoRiskResultsModal = function (e) {

}