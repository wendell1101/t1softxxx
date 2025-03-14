var ASCAABM = ASCAABM || {};
ASCAABM.init = function (theOptions) {
    var _this = this;

    _this.options = {}; // for recive the param, theOptions.

    // initial defaults
    if (typeof (_this.options.defaults) === 'undefined') {
        var _defaults = {};
        _defaults.DtStartUTC = '20200901000000';
        _defaults.DtEndUTC = '20201231000000';
        _defaults.ExpireAfterDays = 3;
        _defaults.NumberOfFreeSpins = 10;
        _defaults._GameKeyNames = [1, 2, 3];
        _defaults.couponCurrencyData = [];
        var _couponCurrencyData = {};
        _couponCurrencyData.CurrencyCode = 'CNY';
        _couponCurrencyData.CoinPosition = 0;
        _defaults.couponCurrencyData[0] = _couponCurrencyData;
        _this.options.defaults = _defaults;
    }

    // if (typeof (_this.options.lang) === 'undefined') {
    //     _this.options.lang = {};
    //     _this.options.lang.toEndOfThisWeek = 'toEndOfThisWeek';
    //     _this.options.lang.toEndOfThisMonth = 'toEndOfThisMonth';
    //     _this.options.lang.toEndOfThisYear = 'toEndOfThisYear';
    //     _this.options.lang['please_wait_the_game_tree_initial'] = 'Please wait the Game Tree initial.';
    //     _this.options.lang.only_numeric = 'only_numeric';
    //     _this.options.lang.is_required = 'is_required';
    // }

    // extend form options of param
    if (typeof (_this.options.base_url) === 'undefined') {
        _this.options.base_url = "/"; // "<?=base_url()?>";
    }

    if (typeof (_this.options.toDisplayCreateAndApplyBonusMulti) === 'undefined') {
        _this.options.toDisplayCreateAndApplyBonusMulti = false;
    }


    _this.options = $.extend(true, _this.options, theOptions);


    _this.caabmModal$El = $('#createandapplybonusmultiModal'); // caabmModal = createandapplybonusmultiModal
    _this.habaneroGameTree$El = _this.caabmModal$El.find('.habaneroGameTree');
    _this.tipToSave$El = $('.tip_to_save');

};

ASCAABM.assignLangList2Options = function (theLangList) {
    var _this = this;
    _this.options.lang = $.extend(true, _this.options.lang, theLangList);
}

ASCAABM.resetValidationTip = function () {
    var _this = this;
    var theTip$Els = _this.caabmModal$El.find('.col_tip');
    theTip$Els.each(function (indexNumber, currEl) {
        var currTip$El = $(currEl);
        _this.validation4tip(currTip$El, function () {
            var _return = {};
            _return['bool'] = true;
            _return['msg'] = '';
            return _return;
        });
    });

}



ASCAABM.initEvents = function () {
    var _this = this;

    if (_this.options.toDisplayCreateAndApplyBonusMulti) {
        $('.append-settings-createandapplybonusmulti').removeClass('hide');
    }


    $('body').
        on('click', '.append-settings-createandapplybonusmulti', function (e) {
            _this.clicked_append_settings_caabm(e);//append-settings-createandapplybonusmulti
        }).on('click', '.btn_append', function (e) {
            _this.clicked_btn_append(e);
        });

    // $('body').
    //     on('change', 'input[name="_GameKeyNames"]', function(e){
    //         _this.changed_gameKeyNames(e);
    //     });

    _this.habaneroGameTree$El
        .on('init.jstree', function () {
            var theInput$El = _this.caabmModal$El.find('input[name="_GameKeyNames"]');
            theInput$El.data('init-flag', 'false').prop('data-init-flag', 'false');
            // console.log('111.init.jstree.theInput$El', theInput$El)
        })
        .on('loaded.jstree', function () {
            // console.log('111.loaded jstree');
            // _this.caabmModal$El.find('input[name="_GameKeyNames"]').prop('data-loaded', 1); // .data('loaded', 1)
            _this.onReady_habaneroGameTree();
        })
        .on('ready.jstree', function () {
            var theInput$El = _this.caabmModal$El.find('input[name="_GameKeyNames"]');
            theInput$El.data('init-flag', '1').prop('data-init-flag', '1');
            // console.log('111.ready.jstree.theInput$El', theInput$El)

        })
        .on('check_node.jstree uncheck_node.jstree', function (node, selected, e) {
            // console.log('111.check_node,uncheck_node jstree');
            // _this.assignChecked_habaneroGameTree(); // It's will be issue while check at root(too many).
        });

    _this.createandapplybonusmultiModalEvents();
}; // EOF initEvents


