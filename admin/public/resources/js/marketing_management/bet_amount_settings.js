var bet_amount_settings = bet_amount_settings || {};

bet_amount_settings.initialize = function () {
    var _this = this;

    _this.levelUpModal$El = $('#levelUpModal_v2');
    _this.bet_amount_settings_modal$El = $('#bet_amount_settings-modal');

    _this.form_bet_amount_settings$El = $('form[name="form_bet_amount_settings"]');

    _this.vip_upgrade_setting = {};

    _this.settings = {};
    _this.settings.bet_amount_type_list = {};
    _this.settings.bet_amount_type_list.total = 'total';
    _this.settings.bet_amount_type_list.detail = 'detail';
};


bet_amount_settings.onReady = function () {
    var _this = this;

    _this.initialize();

    _this.bet_amount_settings_modal$El
        .on('show.bs.modal', function (e) {
            _this.show_bet_amount_settings_modal(e);
        }).on('shown.bs.modal', function (e) {
            // console.log('bet_amount_settings.shown.bs.modal');
        }).on('hide.bs.modal', function (e) {
            // console.log('bet_amount_settings.hide.bs.modal');
        }).on('hidden.bs.modal', function (e) {
            // console.log('bet_amount_settings.hidden.bs.modal');
        });

    $('body').on('click', '.bet_amount_settings_more', function (e) {
        _this.clicked_bet_amount_settings_more(e);
    });

    $('body').on('click', '.remove_bet_amount_settings', function (e) {
        _this.clicked_remove_bet_amount_settings(e);
    });

    $('body').on('click', '.save_bet_amount_settings', function (e) {
        _this.clicked_save_bet_amount_settings(e);
    });

    $('body').on('change', '.help-block .option-group-wrapper input[type="text"]', function (e) {
        // console.log('help-block .option-group-wrapper input[type="text"] change!!', arguments);
        _this.changed_formulaTextInput(e); // modify the amount
    });
    $('body').on('change', '.help-block .option-group-wrapper select', function (e) {
        // console.log('help-block .option-group-wrapper select change!!', arguments);
        _this.changed_formulaSelect(e); // modify the math_sign
    });
    $('body').on('change', '.help-block .option-group-wrapper input[type="checkbox"]', function (e) {
        // console.log('help-block .option-group-wrapper input[type="checkbox"] change!!', arguments);
        _this.changed_formulaCheckboxInput(e); // modify the precon_logic_flag
    });

};

bet_amount_settings.init_game_type_tree = function (upgrade_id, theData) {
    var _this = this;
    _this.game_type_tree$El = $('.game_type_tree');

    if (typeof (upgrade_id) === 'undefined') {
        upgrade_id = 0;
    }

    if (typeof (_this.game_type_tree$El.jstree) !== 'undefined') {
        _this.game_type_tree$El.jstree('destroy');
    }
    // console.error('init_game_type_tree.theData', theData);
    _this.game_type_tree$El
        .bind("loaded.jstree", function (e, data) {
            // console.log('loaded.jstree.data', data);
            // console.log('loaded.jstree.inst', data.instance);
            // data.instance.refresh()
            // _this.gameTypeTreeLoaded = true;
            // _this.on_loadedInJstree(event, data);
        }).bind('check_node.jstree', function (node, selected, e) {
            // var selected_nodes = _this.includedGameTypeTree$El.jstree('get_checked');
            // console.log('check_node.jstree', arguments);
            // _this.changed_CheckNodeInJstree(node, selected, e);
        }).bind('uncheck_node.jstree', function (node, selected, e) {
            // var selected_nodes = _this.includedGameTypeTree$El.jstree('get_checked');
            // console.log('uncheck_node.jstree', arguments);
            // _this.changed_CheckNodeInJstree(node, selected, e);
        }).bind('init.jstree', function (e) {
            // _this.gameTypeTreeLoaded = null;
            // console.log('init.jstree', arguments);
            // Not work
            // }).bind('will.destroy.jstree', function () {
            //     // }).bind('destroy.jstree', function () {
            //     _this.gameTypeTreeLoaded = null;
            //     console.log('destroy.jstree');
        }).bind('ready.jstree', function (e, data) {
            // console.log('ready.jstree', arguments);
            _this.readied_game_type_tree(e, data.instance, theData);
        })
        .jstree({
            'core': {
                'data': {
                    url: '/api/get_game_type_tree/',
                    type: 'json'
                }
            },
            "input_number": {
                "form_sel": '#form_bet_amount_settings'
            },
            "checkbox": {
                "tie_selection": false,
            },
            "plugins": [
                "search", "checkbox", "input_number"
            ]
        });

};

bet_amount_settings.readied_game_type_tree = function (e, theJstree, theData) {
    var _this = this;
    // console.log('readied_game_type_tree.theData:', theData); // @todo jstree 就緒後，應該要設定預設值
    // check exists nodes
    var betAmountSettingsStr = _this.vip_upgrade_setting.bet_amount_settings; // orig
    // var betAmountSettingsStr = level_upgrade.getBetSettingsInputValWithDefault();
    if ($.isEmptyObject(betAmountSettingsStr)) {
        betAmountSettingsStr = '{}';
    }


    var settingBetAmountSettings = JSON.parse(betAmountSettingsStr);
    //
    if (typeof (theData) !== 'undefined') {
        if (!$.isEmptyObject(theData.bet_amount_settings)) {
            var data_bet_amount_settings = JSON.parse(theData.bet_amount_settings);
            // $.extend(true, betAmountSettings, bet_amount_settings);
            if (!$.isEmptyObject(data_bet_amount_settings.itemList)) {
                settingBetAmountSettings.itemList = data_bet_amount_settings.itemList;
            }
            if (!$.isEmptyObject(data_bet_amount_settings.defaultItem)) {
                settingBetAmountSettings.defaultItem = data_bet_amount_settings.defaultItem;
            }
        }
    }

    betAmountSettingsStr = JSON.stringify(settingBetAmountSettings);

    if (!$.isEmptyObject(betAmountSettingsStr)) {
        _this.doCheckNodes_game_type_tree(betAmountSettingsStr);
    }

    // bet_amount_settings.itemList.map(function (keyStr, indexNumber) {
    //     bet_amount_settings.itemList[keyStr].type
    //     bet_amount_settings.itemList[keyStr].math_sign
    //     bet_amount_settings.itemList[keyStr].value
    //     bet_amount_settings.itemList[keyStr].game_type_id
    // })


}

