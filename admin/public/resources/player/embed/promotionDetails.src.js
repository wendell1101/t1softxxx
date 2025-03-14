var promotionDetails = window.promotionDetails = (function(){

    /**
     * for www-designer,
     * Recommend include the js file with URI, "//player.og.local/resources/player/built_in/embed.promotionDetails.min.js".
     *
     * 1. To install ,
     * <script type="text/javascript" src="//player.og.local/resources/player/embed/promotionDetails.src.js"></script>
     * <script type="text/javascript">
     * $(document).ready(function() { // The document on ready script
     *     promotionDetails.onReady();
     *  }); // EOF $(document).ready(function() {...
     *  </script>
     *
     * 2. ADD iframe tag into #promotionDetails element.
     * like as the dom structure,
     * <div id="promotionDetails">
     *    ...
     *     <div class="modal-body">
     *          <div class="loading">
     *              <div class="loadingspinner"></div>
     *          </div>
     *          <iframe width="100%" height="100%" frameborder="0" allowtransparency="true"></iframe> <!-- added iframe -->
     *     <div>
     *    ...
     * </div>
     *
     * 3. Update "Klaim Bonus" Button,
     * <a data-toggle="modal" data-target="#promotionDetails" href="//player.staging.ole777idr.t1t.in/player_center2/promotion/addtopromo/3uxamziv" class="player-promo btnShadow">Klaim Bonus1</a>
     *
     * for RD,
     * Change the code need execute CMD,"./create_links.sh" for generate minify js file.
     * If need to skip "./create_links.sh", recommend include the js file with URI, "//player.og.local/resources/player/embed/promotionDetails.src.js".
     *
     */

    var promotionDetails = promotionDetails || {};
    promotionDetails.selectorList = [];
    promotionDetails.selectorList.modal = '#promotionDetails';
    promotionDetails.selectorList.loading4iframe = promotionDetails.selectorList.modal+ ' .modal-body .loading';
    promotionDetails.selectorList.iframe = promotionDetails.selectorList.modal+ ' iframe';
    promotionDetails.debugLog = true;
    /**
     * initial iframe for display loading div.
     */
    promotionDetails.iframeInitial = function(){
        var self = this;
        $(self.selectorList.iframe).addClass('d-none') // hide
                                    .removeAttr('src');
        $(self.selectorList.loading4iframe).removeClass('d-none'); // show

    };
    /**
     * To load URL into the iframe
     * @param string uri The URL for iframe embeded.
     */
    promotionDetails.iframeLoad = function(uri){
        var self = this;
        self.iframeInitial();
        self.iframeSetSrc(uri);
    };
    /**
     * To setup iframe.src for embed promo to join.
     * @param string uri The embed URL.
     */
    promotionDetails.iframeSetSrc = function(uri){
        var self = this;

        // detect dbg param for safelog
        var query = window.location.search.substring(1);
        var qs = self.parse_query_string(query);
self.safelog('in iframeSetSrc.qs', qs);
        if( 'dbg' in qs
			&& typeof(qs.dbg) !== 'undefined'
			&& qs.dbg
		){
            uri = self.addOrReplaceParam(uri, 'dbg', qs.dbg);
        }


        $(self.selectorList.iframe).prop('src', uri);
    };
    promotionDetails.iframeScrollTop = function(scrollTopPx){
        var self = this;
        $(self.selectorList.modal).find('.modal-body').scrollTop(scrollTopPx);
    };
    /**
     * The script for iframe adjust height.
     * @param integer|float oHeight The height int, unit: px.
     */
    promotionDetails.iframeRecomandHeight = function(oHeight){
        var self = this;
        // fit
        $(self.selectorList.iframe).css({'height':oHeight});
        $(self.selectorList.modal).modal('handleUpdate');
    };
    /**
        * The script for iframe onReady
        */
    promotionDetails.iframeOnReady = function(){
        var self = this;
        $(self.selectorList.loading4iframe).addClass('d-none'); // hide
        $(self.selectorList.iframe).removeClass('d-none'); // show

        self.postEmbedeeOnReadyMessage();
    };

    /**
        * The element has href attribute?
        * @param {jquery(selector)} the$El
        * @return {boolean} If true means has the attr,"href" else not.
        */
    promotionDetails.hasHref = function(the$El){
        return ! $.isEmptyObject( the$El.attr('href'));
    };

    /**
        * The script for document onReady.
        */
    promotionDetails.onReady = function(){
        var self = this;

        // detect dbg=1 in get params for self.safelog output.
		var query = window.location.search.substring(1);
		var qs = self.parse_query_string(query);
		if( 'dbg' in qs
			&& typeof(qs.dbg) !== 'undefined'
			&& qs.dbg
		){
			self.debugLog = true;
		}else{
			self.debugLog = false;
        }

        self.hookEvents();
    };

    /**
    * The events registion script
    */
    promotionDetails.hookEvents = function(){
        var self = this;

        // handle getMessage/postMessage via message event
        self.getMessage = function (e) {
self.safelog('promotionDetails.getMessage', arguments);
            if(e.data.event_id == 'cors_rpc') {
                if( typeof(self[e.data.func]) === 'function' ){
                    self[e.data.func].apply(self, e.data.params);
                }
            }
        };
        window.addEventListener("message", self.getMessage, false);

        /**
        * The script for modal events handle
        */
        $('body')
            // .on('shown.bs.modal', '[data-target="#promotionDetails"][href]', function(e) {
            .on('shown.bs.modal', self.selectorList.modal, function(e) { // after show
                // do something...
            })
            // .on('show.bs.modal', '[data-target="#promotionDetails"][href]', function(e) {
            .on('show.bs.modal', self.selectorList.modal, function(e) { // before show
                // do something...

                var isLogin = self.isLogged();
                /// isLogged
                // // detect no-logined will redirect to login.
                // if( typeof(_export_smartbackend) !== 'undefined' ){
                //     if( _export_smartbackend.variables.logged == false ){
                //         // http://player.og.local/iframe/auth/login
                //         var loginUri =  window.location.protocol + '//' + _export_smartbackend.variables.hosts.player + '/iframe/auth/login';
                //         window.location.href = loginUri;
                //     }
                // }
                ///
                // if( typeof(t1t_player) !== 'undefined'){
                //     if( ! t1t_player.isLogged() ){
                //         t1t_player.logout();
                //     }
                // }
self.safelog('in show.bs.modal.isLogin', isLogin);
                if(isLogin){
                    var triggee$El =  $(e.relatedTarget);
                    if( self.hasHref(triggee$El) ){
                        var herf = triggee$El.attr('href');
                        self.iframeLoad(herf);
                    }
                }else{
                    /// http://player.og.local/iframe/auth/login // player_center2
                    var loginUri =  window.location.protocol + '//' + _export_smartbackend.variables.hosts.player + '/player_center2/'; // will redirect to login while no login.
                    window.location.href = loginUri;
                }

            });
    };// EOF promotionDetails.hookEvents

    /**
     * Post Message OnReady to the embedded iframe.
     */
    promotionDetails.postEmbedeeOnReadyMessage = function(){
        var self = this;
        $(self.selectorList.iframe).get(0).contentWindow.postMessage(
            {
                event_id: 'cors_rpc', // rpc=remote procedure call
                func: 'embedeeOnReady',
                params: []
            } ,
            "*" // or "www.parentpage.com"
        );
    };// EOF postEmbedeeOnReadyMessage


    /**
     * Close the modal.
     */
    promotionDetails.toCloseIframe = function(){
        var self = this;
        $(self.selectorList.modal).modal('hide');
    };

    /**
     * Check is login
     */
    promotionDetails.isLogged = function(){
        var returnIsLogged = false;
        var loginAreaID = '_player_login_area';
        // detect no-logined will redirect to login.
        if( typeof(_export_smartbackend) !== 'undefined' ){
            returnIsLogged = _export_smartbackend.variables.logged;
        }else{
            // detect dom
            if( $('#'+ loginAreaID+ ' input[name="login"]:visible').length > 0){
                returnIsLogged = false;
            }else{
                returnIsLogged = true;
            }
        }
        return returnIsLogged;
    };

    /**
     * Add a URL parameter (or modify if already exists)
     * Ref. to https://stackoverflow.com/a/14469938
     *
	 * @param {string} url
	 * @param {string} param the key to set
	 * @param {string} value
     *
	 */
	promotionDetails.addOrReplaceParam = function(url, param, value) {
		param = encodeURIComponent(param);
		var r = "([&?]|&amp;)" + param + "\\b(?:=(?:[^&#]*))*";
		var a = document.createElement('a');
		var regex = new RegExp(r);
		var str = param + (value ? "=" + encodeURIComponent(value) : "");
		a.href = url;
		var q = a.search.replace(regex, "$1"+str);
		if (q === a.search) {
		a.search += (a.search ? "&" : "") + str;
		} else {
		a.search = q;
		}
		return a.href;
    };

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
    promotionDetails.parse_query_string = function(query) {
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
    promotionDetails.safelog = function(msg){
		var self = this;
		if( typeof(safelog) !== 'undefined'){
			safelog(msg); // for applied
		}else{
			//check exists console
			if(	self.debugLog
				&& typeof(console)!=='undefined'
			){
				console.log.apply(console, Array.prototype.slice.call(arguments));
			}
		}
    }; // EOF safelog

    return promotionDetails;
})();