ASCAABM.onReady_habaneroGameTree = function () {
    var _this = this;

    // var _GameKeyNameList = []; // parse from formula_bonus_release
    var _GameKeyNameList = _this.createAndApplyBonusMulti._GameKeyNames;
    // console.log('onReady_habaneroGameTree._GameKeyNameList', _GameKeyNameList);
    _this.assignValuesToHabaneroGameTree(_GameKeyNameList);
    _this.assignChecked_habaneroGameTree();
}

ASCAABM.validation = function () {
    var _this = this;
    var resultBool = true;

    // // DtStartEndUTC
    var theInput$El = _this.caabmModal$El.find('input[name="DtStartEndUTC"]');
    var theTip$El = theInput$El.parent('div').parent('div').parent('div').find('.col_tip');
    var _validation4tip = ASCAABM.validation4tip(theTip$El, function () {
        var _input$El = _this.caabmModal$El.find('input[name="DtStartEndUTC"]');
        var _return = {};
        var _msgList = [];
        _return['bool'] = true;
        _return['msg'] = '';

        var checkRequiredResult = _this.validation4checkRequired(_input$El);
        _return['bool'] = _return['bool'] && checkRequiredResult['bool'];
        if (!checkRequiredResult['bool']) {
            _msgList.push(checkRequiredResult['msg']);
        }

        _return['msg'] = _msgList.join('<br/>');

        return _return;
    });
    resultBool = resultBool && _validation4tip['bool'];

    // ExpireAfterDays
    var theInput$El = _this.caabmModal$El.find('input[name="ExpireAfterDays"]');
    var theTip$El = theInput$El.parent('div').parent('div').find('.col_tip');
    var _validation4tip = ASCAABM.validation4tip(theTip$El, function () {
        var _input$El = _this.caabmModal$El.find('input[name="ExpireAfterDays"]');
        var _return = {};
        var _msgList = [];
        _return['bool'] = true;
        _return['msg'] = '';

        var checkRequiredResult = _this.validation4checkRequired(_input$El);
        _return['bool'] = _return['bool'] && checkRequiredResult['bool'];
        if (!checkRequiredResult['bool']) {
            _msgList.push(checkRequiredResult['msg']);
        }

        var checkOnlyNumericResult = _this.validation4checkOnlyNumeric(_input$El);
        _return['bool'] = _return['bool'] && checkOnlyNumericResult['bool'];
        if (!checkOnlyNumericResult['bool']) {
            _msgList.push(checkOnlyNumericResult['msg']);
        }

        _return['msg'] = _msgList.join('<br/>');

        return _return;
    });
    resultBool = resultBool && _validation4tip['bool'];


    // NumberOfFreeSpins
    var theInput$El = _this.caabmModal$El.find('input[name="NumberOfFreeSpins"]');
    var theTip$El = theInput$El.parent('div').parent('div').find('.col_tip');
    var _validation4tip = ASCAABM.validation4tip(theTip$El, function () {
        var _input$El = _this.caabmModal$El.find('input[name="NumberOfFreeSpins"]');
        var _return = {};
        var _msgList = [];
        _return['bool'] = true;
        _return['msg'] = '';

        var checkRequiredResult = _this.validation4checkRequired(_input$El);
        _return['bool'] = _return['bool'] && checkRequiredResult['bool'];
        if (!checkRequiredResult['bool']) {
            _msgList.push(checkRequiredResult['msg']);
        }

        var checkOnlyNumericResult = _this.validation4checkOnlyNumeric(_input$El);
        _return['bool'] = _return['bool'] && checkOnlyNumericResult['bool'];
        if (!checkOnlyNumericResult['bool']) {
            _msgList.push(checkOnlyNumericResult['msg']);
        }

        _return['msg'] = _msgList.join('<br/>');

        return _return;
    });
    resultBool = resultBool && _validation4tip['bool'];


    // CurrencyCode
    var theInput$El = _this.caabmModal$El.find('input[name="CurrencyCode"]');
    var theTip$El = theInput$El.parent('div').parent('div').parent('div').find('.col_tip');
    var _validation4tip = ASCAABM.validation4tip(theTip$El, function () {
        var _input$El = _this.caabmModal$El.find('input[name="CurrencyCode"]');
        var _return = {};
        var _msgList = [];
        _return['bool'] = true;
        _return['msg'] = '';

        var checkRequiredResult = _this.validation4checkRequired(_input$El);
        _return['bool'] = _return['bool'] && checkRequiredResult['bool'];
        if (!checkRequiredResult['bool']) {
            _msgList.push(checkRequiredResult['msg']);
        }

        _return['msg'] = _msgList.join('<br/>');

        return _return;
    });
    resultBool = resultBool && _validation4tip['bool'];

    // CoinPosition
    var theInput$El = _this.caabmModal$El.find('input[name="CoinPosition"]');
    var theTip$El = theInput$El.parent('div').parent('div').find('.col_tip');
    var _validation4tip = ASCAABM.validation4tip(theTip$El, function () {
        var _input$El = _this.caabmModal$El.find('input[name="CoinPosition"]');
        var _return = {};
        var _msgList = [];
        _return['bool'] = true;
        _return['msg'] = '';

        var checkRequiredResult = _this.validation4checkRequired(_input$El);
        _return['bool'] = _return['bool'] && checkRequiredResult['bool'];
        if (!checkRequiredResult['bool']) {
            _msgList.push(checkRequiredResult['msg']);
        }

        var checkOnlyNumericResult = _this.validation4checkOnlyNumeric(_input$El);
        _return['bool'] = _return['bool'] && checkOnlyNumericResult['bool'];
        if (!checkOnlyNumericResult['bool']) {
            _msgList.push(checkOnlyNumericResult['msg']);
        }

        _return['msg'] = _msgList.join('<br/>');

        return _return;
    });
    resultBool = resultBool && _validation4tip['bool'];



    // habaneroGameTree, _GameKeyNames
    var theInput$El = _this.caabmModal$El.find('input[name="_GameKeyNames"]');
    var theTip$El = theInput$El.parent('div').parent('div').parent('div').parent('div').find('.col_tip');
    var _validation4tip = ASCAABM.validation4tip(theTip$El, function () {
        var _input$El = _this.caabmModal$El.find('input[name="_GameKeyNames"]');
        var _return = {};
        var _msgList = [];
        _return['bool'] = true;
        _return['msg'] = '';


        if (theInput$El.data('init-flag') != 1) {
            _return['bool'] = _return['bool'] && false;
            _msgList.push(_this.options.lang.please_wait_the_game_tree_initial);
        }

        var checkRequiredResult = _this.validation4checkRequired(_input$El);
        _return['bool'] = _return['bool'] && checkRequiredResult['bool'];
        if (!checkRequiredResult['bool']) {
            _msgList.push(checkRequiredResult['msg']);
        }

        _return['msg'] = _msgList.join('<br/>');

        return _return;
    });
    resultBool = resultBool && _validation4tip['bool'];

    return resultBool;
}

