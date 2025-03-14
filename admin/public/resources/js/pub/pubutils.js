if(typeof _pubutils == "undefined") {
	_pubutils = {
		variables: {
			debugLog: true
		},
		safelog: function (msg) {
            //check exists console.log
            var _this = this;
            if( typeof(console)!=='undefined'
                && 'log' in console
                && _this.variables.debugLog
                // && !_.isUndefined(console)
                // && _.isFunction(console.log)
            ){
                console.log.apply(console, Array.prototype.slice.call(arguments));
            }
		},
		decodeHtmlEntities: function(html, default_lang='default') {
			var txt = document.createElement('textarea');
			txt.setAttribute("id", default_lang + "default_temp_area");
			txt.innerHTML = html;
			var decode_html = txt.value;
			txt = undefined;    //reset txt
			return decode_html;
		}
	};
}

_pubutils.addBookmark = function (e) {
	var bookmarkURL = window.location.href;
	var bookmarkTitle = document.title;

	if ('addToHomescreen' in window && window.addToHomescreen.isCompatible) {
		// Mobile browsers
		addToHomescreen({ autostart: false, startDelay: 0 }).show(true);
	} else if (window.sidebar && window.sidebar.addPanel) {
		// Firefox version < 23
		window.sidebar.addPanel(bookmarkTitle, bookmarkURL, '');
	} else if ((window.sidebar && /Firefox/i.test(navigator.userAgent)) || (window.opera && window.print)) {
		// Firefox version >= 23 and Opera Hotlist
		$(this).attr({
			href: bookmarkURL,
			title: bookmarkTitle,
			rel: 'sidebar'
		}).off(e);
		return true;
	} else if (window.external && ('AddFavorite' in window.external)) {
		// IE Favorite
		window.external.AddFavorite(bookmarkURL, bookmarkTitle);
	} else {
		// Other browsers (mainly WebKit - Chrome/Safari)
		alert('Press ' + (/Mac/i.test(navigator.userAgent) ? 'Cmd' : 'Ctrl') + '+D to bookmark this page.');
	}

	return false;
}

_pubutils.notifyErr = function (err) {

	var notify = $.notify({
		// options
		message: err
	}, {
		// settings
		type: 'danger',
		delay: 0,
		allow_dismiss: true,
		showProgressbar: false,
		mouse_over: 'pause'
	});

	return notify;

}

_pubutils.notifyLoading = function (loadingText) {

	var template = '<div data-notify="container" class="col-xs-11 col-sm-4 alert alert-{0}" role="alert"><button type="button" aria-hidden="true" class="close" data-notify="dismiss" >×</button><span data-notify="icon"></span> <span data-notify="title">{1}</span> <span data-notify="message">{2}</span><div class="progress" data-notify="progressbar"><div class="progress-bar progress-bar-{0} progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div></div><a href="{3}" target="{4}" data-notify="url"></a></div>';

	var notify = $.notify({
		// options
		message: loadingText
	}, {
		// settings
		type: 'warning',
		delay: 0,
		allow_dismiss: true,
		showProgressbar: true,
		mouse_over: 'pause',
		template: template
	});

	return notify;
}

_pubutils.notifySuccess = function (msg) {

	var notify = $.notify({
		// options
		message: msg
	}, {
		// settings
		type: 'success',
		delay: 20000,
		allow_dismiss: true,
		showProgressbar: false,
		mouse_over: 'pause'
	});

	return notify;

}

_pubutils.closeNotify = function (notifyObj) {
	if (notifyObj) {
		notifyObj.close();
	}
}

_pubutils.inArray = function (str, arr, use_indexOf = false) {
	for (var i = 0; i < arr.length; i++) {
		if (arr[i] == str) {
			return true;
		}
        if(use_indexOf){
            if( str.indexOf(arr[i]) !== -1){
                return true;
            }
        }
	}

	return false;
};