bet_amount_settings.doCheckNodes_game_type_tree = function (theBetAmountSettingsStr) {
    var _this = this;

    //reset
    _this.game_type_tree$El.jstree('open_all');
    _this.game_type_tree$El.jstree('uncheck_all');

    var betAmountSettings = JSON.parse(theBetAmountSettingsStr);
    var assorted = level_upgrade.assortBetAmountSettings(betAmountSettings);
    var gamePlatformSettingList = assorted[1]; // gamePlatformSettingList
    var gameTypeSettingList = assorted[2]; // gameTypeSettingList
    // console.log('assorted:', assorted);
    var theSelectedNodeList = [];

    // for game platform node
    $.each(gamePlatformSettingList, function (key, val) {
        var sel = '[id="gp_' + val.game_platform_id + '_anchor"]'; // id="gp_29_anchor" - game_platform
        var theNode = _this.game_type_tree$El.jstree('get_node', sel, true);
        if (!$.isEmptyObject(theNode)) {
            theSelectedNodeList.push(theNode[0].id);
        }
        _this.game_type_tree$El.jstree('check_node', sel);
    });

    // for game platform input, per_gp_29
    $.each(gamePlatformSettingList, function (key, val) {
        var sel = '[id="per_gp_' + val.game_platform_id + '"]'; // id="per_324" - game_type
        var currInput$El = _this.game_type_tree$El.find(sel);
        currInput$El.val(val.value);
    });

    // for game type node
    $.each(gameTypeSettingList, function (key, val) {
        var sel = '[id="' + val.game_type_id + '_anchor"]'; // id="325_anchor" - game_type
        var theNode = _this.game_type_tree$El.jstree('get_node', sel, true);
        if (!$.isEmptyObject(theNode)) {

            // find parent platform node
            var parentNode$El = $(theNode).closest('li[id^="gp_"]')
            // console.log('parentNode$El:', parentNode$El, parentNode$El.length);
            if (parentNode$El.length > 0) {
                theSelectedNodeList.push(parentNode$El.attr('id'));
            }

        }
        _this.game_type_tree$El.jstree('check_node', sel);
    });

    // for game type input, per_324
    $.each(gameTypeSettingList, function (key, val) {
        var sel = '[id="per_' + val.game_type_id + '"]'; // id="per_324" - game_type
        var currInput$El = _this.game_type_tree$El.find(sel);
        currInput$El.val(val.value);
    });

    // console.log('theSelectedNodeList:', theSelectedNodeList);

    _this.doCloseUnselectedNode(theSelectedNodeList);
}; // EOF doCheckNodes_game_type_trees

bet_amount_settings.doCloseUnselectedNode = function (theSelectedNodeList) {
    var _this = this;

    // filter for Unique.
    var theKeepOpenNodeUniqueList = theSelectedNodeList.filter(function (value, index, self) {
        return self.indexOf(value) === index;
    });
    // search platform li for close the node
    $(_this.game_type_tree$El.selector + ' li').each(function (index, value) {
        var currLi$El = $(this);
        var currId = currLi$El.prop('id');
        if (theKeepOpenNodeUniqueList.indexOf(currId) == -1) {
            var node = _this.game_type_tree$El.jstree('get_node', currId);
            _this.game_type_tree$El.jstree('close_node', node);
        }
    });
}; // EOF doCloseUnselectedNode

bet_amount_settings.clicked_bet_amount_settings_more = function (e) {
    var _this = this;
    // var _betSettingsStr = level_upgrade.getBetSettingsInputValWithDefault();
    if ($('input[name="enable_bet_amount"]:checked').length > 0  // for enable_separate_accumulation_in_setting=true
        || $('input.option[value="1"]').length > 0 // for enable_separate_accumulation_in_setting=false
    ) {
        _this.bet_amount_settings_modal$El.modal('show');
    }
}


/**
 * Changed the text input in the formula Div.
 * The text input means the options' limit amount.
 * @param {event} e The event object.
 */
bet_amount_settings.changed_formulaTextInput = function (e) {
    var _this = this;
    var target$El = $(e.target);
    // console.log('changed_formulaTextInput.e', e);

    // (Bet Amount detail) amount-game_platform-29, amount-game_type-22
    // (Bet Amount Total) amount-1 :Bet Amount
    // amount-2 :Deposit Amount
    // amount-3/4: Loss/Win Amount
    var idKeyStr = target$El.prop('id');

    var _type = null;
    var _id = null;// only for game_platform and game_type.
    var _value = target$El.val();
    var isMGPA = _this.isMatchGamePlatformAmount(idKeyStr);
    var isMGTA = _this.isMatchGameTypeAmount(idKeyStr);
    var isMOA = _this.isMatchOptionsAmount(idKeyStr);
    if (isMGPA[0]) {
        _type = 'game_platform';
        _id = isMGPA[1];
    } else if (isMGTA[0]) {
        _type = 'game_type';
        _id = isMGTA[1];
    } else if (isMOA[0]) {
        _type = level_upgrade.jsonKey(isMOA[1]);
    }

    switch (_type) {
        case 'game_platform':
            _this.changed_formulaTextInput4game_platform(_id, _value);
            break;
        case 'game_type':
            _this.changed_formulaTextInput4game_type(_id, _value);
            break;
        case 'bet_amount':
            _this.changed_formulaTextInput4option_amount(_type, _value);
            break;
        case 'deposit_amount':
            _this.changed_formulaTextInput4option_amount(_type, _value);
            break;
        case 'loss_amount':
            _this.changed_formulaTextInput4option_amount(_type, _value);
            break;
        case 'win_amount':
            _this.changed_formulaTextInput4option_amount(_type, _value);
            break;
    }
}; // EOF changed_formulaTextInput