ASCAABM.validation4checkRequired = function (theValid$El) {
    var _this = this;
    var resultObj = {};
    var _NumberOfFreeSpins = theValid$El.val();
    resultObj['bool'] = false;
    if (_NumberOfFreeSpins.trim().length == 0) {
        resultObj['msg'] = _this.options.lang.is_required;
    } else {
        resultObj['bool'] = true;
        resultObj['msg'] = '';
    }

    return resultObj;
};

ASCAABM.validation4checkOnlyNumeric = function (theValid$El) {
    var _this = this;
    var resultObj = {};
    var _value = theValid$El.val();
    resultObj['bool'] = false;
    if (isNaN(_value)) {
        resultObj['msg'] = _this.options.lang.only_numeric;
    } else {
        resultObj['bool'] = true;
        resultObj['msg'] = '';
    }

    return resultObj;
};
ASCAABM.validation4tip = function (theTip$El, theCheckCallBack) {
    var _this = this;

    if (typeof (theCheckCallBack) === 'undefined') {
        theCheckCallBack = function (_valid$El) {
            return { 'bool': true, 'msg': '' };
        }
    }
    var _result = theCheckCallBack();
    var theBool = _result['bool'];
    var theMsg = _result['msg'];

    theTip$El.addClass('hide');
    theTip$El.html('');
    if (!theBool) {
        theTip$El.html(theMsg);
        theTip$El.removeClass('hide');
    }
    return _result;
};

