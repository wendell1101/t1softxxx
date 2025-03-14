var appendHabaResults = appendHabaResults || {};



// var theInput$El = $('#search-rharm-form input[name="playerpromo_id"]');
appendHabaResults.initEvents = function (theOption) {
    var _this = this;

    _this.options = {};
    _this.options.defaultItemsPerPage = 11;// DefaultItemsPerPage
    _this.options.uri4checkHabaResultsByPlayerPromoIds = '/api/checkHabaResultsByPlayerPromoIds'; // api/checkHabaResultsByPlayerPromoIds
    _this.options.uri4reviewHabaApiResultsList = '/api/reviewHabaApiResultsList'; // <?=site_url('api/reviewHabaApiResultsList') ?>

    _this.options = $.extend(true, _this.options, theOption);

    _this.aceList = {}; // reset

    _this.selectStrList = {};
    _this.selectStrList['reviewHabaApiResultsModal'] = '#review_haba_api_resultsModal';
    _this.selectStrList['reviewHabaApiResultsList'] = '#review_haba_api_results_list';
    _this.selectStrList['searchRharmFormInputPlayerpromoId'] = '#search-rharm-form input[name="playerpromo_id"]';
    _this.selectStrList['searchRharmForm'] = '#search-rharm-form';



    _this.searchRharmFormInputPlayerpromoId$El = $(_this.selectStrList['searchRharmFormInputPlayerpromoId']);
    _this.reviewHabaApiResultsList$El = $(_this.selectStrList['reviewHabaApiResultsList']);
    _this.searchRharmForm$El = $(_this.selectStrList['searchRharmForm']);


    $('body').on('click', '.review_haba_api_results_btn', function (e) {
        _this.clicked_review_haba_api_results_btn(e);
    });

    _this.initEvents_RHARM();
}; // EOF initEvents

// Init ACE editor for JSON
appendHabaResults.initialACE = function (IdString) {
    var _this = this;
    if (_this.aceList[IdString]['ace'] === null) {
        _this.aceList[IdString]['ace'] = ace.edit(IdString);
        _this.aceList[IdString]['ace'].setTheme("ace/theme/tomorrow");
        _this.aceList[IdString]['ace'].session.setMode("ace/mode/json");
        // _this.aceList[IdString]['ace'].setOptions({ // reference to https://stackoverflow.com/a/13579233
        //     maxLines: Infinity
        // });
        _this.aceList[IdString]['ace'].setOptions({ // reference to https://stackoverflow.com/a/13579233
            maxLines: 19 // Infinity
        });

        var jsonStr = _this.aceList[IdString]['jsonStr'];

        _this.aceList[IdString]['ace'].setValue(JSON.stringify(JSON.parse(jsonStr), null, 4));

    }

    var theDataTable$El = _this.reviewHabaApiResultsList$El;
    theDataTable$El.find('th:eq(0)').css('width', '1%');
    theDataTable$El.find('th:eq(1)').css('width', '1%')
    theDataTable$El.find('th:eq(2)').css('width', '1%');
    theDataTable$El.find('th:eq(3)').css('width', '1%');
    theDataTable$El.find('th:eq(4)').css('width', '1%');
    theDataTable$El.find('th:eq(7)').css('width', '1%');
    theDataTable$El.find('th:eq(8)').css('width', '1%');


}

appendHabaResults.appendBtnBtn = function () {
    var _this = this;
    var playerPromoIdList = [];
    var theSelectStrList = [];
    theSelectStrList.push('#promo-application-table'); // the list in the promo request list of market_management.
    theSelectStrList.push('#promo-table'); // the list in the player detail.
    $(theSelectStrList.join(',')).find('.check_cms_promo[data-playerpromoid]').each(function (indexNumber, currEl) {
        var curr$El = $(currEl);
        var playerPromoId = curr$El.data('playerpromoid');
        playerPromoIdList.push(playerPromoId);
    });

    if (playerPromoIdList.length > 0) {
        var _ajax = _this.appendBtnBtn4checkAndAppend(playerPromoIdList);
    }
};

