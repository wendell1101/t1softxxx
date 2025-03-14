var T1Iovation = {
	site_url : document.location.origin,

	registerToIovation : function (ioBlackBox, promoCmsSettingId, callback) {
		var self = this;
		var ajax = $.ajax({
                        type: "POST",						
						dataType: "json",
						url: self.site_url + '/player_center/register_to_iovation_by_promotion/',
						data: {'ioBlackBox':ioBlackBox, 'promoCmsSettingId':promoCmsSettingId}
                    });
        
		ajax.always(function(dataOrJqXHR, textStatus, jqXHROrErrorThrown){
			console.log('Send iovation by promotion : always');
		});

		ajax.done(callback);

		return ajax;
	}
    
}