ASCAABM.clicked_btn_append = function (e) {
    // $('#createandapplybonusmulti_form').
    var _this = this;

    _this.assignChecked_habaneroGameTree();

    var validResult = _this.validation();

    if (validResult) {

        var json4formulaBonusRelease = {};

        try {
            var formulaBonusRelease = formulaBonusReleaseEditor.getValue();
            json4formulaBonusRelease = JSON.parse(formulaBonusRelease);
        } catch (e) {
            // If error. that's should be add promorule.
        }

        var newCreateAndApplyBonusMulti = {};

        // DtStartEndUTC
        dateInputAssignToStartAndEnd(_this.caabmModal$El.find('[name="DtStartEndUTC"]'));
        var _DtEndUTC = _this.caabmModal$El.find('.DtEndUTC').val();
        newCreateAndApplyBonusMulti.DtEndUTC = moment(_DtEndUTC, 'YYYY-MM-DD HH:mm:ss').format('YYYYMMDDHHmmss');
        var _DtStartUTC = _this.caabmModal$El.find('.DtStartUTC').val();
        newCreateAndApplyBonusMulti.DtStartUTC = moment(_DtStartUTC, 'YYYY-MM-DD HH:mm:ss').format('YYYYMMDDHHmmss');



        // ExpireAfterDays
        var _ExpireAfterDays = _this.caabmModal$El.find('[name="ExpireAfterDays"]').val();
        newCreateAndApplyBonusMulti.ExpireAfterDays = parseInt(_ExpireAfterDays);

        // NumberOfFreeSpins
        var _NumberOfFreeSpins = _this.caabmModal$El.find('[name="NumberOfFreeSpins"]').val();
        newCreateAndApplyBonusMulti.NumberOfFreeSpins = parseInt(_NumberOfFreeSpins);

        // GameKeyNames
        var _gameKeyNames = _this.caabmModal$El.find('input[name="_GameKeyNames"]').val();
        // console.log('_gameKeyNames', _gameKeyNames);
        newCreateAndApplyBonusMulti._GameKeyNames = _this.catchGamePlatformIdsFromKeys(_gameKeyNames);


        newCreateAndApplyBonusMulti.couponCurrencyData = [];
        var _couponCurrencyData = {};
        // couponCurrencyData[0].CurrencyCode
        _couponCurrencyData.CurrencyCode = _this.caabmModal$El.find('input[name="CurrencyCode"]').val();
        // couponCurrencyData[0].CoinPosition
        _couponCurrencyData.CoinPosition = _this.caabmModal$El.find('input[name="CoinPosition"]').val();
        _couponCurrencyData.CoinPosition = parseInt(_couponCurrencyData.CoinPosition);
        newCreateAndApplyBonusMulti.couponCurrencyData[0] = _couponCurrencyData;

        if (typeof (json4formulaBonusRelease['insvr.CreateAndApplyBonusMulti']) === 'undefined') {
            json4formulaBonusRelease['insvr.CreateAndApplyBonusMulti'] = {};
        }
        json4formulaBonusRelease['insvr.CreateAndApplyBonusMulti'] = _this.build4CreateAndApplyBonusMulti(newCreateAndApplyBonusMulti, json4formulaBonusRelease['insvr.CreateAndApplyBonusMulti']);

        formulaBonusReleaseEditor.setValue(JSON.stringify(json4formulaBonusRelease, null, '\t'));

        _this.tipToSave$El.removeClass('hide');


        _this.destory_habaneroGameTree();

        _this.caabmModal$El.modal('hide');
    }

}; // EOF clicked_btn_append