appendHabaResults.appendBtnBtn4checkAndAppend = function (thePlayerPromoIdList) {
    var _this = this;

    var uri = _this.options.uri4checkHabaResultsByPlayerPromoIds;
    var ajax = $.ajax({
        'url': uri,
        'type': 'POST',
        'data': { 'PlayerPromoIdList': thePlayerPromoIdList },
        'cache': false,
        'dataType': "json",
    });
    ajax.done(function (data, textStatus, jqXHR) {
        if (typeof (data.length) !== 'undefined'
            && data.length > 0
        ) {
            var theSelectStrList = [];
            theSelectStrList.push('#promo-application-table'); // the list in the promo request list of market_management.
            theSelectStrList.push('#promo-table'); // the list in the player detail.

            $.each(data, function (indexNumber, currVal) {
                // currVal.playerpromo_id
                // currVal.counter

                // load the tpl
                var tpl = $('.review_haba_api_results_btn_tpl').html();
                var tpl$El = $(tpl).clone();
                var tplHtml = '&nbsp;' + $('<div>').append(tpl$El).html(); // like outerHTML via jquery

                // assign playerpromo_id into the tpl
                var regex = /\$\{playerpromo_id\}/gi;
                tplHtml = tplHtml.replaceAll(regex, currVal.playerpromo_id);
                // assign results_counter into the tpl
                var regex = /\$\{results_counter\}/gi;
                tplHtml = tplHtml.replaceAll(regex, currVal.counter);

                var currCheckCmsPromo$El = $(theSelectStrList.join(',')).find('.check_cms_promo[data-playerpromoid="' + currVal.playerpromo_id + '"]');
                // append html next to the promo Title.
                currCheckCmsPromo$El.parent().append(tplHtml);
            }); // EOF $.each(data, function(indexNumber, currVal ){...
        } // if( typeof(data.length) !== 'undefined' && data.length > 0 )...
    }); // EOF ajax.done(function(data, textStatus, jqXHR){...

    return ajax;
};

appendHabaResults.clicked_review_haba_api_results_btn = function (e) {
    var _this = this;

    var theTarget$El = $(e.target);
    if (typeof (theTarget$El.data('playerpromoid')) === 'undefined') {
        theTarget$El = theTarget$El.closest('[data-playerpromoid]');
    }

    _this.searchRharmFormInputPlayerpromoId$El.val(theTarget$El.data('playerpromoid'));

    var theCheckCmsPromo$El = theTarget$El.closest('td').find('.check_cms_promo');
    var theCheckCmsPromoTitle = theCheckCmsPromo$El.text();

    _this.searchRharmFormInputPlayerpromoId$El
        .data('promotitle', theCheckCmsPromoTitle)
        .prop('data-promotitle', theCheckCmsPromoTitle);

    $(_this.selectStrList.reviewHabaApiResultsModal).modal('show');

};

appendHabaResults.show_RHARM = function () {
    var _this = this;

    _this.initDataTable_RHARM();

    $('.review_haba_api_results_title').html(_this.searchRharmFormInputPlayerpromoId$El.data('promotitle'));

    _this.dataTable_RHARM.ajax.reload(null, false); // user paging is not reset on reload
};
appendHabaResults.shown_RHARM = function () {
    var _this = this;
};
appendHabaResults.hide_RHARM = function () {
    var _this = this;
    _this.dataTable_RHARM.clear(); // clear data of dataTable
};
appendHabaResults.hidden_RHARM = function () {
    var _this = this;

    _this.searchRharmFormInputPlayerpromoId$El.val(''); // for reset the input
};

