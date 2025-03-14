var level_upgrade = level_upgrade || {};

level_upgrade.initialize = function (theOptions) {
    var _this = this;
    _this.baseUrl = '/./';
    _this.defaultItemsPerPage = 7; // <?= $this -> utils -> getDefaultItemsPerPage() ?>,
    _this.use_new_sbe_color = 1; // $this->utils->getConfig('use_new_sbe_color')
    _this.enable_separate_accumulation_in_setting = '1'; // enable_separate_accumulation_in_setting
    _this.enable_accumulation_computation = 1; // keep 1

    _this = $.extend(true, _this, theOptions);

    _this.theLANG = {};
    _this.theLANG.UpgradeSettingDeleted = 'Upgrade Setting Deleted.'; // lang('Upgrade Setting Deleted')
    _this.theLANG.SuccessfullyUpdateSetting = 'Successfully Update Setting.'; // lang('Successfully Update Setting')
    _this.theLANG.SuccessfullySaveSetting = 'Successfully Save Setting.'; // lang('Successfully Save Setting')
    _this.theLANG.player_mp14 = 'player.mp14';// lang('player.mp14')
    _this.theLANG.SuccessfullyDisableSetting = 'Successfully disable setting.'; // lang('Successfully disable setting')
    _this.theLANG.SuccessfullyEnableSetting = 'Successfully enable setting'; // lang('Successfully enable setting')
    _this.theLANG.Accumulation = 'Accumulation.'; // '<?=lang('cms.accumulation');?>
    _this.theLANG.ACFRD = 'Accumulation Computation From Registration Date.';
    _this.theLANG.ACFLUP = 'Accumulation Computation From Last Upgeade Period.';
    _this.theLANG.ACFLDP = 'Accumulation Computation From Last Downgrade Period.';
    _this.theLANG.ACFLCP = 'Accumulation Computation From Last Changed Period.';
    _this.theLANG.UpgradeOnly = 'Upgrade Only.';
    _this.theLANG.UpgradeAndDowngrade = 'Upgrade and Downgrade.';
    _this.theLANG.DowngradeOnly = 'Downgrade Only.';
    _this.theLANG.Preview = 'Preview.';
    _this.theLANG.Active = 'Active.'; // <?= lang("lang.active"); ?>
    _this.theLANG.Inactive = 'Inactive.'; // <?= lang("lang.inactive"); ?>
    _this.theLANG.BetAmount = 'Bet Amount.'; // AMOUNT_MSG.BET
    _this.theLANG.DepositAmount = 'Deposit Amount.'; // AMOUNT_MSG.DEPOSIT
    _this.theLANG.LossAmount = 'Loss Amount.'; // AMOUNT_MSG.LOSS
    _this.theLANG.WinAmount = 'Win Amount.'; // AMOUNT_MSG.WIN
    // var AMOUNT_MSG = {
    //     BET         : '<?= lang('Bet Amount'); ?>',
    //     DEPOSIT     : '<?= lang('Deposit Amount'); ?>',
    //     LOSS        : '<?= lang('Loss Amount'); ?>',
    //     WIN         : '<?= lang('Win Amount'); ?>'
    // };

    _this.formulaInput$El = $('.formula-container>input[name="formula"]');
    _this.betSettingsInput$El = $('.formula-container>input[name="bet_settings"]');
    _this.accumulationSettingsInput$El = $('.formula-container>input[name="accumulation_settings"]');
    _this.theLevelUpModal$El = $('#levelUpModal,#levelUpModal_v2');
    _this.settingTbl$El = $('#settingTbl');
    _this.listContainer$El = $('#listContainer'); // var $listContainer = $('#listContainer');
    _this.optionKey = ['bet_amount', 'deposit_amount', 'win_amount', 'loss_amount'];

    // var options = {
    _this._options = {
        "positionClass": "toast-top-center",
        closeButton: true,
        timeOut: 1000,
        preventDuplicates: true
    };

    _this.defaults = {};
    _this.defaults.operator = 'and';
    _this.defaults.mathSymbol = '>=';
    _this.defaults.amount = 0;

    _this.defaults.bet_settings = {};
    _this.defaults.bet_settings.itemList = [];
    _this.defaults.bet_settings.defaultItem = {};
    _this.defaults.bet_settings.defaultItem.value = 0;
    _this.defaults.bet_settings.defaultItem.math_sign = '>=';


    _this.defaults.formula = {};
    _this.defaults.formula['bet_amount'] = [_this.defaults.mathSymbol, _this.defaults.amount];

    _this.defaults.formula['operator_2'] = _this.defaults.operator;
    _this.defaults.formula['deposit_amount'] = [_this.defaults.mathSymbol, _this.defaults.amount];

    _this.defaults.formula['operator_3'] = _this.defaults.operator;
    _this.defaults.formula['loss_amount'] = [_this.defaults.mathSymbol, _this.defaults.amount];

    _this.defaults.formula['operator_4'] = _this.defaults.operator;
    _this.defaults.formula['win_amount'] = [_this.defaults.mathSymbol, _this.defaults.amount];

    _this.defaults.accumulation = '0';
    _this.defaults.accumulation_settings = {};
    _this.defaults.accumulation_settings.bet_amount = {};
    _this.defaults.accumulation_settings.bet_amount.accumulation = _this.defaults.accumulation;
    _this.defaults.accumulation_settings.deposit_amount = {};
    _this.defaults.accumulation_settings.deposit_amount.accumulation = _this.defaults.accumulation;
    _this.defaults.accumulation_settings.loss_amount = {};
    _this.defaults.accumulation_settings.loss_amount.accumulation = _this.defaults.accumulation;
    _this.defaults.accumulation_settings.win_amount = {};
    _this.defaults.accumulation_settings.win_amount.accumulation = _this.defaults.accumulation;
    // {"bet_amount": {"accumulation": "4"}, "win_amount": {"accumulation": "1"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}

    _this.detect_console_log();

    _this.safelog('load level_upgrade.js');
    return _this;
};