ASCAABM.catchGamePlatformIdsFromKeys = function (str) {
    // Reference to https://regex101.com/r/zYSo3a/1
    var matchList = [];
    const regex = /gp_\d+_gt_\d+_gd_(\d+)/gm;
    while ((m = regex.exec(str)) !== null) {
        // This is necessary to avoid infinite loops with zero-width matches
        if (m.index === regex.lastIndex) {
            regex.lastIndex++;
        }

        // The result can be accessed through the `m`-variable.
        m.forEach((match, groupIndex) => {


            if (groupIndex == 0) { // gp_5623_gt_1116_gd_14145
            } else if (groupIndex == 1) { // 14145
                matchList.push(match);
            }
            // console.log(`Found match, group ${groupIndex}: ${match}`);
        });
    }
    return matchList;
}// EOF catchGamePlatformIdsFromKeys

ASCAABM.build4CreateAndApplyBonusMulti = function (theData, originalData) {
    // insvr.CreateAndApplyBonusMulti
    var _this = this;
    var createAndApplyBonusMulti = originalData;
    // console.log('build4CreateAndApplyBonusMulti.theData:', theData, originalData);
    $.each(_this.options.defaults, function (key, val) {
        // console.log('build4CreateAndApplyBonusMulti.key:', key, theData[key]);
        if (typeof (theData) !== 'undefined'
            && key in theData
        ) {
            createAndApplyBonusMulti[key] = theData[key];
        }
    });
    return createAndApplyBonusMulti;
}// EOF build4CreateAndApplyBonusMulti

ASCAABM.clicked_append_settings_caabm = function (e) {
    // function clicked_append_settings_caabm(e){
    var _this = this;
    _this.caabmModal$El.modal('show');
    // Release Bonus:  Automatic  Manual
}; // EOF clicked_append_settings_caabm

ASCAABM.createandapplybonusmultiModalEvents = function () {
    var _this = this;
    _this.caabmModal$El
        .on('show.bs.modal', function () {
            _this.showModal4CAABM()
        }).on('shown.bs.modal', function () {
            _this.shownModal4CAABM()
        }).on('hide.bs.modal', function () {
            _this.hideModal4CAABM()
        }).on('hidden.bs.modal', function () {
            _this.hiddenModal4CAABM()
        });

}; // EOF createandapplybonusmultiModalEvents

ASCAABM.showModal4CAABM = function () {
    var _this = this;

    var theCreateAndApplyBonusMulti = _this.options.defaults; // default

    // console.log('111.22 will showModal4CAABM');
    // console.log('111 will init_habaneroGameTree');
    _this.init_habaneroGameTree();

    // apply the current promo
    if (formulaBonusReleaseEditor !== null
        && typeof (formulaBonusReleaseEditor) !== 'undefined'
    ) {

        var theBonusReleaseFormula = formulaBonusReleaseEditor.getValue();
        var jsonBonusReleaseFormula = {};
        try {
            jsonBonusReleaseFormula = JSON.parse(theBonusReleaseFormula);
        } catch (e) {

        }

        if (typeof (jsonBonusReleaseFormula['insvr.CreateAndApplyBonusMulti']) === 'undefined') {
            jsonBonusReleaseFormula['insvr.CreateAndApplyBonusMulti'] = {};
        }

        theCreateAndApplyBonusMulti = jsonBonusReleaseFormula['insvr.CreateAndApplyBonusMulti'];
    }
    _this.createAndApplyBonusMulti = theCreateAndApplyBonusMulti;
    _this.assignValuesToForm(theCreateAndApplyBonusMulti);

    _this.resetValidationTip();
    _this.tipToSave$El.addClass('hide');

};
ASCAABM.shownModal4CAABM = function () {
    // console.log('111.22 will shownModal4CAABM');
};
ASCAABM.hideModal4CAABM = function () {
    // console.log('111.22 will hideModal4CAABM');
};
ASCAABM.hiddenModal4CAABM = function () {
    // console.log('111.22 will hiddenModal4CAABM');
    var _this = this;
    // console.log('hideModal4CAABM~~');
    _this.destory_habaneroGameTree();
};