_pubutils.checkAndGoHttps = function (auto_redirect_to_https_list, donot_auto_redirect_to_https_list) {

	//black list
	if (_pubutils.inArray(window.location.hostname, donot_auto_redirect_to_https_list)) {
		return;
	}

	//white list
	//check https
	//only in domain list
	if (window.location.protocol == 'http:' &&
		_pubutils.inArray(window.location.hostname, auto_redirect_to_https_list)) {
		var url = window.location.href;
		url = 'https' + url.substr(4, url.length);
		//redirect to https
		window.location.href = url;
	}

}

_pubutils.lang = [];
_pubutils.ignoreShowURIList = [];
/**
 * Get the column index of dataTable by not_visible_payment_management
 *
 * @param array _not_visible_payment_management The elememt of the setting withdrawal_list_columnDefs.
 * its depend on "data-field_id" attribute of the <th> element in the dataTable() <table>.
 * @param string The id attribute of the dataTable() target element.
 * @return array _not_visible_cols The array for the option, targets of the dataTable() property, columnDefs.
 */
 _pubutils.getColumnIndexByFieldId4Datatable = function(_not_visible_payment_management, _datatableIdStr){
	var _this = this;
   if( typeof(_not_visible_payment_management) === 'undefined'){
	   _not_visible_payment_management = [];
   }
   if( typeof(_datatableIdStr) === 'undefined'){
	   _datatableIdStr = '';
	   _this.safelog('[getColumnIndexByFieldId4Datatable] The param, DatatableIdStr is Required !');
   }
   var indexThSelectorStr = 'table[id="'+ _datatableIdStr+ '"] tr th'; // dataTable() not yet initialed.
   var _not_visible_cols = [];
   if( ! $.isEmptyObject(_not_visible_payment_management) ) {
	   $.each(_not_visible_payment_management, function(indexNumber, currVal){
		   if(typeof(currVal) == 'number'){
				var currTh$El = $(indexThSelectorStr).eq(currVal);
				if(currTh$El.length > 0){
					_not_visible_cols.push(currVal);
				}else{
					_this.safelog('[getColumnIndexByFieldId4Datatable] Not found the index number,'+ currVal+ ' in the dataTable, #'+ _datatableIdStr+' .');
				}
		   }else if(typeof(currVal) == 'string'){
			   var thSelectorStr = 'th[data-field_id="'+ currVal+ '"]';
			   var currIndex = $(thSelectorStr).index(indexThSelectorStr);
			   if(currIndex != -1){
				   _not_visible_cols.push(currIndex);
			   }else{
				   _this.safelog('[getColumnIndexByFieldId4Datatable] Not found the index of field_id,'+ currVal+ ' in the dataTable, #'+ _datatableIdStr+' .');
				   // console.error('[getColumnIndexByFieldId4Datatable] $(\''+ thSelectorStr+ '\').index(\''+indexThSelectorStr+'\')');
			   }
		   }
	   });
   } // EOF if( ! $.isEmptyObject(_not_visible_payment_management) ) {...
   return _not_visible_cols;
} // EOF getColumnIndexByFieldId4Datatable

_pubutils._init_lock_page = function () {
	if ($('#_lock_screen').length <= 0) {
		$('body').append('<div style="display: none" id="_lock_screen"></div>');
		$('html, body').removeClass('_overlay_screen-lockbody_scroller');
	}
}

_pubutils._lock_page = function (msg) {
	_pubutils._init_lock_page();
	$('#_lock_screen').addClass('_overlay_screen').html(msg).fadeTo(0, 0.4).css('display', 'flex');
	$('html, body').addClass('_overlay_screen-lockbody_scroller');
}

_pubutils._unlock_page = function () {
	_pubutils._init_lock_page();
	$('#_lock_screen').removeClass('_overlay_screen').html('').css('display', 'none');
}