/**
 * Changed the amount of the options:bet_amount, deposit_amount, loss_amount and win_amount into the Formula Input of the Formula Div.
 *
 * @param {string} theId The option key string:bet_amount, deposit_amount, loss_amount and win_amount.
 * @param {number} theValue The limit amount.
 */
bet_amount_settings.changed_formulaTextInput4option_amount = function (theId, theValue) {
    var theFormulaStr = level_upgrade.getFormulaInputValWithDefault(); // source
    var theFormula = JSON.parse(theFormulaStr);
    theFormula[theId][1] = theValue;
    theFormulaStr = JSON.stringify(theFormula);
    level_upgrade.setFormulaInputValWithDefault(theFormulaStr); // replace
}; // EOF changed_formulaTextInput4option_amount



bet_amount_settings.changed_formulaCheckboxInput4game_platform = function (theId, theValue) {
    var theBetSettingsStr = level_upgrade.getBetSettingsInputValWithDefault(); // source
    var theBetSettings = JSON.parse(theBetSettingsStr);

    var _itemList = theBetSettings.itemList.map(function (item, indexNumber, selfArray) {
        // console.log('item:', item, 'selfArray:', selfArray, theValue);
        if (item.type == 'game_platform') {
            if (item.game_platform_id == theId) {
                item.precon_logic_flag = theValue;
            }
        }
        return item;
    });
    theBetSettings.itemList = _itemList;
    theBetSettingsStr = JSON.stringify(theBetSettings);
    level_upgrade.setBetSettingsInputValWithDefault(theBetSettingsStr); // replace
};// EOF changed_formulaCheckboxInput4game_platform


bet_amount_settings.changed_formulaCheckboxInput4game_type = function (theId, theValue) {
    var theBetSettingsStr = level_upgrade.getBetSettingsInputValWithDefault(); // source
    var theBetSettings = JSON.parse(theBetSettingsStr);

    var _itemList = theBetSettings.itemList.map(function (item, indexNumber, selfArray) {
        // console.log('item:', item, 'selfArray:', selfArray, theValue);
        if (item.type == 'game_type') {
            if (item.game_type_id == theId) {
                item.precon_logic_flag = theValue;
            }
        }
        return item;
    });
    theBetSettings.itemList = _itemList;
    theBetSettingsStr = JSON.stringify(theBetSettings);
    level_upgrade.setBetSettingsInputValWithDefault(theBetSettingsStr); // replace
};// EOF changed_formulaCheckboxInput4game_type


bet_amount_settings.changed_formulaCheckboxInput4option_amount = function (theId, theValue) {
    var theFormulaStr = level_upgrade.getFormulaInputValWithDefault(); // source
    var theFormula = JSON.parse(theFormulaStr);
    theFormula['operator_' + theId] = theValue;
    theFormulaStr = JSON.stringify(theFormula);
    level_upgrade.setFormulaInputValWithDefault(theFormulaStr); // replace
};// EOF changed_formulaCheckboxInput4option_amount

/**
 * Changed the math_sign of the game_platform into the BetSettings Input of the Formula Div
 * @param {string} theId The option key string:bet_amount, deposit_amount, loss_amount and win_amount.
 * @param {string} theValue The math symbol, "<=", ">=", "<" and ">".
 */
bet_amount_settings.changed_formulaSelectOption4game_platform = function (theId, theValue) {
    var theBetSettingsStr = level_upgrade.getBetSettingsInputValWithDefault(); // source
    var theBetSettings = JSON.parse(theBetSettingsStr);

    var _itemList = theBetSettings.itemList.map(function (item, indexNumber, selfArray) {
        // console.log('item:', item, 'selfArray:', selfArray, theValue);
        if (item.type == 'game_platform') {
            if (item.game_platform_id == theId) {
                item.math_sign = theValue;
            }
        }
        return item;
    });
    theBetSettings.itemList = _itemList;
    theBetSettingsStr = JSON.stringify(theBetSettings);
    level_upgrade.setBetSettingsInputValWithDefault(theBetSettingsStr); // replace
}; // EOF changed_formulaSelectOption4game_platform

/**
 * Changed the math_sign of the game_type into the BetSettings Input of the Formula Div.
 * @param {string} theId The option key string:bet_amount, deposit_amount, loss_amount and win_amount.
 * @param {string} theValue The math symbol, "<=", ">=", "<" and ">".
 */
bet_amount_settings.changed_formulaSelectOption4game_type = function (theId, theValue) {
    var theBetSettingsStr = level_upgrade.getBetSettingsInputValWithDefault(); // source
    var theBetSettings = JSON.parse(theBetSettingsStr);

    var _itemList = theBetSettings.itemList.map(function (item, indexNumber, selfArray) {
        // console.log('item:', item, 'selfArray:', selfArray, theValue);
        if (item.type == 'game_type') {
            if (item.game_type_id == theId) {
                item.math_sign = theValue;
            }
        }
        return item;
    });
    theBetSettings.itemList = _itemList;
    theBetSettingsStr = JSON.stringify(theBetSettings);
    level_upgrade.setBetSettingsInputValWithDefault(theBetSettingsStr); // replace
}; // EOF changed_formulaSelectOption4game_type
/**
 * Changed the math_sign of the options:bet_amount, deposit_amount, loss_amount and win_amount into the Formula Input of the Formula Div.
 * @param {string} theId The option key string:bet_amount, deposit_amount, loss_amount and win_amount.
 * @param {string} theValue The math symbol, "<=", ">=", "<" and ">".
 */
bet_amount_settings.changed_formulaSelectOption4option_amount = function (theId, theValue) {
    var theFormulaStr = level_upgrade.getFormulaInputValWithDefault(); // source
    var theFormula = JSON.parse(theFormulaStr);
    theFormula[theId][0] = theValue;
    theFormulaStr = JSON.stringify(theFormula);
    level_upgrade.setFormulaInputValWithDefault(theFormulaStr); // replace
}; // EOF changed_formulaSelectOption4option_amount

/**
 * Changed the amount of the game_platform into the BetSettings Input of the Formula Div.
 *
 * @param {integer} theId The game_platform id.
 * @param {number} theValue The limit amount.
 */