ASCAABM.assignValuesToForm = function (theData) {
    var _this = this;

    // var theData = $.extend(true, _this.options.defaults, theData);

    // console.error('assignValuesToForm.theData', theData);

    if (typeof (theData.DtStartUTC) === 'undefined') {
        theData.DtStartUTC = _this.options.defaults.DtStartUTC;
    }
    if (typeof (theData.DtEndUTC) === 'undefined') {
        theData.DtEndUTC = _this.options.defaults.DtEndUTC;
    }
    // console.log('assignValuesToForm theData.DtStartUTC', theData.DtStartUTC, theData.DtEndUTC);
    // moment convert
    // theData.DtStartUTC
    var theDtStartUTC = moment(theData.DtStartUTC, 'YYYYMMDDHHmmss').format('YYYY-MM-DD HH:mm:ss');
    var theDtEndUTC = moment(theData.DtEndUTC, 'YYYYMMDDHHmmss').format('YYYY-MM-DD HH:mm:ss');
    // console.log('will dateInputAssignValue theData.theDtStartUTC', theDtStartUTC, 'theData.theDtStartUTC', theDtEndUTC);

    _this.caabmModal$El.find('.row-dtstartendutc').find('input[name="DtStartUTC"]').val(theDtStartUTC);
    _this.caabmModal$El.find('.row-dtstartendutc').find('input[name="DtEndUTC"]').val(theDtEndUTC);
    var fromStartAndEndEls = true;
    var dateInput$El = $('#createandapplybonusmultiModal [name="DtStartEndUTC"]');
    dateInputAssignValue(dateInput$El, fromStartAndEndEls);
    // $('body').on('show.daterangepicker', '#field_search_dob_without_year,#field_search_dob', function (e, picker) {
    // dateInputAssignToStartAndEnd($('#DtStartEndUTC'))
    // function dateInputAssignValue(dateInput$El, fromStartAndEndEls){

    if (typeof (theData.ExpireAfterDays) === 'undefined') {
        theData.ExpireAfterDays = _this.options.defaults.ExpireAfterDays;
    }
    // _this.options.defaults.ExpireAfterDays = 3;
    _this.caabmModal$El.find('input[name="ExpireAfterDays"]').val(theData.ExpireAfterDays);

    if (typeof (theData.NumberOfFreeSpins) === 'undefined') {
        theData.NumberOfFreeSpins = _this.options.defaults.NumberOfFreeSpins;
    }
    // _this.options.defaults.NumberOfFreeSpins = 10;
    _this.caabmModal$El.find('input[name="NumberOfFreeSpins"]').val(theData.NumberOfFreeSpins);

    if (typeof (theData._GameKeyNames) === 'undefined') {
        theData._GameKeyNames = _this.options.defaults._GameKeyNames;
    }

    _this.assignValuesToHabaneroGameTree(theData._GameKeyNames);

    if (typeof (theData.couponCurrencyData) === 'undefined') {
        theData.couponCurrencyData = _this.options.defaults.couponCurrencyData;
    }
    // console.log('assignValuesToForm theData', theData);
    // console.log('assignValuesToForm theData.couponCurrencyData', theData.couponCurrencyData);
    // _this.options.defaults.CurrencyCode = 'THB';
    _this.caabmModal$El.find('input[name="CurrencyCode"]').val(theData.couponCurrencyData[0].CurrencyCode);
    // _this.options.defaults.CoinPosition = 0;
    _this.caabmModal$El.find('input[name="CoinPosition"]').val(theData.couponCurrencyData[0].CoinPosition);

};

/**
 * To assign the game description id list into the game tree ( via jstree).
 *
 * @param {array} _GameKeyNameList The game description id list.
 */