_pubutils.keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
//turn string to base64 encode string
_pubutils.encode64 = function(input) {
    var output = "";
    var chr1, chr2, chr3 = "";
    var enc1, enc2, enc3, enc4 = "";
    var i = 0;

    do {
        chr1 = input.charCodeAt(i++);
        chr2 = input.charCodeAt(i++);
        chr3 = input.charCodeAt(i++);

        enc1 = chr1 >> 2;
        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
        enc4 = chr3 & 63;

        if (isNaN(chr2)) {
            enc3 = enc4 = 64;
        } else if (isNaN(chr3)) {
            enc4 = 64;
        }

        output = output +
            _pubutils.keyStr.charAt(enc1) +
            _pubutils.keyStr.charAt(enc2) +
            _pubutils.keyStr.charAt(enc3) +
            _pubutils.keyStr.charAt(enc4);
        chr1 = chr2 = chr3 = "";
        enc1 = enc2 = enc3 = enc4 = "";
    } while (i < input.length);

    return output;
}

/**
 * Decodes base64 string to UTF-8 string, compatibility wrapper
 * Will use _decode64_modern() if atob() is available; otherwise _decode64_legacy() is used, OGP-22312
 * @param   string  data    base64 string
 * @return  string  UTF-8 compatible string
 */
 _pubutils.decode64 = function(data) {
    if (typeof (atob) == 'function') {
        return _pubutils._decode64_modern(data);
    }

    return _pubutils._decode64_legacy(data);
}

// reference to https://stackoverflow.com/a/33486055
_pubutils.MD5 = function(d){var r = M(V(Y(X(d),8*d.length)));return r.toLowerCase()};function M(d){for(var _,m="0123456789ABCDEF",f="",r=0;r<d.length;r++)_=d.charCodeAt(r),f+=m.charAt(_>>>4&15)+m.charAt(15&_);return f}function X(d){for(var _=Array(d.length>>2),m=0;m<_.length;m++)_[m]=0;for(m=0;m<8*d.length;m+=8)_[m>>5]|=(255&d.charCodeAt(m/8))<<m%32;return _}function V(d){for(var _="",m=0;m<32*d.length;m+=8)_+=String.fromCharCode(d[m>>5]>>>m%32&255);return _}function Y(d,_){d[_>>5]|=128<<_%32,d[14+(_+64>>>9<<4)]=_;for(var m=1732584193,f=-271733879,r=-1732584194,i=271733878,n=0;n<d.length;n+=16){var h=m,t=f,g=r,e=i;f=md5_ii(f=md5_ii(f=md5_ii(f=md5_ii(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_ff(f=md5_ff(f=md5_ff(f=md5_ff(f,r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+0],7,-680876936),f,r,d[n+1],12,-389564586),m,f,d[n+2],17,606105819),i,m,d[n+3],22,-1044525330),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+4],7,-176418897),f,r,d[n+5],12,1200080426),m,f,d[n+6],17,-1473231341),i,m,d[n+7],22,-45705983),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+8],7,1770035416),f,r,d[n+9],12,-1958414417),m,f,d[n+10],17,-42063),i,m,d[n+11],22,-1990404162),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+12],7,1804603682),f,r,d[n+13],12,-40341101),m,f,d[n+14],17,-1502002290),i,m,d[n+15],22,1236535329),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+1],5,-165796510),f,r,d[n+6],9,-1069501632),m,f,d[n+11],14,643717713),i,m,d[n+0],20,-373897302),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+5],5,-701558691),f,r,d[n+10],9,38016083),m,f,d[n+15],14,-660478335),i,m,d[n+4],20,-405537848),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+9],5,568446438),f,r,d[n+14],9,-1019803690),m,f,d[n+3],14,-187363961),i,m,d[n+8],20,1163531501),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+13],5,-1444681467),f,r,d[n+2],9,-51403784),m,f,d[n+7],14,1735328473),i,m,d[n+12],20,-1926607734),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+5],4,-378558),f,r,d[n+8],11,-2022574463),m,f,d[n+11],16,1839030562),i,m,d[n+14],23,-35309556),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+1],4,-1530992060),f,r,d[n+4],11,1272893353),m,f,d[n+7],16,-155497632),i,m,d[n+10],23,-1094730640),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+13],4,681279174),f,r,d[n+0],11,-358537222),m,f,d[n+3],16,-722521979),i,m,d[n+6],23,76029189),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+9],4,-640364487),f,r,d[n+12],11,-421815835),m,f,d[n+15],16,530742520),i,m,d[n+2],23,-995338651),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+0],6,-198630844),f,r,d[n+7],10,1126891415),m,f,d[n+14],15,-1416354905),i,m,d[n+5],21,-57434055),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+12],6,1700485571),f,r,d[n+3],10,-1894986606),m,f,d[n+10],15,-1051523),i,m,d[n+1],21,-2054922799),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+8],6,1873313359),f,r,d[n+15],10,-30611744),m,f,d[n+6],15,-1560198380),i,m,d[n+13],21,1309151649),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+4],6,-145523070),f,r,d[n+11],10,-1120210379),m,f,d[n+2],15,718787259),i,m,d[n+9],21,-343485551),m=safe_add(m,h),f=safe_add(f,t),r=safe_add(r,g),i=safe_add(i,e)}return Array(m,f,r,i)}function md5_cmn(d,_,m,f,r,i){return safe_add(bit_rol(safe_add(safe_add(_,d),safe_add(f,i)),r),m)}function md5_ff(d,_,m,f,r,i,n){return md5_cmn(_&m|~_&f,d,_,r,i,n)}function md5_gg(d,_,m,f,r,i,n){return md5_cmn(_&f|m&~f,d,_,r,i,n)}function md5_hh(d,_,m,f,r,i,n){return md5_cmn(_^m^f,d,_,r,i,n)}function md5_ii(d,_,m,f,r,i,n){return md5_cmn(m^(_|~f),d,_,r,i,n)}function safe_add(d,_){var m=(65535&d)+(65535&_);return(d>>16)+(_>>16)+(m>>16)<<16|65535&m}function bit_rol(d,_){return d<<_|d>>>32-_};
_pubutils.NON_ENG_MD5 = function(d){return _pubutils.MD5(decodeURIComponent(encodeURIComponent(d)))};