bet_amount_settings.changed_formulaTextInput4game_platform = function (theId, theValue) {
    var theBetSettingsStr = level_upgrade.getBetSettingsInputValWithDefault(); // source
    var theBetSettings = JSON.parse(theBetSettingsStr);
    var _itemList = theBetSettings.itemList.map(function (item, indexNumber, selfArray) {
        // console.log('item:', item, 'selfArray:', selfArray, theValue);
        if (item.type == 'game_platform') {
            if (item.game_platform_id == theId) {
                item.value = theValue;
            }
        }
        return item;
    });
    // console.log('_itemList:', _itemList, 'theBetSettings:', theBetSettings);
    theBetSettings.itemList = _itemList;
    theBetSettingsStr = JSON.stringify(theBetSettings);
    level_upgrade.setBetSettingsInputValWithDefault(theBetSettingsStr); // replace
}; // EOF changed_formulaTextInput4game_platform

/**
 * Changed the amount of the game_type into the BetSettings Input of the Formula Div.
 *
 * @param {integer} theId The game_type id.
 * @param {number} theValue The limit amount.
 */
bet_amount_settings.changed_formulaTextInput4game_type = function (theId, theValue) {
    var _this = this;
    // var theBetSettingsStr = $('.formula-container').find('input[type="hidden"][name="bet_settings"]').val();
    var theBetSettingsStr = level_upgrade.getBetSettingsInputValWithDefault(); // source
    var theBetSettings = JSON.parse(theBetSettingsStr);
    var _itemList = theBetSettings.itemList.map(function (item, indexNumber, selfArray) {
        // console.log('item:', item, 'selfArray:', selfArray, theValue);
        if (item.type == 'game_type') {
            if (item.game_type_id == theId) {
                item.value = theValue;
            }
        }
        return item;
    });
    // console.log('_itemList:', _itemList, 'theBetSettings:', theBetSettings);
    theBetSettings.itemList = _itemList;
    theBetSettingsStr = JSON.stringify(theBetSettings);
    level_upgrade.setBetSettingsInputValWithDefault(theBetSettingsStr); // replace
} // EOF changed_formulaTextInput4game_type



/**
 * Changed the drop down options of the select in the formula Div.
 * The drop down options of the select means the math symbol of the each option.
 * @param {event} e The event object.
 */
bet_amount_settings.changed_formulaSelect = function (e) {
    var _this = this;
    var target$El = $(e.target);

    // (Bet Amount detail) operator-game_platform-29, operator-game_type-18
    // (Bet Amount Total) operator-1 :Bet Amount
    // operator-2 :Deposit Amount
    // operator-3/4 : Loss/Win Amount
    var idKeyStr = target$El.prop('id');
    var _type = null;
    var _id = null;// only for game_platform and game_type.
    var _value = target$El.val();
    _value = level_upgrade.operatorKeyByVal(_value);// 4 convert to >
    var isMGPA = _this.isMatchGamePlatformOperator(idKeyStr);
    var isMGTA = _this.isMatchGameTypeOperator(idKeyStr);
    var isMOA = _this.isMatchOptionsOperator(idKeyStr);
    if (isMGPA[0]) {
        _type = 'game_platform';
        _id = isMGPA[1];
    } else if (isMGTA[0]) {
        _type = 'game_type';
        _id = isMGTA[1];
    } else if (isMOA[0]) {
        _type = level_upgrade.jsonKey(isMOA[1]);
    }
    // console.log('changed_formulaSelect._type:', _type, '_id:', _id, '_value:', _value, 'target$El:', target$El);
    switch (_type) {
        case 'game_platform':
            _this.changed_formulaSelectOption4game_platform(_id, _value);
            break;
        case 'game_type':
            _this.changed_formulaSelectOption4game_type(_id, _value);
            break;
        case 'bet_amount':
        // _this.changed_formulaSelectOption4option_amount(_type, _value);
        // break;
        case 'deposit_amount':
        // _this.changed_formulaSelectOption4option_amount(_type, _value);
        // break;
        case 'loss_amount':
        // _this.changed_formulaSelectOption4option_amount(_type, _value);
        // break;
        case 'win_amount':
            _this.changed_formulaSelectOption4option_amount(_type, _value);
            break;
    }

} // EOF changed_formulaSelect

/**
 * Changed the pre logic flag in the formula Div.
 * The text input means the options' limit amount.
 * @param {event} e The event object.
 */
bet_amount_settings.changed_formulaCheckboxInput = function (e) {
    var _this = this;
    var target$El = $(e.target);

    // (Bet Amount detail) toggle-game_platform-29, toggle-game_type-18
    // (Bet Amount Total) toggle-1 :Bet Amount
    // toggle-2 :Deposit Amount
    // toggle-3/4 : Loss/Win Amount
    var idKeyStr = target$El.prop('id');
    var _type = null;
    var _id = null;// only for game_platform and game_type.
    var _value = null;
    if (target$El.prop('checked')) {
        _value = 'and';
    } else {
        _value = 'or';
    }
    var isMGPA = _this.isMatchGamePlatformToggle(idKeyStr);
    var isMGTA = _this.isMatchGameTypeToggle(idKeyStr);
    var isMOA = _this.isMatchOptionsToggle(idKeyStr);
    if (isMGPA[0]) {
        _type = 'game_platform';
        _id = isMGPA[1];
    } else if (isMGTA[0]) {
        _type = 'game_type';
        _id = isMGTA[1];
    } else if (isMOA[0]) {
        _type = level_upgrade.jsonKey(isMOA[1]);
    }
    // console.log('changed_formulaCheckboxInput.564._type:', _type);
    // console.log('changed_formulaCheckboxInput._value:', _value);
    switch (_type) {
        case 'game_platform':
            _this.changed_formulaCheckboxInput4game_platform(_id, _value);
            break;
        case 'game_type':
            _this.changed_formulaCheckboxInput4game_type(_id, _value);
            break;

        // case 'bet_amount':
        case 'deposit_amount':
        case 'loss_amount':
        case 'win_amount':
            _type = isMOA[1];
            _this.changed_formulaCheckboxInput4option_amount(_type, _value);
            break;
    }
};// EOF changed_formulaCheckboxInput