ASCAABM.assignValuesToHabaneroGameTree = function (_GameKeyNameList) {
    var _this = this;
    // console.log('assignValuesToHabaneroGameTree._GameKeyNameList:', _GameKeyNameList);

    var theSelectedNodeList = [];

    _this.habaneroGameTree$El.jstree('uncheck_all'); //reset

    if ($.isEmptyObject(_GameKeyNameList)) {
        _GameKeyNameList = [];
    }

    // The jstree function,"get_node" canot find out the node under the closed parent node.
    _this.habaneroGameTree$El.jstree('open_all');

    $.each(_GameKeyNameList, function (key, val) {

        var sel = '[id$="_gd_' + val + '"]';

        var theNode = _this.habaneroGameTree$El.jstree('get_node', sel, true);

        if (!$.isEmptyObject(theNode)) {
            theSelectedNodeList.push(theNode[0].id);
        }

        _this.habaneroGameTree$El.jstree('check_node', sel);
    });
    // console.log('assignValuesToHabaneroGameTree.theSelectedNodeList:', theSelectedNodeList);
    _this.closeUnselectedNode(theSelectedNodeList);

}

/**
 * Close the nodes that is Not selected.
 *
 * @param {array} theSelectedList  The Id list of the selected node .
 */
ASCAABM.closeUnselectedNode = function (theSelectedList) {
    var _this = this;
    var theKeepOpenNodeList = [];

    // console.log('closeUnselectedNode.theSelectedList:', theSelectedList);

    $(_this.habaneroGameTree$El.selector + ' li').each(function (index, value) {
        var currLi$El = $(this);
        var node = _this.habaneroGameTree$El.jstree('get_node', currLi$El.prop('id'));

        // console.log('node.id:', node.id);

        if (theSelectedList.indexOf(node.id) != -1) {
            theKeepOpenNodeList.push(node.id);

            theKeepOpenNodeList.push(node.parents[0]); // for game type node, "_gt_".
            theKeepOpenNodeList.push(node.parents[1]); // for game platform node, "gp_"
        }

    });

    var theKeepOpenNodeUniqueList = theKeepOpenNodeList.filter(function (value, index, self) {
        return self.indexOf(value) === index;
    });

    // console.log('theKeepOpenNodeUniqueList:', theKeepOpenNodeUniqueList, theKeepOpenNodeList);

    $(_this.habaneroGameTree$El.selector + ' li').each(function (index, value) {
        var currLi$El = $(this);

        if (theKeepOpenNodeUniqueList.indexOf(currLi$El.prop('id')) == -1) {
            var node = _this.habaneroGameTree$El.jstree('get_node', currLi$El.prop('id'));
            _this.habaneroGameTree$El.jstree('close_node', node);
        }
    });
} // EOF closeUnselectedNode

/**
 * In the jstree, to get_checked and assign into the input,"input[name="_GameKeyNames"]".
 *
 */
ASCAABM.assignChecked_habaneroGameTree = function () {
    var _this = this;
    var checked = _this.habaneroGameTree$El.jstree('get_checked');
    _this.caabmModal$El.find('input[name="_GameKeyNames"]').val(checked.join(','));
}

ASCAABM.init_habaneroGameTree = function () {
    var _this = this;

    var _promorulesId = 0;

    if (CURRENT_PROMO_RULE !== null) {
        _promorulesId = CURRENT_PROMO_RULE.promorulesId;
    }

    var theUri = _this.options.base_url + 'api/get_haba_game_tree_by_promo_formula/' + _promorulesId;

    _this.habaneroGameTree$El.jstree({
        'core': {
            'data': {
                "url": theUri,
                "dataType": "json" // needed only if you do not supply JSON headers
            }
        },
        "input_number": {
            "form_sel": '#createandapplybonusmulti_form'
        },
        "checkbox": {
            "tie_selection": false,
        },
        "plugins": [
            "search", "checkbox"
        ]
    });

}; // EOF init_habaneroGameTree

ASCAABM.destory_habaneroGameTree = function () {
    var _this = this;
    // console.log('destory_habaneroGameTree:', _this.habaneroGameTree$El);
    // var rr = _this.habaneroGameTree$El.jstree('destory');
    // console.log('rr:', rr);

};
