    var AjaxQueue = AjaxQueue||{};
    AjaxQueue.init = function ( options ){
        var _this = this;
        _this.list = [];

        /// go_with_sync() used
        _this.alwaysCallback_in_each_atask = false;
        _this.deferr = false;
        _this.alwaysCallback_in_deferr = false;

        _this.langs = {};
        _this.langs.abort = '_suspend_';

        _this = $.extend(true, {}, _this, options );
        return _this;
    };
    AjaxQueue.init_atask = function(_ajax_options){
        var atask = {};
        atask.options = _ajax_options;
        atask.ajax = false;
        atask.response = '';
        atask.doneCallback = false;
        // .done(function( data, textStatus, jqXHR ) {});
        if(!!_ajax_options.doneCallback){
            atask.doneCallback = _ajax_options.doneCallback;
        }
        atask.failCallback = false;
        // .fail(function( jqXHR, textStatus, errorThrown )
        if(!!_ajax_options.failCallback){
            atask.failCallback = _ajax_options.failCallback;
        }

        atask.alwaysCallback = false;
        // .always(function( data|jqXHR, textStatus, jqXHR|errorThrown ) { })
        if(!!_ajax_options.alwaysCallback){
            atask.alwaysCallback = _ajax_options.alwaysCallback;
        }
        return atask;
    };
    AjaxQueue.push_task_in_list = function (_ajax_options){
        var _this = this;
        var atask = _this.init_atask(_ajax_options);
        _this.list.push(atask);
    };
    AjaxQueue.clear_list = function(){
        var _this = this;
        // abort all tasks in list
        _this.abort_all_atask_in_list();
        _this.list = [];
    };

    AjaxQueue.abort_all_atask_in_list = function(){
        var _this = this;
        if(_this.list.length > 0){
            $.each(_this.list, function(i, _atask) {
                if( _this.has_ajax(_atask) ){
                    _atask.ajax.abort(_this.langs.abort);
                }
            });
        }
    }
    AjaxQueue.go_with_sync = function(){ // one by one, shortcut of go_atask_in_list_with_sync()
        var _this = this;
        _this.go_atask_in_list_with_sync();
    };
    AjaxQueue.go_atask_in_list_with_sync = function(){ // one by one
        var _this = this;
        if( $.isEmptyObject(_this.deferr) ){
            _this.deferr = $.Deferred();
            _this.deferr.progress(function () { // delegate of _this.deferr.notify();
                var undo_count = 0;
                var atask_count = _this.list.length;
                if(atask_count > 0){
                    $.each(_this.list, function(i, _atask) {
                        if(!_this.has_ajax(_atask)){
                            undo_count++;
                        }
                    });
                }
                // console.log('undo_count:', undo_count, 'total', atask_count);
            });

            // for detect the task all done?
            _this.alwaysCallback_in_each_atask = function(_atask, always_arguments){
                var no_ajax_atask = _this.get_no_ajax_atask_in_list();
                // console.log('alwaysCallback_in_each_atask.528.no_ajax_atask', no_ajax_atask);
                // console.log( '_atask:', _atask, 'always_arguments:', always_arguments);
                /// always_arguments = (data_or_jqXHR, textStatus, jqXHR_or_errorThrown )
                _atask.ajax._textStatus = always_arguments[1]; // for quick confirm the result by dev.
                if( $.isEmptyObject(no_ajax_atask) ){
                    _this.deferr.resolve(); // all tasks had done in ajax
                }else{
                    _this.deferr.notify();
                    _this.go_atask_via_ajax(no_ajax_atask);
                }
            };

            if(!!_this.alwaysCallback_in_deferr){
                _this.deferr.always( function(){
                    var cloned_arguments = Array.prototype.slice.call(arguments);
                // console.log('_this.deferr.always.102', cloned_arguments);

                    _this.alwaysCallback_in_deferr.apply(null, cloned_arguments);
                });
            }
        } // EOF if( $.isEmptyObject(_this.deferr) ){...

        if(_this.list.length > 0){
            var deferr_state = _this.deferr.state();
            if( deferr_state != 'resolved'
                && deferr_state != 'rejected'
            ){
                var no_ajax_atask = _this.get_no_ajax_atask_in_list();
                if( ! $.isEmptyObject(no_ajax_atask) ){
                    _this.deferr.notify();
                    _this.go_atask_via_ajax(no_ajax_atask);
                }
            }else{
                // already started
                _this.deferr.notify();
            }
        }else{
            _this.deferr.notify();
            _this.deferr.resolve(); // empty atask in list
        }
        return _this.deferr.promise();
    } // EOF go_atask_in_list_with_sync()
    AjaxQueue.get_no_ajax_atask_in_list = function(){
        var _this = this;
        var atask_no_ajax = false;
        if(_this.list.length > 0){
            $.each(_this.list, function(i, _atask) {
                if(!_this.has_ajax(_atask)){
                    atask_no_ajax = _atask;
                    return false; // breaks
                }
            });
        }
        return atask_no_ajax;
    }
    AjaxQueue.has_ajax = function(atask){
        var has_ajax = false;
        if( ! $.isEmptyObject(atask.ajax) ){
            has_ajax = true;
        }
        return has_ajax;
    }

    AjaxQueue.go_with_async = function(){  // send ajax at the same time
        var _this = this;

        if(_this.list.length > 0){
            $.each(_this.list, function(i, _atask) {
                _this.go_atask_via_ajax(_atask);
            });
        }
    };
    AjaxQueue.go_atask_via_ajax = function(atask){
        var _this = this;
        if( ! $.isEmptyObject(atask.ajax) ){
            return atask.ajax;
        }
        atask.ajax = $.ajax(atask.options);
        atask.ajax.done(function(_data, _textStatus, _jqXHR){
            atask.response = _data;

            if( !!(atask.doneCallback) ){
                var cloned_arguments = Array.prototype.slice.call(arguments);
                atask.doneCallback.apply(null, cloned_arguments);
            }
        });
        atask.ajax.fail(function( jqXHR, textStatus, errorThrown ){
            if( !!(atask.failCallback) ){
                var cloned_arguments = Array.prototype.slice.call(arguments);
                atask.failCallback.apply(null, cloned_arguments);
            }
        });
        atask.ajax.always(function( data_jqXHR, textStatus, jqXHR_errorThrown ){
            var cloned_arguments = Array.prototype.slice.call(arguments);
// console.log('atask.ajax.always.623', _this.alwaysCallback_in_each_atask);
// console.log('atask.ajax.alwaysCallback.158', atask);

            if( !! (_this.alwaysCallback_in_each_atask) ){
                _this.alwaysCallback_in_each_atask.apply(_this, [ atask, cloned_arguments ]);
            }
            if( !!(atask.alwaysCallback) ){
                atask.alwaysCallback.apply(null, cloned_arguments);
            }
        });
        return atask.ajax;
    }