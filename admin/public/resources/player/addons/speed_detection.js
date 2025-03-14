(function () {
    // Same as $(document).ready()

    function T1T_SpeedDetection() {
        var self = this;
        self.name = 'speed_detection';

        // this.player_auto_lock = null;

        self.line_detection_class = 'ping__btn__wrapper';
        self.script_domain = window.location.hostname;
        self.tpl_uri = '//' + self.script_domain + '/resources/player/built_in/embed.speed_detection.templates.html';
        self.detect_list = [];
        self.sbe_initializ_deferr = $.Deferred();
        self.sbe_initializ_promise = self.sbe_initializ_deferr.promise();
        self.fn_speed_detection = null;
    }

    // init.t1t.smartbackend


    T1T_SpeedDetection.prototype.addJS = function(url, force_load, callback){
        var self = this;
        var embedded_url = url + ((force_load) ? ((url.indexOf('?') >= 0) ? '&v=' : '?v=') + ('' + Math.random()).substr(2, 16) : '');
        var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
        g.type = 'text/javascript';
        g.async = true;
        g.defer = true;
        g.src = embedded_url;
        g.onload = function(){
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof callback === "function") callback.apply(self, cloned_arguments);
        };
        s.parentNode.insertBefore(g, s);

        return g;
    };

    /**
     * https://stackoverflow.com/a/13556622
     *
     * @param {string} href
     */
    T1T_SpeedDetection.prototype.loadCSS = function(href) {

        var cssLink = $("<link>");
        $("head").append(cssLink); //IE hack: append before setting href

        cssLink.attr({
          rel:  "stylesheet",
          type: "text/css",
          href: href
        });

    };


    // $.ajax({
    //     url: href,
    //     dataType: 'text',
    //     success: function(data) {
    //         $('<style type="text/css">\n' + data + '</style>').appendTo("head");
    //     }
    // });

    /**
     * https://stackoverflow.com/a/57508727
     * @param {array} urls
     * @param {CallableFunction} successCallback
     * @param {CallableFunction} failureCallback
     */
    T1T_SpeedDetection.prototype.loadJavascriptAndCssFiles = function (urls, successCallback, failureCallback) {
        var self = this;
        $.when.apply($,
            $.map(urls, function(url) {
                if(self.fname(url).endsWith(".css")) {
                    var defer = $.Deferred();
                    self.loadCSS(url);

                    defer.resolve.apply(self, [url]);
                    return defer.promise();

                    // return $.get(url, function(css) {
                    //     $("<style>" + css + "</style>").appendTo("head");
                    // });
                } else {
                    return $.getScript(url);
                }
            })
        ).then(function() {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if (typeof successCallback === 'function') successCallback.apply(self, cloned_arguments);
        }).fail(function() {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if (typeof failureCallback === 'function') failureCallback.apply(self, cloned_arguments);
        });

    };

    /**
     * js function to get filename from url
     * https://stackoverflow.com/a/27800498
     * @param {string} url
     * @returns
     */
    T1T_SpeedDetection.prototype.fname = function(url)
    {
        return url?url.split('/').pop().split('#').shift().split('?').shift():null
    };


    T1T_SpeedDetection.prototype.getLangText = function () {
        return variables._langText;
    };


    T1T_SpeedDetection.prototype.is_exist_line_detection = function () {
        var self = this;
        var has_line_detection = false;
        if( $( '.' + self.line_detection_class ).length > 0 ){
            has_line_detection = true;
        }
        return has_line_detection;
    };

    T1T_SpeedDetection.prototype.handle_queue_result = function(queue_result) {
        /**
         *  Response, "queue_result" Noted,
         *  - msg: "onload" Or "onerror"
         *  - latency: integer Or null
         *  - is_done should be 1, after testing.
         *  - start : integer timestamp (ms)
         *  - end : integer timestamp (ms), under msg="onload", else undefined.
         *  - label: The label of option, ex:"www.smashup05.com".
         *  - go_href: The Go's URI, ex: "https://m.smashup05.com/"
         */
        console.log('directily_test.loadResource.arguments:', arguments);
        console.log('directily_test.loadResource.spent:', ((queue_result.latency === null) ? 'timeout' : queue_result.latency + 'ms')
            , 'label:', queue_result.label);
    }

    /**
     *
    <div class="ping__btn__wrapper"
        data-report_uri="//player.brl.staging.smash.t1t.in/pub/speed_detect_report"
        data-tpl_uri="/resources/player/built_in/embed.speed_detection.min.js"
        data-detect_list='[{"label":"www.smashup03.com","detect_uri":"https://www.smashup03.com/includes/images/logo.png","go_href":"https://m.smashup03.com/","_ajax":null},{"label":"www.smashup04.com","detect_uri":"https://www.smashup04.com/includes/images/logo.png","go_href":"https://m.smashup04.com/","_ajax":null},{"label":"www.smashup04-no-exist.com","detect_uri":"https://www.smashup04.com/includes/images/logo-no-exist.png","go_href":"https://m.smashup031.com/","_ajax":null}]'
        data-script_domain="" >
            <a id="close__ping__popup" href="#!">Line Detection</a>
        </div>
     */
    T1T_SpeedDetection.prototype.setup_line_detection = function () {
        var self = this;
        var lineDetection$El = $( '.' + self.line_detection_class );
        if(typeof( lineDetection$El.data('script_domain') ) !== 'undefined' ){
            if(lineDetection$El.data('script_domain') !== ''){
                self.script_domain = lineDetection$El.data('script_domain');
            }
        }
        if(typeof( lineDetection$El.data('tpl_uri') ) !== 'undefined' ){
            if(lineDetection$El.data('tpl_uri') !== ''){
                self.tpl_uri = lineDetection$El.data('tpl_uri');
            }
        }
        if(typeof( lineDetection$El.data('detect_list') ) !== 'undefined' ){
            if( Array.isArray( lineDetection$El.data('detect_list') ) ){
                self.detect_list = lineDetection$El.data('detect_list');
            }
        }

        self.report_uri = variables.report_uri;
        if(typeof( lineDetection$El.data('report_uri') ) !== 'undefined' ){
            if( lineDetection$El.data('report_uri') !== ''){
                self.report_uri = lineDetection$El.data('report_uri');
            }
        }

        var scripts = [ "//" + self.script_domain + "/resources/player/built_in/embed.speed_detection.min.js"
                        , utils.getPlayerCmsUrl('/resources/third_party/jquery-modal/0.9.1/jquery.modal.min.js')
                        , utils.getPlayerCmsUrl('/resources/third_party/jquery-modal/0.9.1/jquery.modal.min.css')
                    ];
        self.loadJavascriptAndCssFiles(scripts, function(){ // successCallback
            // console.log('setup_line_detection.loadJavascriptAndCssFiles.178.successCallback.arguments:', arguments);
            self.active_script(Speed_Detection);
        }, function(){ // failureCallback
            // console.log('setup_line_detection.loadJavascriptAndCssFiles.180.failureCallback.arguments:', arguments);
        });
    };




    T1T_SpeedDetection.prototype.active_script = function(_fn_speed_detection){
        var self = this;
        var _options = {};
        _options.options = {};
        _options.options.report_log_uri = self.report_uri; // variables.report_uri;
        _options.options.templates_uri = self.tpl_uri;
        _options.options.detect_list = self.detect_list;
        _options.langs = {}; // by defaults of langs

        self.fn_speed_detection = _fn_speed_detection.initialize(_options);

        $('body').on('click', '.'+ self.line_detection_class, function (e) {
            // $.fn.speed_detection_open(self.fn_speed_detection);
            return self.fn_speed_detection.popup('open'); // will call do_popup_open()
        });

        $('body').on('click', '.directily_test', function (e) {
            /**
             *  directily test Usage,
             */
            var _detect_item = {
                label: 'www.smashup05.com'
                , detect_uri: 'https://www.smashup05.com/includes/images/logo.png' // 跨網域錯誤
                , go_href: 'https://m.smashup05.com/'
                , _ajax: null
            };
            self.fn_speed_detection.loadResource(_detect_item).always(function (queue_result) {
                self.handle_queue_result(queue_result);
            });

            var _detect_item2 = {
                label: 'www.smashup03-no-exist.com'
                , detect_uri: 'https://www.smashup03-no-exist.com/includes/images/logo.png' // 跨網域錯誤
                , go_href: 'https://m.smashup03-no-exist.com/'
                , _ajax: null
            };
            self.fn_speed_detection.loadResource(_detect_item2).always(function (queue_result) {
                self.handle_queue_result(queue_result);
            });
        }); // EOF $('body').on('click', '.directily_test', function (e) {...
    }

    var speed_detection = new T1T_SpeedDetection();

    smartbackend.addAddons(speed_detection.name, speed_detection);

    smartbackend.on('init.t1t.smartbackend', function () {
        if( speed_detection.is_exist_line_detection() ){
            speed_detection.setup_line_detection()
        }

        var deferr_return = {};
        deferr_return._langText = variables._langText;

        if('sbe_initializ_deferr' in speed_detection){
            speed_detection.sbe_initializ_deferr.resolve(deferr_return);
        }

    });

    // smartbackend.on('run.t1t.smartbackend', function(){
    //     // utils.safelog('run.t1t.smartbackend.200.arguments', arguments);
    //     // speed_detection.active_script();
    // });

    return speed_detection;
})();