/**
 * Decodes base64 string to UTF-8 string, modern version
 * Do not use directly; use decode64() instead, OGP-22312
 * Uses atob() function built in most modern browsers
 * @param   string  data    base64 string
 * @return  string  UTF-8 compatible string
 */
 _pubutils._decode64_modern = function(data) {
    var decoded = decodeURIComponent(atob(data));
    return decoded;
}

/**
 * Decodes base64 string to utf-8 string, legacy version
 * Do not use directly; use decode64() instead, OGP-22312
 * Adapted from https://simplycalc.com/base64-source.php
 * @param   string  data    base64 string
 * @return  string  UTF-8 compatible string
 */
 _pubutils._decode64_legacy = function(data) {
    var b64pad = '=';
    var b64u = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";

    function base64_charIndex(c) {
        if (c == "+") return 62;
        if (c == "/") return 63;
        return b64u.indexOf(c);
    }

    var dst = "";
    var i, a, b, c, d, z;

    for (i = 0; i < data.length - 3; i += 4) {
        a = base64_charIndex(data.charAt(i + 0));
        b = base64_charIndex(data.charAt(i + 1));
        c = base64_charIndex(data.charAt(i + 2));
        d = base64_charIndex(data.charAt(i + 3));

        dst += String.fromCharCode((a << 2) | (b >>> 4));
        if (data.charAt(i + 2) != b64pad) {
            dst += String.fromCharCode(((b << 4) & 0xF0) | ((c >>> 2) & 0x0F));
        }
        if (data.charAt(i + 3) != b64pad) {
            dst += String.fromCharCode(((c << 6) & 0xC0) | d);
        }
    }

    // dst = decodeURIComponent(escape(dst));
    dst = decodeURIComponent(dst);
    return dst;
}