bet_amount_settings.clicked_remove_bet_amount_settings = function (e) {
    var _this = this;
    _this.bet_amount_settings_modal$El.modal('hide');
};

/**
 * Clicked the Save Button of the Bet Amount Settings Dialog.
 * @param {event} e The event object.
 */
bet_amount_settings.clicked_save_bet_amount_settings = function (e) {
    var _this = this;
    // var selected_game=$('#gameTree').jstree('get_checked');
    // $('#gameTree').jstree('generate_number_fields');
    // {per_gp_29: 2, per_gp_5587: ""}

    /// form validation
    // display in the elements, ".tip-field"
    var isValidation = true;
    var bet_amount_type = $('input[name="bet_amount_type"]:checked').val();
    switch (bet_amount_type) {
        case 'total':
            var theTipField$El = $('.row-total_bet_amount').find('.tip-field');
            var total_bet_amount = $('.row-total_bet_amount').find('input[name="total_bet_amount"]').val();
            if (total_bet_amount.trim().length == 0) {
                isValidation = isValidation && false;

                var tipStr = '';
                tipStr += level_upgrade.theLANG.totalBetAmount;
                tipStr += ' ';
                tipStr += level_upgrade.theLANG.areRequired;
                theTipField$El.removeClass('hide').html(tipStr);
            } else {
                theTipField$El.addClass('hide');
                theTipField$El.html('');
            }
            break;
        case 'detail':
            var theTipField$El = $('.row-default_bet_amount').find('input[name="default_bet_amount"]').closest('.row').find('.tip-field');
            var default_bet_amount = $('.row-default_bet_amount').find('input[name="default_bet_amount"]').val();
            if (default_bet_amount.trim().length == 0) {
                isValidation = isValidation && false;

                var tipStr = '';
                tipStr += level_upgrade.theLANG.defaultBetAmount;
                tipStr += ' ';
                tipStr += level_upgrade.theLANG.areRequired;
                theTipField$El.removeClass('hide').html(tipStr);
            } else {
                theTipField$El.addClass('hide');
                theTipField$El.html('');
            }

            var theTipField$El = _this.game_type_tree$El.closest('.row').find('.tip-field');
            var jstree_checked = _this.game_type_tree$El.jstree('get_checked', true);
            if (jstree_checked.length == 0) {
                isValidation = isValidation && false;

                var tipStr = '';
                tipStr += level_upgrade.theLANG.gameTree;
                tipStr += ' ';
                tipStr += level_upgrade.theLANG.areRequired;// text.loading
                theTipField$El.removeClass('hide').html(tipStr);
            } else {
                theTipField$El.addClass('hide');
                theTipField$El.html('');
            }

            break;
    }



    if (isValidation) {
        var bet_amount_type = $('input[name="bet_amount_type"]:checked').val(); // total OR detail
        var total_bet_amount = $('input[name="total_bet_amount"]').val();
        var default_bet_amount = $('input[name="default_bet_amount"]').val();
        var jstree_checked = _this.game_type_tree$El.jstree('get_checked'); //  ["21", "22", "96", "324", "gp_29"]
        var jstree_generate_number_fields = _this.game_type_tree$El.jstree('generate_number_fields'); // {per_gp_29: "4", per_324: "3"}  , 'per_'+checked: input

        _this.assignToAddFormData(bet_amount_type, total_bet_amount, default_bet_amount, jstree_checked, jstree_generate_number_fields);
    }
};


bet_amount_settings.isMatch = function (keyStr, regex) {
    var _this = this;
    var isMatch = false;
    var matchId = null;
    // for detect operator-game_platform-XXX
    if (typeof (regex) === 'undefined') {
        regex = /operator-game_platform-(\d+)/i;// just default. should not referred it.
    }
    var found = keyStr.match(regex);

    if (found !== null) {
        isMatch = true;
        matchId = found[1];
    }
    return [isMatch, matchId];
}

// toggle-game_platform-29
bet_amount_settings.isMatchGamePlatformToggle = function (keyStr) {
    var _this = this;
    var regex = /toggle-game_platform-(\d+)/i;
    return _this.isMatch(keyStr, regex);
}
// toggle-game_type-19
bet_amount_settings.isMatchGameTypeToggle = function (keyStr) {
    var _this = this;
    var regex = /toggle-game_type-(\d+)/i;
    return _this.isMatch(keyStr, regex);
}
// toggle-2
bet_amount_settings.isMatchOptionsToggle = function (keyStr) {
    var _this = this;
    var regex = /toggle-(\d+)/i;
    return _this.isMatch(keyStr, regex);
}

bet_amount_settings.isMatchGamePlatformOperator = function (keyStr) {
    var _this = this;
    var regex = /operator-game_platform-(\d+)/i;
    return _this.isMatch(keyStr, regex);

    // var isMatch = false;
    // var matchId = null;
    // // for detect operator-game_platform-XXX
    // var re = /operator-game_platform-(\d+)/i;
    // var found = keyStr.match(re);
    //
    // if (found !== null) {
    //     isMatch = true;
    //     matchId = found[1];
    // }
    // return [isMatch, matchId];
}
bet_amount_settings.isMatchGameTypeOperator = function (keyStr) {
    var _this = this;
    var regex = /operator-game_type-(\d+)/i;
    return _this.isMatch(keyStr, regex);
}
bet_amount_settings.isMatchOptionsOperator = function (keyStr) {
    var _this = this;
    var regex = /operator-(\d+)/i;
    return _this.isMatch(keyStr, regex);
}


