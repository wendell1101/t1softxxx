(function( factory ) {
	if ( typeof define === "function" && define.amd ) {
		define( ["jquery", "../jquery.validate"], factory );
	} else if (typeof module === "object" && module.exports) {
		module.exports = factory( require( "jquery" ) );
	} else {
		factory( jQuery );
	}
}(function( $ ) {

/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: HI (Hindi; India)
 */
$.extend( $.validator.messages, {
	required: "यह फ़ील्ड आवश्यक है।",
	remote: "कृपया इस फ़ील्ड को सही करें।",
	email: "कृपया एक मान्य ईमेल प्रारूप दर्ज करें।",
	url: "कृपया एक मान्य URL प्रारूप दर्ज करें।",
	date: "कृपया एक मान्य तिथि प्रारूप दर्ज करें।",
	dateISO: "कृपया एक मान्य तिथि (ISO) प्रारूप दर्ज करें।",
	number: "कृपया एक मान्य संख्या दर्ज करें।",
	digits: "कृपया केवल अंक दर्ज करें।",
	creditcard: "कृपया एक मान्य क्रेडिट कार्ड प्रारूप दर्ज करें।",
	equalTo: "कृपया पहले जैसा ही मान दर्ज करें।",
	maxlength: $.validator.format("इनपुट {0} अक्षरों तक सीमित है।"),
	minlength: $.validator.format("इनपुट कम से कम {0} अक्षर होने चाहिए।"),
	rangelength: $.validator.format("स्वीकृत अक्षर लंबाई {0} और {1} के बीच होनी चाहिए।"),
	range: $.validator.format("कृपया {0} और {1} के बीच एक मान दर्ज करें।"),
	max: $.validator.format("कृपया {0} से कम या उसके बराबर मान दर्ज करें।"),
	min: $.validator.format("कृपया {0} से अधिक या उसके बराबर मान दर्ज करें।")
} );

}));