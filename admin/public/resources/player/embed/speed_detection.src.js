// ref. to https://github.com/umdjs/umd/blob/master/templates/jqueryPlugin.js
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof module === 'object' && module.exports) {
        // Node/CommonJS
        module.exports = function (root, jQuery) {
            if (jQuery === undefined) {
                // require('jQuery') returns a factory that requires window to
                // build a jQuery instance, we normalize how we use modules
                // that require this pattern but the window provided is a noop
                // if it's defined (how jquery works)
                if (typeof window !== 'undefined') {
                    jQuery = require('jquery');
                }
                else {
                    jQuery = require('jquery')(root);
                }
            }
            factory(jQuery);
            return jQuery;
        };
    } else {
        // Browser globals
        factory(jQuery);
    }

}(function ($) {
    'use strict';

    var speed_detection = speed_detection || {};

    // defaults
    speed_detection.defaults = {};
    speed_detection.defaults.debugLog = false;
    // speed_detection.defaults.each_detect_uri_timeout = 60 * 2000; // milliseconds
    speed_detection.defaults.report_log_uri = 'http://player.og.local/pub/speed_detect_report'; // window.navigator.userAgent
    speed_detection.load_resources_queue = [];
    speed_detection.defaults.detect_queue_task = {};
    speed_detection.defaults.detect_queue_task.start = null;
    speed_detection.defaults.detect_queue_task.deferr = null;
    speed_detection.defaults.detect_queue_task.is_done = null;
    speed_detection.defaults.detect_queue_task.latency = null;
    speed_detection.defaults.detect_item = { // ok
        label: 'www.default02.com'
        , detect_uri: 'https://www.default02.com/includes/images/logo.png'
        , go_href: 'https://m.default02.com/'
        // , _ajax: null
        , spent: null // milliseconds
    };
    speed_detection.defaults.detect_list = []; // ok
    speed_detection.defaults.detect_list.push(speed_detection.defaults.detect_item);
    speed_detection.defaults.detect_list.push({
        label: 'www.alibaba.com'
        , detect_uri: 'https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/cn.png' // 跨網域錯誤
        , go_href: 'https://www.alibaba.com/'
        , _ajax: null
    });
    // ok, ref.to sites/black_and_red/includes/speed_detection/templates.default.html
    speed_detection.defaults.templates_uri = '//www.og.local/includes/speed_detection/templates.default.html'; // @todo

    speed_detection.loaded_templates = {};
    speed_detection.loaded_templates.load_template = null;
    speed_detection.referr_list = {};
    // speed_detection.referr_list.load_templates = null;
    speed_detection.referr_list.detect_list_test_via_load_resources = null;
    // =====
    //  defaults.langs will be replaced by options.langs
    speed_detection.defaults.langs = {};
    speed_detection.defaults.langs.lang_millisecond_abr = 'ms.'; // lang_millisecond_abr
    speed_detection.defaults.langs.speed_detection = 'Speed Detection.'; // Speed Detection
    speed_detection.defaults.langs.lang_timeout = 'timeout.'; // lang_timeout
    speed_detection.defaults.langs.lang_go = 'go.'; // lang_go
    speed_detection.defaults.langs.lang_refresh = 'refresh.'; // lang_refresh
    speed_detection.defaults.langs.lang_close = 'close.'; // lang_close
    speed_detection.defaults.langs.directily_test = 'directily_test.'; // directily_test
    speed_detection.defaults.langs.translate_by_dict = 'translate_by_dict.'; // translate_by_dict


    // speed_detection.hook_element_selector = '.speed_detection';
    // speed_detection.templates_uri = '//www.og.local/includes/speed_detection/templates.html'; // @todo
    speed_detection.templates = {};
    speed_detection.templates.popup_tpl = null;
    speed_detection.templates.select_tpl = null;
    speed_detection.templates.option_tpl = null;
    // speed_detection.loaded_templates = {}; // Moved to UP
    // speed_detection.referr_list = {}; // Moved to UP
    // speed_detection.referr_list.load_templates = null;
    speed_detection.referr_list.detect_list_test_via_load_resources = null;

    // speed_detection.detect_list = [];
    // speed_detection.detect_list.push(speed_detection.defaults.detect_item);
    // speed_detection.detect_list.push({
    //     label: 'www.smashup02.com'
    //     , detect_uri: 'https://www.smashup05.com/includes/images/logo.png' // 跨網域錯誤
    //     // , detect_uri: 'https://player.smashup02.com/pub/announcement/jsonp'
    //     // , detect_uri: 'http://player.og.local/resources/player/echo.js'
    //     , go_href: 'https://m.smashup02.com/'
    //     , _ajax: null
    // });

    speed_detection.options = {};
    speed_detection.langs = {};


    speed_detection.has_loaded_templates = function () {
        var _this = this;
        return !$.isEmptyObject(_this.templates.popup_tpl);
    }

    /**
     * Load the templates for URI
     * load the templates with async ajax.
     *
     * @return jqXHR|Promise
     */
    speed_detection.load_templates = function () {
        var _this = this;

        var _ajax;

        if (!_this.has_loaded_templates()) {

            _ajax = $.ajax(_this.options.templates_uri, {
                method: 'GET',
                beforeSend: function (jqXHR, settings) {
                    // @todo display loading UI

                }
            }).always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
                _this.safelog('load_templates.always.arguments:', arguments);
                // @todo hidden loading UI
            }).done(function (data, textStatus, jqXHR) {
                _this.safelog('load_templates.done.arguments:', arguments);
                var doAppendToBody = true;
                _this.parse_templates_from_uri(data, doAppendToBody);
            }).fail(function (jqXHR, textStatus, errorThrown) {

            });
        } else {
            var $deferr = $.Deferred();
            var _data = null;
            var _textStatus = 'succeeded';
            var _jqXHR = null;
            $deferr.resolve.apply(_this, [_data, _textStatus, _jqXHR]);
            _ajax = $deferr.promise();
        }
        return _ajax;
    } // EOF load_templates

    speed_detection.parse_templates_from_uri = function (theData) {
        var _this = this;
        $(theData).appendTo('body');

        _this.templates.popup_tpl = $.trim($('.speed_detection_popup_tpl').html());
        /// assign method,
        // $(_this.templates.popup_tpl).find('.detect_list option').each(function(){
        //     console.log(this)
        //  })

        _this.templates.select_tpl = $.trim($('.speed_detection_select_tpl').html());

        _this.templates.option_tpl = $.trim($('.speed_detection_option_tpl').html());

        _this.safelog('parse_templates_from_uri.popup_tpl:', _this.templates.popup_tpl);
        _this.safelog('parse_templates_from_uri.select_tpl:', _this.templates.select_tpl);
        _this.safelog('parse_templates_from_uri.option_tpl:', _this.templates.option_tpl);
    } // EOF parse_templates_from_uri


    speed_detection.display_loading_popup = function (doneCB, failCB) {
        var _this = this;
        var _loading = _this.loaded_templates.load_templates;
        // build detect options
        _loading.done(function (data, textStatus, jqXHR) {
            _this.popupDom

        });
    }

    speed_detection.do_rebuild_popup = function (doneCB, failCB) {
        var _this = this;
        _this.referr_list.detect_list_test_via_load_resources = _this.detect_list_test_via_load_resources();

        var _deferr4BUO = _this.build_uri_options();
        _deferr4BUO.done(function (_deferr_return) {
            _this.safelog('build detect options.done:', arguments);
            if (_deferr_return.boolean) { // _deferr_return.ption_list And _deferr_return.options_html Need to debug
                _this.speed_detection_popup$El.find('.detect_list select[name="detected_uri"] option').remove();
                _this.speed_detection_popup$El.find('.detect_list select[name="detected_uri"]').append(_deferr_return.options_html);
            }
        });
        return _deferr4BUO;
    }
    /**
     * Build the dom structure
     * @param callable doneCB
     * @param callable failCB
     * @returns
     */
    speed_detection.build_popup = function (doneCB, failCB) {
        var _this = this;

        var _loading = _this.loaded_templates.load_templates;
        // var _loading = _this.load_templates(); // immediately

        _this.speed_detection_container_class = 'speed_detection_container';
        _this.speed_detection_container_selector = '.' + _this.speed_detection_container_class;
        if ($.isEmptyObject(_this.speed_detection_container$El)) {
            _this.speed_detection_container$El = null;
        }

        _this.speed_detection_popup_class = 'speed_detection_popup';
        _this.speed_detection_popup_selector = '.' + _this.speed_detection_popup_class;
        if ($.isEmptyObject(_this.speed_detection_popup$El)) {
            _this.speed_detection_popup$El = null;
        }



        /// append popup to body
        if ($(_this.speed_detection_container_selector).length == 0) {
            $('<div>').addClass(_this.speed_detection_container_class).appendTo('body');
        }
        if( $.isEmptyObject(_this.speed_detection_container$El) ){
            _this.speed_detection_container$El = $(_this.speed_detection_container_selector);
            _this.safelog('build_popup._this.227.speed_detection_container$El.length:', _this.speed_detection_container$El.length);
        }


        _this.safelog('build_popup._this.$(_this.speed_detection_container_selector):', $(_this.speed_detection_container_selector));
        _this.safelog('build_popup._this.232.speed_detection_container$El.length:', _this.speed_detection_container$El.length);

        /// display loading UI
        _this.speed_detection_container$El.find('.loading').removeClass('hide');
        _this.speed_detection_container$El.find('.speed_detection_popup').addClass('hide');
        // hide select UI for loading
        _this.speed_detection_container$El.find('.detect_list').removeClass('hide');

        // build detect options
        _loading.done(function (data, textStatus, jqXHR) {
            _this.safelog('build_popup.245._loading.done.arguments:', arguments);
            // append to speed_detection_container
            $(_this.templates.popup_tpl).appendTo(_this.speed_detection_container_selector);
            _this.speed_detection_popup$El = $(_this.speed_detection_popup_selector);
            _this.safelog('build_popup._loading.done.speed_detection_container_selector.html:', $(_this.speed_detection_container_selector).html());
            _this.safelog('build_popup._loading.done._this.speed_detection_popup$El:', _this.speed_detection_popup$El);


            /// hidden loading UI
            _this.speed_detection_popup$El.find('.loading').addClass('hide');
            _this.speed_detection_popup$El.find('.speed_detection_popup').removeClass('hide');
            // display select UI
            _this.speed_detection_popup$El.find('.detect_list').removeClass('hide');

            _this.apply_langs_title();
            _this.apply_langs_buttons();

            // remove select's options of speed_detection_container.
            _this.speed_detection_popup$El.find('select[name="detected_uri"] option').remove();


            // initial options, display label only
            // $('select[name="detected_uri"] option', _this.popupDom).remove();
            if (_this.options.detect_list.length > 0) {
                _this.options.detect_list.map(function (_detect_detail, indexNumber, _detect_list) {
                    var optionHtml = _this.getOptionHtmlAfterAssignValues(_detect_detail.go_href, _detect_detail.detect_uri, _detect_detail.label, undefined);
                    _this.speed_detection_popup$El.find('select[name="detected_uri"]').append(optionHtml);
                });
            }

        });


        // build detect options
        _loading.done(function (data, textStatus, jqXHR) {

            _this.safelog('build_popup._loading.done.arguments:', arguments);

            /// hidden loading UI
            _this.speed_detection_popup$El.find('.loading').addClass('hide');
            _this.speed_detection_popup$El.find('.speed_detection_popup').removeClass('hide');
            // display select UI
            _this.speed_detection_popup$El.find('.detect_list').removeClass('hide');

            // append results to options
            var _deferr4BUO = _this.build_uri_options();
            _deferr4BUO.done(function (_deferr_return) {
                _this.safelog('build detect options.done:', arguments);
                if (_deferr_return.boolean) { // _deferr_return.ption_list And _deferr_return.options_html Need to debug
                    _this.speed_detection_popup$El.find('.detect_list select[name="detected_uri"] option').remove();
                    _this.speed_detection_popup$El.find('.detect_list select[name="detected_uri"]').append(_deferr_return.options_html);
                }
            }); // EOF _deferr4BUO.done(function (_deferr_return) {...

        }); // EOF _loading.done(function (data, textStatus, jqXHR) {...

        /// callback to caller.
        _loading.done(function (data, textStatus, jqXHR) { // or resolve()
            if (typeof (doneCB) !== 'undefined') {
                var cloned_arguments = Array.prototype.slice.call(arguments);
                doneCB.apply(_this, cloned_arguments);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) { // or reject()
            if (typeof (failCB) !== 'undefined') {
                var cloned_arguments = Array.prototype.slice.call(arguments);
                failCB.apply(_this, cloned_arguments);
            }
        });


        return _loading;
    }

    /**
     * Build the option with the speed info
     *
     * @return jqXHR
     */
    speed_detection.build_uri_options = function () {
        var _this = this;
        var _deferr = $.Deferred();
        var options_html = '';

        var _deferr4DLTVLR = _this.referr_list.detect_list_test_via_load_resources; // _this.detect_list_test_via_load_resources();
        _deferr4DLTVLR.done(function (_deferr_return) { // resolve, always be resolve().
            // var _ajax = _this.detect_list_test().done(function (_deferr_return) { // resolve
            _this.safelog('build_uri_options._deferr_retur:', _deferr_return);

            _deferr_return.details.sort(function (a, b) {
                var a_latency = Number.MAX_VALUE; // 999999999;
                var b_latency = Number.MAX_VALUE; // 999999999;
                if (a.latency !== null) {
                    a_latency = a.latency;
                }
                if (b.latency !== null) {
                    b_latency = b.latency;
                }
                return a_latency - b_latency;
            });

            var option_list = _deferr_return.details.map(function (detected_item, indexNumber, detected_item_list) {
                // <!-- speed_detection_option_tpl, params: value, uri, label, spent -->


                // _this.getOptionHtmlAfterAssignValues(detected_item.go_href, detected_item.label, detected_item.label, detected_item.latency);
                var optionHtml = _this.templates.option_tpl.slice(); // clone tpl string
                _this.safelog('_deferr_return.map.237.detected_item:', detected_item);
                // assign the params into the tpl
                optionHtml = optionHtml.replace(/\$\{value\}/g, detected_item.label);
                optionHtml = optionHtml.replace(/\$\{uri\}/g, detected_item.go_href);
                optionHtml = optionHtml.replace(/\$\{label\}/g, detected_item.label);
                var spent_info = '';
                if (detected_item.latency === null) {
                    spent_info = _this.langs.lang_timeout;
                } else {
                    spent_info = detected_item.latency + ' ' + _this.langs.lang_millisecond_abr;
                }
                optionHtml = optionHtml.replace(/\$\{spent\}/g, spent_info); /// @todo need to add unit

                return optionHtml;
            });

            options_html = option_list.join(' ');
            // replace in select
            _deferr.resolve({
                'options_html': options_html
                , 'option_list': option_list
                , 'boolean': true
            });
        }); // EOF resolve

        return _deferr.promise();
    };

    /// moved to defaults of top
    // speed_detection.load_resources_queue = [];
    // speed_detection.defaults.detect_queue_task = {};
    // speed_detection.defaults.detect_queue_task.start = null;
    // speed_detection.defaults.detect_queue_task.deferr = null;
    // speed_detection.defaults.detect_queue_task.is_done = null;
    // speed_detection.defaults.detect_queue_task.latency = null;

    /**
     * load a image resource for detect
     * @param string img_uri the image uri.
     * @returns $.Deferred.promise
     */
    speed_detection.loadResource = function (detect_item) {
        var _this = this;

        detect_item.queue = $.extend(true, {}, _this.defaults.detect_queue_task);
        detect_item.queue.start = new Date().getTime();
        detect_item.queue.deferr = $.Deferred();
        detect_item.queue.label = detect_item.label;
        detect_item.queue.go_href = detect_item.go_href;
        detect_item.queue.detect_uri = detect_item.detect_uri;
        _this.safelog('loadResource.detect_item:', detect_item);
        var _curr_promise = detect_item.queue.deferr.promise();

        _curr_promise.done(function (queue_result) {
            _this.safelog('loadResource.done:', arguments, detect_item.queue.start);
            // @todo is all queue finish?
            // detect_item.queue.is_done = 1;
            detect_item.latency = queue_result.latency;
        }).fail(function (queue_result) {
            // detect_item.queue.is_done = 1;
            detect_item.latency = null;
        }).always(function (queue_result) {
            // console.log('loadResource.always:', arguments, _queue.start);
            // var curr_queue = _this.load_resources_queue[queue_result.index_number];
            detect_item.queue.is_done = 1;
            detect_item.is_done = 1;
        });

        var image1 = new Image();
        image1.onload = function (e) {
            // _this.safelog('loadResources:', arguments);
            _this.resourceTiming(e, detect_item.queue);
        };
        image1.onerror = function (e) {
            _this.safelog('loadResource.onerror:', arguments, detect_item.queue);
            detect_item.queue.msg = 'onerror';
            detect_item.queue.event = e;
            detect_item.queue.deferr.reject({
                // 'index_number': _queue_index_number
                'latency': null
                , 'msg': detect_item.queue.msg// @todo
                , 'event': e
                , 'label': detect_item.queue.label
                , 'go_href': detect_item.queue.go_href
                , 'detect_uri': detect_item.queue.detect_uri
            })
        };
        // image1.src = 'https://www.w3.org/Icons/w3c_main.png';
        image1.src = detect_item.detect_uri;
        return _curr_promise;
    }
    /**
     * Calc the timing onload of <img>.
     *
     * @param {event} e the event object.
     * @param {detect_queue_task} curr_queue The structure from "speed_detection.defaults.detect_queue_task" .
     */
    speed_detection.resourceTiming = function (e, curr_queue) {
        var _this = this;
        _this.safelog('resourceTiming:', arguments, curr_queue);

        var now = new Date().getTime();
        curr_queue.end = now;
        curr_queue.latency = now - curr_queue.start;
        curr_queue.msg = 'onload';
        curr_queue.deferr.resolve({ // will call doneCB of the deferr
            // 'index_number': queue_index_number
            'latency': curr_queue.latency
            , 'start': curr_queue.start
            , 'end': now
            , 'msg': curr_queue.msg // @todo
            , 'label': curr_queue.label
            , 'go_href': curr_queue.go_href
            , 'detect_uri': curr_queue.detect_uri
        });
    } // EOF resourceTiming

    speed_detection.get_some_tasks_in_queue = function (_item_detect_CB) {
        var _this = this;
        var not_done_list = _this.options.detect_list.filter(function (_detect_item, indexNumber, _detect_list) {
            _this.safelog('get_some_tasks_in_queue.detect_status_in_queue:', _detect_item);
            return _item_detect_CB.apply(_this, Array.prototype.slice.call(arguments));
        });
        _this.safelog('get_some_tasks_in_queue.not_done_list:', not_done_list);
        return not_done_list;
    };

    speed_detection.is_queue_all_done = function () {
        var _this = this;

        var is_queue_all_done = false;
        var not_done_list = _this.get_some_tasks_in_queue(function (_item, _indexNumber, _list) {
            if (_item.queue.is_done !== 1) {
                return _item;
            }
            return false;
        });
        if (not_done_list.length == 0) {
            is_queue_all_done = true;
        }
        _this.safelog('is_queue_all_done.not_done_list:', not_done_list);
        return is_queue_all_done;
    }
    speed_detection.detect_list_test_via_load_resources = function () {
        var _this = this;
        var $deferr = $.Deferred();
        if (_this.options.detect_list.length > 0) {
            _this.options.detect_list.map(function (_detect_item, indexNumber, _detect_list) {

                _this.loadResource(_detect_item).always(function (queue_result) {
                    _this.safelog('is_queue_all_done:', _this.is_queue_all_done());
                    if (_this.is_queue_all_done()) {
                        // if (is_queue_all_done) {
                        var deferr_return = {}
                        deferr_return.boolean = true; // only did ajax, not all uris has spent time.
                        deferr_return.msg = 'detect_list is completed.(via load resources)';
                        deferr_return.details = _this.collect_results_by_detect_list_via_load_resources();
                        _this.report_to_log(deferr_return.details);
                        $deferr.resolve(deferr_return);
                    }
                });
            });
        }

        return $deferr.promise();
    }
    speed_detection.collect_results_by_detect_list_via_load_resources = function () {
        var _this = this;
        var collected_list = _this.options.detect_list.map(function (_detect_item, indexNumber, _detect_list) {
            var _queue = _detect_item.queue;
            _this.safelog('399._detect_item', _detect_item);
            var collected_item = {};
            collected_item.detect_uri = _detect_item.detect_uri;
            collected_item.go_href = _detect_item.go_href;
            collected_item.label = _detect_item.label;
            // collected_item.speedBps = _detect_item._ajax.speedBps;
            collected_item.latency = _queue.latency;
            collected_item.start_time = _queue.start;
            collected_item.result_msg = _queue.msg;
            if (typeof (_queue.event) !== 'undefined') {
                collected_item.event = _queue.event;
            }
            return collected_item;
        });
        return collected_list;
    }


    // https://stackoverflow.com/a/736970
    speed_detection.getLocation = function (href) {
        var l = document.createElement("a");
        l.href = href;
        return l;
    };

    speed_detection.strpos = function (haystack, needle, offset) {
        var i = (haystack+'').indexOf(needle, (offset || 0));
        return i === -1 ? false : i;
    }

    speed_detection.report_to_log = function (_results) {
        var _this = this;

        ///  collect info into _data
        var _data = {};
        _data['user_agent'] = window.navigator.userAgent;
        if (typeof (_export_sbe_t1t) !== 'undefined') {
            if (!$.isEmptyObject(_export_sbe_t1t.variables.playerId)) {
                _data['player_id'] = _export_sbe_t1t.variables.playerId;
            }
        }
        _this.safelog('report_to_log._results:', _results);


        // report to results by each option.
        _results.map(function (_result, indexNumber, _result_list) {
            var _data_by_result = {};
            _data_by_result['']
            if (_result.latency === null) {
                _data_by_result.spent_ms = 'NULL'; // the case means timeout
            } else {
                _data_by_result.spent_ms = _result.latency;
            }

            // _data_by_result.start_time = _result.start_time;
            var _detect_uri = _this.getLocation(_result.detect_uri);
            _data_by_result.domain = _detect_uri.hostname;
            _data_by_result = $.extend(true, {}, _data_by_result, _data);
            var curr_timestamp = new Date().getTime();
            var _uri = _this.options.report_log_uri;
            if( _this.strpos(_uri,'${timestamp}') !== false){
                _uri = _uri.replace(/\$\{timestamp\}/g, curr_timestamp);
            }else{
                _uri =  _uri + '/' + curr_timestamp;
            }
            _this.safelog('report_to_log._uri:', _uri);
            var _ajax = $.ajax(_uri, {
                method: 'GET',
                cache: false,
                data: _data_by_result,
                crossDomain: true,
                dataType: 'jsonp',
                beforeSend: function (jqXHR, settings) {
                    // @todo display loading UI
                }
            }).always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
                // @todo hidden loading UI
            }).done(function (data, textStatus, jqXHR) {
                _this.safelog('report_to_log.done:', arguments);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                _this.safelog('report_to_log.fail:', arguments);
            });
        });

    };

    speed_detection.getOptionHtmlAfterAssignValues = function (_uri, _value, _label, _spent) {
        var _this = this;
        if (typeof (_spent) === 'undefined') {
            _spent = null;
        }
        // _this.safelog('getOptionHtmlAfterAssignValues.arguments:', arguments);
        // var option_tpl_dom = $('<temp>').append($.parseHTML(_this.templates.option_tpl));
        // var optionHtml = option_tpl_dom.html();
        var optionHtml = _this.templates.option_tpl;
        optionHtml = optionHtml.replace(/\$\{uri\}/g, _uri);
        optionHtml = optionHtml.replace(/\$\{value\}/g, _value);
        optionHtml = optionHtml.replace(/\$\{label\}/g, _label);
        var latency_info = '';
        if (_spent !== null) {
            latency_info += _spent;
            latency_info += _this.langs.lang_millisecond_abr;
        }
        optionHtml = optionHtml.replace(/\$\{spent\}/g, latency_info);
        // console.error('[error]getOptionHtmlAfterAssignValues.optionHtml:', optionHtml);
        // _this.safelog('getOptionHtmlAfterAssignValues.optionHtml:', optionHtml);
        return optionHtml;
    }

    speed_detection.is_kylefox_modal = function () {
        var _this = this;
        var returnBool = false;
        if (typeof ($.modal) !== 'undefined' && 'defaults' in $.modal) {
            if ('blockerClass' in $.modal.defaults) {
                returnBool = true;
            }
        }
        return returnBool;
    }
    // $.fn.speed_detection = function (el, options) {
    //     this.options = $.extend({}, $.modal.defaults, options);
    // };

    speed_detection.detect_console_log = function () {
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
    speed_detection.parse_query_string = function (query) {
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

    speed_detection.safelog = function (msg) {
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


    // Cloned from includes/js/triggers.js in www-site.
    /*********************************************************************
    *Setting Cookie
    *********************************************************************
    * setCookie
    *********************************************************************
    * @param {String} key Cookie name.
    * i.e : '_lang'
    * ********************************************************************
    * @param {String} val Cookie value.
    * i.e : 'en' , 'cn' or 'id'
    *********************************************************************/
    speed_detection.setCookie = function (key, val) {
        var mainDomain = window.location.hostname.replace('www', '');
        var date = new Date();
        date.setTime(date.getTime() + (0.5 * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
        var cookie = document.cookie = key + "=" + val + expires + ";domain="+ mainDomain +";path=/";
    };

    // Cloned from includes/js/triggers.js in www-site.
    /*********************************************************************
    *Getting Cookie
    *********************************************************************
    * getCookie
    *********************************************************************
    * @param {String} key Cookie name.
    * i.e : '_lang'
    *********************************************************************/
    speed_detection.getCookie = function (data) {
        var data_set = data + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(data_set) == 0) return c.substring(data_set.length,c.length);
        }
        return "";
    }

    speed_detection.apply_langs_title = function (theTitle) {
        var _this = this;
        if (typeof (theTitle) === 'undefined') {
            theTitle = _this.langs.speed_detection;
        }

        var headerHtml = _this.speed_detection_container$El.find('.modal-header').html();
        _this.safelog('apply_langs_title.headerHtml:', headerHtml, 'theTitle:', theTitle);
        _this.safelog('apply_langs_title.speed_detection_containerEl:', _this.speed_detection_container$El.find('.modal-header'), 'length:', _this.speed_detection_container$El.find('.modal-header').length);
        headerHtml = headerHtml.replace(/\$\{speed_detection\}/g, theTitle);
        _this.speed_detection_container$El.find('.modal-header').html(headerHtml);
    }


    speed_detection.apply_langs_buttons = function () {
        var _this = this;

        var footerHtml = _this.speed_detection_container$El.find('.modal-footer').html();
        footerHtml = footerHtml.replace(/\$\{translate_by_dict\}/g, _this.langs.translate_by_dict);
        footerHtml = footerHtml.replace(/\$\{directily_test\}/g, _this.langs.directily_test);
        footerHtml = footerHtml.replace(/\$\{lang_refresh\}/g, _this.langs.lang_refresh);
        footerHtml = footerHtml.replace(/\$\{lang_go\}/g, _this.langs.lang_go);
        footerHtml = footerHtml.replace(/\$\{lang_close\}/g, _this.langs.lang_close);
        _this.speed_detection_container$El.find('.modal-footer').html(footerHtml);

    }

    speed_detection.reload_langs = function (_langs) {
        var _this = this;
        _this.safelog('[speed_detection] reload_langs', _langs);
        // preload langs
        if (typeof (_export_sbe_t1t.speed_detection) !== 'undefined') {
            var _langText = _export_sbe_t1t.speed_detection.getLangText();
            if (!$.isEmptyObject(_langText)) {
                _this.langs = $.extend(true, {}, _this.defaults.langs, _langText);
            }
        }
        // replaced by _langs
        _this.langs = $.extend(true, {}, _this.defaults.langs, _langs);

    };

    speed_detection.initialize = function (_options) {
        var _this = this;
        _this.detect_console_log();

        _this.options = $.extend(true, {}, _this.defaults, _options.options);


        _this.safelog('[speed_detection] _export_sbe_t1t', _export_sbe_t1t);
        if (typeof (_export_sbe_t1t) !== 'undefined') {
            if (!$.isEmptyObject(_export_sbe_t1t.speed_detection.sbe_initializ_deferr)) {
                try {
                    _export_sbe_t1t.speed_detection.sbe_initializ_deferr.done(function (deferr_return) {
                        _this.reload_langs(deferr_return._langText);
                    });
                } catch (e){
                    _this.safelog('[speed_detection] Hook sbe_initializ_deferr.done() Failed', e.message);
                } finally {

                }

            }
        }

        // _this.safelog('[speed_detection] !isEmptyObject smartbackend', smartbackend);
        // if (!$.isEmptyObject(smartbackend)) {
        //     _this.safelog('[speed_detection] !isEmptyObject smartbackend');
        //     smartbackend.on('init.t1t.smartbackend', function () {
        //         // preload langs
        //         _this.safelog('[speed_detection] !isEmptyObject smartbackend will reload_langs');
        //         _this.reload_langs(_options.langs);
        //     });
        // }





        if ($.isEmptyObject(_this.templates.popup_tpl)
            && $.isEmptyObject(_this.templates.select_tpl)
            && $.isEmptyObject(_this.templates.option_tpl)
        ) {
            _this.loaded_templates.load_templates = _this.load_templates();
            _this.loaded_templates.load_templates.done(function (data, textStatus, jqXHR) {
                _this.safelog('[speed_detection]', 'load_templates done.');
                _this.referr_list.detect_list_test_via_load_resources = _this.detect_list_test_via_load_resources();
                _this.referr_list.detect_list_test_via_load_resources.done(function (_deferr_return) { // resolve, always be resolve()
                    _this.safelog('[speed_detection]', 'detect_list_test_via_load_resources done.');
                });
            });
        }



        // _this.safelog('619.onReady.templates:', JSON.stringify(_this.templates));



        // _this $.extend(true, {}, _this.defaults, options);


        $(document).ready(function () {
            if (!_this.has_loaded_templates()) {
                _this.onReady();
            }
        });

        return _this;
    }


    speed_detection.hookEvents = function (theSelectorStr) {
        var _this = this;
        // $('body').on('click', theSelectorStr, function (e) {
        // });

        $('body').on($.modal.BEFORE_OPEN, '.speed_detection_popup', function (event, modal) {
            _this.before_open_speed_detection_popup(event, modal);
        });
        $('body').on($.modal.OPEN, '.speed_detection_popup', function (event, modal) {
            _this.opened_speed_detection_popup(event, modal);
        });

        $('body').on($.modal.BEFORE_CLOSE, '.speed_detection_popup', function (event, modal) {
            _this.before_close_speed_detection_popup(event, modal);
        });
        $('body').on($.modal.AFTER_CLOSE, '.speed_detection_popup', function (event, modal) {
            _this.after_closed_speed_detection_popup(event, modal);
        });


        $('body').on('click', '.detected_item_go', function (e) {
            _this.clicked_detected_item_go(e);
        });
        $('body').on('click', '.detected_item_refresh', function (e) {
            _this.clicked_detected_item_refresh(e);
        });
        $('body').on('click', '.detected_item_close', function (e) {
            _this.clicked_detected_item_close(e);
        });
    }
    speed_detection.before_open_speed_detection_popup = function (e) {
        var _this = this;
        _this.safelog('before_open_speed_detection_popup.arg', arguments);

    }
    speed_detection.opened_speed_detection_popup = function (e) {
        var _this = this;
        _this.safelog('opened_speed_detection_popup.arg', arguments);



    }
    speed_detection.before_close_speed_detection_popup = function (e) {
        var _this = this;
        _this.safelog('before_close_speed_detection_popup.arg', arguments);

    }
    speed_detection.after_closed_speed_detection_popup = function (e) {
        var _this = this;
        _this.safelog('after_closed_speed_detection_popup.arg', arguments);

        $(_this.speed_detection_popup_selector).remove();


    }
    speed_detection.do_detected_item_go = function (uri) {
        var _this = this;
        _this.safelog('do_detected_item_go.uri', uri);
        location.href = uri; // @todo TODO: remove it, before push
    }

    speed_detection.clicked_detected_item_go = function (e) {
        var _this = this;

        _this.safelog('clicked_detected_item_go.arg', arguments);
        var _data = $(_this.speed_detection_popup_selector).find('select[name="detected_uri"] option:selected').data();
        if (!$.isEmptyObject(_data.uri)) {
            _this.do_detected_item_go(_data.uri);
        }
    }

    speed_detection.clicked_detected_item_refresh = function (e) {
        var _this = this;
        _this.safelog('clicked_detected_item_refresh.arg', arguments);
        _this.popup('rebuild_option').done(function (_deferr_return) {
            _this.safelog('clicked_detected_item_refresh._deferr_return', _deferr_return);
        });
    }

    speed_detection.clicked_detected_item_close = function (e) {
        var _this = this;
        _this.safelog('clicked_detected_item_close.arg', arguments);
        _this.popup('close');
    }

    speed_detection.onReady = function (options) {
        var _this = this;

        // _this.referr_list.detect_list_test_via_load_resources.done(function (_deferr_return) { // resolve, always be resolve()
        //
        // });

        // _this.build_popup(function () { // doneCB
        //     if (_this.is_kylefox_modal()) {
        //         // 要放在 load_templates() 回來後，才出現彈窗
        //         // var tempDom = $('<temp>').append($.parseHTML(speed_detection.templates.popup_tpl));
        //         // $('.speed_detection_popup', tempDom).modal();
        //         // $('.speed_detection_popup', _this.popupDom).modal(); // will trigger speed_detection::before_open_speed_detection_popup()
        //     } else {
        //         // @todo TODO: Not kylefox modal handle
        //     }
        // });



        // _this.apply_langs_title('TEST123');


        _this.hookEvents(_this.hook_element_selector);
    };

    speed_detection.popup = function (doType) {
        var _this = this;
        switch (doType) {
            case 'open':
                return _this.do_popup_open();
                break;
            case 'close':
                return _this.do_popup_close();
                break;
            case 'rebuild_option':
                return _this.do_rebuild_popup();
                break;
        }

    }

    speed_detection.do_popup_open = function () {
        var _this = this;

        _this.build_popup(function () { // doneCB
            if (_this.is_kylefox_modal()) {
                // 要放在 load_templates() 回來後，才出現彈窗
                $('.speed_detection_popup').modal(); // will trigger speed_detection::before_open_speed_detection_popup()
            } else {
                // @todo TODO: Not kylefox modal handle
            }
        });
        return _this;
    }
    speed_detection.do_popup_close = function () {
        var _this = this;

        if (_this.speed_detection_popup$El.find('.close-modal').length > 0) {
            _this.speed_detection_popup$El.find('.close-modal').trigger('click');
        } else {
            $.modal(_this.speed_detection_popup$El, 'close');
            /// or close all
            // $.modal.close();
        }


    }


    $.fn.speed_detection_initial = function (options) {
        var _speed_detection = speed_detection.initialize(options);
        return _speed_detection;
    }

    $.fn.speed_detection_open = function (_speed_detection) {
        return _speed_detection.popup('open'); // will call do_popup_open()

        // @todo TODO: _this.popupDom 在網頁上的位置，跟 CSS 考量
        // 更新下拉選單到該位置
        // 註冊彈窗上的按鈕行為
        // 多國語言
        // iframe 在 src開始，到 onload 的時間。公開函式測速用，應該要回傳給主機數據。
        // sd.build_popup(function () { // doneCB
        //     if (sd.is_kylefox_modal()) {
        //         // 要放在 ajax 回來時才出現彈窗
        //         // var tempDom = $('<temp>').append($.parseHTML(speed_detection.templates.popup_tpl));
        //         // $('.speed_detection_popup', tempDom).modal();
        //         $('.speed_detection_popup', sd.popupDom).modal();
        //
        //     } else {
        //         // Not kylefox modal handle
        //     }
        // });
        //        _this.safelog('speed_detection', speed_detection);


    };
    $.fn.speed_detection_close = function (_speed_detection) {
        return _speed_detection.popup('close'); // will call do_popup_close()
    };

    $.fn.speed_detection_rebuild_popup = function (_speed_detection) {
        // sd.do_rebuild_popup();
        return _speed_detection.popup('rebuild_popup'); // will call do_popup_close()
    }

     window.Speed_Detection = $.fn.Speed_Detection = speed_detection;

}));