bet_amount_settings.isMatchGamePlatformAmount = function (keyStr) {
    var _this = this;
    var regex = /amount-game_platform-(\d+)/i;
    return _this.isMatch(keyStr, regex);

    // var isMatch = false;
    // var matchId = null;
    // // for detect amount-game_platform-XXX
    // var re = /amount-game_platform-(\d+)/i;
    // var found = keyStr.match(re);
    //
    // if (found !== null) {
    //     isMatch = true;
    //     matchId = found[1];
    // }
    // return [isMatch, matchId];
};
bet_amount_settings.isMatchGameTypeAmount = function (keyStr) {
    var _this = this;
    var regex = /amount-game_type-(\d+)/i;
    return _this.isMatch(keyStr, regex);

    // var isMatch = false;
    // var matchId = null;
    // // for detect amount-game_type-XXX
    // var re = /amount-game_type-(\d+)/i;
    // var found = keyStr.match(re);
    //
    // if (found !== null) {
    //     isMatch = true;
    //     matchId = found[1];
    // }
    // return [isMatch, matchId];
};
bet_amount_settings.isMatchOptionsAmount = function (keyStr) {
    var _this = this;
    var regex = /amount-(\d+)/i;
    return _this.isMatch(keyStr, regex);

    // var isMatch = false;
    // var matchId = null;
    // /// for detect amount-XXX
    // // (Bet Amount Total) amount-1 :Bet Amount
    // // amount-2 :Deposit Amount
    // // amount-3/4: Loss/Win Amount
    // var re = /amount-(\d+)/i;
    // var found = keyStr.match(re);
    //
    // if (found !== null) {
    //     isMatch = true;
    //     matchId = found[1];
    // }
    // return [isMatch, matchId];
};

/**
 * To pre-process for level_upgrade.addFormData().
 *
 * @param {string} theBetAmountType So far, support the values, "total" And "detail".
 * @param {integer} theTotalBetAmount The amount will be total bet amount for the formula.
 * @param {integer} theDefaultBetAmount The amount will appily into the nodes that's had checked and empty input.
 * @param {array} theJStreeChecked  The checked nodes list in the jstree.
 * @param {object} theJStreeGenerateNumberFields  The Not empty input list in the jstree.
 */
bet_amount_settings.assignToAddFormData = function (theBetAmountType, theTotalBetAmount, theDefaultBetAmount, theJStreeChecked, theJStreeGenerateNumberFields) {
    var _this = this;
    // theBetAmountType // total OR detail
    // theTotalBetAmount // number
    var _formulaStr = level_upgrade.getFormulaInputValWithDefault()
    var _betSettingsStr = level_upgrade.getBetSettingsInputValWithDefault();
    var _accumulationSettingsStr = level_upgrade.getAccumulationSettingsInputValWithDefault();
    // console.log('assignToAddFormData.arguments:', arguments);
    var optionKey = 'bet_amount';
    var optionId = level_upgrade.jsonValByKey(optionKey);
    $('.option[value="' + optionId + '"]').prop('checked', true);

    switch (theBetAmountType) {
        case _this.settings.bet_amount_type_list.total:

            // string convert to object for update
            var _formula = JSON.parse(_formulaStr);
            if ($.isEmptyObject(_formula[optionKey])) { // if not exists
                _formula[optionKey] = level_upgrade.defaults.formula['bet_amount'];
            } else {
                _formula[optionKey][1] = theTotalBetAmount;
            }
            // detect next exists and add operator_(n+1)
            _formula = level_upgrade.autoAddOperatorDuringOptions(_formula);
            // console.log('assignToAddFormData.total._formula', JSON.stringify(_formula));

            _formula = level_upgrade.sortFormulaByDefaults(_formula);
            // console.log('assignToAddFormData.total.sortFormulaByDefaults._formula', JSON.stringify(_formula));

            _formula = level_upgrade.removeFirstOperatorN(_formula);
            // console.log('assignToAddFormData.total.removeFirstOperatorN._formula', JSON.stringify(_formula));

            _formulaStr = JSON.stringify(_formula); // replace the _formulaStr param
            // {"bet_amount":[">=","3333"],"operator_2":"and","deposit_amount":["<=","4444"],"operator_3":"and","loss_amount":[">","5555"],"operator_4":"and","win_amount":["<","6666"]}
            // bet_amount[0]
            // bet_amount[1]

            _betSettingsStr = 'null'; // clear
            // console.log('assignToAddFormData.total._formulaStr:', _formulaStr);
            break;
        case _this.settings.bet_amount_type_list.detail:

            // theDefaultBetAmount
            // jstree_checked  ["21", "22", "96", "324", "gp_29"]
            // theJStreeGenerateNumberFields {per_gp_29: "4", per_324: "3"}
            if ($.isEmptyObject(_betSettingsStr)) {
                _betSettingsStr = '{}';
            }
            var _betSettings = JSON.parse(_betSettingsStr);
            if (_betSettings === null) {// for new
                _betSettings = {};
            }
            _betSettings.itemList = [];
            var needSimplifyGamePlatformList = []; //Need to detect and Simplify the game_type while the game_platform checked.
            // console.log('assignToAddFormData.theJStreeChecked:', theJStreeChecked);
            if (!$.isEmptyObject(theJStreeChecked)) {
                theJStreeChecked.map(function (currValue, indexNumber) {
                    var _item = {}
                    var typePrefix = '';
                    var gameId = null;
                    if (currValue.indexOf('gp_') > -1) {
                        typePrefix = 'gp_';
                        // game platform,

                        _item.type = 'game_platform';
                        gameId = currValue.replace(typePrefix, '');
                        _item.game_platform_id = gameId;

                        /// 需要 這個 GamePlatform 旗下的所有 game_type ，若有下面任一種狀況時，要化簡：
                        // - 都是與GamePlatform同一個數值。
                        // - 都是空值的時候。
                        /// 化簡動作：把旗下的所有 game_type 移除，保留 GamePlatform 代表即可。
                        needSimplifyGamePlatformList.push(gameId);
                    } else {
                        // game type
                        _item.type = 'game_type';
                        gameId = currValue;
                        _item.game_type_id = gameId;
                    }

                    if (typeof (theJStreeGenerateNumberFields['per_' + typePrefix + gameId]) === 'undefined') {
                        theJStreeGenerateNumberFields['per_' + typePrefix + gameId] = theDefaultBetAmount
                    }
                    _item.value = theJStreeGenerateNumberFields['per_' + typePrefix + gameId];
                    _item.math_sign = level_upgrade.defaults.bet_settings.defaultItem.math_sign;
                    _item.precon_logic_flag = level_upgrade.defaults.operator;
                    _betSettings.itemList.push(_item);
                });
            }

            // process the needSimplifyGamePlatformList
            if (needSimplifyGamePlatformList.length > 0) {
                _betSettings.itemList = _this.doSimplifyGamePlatformList(needSimplifyGamePlatformList, _betSettings.itemList);
            }

            // remove the precon_logic_flag of the first item.
            delete _betSettings.itemList[0].precon_logic_flag;

            // assign defaultItem
            _betSettings.defaultItem = {};
            _betSettings.defaultItem.value = _this.bet_amount_settings_modal$El.find('input[name="default_bet_amount"]').val();
            _betSettings.defaultItem.math_sign = level_upgrade.defaults.bet_settings.defaultItem.math_sign;

            _betSettingsStr = JSON.stringify(_betSettings);



            // tings.doCheckNodes_game_type_tree(theBetAmountSettingsStr)
            break;
    }


    var $deferred = level_upgrade.redrawFormula(_formulaStr, _accumulationSettingsStr, _betSettingsStr);
    $deferred.always(function () {
        _this.bet_amount_settings_modal$El.modal('hide');
    });
}; // EOF assignToAddFormData


