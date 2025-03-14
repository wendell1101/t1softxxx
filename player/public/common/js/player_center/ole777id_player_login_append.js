var Ole777idPlayerLogin = {
	append_custom_js: function(){
		var segments = window.location.hostname.split('.');
		segments.shift();

		var hostname = segments.join('.').toLowerCase();
		hostname = hostname.toLowerCase();

		if(hostname == 'gol7758.com'){
			var specify_script = '<meta name="robots" content="noindex,nofollow">';
			$('head').append(specify_script);
		}
	}
}