_pubutils.setupAjaxReplayWithDelay = function() {
    var _this = this;
    _this.isShowAlertOfAjaxReplayWithDelay = false;
    $.ajaxSetup({
        complete: function(xhr, status) {
            var request = xhr;
            // _this.safelog('setAjaxReplayWithDelay.xhr', xhr);
            // _this.safelog('setAjaxReplayWithDelay.status', status);
            // _this.safelog('============================');
            var doAjaxReplayWithDelay = false; // for each ajax completed

            var wait_sec = 0; // initial
            if( typeof(request.responseJSON) !== 'undefined'){
                var responseJSON = request.responseJSON;
                if( typeof(responseJSON.code) !== 'undefined'){
                    if(responseJSON.code === 99991163){
                        doAjaxReplayWithDelay = true;
                        wait_sec = responseJSON.wait_sec;
                        var ignoreShow = _this.inArray(responseJSON.request_uri, _this.ignoreShowURIList, true);
                        if(ignoreShow){
                            doAjaxReplayWithDelay = false;
                        }
                    }
                }
            }
            if(doAjaxReplayWithDelay){
                if(!_this.isShowAlertOfAjaxReplayWithDelay){
                    _this.isShowAlertOfAjaxReplayWithDelay = true;
                    var msg = _this.lang['System is busy, please wait {0} seconds before trying again']
                    msg = msg.cformat(wait_sec);
                    var re = alert(msg);
                    _this.isShowAlertOfAjaxReplayWithDelay = false;
                    // _this.safelog('AlertOfAjaxReplayWithDelay.re:', re);
                }else{
                    _this.safelog('AlertOfAjaxReplayWithDelay had shown');
                }
            }
        } // EOF complete: function(xhr,status) {...
    }); // EOF $.ajaxSetup({...
}
_pubutils.setupAjaxReplayWithDelay();

// https://stackoverflow.com/a/4256130
String.prototype.cformat = function() {
    var formatted = this;
    for (var i = 0; i < arguments.length; i++) {
        var regexp = new RegExp('\\{'+i+'\\}', 'gi');
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};

_pubutils.callUri = function(uri, data, beforeCallback, completeCallback ){
    var _this = this; // _pubutils
    var _ajax = $.ajax({
        url: uri,
        type: 'POST',
        'data': data,
        beforeSend: function (xhr, settings) {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof(beforeCallback) !== 'undefined'){
                beforeCallback.apply(_this, cloned_arguments); // aka. _pubutils.beforeCallback()
            }
        },
        complete: function (xhr, textStatus) {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof(completeCallback) !== 'undefined'){
                completeCallback.apply(_this, cloned_arguments); // aka. _pubutils.completeCallback()
            }
        }
    });
    return _ajax;
} // EOF callUri

$(document).ready(function () {

	$('._select_currecny_on_logged').change(function () {
		//call change active db
		var key = $(this).val();
		//lock page
		_pubutils._lock_page(_pubutils.lang['Changing Currency']);
		$.ajax(
			'/auth/change_active_currency_for_logged/' + key,
			{
				dataType: 'json',
				cache: false,
				success: function (data) {
					if (data && data['success']) {
						if (data['redirect_url']) {
							window.location.href = data['redirect_url'];
						} else {
							window.location.reload();
						}
					} else {
						alert(_pubutils.lang['Change Currency Failed']);
						_pubutils._unlock_page();
					}
				},
				error: function () {
					alert(_pubutils.lang['Change Currency Failed']);
					_pubutils._unlock_page();
				}
			}
		).always(function () {
			// _unlock_page();
		});
	});

	$('._select_currecny_on_login').change(function () {
		//call change active db
		var key = $(this).val();
		//lock page
		_pubutils._lock_page(_pubutils.lang['Changing Currency']);
		$.ajax(
			'/auth/change_active_currency?__OG_TARGET_DB=' + key,
			{
				dataType: 'json',
				cache: false,
				success: function (data) {
					if (data && data['success']) {
						window.location.reload();
					} else {
						alert(_pubutils.lang['Change Currency Failed']);
						_pubutils._unlock_page();
					}
				},
				error: function () {
					alert(_pubutils.lang['Change Currency Failed']);
					_pubutils._unlock_page();
				}
			}
		).always(function () {
			// _unlock_page();
		});
	});

})