bet_amount_settings.doSimplifyGamePlatformList = function (theSimplifyGamePlatformList, theItemList) {
    var _this = this;
    // var _itemList = JSON.parse(JSON.stringify(theItemList)); // clone array
    var _itemList = theItemList.slice(); // clone array

    // console.log('doSimplifyGamePlatformList:', theSimplifyGamePlatformList, _itemList);

    theSimplifyGamePlatformList.map(function (currValue, indexNumber) {
        var doSimplify = false;
        var currGamePlatformId = currValue;
        var jstree_checked = _this.game_type_tree$El.jstree('get_checked', true);
        var currGamePlatform$El = $(_this.game_type_tree$El).find('li[id="gp_' + currGamePlatformId + '"]');
        var currGameType$Els = currGamePlatform$El.find('.jstree-leaf');
        var game_type_default_amount = currGamePlatform$El.find('#per_gp_' + currGamePlatformId).val();
        if (game_type_default_amount == '') {
            game_type_default_amount = _this.bet_amount_settings_modal$El.find('input[name="default_bet_amount"]').val();
        }
        var game_type_length = currGameType$Els.length;
        var game_type_checked_length = currGameType$Els.find('.jstree-anchor.jstree-checked').length;

        var game_type_empty_length = 0; // empty as default
        var game_type_empty$Els = currGameType$Els.find('input[id^="per_"]').filter(function (typeIndexNumber, currEl) {
            var curr$El = $(currEl);
            return curr$El.val().trim() == '' || curr$El.val().trim() == game_type_default_amount;
        });
        game_type_empty_length = game_type_empty$Els.length;

        if (game_type_length == game_type_checked_length && game_type_length == game_type_empty_length) {
            doSimplify = true;
        }
        // console.log('doSimplifyGamePlatformList.doSimplify:', doSimplify
        //     , 'game_type_length(TOTAL):', game_type_length
        //     , 'game_type_checked_length(checked):', game_type_checked_length
        //     , 'game_type_empty_length(empty or default):', game_type_empty_length
        //     , 'jstree_checked:', jstree_checked);
        if (doSimplify) {
            // console.log('doSimplifyGamePlatformList.currGamePlatformId:', currGamePlatformId);
            var willFiltedGameTypeIdList = [];// collect the game_type_id for filter
            jstree_checked.map(function (currItem, checkedIndexNumber) {
                if (currItem.parent == 'gp_' + currGamePlatformId) {
                    willFiltedGameTypeIdList.push(currItem.id);
                }
            });
            // currGameType$Els.find('.jstree-anchor.jstree-checked').each(function (checkedIndexNumber, currEl) {
            //     var curr$El = $(currEl);
            //     // get the _game_type_id under the platform
            //     var _game_type_id = curr$El.prop('id').replace('_anchor', '')
            //     willFiltedGameTypeIdList.push(_game_type_id);
            // });

            // console.log('doSimplifyGamePlatformList.willFiltedGameTypeIdList:', willFiltedGameTypeIdList, jstree_checked);
            // filter the game type of the platform
            _itemList = _itemList.filter(function (currVal, filtedIndexNumber) {
                var _isFilted = false;
                if (typeof (currVal.game_type_id) !== 'undefined') { // for game type
                    // if find the game_type_id
                    _isFilted = willFiltedGameTypeIdList.indexOf(currVal.game_type_id) > -1;
                }
                return !_isFilted;
            });
            // console.log('doSimplifyGamePlatformList._itemListFilted:', _itemListFilted);
            // _itemList = _itemListFilted;
        }

    }); // EOF theSimplifyGamePlatformList.map(function (currValue, indexNumber) {...


    // console.log('doSimplifyGamePlatformList._itemList:', _itemList);
    return _itemList;
}; // EOF doSimplifyGamePlatformList

bet_amount_settings.show_bet_amount_settings_modal = function () {
    var _this = this;

    var upgrade_id = _this.levelUpModal$El.find('#upgradeId').val(); // $('#levelUpModal_v2 input[type="hidden"][id="upgradeId"]')

    var data = {};
    if (!$.isEmptyObject(upgrade_id)) {
        data = _this.getDataTableDataByUpgradeId(upgrade_id);
    }
    _this.vip_upgrade_setting = data;
    // console.log('show_bet_amount_settings_modal.data:', data);

    var _vip_upgrade_setting = JSON.parse(JSON.stringify(_this.vip_upgrade_setting)); // clone object
    var _formulaStr = level_upgrade.getFormulaInputValWithDefault()
    var _betSettingsStr = level_upgrade.getBetSettingsInputValWithDefault();
    // var _accumulationSettingsStr = level_upgrade.getAccumulationSettingsInputValWithDefault();
    _vip_upgrade_setting.formula = _formulaStr; // merge adjusted
    _vip_upgrade_setting.bet_amount_settings = _betSettingsStr; // merge adjusted

    _this.assign_bet_amount_settings_modal(_vip_upgrade_setting);

    // reset the tip after form validation
    _this.bet_amount_settings_modal$El.find('.tip-field').html('').addClass('hide');

}; // EOF show_bet_amount_settings_modal