level_upgrade.detect_console_log = function () {
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

level_upgrade.safelog = function (msg) {
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
level_upgrade.parse_query_string = function (query) {
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

level_upgrade.applyLang = function (theLANG) {
    var _this = this;
    _this.theLANG = $.extend(true, _this.theLANG, theLANG);
}


level_upgrade.init_accumulation_settingsFromComm = function (commAccumulation) {
    var _this = this;
    var _accumulation_settings = JSON.parse(JSON.stringify(_this.defaults.accumulation_settings)); // clone object
    Object.keys(_this.defaults.accumulation_settings).map(function (keyStr, indexNumber) {
        _accumulation_settings[keyStr].accumulation = commAccumulation;
    });
    return _accumulation_settings;
}

level_upgrade.autoAddOperatorDuringOptions = function (theFormula) {
    var _this = this;
    var _formula = JSON.parse(JSON.stringify(theFormula)); // clone object
    for (var i = 1; i < _this.optionKey.length + 1; i++) {
        // console.log('autoAddOperatorDuringOptions.i', i);
        _formula = _this.addOperatorByOptionId(_formula, i);
    }
    return _formula;
}

level_upgrade.addOperatorByOptionId = function (theFormula, theOptionId) {
    var _this = this;
    var _formula = JSON.parse(JSON.stringify(theFormula)); // clone object
    var theOptionKey = _this.jsonKey(theOptionId);
    if (typeof (_formula[theOptionKey]) !== 'undefined') {
        if (typeof (_formula['operator_' + theOptionId]) === 'undefined') {
            _formula['operator_' + theOptionId] = _this.defaults.operator;
        }
    }
    return _formula;
}; // EOF addOperatorByOptionId

/**
 * Sort/Fileter the elements of Formula by "defaults.formula".
 *
 * @param {object} theFormula  The Formula structure like as the attribute,"defaults.formula".
 * @return The formula after sorted/filted the elements in that.
 */
level_upgrade.sortFormulaByDefaults = function (theFormula) {
    var _this = this;
    var _formulaSorted = {};
    var theFormulaCopy = JSON.parse(JSON.stringify(theFormula)); // clone object
    Object.keys(_this.defaults.formula).map(function (keyStr, indexNumber) {
        if (typeof (theFormulaCopy[keyStr]) !== 'undefined') {
            _formulaSorted[keyStr] = theFormulaCopy[keyStr];
        }
    })
    theFormulaCopy = _formulaSorted;
    // console.log('sortFormula._formulaSorted: ', _formulaSorted);
    return theFormulaCopy;
}; // EOF sortFormula

/**
 * Remove the First element, like as "operator_2". If the pre-element is empty.
 * @param {object} theSortedFormula Recommend the object sorted by the "defaults.formula".
 * @return {object} The fixed object.
 */
level_upgrade.removeFirstOperatorN = function (theSortedFormula) {
    var theSortedFormulaCopy = JSON.parse(JSON.stringify(theSortedFormula)); // clone object

    var firstKeyStr = null;
    var preKeyStr = null;
    // var nextKeyStr = null;
    // detect the first operator and remove it.
    Object.keys(theSortedFormula).map(function (keyStr, indexNumber) {
        if (firstKeyStr === null
            && keyStr.indexOf('operator_') == -1
        ) {
            preKeyStr = keyStr;
        }
        if (keyStr.indexOf('operator_') > -1) {
            if (firstKeyStr === null) {
                firstKeyStr = keyStr;
            }
        }
        // if (firstKeyStr !== null
        //     && nextKeyStr === null
        // ) {
        //     nextKeyStr = keyStr;
        // }
    });

    // reset preKeyStr and nextKeyStr, if not found.
    if (firstKeyStr === null) {
        preKeyStr = null;
        // nextKeyStr = null;
    }

    if (firstKeyStr !== null) {
        if (preKeyStr === null) {
            delete theSortedFormulaCopy[firstKeyStr];
        }
    }

    return theSortedFormulaCopy;
}; // EOF removeFirstOperatorN

level_upgrade.onReady = function () {
    var _this = this;

    $(document).on('show.bs.modal', '.modal', function (event) {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);

        setTimeout(function () {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);

    });
    $(document)
        .on('show.bs.modal', '#levelUpModal,#levelUpModal_v2', function (e) { // on show
            _this.resetFormModal();
        }).on('shown.bs.modal', '#levelUpModal,#levelUpModal_v2', function (e) { // after show

        }).on('hide.bs.modal', '#levelUpModal,#levelUpModal_v2', function (e) { // on hidden

        }).on('hidden.bs.modal', '#levelUpModal,#levelUpModal_v2', function (e) { // after hidden

        });

    _this.init_settingTbl_DataTable();

    // click .updateSetting in #settingTbl for edit setting
    _this.settingTbl$El.on('click', '.updateSetting', function (e) {
        var target$El = $(e.target);
        var tr$El = target$El.closest('tr');
        var data = _this.settingTbl_DataTable.row(tr$El).data();
_this.safelog('227.clicked_updateSetting',e);
        _this.addFormData(data);
    });
    // click .enableDisable in #settingTbl
    _this.settingTbl$El.on('click', '.enableDisable', function (e) {

        var status = $(this).attr('data-status');
        $.post(_this.baseUrl + 'vipsetting_management/enableDisableSetting',
            {
                id: $(this).attr('data-id'),
                status: status
            }, function () {
                if (status == 1) {
                    toastr.success(_this.theLANG.SuccessfullyDisableSetting, '', _this._options);
                } else {
                    toastr.success(_this.theLANG.SuccessfullyEnableSetting, '', _this._options);
                }

                settingTbl_DataTable.ajax.reload(null, false);
                _this.loadUpDownGradeSetting();
            }
        ); /// EOF $.post(...
    });// EOF $('#settingTbl').on('click', '.enableDisable', function(e){...

    // click .deleteSetting in #settingTbl
    _this.settingTbl$El.on('click', '.deleteSetting', function (e) {
        $('#name').text($(this).attr('data-name'));
        $('#hiddenId').val($(this).attr('data-id'));
        $('#deleteModal').modal('show');
    });

    $('#showList').on('click', function () {
        if (_this.listContainer$El.hasClass('hide')) {
            _this.listContainer$El.removeClass('hide');
            $(this).html('<i class="fa fa-caret-square-o-down" aria-hidden="true"></i> ' + LANG.HIDE_LIST);
        } else {
            _this.listContainer$El.addClass('hide');
            $(this).html('<i class="fa fa-caret-square-o-right" aria-hidden="true"></i> ' + LANG.SHOW_LIST);
        }
    });

    $('#saveSettingBtn').on('click', function () {
        if (_this.validateFields()) {
            toastr.error(_this.theLANG.player_mp14, '', _this._options);
            return false;
        }

        var conjunction = [];
        var formula = {};
        var $id = _this.theLevelUpModal$El.find('#upgradeId').val();

        _this.theLevelUpModal$El.find('.help-block .conjunction').each(function () {
            var $this = $(this);
            if ($this.is(':checked')) {
                conjunction.push('and');
            } else {
                conjunction.push('or');
            }
        });

        // var arrayLenth = checkedRows.length;
        // if (arrayLenth >= 1) {
        //     for (var i = 0; i < arrayLenth; i++) {
        //         if (checkedRows[i]) {
        //             var x = checkedRows[i];
        //             var name = _this.jsonKey(x);
        //             var operator = _this.theLevelUpModal$El.find('#operator-' + x).val();
        //             var amount = _this.theLevelUpModal$El.find('#amount-' + x).val();
        //             formula[name] = [operator, amount];
        //         }
        //     }
        // }
        var formulaStr = $('input[name="formula"]').val();
        formula = JSON.parse(formulaStr);

        var bet_settingsStr = $('input[name="bet_settings"]').val();
        bet_settings = JSON.parse(bet_settingsStr);

        var accumulation_settingsStr = $('input[name="accumulation_settings"]').val();
        accumulation_settings = JSON.parse(accumulation_settingsStr);


        var accumulation = $('input[type="radio"][name="accumulation"]:checked').val();
        var accumulationFrom = default_accumulationFrom; // default, disable accumulationFrom
        // display for setup, hide for disable.
        // if ($('input[type="radio"][name="accumulationFrom"]:visible').length > 0) {  // accumulationFrom visable?
        if (accumulation > 0) {
            accumulationFrom = $('input[type="radio"][name="accumulationFrom"]:checked').val();
        }

        var data = {
            settingName: $('#settingName').val(),
            description: $('#description').val(),
            levelUpgrade: $('#levelUpgrade').val(),
            'accumulationFrom': accumulationFrom,
            'formula': formula,
            'accumulation': accumulation,
            conjunction: conjunction,
            'bet_settings': bet_settings,
            'accumulation_settings': accumulation_settings
        };
        if ($id) {
            data.upgrade_id = $id;
        }

        if (!$.isEmptyObject(_this.enable_separate_accumulation_in_setting)) {
            // catch the checked xxx_amount and selected accumulation.
            $('fieldset[class*="fieldset_"]:has(.option:checked) [name*="accumulation"]:checked').each(function (indexNumber, currEl) {
                var curr$El = $(currEl);
                var optionName = curr$El.prop('name');
                optionName = optionName.split('accumulation_').join(''); // remove prefix,"accumulation_"
                data['accumulation_' + optionName] = curr$El.val();
            });
        } // EOF if( enable_separate_accumulation_in_setting ){...

        $(this).find('i').addClass('fa-refresh fa-spin');
        // setTimeout(function () {
        var jqxhr = $.post(_this.baseUrl + 'vipsetting_management/saveUpgradeSetting', data, function (data) {
            if ($id) {
                toastr.success(_this.theLANG.SuccessfullyUpdateSetting, '', _this._options);
            } else {
                toastr.success(_this.theLANG.SuccessfullySaveSetting, '', _this._options);
            }
            _this.settingTbl$El.DataTable().ajax.reload(null, false);
            _this.loadUpDownGradeSetting();
            _this.resetFormModal();
        });
        jqxhr.done(function () {
            $('#saveSettingBtn').find('i').removeClass('fa-refresh fa-spin');
        });
        // }, 200); // EOF setTimeout(function(){...
    }); // EOF $('#saveSettingBtn').on('click', function(){...

    $('#deleteBtn').on('click', function () {
        $('#deleteBtn').find('i').addClass('fa-refresh fa-spin');
        setTimeout(function () {
            $.post(_this.baseUrl + 'vipsetting_management/deleteUpgradeLevelSetting', { id: $('#hiddenId').val() }, function () {
                toastr.success(_this.theLANG.UpgradeSettingDeleted, '', _this._options);
                _this.settingTbl$El.DataTable().ajax.reload(null, false);
                _this.loadUpDownGradeSetting();
                $('#deleteModal').modal('hide');
                $('#deleteBtn').find('i').removeClass('fa-refresh fa-spin');
            });
        }, 200)
    });

    $('.option').change(function (e) { // the ".option" element change event
        var accumulation = _this.theLevelUpModal$El.find('input[type="radio"][name="accumulation"]:checked').val();
        var isCheck = $(this).is(":checked");
        var value = $(this).val(); // optionId

        if (isCheck) {
            checkedRows.push($(this).val());
        } else {
            // remove the item form checkedRows
            var index = checkedRows.indexOf($(this).val());
            if (index > -1) {
                checkedRows.splice(index, 1);
            }
        }
        // var _formulaStr = _this.formulaInput$El.val();
        var _formulaStr = _this.getFormulaInputValWithDefault()
        // var _betSettingsStr = _this.betSettingsInput$El.val();
        var _betSettingsStr = _this.getBetSettingsInputValWithDefault();
        // var _accumulationSettingsStr = _this.accumulationSettingsInput$El.val();
        var _accumulationSettingsStr = _this.getAccumulationSettingsInputValWithDefault();

        if (_accumulationSettingsStr == '') {
            if (!$.isEmptyObject(_this.enable_separate_accumulation_in_setting)) {
                _accumulationSettingsStr = JSON.stringify(_this.defaults._this.defaults.accumulation_settings);
            } else {
                _accumulationSettingsStr = _this.defaults.accumulation;
            }
        }

        var _formula = JSON.parse(_formulaStr);

        var optionTitle = _this.jsonKey(value); // loss_amount

        if (optionTitle == 'bet_amount') {
            if (isCheck) {
                $('.bet_amount_settings_more').removeClass('disabled');
            } else {
                $('.bet_amount_settings_more').addClass('disabled');
            }
        }

        // console.log('optionTitle:', optionTitle);
        if (isCheck) {
            _formula['operator_' + value] = _this.defaults.operator;

            if (typeof (_formula[optionTitle]) === 'undefined') { // for update from bet_amount_settings
                _formula[optionTitle] = [];
            }
            if (typeof (_formula[optionTitle][0]) === 'undefined') { // for update from bet_amount_settings
                _formula[optionTitle][0] = _this.defaults.mathSymbol;
            }
            if (typeof (_formula[optionTitle][1]) === 'undefined') { // for update from bet_amount_settings
                _formula[optionTitle][1] = _this.defaults.amount;
            }

            // detect next exists and add operator_(n+1)
            _formula = _this.autoAddOperatorDuringOptions(_formula);
            if (optionTitle == 'bet_amount') {
                _betSettingsStr = JSON.stringify(null);// _this.defaults.bet_settings

                // delete _formula['operator_' + value]; // without operator_1
            }
        } else {
            delete _formula['operator_' + value];
            delete _formula[optionTitle];
            if (optionTitle == 'bet_amount') {
                _betSettingsStr = JSON.stringify(null);
            }
        }

        /// the properties sort by the defaults
        // var _formulaSorted = {};
        // Object.keys(_this.defaults.formula).map(function (keyStr, indexNumber) {
        //     if (typeof (_formula[keyStr]) !== 'undefined') {
        //         _formulaSorted[keyStr] = _formula[keyStr];
        //     }
        // });
        // _formula = _formulaSorted;
        _formula = _this.sortFormulaByDefaults(_formula);
        // console.log('option.change._formulaSorted: ', _formula);

        /// detect the first operator and remove it.
        // var firstKeyStr = Object.keys(_formula)[0];
        // if (firstKeyStr.indexOf('operator_') > -1) {
        //     delete _formula[firstKeyStr];
        // }
        _formula = _this.removeFirstOperatorN(_formula);

        // replace and redraw
        _formulaStr = JSON.stringify(_formula);

        // console.log('option.change: ', _formulaStr);


        if (enable_separate_accumulation_in_setting) {
            var theTarget$El = $(e.target);
            var optionName = _this.jsonKey(theTarget$El.val());
            var accumulationVal = theTarget$El.closest('fieldset').find('input[name*="accumulation"]:checked').val();

            if (typeof (accumulationVal) === 'undefined') {
                accumulationVal = 0; // No accumulation
            }
            _this.toggleAndSelectAccumulationByOption(optionName, isCheck, accumulationVal);
        }// EOF if( enable_separate_accumulation_in_setting ){

        var $deferred = _this.redrawFormula(_formulaStr, _accumulationSettingsStr, _betSettingsStr);

        $deferred.always(function () { // execute next step
            // _this.activateToolTip();
            // _this.loadBootstrapToggle();
        });

        // theFormulaStr, accumulation_or_separate_accumulation_settings, betAmountSettingsStr

        // var checkedRowsLength = $('.option:checked').length;
        // _this.loadFormula(value, checkedRowsLength, isCheck, 0, 0, 'and', parseInt(accumulation));

        // if (enable_separate_accumulation_in_setting) {
        //     var theTarget$El = $(e.target);
        //     var optionName = _this.jsonKey(theTarget$El.val());
        //     var accumulationVal = theTarget$El.closest('fieldset').find('input[name*="accumulation"]:checked').val();

        //     if (typeof (accumulationVal) === 'undefined') {
        //         accumulationVal = 0; // No accumulation
        //     }
        //     _this.toggleAndSelectAccumulationByOption(optionName, isCheck, accumulationVal);
        // }// EOF if( enable_separate_accumulation_in_setting ){

        // _this.activateToolTip();
        // _this.loadBootstrapToggle();
    }); // EOF $('.option').change(function() {...

    $(document).on('reset', '#levelUpModal form,#levelUpModal_v2 form', function (e) {

        setTimeout(function () { // Patch for reset failed, at the moment, the ".option" elememts is changing...
            // sync accumulationNoption_XXX_amount
            $('.option').each(function (indexNumber, currEl) {
                var curr$El = $(currEl);
                var currOptionName = _this.jsonKey(curr$El.val());
                _this.toggleAccumulationNoption(currOptionName);
            });

            $('.col-separate_options .text-danger').addClass('hide');
        }, 0);

    });

    // 調整個項目的 accumulation 同步 formula 介面
    var selectorStrList = [];
    selectorStrList.push('input[type="radio"][name*="accumulation"]');
    selectorStrList.push('input[type="radio"][name="accumulationFrom"]');
    _this.theLevelUpModal$El.on('change', selectorStrList.join(','), function (e) {
        var target$El = $(e.target);
        // var option$El = target$El.closest('fieldset').find('.option');
        var optionName = target$El.prop('name');
        optionName = optionName.split('accumulation_').join(''); // remove prefix,"accumulation_"

        // _this.applyAccumulationInFormula(optionName); // orig

        // for enable_separate_accumulation_in_setting = true
        var accumulationVal = $('input[type="radio"][name="accumulation_' + optionName + '"]:checked').val();
        // load origial settings
        // var _formulaStr = _this.formulaInput$El.val();
        var _formulaStr = _this.getFormulaInputValWithDefault()
        // var _betSettingsStr = _this.betSettingsInput$El.val();
        var _betSettingsStr = _this.getBetSettingsInputValWithDefault();
        // var _accumulationSettingsStr = _this.accumulationSettingsInput$El.val();
        var _accumulationSettingsStr = _this.getAccumulationSettingsInputValWithDefault();
        if ($.isEmptyObject(_accumulationSettingsStr)) {
            _accumulationSettingsStr = _this.defaults.accumulation;
        }
        var _accumulationSettings = JSON.parse(_accumulationSettingsStr);
        /// update settings
        if (!$.isEmptyObject(_this.enable_separate_accumulation_in_setting)) { // for separate_accumulation
            // if (typeof (_accumulationSettings) === 'number') { // from common accumulation
            if (!isNaN(_accumulationSettings)) { // from common accumulation
                _accumulationSettings = _this.init_accumulation_settingsFromComm(_accumulationSettingsStr);
            }

            if (typeof (_accumulationSettings[optionName]) === 'undefined') {
                _accumulationSettings[optionName] = {};
            }
            _accumulationSettings[optionName]['accumulation'] = accumulationVal;
            _accumulationSettingsStr = JSON.stringify(_accumulationSettings);
        } else { // for common accumulation
            var accumulationVal = $('input[type="radio"][name="accumulation"]:checked').val();
            if (parseInt(accumulationVal) > 0) {
                accumulationVal = $('input[type="radio"][name="accumulationFrom"]:checked').val();
            }
            _accumulationSettingsStr = accumulationVal;
        }



        // console.log('417._accumulationSettingsStr', _accumulationSettingsStr);
        // console.log('417._betSettingsStr', _betSettingsStr);
        var $deferred = _this.redrawFormula(_formulaStr, _accumulationSettingsStr, _betSettingsStr);

    }); // EOF $('#levelUpModal,#levelUpModal_v2').on('change', selectorStrList.join(','), function(e){...

    _this.theLevelUpModal$El.on('change', 'input[type="radio"][name="accumulation"]', function (e) {
        // console.log('change.input[type="radio"][name="accumulation"]:', arguments);
        var accumulation$El = $(this);
        var target$El = $(e.target);
        if (target$El.find('input:radio[name="accumulation"]').length > 0) { // for click on label
            accumulation$El = target$El.find('input:radio:checked[name="accumulation"]');
        }
        var accumulation = accumulation$El.val();
        var selectorStrList = [];
        selectorStrList.push('#levelUpModal .help-block > div > label');
        selectorStrList.push('#levelUpModal_v2 .help-block label.inline-name');
        $(selectorStrList.join(',')).each(function () { // .formula-container

            if (parseInt(accumulation) == 1) {

                var org_text = $(this).text();

                if ($(this).find('.accumulation').length == 0) {

                    var accumulationNameHtml = _this.getAccumulationToInlineOptionFromTpl(org_text);

                    $(this).html(accumulationNameHtml); // Accumulation Computation
                }
            } else {
                $(this).find('.accumulation').remove();
            }
        });

        if (accumulation == 1) { // selected "Yes"
            _this.displayComputation(true);
        } else { // selected "No"
            _this.displayComputation(false);
        }
    }); // EOF $('#levelUpModal input[type="radio"][name="accumulation"]').on('change', function(){




}; // EOF level_upgrade.onReady



/**
 * To enable The Accumulation N options,(0,1,4) By the Option Name
 *
 * @param string optionName The Option Name contains, "bet_amount", "disposit_amount", "win_amount" and "loss_amount".
 */
level_upgrade.enableAccumulationNoption = function (optionName) {

    var selectorList = [];
    selectorList.push('[name="accumulation_' + optionName + '"]');
    var theRadio$El = $(selectorList.join(','));
    theRadio$El.prop('disabled', false);

    theRadio$El.closest("label").removeClass('disabled').addClass('cursor-pointer');
} // EOF enableAccumulationNoption()

/**
 * To disable The Accumulation N options,(0,1,4) By the Option Name
 *
 * @param string optionName The Option Name contains, "bet_amount", "disposit_amount", "win_amount" and "loss_amount".
 */
level_upgrade.disableAccumulationNoption = function (optionName) {

    var selectorList = [];
    selectorList.push('[name="accumulation_' + optionName + '"]');
    var theRadio$El = $(selectorList.join(','));
    theRadio$El.prop('disabled', true);

    theRadio$El.closest("label").addClass('disabled').removeClass('cursor-pointer');

} // EOF disableAccumulationNoption()


/**
 * Display OR hidden the computation row
 * @param {boolean} enforceShow To display the computation row if true, else hidden.
 */
level_upgrade.displayComputation = function (enforceShow) {
    var _this = this;
    if (!_this.enable_accumulation_computation) {
        enforceShow = false;
    }
    var computation$El = $('.row-computation');
    if (enforceShow === true) {
        // to/keep display
        if (computation$El.hasClass('hide')) { // hide to display
            computation$El.removeClass('hide');
            _this.animateCSS(computation$El[0], 'fadeIn', function () {
                computation$El.removeClass('fadeIn');
            });
        } // EOF if( computation$El.hasClass('hide') )
    } else {
        // to/keep hidden
        if (!computation$El.hasClass('hide')) { // not hide, display to hide
            _this.animateCSS(computation$El[0], 'fadeOut', function () {
                computation$El.addClass('hide');
                computation$El.removeClass('fadeOut');
            });
        } // EOF if( ! computation$El.hasClass('hide') )
    } // EOF if(enforceShow === true)
} // EOF displayComputation

/**
 * Get element's outter Html like node.outerHTML .
 * @param {string} selectorStr The selector string.
 * @return {string} The html script.
 */
level_upgrade.outerHtml = function (selectorStr) {
    return $('<div>').append($(selectorStr).clone()).html();
};

/**
 * Apply Accumulation Info into the Formula div By the Option Name.
 *
 * @param string optionName The Option Name contains, "bet_amount", "disposit_amount", "win_amount" and "loss_amount".
 */
level_upgrade.applyAccumulationInFormula = function (optionName) {
    var _this = this;
    var accumulationVal = $('input[type="radio"][name="accumulation_' + optionName + '"]:checked').val();
    // console.log('applyAccumulationInFormula.optionName:', optionName);
    // var selectorStrList = [];
    // selectorStrList.push('#levelUpModal .help-block > div.' + _this.jsonValByKey(optionName));
    // selectorStrList.push('#levelUpModal_v2 .help-block > div.' + _this.jsonValByKey(optionName));
    // var label$El = $(selectorStrList.join(',')).find('label'); // in .formula-container

    var label$El = _this.theLevelUpModal$El.find('.help-block .inline.' + _this.jsonValByKey(optionName));
    if (label$El.find('.accumulation').length == 0) {
        label$El.prepend('<span class="accumulation">'); // initial
    }

    if (accumulationVal > 0) {
        label$El.find('.accumulation').text(_this.theLANG.Accumulation);
    } else {
        label$El.find('.accumulation').text('');
    }
} // EOF applyAccumulationInFormula

level_upgrade.validateFields = function () {
    var _this = this;
    var isEmpty = false;
    var $s = _this.theLevelUpModal$El.find('#settingName').val();
    var $d = _this.theLevelUpModal$El.find('#description').val();
    var $l = _this.theLevelUpModal$El.find('#levelUpgrade').val();

    //  Patch for the issue, Save the existing setting as empty ".option" , click "Save Setting" after remove all ".option ".
    var checkedRowsLength = $('.option:checked').length;
    if ($s == '' || $d == '' || $l == '' || checkedRowsLength <= 0) {
        isEmpty = true;
    }
    return isEmpty;
}

level_upgrade.loadUpDownGradeSetting = function () {
    var _this = this;
    return loadUpDownGradeSetting();
};


level_upgrade.init_settingTbl_DataTable = function () {
    var _this = this;

    if (typeof (_this.settingTbl_DataTable) === 'undefined') {
        _this.settingTbl_DataTable = _this.settingTbl$El.DataTable({
            ajax: {
                url: _this.baseUrl + 'vipsetting_management/upgradeLevelSetting',
                type: 'POST',
                async: true
            },
            "order": [[0, "desc"]],
            searching: true,
            lengthChange: true,
            pageLength: _this.defaultItemsPerPage, // <?= $this -> utils -> getDefaultItemsPerPage() ?>,
            columns: [
                { data: 'upgrade_id', visible: false },
                { data: 'setting_name' },
                { data: 'description' },
                { data: 'formula',
                    render: function (data, type, row) {
                        var formula = jQuery.parseJSON(data);
                        var formulaHtml = '';
                        var operator = '', amount = '';
                        var arr = '';
                        var formulaKey = Object.keys(formula);
                        for (var i in formulaKey) {
                            if (_this.optionKey.indexOf(formulaKey[i]) >= 0) {
                                arr = formula[formulaKey[i]];
                                operator = arr[0];
                                amount = arr[1];
                                if (parseInt(row.accumulation) == 1) {
                                    formulaHtml += _this.theLANG.Accumulation;
                                }
                                formulaHtml += _this.optionNameByKey(formulaKey[i]) + ' ' + operator + ' ' + amount + ' ';
                            } else {
                                formulaHtml += formula[formulaKey[i]] + ' ';  // conjunction (or and)
                            }
                        }

                        // Accumulation Computation
                        var theLANG_AC = '';
                        switch (row.accumulation) {
                            default:
                            case _this.ACCUMULATION_MODE_DISABLE:
                                break;
                            case _this.ACCUMULATION_MODE_FROM_REGISTRATION:
                                theLANG_AC = _this.theLANG.ACFRD; // '<?= lang('Accumulation Computation From Registration Date'); ?>';
                                formulaHtml += '<br>';
                                formulaHtml += theLANG_AC;
                                break;
                            case _this.ACCUMULATION_MODE_LAST_UPGEADE:
                                theLANG_AC = _this.theLANG.ACFLUP; // '<?= lang('Accumulation Computation From Last Upgeade Period'); ?>';
                                formulaHtml += '<br>';
                                formulaHtml += theLANG_AC;
                                break;
                            case _this.ACCUMULATION_MODE_LAST_DOWNGRADE:
                                theLANG_AC = _this.theLANG.ACFLDP; // '<?= lang('Accumulation Computation From Last Downgrade Period'); ?>';
                                formulaHtml += '<br>';
                                formulaHtml += theLANG_AC;
                                break;
                            case _this.ACCUMULATION_MODE_LAST_CHANGED_GEADE:
                                theLANG_AC = _this.theLANG.ACFLCP; // '<?= lang('Accumulation Computation From Last Changed Period'); ?>';
                                formulaHtml += '<br>';
                                formulaHtml += theLANG_AC;
                                break;
                            // case _this.ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS:
                            case _this.ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET:
                                // @todo OGP-24373
                                break;
                        }

                        var title = '';
                        if (row.level_upgrade == _this.UPGRADE_ONLY) {
                            title = _this.theLANG.UpgradeOnly; // '<?= lang('Upgrade Only'); ?>';
                        } else if (row.level_upgrade == _this.UPGRADE_DOWNGRADE) {
                            title = _this.theLANG.UpgradeAndDowngrade; // '<?= lang('Upgrade and Downgrade'); ?>';
                        } else if (row.level_upgrade == _this.DOWNGRADE_ONLY) {
                            title = _this.theLANG.DowngradeOnly; // '<?= lang('Downgrade Only'); ?>';
                        }

                        var classStr = 'red-tooltip';
                        if (_this.use_new_sbe_color) {
                            classStr = classStr + 'btn btn-sm btn-linkwater';
                        }

                        return '<button type="button" title="' + title + '" data-placement="top" data-toggle="popover" data-trigger="focus" ' +
                            'data-content="' + formulaHtml + '" class="' + classStr + '">' + _this.theLANG.Preview + '</button>';
                    }
                },
                {
                    data: 'status',
                    render: function (data) {
                        var status = '';

                        var colorTag = "#66cc66";
                        if (_this.use_new_sbe_color) {
                            colorTag = "#3C8795";
                        }

                        if (data == 1) {
                            status = '<span style="color:' + colorTag + ';font-weight:bold;"><?= lang("lang.active"); ?></span>';
                        } else {
                            status = '<span style="color:#ff6666;font-weight:bold;">' + _this.theLANG.Active + '</span>';
                        }
                        return status;
                    }
                },
                {
                    data: 'upgrade_id',
                    render: function (data, type, row) {
                        var glypIcon = '', title = '', color = '';
                        if (row.status == 1) {
                            glypIcon = 'glyphicon-ban-circle';
                            title = LANG.DISABLE;
                            color = '#D1374A';
                        } else {
                            glypIcon = 'glyphicon-ok-sign';
                            title = LANG.ENABLE;
                        }
                        return '<a data-toggle="tooltip" class="deleteSetting" data-id="' + data + '" data-name="' + row.setting_name + '" data-original-title="' + LANG.DELETE + '"><span class="glyphicon glyphicon glyphicon-trash"  style="color:' + color + '"></span> </a>  ' +
                            '<a data-toggle="tooltip" class="enableDisable" data-id="' + data + '" data-original-title="' + title + '" data-status="' + row.status + '"><span class="glyphicon ' + glypIcon + '" style="color:' + color + '"></span></a> ' +
                            '<a data-toggle="tooltip" class="updateSetting" data-id="' + data + '" data-original-title="' + LANG.EDIT + '"><span class="glyphicon glyphicon-edit"></span> </a>';
                    }
                }
            ],
            drawCallback: function (data) {

                $('[data-toggle="popover"]').popover({
                    html: true
                });
            },
            rowCallback: function (row, data) {
                if (data.status == 2) {
                    row.className = 'info';
                }
                /// moved to "click .updateSetting in #settingTbl".
                // $('.updateSetting', row).off('click').on('click', function(){
                //     addFormData(data);
                // });
            }
        }); // EOF  $('#settingTbl').DataTable({...

        _this.settingTbl_DataTable.page.len(_this.defaultItemsPerPage).draw(); // for lengthChange

    } // EOF if( typeof( $( selector ).DataTable() ) === 'undefined' )
} // EOF level_upgrade.init_settingTbl_DataTable

// helper
level_upgrade.isJsonString = function (str) {
    if (str == '') return true;
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}


level_upgrade.getGameNameWithAjax = function (platformIdList, typeIdList, handleDoneCB) {
    var _this = this;
    var theUri = _this.baseUrl + 'api/getGameNameByPlatformTypeDescriptionId';
    var theSeparator = '____';
    var theData = {};
    theData.separator = theSeparator; // 4 under lines.s
    if (typeof (platformIdList) !== 'undefined') {
        // theData.PlatformIdList = JSON.stringify(platformIdList);
        theData.PlatformIdList = platformIdList;
    }
    if (typeof (typeIdList) !== 'undefined') {
        theData.TypeIdList = typeIdList;
    }
    var jqXHR = $.ajax({
        type: 'POST',
        url: theUri,
        data: theData,
        beforeSend: function () {
            // targetBtn$El.closest('[data-toggle="tooltip"]').tooltip('hide');
            // targetBtn$El.button('loading');
        },
        complete: function () {
            // targetBtn$El.button('reset');
        }
    });

    jqXHR.done(function (data, textStatus, xhr) {
        var results = {};

        // for gamePlatform
        if (typeof (data.gamePlatform) !== 'undefined') {
            results['gamePlatform'] = [];
            $.each(data.gamePlatform, function (indexNumber, currItem) {
                var _split = currItem.split(theSeparator);
                // var idStr = _split[_split.length - 1];
                var idStr = _split.pop(); // get and remove tail item, id.
                var nameStr = _split.join(theSeparator);
                results['gamePlatform'].push({
                    'id': idStr,
                    'name': nameStr,
                    'separator': theSeparator
                });
            });
        }
        if (typeof (data.gameType) !== 'undefined') {
            results['gameType'] = [];
            $.each(data.gameType, function (indexNumber, currItem) {
                var _split = currItem.split(theSeparator);
                // var idStr = _split[_split.length - 1];
                var idStr = _split.pop(); // get and remove tail item, id.
                var nameStr = _split.join(theSeparator);
                results['gameType'].push({
                    'id': idStr,
                    'name': nameStr,
                    'separator': theSeparator
                });
            });
        }
        if (typeof (handleDoneCB) !== 'undefined') {
            handleDoneCB(results);
        }
    });
    return jqXHR;
};


level_upgrade.applyBetAmountSettings = function (betAmountSettings, beforeCB, beforeApplyEachCB, applyEachGamePlatformCB, applyEachGameTypeCB, applyEachDoneCB) {
    var _this = this;

    var theBetAmountSettingsHtmlList = [];
    var theBetAmountSettingsHtml = '';

    var assorted = _this.assortBetAmountSettings(betAmountSettings);
    // get game platform list
    var gamePlatformSettingList = [];
    $.each(assorted[1], function (indexNumber, currItem) {
        gamePlatformSettingList.push(currItem.game_platform_id);
    });
    // get game type list
    var gameTypeSettingList = [];
    $.each(assorted[2], function (indexNumber, currItem) {
        gameTypeSettingList.push(currItem.game_type_id);
    });

    if (typeof (beforeCB) !== 'undefined') {
        beforeCB();
    }
    // ajax get game platform + type name
    var ajax = _this.getGameNameWithAjax(gamePlatformSettingList, gameTypeSettingList, function (results) { // handleDoneCB
        // console.log('getGameNameWithAjax.handleDoneCB:', results);
        // for sort by betAmountSettings.itemList

        if (typeof (beforeApplyEachCB) !== 'undefined') {
            beforeApplyEachCB();
        }

        $.each(betAmountSettings.itemList, function (indexNumber, currItem) {
            switch (currItem.type) {
                case 'game_platform':
                    var theGamePlatform = {};
                    var theGamePlatformList = results['gamePlatform'].filter(function (item, index, array) {
                        return currItem.game_platform_id == item.id;
                    });
                    if (typeof (theGamePlatformList[0]) !== 'undefined') {
                        theGamePlatform = theGamePlatformList[0];
                    }
                    applyEachGamePlatformCB(currItem, theGamePlatform);
                    break;
                case 'game_type':
                    var theGameType = {};
                    var theGameTypeList = results['gameType'].filter(function (item, index, array) {
                        return currItem.game_type_id == item.id;
                    });
                    if (typeof (theGameTypeList[0]) !== 'undefined') {
                        theGameType = theGameTypeList[0];
                    }

                    applyEachGameTypeCB(currItem, theGameType);
                    break;
                default:
                    break;
            }
        });

        if (typeof (applyEachDoneCB) !== 'undefined') {
            applyEachDoneCB();
        }
    }); // EOF _this.getGameNameWithAjax

    return ajax;
};


/**
 * 分類成 defaultItem 跟 gamePlatformSettingList gameTypeSettingList
 * 會破壞順序，故暫時不使用
 * @param {*} betAmountSettings
 */
level_upgrade.assortBetAmountSettings = function (betAmountSettings) {

    var defaultSetting = {};
    var gamePlatformSettingList = [];
    var gameTypeSettingList = [];
    if (!$.isEmptyObject(betAmountSettings)) {
        Object.keys(betAmountSettings).map(function (keyStr, indexNumber) {
            if (keyStr == 'defaultItem') {
                defaultSetting = betAmountSettings[keyStr];
            } else if (keyStr == 'itemList') {
                betAmountSettings[keyStr].map(function (item, currIndexNumber, itemList) {
                    if (item['type'] == 'game_platform') {
                        gamePlatformSettingList.push(item);
                    } else if (item['type'] == 'game_type') {
                        gameTypeSettingList.push(item);
                    }
                });
            }
        });
    }

    return [defaultSetting, gamePlatformSettingList, gameTypeSettingList];
};

/**
 * Get formula Input Value of .formula-container
 * @return {string} The json string for formula in the ".formula-container" div.
 */
level_upgrade.getFormulaInputValWithDefault = function () {
    var _this = this;
    var _formulaStr = _this.formulaInput$El.val();
    if ($.isEmptyObject(_formulaStr)) {
        _formulaStr = JSON.stringify({});
    }
    return _formulaStr;
};

/**
 * Get bet_settings Input Value of .formula-container
 * @return {null|string} The json string for bet_settings in the ".formula-container" div.
 */
level_upgrade.getBetSettingsInputValWithDefault = function () {
    var _this = this;
    var _betSettingsStr = _this.betSettingsInput$El.val();
    if ($.isEmptyObject(_betSettingsStr)) {
        _betSettingsStr = JSON.stringify(null);
    }
    return _betSettingsStr;
};

level_upgrade.setFormulaInputValWithDefault = function (theFormulaStr) {
    var _this = this;
    _this.formulaInput$El.val(theFormulaStr);
}
/**
 * Set bet_settings Input Value of .formula-container
 * @param {string} theBetSettingsStr The bet_settings
 */
level_upgrade.setBetSettingsInputValWithDefault = function (theBetSettingsStr) {
    var _this = this;
    _this.betSettingsInput$El.val(theBetSettingsStr);
}

/**
 * Set Accumulation Input Value of .formula-container
 * @param {string} theAccumulationSettingsStr The Accumulation
 */
level_upgrade.setAccumulationSettingsInputValWithDefault = function (theAccumulationSettingsStr) {
    var _this = this;
    _this.accumulationSettingsInput$El.val(theAccumulationSettingsStr);
}

/**
 * Get accumulation_settings Input Value of .formula-container
 * @return {number|string} The json string for accumulation_settings in the ".formula-container" div.
 */
level_upgrade.getAccumulationSettingsInputValWithDefault = function () {
    var _this = this;
    var _accumulationSettingsStr = _this.accumulationSettingsInput$El.val();
    if ($.isEmptyObject(_accumulationSettingsStr)) {
        _accumulationSettingsStr = JSON.stringify(0);
    }
    return _accumulationSettingsStr;
}

/**
 *
 * @param {json} theFormulaStr
 * ex: {"bet_amount":[">=","3333"],"operator_2":"and","deposit_amount":["<=","4444"],"operator_3":"and","loss_amount":[">","5555"],"operator_4":"and","win_amount":["<","6666"]}
 * @param {integer|json} accumulation_or_separate_accumulation_settings
 * @param {string} betAmountSettingsStr
 */
level_upgrade.redrawFormula = function (theFormulaStr, accumulation_or_separate_accumulation_settings, betAmountSettingsStr) {
    var _this = this;
    // console.log('redrawFormula.arguments:', arguments);
    if ($.isEmptyObject(theFormulaStr)) {
        theFormulaStr = JSON.stringify({});
    }

    if ($.isEmptyObject(accumulation_or_separate_accumulation_settings)) {
        accumulation_or_separate_accumulation_settings = 0;
    }

    if ($.isEmptyObject(betAmountSettingsStr)) {
        betAmountSettingsStr = JSON.stringify(null);
    }

    var $deferred = $.Deferred();

    if ($.isEmptyObject(betAmountSettingsStr)) {
        betAmountSettingsStr = '{}';
    }
    var bet_amount_settings = JSON.parse(betAmountSettingsStr);

    var accumulation = null;
    // if (typeof (accumulation_or_separate_accumulation_settings) === 'number') {
    if (!isNaN(accumulation_or_separate_accumulation_settings)) {
        // should be common accumulation
        accumulation = accumulation_or_separate_accumulation_settings;
    } else if (_this.isJsonString(accumulation_or_separate_accumulation_settings)) {
        // should be separate accumulation
        accumulation = JSON.parse(accumulation_or_separate_accumulation_settings);// sas = separate_accumulation_settings
    } else {
        // accumulation = _this.init_accumulation_settingsFromComm()
    }

    var formulaElList = [];

    var formula = $.parseJSON(theFormulaStr);

    var doAppendAccumulationSettings = {};
    Object.keys(formula).map(function (keyStr, indexNumber) {
        if (_this.optionKey.indexOf(keyStr) > -1) {
            var doAppendAccumulation = false;
            var currAccumulation = null;
            // if (typeof (accumulation) === 'number') { // common accumulation
            if (!isNaN(accumulation)) { // common accumulation
                currAccumulation = accumulation;
            } else if (typeof (accumulation) === 'object') { // separate accumulation
                if (keyStr in accumulation) {
                    currAccumulation = accumulation[keyStr]['accumulation'];
                } else {
                    currAccumulation = _this.ACCUMULATION_MODE_DISABLE
                }
            }
            if (currAccumulation != _this.ACCUMULATION_MODE_DISABLE) { //
                doAppendAccumulation = true;
            }

        }
        doAppendAccumulationSettings[keyStr] = doAppendAccumulation;
    });
    Object.keys(formula).map(function (keyStr, indexNumber) {

        // for detect operator_XXX
        var re = /operator_(\d+)/i;
        var found = keyStr.match(re);

        if (_this.optionKey.indexOf(keyStr) > -1) {
            // bet_amount, deposit, win and loss amount
            var option = _this.jsonValByKey(keyStr); // bet convert to 1
            var name = _this.optionName(option);
            var amount = formula[keyStr][1];

            var operator = formula[keyStr][0];
            var selectOption = _this.operatorValByKey(operator);

            var doAppendAccumulation = false;
            var currAccumulation = null;

            // if (typeof (accumulation) === 'number') { // common accumulation
            if (!isNaN(accumulation)) { // common accumulation
                currAccumulation = accumulation;
            } else if (typeof (accumulation) === 'object') { // separate accumulation
                if (keyStr in accumulation) {
                    currAccumulation = accumulation[keyStr]['accumulation'];
                } else {
                    currAccumulation = _this.ACCUMULATION_MODE_DISABLE
                }
            }
            if (currAccumulation != _this.ACCUMULATION_MODE_DISABLE) { //
                doAppendAccumulation = true;
            }
            var _html = _this.applyInlineOptionTplHtml(option, name, selectOption, amount, doAppendAccumulation);
            formulaElList.push(_html);

        } else if (found !== null) {

            // formulaElList.push('<div class="row option-group-wrapper">');
            // operator_XXX
            // console.log('found:', found); // ["operator_2", "2", index: 0, input: "operator_2", groups: undefined]
            var option = found[1];
            var andor = formula[found[0]];
            var checked = '';
            if (andor == 'and') {
                checked = 'checked';
            }

            var _html = _this.applyInlinePreConjunctionHtml(option, checked);
            formulaElList.push(_html);
        } else {
            // other case
            var _html = JSON.stringify(formula[keyStr]);
            formulaElList.push(_html);
        }

    }); // EOF Object.keys(formula).map(function (keyStr, indexNumber)

    var theHtml = formulaElList.join(' '); // &nbsp;
    // console.log('redrawFormula.theHtml', theHtml);
    $('.formula-container .help-block.notes').html(theHtml);

    /// wrap into option-group
    // wrap .check-toggle and .inline-option-wrapper into option-group
    $('.check-toggle').each(function (index, currEl) { // ref. to https://gist.github.com/BigglesZX/747490
        var curr$El = $(currEl);
        curr$El.next('.inline-option-wrapper').addBack().wrapAll("<div class='row option-group-wrapper' />")
    });
    /// wrap first .inline-option-wrapper into option-group
    $('.notes>.inline-option-wrapper:first').wrapAll("<div class='row option-group-wrapper' />");


    if ($.isEmptyObject(bet_amount_settings)) {
        $deferred.resolve(); // trigger next step
    } else {
        /// extend BetAmountSettings
        // var assorted = _this.assortBetAmountSettings(bet_amount_settings);
        // console.error('assorted:', assorted, bet_amount_settings);
        var optionBetSetting$El = $('.option-group-wrapper:has(".inline-option-wrapper.1.inline")');
        // console.log('redrawFormula.optionBetSetting$El:', optionBetSetting$El);
        _this.applyBetAmountSettings(bet_amount_settings, function () { // beforeCB

            optionBetSetting$El.html('Loading Data...');
        }, function () { // beforeApplyEachCB
            //  hide loading
            optionBetSetting$El.html('');// clear BetAmountSettings
        }, function (currItem, theGamePlatform) { // applyEachGamePlatformCB
            // console.log('applyBetAmountSettings.applyEachGamePlatformCB:', currItem, theGamePlatform)
            // apply tpl into
            var _html = '';
            var theType = currItem.type;
            var theId = currItem.game_platform_id;
            if (typeof (currItem.precon_logic_flag) !== 'undefined') {
                var andor = currItem.precon_logic_flag;
                var checked = '';
                if (andor == 'and') {
                    checked = 'checked';
                }
                _html = _html + _this.applyInlinePreConjunctionTypeHtml(theType, theId, checked);
            }

            var amount = currItem.value;
            var name = theGamePlatform.name;
            // var doAppendAccumulation had assign
            var doAppendAccumulation = doAppendAccumulationSettings['bet_amount'];// @todo
            var selectOption = _this.operatorValByKey(currItem.math_sign);

            _html = _html + _this.applyInlineOptionTypeTplHtml(theType, theId, name, selectOption, amount, doAppendAccumulation);

            optionBetSetting$El.append(_html);

        }, function (currItem, theGameType) { // applyEachGameTypeCB
            // console.log('applyBetAmountSettings.applyEachGameTypeCB:', currItem, theGameType)

            // apply tpl into
            var _html = '';
            var theType = currItem.type;
            var theId = currItem.game_type_id;
            if (typeof (currItem.precon_logic_flag) !== 'undefined') {
                var andor = currItem.precon_logic_flag;
                var checked = '';
                if (andor == 'and') {
                    checked = 'checked';
                }
                _html = _html + _this.applyInlinePreConjunctionTypeHtml(theType, theId, checked);
            }

            var amount = currItem.value;
            var _gameTypeName = theGameType.name;
            var _split = _gameTypeName.split(theGameType.separator);
            // _gameTypeName = _split.pop();
            _gameTypeName = _split.join('=&gt;'); // =>
            var name = _gameTypeName; // theGameType.name;//.split(theGameType.separator).pop();
            // var doAppendAccumulation had assign
            var doAppendAccumulation = doAppendAccumulationSettings['bet_amount'];// @todo
            var selectOption = _this.operatorValByKey(currItem.math_sign);

            _html = _html + _this.applyInlineOptionTypeTplHtml(theType, theId, name, selectOption, amount, doAppendAccumulation);

            optionBetSetting$El.append(_html);

        }, function () { // applyEachDoneCB

            var _optionGroupBorderBetAmount = $('.notes>.option-group-wrapper:has(".inline.1")').find('.option-group-border.bet_amount');
            var isExists = _optionGroupBorderBetAmount.length > 0;
            if (!isExists) {
                /// add the border for bet_amount-game settings
                $('.notes>.option-group-wrapper:has(".inline.1")').wrapInner("<fieldset class='option-group-border bet_amount text-left' ></fieldset>");
                $('.notes .option-group-border.bet_amount').append('<legend><div class="h5">' + _this.theLANG.BetAmount + '</div></legend>');
            }

            // console.log('applyBetAmountSettings.applyEachDoneCB:')
            $deferred.resolve(); // trigger next step
        }); // EOF _this.applyBetAmountSettings(bet_amount_settings, function () {...
    } // EOF if ($.isEmptyObject(bet_amount_settings))

    // console.log('redrawFormula.theFormulaStr', theFormulaStr);
    _this.setFormulaInputValWithDefault(theFormulaStr);
    // _this.formulaInput$El.val(theFormulaStr);
    _this.setBetSettingsInputValWithDefault(betAmountSettingsStr);
    // _this.betSettingsInput$El.val(betAmountSettingsStr);
    // console.error('redrawFormula.accumulation_or_separate_accumulation_settings:', accumulation_or_separate_accumulation_settings);
    // _this.accumulationSettingsInput$El.val(accumulation_or_separate_accumulation_settings);
    _this.setAccumulationSettingsInputValWithDefault(accumulation_or_separate_accumulation_settings);

    $deferred.always(function () { // execute next step
        _this.activateToolTip();
        _this.loadBootstrapToggle();
    });

    return $deferred.promise();
} // EOF redrawFormula



level_upgrade.applyInlinePreConjunctionHtml = function (option, checked) {
    var _this = this;

    var tplHtml = _this.getTemplateHtml('.inline-pre-conjunction');
    // assign the params into the tpl
    var langAndOr = _this.theLANG.AND_OR;
    tplHtml = tplHtml.replace(/\$\{option\}/g, option);
    tplHtml = tplHtml.replace(/\$\{checked\}/g, checked);
    tplHtml = tplHtml.replace(/\$\{langAndOr\}/g, langAndOr);
    return tplHtml;
}// EOF applyInlinePreConjunctionHtml

level_upgrade.getAccumulationToInlineOptionFromTpl = function (name) {
    var _this = this;

    // get the tpl
    var tplHtml = _this.getTemplateHtml('.inline-accumulation-name');
    // assign the params into the tpl
    tplHtml = tplHtml.replace(/\$\{name\}/g, name);
    tplHtml = tplHtml.replace(/\$\{lang_accumulation\}/g, _this.theLANG.Accumulation);
    // name = tplHtml;
    // _this.outerHtml()
    // console.log('applyInlineOptionTplHtml.name:', name);
    // inline-option-wrapper
    return tplHtml;
}

level_upgrade.applyInlinePreConjunctionTypeHtml = function (theType, theId, checked) {
    var _this = this;

    var tplHtml = _this.getTemplateHtml('.inline-pre-conjunction-type');
    // assign the params into the tpl
    var langAndOr = _this.theLANG.AND_OR;
    tplHtml = tplHtml.replace(/\$\{type\}/g, theType);
    tplHtml = tplHtml.replace(/\$\{id\}/g, theId);
    tplHtml = tplHtml.replace(/\$\{checked\}/g, checked);
    tplHtml = tplHtml.replace(/\$\{langAndOr\}/g, langAndOr);
    return tplHtml;
}// EOF applyInlinePreConjunctionTypeHtml

level_upgrade.applyInlineOptionTypeTplHtml = function (theType, theId, name, selectedMathSymbol, amount, doAppendAccumulation) {
    var _this = this;
    // get the tpl
    var tplHtml = _this.getTemplateHtml('.inline-option-type');
    var optionId = 1;
    if (doAppendAccumulation) {
        name = _this.getAccumulationToInlineOptionFromTpl(name);
    }

    var option_1 = '';
    var option_2 = '';
    var option_3 = '';
    var option_4 = '';
    switch (selectedMathSymbol) { // selectOption
        case 1: option_1 = 'selected="selected"'; break;
        case 2: option_2 = 'selected="selected"'; break;
        case 3: option_3 = 'selected="selected"'; break;
        case 4: option_4 = 'selected="selected"'; break;
    }

    // assign the params into the tpl
    tplHtml = tplHtml.replace(/\$\{type\}/g, theType);
    tplHtml = tplHtml.replace(/\$\{optionId\}/g, optionId); // return of jsonValByKey().
    tplHtml = tplHtml.replace(/\$\{id\}/g, theId);
    tplHtml = tplHtml.replace(/\$\{name\}/g, name);
    tplHtml = tplHtml.replace(/\$\{option_1\}/g, option_1);
    tplHtml = tplHtml.replace(/\$\{option_2\}/g, option_2);
    tplHtml = tplHtml.replace(/\$\{option_3\}/g, option_3);
    tplHtml = tplHtml.replace(/\$\{option_4\}/g, option_4);
    tplHtml = tplHtml.replace(/\$\{amount\}/g, amount);

    return tplHtml;
}; // EOF applyInlineOptionTypeTplHtml

/**
 * Get the HTML after applied params.
 * @param {string} option The id that's means name.
 * @param {string} name The amount name, usually be bet, deposit, loss and win amount.
 * @param {integer} selectedMathSymbol Apply the selected into the Math Symbol of the option.
 * @param {integer|float} amount The number in the input.
 * @param {boolean} doAppendAccumulation If true than display the Accumulation word.
 */
level_upgrade.applyInlineOptionTplHtml = function (option, name, selectedMathSymbol, amount, doAppendAccumulation) {
    var _this = this;

    // get the tpl
    var tplHtml = _this.getTemplateHtml('.inline-option');

    if (doAppendAccumulation) {
        name = _this.getAccumulationToInlineOptionFromTpl(name);
        // // get the tpl
        // var tplInlineAccumulationNameHtml = _this.getTemplateHtml('.inline-accumulation-name');
        // // assign the params into the tpl
        // tplInlineAccumulationNameHtml = tplInlineAccumulationNameHtml.replace(/\$\{name\}/g, name);
        // tplInlineAccumulationNameHtml = tplInlineAccumulationNameHtml.replace(/\$\{lang_accumulation\}/g, _this.theLANG.Accumulation);
        // name = tplInlineAccumulationNameHtml;
        // // _this.outerHtml()
        // // console.log('applyInlineOptionTplHtml.name:', name);
    }

    var option_1 = '';
    var option_2 = '';
    var option_3 = '';
    var option_4 = '';
    switch (selectedMathSymbol) { // selectOption
        case 1: option_1 = 'selected="selected"'; break;
        case 2: option_2 = 'selected="selected"'; break;
        case 3: option_3 = 'selected="selected"'; break;
        case 4: option_4 = 'selected="selected"'; break;
    }


    // assign the params into the tpl
    tplHtml = tplHtml.replace(/\$\{option\}/g, option);
    tplHtml = tplHtml.replace(/\$\{name\}/g, name);
    tplHtml = tplHtml.replace(/\$\{option_1\}/g, option_1);
    tplHtml = tplHtml.replace(/\$\{option_2\}/g, option_2);
    tplHtml = tplHtml.replace(/\$\{option_3\}/g, option_3);
    tplHtml = tplHtml.replace(/\$\{option_4\}/g, option_4);
    tplHtml = tplHtml.replace(/\$\{amount\}/g, amount);
    return tplHtml;
} // EOF level_upgrade.applyInlineOptionTplHtml

level_upgrade.addFormData = function (data) {
    var _this = this;
    // console.log('addFormData.data:', data);
    // @todo data.bet_amount_settings

    _this.theLevelUpModal$El.find('#upgradeId').val(data.upgrade_id);
    _this.theLevelUpModal$El.find('#settingName').val(data.setting_name);
    _this.theLevelUpModal$El.find('#description').val(data.description);
    _this.theLevelUpModal$El.find('#levelUpgrade').val(data.level_upgrade);
    _this.theLevelUpModal$El.find('.help-block').empty();
    _this.theLevelUpModal$El.find('.option[type="checkbox"]').prop('checked', false);
    checkedRows = [];
    var formula = $.parseJSON(data.formula);
    var has_SAS = false; // SAS = separate_accumulation_settings
    var separate_accumulation_settings = {};
    if (data.separate_accumulation_settings !== null) {
        if (data.separate_accumulation_settings.length > 0) {
            separate_accumulation_settings = $.parseJSON(data.separate_accumulation_settings);
        }
    }
    if (!$.isEmptyObject(separate_accumulation_settings)) {
        has_SAS = true; // SAS = separate_accumulation_settings
    }

    if (!has_SAS) { // SAS = separate_accumulation_settings
        var accumulation = parseInt(data.accumulation);
        var _accumulation = accumulation;
    } // EOF if( ! has_SAS ){...

    // var formulaKey = Object.keys(formula);
    // for (var i in formulaKey) {
    //     i = parseInt(i);
    //     if (_this.optionKey.indexOf(formulaKey[i]) >= 0) {
    //         operator = formula[formulaKey[i]][0];
    //         amount = formula[formulaKey[i]][1];
    //         var value = _this.jsonValByKey(formulaKey[i]);
    //         _this.theLevelUpModal$El.find('.option[type="checkbox"][value="' + value + '"]').prop('checked', true);
    //         checkedRows.push(value);
    //         var andor = '';
    //         if (typeof (formulaKey[i - 1]) !== 'undefined') {
    //             andor = formula[formulaKey[i - 1]];
    //         }
    //
    //         _this.loadFormula(value, checkedRows.length, true, _this.operatorValByKey(operator), amount, andor, accumulation);
    //
    //     } // EOF if(_this.optionKey.indexOf(formulaKey[i]) >= 0) {
    // }// EOF for (var i in formulaKey) {...


    Object.keys(formula).map(function (keyStr, indexNumber) {
        if (_this.optionKey.indexOf(keyStr) > -1) {
            var value = _this.jsonValByKey(keyStr);
            _this.theLevelUpModal$El.find('.option[type="checkbox"][value="' + value + '"]').prop('checked', true);
            _this.theLevelUpModal$El.find('.option[type="checkbox"][value="' + value + '"]').trigger('change'); // will trigger the ".option" element change event
        }
    });

    var theFormulaStr = data.formula;
    var accumulation_or_separate_accumulation_settings = data.accumulation;
    if (data.separate_accumulation_settings !== null) {
        accumulation_or_separate_accumulation_settings = data.separate_accumulation_settings;
    }
    var betAmountSettingsStr = data.bet_amount_settings;
    var $deferred = _this.redrawFormula(theFormulaStr, accumulation_or_separate_accumulation_settings, betAmountSettingsStr);

    /// Patch the issue,
    // To sync the UI for the case with Accumulation and "Last Change Period".
    if (!has_SAS) { // SAS = separate_accumulation_settings
        if (accumulation >= ACCUMULATION_MODE_FROM_REGISTRATION) {  // will assign Accumulation and "Accumulation Computation"
            var _accumulation = ACCUMULATION_MODE_FROM_REGISTRATION;
            var accumulationFrom = accumulation;
            $('input:radio[name="accumulationFrom"][value="' + accumulationFrom + '"]').prop('checked', true);
        }
        var doTriggerChange = true;
        _this.accumulationSetChecked(_accumulation, doTriggerChange);
    }


    if (!$.isEmptyObject(_this.enable_separate_accumulation_in_setting)) {
        for (var i in _this.optionKey) { // reset Accumulation UI
            var optionName = _this.optionKey[i];
            var optionVal = false;
            var accumulationVal = 0;
            _this.toggleAndSelectAccumulationByOption(optionName, optionVal, accumulationVal); // reset
        }

        if (has_SAS) { // SAS = separate_accumulation_settings
            var formulaKey = Object.keys(formula);
            // to execute toggleAndSelectAccumulationByOption() in data.separate_accumulation_settings for each optionKey
            for (var i in _this.optionKey) {
                if (_this.optionKey[i] in separate_accumulation_settings) {
                    var optionName = _this.optionKey[i];
                    if( typeof(separate_accumulation_settings[optionName]) !== 'undefined'
                        && 'accumulation' in separate_accumulation_settings[optionName]
                    ){
                        var isCheckedCurrOption = formulaKey.indexOf(optionName) > -1;
                        var accumulationVal = separate_accumulation_settings[optionName]['accumulation'];
                        _this.toggleAndSelectAccumulationByOption(optionName, isCheckedCurrOption, accumulationVal);
                    }
                }
            }// EOF for (var i in _this.optionKey) {...
        } else {

            var accumulationVal = data.accumulation;
            var optionVal = true;
            var formulaKey = Object.keys(formula);
            for (var i in formulaKey) {
                i = parseInt(i);
                if (_this.optionKey.indexOf(formulaKey[i]) >= 0) {
                    var optionName = formulaKey[i];
                    _this.toggleAndSelectAccumulationByOption(optionName, optionVal, accumulationVal);
                }
            }

        }// EOF if(has_SAS){...

        if (!has_SAS) { // SAS = separate_accumulation_settings
            $('.col-separate_options .text-danger').removeClass('hide');
        } else {
            $('.col-separate_options .text-danger').addClass('hide');
        }

    } // EOF if(_this.enable_separate_accumulation_in_setting){

    // $deferred.always(function () { // execute next step
    //     _this.activateToolTip();
    //     _this.loadBootstrapToggle();
    // });

}; // EOF addFormData

level_upgrade.activateToolTip = function () {
    $('[data-toggle="tooltip"]').tooltip();
}; // EOF activateToolTip

level_upgrade.loadBootstrapToggle = function () {
    var _this = this;

    var selectorStrList = [];
    selectorStrList.push('#toggle-1');
    selectorStrList.push('#toggle-2');
    selectorStrList.push('#toggle-3');
    selectorStrList.push('#toggle-4');
    selectorStrList.push('[id*="toggle-"]'); // for ver.2
    _this.theLevelUpModal$El.find(selectorStrList.join(',')).bootstrapToggle({
        on: 'And', off: 'Or', size: "mini"
    });
}

level_upgrade.toggleAndSelectAccumulationByOption = function (optionName, optionChecked, accumulationVal) {
    var _this = this;

    var jsonVal = _this.jsonValByKey(optionName);
    if (typeof (optionChecked) === 'undefined') {
        optionChecked = false;
    }
    if (typeof (accumulationVal) === 'undefined') {
        accumulationVal = 0; // No accumulation
    }

    $('.option[value="' + jsonVal + '"]').prop('checked', optionChecked);
    _this.toggleAccumulationNoption(optionName);

    $('input[name="accumulation_' + optionName + '"][value="' + accumulationVal + '"]').prop('checked', true);
    _this.applyAccumulationInFormula(optionName);
} // EOF toggleAndSelectAccumulationByOption


/**
 * To dis/enable The Accumulation N options,(0,1,4) By the Option Name
 * @param string optionName The Option Name contains, "bet_amount", "disposit_amount", "win_amount" and "loss_amount".
 * @param string forceStatus Ex: enable, disable.
 */
level_upgrade.toggleAccumulationNoption = function (optionName, forceStatus) {
    var _this = this;

    var isToEnable = null;
    switch (forceStatus) {
        case 'enable':
            isToEnable = true;
            break;

        case 'disable':
            isToEnable = false;
            break;

        default:
            var jsonVal = _this.jsonValByKey(optionName);
            var theChecked$El = $('input.option[value="' + jsonVal + '"]:checked');

            if (theChecked$El.length > 0) { // checked then enable
                isToEnable = true;
            } else { // non-checked should be disabled.
                isToEnable = false;
            }
            break;
    }

    if (isToEnable) {
        _this.enableAccumulationNoption(optionName);
    } else {
        _this.disableAccumulationNoption(optionName);
    }
} // EOF toggleAccumulationNoption()

/**
 * Cenvert option Id to Key
 * @param {integer} x The option Id, 1~4
 * @return {string} key, ex: bet_amount, deposit_amount, loss_amount and win_amount.
 */
level_upgrade.jsonKey = function (x) {
    var key = 0;
    if (x == 1) {
        key = 'bet_amount';
    } else if (x == 2) {
        key = 'deposit_amount';
    } else if (x == 3) {
        key = 'loss_amount';
    } else if (x == 4) {
        key = 'win_amount';
    }
    return key;
}

/**
 * Get option Val form Key
 * ex: bet_amount convert to  1
 * @param {string} key ex: bet_amount, deposit_amount, loss_amount and win_amount.
 * @return {integer}
 */
level_upgrade.jsonValByKey = function (key) {
    var val = 0;
    switch (key) {
        case 'bet_amount': val = 1; break;
        case 'deposit_amount': val = 2; break;
        case 'loss_amount': val = 3; break;
        case 'win_amount': val = 4; break;
    }
    return val;
} // EOF jsonValByKey

level_upgrade.optionNameByKey = function (key) {
    var _this = this;
    var optionName = '';
    if (key == 'bet_amount') {
        optionName = _this.theLANG.BetAmount; // AMOUNT_MSG.BET;
    } else if (key == 'deposit_amount') {
        optionName = _this.theLANG.DepositAmount; // AMOUNT_MSG.DEPOSIT;
    } else if (key == 'loss_amount') {
        optionName = _this.theLANG.LossAmount; // AMOUNT_MSG.LOSS;
    } else if (key == 'win_amount') {
        optionName = _this.theLANG.WinAmount; // AMOUNT_MSG.WIN;
    }
    return optionName;
} // EOF optionNameByKey

level_upgrade.operatorKeyByVal = function (val) {
    var key = null;
    switch (val) {
        case '1': key = '>='; break;
        case '2': key = '<='; break;
        case '3': key = '>'; break;
        case '4': key = '<'; break;
    }
    return key;
}

level_upgrade.operatorValByKey = function (key) {
    // function operatorValByKey(key) {
    var val = 0;
    switch (key) {
        case '>=': val = 1; break;
        case '<=': val = 2; break;
        case '>': val = 3; break;
        case '<': val = 4; break;
    }
    return val;
} // EOF operatorValByKey

level_upgrade.resetFormModal = function () {
    var _this = this;
    var selectorStrList = [];
    selectorStrList.push('#levelUpModal input[type="hidden"][id="upgradeId"]');
    selectorStrList.push('#levelUpModal_v2 input[type="hidden"][id="upgradeId"]');
    $(selectorStrList.join(',')).val('');

    var doTriggerChange = false;
    _this.accumulationSetChecked('0', doTriggerChange);

    var computation$El = $('.row-computation');
    computation$El.removeClass('fadeOut fadeIn animated').addClass('hide');

    var selectorStrList = [];
    selectorStrList.push('#levelUpModal .help-block');
    selectorStrList.push('#levelUpModal_v2 .help-block');
    $(selectorStrList.join(',')).html('');
    checkedRows = [];

    var theFormulaStr = '{}';
    var betAmountSettingsStr = '{}';
    var theAccumulationStr = '0';
    _this.setFormulaInputValWithDefault(theFormulaStr);
    _this.setBetSettingsInputValWithDefault(betAmountSettingsStr);
    _this.setAccumulationSettingsInputValWithDefault(theAccumulationStr);

    _this.theLevelUpModal$El.find('form').trigger('reset'); // will trigger  $(document).on('reset', '#levelUpModal form', function(e){...

} // EOF level_upgrade.resetFormModal


/**
     * Set a Radio input o f accumulation to checked.
     *
     * @param {string} setValue2Checked depend on radio inputs. i.e. "1" or "0", default Zero.
     * @param {boolean} doTriggerChange if true will trigger change event.
     */
level_upgrade.accumulationSetChecked = function (setValue2Checked, doTriggerChange) {
    var _this = this;
    var _accumulation = null;
    if (typeof (setValue2Checked) === 'undefined') {
        _accumulation = 0;
    } else {
        _accumulation = setValue2Checked;
    }
    if (typeof (doTriggerChange) === 'undefined') {
        doTriggerChange = false;
    } else {
        doTriggerChange = !!doTriggerChange;
    }

    var accumulation$El = _this.theLevelUpModal$El.find('input[type="radio"][name="accumulation"][value="' + _accumulation + '"]');
    accumulation$El.prop("checked", true);
    // console.log('accumulationSetChecked.accumulation$El', accumulation$El);
    if (doTriggerChange) {
        accumulation$El.trigger('change'); // will trigger
    }

} // EOF accumulationSetChecked
level_upgrade.optionName = function (optionVal) {
    var _this = this;
    var optionName = '';
    if (optionVal == 1) {
        optionName = _this.theLANG.BetAmount; // AMOUNT_MSG.BET;
    } else if (optionVal == 2) {
        optionName = _this.theLANG.DepositAmount; // AMOUNT_MSG.DEPOSIT;
    } else if (optionVal == 3) {
        optionName = _this.theLANG.LossAmount; // AMOUNT_MSG.LOSS;
    } else if (optionVal == 4) {
        optionName = _this.theLANG.WinAmount; // AMOUNT_MSG.WIN;
    }
    return optionName;
}


level_upgrade.getTemplateHtml = function (selectorStr) {
    var html = $("template" + selectorStr).html();
    return html;
}

level_upgrade.loadFormulaWithTpl = function (option, optionLength, isCheck, selectOption, value, andor, selectAccumulation) {
    var _this = this;
    _this.safelog('1836.loadFormulaWithTpl.arguments:', arguments);
    var $option = $('.' + option);
    var formulaHtml = '';
    var createAndOr = '';
    var formula = '';

    var name = _this.optionName(option);

    // var option_1 = '';
    // var option_2 = '';
    // var option_3 = '';
    // var option_4 = '';
    // switch (selectOption) {
    //     case 1: option_1 = 'selected="selected"'; break;
    //     case 2: option_2 = 'selected="selected"'; break;
    //     case 3: option_3 = 'selected="selected"'; break;
    //     case 4: option_4 = 'selected="selected"'; break;
    // }

    var amount = 0;
    if (typeof (value) !== 'undefined') {
        amount = value;
    }

    var doAppendAccumulation = false;
    if (selectAccumulation !== undefined) {
        if (selectAccumulation == 1) {
            // // get the tpl
            // var tplInlineAccumulationNameHtml = _this.getTemplateHtml('.inline-accumulation-name');
            // // assign the params into the tpl
            // tplInlineAccumulationNameHtml = tplInlineAccumulationNameHtml.replace(/\$\{name\}/g, name);
            // tplInlineAccumulationNameHtml = tplInlineAccumulationNameHtml.replace(/\$\{lang_accumulation\}/g, _this.theLANG.Accumulation);
            // name = tplInlineAccumulationNameHtml;
            doAppendAccumulation = true;
        }
    }

    // // get the tpl
    // var tplInlineOptionHtml = _this.getTemplateHtml('.inline-option');
    // // assign the params into the tpl
    // tplInlineOptionHtml = tplInlineOptionHtml.replace(/\$\{option\}/g, option);
    // tplInlineOptionHtml = tplInlineOptionHtml.replace(/\$\{name\}/g, name);
    // tplInlineOptionHtml = tplInlineOptionHtml.replace(/\$\{option_1\}/g, option_1);
    // tplInlineOptionHtml = tplInlineOptionHtml.replace(/\$\{option_2\}/g, option_2);
    // tplInlineOptionHtml = tplInlineOptionHtml.replace(/\$\{option_3\}/g, option_3);
    // tplInlineOptionHtml = tplInlineOptionHtml.replace(/\$\{option_4\}/g, option_4);
    // tplInlineOptionHtml = tplInlineOptionHtml.replace(/\$\{amount\}/g, amount);
    // formulaHtml = tplInlineOptionHtml;
    // formulaHtml = _this.applyInlineOptionTplHtml(option, name, option_1, option_2, option_3, option_4, amount, doAppendAccumulation);
    formulaHtml = _this.applyInlineOptionTplHtml(option, name, selectOption, amount, doAppendAccumulation);


    // createAndOr += '<div class="inline check-toggle">';
    var checked = '';
    if (andor == 'and') {
        checked = 'checked';
    }



    // get the tpl
    // var tplInlinePreConjunctionHtml = _this.getTemplateHtml('.inline-pre-conjunction');
    // // assign the params into the tpl
    // var langAndOr = LANG.AND_OR;
    // tplInlinePreConjunctionHtml = tplInlinePreConjunctionHtml.replace(/\$\{option\}/g, option);
    // tplInlinePreConjunctionHtml = tplInlinePreConjunctionHtml.replace(/\$\{checked\}/g, checked);
    // tplInlinePreConjunctionHtml = tplInlinePreConjunctionHtml.replace(/\$\{langAndOr\}/g, langAndOr);
    // createAndOr = tplInlinePreConjunctionHtml;
    createAndOr = _this.applyInlinePreConjunctionHtml(option, checked);

    if (isCheck) {
        if (optionLength == 1) {
            formula = formulaHtml;
        } else if (optionLength >= 2) {
            formula = createAndOr + formulaHtml;
        }
    } else {
        if ($option.next('.check-toggle').length) {
            $option.next('.check-toggle').remove();
        } else {
            $option.prev('.check-toggle').remove();
        }
        $option.remove();
    }

    var selectorStrList = [];
    selectorStrList.push('#levelUpModal .help-block');
    selectorStrList.push('#levelUpModal_v2 .help-block');
    $(selectorStrList.join(',')).append(formula);
}// EOF level_upgrade.loadFormulaWithTpl

level_upgrade.loadFormula = function (option, optionLength, isCheck, selectOption, value, andor, selectAccumulation) {
    var _this = this;

    return _this.loadFormulaWithTpl(option, optionLength, isCheck, selectOption, value, andor, selectAccumulation);

    var $option = $('.' + option);
    var formulaHtml = '';
    var createAndOr = '';
    var formula = '';

    var name = _this.optionName(option);

    if (selectAccumulation !== undefined) {
        if (selectAccumulation == 1) {
            name = '<span class="accumulation">' + _this.theLANG.Accumulation + '</span>' + name;
        }
    }
    formulaHtml += '<div class="inline ' + option + '">';
    formulaHtml += '  <label style="font-weight: bold;">' + name + '</label>';
    formulaHtml += '  <select class="condition" id="operator-' + option + '" data-toggle="tooltip" data-placement="top" >';
    var option_1 = '';
    var option_2 = '';
    var option_3 = '';
    var option_4 = '';
    switch (selectOption) {
        case 1: option_1 = 'selected="selected"'; break;
        case 2: option_2 = 'selected="selected"'; break;
        case 3: option_3 = 'selected="selected"'; break;
        case 4: option_4 = 'selected="selected"'; break;
    }
    formulaHtml += '    <option value="1" ' + option_1 + '> >= </option>';
    formulaHtml += '    <option value="2" ' + option_2 + '> <= </option>';
    formulaHtml += '    <option value="3" ' + option_3 + '> > </option>';
    formulaHtml += '    <option value="4" ' + option_4 + '> < </option>';
    formulaHtml += '  </select>';
    var amount = 0;
    if (typeof (value) !== 'undefined') {
        amount = value;
    }
    formulaHtml += '  <input type="text" class="custom-input" id="amount-' + option + '" data-toggle="tooltip" data-placement="top"  value="' + amount + '">';
    formulaHtml += '</div>';

    createAndOr += '<div class="inline check-toggle">';
    var checked = '';
    if (andor == 'and') {
        checked = 'checked';
    }
    createAndOr += '    <input id="toggle-' + option + '" class="conjunction" ' + checked + ' type="checkbox" data-onstyle="success" data-offstyle="info" data-toggle="tooltip" data-placement="top" title="' + LANG.AND_OR + '">';
    createAndOr += '</div>';

    if (isCheck) {
        if (optionLength == 1) {
            formula = formulaHtml;
        } else if (optionLength >= 2) {
            formula = createAndOr + formulaHtml;
        }
    } else {
        if ($option.next('.check-toggle').length) {
            $option.next('.check-toggle').remove();
        } else {
            $option.prev('.check-toggle').remove();
        }
        $option.remove();
    }

    var selectorStrList = [];
    selectorStrList.push('#levelUpModal .help-block');
    selectorStrList.push('#levelUpModal_v2 .help-block');
    $(selectorStrList.join(',')).append(formula);
}// EOF level_upgrade.loadFormula

// after animated  by animate.css
level_upgrade.animateCSS = function (nodeElement, animationName, callback) {
    var handleAnimationEnd = function () {
        nodeElement.classList.remove('animated', animationName);
        nodeElement.removeEventListener('animationend', handleAnimationEnd);
        if (typeof callback === 'function') callback();
    }
    nodeElement.addEventListener('animationend', handleAnimationEnd);
    nodeElement.classList.add('animated', animationName);
} // EOF level_upgrade.animateCSS