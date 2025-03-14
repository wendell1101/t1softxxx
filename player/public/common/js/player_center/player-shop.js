var Shop = {
	site_url: document.location.origin,
	default_banner_img_src: '',
	claim_now_msg: '',
	pending_msg: '',

	displayShoppingDetails : function (objShoppingItem, playerId) {
		var self  = this;

		$('#shopMsg').hide();
		$('#shopMsgSec').hide();
		$('#shopMsg').html("");
		$("#shopItemPreviewImg").attr("src",self.site_url + "/resources/images/promothumbnails/loader.gif");
		$("#shopItemPreviewImg").css({"padding" : "100px 0", "margin" : "0 auto", "display" : "block"});

		$(".claimShoppingItemBtn").show();
		$("#claimNowBtnTxt").text(self.claim_now_msg);

		$.ajax({
			'url' : self.site_url + '/player_center/getShoppingItemDetailsWithPlayerId/' + objShoppingItem.id+'/'+playerId,
			'type' : 'GET',
			'dataType' : "json",
			'success' : function(data){
				// console.log(data);
				$("#shopItemRequirePoints").html('<i class="fa fa-cubes" aria-hidden="true"></i>'+JSON.parse(data.requirements).required_points);
				$("#shopItemTitle").text(data.title);
				$("#shopItemDesc").html(data.details);

				var flag_true = '1';
				$('#shopItemPreviewImg').removeAttr('style');
				if(data.banner_url == null){
					$("#shopItemPreviewImg").attr("src", self.default_banner_img_src);
				}else{
					if(data.is_default_banner_flag == flag_true){
						$("#shopItemPreviewImg").attr("src", self.site_url + data.banner_url);
					}else{
						$("#shopItemPreviewImg").attr("src", self.site_url + data.banner_url);
					}
				}

				var newBtn = "";
				$("#claimBtnSec").html(newBtn);

				if(data.is_player_item_req_exists){
					newBtn = "<button type='button' class='btn btn-default submit-btn claimShoppingItemBtn'><span id='claimNowBtnTxt'>" + self.pending_msg + "</span></button>";
				}else{
					newBtn = "<button type='button' id='"+data.id+"' onclick='Shop.claimShoppingItem(this, " + playerId + ")' class='btn btn-default submit-btn claimShoppingItemBtn'><span id='claimNowBtnTxt'>" + self.claim_now_msg + "</span></button>";
				}

				$("#claimBtnSec").html(newBtn);
			}
		});
	},

	claimShoppingItem : function (shoppingItem, player_id) {
		var self = this;
		var shoppingItemId = shoppingItem.id;

		$.getJSON(self.site_url + '/player_center/claimShoppingItem/' + shoppingItemId+'/'+player_id, function(data){
			$('#shopMsgSec').show();
			$('#shopMsg').show();
			$('#shopMsg').html("");
			$('#shopMsg').html("<b>"+data.msg+"</b>");
			$("#claimNowBtnTxt").text(self.pending_msg);
			stop_loading();

			if(data.enable_hide_shop_claim_button) {                              
				$(".claimShoppingItemBtn").hide();
            }

			if(data.enable_shop_claim_item_auto_reload_desktop) {
                location.reload();
            }

		}).always(function(){
			$("#claimNowBtnTxt").text(self.pending_msg);
			stop_loading();
		});
	},
	displayShoppingDetailsMobileVer: function (objShoppingItem, playerId) {
		if (objShoppingItem.className.indexOf("collapsed") > 0) {

			var self = this;
			var itemId = objShoppingItem.id ;
	
			$('#shopMsg_item_' + itemId).hide();
			$('#shopMsgSec_item_' + itemId).hide();
			$('#shopMsg_item_' + itemId).html("");

			$(".claimShoppingItemBtn_item_" + itemId).show();
			$("#claimNowBtnTxt_item_" + itemId).text(self.claim_now_msg);
	
			$.ajax({
				'url': self.site_url + '/player_center/getShoppingItemDetailsWithPlayerId/' + itemId + '/' + playerId,
				'type': 'GET',
				'dataType': "json",
				'success': function (data) {
					// console.log(data);

					var newBtn = "";
					$("#claimBtnSec_item_" + itemId).html(newBtn);
	
					if (data.is_player_item_req_exists) {
						newBtn = "<button type='button' class='btn btn-default submit-btn claimShoppingItemBtn_item_" + itemId +"'>\
						<span id='claimNowBtnTxt_item_" + itemId +"'>" + self.pending_msg + "</span>\
						</button>";
					} else {
						newBtn = "<button type='button' id='" + data.id + "' onclick='Shop.claimShoppingItemMobileVer(this, " + playerId + ")' class='btn btn-default submit-btn claimShoppingItemBtn" + itemId +"'>\
						<span id='claimNowBtnTxt_item_" + itemId +"'>" + self.claim_now_msg + "</span>\
						</button>";
					}
	
					$("#claimBtnSec_item_" + itemId).html(newBtn);
				}
			});
		}

	},
	claimShoppingItemMobileVer: function (shoppingItem, player_id) {
		var self = this;
		var shoppingItemId = shoppingItem.id;

		$.getJSON(self.site_url + '/player_center/claimShoppingItem/' + shoppingItemId + '/' + player_id, function (data) {
			$('#shopMsgSec' + shoppingItemId).show();
			$('#shopMsg' + shoppingItemId).show();
			$('#shopMsg' + shoppingItemId).html("");
			$('#shopMsg' + shoppingItemId).html("<b>" + data.msg + "</b>");
			$("#claimNowBtnTxt" + shoppingItemId).text(self.pending_msg);
            
			if(data.status == "success") {
                MessageBox.success(data.msg, null, function(){
                    show_loading();
                    window.location.reload(true);
                });
            }else{
                MessageBox.danger(data.msg, null, function(){
                    show_loading();
                    window.location.reload(true);
                });
            }

		}).always(function () {
			$("#claimNowBtnTxt" + shoppingItemId).text(self.pending_msg);
			stop_loading();
		});
	},

}