bet_amount_settings.getDataTableDataByUpgradeId = function (upgrade_id) {
    var _this = this;

    var settingTbl_DataTable = $('#settingTbl').DataTable();
    var trList = settingTbl_DataTable.rows().nodes();
    var data = {};
    $.each(trList, function (indexNumber, currTr) {
        var currTr$El = $(currTr);
        data = settingTbl_DataTable.row(currTr$El).data();
        // console.log('getDataTableDataByUpgradeId.data:', data);
        if (data.upgrade_id == upgrade_id) {
            return false; // break
        }
    });

    return data;

} // EOF getDataTableDataByUpgradeId

/**
 * Assign values into the bet amount settings modal.
 *
 * @param {object} data The vip settings data.
 */
bet_amount_settings.assign_bet_amount_settings_modal = function (data) {
    var _this = this;
    // console.log('assign_bet_amount_settings_modal.arguments', arguments);

    var settingName = data.setting_name; // _this.levelUpModal$El.find('#settingName').val();
    _this.bet_amount_settings_modal$El.find('.title_setting_name').html(settingName);

    var bet_amount_type = _this.settings.bet_amount_type_list.total; // default, bet_amount_type = total

    if ($.isEmptyObject(data.bet_amount_settings)) { // new setting case
        bet_amount_type = _this.settings.bet_amount_type_list.total;
    } else if (data.bet_amount_settings === null) { // old settings/ without bet_amount_settings
        bet_amount_type = _this.settings.bet_amount_type_list.total;
    } else {
        var bet_amount_settings = {};
        if (!$.isEmptyObject(data.bet_amount_settings)) {
            bet_amount_settings = JSON.parse(data.bet_amount_settings);
        }
        // check bet_amount_type = detail
        if (!$.isEmptyObject(bet_amount_settings)) {
            if (!$.isEmptyObject(bet_amount_settings.itemList)) {
                bet_amount_type = _this.settings.bet_amount_type_list.detail;
            }
        }
    }

    // sync to bet_amount_type radio button
    $('[name="bet_amount_type"]').prop('checked', false); // reset
    $('[name="bet_amount_type"]').filter('[value="' + bet_amount_type + '"]').prop('checked', true);

    // console.log('assign_bet_amount_settings_modal.bet_amount_type', bet_amount_type);
    switch (bet_amount_type) {
        default:
        case _this.settings.bet_amount_type_list.total:
            _this.assign_total_bet_amount_settings_modal(data);
            break;
        case _this.settings.bet_amount_type_list.detail:
            _this.assign_detail_bet_amount_settings_modal(data);
            break;
    }
}; // EOF assign_bet_amount_settings_modal

/**
 * Assign TOTAL part UI of the bet amount settings modal.
 * @param {object} data The vip settings data.
 */
bet_amount_settings.assign_total_bet_amount_settings_modal = function (data) {
    var _this = this;

    // // checked to total radio button.
    // $('[name="bet_amount_type"]').prop('checked', false);
    // $('[name="bet_amount_type"]').filter('[value="total"]').prop('checked', true);

    // console.log('assign_total_bet_amount_settings_modal.arguments', arguments);
    // total amount ref. to formula.
    var total = _this.getTotalOfFormula(data);
    // console.log('assign_total_bet_amount_settings_modal.total', total);
    _this.form_bet_amount_settings$El.find('input[name="total_bet_amount"]').val(total);
    // if (typeof (data.formula) !== 'undefined') {
    //     var formula = JSON.parse(data.formula);
    //     if (typeof (formula.bet_amount) !== 'undefined') {
    //         _this.form_bet_amount_settings$El.find('input[name="total_bet_amount"]').val(total);
    //     }
    // }

    _this.init_game_type_tree(0);
}; // EOF assign_total_bet_amount_settings_modal

bet_amount_settings.assign_detail_bet_amount_settings_modal = function (data) {
    var _this = this;

    // $('[name="bet_amount_type"]').prop('checked', false);
    // $('[name="bet_amount_type"]').filter('[value="detail"]').prop('checked', true);

    var default_bet_amount = _this.getTotalOfFormula(data);
    // console.log('assign_detail_bet_amount_settings_modal.568.default_bet_amount', default_bet_amount);
    var _defaultItem = _this.getDefaultItemFromBetSettings(data);
    if (_defaultItem !== null) {
        default_bet_amount = _defaultItem.value;
    }
    // console.log('assign_detail_bet_amount_settings_modal.573.default_bet_amount', default_bet_amount);
    _this.form_bet_amount_settings$El.find('input[name="default_bet_amount"]').val(default_bet_amount);


    // console.log('176.assign_detail_bet_amount_settings_modal.data:', data);
    _this.init_game_type_tree(0, data); // @todo data.bet_amount_settings
}; // EOF assign_detail_bet_amount_settings_modal

bet_amount_settings.getDefaultItemFromBetSettings = function (data) {
    var _this = this;
    var bet_settings = null;

    var _defaultItem = {};
    if (typeof (data.bet_amount_settings) !== 'undefined') {
        var betAmountSettingsStr = data.bet_amount_settings;
        bet_settings = JSON.parse(betAmountSettingsStr);
    }

    if (typeof (bet_settings.defaultItem) !== 'undefined') {
        _defaultItem = bet_settings.defaultItem;
    }
    return _defaultItem;
}

bet_amount_settings.getTotalOfFormula = function (data) {
    var _this = this;
    var total = 0;

    // total amount ref. to formula.
    if (typeof (data.formula) !== 'undefined') {
        var formula = JSON.parse(data.formula);
        // console.log('getTotalOfFormula.formula', formula);
        if (typeof (formula.bet_amount) !== 'undefined') {
            if (!isNaN(formula.bet_amount[1])) {
                total = formula.bet_amount[1];
            }
        }
    }
    return total;
}// EOF getTotalOfFormula

