(function () {

    var Multiple_Range_Rules_By_Game_Tags = {} || Multiple_Range_Rules_By_Game_Tags;

    Multiple_Range_Rules_By_Game_Tags.init = function (options) {
        var self = this;

        self.options = {};
        self.options.default_offset = 0;
        self.options.default_per_page = 10;
        self.options.container = ".common_cashback_rules_by_multiple_range_for_game_tags";


        self.COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG = 'game_tag';

        self.template_settings = null;
        self.container$El = $(self.options.container);
        self.loader$El = $('<div class="common_cashback_multiple_range_raules_loader_for_game_tags"><div class="loader_instance"></div></div>');

        self.options.text = {};
        self.options.text.unknown_error = "Sorry, something wrong";
        self.options.text.close = "Close";

        self.options.text.save = "Save";
        self.options.text.tier_calc = 'textTierCalc';
        self.options.text.settings = 'textSettings';
        self.options.text.active = 'textActive';
        self.options.text.inactive = 'textInactive';
        self.options.text.active_childs = 'textActive_childs';
        self.options.text.rules = 'textRules';
        self.options.text.add = 'textAdd';
        self.options.text.min_bet_amount = 'textMin_bet_amount';
        self.options.text.max_bet_amount = 'textMax_bet_amount';
        self.options.text.cashback_percentage = 'textCashback_percentage';
        self.options.text.max_cashback_amount = 'textMax_cashback_amount';
        self.options.text.common_cashback_settings_of_multiple_range = 'common_cashback_settings_of_multiple_range';

        self.options.text.active_cashback = 'active_cashback';
        self.options.text.inactive_cashback = 'inactive_cashback';
        self.options.text.cashbackSettings = 'textCashbackSettings';
        self.options.text.save_success = 'save_success';
        self.options.text.save_failed = 'save_failed';
        self.options.text.confirm_delete_rule = 'confirm_delete_rule';
        self.options.text.delete = 'delete';
        self.options.text.deleted_successfully = 'deleted_successfully';
        self.options.text.deleted_failed = 'deleted_failed';

        self.options.text.tier_calculation_enabled_confirm = 'Are you sure to Enable the tier calculation??';
        self.options.text.tier_calculation_disabled_confirm = 'Are you sure to Disable the tier calculation??';

        self.options.text.recommand_set_to = 'Recommend set to ${amount}';
        // wlp = warning_logical_problem
        self.options.text.wlp_msg_empty_rules = 'Empty Rules';

        self.options.urls = {};
        self.options.urls.get_active_rules = "/marketing_management/getCommonCashbackRuleByMultipleRangeRulesByGameTags";
        self.options.urls.save_settings = "/marketing_management/saveCommonCashbackRuleByMultipleRangeSettingsByGameTags"
        self.options.urls.save_setting = "/marketing_management/saveCommonCashbackRuleByMultipleRangeSettingByGameTags"
        self.options.urls.create_rule = "/marketing_management/createCommonCashbackRuleByMultipleRangeRuleByGameTags";
        self.options.urls.update_rule = "/marketing_management/updateCommonCashbackRuleByMultipleRangeRuleByGameTags";
        self.options.urls.delete_rule = "/marketing_management/deleteCommonCashbackRuleByMultipleRangeRuleByGameTags";

        self.options = $.extend(true, {}, self.options, options);

        if (typeof (self.options.COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG) !== 'undefined') {
            self.COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG = self.options.COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG;
        }



        // setup the dom of document.
        var loaderOuterHtml = $('<div>').append(self.loader$El).html(); // like outerHTML via jquery
        self.container$El.empty().html(loaderOuterHtml);

        self.initEvents(); // the events handle

    } // EOF init()




    Multiple_Range_Rules_By_Game_Tags.initEvents = function () {
        var self = this;

        var selectorStr = '.mrrbgt_run_with_pagination';
        $('body').on('click', selectorStr, function (e) {
            // MRRBGT = Multiple_Range_Rules_By_Game_Tags
            self.clicked_mrrbgt_run_with_pagination(e);
        });

        // $(self.container$El).on('click', ' .game_tag_entry_body', function (e) {
        var selectorStr = self.options.container + ' .game_tag_entry_body';
        $('body').on('click', selectorStr, function (e) {
            self.clicked_game_tag_entry_body(e);
        });

        // $(self.container).on('click', '.create_rule', function(){
        var selectorStr = self.options.container + ' .create_rule';
        $('body').on('click', selectorStr, function (e) {
            self.clicked_create_rule(e);
        });

        // $(self.container).on('click', '.edit_rule', function(){
        var selectorStr = self.options.container + ' .edit_rule';
        $('body').on('click', selectorStr, function (e) {
            self.clicked_edit_rule(e);
        });

        // $(self.container).on('click', '.delete_rule', function(){
        var selectorStr = self.options.container + ' .delete_rule';
        $('body').on('click', selectorStr, function (e) {
            self.clicked_delete_rule(e);
        });


        // $(self.container).on('shown.bs.collapse', '.game_platform_entry_details', function(e){
        var selectorStr = self.options.container + ' .game_tag_entry_details';
        $('body').on('shown.bs.collapse', selectorStr, function (e) {
            self.shown_bs_collapse_game_tag_entry_details(e); // @todo
        });

        self.container$El.on('show.bs.collapse hide.bs.collapse', function (e) {
            self.toggleCollapse_container(e);
        });

        $('body').on('show.bs.tab', '.common_cashback_rules_mode_multiple_range_for_game_tags_toggle', function (e) {
            if ($(this).data('has-run')) {
            } else {
                $(this).data('has-run', true);
                self.runWithPagination();
            }
        });
        // $('.common_cashback_rules_mode_multiple_range_toggle').on('show.bs.tab', function(){
        //     if($(this).data('has-run')){
        //     }else{
        //         $(this).data('has-run', true);
        //         Common_Cashback_Multiple_Range_Rules.runWithPagination();
        //     }
        // });


        var selectorStr = self.options.container + ' .switch_cashback input[type=radio]';
        $('body').on('click', selectorStr, function (e) {
            self.clicked_switch_cashback_radio(e);

        }); // EOF $(self.container).on('click', '.switch_cashback input[type=radio]', function(){...

        // .switch_tier_calc_cashback
        // game_tag_2_tier_calc_cashback_switch
        var selectorStr = self.options.container + ' .switch_tier_calc_cashback input[type=radio]';

        $('body').on('click', selectorStr, function (e) {
            self.clicked_switch_tier_calc_cashback_radio(e);
        });

        var selectorStr = '#common_cashback_multiple_range_rules_modal:has(#common_cashback_multiple_range_setting_tier_calculation_form)';
        var selectorStrSubmit = selectorStr + ' .btn-submit';
        $('body').on('click', selectorStrSubmit, function (e) {
            self.clicked_save_of_switch_tier_calc_cashback_modal(e);
        });

    } // EOF initEvents

    Multiple_Range_Rules_By_Game_Tags.outerHtml = function (selectorStr) {
        return $('<div>').append($(selectorStr).clone()).html();
    };


    Multiple_Range_Rules_By_Game_Tags.getTplHtmlWithOuterHtmlAndReplaceAll = function (selectorStr, regexList) {
        var self = this;

        var _outerHtml = '';
        if (typeof (selectorStr) !== 'undefined') {
            _outerHtml = $(selectorStr).html(); // self.outerHtml(selectorStr);
        }
        // REF. BY data-results_counter="${results_counter}" data-playerpromoid="${playerpromo_id}"

        if (typeof (regexList) === 'undefined') {
            regexList = [];
        }

        if (regexList.length > 0) {
            regexList.forEach(function (currRegex, indexNumber) {
                // assign playerpromo_id into the tpl
                var regex = currRegex['regex']; // var regex = /\$\{playerpromo_id\}/gi;
                _outerHtml = _outerHtml.replaceAll(regex, currRegex['replaceTo']);// currVal.playerpromo_id);
            });
        }
        return _outerHtml;
    } // getTplHtmlWithOuterHtmlAndReplaceAll

    Multiple_Range_Rules_By_Game_Tags.render = function (template_settings) {
        var self = this;
        self.container$El.empty();

        // var outerHtml = self.outerHtml('#tpl-game_tag_entry_container');

        var html = '';
        $.each(template_settings.settings, function (game_tag_id, game_tag_data) {

            var cb_mr_sid = game_tag_data.cashback_settings.cb_mr_sid;
            if (typeof (cb_mr_sid) === 'undefined') {
                cb_mr_sid = 0;
            }

            var regexList = [];
            var nIndex = -1;

            nIndex++; // # 0
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{game_tag_id\}/gi; // ${game_tag_id};
            regexList[nIndex]['replaceTo'] = game_tag_id;

            nIndex++; // # 1

            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{cb_mr_sid\}/gi; // ${cb_mr_sid};
            regexList[nIndex]['replaceTo'] = cb_mr_sid;

            nIndex++; // # 2
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{theGameTagEntryBody\}/gi; // ${theGameTagEntryBody};
            regexList[nIndex]['replaceTo'] = self.renderGameTagEntryBody(game_tag_id, game_tag_data); // ref. to renderGamePlatformEntryBody

            nIndex++; // # 3
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{theGamePlatformEntryDetails\}/gi; // ${game_tag_id};
            regexList[nIndex]['replaceTo'] = self.renderGameTagEntryDetails(game_tag_id, game_tag_data); // ref. to renderGamePlatformEntryDetails
            var outerHtml = self.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-game_tag_entry_container', regexList);

            html += outerHtml;
        });
        self.container$El.html(html);

        self.container$El.append(self.loader$El);

        self.loader$El.hide();
    };// EOF render

    Multiple_Range_Rules_By_Game_Tags.renderGameTagEntryBody = function (game_tag_id, game_tag_data) {
        var self = this;
        var html = '';
        // tpl-game_tag_entry_body
        var regexList = [];
        var nIndex = -1;

        nIndex++; // # 0
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{game_tag_id\}/gi; // ${game_tag_id};
        regexList[nIndex]['replaceTo'] = game_tag_id;

        nIndex++; // # 1
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{game_tag_name\}/gi; // ${game_tag_name};
        regexList[nIndex]['replaceTo'] = game_tag_data.name;

        var outerHtml = self.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-game_tag_entry_body', regexList);
        html += outerHtml;
        // html += '<div id="game_platform_' + game_platform_id + '_body" class="game_platform_entry_body">';

        // html += '<div class="game_platform_details_toggle">';
        // html += '<button data-toggle="collapse" data-target="#game_platform_' + game_platform_id + '_details"><i class="glyphicon glyphicon-chevron-up"></i></button>';
        // html += '</div>'; // game_platform_details_toggle

        // html += '<div class="game_platform_entry_content">';
        // html += '<div class="game_platform_entry_title">' + game_platform_data.name + '</div>';
        // html += '<div class="game_platform_entry_summary">';
        // html += '</div>'; // game_platform_entry_summary
        // html += '</div>'; // game_platform_entry_content

        // html += '<div class="clearfix"></div>';
        // html += '</div>'; // game_platform_entry_body

        return html;
    }; // EOF renderGameTagEntryBody

    // ref. to renderGamePlatformEntryDetails
    Multiple_Range_Rules_By_Game_Tags.renderGameTagEntryDetails = function (game_tag_id, game_tag_data) {
        var self = this;

        var enabled_tier_calc_cashback = 0;
        if (typeof (game_tag_data.cashback_settings.enabled_tier_calc_cashback) !== 'undefined') {
            enabled_tier_calc_cashback = game_tag_data.cashback_settings.enabled_tier_calc_cashback;
        }

        var cb_mr_sid = game_tag_data.cashback_settings.cb_mr_sid;
        if (typeof (cb_mr_sid) === 'undefined') {
            cb_mr_sid = 0; // Not yet setup, need to created
        }

        // params:
        //     textSettings v
        //     game_tag_id v
        //     setting_type v
        //     type_map_id v
        //     textActive v
        //     textInactive v
        //     textActive_childs v
        //
        //     container_class
        //     textRules v
        //     textAdd v
        //     setting_type v
        //     type_map_id v
        //     textMin_bet_amount v
        //     textMax_bet_amount v
        //     textCashback_percentage v
        //     textMax_cashback_amount v
        var nIndex = -1;
        var html = '';
        // tpl-game_tag_entry_body
        var regexList = [];

        nIndex++; // #0 textSettings
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textSettings\}/gi; // ${game_tag_id};
        regexList[nIndex]['replaceTo'] = self.options.text.settings;

        nIndex++; // #0.1 textTierCalc
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textTierCalc\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.tier_calc;


        nIndex++; // #1 textActive
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textActive\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.active;

        nIndex++; // #2 textInactive
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textInactive\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.inactive;

        nIndex++; // #3 textActive_childs
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textActive_childs\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.active_childs;

        nIndex++; // #4 textRules
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textRules\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.rules;

        nIndex++; // #5 textAdd
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textAdd\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.add;

        nIndex++; // #6 textMin_bet_amount
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textMin_bet_amount\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.min_bet_amount;

        nIndex++; // #7 textMax_bet_amount
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textMax_bet_amount\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.max_bet_amount;

        nIndex++; // #8 textCashback_percentage
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textCashback_percentage\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.cashback_percentage;

        nIndex++; // #9 textMax_cashback_amount
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textMax_cashback_amount\}/gi;
        regexList[nIndex]['replaceTo'] = self.options.text.max_cashback_amount;

        nIndex++; // #10
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{game_tag_id\}/gi; // ${game_tag_id};
        regexList[nIndex]['replaceTo'] = game_tag_id;

        nIndex++; // #11
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{setting_type\}/gi; // ${setting_type};
        regexList[nIndex]['replaceTo'] = self.COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG;

        nIndex++; // #12
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{type_map_id\}/gi; // ${type_map_id};
        regexList[nIndex]['replaceTo'] = game_tag_id; // game_tag_data.cashback_settings.type_map_id;

        nIndex++; // #13
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{game_tag_name\}/gi; // ${game_tag_name};
        regexList[nIndex]['replaceTo'] = game_tag_data.name;

        nIndex++; // #14
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{container_class\}/gi; // ${container_class};
        regexList[nIndex]['replaceTo'] = 'game_tag_entry_rules_container'; // orig, game_platform_entry_rules_container


        nIndex++; // #15
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{cb_mr_sid\}/gi; // ${cb_mr_sid};
        regexList[nIndex]['replaceTo'] = cb_mr_sid;


        var enable_tier_calc_cashback_checked = '';
        var disable_tier_calc_cashback_checked = '';
        if (!!enabled_tier_calc_cashback) {
            enable_tier_calc_cashback_checked = 'checked="checked"';
        } else {
            disable_tier_calc_cashback_checked = 'checked="checked"';
        }

        nIndex++; // #16
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{enable_tier_calc_cashback_checked\}/gi; // ${enable_tier_calc_cashback_checked};
        regexList[nIndex]['replaceTo'] = enable_tier_calc_cashback_checked;

        nIndex++; // #17
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{disable_tier_calc_cashback_checked\}/gi; // ${disable_tier_calc_cashback_checked};
        regexList[nIndex]['replaceTo'] = disable_tier_calc_cashback_checked;

        var outerHtml = self.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-game_tag_entry_details', regexList);
        html += outerHtml;

        return html;
    } // EOF renderGameTagEntryDetails

    Multiple_Range_Rules_By_Game_Tags.combine_pagination_into_uri_get_active_rules = function (the_uri, offset, per_page) {
        the_uri += '/';
        the_uri += offset + '/';
        the_uri += per_page + '/';
        return the_uri;
    }; // EOF combine_pagination_into_uri_get_active_rules

    Multiple_Range_Rules_By_Game_Tags.runWithPagination = function (offset, per_page) {
        var self = this;
        if (typeof (offset) == 'undefined') {
            offset = self.options.default_offset;
        }

        if (typeof (per_page) == 'undefined') {
            per_page = self.options.default_per_page;
        }

        // update for Pagination
        self.options.offset = offset;
        self.options.per_page = per_page;

        var toShowLoading = true;
        self.reloadSettingsWithPagination(function (data) { // doneCB
            self.render(data);
            self.applyAllGameTagSettings(data);
        }, toShowLoading);
    } // EOF runWithPagination

    Multiple_Range_Rules_By_Game_Tags.applyAllGameTagSettings = function (template_settings) {
        var self = this;

        $.each(template_settings.settings, function (game_tag_id, game_tag_data) {
            self.applyGameTagSettings(game_tag_id, game_tag_data);
        });
    };

    /**
     * To ajax for get rules data.
     *
     * @param {function|script} callback The callback while response of ajax.
     * @param {boolean} toShowLoading The switch for display loadding during ajax.
     *
     */
    Multiple_Range_Rules_By_Game_Tags.getActiveRulesWithPagination = function (callback, toShowLoading) {
        var self = this;

        if (typeof (toShowLoading) === 'undefined') {
            toShowLoading = false;
        }
        if (toShowLoading) {
            self.loader$El.show(); // orgin, self.loader
        }

        // self.loader.hide();
        var ajax = $.ajax({
            "contentType": "application/json; charset=utf-8",
            "dataType": "json",
            "url": self.combine_pagination_into_uri_get_active_rules(self.options.urls.get_active_rules, self.options.offset, self.options.per_page),
            "type": "GET",
            "success": function (result) { // doneCB arguments
                if (result.status === "success") {
                    if (typeof callback === "function") {
                        callback.apply(self, [result.data]);
                    }
                }
            },
            "error": function () {
            }
        });

        if (toShowLoading) {
            ajax.always(function () {
                self.loader$El.hide(); // orgin, self.loader
            });
        }

    }; // EOF getActiveRulesWithPagination

    /**
     * Reload Settings with Pagination
     * Ref. by self::reloadSettings().
     * @param {function|script} callback The callback while response of ajax.
     * @param {boolean} toShowLoading The switch for display loading during ajax.
     *
     */
    Multiple_Range_Rules_By_Game_Tags.reloadSettingsWithPagination = function (callback, toShowLoading) {
        var self = this;
        self.getActiveRulesWithPagination(function (settings) { // doneCB
            self.template_settings = settings;

            self.checkSettings(settings);
            if (typeof callback === "function") callback(settings);

            var container = self.container$El;
            container.closest('.container').find('.dataTables_paginate.paging_simple_numbers').html(settings.pagination.create_links);
            container.closest('.container').find('.dataTables_info').html(settings.pagination.info);
            container.closest('.container').find('.paginate_button.active').attr('data-offset', settings.pagination.curr_offset).prop('data-offset', settings.pagination.curr_offset); // for callback after saved settings.

        }, toShowLoading); // EOF getActiveRulesWithPagination
    } // EOF reloadSettingsWithPagination


    Multiple_Range_Rules_By_Game_Tags.saveSettings = function (tpl_id
        , type
        , type_map_id
        , enabled_cashback
        , callback
    ) {
        var self = this;
        var data = {
            "tpl_id": tpl_id,
            "type": type,
            "type_map_id": type_map_id,
            "enabled_cashback": enabled_cashback
        };
        // var container = self.container;
        // $offset = container.closest('.container').find('.paginate_button.active').data('offset');
        // self.runWithPagination($offset);

        var _ajax = $.ajax({
            "contentType": "application/x-www-form-urlencoded",
            "url": self.options.urls.save_settings,
            "type": "POST",
            "data": data,
            "success": function (result) { // doneCB
                if (typeof callback === "function") callback(result);
            },
            "error": function () {
                if (typeof callback === "function") callback();
            }
        });
        return _ajax;
    } // EOF saveSettings

    /**
     * save Setting for Tier Calculation
     *
     * @param {integer} tpl_id
     * @param {*} type
     * @param {*} type_map_id
     * @param {*} enabled_tier_calc_cashback
     * @param {*} callback
     * @returns
     */
    Multiple_Range_Rules_By_Game_Tags.saveSetting4TierCalculation = function (tpl_id
        , type
        , type_map_id
        , enabled_tier_calc_cashback
        , callback
    ) {
        var self = this;

        var data = {
            "tpl_id": tpl_id,
            "type": type,
            "type_map_id": type_map_id,
            "enabled_tier_calc_cashback": enabled_tier_calc_cashback,
        };

        var _ajax = $.ajax({
            "contentType": "application/x-www-form-urlencoded",
            "url": self.options.urls.save_setting,
            "type": "POST",
            "data": data,
            beforeSend: function (jqXHR, settings) {
                jqXHR.requestData = {};
                jqXHR.requestData.url = settings.url;
                jqXHR.requestData.type = settings.type;
                jqXHR.requestData.data = settings.data;
                jqXHR.requestData.parsedData = self.parseQuery(settings.data);
            },
            "success": function (result, textStatus, jqXHR) { // doneCB
                // if (typeof callback === "function") callback(result);
                var cloned_arguments = Array.prototype.slice.call(arguments);
                if (typeof callback === "function") callback.apply(self, cloned_arguments);
            },
            "error": function (jqXHR, textStatus, errorThrown) {
                var cloned_arguments = Array.prototype.slice.call(arguments);
                if (typeof callback === "function") callback.apply(self, cloned_arguments);
            }
        });
        return _ajax;
    } // EOF saveSetting4TierCalculation

    Multiple_Range_Rules_By_Game_Tags.checkSettings = function (template_settings) {
        var self = this;

        $.each(template_settings.settings, function (game_platform_id, game_platform_data) {
            var game_platform_other_settings = {
                "has_cashback_rules_error": false,
                "childs_has_cashback_rules_error": false,
                "childs_has_enabled_cashback": false,
                "total_enabled_cashback_games": 0,
                "total_games": 0,
                "total_new_games": 0
            };

            game_platform_other_settings.has_cashback_rules_error = $.isEmptyObject(game_platform_data.cashback_rules);

            $.each(game_platform_data.types, function (game_type_id, game_type_data) {
                var game_type_other_settings = {
                    "has_cashback_rules_error": false,
                    "childs_has_cashback_rules_error": false,
                    "childs_has_enabled_cashback": false,
                    "total_enabled_cashback_games": 0,
                    "total_games": 0,
                    "total_new_games": 0
                };

                game_type_other_settings.has_cashback_rules_error = $.isEmptyObject(game_type_data.cashback_rules);
                if (game_type_other_settings.has_cashback_rules_error) {
                    game_type_other_settings.has_cashback_rules_error = game_platform_other_settings.has_cashback_rules_error;
                }

                $.each(game_type_data.game_list, function (game_description_id, game_description_data) {
                    var game_other_settings = {
                        "has_cashback_rules_error": $.isEmptyObject(game_description_data.cashback_rules)
                    };

                    if (game_description_data.cashback_settings.enabled_cashback) {
                        if (game_other_settings.has_cashback_rules_error) {
                            game_other_settings.has_cashback_rules_error = game_type_other_settings.has_cashback_rules_error;

                            if (game_other_settings.has_cashback_rules_error) {
                                game_type_other_settings.childs_has_cashback_rules_error = true;
                            }
                        }

                        game_type_other_settings.childs_has_enabled_cashback = true;
                        game_type_other_settings.total_enabled_cashback_games = game_type_other_settings.total_enabled_cashback_games + 1;
                    }

                    game_type_other_settings.total_games = game_type_other_settings.total_games + 1;

                    if ($.parseJSON(game_description_data.details.flag_new_game)) {
                        game_type_other_settings.total_new_games = game_type_other_settings.total_new_games + 1;
                    }

                    game_description_data.others = game_other_settings;
                }); // EOF $.each(game_type_data.game_list, function (game_description_id, game_description_data) {...

                game_platform_other_settings.childs_has_cashback_rules_error = (game_type_other_settings.childs_has_cashback_rules_error) ? true : game_platform_other_settings.childs_has_cashback_rules_error;
                game_platform_other_settings.childs_has_enabled_cashback = (game_type_other_settings.childs_has_enabled_cashback) ? true : game_platform_other_settings.childs_has_enabled_cashback;
                game_platform_other_settings.total_enabled_cashback_games = game_platform_other_settings.total_enabled_cashback_games + game_type_other_settings.total_enabled_cashback_games;
                game_platform_other_settings.total_games = game_platform_other_settings.total_games + game_type_other_settings.total_games;
                game_platform_other_settings.total_new_games = game_platform_other_settings.total_new_games + game_type_other_settings.total_new_games;

                game_type_data.others = game_type_other_settings;
            }); // EOF $.each(game_platform_data.types, function (game_type_id, game_type_data) {...

            game_platform_data.others = game_platform_other_settings;
        });
    }; // EOF checkSettings

    Multiple_Range_Rules_By_Game_Tags.reloadAndApplySettingsWithPagination = function (trigger_element, callback) {
        var self = this;
        self.reloadSettingsWithPagination(function () {
            // orig,game_platform_entry_container
            var game_tag_entry_container$El = trigger_element.closest('.game_tag_entry_container');
            if (game_tag_entry_container$El.length <= 0) {
                self.showUnknownError();
                return true;
            }

            // orig, game_platform_id
            var game_tag_id = game_tag_entry_container$El.data('game_tag_id');
            var game_tag_data = self.getGameTagData(game_tag_id);

            if (!game_tag_data) {
                self.showUnknownError();
                return true;
            }

            self.applyGameTagSettings(game_tag_id, game_tag_data);
            // self.applyAllGameTypeSettings(game_platform_id, game_platform_data);
            // self.applyAllGameSettingsWithGamePlatform(game_platform_id, game_platform_data); // @todo review is need to used.

            if (typeof callback === "function") callback();
        }); // EOF self.reloadSettingsWithPagination(self.options.offset, self.options.per_page, function(){...

    }; // EOF var reloadAndApplySettingsWithPagination = function(...

    Multiple_Range_Rules_By_Game_Tags.renderRulesEntry = function (container$El, cashback_rules) {
        var self = this;
        $('tbody', container$El).empty();


        $.each(cashback_rules, function (idx, rule_data) {

            var nIndex = -1;
            var regexList = [];

            nIndex++; // #0 cb_mr_rule_id
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{cb_mr_rule_id\}/gi; // ${cb_mr_rule_id};
            regexList[nIndex]['replaceTo'] = rule_data.cb_mr_rule_id;

            nIndex++; // #1 type
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{type\}/gi; // ${type};
            regexList[nIndex]['replaceTo'] = rule_data.type;

            nIndex++; // #2 type_map_id
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{type_map_id\}/gi; // ${type_map_id};
            regexList[nIndex]['replaceTo'] = rule_data.type_map_id;
            nIndex++; // #3 min_bet_amount
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{min_bet_amount\}/gi; // ${min_bet_amount};
            regexList[nIndex]['replaceTo'] = rule_data.min_bet_amount;
            nIndex++; // #4 max_bet_amount
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{max_bet_amount\}/gi; // ${max_bet_amount};
            regexList[nIndex]['replaceTo'] = rule_data.max_bet_amount;
            nIndex++; // #5 cashback_percentage
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{cashback_percentage\}/gi; // ${cashback_percentage};
            regexList[nIndex]['replaceTo'] = rule_data.cashback_percentage;
            nIndex++; // #6 min_bet_amount_text
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{min_bet_amount_text\}/gi; // ${min_bet_amount_text};
            regexList[nIndex]['replaceTo'] = rule_data.min_bet_amount_text;
            nIndex++; // #7 max_bet_amount_text
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{max_bet_amount_text\}/gi; // ${max_bet_amount_text};
            regexList[nIndex]['replaceTo'] = rule_data.max_bet_amount_text;
            nIndex++; // #8 max_cashback_amount
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{max_cashback_amount\}/gi; // ${max_cashback_amount};
            regexList[nIndex]['replaceTo'] = rule_data.max_cashback_amount;
            nIndex++; // #9 max_cashback_amount_text
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{max_cashback_amount_text\}/gi; // ${max_cashback_amount_text};
            regexList[nIndex]['replaceTo'] = rule_data.max_cashback_amount_text;

            var outerHtml = self.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-rule_entry', regexList);

            $('tbody', container$El).append(outerHtml);
        });
    } // EOF renderRulesEntry
    /**
     * Get the Rule Dialog for Create
     * @param {string} type
     * @param {integer} type_map_id
     * @param {integer} min_bet_amount
     * @param {integer} max_bet_amount
     * @param {integer|float} cashback_percentage
     * @param {integer} max_cashback_amount
     * @returns
     */
    Multiple_Range_Rules_By_Game_Tags.getCreateRuleModal = function (type, type_map_id, min_bet_amount, max_bet_amount, cashback_percentage, max_cashback_amount) {
        var self = this;

        return self.createRuleModal(null, type, type_map_id, min_bet_amount, max_bet_amount, cashback_percentage, max_cashback_amount);
    }; // EOF getCreateRuleModal
    /**
     * Get the Rule Dialog for Edit
     * @param {integer} rule_id
     * @param {string} type
     * @param {integer} type_map_id
     * @param {integer} min_bet_amount
     * @param {integer} max_bet_amount
     * @param {integer|float} cashback_percentage
     * @param {integer} max_cashback_amount
     * @returns void Just display the dialog for Edit the Rule.
     */
    Multiple_Range_Rules_By_Game_Tags.getEditRuleModal = function (rule_id, type, type_map_id, min_bet_amount, max_bet_amount, cashback_percentage, max_cashback_amount) {
        var self = this;
        return self.createRuleModal(rule_id, type, type_map_id, min_bet_amount, max_bet_amount, cashback_percentage, max_cashback_amount);
    }; // EOF getEditRuleModal

    /**
     * Send the Create Rule Request to Server
     * @param {integer} tpl_id
     * @param {string} type
     * @param {integer} type_map_id
     * @param {integer} min_bet_amount
     * @param {integer} max_bet_amount
     * @param {integer|float} cashback_percentage
     * @param {integer} max_cashback_amount
     * @param {CallableFunction} callback
     * @returns
     */
    Multiple_Range_Rules_By_Game_Tags.createRule = function (tpl_id
        , type
        , type_map_id
        , min_bet_amount
        , max_bet_amount
        , cashback_percentage
        , max_cashback_amount
        , callback // alwaysCB like, it will executed in "done" and "error"
    ) {
        var self = this;
        var data = {
            "tpl_id": tpl_id,
            "type": type,
            "type_map_id": type_map_id,
            "min_bet_amount": min_bet_amount,
            "max_bet_amount": max_bet_amount,
            "cashback_percentage": cashback_percentage,
            "max_cashback_amount": max_cashback_amount
        };
        // var container = self.container;
        // $offset = container.closest('.container').find('.paginate_button.active').data('offset');
        // self.runWithPagination($offset);

        var _ajax = $.ajax({
            "contentType": "application/x-www-form-urlencoded",
            "url": this.options.urls.create_rule,
            "type": "POST",
            "data": data,
            "success": function (result) {
                if (typeof callback === "function") callback(result);
            },
            "error": function () {
                if (typeof callback === "function") callback();
            }
        });
        return _ajax;
    }; // EOF createRule


    Multiple_Range_Rules_By_Game_Tags.updateRule = function (rule_id, min_bet_amount, max_bet_amount, cashback_percentage, max_cashback_amount, callback) {
        var self = this;
        var data = {
            "rule_id": rule_id,
            "min_bet_amount": min_bet_amount,
            "max_bet_amount": max_bet_amount,
            "cashback_percentage": cashback_percentage,
            "max_cashback_amount": max_cashback_amount
        };

        // var container = self.container;
        // $offset = container.closest('.container').find('.paginate_button.active').data('offset');
        // self.runWithPagination($offset);
        var _ajax = $.ajax({
            "contentType": "application/x-www-form-urlencoded",
            "url": self.options.urls.update_rule,
            "type": "POST",
            "data": data,
            "success": function (result) {
                if (typeof callback === "function") callback(result);
            },
            "error": function () {
                if (typeof callback === "function") callback();
            }
        });
        return _ajax;
    }; // EOF updateRule

    Multiple_Range_Rules_By_Game_Tags.deleteRule = function (rule_id, callback) {
        var self = this;
        var data = {
            "rule_id": rule_id
        };

        // var container = self.container;
        // $offset = container.closest('.container').find('.paginate_button.active').data('offset');
        // self.runWithPagination($offset);

        var _ajax = $.ajax({
            "contentType": "application/x-www-form-urlencoded",
            "url": self.options.urls.delete_rule,
            "type": "POST",
            "data": data,
            "success": function (result) {
                if (typeof callback === "function") callback(result);
            },
            "error": function () {
                if (typeof callback === "function") callback();
            }
        });
        return _ajax;
    } // EOF deleteRule

    Multiple_Range_Rules_By_Game_Tags.createModal = function (showCB, shownCB, hideCB, hiddenCB) {
        var self = this;
        var modal = null;
        if ($('#common_cashback_multiple_range_rules_modal').length > 0) {
            modal = $('#common_cashback_multiple_range_rules_modal');
        } else {
            var nIndex = -1;
            var regexList = [];

            nIndex++; // #0 textＣashbackSettings
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{textCashbackSettings\}/gi; // ${textCashbackSettings};
            regexList[nIndex]['replaceTo'] = self.options.text.cashbackSettings;

            nIndex++; // #1 textSave
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{textSave\}/gi; // ${textSave};
            regexList[nIndex]['replaceTo'] = self.options.text.save;

            nIndex++; // #2 textClose
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{textClose\}/gi; // ${textClose};
            regexList[nIndex]['replaceTo'] = self.options.text.close;

            var modal_html = self.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-common_cashback_multiple_range_rules_modal', regexList);
            modal_html = modal_html.replace(/<\!--[\r\n\S\s]*?-->/g, ""); // patch for the issue, many $('.modal-backdrop) append to modal.

            modal = $(modal_html).appendTo($('body'));

            $('#common_cashback_multiple_range_rules_modal')
                .on('show.bs.modal', function (event) {
                    if ('function' === typeof (showCB)) {
                        var cloned_arguments = Array.prototype.slice.call(arguments);
                        showCB.apply(self, cloned_arguments);
                    }

                    self.show_bs_modal_common_cashback_multiple_range_rules_modal.apply(self, event);
                }).on('shown.bs.modal', function (event) {
                    if ('function' === typeof (shownCB)) {
                        var cloned_arguments = Array.prototype.slice.call(arguments);
                        shownCB.apply(self, cloned_arguments);
                    }

                    self.shown_bs_modal_common_cashback_multiple_range_rules_modal.apply(self, event);
                }).on('hide.bs.modal', function (event) {
                    if ('function' === typeof (hideCB)) {
                        var cloned_arguments = Array.prototype.slice.call(arguments);
                        hideCB.apply(self, cloned_arguments);
                    }

                }).on('hidden.bs.modal', function (event) {
                    if ('function' === typeof (hiddenCB)) {
                        var cloned_arguments = Array.prototype.slice.call(arguments);
                        hiddenCB.apply(self, cloned_arguments);
                    }

                    modal.remove(); // orgin
                });

        }

        // modal.off('hide.bs.modal').on('hide.bs.modal', function () {
        //     modal.remove();
        // });

        return modal;
    } // EOF createModal

    /**
     * Confirm the Tier Calculation will to enabled/disabled on the setting
     * @param {integer} theSettingId
     * @param {bool} toEnabled
     *
     * enable_tier_calc_cashback, disable_tier_calc_cashback
     */
    Multiple_Range_Rules_By_Game_Tags.getConfirmTierCalculationModal = function (theSettingId, toEnabled, type_map_id, type) {
        var self = this;

        var to_enabled = 0;
        if (typeof (toEnabled) !== 'undefined') {
            to_enabled = toEnabled;
        }

        var textTier_calculation_confirm = self.options.text.tier_calculation_disabled_confirm;
        if (toEnabled == 1) {
            textTier_calculation_confirm = self.options.text.tier_calculation_enabled_confirm;
        }

        var modal = self.createModal(function (e) { // showCB
            // console.log('getConfirmTierCalculationModal.createModal.showCB', arguments);
        }, function (e) { // shownCB
            // console.log('getConfirmTierCalculationModal.createModal.shownCB', arguments);
        }, function (e) { // hideCB
            // console.log('getConfirmTierCalculationModal.createModal.hideCB', arguments);
        }, function (e) { // hiddenCB
            var theTarget$El = $(e.target);
            // CCMRSTCF = common_cashback_multiple_range_setting_tier_calculation_form
            var theCCMRSTCF$El = theTarget$El.find('#common_cashback_multiple_range_setting_tier_calculation_form');
            self.resetTierCalcRadiosOfSTCCByCCMRSTCF(theCCMRSTCF$El);
        });

        $('.modal-body', modal).empty(); // Clear the .modal-body of the modal.

        var nIndex = -1;
        var regexList = [];

        nIndex++; // #0 textTier_calculation_enabled_confirm
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textTier_calculation_confirm\}/gi; // ${textTier_calculation_confirm};
        regexList[nIndex]['replaceTo'] = textTier_calculation_confirm;

        nIndex++; // #1 setting_id
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{setting_id\}/gi; // ${setting_id};
        regexList[nIndex]['replaceTo'] = theSettingId;

        nIndex++; // #2 to_enabled
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{to_enabled\}/gi; // ${to_enabled};
        regexList[nIndex]['replaceTo'] = to_enabled;

        // nIndex++; // #3 game_tag_id
        // regexList[nIndex] = {};
        // regexList[nIndex]['regex'] = /\$\{game_tag_id\}/gi; // ${game_tag_id};
        // regexList[nIndex]['replaceTo'] = game_tag_id;

        nIndex++; // #4 type
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{type\}/gi; // ${type};
        regexList[nIndex]['replaceTo'] = type;

        nIndex++; // #5 type_map_id
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{type_map_id\}/gi; // ${type_map_id};
        regexList[nIndex]['replaceTo'] = type_map_id;

        nIndex++; // #6 tpl_id
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{tpl_id\}/gi; // ${tpl_id};
        regexList[nIndex]['replaceTo'] = self.template_settings.cb_mr_tpl_id;


        var form_html = self.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-common_cashback_multiple_range_setting_tier_calculation_form', regexList);

        var form = $(form_html).appendTo($('.modal-body', modal));

        return modal;
    }
    /**
     * Get/Create the Dialog for edit/create the Rule.
     *
     * Get the Dialog from the template,"#tpl-common_cashback_multiple_range_rules_modal", while the dialog is Not exists.
     *
     * @param {integer} rule_id
     * @param {string} type
     * @param {integer} type_map_id
     * @param {integer} min_bet_amount
     * @param {integer} max_bet_amount
     * @param {integer|float} cashback_percentage
     * @param {integer} max_cashback_amount
     * @returns
     */
    Multiple_Range_Rules_By_Game_Tags.createRuleModal = function (rule_id, type, type_map_id, min_bet_amount, max_bet_amount, cashback_percentage, max_cashback_amount) {
        var self = this;

        var modal = self.createModal(function () { // showCB
            // console.log('createRuleModal.createModal.showCB');
        }, function () { // shownCB
            // console.log('createRuleModal.createModal.shownCB');
        }, function () { // hideCB
            // console.log('createRuleModal.createModal.hideCB');
        }, function () { // hiddenCB
            // console.log('createRuleModal.createModal.hiddenCB');
        });

        $('.modal-body', modal).empty(); // Clear the .modal-body of the modal.

        var nIndex = -1;
        var regexList = [];

        nIndex++; // #0 textMin_bet_amount
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textMin_bet_amount\}/gi; // ${textMin_bet_amount};
        regexList[nIndex]['replaceTo'] = self.options.text.min_bet_amount;

        nIndex++; // #1 textMax_bet_amount
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textMax_bet_amount\}/gi; // ${textMax_bet_amount};
        regexList[nIndex]['replaceTo'] = self.options.text.max_bet_amount;

        nIndex++; // #2 textCashback_percentage
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textCashback_percentage\}/gi; // ${textCashback_percentage};
        regexList[nIndex]['replaceTo'] = self.options.text.cashback_percentage;

        nIndex++; // #3 textMax_cashback_amount
        regexList[nIndex] = {};
        regexList[nIndex]['regex'] = /\$\{textMax_cashback_amount\}/gi; // ${textMax_cashback_amount};
        regexList[nIndex]['replaceTo'] = self.options.text.max_cashback_amount;

        var form_html = self.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-common_cashback_multiple_range_rules_form', regexList);
        var form = $(form_html).appendTo($('.modal-body', modal));

        var isEnabledByTier = false;

        var game_tag_id = type_map_id;
        var selectorStr = '#game_tag_' + game_tag_id + '_details';
        var theGameTagContainer$El = $(selectorStr);

        selectorStr = '[name="game_tag_' + game_tag_id + '_tier_calc_cashback_switch"]';
        var theSwitchTierCalcCashback$Els = $(selectorStr);
        if (theSwitchTierCalcCashback$Els.val() == 1) {
            isEnabledByTier = true;
        }

        var rule_id_field = $('[name="rule_id"]', form);
        var type_field = $('[name="type"]', form);
        var type_map_id_field = $('[name="type_map_id"]', form);
        var min_bet_amount_field = $('[name="min_bet_amount"]', form);
        var max_bet_amount_field = $('[name="max_bet_amount"]', form);
        var cashback_percentage_field = $('[name="cashback_percentage"]', form);
        var max_cashback_amount_field = $('[name="max_cashback_amount"]', form);

        var submit_btn = $('.btn-submit', modal);

        rule_id_field.val(rule_id);
        type_field.val(type);
        type_map_id_field.val(type_map_id);
        min_bet_amount_field.val((min_bet_amount) ? min_bet_amount : '');
        max_bet_amount_field.val((max_bet_amount) ? max_bet_amount : '');
        cashback_percentage_field.val((cashback_percentage) ? cashback_percentage : '');
        max_cashback_amount_field.val((max_cashback_amount) ? max_cashback_amount : '');

        form.on('keydown', '[type=number]', function (e) {
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                // Allow: Ctrl+A
                (e.keyCode == 65 && e.ctrlKey === true) ||
                // Allow: home, end, left, right, down, up
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                // let it happen, don't do anything
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        min_bet_amount_field.on('change', function () {
            if ($(this).val().length) {
                $(this).val(parseFloat($(this).val()).toFixed(0));
            }
        });

        max_bet_amount_field.on('change', function () {
            if ($(this).val().length) {
                $(this).val(parseFloat($(this).val()).toFixed(0));
            }
        });

        cashback_percentage_field.on('change', function () {
            if ($(this).val().length) {
                $(this).val(parseFloat($(this).val()).toFixed(3));
            }
        });

        max_cashback_amount_field.on('change', function () {
            if ($(this).val().length) {
                $(this).val(parseFloat($(this).val()).toFixed(0));
            }
        });

        var maxestBetAmount = 0;
        if (isEnabledByTier) { // the validation for Tier Calculation
            /// Collect all rules for get the maxest bet amount of the settings
            var theRulesList$El = theGameTagContainer$El.find('.rules_list');
            var theEditRule$Els = theRulesList$El.find('.edit_rule');
            theEditRule$Els.each(function (index, element) {
                var curr$El = $(element);
                if (maxestBetAmount < curr$El.data('max_bet_amount')) {
                    maxestBetAmount = curr$El.data('max_bet_amount');
                }
            });
        } // EOF if (isEnabledByTier) {...

        if (rule_id === null) { // add form
            var recommand_set_to_min = maxestBetAmount + 1;
            min_bet_amount_field.val(recommand_set_to_min);
        }

        submit_btn.on('click', function () {
            var data = form.serializeArray().reduce(function (obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});

            // clear class
            form.find('.has-success, .has-error').each(function () {
                $(this).removeClass('has-success has-error');
            });
            form.find('.help-block').each(function () {
                $(this).empty();
            });

            var min_bet_amount = min_bet_amount_field.val();
            if (min_bet_amount.length && isNaN(parseFloat(min_bet_amount))) {
                min_bet_amount_field.closest('.form-group').addClass('has-error').find('.help-block').html(self.options.text.min_bet_amount_format_error);
            }

            var max_bet_amount = max_bet_amount_field.val();
            if (max_bet_amount.length && isNaN(parseFloat(max_bet_amount))) {
                max_bet_amount_field.closest('.form-group').addClass('has-error').find('.help-block').html(self.options.text.max_bet_amount_format_error);
            }

            var cashback_percentage = cashback_percentage_field.val();
            if (cashback_percentage.length <= 0) {
                cashback_percentage_field.closest('.form-group').addClass('has-error').find('.help-block').html(self.options.text.cashback_percentage_is_required);
            } else {
                if (isNaN(parseFloat(cashback_percentage))) {
                    cashback_percentage_field.closest('.form-group').addClass('has-error').find('.help-block').html(self.options.text.cashback_percentage_format_error);
                }
            }

            var max_cashback_amount = max_cashback_amount_field.val();
            if (max_cashback_amount.length && isNaN(parseFloat(max_cashback_amount))) {
                max_cashback_amount_field.closest('.form-group').addClass('has-error').find('.help-block').html(self.options.text.max_cashback_amount_format_error);
            }

            if (
                (min_bet_amount.length && !isNaN(parseFloat(min_bet_amount))) &&
                (max_bet_amount.length && !isNaN(parseFloat(max_bet_amount))) &&
                (parseFloat(min_bet_amount) > parseFloat(max_bet_amount))
            ) {
                max_bet_amount_field.closest('.form-group').addClass('has-error').find('.help-block').html(self.options.text.max_bet_ammount_must_be_greater_than_min_bet_amount);
            }



            if (isEnabledByTier) { // the validation for Tier Calculation

                if (rule_id === null) { // add form
                    var recommand_set_to_min = maxestBetAmount + 1;
                    if (min_bet_amount_field.val() != recommand_set_to_min) {
                        // assign the recommand amount
                        var recommand_set_to_amount = self.options.text.recommand_set_to;
                        var regex = /\$\{amount\}/gi; // ${amount}
                        recommand_set_to_amount = recommand_set_to_amount.replaceAll(regex, recommand_set_to_min);

                        min_bet_amount_field.closest('.form-group').addClass('has-error').find('.help-block').html(recommand_set_to_amount);
                    } // EOF if (min_bet_amount_field.val() != recommand_set_to_min) {...
                } // EOF if (rule_id === null) {...

            } // EOF if (isEnabledByTier) {...

            if ($('.has-error', form).length) {
                $('.has-error input:first', form).focus();
            } else {
                modal.trigger($.Event('valid.t1t.cashback.multiple-range.rules.form', {}), data);
            }

        }); // EOF submit_btn.on('click', function () {...

        return modal;
    } // EOF createRuleModal


    /**
     * Display the Error Dialog.
     *
     * @param function callback The callback will be executed after hide the Dialog, onhide().
     * @param string unknown_error The Specified Error Message.
     */
    Multiple_Range_Rules_By_Game_Tags.showUnknownError = function (callback, unknown_error) {
        var self = this;

        if (typeof (unknown_error) === 'undefined') {
            unknown_error = self.options.text.unknown_error;
        }
        if (typeof callback === "undefined") {
            callback = function () {
                window.location.reload();
            };
        }
        BootstrapDialog.show({
            "title": self.options.text.common_cashback_settings_of_multiple_range,
            "message": unknown_error,
            "onhide": function (dialog) {
                if (typeof callback === "function") {
                    callback.apply(self, arguments);
                }
            }
        });
    } // EOF showUnknownError

    Multiple_Range_Rules_By_Game_Tags.getDataByTypeMap = function (type, type_map_id) {
        var self = this;
        if (type == self.COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG) {
            var game_tag_id = type_map_id;
            if (!self.template_settings.settings.hasOwnProperty(game_tag_id)) {
                return false;
            }
            return self.template_settings.settings[game_tag_id];
        }
        return false;
    }; // EOF Multiple_Range_Rules_By_Game_Tags.getDataByTypeMap = function(type, type_map_id){...


    Multiple_Range_Rules_By_Game_Tags.reload_rules = function (trigger_element, type, type_map_id) {
        var self = this;
        var rules, container, game_tag_id, game_tag_data;
        var game_tag_entry_container$El = trigger_element.closest('.game_tag_entry_container');
        if (game_tag_entry_container$El.length <= 0) {
            self.showUnknownError();
            return true;
        }

        if (type == self.COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG) {

        }

        game_tag_id = type_map_id;
        game_tag_data = self.getGameTagData(game_tag_id);

        if (!game_tag_data) {
            self.showUnknownError();
            return true;
        }

        container = $('.game_tag_entry_rules_container .rules_list', game_tag_entry_container$El);
        rules = game_tag_data.cashback_rules;

        self.renderRulesEntry(container, rules);
    } // EOF reload_rules


    Multiple_Range_Rules_By_Game_Tags.clicked_create_rule = function (e) {
        var self = this;
        var theTarget$El = $(e.target);
        if (!theTarget$El.hasClass('create_rule')) {
            theTarget$El = theTarget$El.closest('.create_rule');
        }

        var button = theTarget$El; // $(this)
        var type = theTarget$El.data('type');
        var type_map_id = theTarget$El.data('type_map_id'); //  theTarget$El  convert to type_map_id
        var modal = self.getCreateRuleModal(type, type_map_id);//  createRuleModal

        modal.on('valid.t1t.cashback.multiple-range.rules.form', function (e, data) {

            modal.modal('hide');
            self.loader$El.show();

            self.createRule(self.template_settings.cb_mr_tpl_id // @todo
                , data.type
                , data.type_map_id
                , data.min_bet_amount
                , data.max_bet_amount
                , data.cashback_percentage
                , data.max_cashback_amount
                , function (result) { // callback

                    self.reloadAndApplySettingsWithPagination(button, function () { // callbackCB
                        self.loader$El.hide();
                        self.reload_rules(button, type, type_map_id);
                        if (result.status === "success") {
                            BootstrapDialog.show({
                                "title": self.options.text.common_cashback_settings_of_multiple_range + '1055',// @todo debug
                                "message": self.options.text.save_success
                            });
                        } else {
                            BootstrapDialog.show({
                                "title": self.options.text.common_cashback_settings_of_multiple_range + '1060',// @todo debug
                                "message": self.options.text.save_failed
                            });
                        }
                    });
                });
        }); // EOF modal.on('valid.t1t.cashback.multiple-range.rules.form', function (e, data) {
        modal.modal('show');
    }; // EOF clicked_create_rule

    Multiple_Range_Rules_By_Game_Tags.clicked_edit_rule = function (e) {
        var self = this;

        var theTarget$El = $(e.target);
        var editRule$El = theTarget$El;
        if (!theTarget$El.hasClass('edit_rule')) {
            editRule$El = theTarget$El.closest('.edit_rule');
        }

        var button = editRule$El; // $(this);
        var rule_id = editRule$El.data('rule_id'); // $(this).data('rule_id');
        var type = editRule$El.data('type'); // $(this).data('type');
        var type_map_id = editRule$El.data('type_map_id'); // $(this).data('type_map_id');
        var min_bet_amount = editRule$El.data('min_bet_amount'); // $(this).data('min_bet_amount');
        var max_bet_amount = editRule$El.data('max_bet_amount'); // $(this).data('max_bet_amount');
        var cashback_percentage = editRule$El.data('cashback_percentage'); // $(this).data('cashback_percentage');
        var max_cashback_amount = editRule$El.data('max_cashback_amount'); // $(this).data('max_cashback_amount');
        var modal = self.getEditRuleModal(rule_id, type, type_map_id, min_bet_amount, max_bet_amount, cashback_percentage, max_cashback_amount);

        modal.on('valid.t1t.cashback.multiple-range.rules.form', function (e, data) {
            modal.modal('hide');
            self.loader$El.show();
            self.updateRule(data.rule_id, data.min_bet_amount, data.max_bet_amount, data.cashback_percentage, data.max_cashback_amount, function (result) {
                self.reloadSettingsWithPagination(function () {
                    self.loader$El.hide();
                    self.reload_rules(button, type, type_map_id);
                    if (result.status === "success") {
                        BootstrapDialog.show({
                            "title": self.options.text.common_cashback_settings_of_multiple_range + '1100',// @todo debug
                            "message": self.options.text.save_success
                        });
                    } else {
                        BootstrapDialog.show({
                            "title": self.options.text.common_cashback_settings_of_multiple_range + '1105',// @todo debug
                            "message": self.options.text.save_failed
                        });
                    }
                });
            });
        });
        modal.modal('show');
    } // EOF clicked_edit_rule


    Multiple_Range_Rules_By_Game_Tags.clicked_delete_rule = function (e) {
        var self = this;

        var theTarget$El = $(e.target);
        var deleteRule$El = theTarget$El;
        if (!theTarget$El.hasClass('delete_rule')) {
            deleteRule$El = theTarget$El.closest('.delete_rule');
        }

        var button = deleteRule$El; // $(this);
        var type = deleteRule$El.data('type'); // $(this).data('type');
        var type_map_id = deleteRule$El.data('type_map_id'); // $(this).data('type_map_id');

        var data = deleteRule$El.data(); // $(this).data();

        BootstrapDialog.show({
            title: self.options.text.common_cashback_settings_of_multiple_range,
            message: self.options.text.confirm_delete_rule,
            buttons: [{
                "id": 'common_cashback_rule_delete_btn',
                "label": self.options.text.delete,
                "cssClass": 'btn-danger',
                "action": function (dialog) {
                    var $button = this;
                    $button.disable();
                    $button.spin();
                    dialog.setClosable(false);

                    var $closeButton = dialog.getButton('common_cashback_rule_close_btn');
                    $closeButton.disable();

                    self.deleteRule(data.rule_id, function (result) {
                        self.reloadAndApplySettingsWithPagination(button, function () {
                            dialog.close();

                            if (result.status === "success") {
                                BootstrapDialog.show({
                                    "title": self.options.text.common_cashback_settings_of_multiple_range,
                                    "message": self.options.text.deleted_successfully,
                                    "onhide": function (dialog) {
                                        self.reload_rules(button, type, type_map_id);
                                    }
                                });
                            } else {
                                BootstrapDialog.show({
                                    "title": self.options.text.common_cashback_settings_of_multiple_range,
                                    "message": self.options.text.deleted_failed,
                                    "onhide": function (dialog) {
                                        self.reload_rules(button, type, type_map_id);
                                    }
                                });
                            }
                        });
                    });
                } // EOF "action": function(dialog){...
            }, {
                "id": 'common_cashback_rule_close_btn',
                "label": self.options.text.close,
                "action": function (dialog) {
                    var $button = this;
                    $button.disable();
                    $button.spin();
                    dialog.close();
                }
            }] // EOf buttons: [{...
        }); // EOF BootstrapDialog.show({...
    } // EOF clicked_delete_rule

    Multiple_Range_Rules_By_Game_Tags.show_bs_modal_common_cashback_multiple_range_rules_modal = function (e) {
        // console.error('914.backdrop:', $('.modal-backdrop').length);
    };

    Multiple_Range_Rules_By_Game_Tags.shown_bs_modal_common_cashback_multiple_range_rules_modal = function (e) {
        // console.error('921.backdrop:', $('.modal-backdrop').length);
        // $('.modal-backdrop.in:not(".fade")').remove();
    };

    Multiple_Range_Rules_By_Game_Tags.clicked_mrrbgt_run_with_pagination = function (e) {
        var self = this;
        var target$El = $(e.target);

        var ahref = target$El.prop('href');
        var found = ahref.match(/runWithPagination\((\d+)\)/i); // javascript:void(0); /* runWithPagination(%s) */
        self.runWithPagination(found[1]);
    } // EOF clicked_mrrbgt_run_with_pagination

    Multiple_Range_Rules_By_Game_Tags.clicked_game_tag_entry_body = function (e) {
        var self = this;

        var target$El = $(e.target);
        var game_tag_entry_details = target$El.closest('.game_tag_entry_body').siblings('.game_tag_entry_details'); // $(this).siblings('.game_tag_entry_details');
        if (game_tag_entry_details.is(':visible')) {
            game_tag_entry_details.collapse('hide');
            return true;
        }

        var game_tag_entry_container$El = target$El.closest('.game_tag_entry_container');
        if (game_tag_entry_container$El.length <= 0) {
            self.showUnknownError(); // game_tag_entry_container$El.length = 0
            return true;
        }

        var game_tag_id = game_tag_entry_container$El.data('game_tag_id');
        var game_tag_data = self.getGameTagData(game_tag_id);

        if (!game_tag_data) {
            self.showUnknownError(); // game_tag_data is empty
            return true;
        }

        self.applyGameTagSettings(game_tag_id, game_tag_data);

        game_tag_entry_details.collapse('toggle');
    } // EOF clicked_game_tag_entry_body

    Multiple_Range_Rules_By_Game_Tags.clicked_switch_tier_calc_cashback_radio = function (e) {
        var self = this;
        var theTarget$El = $(e.target);
        var game_tag_entry_container$El = theTarget$El.closest('.game_tag_entry_container');

        var cb_mr_sid = game_tag_entry_container$El.data('cb_mr_sid');
        // will apply setting_id
        var theSettingId = cb_mr_sid;
        var toEnabled = theTarget$El.attr('value');
        var type_map_id = theTarget$El.data('type_map_id');
        var type = theTarget$El.data('type');

        var theModal = self.getConfirmTierCalculationModal(theSettingId, toEnabled, type_map_id, type);
        theModal.modal('show');
    } //  EOF clicked_switch_tier_calc_cashback_radio

    Multiple_Range_Rules_By_Game_Tags.clicked_save_of_switch_tier_calc_cashback_modal = function (e) {
        var self = this;
        var theTarget$El = $(e.target);

        var common_cashback_multiple_range_rules_modal$El = theTarget$El.closest('#common_cashback_multiple_range_rules_modal');
        var common_cashback_multiple_range_setting_tier_calculation_form$El = common_cashback_multiple_range_rules_modal$El.find('#common_cashback_multiple_range_setting_tier_calculation_form');

        var setting_id = common_cashback_multiple_range_setting_tier_calculation_form$El.find('[name="setting_id"]').val();
        // var game_tag_id = common_cashback_multiple_range_setting_tier_calculation_form$El.find('[name="game_tag_id"]').val();
        var to_enabled = common_cashback_multiple_range_setting_tier_calculation_form$El.find('[name="to_enabled"]').val();

        var type_map_id = common_cashback_multiple_range_setting_tier_calculation_form$El.find('[name="type_map_id"]').val();
        var type = common_cashback_multiple_range_setting_tier_calculation_form$El.find('[name="type"]').val();
        var tpl_id = self.template_settings.cb_mr_tpl_id;

        theTarget$El.button('loading');

        /// ajax saveCommonCashbackRuleByMultipleRangeSettingsByGameTags
        // self.options.urls.save_settings

        var enabled_tier_calc_cashback = to_enabled;
        var _ajax = self.saveSetting4TierCalculation(tpl_id
            , type
            , type_map_id
            , enabled_tier_calc_cashback
            , function (result, textStatus, jqXHR) { // done

                // update the attr.,checked="checked" for default.
                var type_map_id = jqXHR.requestData.parsedData.type_map_id;
                var enabled_tier_calc_cashback = jqXHR.requestData.parsedData.enabled_tier_calc_cashback;
                var selectorStr = 'input:radio[name="game_tag_' + type_map_id + '_tier_calc_cashback_switch"]'; // ex: $('input:radio[name="game_tag_2_tier_calc_cashback_switch"]')
                $(selectorStr).prop('checked', false).attr('checked', null); // for reset

                selectorStr = selectorStr + '[value=' + enabled_tier_calc_cashback + ']'; // ex: $('input:radio[name="game_tag_2_tier_calc_cashback_switch"][value=1]')
                $(selectorStr).prop('checked', 'checked').attr('checked', 'checked'); // add for default

                // close the modal
                common_cashback_multiple_range_rules_modal$El.find('.btn-default').trigger('click'); // close
            });
        _ajax.fail(function (jqXHR, textStatus, errorThrown) { // jqXHR, textStatus, errorThrown
            // alert( "Request failed: " + textStatus );
        });

        _ajax.always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
            theTarget$El.button('reset');
        });

    }// EOF clicked_save_of_switch_tier_calc_cashback_modal

    // CCMRSTCF = common_cashback_multiple_range_setting_tier_calculation_form
    // STCC = switch_tier_calc_cashback
    /**
     * Reset the Radio buttons of .switch_tier_calc_cashback By the tier calculation form of Modal
     * Use for non-save and close at the tier calculation donfirm modal
     *
     * @param {jquery(selector)} theCCMRSTCF$El The element, #common_cashback_multiple_range_setting_tier_calculation_form of the jquery.
     */
    Multiple_Range_Rules_By_Game_Tags.resetTierCalcRadiosOfSTCCByCCMRSTCF = function (theCCMRSTCF$El) {
        var self = this;

        var type_map_id = theCCMRSTCF$El.find('[name="type_map_id"]').val();

        // reset the radios
        var selectorStrName = 'game_tag_' + type_map_id + '_tier_calc_cashback_switch';
        var selectorStr = '[name="' + selectorStrName + '"][checked]';
        $(selectorStr).prop('checked', 'checked');
    } // EOF resetTierCalcRadiosOfSTCCByCCMRSTCF


    Multiple_Range_Rules_By_Game_Tags.clicked_switch_cashback_radio = function (e) {
        var self = this;
        var theTarget$El = $(e.target);

        if (theTarget$El.is(':disabled')) { // $(this).is(':disabled')
            return false;
        }

        var type = theTarget$El.data('type');
        var type_map_id = theTarget$El.data('type_map_id');
        var type_data = self.getDataByTypeMap(type, type_map_id);

        var value = theTarget$El.val();

        var option = theTarget$El;

        var bootstrap_dialog_btn_confirm_action = function (dialog) {
            var $button = this;
            $button.disable();
            $button.spin();

            var $closeButton = dialog.getButton('common_cashback_rule_close_btn');
            $closeButton.disable();

            self.saveSettings(self.template_settings.cb_mr_tpl_id, type, type_map_id, value, function () {
                self.reloadAndApplySettingsWithPagination(option, function () {
                    dialog.close();
                });
            });
        };

        var bootstrap_dialog_toggle_game_action = function (dialog) {
            var $closeButton = dialog.getButton('common_cashback_rule_close_btn');
            $closeButton.spin();
            $closeButton.disable();

            self.saveSettings(self.template_settings.cb_mr_tpl_id, type, type_map_id, value, function () {
                self.reloadAndApplySettingsWithPagination(option, function () {
                    dialog.close();
                });
            });
        };

        var bootstrap_dialog_btn_confirm_active = {
            "id": 'common_cashback_rule_confirm_btn',
            "label": self.options.text.active,
            "cssClass": 'btn-warning',
            "action": bootstrap_dialog_btn_confirm_action
        };

        var bootstrap_dialog_btn_confirm_inactive = {
            "id": 'common_cashback_rule_confirm_btn',
            "label": self.options.text.inactive,
            "cssClass": 'btn-warning',
            "action": bootstrap_dialog_btn_confirm_action
        };

        var bootstrap_dialog_btn_close = {
            "id": 'common_cashback_rule_close_btn',
            "hotkey": 13, // Enter.
            "label": self.options.text.close,
            "action": function (dialog) {
                var $button = this;
                dialog.close();
            }
        };

        var bootstrap_dialog_options = {
            "closable": false,
            "closeByBackdrop": false,
            "closeByKeyboard": false,
            "title": self.options.text.common_cashback_settings_of_multiple_range,
            "onshown": function (dialog) {
                var $closeButton = dialog.getButton('common_cashback_rule_close_btn');
                $closeButton.focus();
            }
        };

        if (value == "2") {
            return false;
        }

        if (type != self.COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG) {
            if (value == "1") {
                bootstrap_dialog_options['message'] = self.options.text.active_cashback_to_all_games_msg;
                bootstrap_dialog_options['buttons'] = [bootstrap_dialog_btn_confirm_active, bootstrap_dialog_btn_close];
                bootstrap_dialog_options['title'] = bootstrap_dialog_options['title'] + '1255'; // @todo debug
                BootstrapDialog.show(bootstrap_dialog_options);
            } else {
                bootstrap_dialog_options['message'] = self.options.text.inactive_cashback_to_all_games_msg;
                bootstrap_dialog_options['buttons'] = [bootstrap_dialog_btn_confirm_inactive, bootstrap_dialog_btn_close];
                bootstrap_dialog_options['title'] = bootstrap_dialog_options['title'] + '1260'; // @todo debug
                BootstrapDialog.show(bootstrap_dialog_options);
            }
        } else {
            if (value == "1") {
                BootstrapDialog.show({
                    "title": self.options.text.common_cashback_settings_of_multiple_range + '1264', // @todo debug
                    "message": self.options.text.active_cashback,
                    "buttons": [bootstrap_dialog_btn_close],
                    "onshown": bootstrap_dialog_toggle_game_action
                });
            } else {
                BootstrapDialog.show({
                    "title": self.options.text.common_cashback_settings_of_multiple_range + '1271', // @todo debug
                    "message": self.options.text.inactive_cashback,
                    "buttons": [bootstrap_dialog_btn_close],
                    "onshown": bootstrap_dialog_toggle_game_action
                });
            }
            return false;
        }

        return false;
    } // EOF clicked_switch_cashback_radio

    Multiple_Range_Rules_By_Game_Tags.toggleCollapse_container = function (e) {
        var element = $(e.target);
        var targetId = element.attr('id');
        var targetElement = $('button[data-target="#' + targetId + '"] .glyphicon');

        targetElement.toggleClass('glyphicon-chevron-up glyphicon-chevron-down');
    }// EOF toggleCollapse_container

    Multiple_Range_Rules_By_Game_Tags.shown_bs_collapse_game_tag_entry_details = function (e) {
        var self = this;
        var theTarget$El = $(e.target);
        var game_tag_entry_container$El = theTarget$El.closest('.game_tag_entry_container');
        var game_tag_id = game_tag_entry_container$El.data('game_tag_id');
        // type_map_id
        var game_tag_data = self.getGameTagData(game_tag_id); // ref. to getGamePlatofrmData() @todo getGameTagData()

        if (!game_tag_data) {
            self.showUnknownError(function () {
                // window.location.reload(); // @todo enable after done
            }, 'aaa');
            return true;
        }

        // // ref. to  applyAllGameTypeSettings(), applyGameTypeSettings()
        // self.applyAllGameTagSettings(self.template_settings); // @todo applyAllGameTagSettings()
        self.applyGameTagSettings(game_tag_id, game_tag_data);

        // ref. to renderRulesEntry()
        self.renderRulesEntry($('.game_tag_entry_rules_container .rules_list', game_tag_entry_container$El), game_tag_data.cashback_rules); // @todo renderRulesEntry()
    } // EOF shown_bs_collapse_game_tag_entry_details


    Multiple_Range_Rules_By_Game_Tags.getGameTagData = function (game_tag_id) {
        var self = this;
        if (!self.template_settings.settings.hasOwnProperty(game_tag_id)) {
            return false;
        }

        return self.template_settings.settings[game_tag_id];
    };

    Multiple_Range_Rules_By_Game_Tags.applyGameTagSettings = function (game_tag_id, game_tag_data) {
        var self = this;
        // type_map_id
        var container$El = $('#game_tag_' + game_tag_id, self.container$El);
        var body_container$El = $('#game_tag_' + game_tag_id + '_body', container$El);
        var details_container$El = $('#game_tag_' + game_tag_id + '_details', container$El);

        var summary_html = '';

        body_container$El.removeClass('warning_logical_problem');
        body_container$El.prop('data-wlp_msg', false).attr('data-wlp_msg', null);

        if (game_tag_data.cashback_settings.enabled_cashback) {
            summary_html += '<div class="entry"><span class="status_label">' + self.options.text.status + ':</span><span class="status_value active">' + self.options.text.active + '</span></div>';

            $('.game_tag_entry_settings_container .switch_cashback [value=1]', details_container$El).prop('checked', true);

            if (game_tag_data.others.has_cashback_rules_error) {
                // wlp = warning_logical_problem
                var wlp_msg = '';
                if (!$.isEmptyObject(body_container$El.data('wlp_msg'))) {
                    wlp_msg = body_container$El.data('wlp_msg');
                }
                if (wlp_msg.indexOf(self.options.text.wlp_msg_empty_rules) == -1) {
                    if (wlp_msg != '') {
                        wlp_msg += ', ';
                    }
                    wlp_msg += self.options.text.wlp_msg_empty_rules;
                }
                body_container$El.prop('data-wlp_msg', wlp_msg);
                body_container$El.attr('data-wlp_msg', wlp_msg);
            }

        } else {
            summary_html += '<div class="entry"><span class="status_label">' + self.options.text.status + ':</span><span class="status_value inactive">' + self.options.text.inactive + '</span></div>';

            $('.game_tag_entry_settings_container .switch_cashback [value=0]', details_container$El).prop('checked', true);
        }

        /// hide for $isHideTheGamesUnderTag = true;
        summary_html += '<div class="entry hide"><span class="total_games_label">' + self.options.text.total_games + ':</span><span class="total_games_value">' + game_tag_data.others.total_games + '</span></div>';
        summary_html += '<div class="entry hide"><span class="enabled_cashback_games_label">' + self.options.text.total_enabled_cashback_games + ':</span><span class="enabled_cashback_games_value">' + game_tag_data.others.total_enabled_cashback_games + '</span></div>';
        summary_html += '<div class="entry hide"><span class="total_new_games_label">' + self.options.text.total_new_games + ':</span><span class="total_new_games_value">' + game_tag_data.others.total_new_games + '</span></div>';

        $('.game_tag_entry_summary', body_container$El).html(summary_html);

        $(".warning_logical_problem").tooltip();


        $('[data-wlp_msg]').each(function () {
            var wlp_msg = $(this).data('wlp_msg');
            $(this).find('.game_tag_entry_summary .entry:has(".badge")').remove(); // reset

            $(this).find('.game_tag_entry_summary').append('<div class="entry"><span class="badge warning_logical_problem" data-toggle="tooltip" title="' + wlp_msg + '">!</span></div>');
        });


    } // EOF applyGameTagSettings


    /**
     *
     * Ref. to https://stackoverflow.com/a/13896633
     * @param string str The request string
     * @returns object
     */
    Multiple_Range_Rules_By_Game_Tags.parseQuery = function (str) {
        if (typeof str != "string" || str.length == 0) return {};
        var s = str.split("&");
        var s_length = s.length;
        var bit, query = {}, first, second;
        for (var i = 0; i < s_length; i++) {
            bit = s[i].split("=");
            first = decodeURIComponent(bit[0]);
            if (first.length == 0) continue;
            second = decodeURIComponent(bit[1]);
            if (typeof query[first] == "undefined") query[first] = second;
            else if (query[first] instanceof Array) query[first].push(second);
            else query[first] = [query[first], second];
        }
        return query;
    } // EOF parseQuery

    window['Multiple_Range_Rules_By_Game_Tags'] = Multiple_Range_Rules_By_Game_Tags;
})();