appendHabaResults.initEvents_RHARM = function () {
    var _this = this;
    // RHARM = reviewHabaApiResultsModal
    $('body').on('show.bs.modal', _this.selectStrList.reviewHabaApiResultsModal, function (e) {
        // $(_this.selectStrList.reviewHabaApiResultsModal)
        _this.show_RHARM(e);
    }).on('shown.bs.modal', _this.selectStrList.reviewHabaApiResultsModal, function (e) {
        _this.shown_RHARM(e);
    }).on('hide.bs.modal', _this.selectStrList.reviewHabaApiResultsModal, function (e) {
        _this.hide_RHARM(e);
    }).on('hidden.bs.modal', _this.selectStrList.reviewHabaApiResultsModal, function (e) {
        _this.hidden_RHARM(e);
    });


};
appendHabaResults.initDataTable_RHARM = function () {
    var _this = this;
    var reviewHabaApiResultsList$El = $(_this.selectStrList['reviewHabaApiResultsList']);
    var theRequestIndex = 5;
    var theResponseIndex = 6;
    if (reviewHabaApiResultsList$El.data('initialized-datatable') != '1') {
        _this.dataTable_RHARM = reviewHabaApiResultsList$El.DataTable({
            autoWidth: false,
            searching: false,
            cache: false,
            pageLength: _this.options.defaultItemsPerPage,
            "dom": "<'panel-body' <'pull-right'f><'pull-right progress-container'>l>t<'panel-body'<'pull-right'p>i>",
            "columnDefs": [{
                className: 'control',
                orderable: false,
                targets: 0
            },
            {
                targets: theRequestIndex,
                "data": null, // Use the full data source object for the renderer's source
                render: function (data, type, row, meta) {
                    var results_id = row[0];
                    // console.log(arguments);
                    var jsonStr = row[theRequestIndex];
                    var json = JSON.parse(jsonStr);
                    var idString = 'request_editor_' + results_id;
                    _this.aceList[idString] = {};
                    _this.aceList[idString]['ace'] = null;
                    _this.aceList[idString]['jsonStr'] = jsonStr;
                    // console.log('columnDefs.data:', data, 'type:', type, 'row:', row, 'meta:', meta);

                    return '<div class="request_editor" id="' + idString + '">' + JSON.stringify(json, null, '\t') + '</div>';

                }
            },
            {
                targets: theResponseIndex,
                "data": null, // Use the full data source object for the renderer's source
                render: function (data, type, row, meta) {
                    var results_id = row[0];
                    // console.log(arguments);
                    var jsonStr = row[theResponseIndex];
                    var json = JSON.parse(jsonStr);
                    var idString = 'response_editor_' + results_id;
                    _this.aceList[idString] = {};
                    _this.aceList[idString]['ace'] = null;
                    _this.aceList[idString]['jsonStr'] = jsonStr;
                    return '<div class="response_editor" id="' + idString + '">' + JSON.stringify(json, null, '\t') + '</div>';

                }
            }
            ],
            buttons: [],
            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                var playerpromo_id = _this.searchRharmFormInputPlayerpromoId$El.val();
                if (!$.isEmptyObject(playerpromo_id)) {
                    data.extra_search = _this.searchRharmForm$El.serializeArray();
                    var _ajax = $.post(_this.options.uri4reviewHabaApiResultsList, data, function (data) {
                        callback(data);
                    }, 'json');
                }
            },
        }); // EOF reviewHabaApiResultsList$El.DataTable({...

        // _this.selectStrList['reviewHabaApiResultsList']

        _this.dataTable_RHARM.on('draw', function (e, settings) {
            // console.log('column-reorder.draw');

            for (var anACEId in _this.aceList) {
                _this.initialACE(anACEId);
            }
        });

        var regex = /#/gi;
        tableId = _this.selectStrList['reviewHabaApiResultsList'].replaceAll(regex, '');
        initBarLoader(tableId); // for .progress-container'

        // mark initialized
        reviewHabaApiResultsList$El.data('initialized-datatable', '1').prop('data-initialized-datatable', '1');
    }

};