var Promotions = Promotions || {
	embedMode: false,
	site_url: document.location.origin,
	currency_symbol: '',
	preloadPromo: {}, // preload promo detail
	preloadPromoRespJoined: {}, // the resp. after added the promo
	lastAlertMessageJson: {},
	promise4promodetails_modal: {},
	deferred4promodetails_modal: {},
	debugLog: true,
    //
    /// OGP-29899
    is_mobile: false,
    enabled_get_allpromo_with_category_via_ajax: false,// from getConfig
    hidden_player_center_promotion_page_title_and_img: false, // from isEnabledFeature
    enabled_multiple_type_tags_in_promotions: false, // from getConfig
    disabled_show_promo_detail_on_list:false, // from isEnabledFeature disabled_show_promo_detail_on_list
    enabled_request_promo_now_on_list:false, // from isEnabledFeature enabled_request_promo_now_on_list
    promo_auto_redirect_to_deposit_page:false, // from getConfig promo_auto_redirect_to_deposit_page
    uris:{
        'getPlayerAvailablePromoList': '/async/getPlayerAvailablePromoList/{$promoCategoryId}'
    },
    langs_key_list : [ 'lang.new',
        'Favourite',
        'End Soon',
        'Claim Now',
        'cat.no.promo',
        'View Details',
        'lang.norec',
        'lang.details'
    ],
    responseData:{},
    langs: {},

	shownCB4promodetails_modal: function () {

	},
	/// URIs,
	// player.og.local/player_center2/promotion/embed/{promoCode}/join
	// player.og.local/player_center2/promotion/embed/#{playerId}/{promoId}
	// http://player.og.local/player_center2/promotion/embed/#32728/17123
	// http://player.og.local/player_center2/promotion/embed/#playerId=32728&promoId=17123
	// http://player.og.local/player_center2/promotion/embed/17123

	onReady: function () {
		var self = this;

		self.initDebugLog();


		self.initialDeferred4promodetails_modal()
		self.shownCB4promodetails_modal = function () {
			self.safelog('in shownCB4promodetails_modal:', arguments);
		};

        self.onReady4ajaxPromotion();

		self.events();
	},
    onReadyMobi: function () {
		var self = this;

		self.initDebugLog();

        self.onReady4ajaxPromotion();

		self.events4ajaxPromotion();
	},

	initialDeferred4promodetails_modal: function () {
		var self = this;
		if ($.isEmptyObject(self.deferred4promodetails_modal)) {
			self.deferred4promodetails_modal = $.Deferred();
			self.promise4promodetails_modal = self.deferred4promodetails_modal.promise();
		}

		if (self.deferred4promodetails_modal.state() !== 'pending') { // rejected OR resolved
			self.deferred4promodetails_modal = {}; // reset
			self.initialDeferred4promodetails_modal();
		}
	},


	events: function () {
		var self = this;




		// handle getMessage/postMessage via message event
		self.getMessage = function (e) {
			self.safelog('promotions.getMessage', arguments);
			if (e.data.event_id == 'cors_rpc') {
				if (typeof (self[e.data.func]) === 'function') {
					self[e.data.func].apply(self, e.data.params);
				}
			}
		};
		window.addEventListener("message", self.getMessage, false);

		$('body')
			.on('show.bs.modal', '#promodetails_modal', function (e) { // after show
				self.safelog('in show.bs.modal');
			})
			.on('shown.bs.modal', '#promodetails_modal', function (e) { // after show

				self.safelog('in shown.bs.modal');
				self.preloadPromo = {}; // reset preloadPromo

				self.deferred4promodetails_modal.resolve(); // for next
			});
		$('body').on('hide.bs.modal', '#promodetails_modal', function (e) { // on hide
			self.postToCloseIframeMessage();
			self.stop_countdown_check();
			$('#promo_period_countdown').addClass('hide');
		});

		/// Patch for "has been blocked by CORS policy: Cross origin requests are only supported"
		// while the element,"#promodetails_modal" modal(show) with build-in mothed.
		// during "show.bs.modal" and "shown.bs.modal" event.
		$('body').on('click', '.viewPromoDetailsAllPromoItem', function (e) {
			self.clicked_viewPromoDetailsAllPromoItem(e);
		});


		self.deferred4promodetails_modal.done(function () {
			self.safelog('in deferred4promodetails_modal.done');
			self.shownCB4promodetails_modal.apply(self, arguments);
		});


		/// Dont execute scriptDoPreloadPromo here, because self.embedMode not setup from embeddee page.
		// self.scriptDoPreloadPromo();

        self.events4ajaxPromotion();

	}, // EOF events

	scriptDoPreloadPromo: function () {
		var self = this;
		var isPreloadPromoEmpty = null;
		var deferred = $.Deferred();
		// @todo TEST CASES, EMPTY promo/without promo/without promo for the player
		// preload promo detail via promo code.
		if (!$.isEmptyObject(self.preloadPromo)) {
			isPreloadPromoEmpty = false;
			self.safelog('in scriptDoPreloadPromo');


			/// hidden list
			// var promoId = self.preloadPromo['promo_list'][0]['promoCmsSettingId'];
			// self.selectCategoryListByPromoId(promoId);

			// setTimeout(function(){
			// 	self.postScrollTopMessage($('.promotion-content:has(.viewPromoDetailsAllPromoItem[id="17128"])').closest('div').offset().top);
			// }, 16000); // TEST CASE, postScrollTopMessage 需要加入 deferred 等 embedee 的 iframe 顯示就緒（隱藏 loadding 顯示內嵌頁)

			self.viewPromoDetailWithPreloadPromo();


			// join request in once request.
			if (!$.isEmptyObject(self.preloadPromoRespJoined)) {
				// var $deferr = $.Deferred();
				// var promise = $deferr.promise();
				self.onDone4requestPromoNow(self.preloadPromoRespJoined, function (data) {
					if (data.status !== 'success') {
						MessageBox.danger(data.msg, '', function () { //hiddenCB
							self.safelog('will reset preloadPromoRespJoined', Promotions.preloadPromoRespJoined);
							Promotions.preloadPromoRespJoined = {}; // reset
						}, undefined, function (e) { // shownCB
							self.postScrollTopMessage(0);
							deferred.resolve(); // for next step
							self.safelog('in MessageBox.shownCB.116');
						});
					} else {
						MessageBox.success(data.msg, null, function () {
						}, undefined, function (e) { // shownCB
							self.postScrollTopMessage(0);
							deferred.resolve(); // for next step
							self.safelog('in MessageBox.shownCB.122');
						});
					}
					// $deferr.resolve(data); // will trigger promise.done().
				});
			} else {
				deferred.resolve(); // for next step
			} // EOF if( ! $.isEmptyObject(self.preloadPromoRespJoined) ){

		} else {
			isPreloadPromoEmpty = true;
			self.deferred4promodetails_modal.resolve(); // for next
			deferred.resolve(); // for next step
		} // EOF if( ! $.isEmptyObject(self.preloadPromo) ){...

		/// TEST CASE ,正常可重複的加入優惠，會需要 preloadPromoRespJoined 為空的時候，顯示訊息。
		if (!$.isEmptyObject(self.lastAlertMessageJson)) {
			// MessageBox by dynamic type
			MessageBox[self.lastAlertMessageJson.type](self.lastAlertMessageJson.message, '', function () { //hiddenCB
				self.lastAlertMessageJson = {}; // reset

				/// for no preloadPromo at embedMode = true.
				if (isPreloadPromoEmpty
					&& self.embedMode
				) {
					self.postToCloseIframeMessage();
				}
			}, undefined, function (e) { // shownCB
				self.scriptMessageBoxShownCB(e);
				self.postScrollTopMessage(0);
				deferred.resolve(); // for next step
				self.safelog('in MessageBox.shownCB.141');
			});
		}// EOF if( ! $.isEmptyObject(self.lastAlertMessageJson) ){

		var returnPromise = $.when(deferred.promise(), self.promise4promodetails_modal);
		return returnPromise;
	}, // EOF scriptDoPreloadPromo


	/**
	 * The script for after MessageBox show
	 * event name:"shown.t1t.ui.modal"
	 *
	 * @param {event} e
	 */
	scriptMessageBoxShownCB: function (e) {
		var self = this;
		if (self.embedMode) { // for over embeddee view
			var modal = $(e.delegateTarget);
			// move to top for display.
			modal.find('.modal-dialog').css({
				'vertical-align': ''
				, 'top': '4vh'
			});
		}
	}, // EOF scriptMessageBoxShownCB

	/**
	 * Will fired while embeddee on Ready to display the list.
	 * The embeddee will hidden loading div then display and update src of iframe div.
	 */
	embedeeOnReady: function () {
		// return false;
		var self = this;
		self.setEmbedMode(true);
		self.safelog('in embedeeOnReady');
		// if( ! $.isEmptyObject(self.preloadPromo) ){
		// 	// scroll embedee to promo detail of list
		// 	var promoId = self.preloadPromo['promo_list'][0]['promoCmsSettingId'];
		// 	var scrollTop = $('.promotion-content:has(.viewPromoDetailsAllPromoItem[id="'+ promoId+ '"])').closest('div').offset().top;
		// 	self.postScrollTopMessage(scrollTop);
		// }

		var promise = self.scriptDoPreloadPromo();
		promise.done(function () {


			var height = null;
			var messageBoxVisibleCounter = $('.t1t-message-box .modal-content:visible').length;
			var detailsModalVisibleCounter = $('#promodetails_modal .modal-content:visible').length;
			self.safelog('in embedeeOnReady.promise.done.visibles', messageBoxVisibleCounter, detailsModalVisibleCounter);

			if (detailsModalVisibleCounter > 0) { // if( ! $.isEmptyObject(self.preloadPromo) ){
				var childNodes = document.querySelectorAll('#promodetails_modal .modal-content');
				height = self.findHighestNode(childNodes);
				// var aa = $('#promodetails_modal .modal-content').height();
				// var bb = $('#promodetails_modal').height();
				// self.safelog('in deferred4promodetails_modal.done197', height, aa, bb, arguments );
			} else if (messageBoxVisibleCounter > 0) {
				var childNodes = document.querySelectorAll('.t1t-message-box .modal-content');
				height = self.findHighestNode(childNodes);
			}

			if (height !== null) {
				self.postRecomandHeightMessage(height);
			}


			$('#promotions,#footer_template').addClass('hide'); // hide the list
		});

	}, // EOF embedeeOnReady

	setEmbedMode: function (setTo) {
		var self = this;
		self.embedMode = setTo;
	}, // EOF setEmbedMode


	adjustNavs: function () {
		var selectorStrList = [];
		selectorStrList.push('header');
		selectorStrList.push('.copyright');
		selectorStrList.push('.gamesProviders');
		selectorStrList.push('#promotions>h1');

		// // hide the list for embeddee
		// selectorStrList.push('#promotions');

		selectorStrList.push('#header_template');
		selectorStrList.push('#footer_template');
		$(selectorStrList.join(',')).addClass('hide');

		var selectorStrList = [];
		selectorStrList.push('body');
		// selectorStrList.push('.modal-backdrop.fade.in');
		$(selectorStrList.join(',')).addClass('t1t-background-transparent');

	},

	/**
	 * To select the category for the current promo.
	 * @param {integer|string} promoId
	 */
	selectCategoryListByPromoId: function (promoId) {
		var tabId = $('.viewPromoDetailsAllPromoItem[id="' + promoId + '"]').closest('[role="tabpanel"]').attr('id');
		// $('li[role="presentation"]:has(a[href="#tab10"])').tab('show');
		$('li[role="presentation"]>a[href="#' + tabId + '"]').tab('show');
	},


	doInitializeDialog: function () {
		var self = this;
		$('#informRow').hide();
		$('#informMsg').hide();
		$('#promoMsg').hide();
		$('#promoMsgSec').hide();
		$('#promoMsg').html("");
		var promothumbnailsURI = self.site_url + "/resources/images/promothumbnails/loader.gif";

		$("#promoItemPreviewImg").attr("src", promothumbnailsURI);
		$("#promoItemPreviewImg").css({ "padding": "100px 0", "margin": "0 auto", "display": "block" });

		$("#dateApplied").show();
		$("#closeModal").show();
		$(".applyBtn").hide();
		$('.reject-mesg').text('').hide();
		$('#promo-bonus-amount').text(self.currency_symbol + " ---");
	},

	/**
	 * View the promo detail on list.
	 * @param {element} objCalling The trigger element.
	 * @param {string|integer} playerId The player.player_id
	 */
	viewPromoDetailsAll: function (objCalling, playerId) {
		var self = this;
		var promoId = objCalling.id;

		var deferred = self.viewPromoDetailsAllWithPromoIdAndPlayerId(promoId, playerId);
		deferred.always(function () {
			// document.querySelector(`.btn-details-${promoId}`)?.scrollIntoView({block: "center", behavior: "smooth"});
			try {
				objCalling?.scrollIntoView({block: "center", behavior: "smooth"});
			} catch (error) {
				console.error('error', error);
			}
		});
		return deferred;
	},

	viewPromoDetailWithPreloadPromo: function () {
		var self = this;

		self.doInitializeDialog();
		if (!$.isEmptyObject(self.preloadPromo)) {
			self.onDone4viewPromoDetailsAllWithPromoIdAndPlayerId(self.preloadPromo);
			// init preloadPromo in the "shown.bs.modal".
			// callback ref. to deferred4promodetails_modal
			$('#promodetails_modal').modal('show');// init preloadPromo in the "shown.bs.modal".
		}

	},// EOF viewPromoDetailWithPreloadPromo

	/**
	 * The script for show loading div.
	 * Here maybe need to Scroll top for display loading div form the embeddee page (www-domain),"sites/black_and_red/promotion.html".
	 *
	 */
	scriptShowLoading: function () {
		var self = this;
		if (self.embedMode) {
			Loader.show(function (e) {
				$(e.loader_container).find('.loader_vertical_helper').addClass('top');
				self.postScrollTopMessage(0);
			});
		} else {
			Loader.show(); // alias, show_loading();
		}
	}, // EOF scriptShowLoading
	viewPromoDetailsAllWithPromoIdAndPlayerId: function (promoId, playerId) {//
		var self = this;
		self.doInitializeDialog();

		self.scriptShowLoading();

		var ajax = $.ajax({
			'url': self.site_url + "/player_center/getPromoCmsItemDetailsByPlayerId/promojoint/" + playerId + "/" + promoId,
			'type': 'GET',
			'dataType': "json",
			'success': function (data) {
				if (data == null) {
					window.location.reload();
					return false;
				}
				self.onDone4viewPromoDetailsAllWithPromoIdAndPlayerId(data);
			} // EOF success
		}); // EOF $.ajax({
		ajax.always(function () {
			stop_loading();
		});

		return ajax;
	}, // EOF viewPromoDetailsAllWithPromoIdAndPlayerId

	/**
	 *
	 */
	onDone4viewPromoDetailsAllWithPromoIdAndPlayerId: function (data) {
		// if (data == null) {return false;}
		var self = this;
		var first_child = 0;
		var fpromo = data.promo_list[first_child];
		var bonusAmount = 0;

		//OGP-22132
		$('#dateAppliedTxt').empty();

		if (fpromo.player_promo != undefined) {
			bonusAmount = fpromo.player_promo.bonusAmount;
			$('#dateAppliedTxt').html(fpromo.player_promo.dateApply);
		}
		else if (fpromo.promorule.bonusAmount == 0) {
			var depositPercentage = fpromo.promorule.depositPercentage;
			var nonfixedDepositMinAmount = fpromo.promorule.nonfixedDepositMinAmount;
			bonusAmount = nonfixedDepositMinAmount * (depositPercentage / 100);
		} else {
			bonusAmount = fpromo.promorule.bonusAmount;
		}

		//25722
		if (fpromo.enabled_progression_btn) {
			$('#promodetails_modal').removeClass('view-progression-btn');
			$('#progression-itemDetailsId').empty();
			if (!_export_sbe_t1t.variables.is_mobile) {
				$('.progressionBtn').remove();
			}
			if (fpromo.player_allowed_for_promo) {
				if ((typeof fpromo.enabled_progression_btn[fpromo.promoCmsSettingId] != 'undefined' )) {
					createProgressionBtn(fpromo.promo_category,fpromo.promoCmsSettingId);
					$('.progressionBtn').removeClass('hide');
					$('#promodetails_modal').addClass('view-progression-btn');
				}
			}
		}

		//25515,25516
		$('#dateAppliedTxt').empty();
		$('#remainingAvailable').addClass('hide');
		if (fpromo.enabled_remaining_available && fpromo.promorule.total_approved_limit > 0) {
			var available_count = fpromo.promorule.promo_remaining_available;
			var available_count_color = "#F73C3C"; //red
			if(available_count > 50){
				available_count_color = "#00A86B"; //green
			}else if(available_count > 10){
				available_count_color = "#FFD300"; //yellow
			}

			$('#remainingAvailableTxt').text(fpromo.promorule.promo_remaining_available).css({ "color": available_count_color, "font-weight": "bold", "font-size": "16px"});
			$('#remainingAvailable').removeClass('hide');
		}

		if (typeof fpromo.promorule != 'undefined' && fpromo.promorule) {
			$('#promoCmsPromoTypeModal').html(fpromo.promorule.promoTypeName);
			$('#promo-bonus-amount').text(self.currency_symbol + " " + bonusAmount);

			if (fpromo.promorule.promo_period_countdown == '1') {
				var hide_date = fpromo.promorule.hide_date;
				var countdown_txt = '#promo_period_countdown_txt';

				if (!_export_sbe_t1t.variables.is_mobile) {
					if(fpromo.enabled_promo_countdown_icon){
						$('.promo-countdown').removeClass('hide');
					}else{
						$('#promo_period_countdown').removeClass('hide');
					}
					self.stop_countdown_check();
					self.countDown(hide_date, countdown_txt, '');
				}else{
					let mobile_id = '#promo_'+fpromo.promo_category+'_item_'+fpromo.promoCmsSettingId+'_detail';
					countdown_txt = mobile_id + ' .promo_period_countdown_txt';
					self.stop_countdown_check();

					$(mobile_id + ' .promo_period_countdown').addClass('hide');
					if ($(mobile_id).hasClass('in')) {
						$(mobile_id + ' .promo_period_countdown').removeClass('hide');
						self.countDown(hide_date, countdown_txt, mobile_id);
					}
				}
			}
		}

		$('#promoCmsTitleModal').text(fpromo.promoName);
		$('#promoCmsPromoDetailsModal').html(_export_sbe_t1t.utils.decodeHtmlEntities(fpromo.promoDetails));
		$("#badgeNew").hide();

		//OGP-25827
		if (fpromo.enabled_multiple_tags && (typeof fpromo.enabled_multiple_tags[fpromo.tag_as_new_flag] != 'undefined' )) {
			$("#badgeNew").show();
			$("#badgeNew").text(fpromo.enabled_multiple_tags[fpromo.tag_as_new_flag]['lang_key']);
			$("#badgeNew").addClass(fpromo.enabled_multiple_tags[fpromo.tag_as_new_flag]['cs_class']);
		}else{
			var flag_true = '1';
			if (fpromo.tag_as_new_flag == flag_true) {
				$("#badgeNew").show();
			} else {
				$("#badgeNew").hide();
			}
		}

		var is_promo_disabled = fpromo.disabled;

		//need to check why id, I need to add attribute id="requestPromoBtn"
		if(is_promo_disabled){
			$("#requestPromoBtn").prop("disabled",true);
		}else{
			$("#requestPromoBtn").prop("disabled",false);
		}

		var is_disabled_pre_application = fpromo.disabled_pre_application;
		// The element,"#preApplicationPromoBtn" be not found  used.
		if (is_disabled_pre_application) {
			$("#preApplicationPromoBtn").prop("disabled", true);
		} else {
			$("#preApplicationPromoBtn").prop("disabled", false);
		}

		if (typeof (fpromo.inform) != 'undefined') {
			$('#informMsg').html(fpromo.inform); // TEST CASE, OGP-16681
			// Duplicate with mobile version, so hide it.
			$('#informRow').show();
			$('#informMsg').show();
		} else {
			$('#informRow').hide();
			$('#informMsg').hide();
			$('#informMsg').html('');
		}

		//OGP-29960
		$('.custom-mesg').html('')
		if (typeof (fpromo.referral_success_count) != 'undefined') {
			if(!!parseInt(fpromo.referral_success_count)){
				$('.custom-mesg').append('<span class="square_brackets">[</span>');
				$('.custom-mesg').append('<span class="ref_count">'+fpromo.referral_success_count+'</span>');
				$('.custom-mesg').append('<span class="square_brackets">]</span>');
			}
		}

		$('.applyBtn').off('click');

		var is_mobile = $('body').data('mobile') != undefined;
		var promo_claim_btn = '.btn-claim-' + fpromo.promoCmsSettingId;

		if (!!parseInt(fpromo.allow_claim_promo_in_promo_page)) {

			$('.claimLinkBtn').hide();
			if (fpromo.player_allowed_for_promo == false) {
				if (!is_mobile) {
					$('.applyBtn').hide();
					if (fpromo.hasOwnProperty('error_redirect_url')) {
						$('.reject-mesg').html('<a href="' + fpromo.error_redirect_url + '">' + fpromo.player_allowed_for_promo_mesg + '</a>').show();
					} else {
						$('.reject-mesg').html(fpromo.player_allowed_for_promo_mesg).show();
					}

					// reset close button
					$('#closeModal').text($('#closeModal').data('stdmesg'));
					$('#closeModal').off('click');
					// Handler for redirect_to_deposit
					if (typeof (fpromo.redirect_to_deposit) != 'undefined') {
						$('#closeModal').text(fpromo.redirect_to_deposit.mesg);
						$('#closeModal').on('click', function () {
							window.location.href = fpromo.redirect_to_deposit.url;
						});
					}
					$('#closeModal').show();
				}
				else {
					if (typeof (fpromo.redirect_to_deposit) != 'undefined') {
						stop_loading();
						var go_deposit = confirm(fpromo.redirect_to_deposit.mesg);
						if (go_deposit) {
							window.location.href = fpromo.redirect_to_deposit.url;
						}
					}else{
					    if(typeof (fpromo.player_allowed_for_promo_mesg) != 'undefined'){
					       stop_loading();
					       $(promo_claim_btn).attr('href', 'javascript: void(0)').off('click').html(fpromo.player_allowed_for_promo_mesg);
					    }
					}
				}
			} else {
				// Allow to apply
				if (fpromo.hasOwnProperty('contact_live_chat_to_apply')) {
					$('.apply-mesg').html(fpromo.contact_live_chat_to_apply).show();
					$('#closeModal').show();
				} else {
					if(!!parseInt(fpromo.display_apply_btn_in_promo_page)){
						$('.applyBtn').show();
						$('#closeModal').hide();
					}else{
						if (!is_mobile) {
							$('.applyBtn').hide();
							$('#closeModal').show();
						}else{
							$(promo_claim_btn).attr('href', 'javascript: void(0)').off('click').hide();
						}
					}
					$('.reject-mesg').text('').hide();
				}
			}
		} else {
			if (fpromo.claim_button_name) {
				$('.claimLinkBtn a').text(fpromo.claim_button_name);
			}
			$('.claimLinkBtn').show();
			$('.claimLinkBtn a').attr('href', fpromo.claim_button_url);

			$('.reject-mesg').text('').hide();
			$('#closeModal').hide();
		}

		$('#itemDetailsId').val(fpromo.promoCmsSettingId);

		$('#promoItemPreviewImg').removeAttr('style');
		if (fpromo.promoThumbnail == null || fpromo.promoThumbnail == "") {
			$("#promoItemPreviewImg").attr("src", self.site_url + "/resources/images/promothumbnails/default_promo_cms_1.jpg");
		} else {
			var imageUrl = self.site_url + fpromo.promoThumbnailPath + fpromo.promoThumbnail;
			self.imageExists(imageUrl, function (exists) {
				if (exists) {
					$("#promoItemPreviewImg").attr("src", imageUrl);
				} else {
					$("#promoItemPreviewImg").attr("src", self.site_url + "/resources/images/promothumbnails/" + fpromo.promoThumbnail);
				}
			});

		}
	},// EOF onDone4viewPromoDetailsAllWithPromoIdAndPlayerId

	clicked_viewPromoDetailsAllPromoItem: function (e) {
		var self = this;
		var theTarget$El = $(e.target);
		var promoId = theTarget$El.attr('id');
		var playerId = theTarget$El.data('playerid');
		var deferred = self.viewPromoDetailsAllWithPromoIdAndPlayerId(promoId, playerId);
		deferred.done(function () {
			$('#promodetails_modal').modal('show');
		})
	},// EOF clicked_viewPromoDetailsAllPromoItem

	viewMyPromoDetails: function (objCalling, playerId) {
		var self = this;
		var first_child = 0;
		var promoId = objCalling.id;

		/** Initialize dialog */
		self.doInitializeDialog();

		$.ajax({
			'url': self.site_url + "/player_center/getPromoCmsItemDetailsByPlayerId/mypromo/" + playerId + "/" + promoId,
			'type': 'GET',
			'dataType': "json",
			'success': function (data) {

				$('#promoCmsPromoTypeModal').html(data[first_child].promoTypeName);
				// $('#promoCmsBonusAmountModal').html("<i class='fa fa-cubes' aria-hidden='true'></i>" + self.currency_symbol + " " +data[first_child].bonusAmount);
				$('#promo-bonus-amount').text(self.currency_symbol + " " + data[first_child].bonusAmount);

				$('#dateAppliedTxt').html(data[first_child].dateApply);
				$('#promoCmsTitleModal').text(data[first_child].promoName);
				$('#promoCmsPromoDetailsModal').html(_export_sbe_t1t.utils.decodeHtmlEntities(data[first_child].promoDetails));
				$("#badgeNew").hide();

				var flag_true = '1';
				if (data[first_child].tag_as_new_flag == flag_true) {
					$("#badgeNew").show();
				} else {
					$("#badgeNew").hide();
				}

				var is_promo_disabled = data[first_child].disabled;

				if (is_promo_disabled) {
					$("#requestPromoBtn").prop("disabled", true);
				} else {
					$("#requestPromoBtn").prop("disabled", false);
				}

				var is_disabled_pre_application = data[first_child].disabled_pre_application;

				// The element,"#preApplicationPromoBtn" be not found  used.
				if (is_disabled_pre_application) {
					$("#preApplicationPromoBtn").prop("disabled", true);
				} else {
					$("#preApplicationPromoBtn").prop("disabled", false);
				}

				$('#itemDetailsId').val(data[first_child].promoCmsSettingId);

				$('#promoItemPreviewImg').removeAttr('style');
				if (data[first_child].promoThumbnail == null || data[first_child].promoThumbnail == "") {
					$("#promoItemPreviewImg").attr("src", self.site_url + "/resources/images/promothumbnails/default_promo_cms_1.jpg");
				} else {
					var imageUrl = self.site_url + data[first_child].promoThumbnailPath + data[first_child].promoThumbnail;

					self.imageExists(imageUrl, function (exists) {
						if (exists) {
							$("#promoItemPreviewImg").attr("src", imageUrl);
						} else {
							$("#promoItemPreviewImg").attr("src", self.site_url + "/resources/images/promothumbnails/" + data[first_child].promoThumbnail);
						}
					});
				}
			}
		});
	},

	/**
	 * Request join Promo.
	 * @param {string|integer} promoCmsSettingId
	 * @param {script} callback The script onDone of  ajax.
	 * @return (jquery.ajax)
	 */
	requestPromoNow: function (promoCmsSettingId, params, callback) {
		// console.log(params);
		var self = this;
		var ajax = $.ajax({
			_requestPromoNow_callback: callback,
			dataType: "json",
			url: self.site_url + '/player_center/request_promo/' + promoCmsSettingId,
			data: params,
			// OGP-19914: change method to POST to prevent GET request too long with ioBlackbox added
			method: 'POST',
			beforeSend: function (jqXHR, settings) {
				self.scriptShowLoading();
				$('#informMsg').hide();
				$('#informMsg').html('');
				return true;
			},
		});
		ajax.requestPromoNow_callback = callback;
		ajax.always(function (dataOrJqXHR, textStatus, jqXHROrErrorThrown) {
			$(".applyBtn").hide();
			stop_loading();
		});

		ajax.done(function (data, textStatus, jqXHR) {
			self.onDone4requestPromoNow(data, callback);
		});
		return ajax;
	}, // EOF requestPromoNow

    requestRedemptionCode: function (promoCmsSettingId, params, callback) {
		var self = this;
		var ajax = $.ajax({
			_requestPromoNow_callback: callback,
			dataType: "json",
			url: self.site_url + '/player_center/request_redemption/' + promoCmsSettingId,
			data: params,
			method: 'POST',
			beforeSend: function (jqXHR, settings) {
				self.scriptShowLoading();
				$('#informMsg').hide();
				$('#informMsg').html('');
				return true;
			},
		});
		ajax.requestPromoNow_callback = callback;
		ajax.always(function (dataOrJqXHR, textStatus, jqXHROrErrorThrown) {
			$(".applyBtn").hide();
			stop_loading();
		});

		ajax.done(function (data, textStatus, jqXHR) {
			self.onDone4requestPromoNow(data, callback);
		});
		return ajax;
	}, // EOF requestRedemptionCode

	onDone4requestPromoNow: function (data, callback) {
		var self = this;

		if(!!data.external_link){
			window.open(data.external_link);
		}

		// done
		$('#promoMsgSec').show();
		$('#promoMsg').show();
		$('#promoMsg').html("");
		$('#promoMsg').html("<b>" + data.msg + "</b>");

		if (typeof callback === "function") {
			callback.apply(null, [data]);
		}
	}, // EOF onDone4requestPromoNow


	preapplicationPromo: function (promoCmsSettingId, callback) {
		var self = this;
		promoCmsSettingId = (promoCmsSettingId === undefined) ? $("#itemDetailsId").val() : promoCmsSettingId;
		show_loading();

		var response_data = null;
		$.getJSON(self.site_url + '/player_center/request_promo/' + promoCmsSettingId, function (data) {
			$('#promoMsgSec').show();
			$('#promoMsg').show();
			$('#promoMsg').html("");
			$('#promoMsg').html("<b>" + data.msg + "</b>");
			response_data = data;
		}).always(function () {
			$(".applyBtn").hide();
			stop_loading();
			if (typeof callback === "function") {
				callback.apply(null, [response_data]);
			}
		});
	},

	imageExists: function (url, callback) {
		var img = new Image();
		img.onload = function () { callback(true); };
		img.onerror = function () { callback(false); };
		img.src = url;
	}
} // EOF Promotions


// ==== postMessage ====
Promotions.postOnReadyMessage = function () {
	// ref. to https://iandays.com/2018/09/23/postmessage/
	window.parent.postMessage(
		{
			event_id: 'cors_rpc', // rpc=remote procedure call
			func: 'iframeOnReady',
			params: []
		},
		"*" // or "www.parentpage.com"
	);
};

/**
 * Post Message to embeddee for adjust height of iframe
 */
Promotions.postRecomandHeightMessage = function (height) {
	var self = this;

	self.safelog('will postRecomandHeightMessage', arguments);
	window.parent.postMessage(
		{
			event_id: 'cors_rpc', // rpc=remote procedure call
			func: 'iframeRecomandHeight',
			params: [height]
		},
		"*" // or "www.parentpage.com"
	);
};

/**
 * Post Message to embeddee for ScrollTop.
 * @param {integer} oScrollTop To scroll top to px.
 */
Promotions.postScrollTopMessage = function (oScrollTop) {
	window.parent.postMessage(
		{
			event_id: 'cors_rpc', // rpc=remote procedure call
			func: 'iframeScrollTop',
			params: [oScrollTop]
		},
		"*" // or "www.parentpage.com"
	);
}; // EOF postScrollTopMessage

/**
 * Close detail and same time to close iframe.
 */
Promotions.postToCloseIframeMessage = function () {
	window.parent.postMessage(
		{
			event_id: 'cors_rpc', // rpc=remote procedure call
			func: 'toCloseIframe',
			params: []
		},
		"*" // or "www.parentpage.com"
	);
};// EOF postCloseIframeMessage

// ==== Utils ====

/**
 * Find height by nodes
 * Ref. to https://stackoverflow.com/a/41181003
 *
 * @param {nodes} nodesList The dom node list.
 * @param {integer} pageHeight The height after calc.
 */
Promotions.findHighestNode = function (nodesList, pageHeight) {
	var self = this;
	if (typeof (pageHeight) === 'undefined') {
		pageHeight = 0;
	}
	for (var i = nodesList.length - 1; i >= 0; i--) {
		if (nodesList[i].scrollHeight && nodesList[i].clientHeight) {
			var elHeight = Math.max(nodesList[i].scrollHeight, nodesList[i].clientHeight);
			pageHeight = Math.max(elHeight, pageHeight);
		}
		if (nodesList[i].childNodes.length) self.findHighestNode(nodesList[i].childNodes, pageHeight);
	}
	return pageHeight;
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
Promotions.parse_query_string = function (query) {
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

Promotions.safelog = function (msg) {
	var self = this;

	if (typeof (safelog) !== 'undefined') {
		safelog(msg); // for applied
	} else {
		//check exists console
		if (self.debugLog
			&& typeof (console) !== 'undefined'
		) {
			console.log.apply(console, Array.prototype.slice.call(arguments));
		}
	}
}; // EOF safelog


Promotions.countDown = function (hide_date, countdown_txt, this_id){
	var self = this;

	this.idle_timer = setInterval(function(){
		countdown = self.calTime(hide_date, this_id);
		$(countdown_txt).text(countdown);
		countdown_arr = self.arrayTime(hide_date);
		Object.keys(countdown_arr).forEach(function(key) {
			$("#countdown_" + key).text(countdown_arr[key]);
		});
	}, 1000);

};

Promotions.stop_countdown_check = function(){
	if(!this.idle_timer){
		return this;
	}
	clearInterval(this.idle_timer);
};

/**
 * 倒计时
 * @param nowTime 现在的时间
 * @param startTime 计时开始时间
 * @param endTime 计时结束时间
 * @returns {*} 计时标识
 */
Promotions.calTime = function (endDateTime, this_id) {
	var self = this;
	var nowTime = +new Date();
	var endTime = +new Date(endDateTime);

	if (nowTime > endTime) {
		self.stop_countdown_check();
		$('.requestPromoBtn').hide();
		if (this_id) {$(this_id).hide();}
		return lang('promo_countdown.end');
	}

	var times = (endTime - nowTime) / 1000;
	var d = parseInt(times / 60 / 60 / 24);
	d = d < 10 ? '0' + d : d;
	var h = parseInt(times / 60 / 60 % 24);
	h = h < 10 ? '0' + h : h;
	var m = parseInt(times / 60 % 60);
	m = m < 10 ? '0' + m : m;
	var s = parseInt(times % 60);
	s = s < 10 ? '0' + s : s;
	return d + lang('promo_countdown.Day') + h + lang('promo_countdown.Hour') + m + lang('promo_countdown.Min') + s + lang('promo_countdown.Sec');
};

Promotions.arrayTime = function (endDateTime) {
	var self = this;
	var nowTime = +new Date();
	var endTime = +new Date(endDateTime);

	if (nowTime > endTime) {
		self.stop_countdown_check();
		$('.requestPromoBtn').hide();
		if (this_id) {$(this_id).hide();}
		return lang('promo_countdown.end');
	}

	var times = (endTime - nowTime) / 1000;
	var d = parseInt(times / 60 / 60 / 24);
	d = d < 10 ? '0' + d : d;
	var h = parseInt(times / 60 / 60 % 24);
	h = h < 10 ? '0' + h : h;
	var m = parseInt(times / 60 % 60);
	m = m < 10 ? '0' + m : m;
	var s = parseInt(times % 60);
	s = s < 10 ? '0' + s : s;
	arrTime ={"day" : d, "hour": h, "min" : m, "sec" : s};
	return arrTime;
}


// id="allpromo"
Promotions.onReady4ajaxPromotion = function(){
    var self = this;
    self.safelog('in onReady4ajaxPromotion');
    if(!self.enabled_get_allpromo_with_category_via_ajax){
        return; // disable in enabled_get_allpromo_with_category_via_ajax=false
    }

    var _promoCategoryId = 0;
    var theCateIdList = [];

    if(self.is_mobile){
        // for mobi
        $('[aria-labelledby="select_promo_category_toggle"] li[disabled!="disabled"]').each(function(){
            theCateIdList.push( $(this).val() );
        });
    }else{
        // for PC
        $('#allpromo li>a[data-category_id]').each(function(){
            theCateIdList.push( $(this).data('category_id'));
        });
    }


    if(theCateIdList.length > 0){
        _promoCategoryId = theCateIdList.shift(); // take out the first element from theCateIdList.
    }

    self.safelog('will.allpromo.length:', $('#allpromo li>a[data-category_id]').length );
    self.safelog('will.do_query_promo_content_by_cate._promoCategoryId:', _promoCategoryId, 'theCateIdList', theCateIdList);

    if(_promoCategoryId > 0){

        $('.loader_promotions').removeClass('hide'); // show loading
        var $deferr = self.doRenderPromoContentWithCateId( _promoCategoryId );
        $deferr.done(function(data, textStatus, jqXHR){
            $('.loader_promotions').addClass('hide'); // hide loading
        })
        //
        // var postData = {};
        // if( $.isEmptyObject(self.langs) ){
        //     postData.langs_key_list = self.langs_key_list;
        // }
        // var responseDataKeyStr = '';
        // self.do_query_promo_content_by_cate(_promoCategoryId, function(jqXHR, settings){ // beforeSendCB
        //     responseDataKeyStr = encodeURIComponent(jqXHR._url);
        // }, function(jqXHR, textStatus){ // completeCB
        //     var resp = {};
        //     if( typeof(jqXHR.responseJSON) !== 'undefined' ){
        //         resp = jqXHR.responseJSON;
        //         self.setResponseData(resp, responseDataKeyStr);
        //     }
        //
        //     if( !$.isEmptyObject(resp.data.langs) ){
        //         for (var lang_key in resp.data.langs) {
        //             self.langs[lang_key] = resp.data.langs[lang_key];
        //         }
        //     }
        //     self.safelog('onReady4ajaxPromotion.do_query_promo_content_by_cate.data:', resp,'textStatus:', textStatus,'jqXHR:', jqXHR);
        //
        //     var _responseData = self.getResponseData(responseDataKeyStr);
        //
        //     self.do_render_promo_content_by_cate(_responseData);
        //
        // }, postData ); // EOF self.do_query_promo_content_by_cate(...
        $.when($deferr).then(function() {
            self.do_query_promo_content_with_cateList( theCateIdList );
        });
    }else{
        self.safelog('onReady4ajaxPromotion.emptyCategoryId, _promoCategoryId:', _promoCategoryId);
    }// EOF if(_promoCategoryId > 0){...


}; // EOF onReady4ajaxPromotion

Promotions.do_query_promo_content_with_cateList = function( _CateIdList ){
    var self = this;

    _CateIdList.forEach(function(_CateId, indexNumber, _array){
        self.doRenderPromoContentWithCateId(_CateId);
    });
}; // EOF do_query_promo_content_with_cateList
//
Promotions.doRenderPromoContentWithCateId = function(_promoCategoryId){
    var self = this;
    var responseDataKeyStr = '';

    var postData = {};

    var _ajax = self.do_query_promo_content_by_cate(_promoCategoryId, function(jqXHR, settings){ // beforeSendCB
        responseDataKeyStr = encodeURIComponent(jqXHR._url);
    }, function(jqXHR, textStatus){ // completeCB
        var resp = {};
        if( typeof(jqXHR.responseJSON) !== 'undefined' ){
            resp = jqXHR.responseJSON;
            self.setResponseData(resp, responseDataKeyStr);
        }

        self.safelog('onReady4ajaxPromotion.do_query_promo_content_by_cate.data:', resp,'textStatus:', textStatus,'jqXHR:', jqXHR);

        var _responseData = self.getResponseData(responseDataKeyStr);

        if(self.is_mobile){
            /// todo, mobi
            self.do_render_promo_content_by_cate_mobi(_responseData);

            self.do_render_no_promo_by_cate_mobi(_promoCategoryId); // tpl4catNoPromo
        }else{
            /// PC
            self.do_render_promo_content_by_cate(_responseData);

            self.do_render_no_promo_by_cate(_promoCategoryId); // tpl4catNoPromo
        }

    }, postData ); // EOF self.do_query_promo_content_by_cate(...
    return _ajax;
}; // EOF doRenderPromoContentWithCateId
Promotions.setResponseData = function(_data, _keyStr){
    var self = this;
    self.responseData[_keyStr] = _data;
    return _data;
}; // EOF setResponseData
Promotions.getResponseData = function(_keyStr){
    var self = this;
    var _data = null;
    if( typeof(self.responseData[_keyStr]) !== 'undefined'){
        _data = self.responseData[_keyStr];
    }
    return _data;
}; // EOF getResponseData
Promotions.do_query_promo_content_by_cate = function( _promoCategoryId, beforeSendCB, completeCB, postData = {}){
    var self = this;
    // var postData = {};
    var _uri = self.uris.getPlayerAvailablePromoList;
    _uri = _uri.replace(/{\$promoCategoryId}/gi, _promoCategoryId);
    self.safelog('in do_query_promo_content_by_cate._uri:', _uri);
    var _ajax = self._do_Uri( function(jqXHR, settings){ // beforeSendCB
        if( typeof(beforeSendCB) !== 'undefined'){
            var cloned_arguments = Array.prototype.slice.call(arguments);
            beforeSendCB.apply(self, cloned_arguments);
        }
    }, function(jqXHR, textStatus){ // completeCB
        var cloned_arguments = Array.prototype.slice.call(arguments);
        self.safelog('in do_query_promo_content_by_cate.completeCB.cloned_arguments:', cloned_arguments);
        if( typeof(completeCB) !== 'undefined'){
            completeCB.apply(self, cloned_arguments);
        }
    }, _uri, postData);
    return _ajax;
};// EOF do_query_promo_content_by_cate
//

Promotions.do_render_promo_content_by_cate_mobi = function( _responseData ){ // , _promoCategoryId
    var self = this;

    if( $.isEmptyObject(_responseData) ){
        self.safelog('in do_render_promo_content_by_cate_mobi, cancel by empty response');
        return; // cancel by empty response
    }

    if(_responseData.status !== 'success'){
        self.safelog('in do_render_promo_content_by_cate_mobi, cancel by NG response');
        return; // cancel by NG response
    }

    if( $.isEmptyObject(_responseData.data.promo_list) ){
        self.safelog('in do_render_promo_content_by_cate_mobi, cancel by empty promo_list', _responseData.data.promo_list);
        return; // cancel by empty promo_list
    }

    _responseData.data.promo_list.forEach(function(_promo, indexNumber, _array){

        var html4promotionHeaderMobi = self.tpl4promotionHeaderMobi( _promo.promoCmsSettingId
                                    , _promo.promoThumbnailUrl4mobile
                                    , _promo.promoName
                                    , _promo.promoDescription
                                    , _promo.promo_category
                                    , _promo.display_apply_btn_in_promo_page
                                    , _promo.allow_claim_promo_in_promo_page
                                    , _promo.claim_button_url
                                    , _promo.claim_button_name );

        var html4tpl4promotionBodyMobi = self.tpl4promotionBodyMobi( _promo.promo_category
                                                                    , _promo.promoCmsSettingId
                                                                    , _promo.promoDetails4lestCo
                                                                    , _promo.allow_claim_promo_in_promo_page
                                                                    , _promo.iframeRequestPromoUri
                                                                    , _promo.claim_button_url
                                                                    , _promo.claim_button_name );
        // self.safelog('in forEach.indexNumber', indexNumber, 'html4promotionHeaderMobi:', html4promotionHeaderMobi);
        // self.safelog('in forEach.indexNumber', indexNumber, 'html4tpl4promotionBodyMobi:', html4tpl4promotionBodyMobi);

        var html4promotionContentMobi = self.tpl4promotionContentMobi(html4promotionHeaderMobi, html4tpl4promotionBodyMobi);
        self.safelog('in forEach.indexNumber', indexNumber, 'html4promotionContentMobi:', html4promotionContentMobi);

        var appendToStr = `.promotions-category-list[data-promo_category_id="${_promo.promo_category}"]`;

        self.safelog('in forEach.indexNumber', indexNumber, 'appendTo', appendToStr,  $(appendToStr));
        $( html4promotionContentMobi ).appendTo(appendToStr);
    });

}; // EOF do_render_promo_content_by_cate_mobi
Promotions.do_render_promo_content_by_cate = function( _responseData ){ // , _promoCategoryId
    var self = this;

    self.safelog('in do_render_promo_content_by_cate._responseData:', _responseData);

    if( $.isEmptyObject(_responseData) ){
        self.safelog('in do_render_promo_content_by_cate, cancel by empty response');
        return; // cancel by empty response
    }

    if(_responseData.status !== 'success'){
        self.safelog('in do_render_promo_content_by_cate, cancel by NG response');
        return; // cancel by NG response
    }

    if( $.isEmptyObject(_responseData.data.promo_list) ){
        self.safelog('in do_render_promo_content_by_cate, cancel by empty promo_list');
        return; // cancel by empty promo_list
    }

    var _playerId = self.playerId;
    /// .promotion-header
    // _responseData:
    // @.data.promo_list[n].promoName
    // @.data.promo_list[n].promo_category = _promoCategoryId
    // @.data.promo_list[n].tag_as_new_flag
    // @.data.promo_list[n].promoThumbnail // todo, utils->getPromoThumbnailsUrl($key['promoThumbnail'], false)
    /// .promotion-body
    // @.data.promo_list[n].promoDescription
    // @.data.promo_list[n].promoCmsSettingId
    // @.data.promo_list[n].display_apply_btn_in_promo_page
    // Done, playerId

    _responseData.data.promo_list.forEach(function(_promo, indexNumber, _array){

        // viewPromoDetailsAllPromoItem" data-promocmssettingid="${promoCmsSettingId}"
        var _promoCmsSettingId = _promo.promoCmsSettingId;
        var _promoCategoryId = _promo.promo_category;

        var html4promotionHeader = self.tpl4promotionHeader(_promo.promoName, _promo.tag_as_new_flag, _promo.promoThumbnailUrl);
        var html4promotionBody = self.tpl4promotionBody(_promo.promoDescription, _promo.promoCmsSettingId, _playerId, _promo.display_apply_btn_in_promo_page);
        var html4promotionContent = self.tpl4promotionContent(html4promotionHeader, html4promotionBody);
        self.safelog('in forEach.indexNumber', indexNumber, 'html4promotionHeader:', html4promotionHeader);
        var appendToStr = "#tab"+ _promoCategoryId+ " .row";
        $( html4promotionContent ).appendTo(appendToStr);

    });

}; // EOF do_render_promo_content_by_cate
//
Promotions.do_render_no_promo_by_cate_mobi = function(_promoCategoryId){
    var self = this;

    var appendToStr = `.promotions-category-list[data-promo_category_id="${_promoCategoryId}"]`;
    var promotionContentStr = appendToStr+ ">[data-promo_item_anchor]";

    if($(promotionContentStr).length == 0){
        $( self.tpl4catNoPromoMobi() ).appendTo(appendToStr);
    }

    // <div id="promo_category_23" class="promotions-category-list" data-promo_category_id="23">
    //     <div class="pr_show threepage cpt5" data-promo_item_anchor="promo_item_16915">
    //     ....
    //     <div id="promo_23_item_16915_detail" class="collapse">
    //     ....
/* tpl4promotionHeaderMobi()
<div class="pr_show threepage cpt5" data-promo_item_anchor="promo_item_16915">
  <div class="primage">
    <img data-cfsrc="/resources/images/promothumbnails/default_promo_cms_1.jpg?v=6.206.01.001?v=6.206.01.001" src="https://player.staging.ole777idr.t1t.in/resources/images/promothumbnails/default_promo_cms_1.jpg?v=6.206.01.001?v=6.206.01.001">
  </div>
  <div class="title"> 【TEST PROMO】 </div>
  <div class="description">SEA GAMES</div>
  <div class="actions">
    <a href="javascript: void(0);" class="btn btn-sm btn-info btn-details-16915" data-toggle="collapse" onclick="if (!window.__cfRLUnblockHandlers) return false; Promotions.viewPromoDetailsAll(this, 161555);" id="16915" data-target="#promo_23_item_16915_detail">Detail</a>
  </div>
</div>
*/

/*
<div id="promo_23_item_16915_detail" class="collapse">
  <div class="panel-body">
    <!-- enabled_promo_period_countdown -->
    <div class="promo_period_countdown hide">
      <p>
        <span>Remaining time:</span>
        <span class="promo_period_countdown_txt"></span>
      </p>
    </div>
    <div class="lestCo">
      <h3>
        <strong>SEA GAMES EVENT 2023</strong>
      </h3>
      <p>
        <strong>Periode Promosi : 5 May – 17 May 2023 00:00 – 23:59 (GMT+8) <br>Berlaku Untuk : Semua Member OLE777 <br>
          <br>Mekanisme : </strong>
      </p>
      <ol class="promo-o-list">
        <li>Mencapai minimal total deposit IDR 10,000 untuk mengikuti promo ini.</li>
        <li>Pembayaran Bonus didasarkan pada pencapaian total deposit dan total medali yang diraih oleh Tim Indonesia di SEA Games 2023.</li>
        <li>Untuk mengikuti event promo ini, member harus mengisi Google Form. <br>
          <a rel="noreferrer noopener" href="https://forms.gle/YpJ3ewB5Wb31qgRv9" target="_blank">KLIK DI SINI</a> untuk berpartisipasi.
        </li>
      </ol>
      <figure class="wp-block-table">
        <table>
          <tbody>
            <tr class="tb-head-gold">
              <td colspan="6">THIS IS A TEST</td>
            </tr>
            <tr class="tb-head">
              <td>MEDALI</td>
              <td colspan="2">MIN. <br>DEPOSIT </td>
              <td colspan="2">BONUS</td>
              <td>TURNOVER</td>
            </tr>
            <tr>
              <td>BRONZE</td>
              <td colspan="2">IDR <br>10,000 </td>
              <td colspan="2">total <br>medali <br>x IDR 2 </td>
              <td rowspan="3">5x</td>
            </tr>
            <tr>
              <td>SILVER</td>
              <td colspan="2">IDR <br>50,000 </td>
              <td colspan="2">total <br>medali <br>x IDR 4 </td>
            </tr>
            <tr>
              <td>GOLD</td>
              <td colspan="2">IDR <br>100,000 </td>
              <td colspan="2">total <br>medali <br>x IDR 8 </td>
            </tr>
          </tbody>
        </table>
      </figure>
      <p>
        <em>Angka yang tertera adalah dalam ribuan (000) . Contoh : Min Deposit IDR10,000 = IDR10,000,000 (sepuluh juta rupiah)</em>
      </p>
      <p>
        <strong>Syarat dan Ketentuan Promosi:</strong>
      </p>
      <ol>
        <li>Promo berlaku untuk semua member dengan akumulasi deposit IDR 10,000 ke atas mulai 5 Mei 2023 00:00 (GMT+8) – 17 Mei 2023 23:59 (GMT+8).</li>
        <li>Anggota bisa mendapatkan bonus berdasarkan pencapaian total deposit selama periode promo. <br>Contoh : Member memiliki total deposit IDR 50,000 selama periode promo, member berhak mendapatkan bonus medali SILVER dan Tim Indonesia meraih 80 medali perak. <br>Perhitungan Pembayaran Bonus : 80 medali perak x IDR 4 = IDR 320. </li>
        <li>Bonus akan dikreditkan secara otomatis ke akun member pada 19 Mei 2023 paling lambat pukul 18:00 (GMT+8)</li>
        <li>Bonus harus dipertaruhkan sebanyak 5x di semua permainan.</li>
        <li>Penawaran Bonus ini terbatas pada 1 (satu) Nama Pengguna, Nama Pemilik Akun, Alamat, IP, Perangkat dan Browser, jika Anggota melakukan pelanggaran dalam penggunaan Bonus ini, maka OLE777 berhak untuk membatalkan Bonus bersama dengan Anggota kemenangan.</li>
        <li>Promosi ini tunduk pada Syarat dan Ketentuan Umum OLE777.</li>
      </ol>
    </div>
    <div class="action">
      <a href="https://player.staging.ole777idr.t1t.in/iframe_module/request_promo/16915" data-promo-cms-setting-id="16915" class="btn btn-sm btn-info claim-promo btn-claim-16915">Claim Now</a>
    </div>
  </div>
</div>
*/

}; // EOF do_render_no_promo_by_cate_mobi
//
Promotions.do_render_no_promo_by_cate = function(_promoCategoryId){
    var self = this;
    var appendToStr = "#tab"+ _promoCategoryId+ " .row";
    var promotionContentStr = "#tab"+ _promoCategoryId+ " .row .promotion-content";

    if($(promotionContentStr).length == 0){
        $( self.tpl4catNoPromo() ).appendTo(appendToStr);
    }
}; // EOF do_render_no_promo_by_cate
//
Promotions.events4ajaxPromotion = function(){
    var self = this;
    if(!self.enabled_get_allpromo_with_category_via_ajax){
        return; // disable in enabled_get_allpromo_with_category_via_ajax=false
    }

}; // EOF events4ajaxPromotion
//
Promotions.tpl4promotionContentMobi = function(_htmlPromotionHeader = '%_htmlPromotionHeader%', _htmlPromotionBody = '%_htmlPromotionBody%'){
    var tpl = `${_htmlPromotionHeader} ${_htmlPromotionBody}`;
    return tpl;
} // EOF tpl4promotionContentMobi
Promotions.tpl4promotionContent = function(_htmlPromotionHeader = '%_htmlPromotionHeader%', _htmlPromotionBody = '%_htmlPromotionBody%'){
    var sampleTpl = `
    <div class="col-sm-6">
        <div class="promotion-content">
            <div class="promotion-header">
            <h1 class="title-name"> Rolling Paradise (AE) <span class="badge-new">ใหม่</span>
            </h1>
            <img width="377" height="199" src="/resources/images/promothumbnails/promothumbnail-629641261fa59.jpg?v=6.206.01.001" />
            </div> <!-- EOF .promotion-header -->

            <div class="promotion-body clearfix">
            <div class="col-xs-8">
                <p> Rolling paradise (AE) </p>
            </div>
            <div class="col-xs-4 text-right">
                <a href="javascript: void(0);" class="btn viewPromoDetailsAllPromoItem" id="17162" data-playerid="16972">ดูเพิ่มเติม</a>
            </div>
            </div> <!-- EOF .promotion-body -->
        </div> <!-- EOF .promotion-content -->
    </div> <!-- EOF .col-sm-6 -->
    `;

    var tpl = `
    <div class="col-sm-6">
        <div class="promotion-content">
            ${_htmlPromotionHeader}

            ${_htmlPromotionBody}
        </div> <!-- EOF .promotion-content -->
    </div> <!-- EOF .col-sm-6 -->
    `;
    return tpl;

}; // EOF tpl4promotionContent
//
Promotions.tpl4promotionHeaderMobi = function( _promoCmsSettingId
                                                , _promoThumbnail
                                                , _promoName
                                                , _promoDescription
                                                , _promoCategoryId
                                                , _display_apply_btn_in_promo_page
                                                , _allow_claim_promo_in_promo_page
                                                , _claim_button_url
                                                , _claim_button_name
){
    var self = this;


    // self.hidden_player_center_promotion_page_title_and_img// isEnabledFeature
    // self.disabled_show_promo_detail_on_list //isEnabledFeature
    // self.display_apply_btn_in_promo_page
    // TODO


    var _playerId = self.playerId;
    var langDetails = self.langs['lang.details'];
    var langClaimNow = self.langs['Claim Now'];

    var sampleTpl = `
    <div class="pr_show threepage cpt5" data-promo_item_anchor="promo_item_16915">
        <div class="primage">
        <img src="https://player.staging.ole777idr.t1t.in/resources/images/promothumbnails/default_promo_cms_1.jpg?v=6.206.01.001?v=6.206.01.001">
        </div>
        <div class="title"> 【TEST PROMO】 </div>
        <div class="description">SEA GAMES</div>
        <div class="actions">
        <a href="javascript: void(0);" class="btn btn-sm btn-info btn-details-16915" data-toggle="collapse" onclick="Promotions.viewPromoDetailsAll(this, 161555);" id="16915" data-target="#promo_23_item_16915_detail">Detail</a>
        </div>
    </div>
    `;

    /// TODO,
    // if(file_exists($this->utils->getPromoThumbnails() . $promo_item['promoThumbnail']) && !empty($promo_item['promoThumbnail'])){
    //     $promoThumbnail = $this->utils->getPromoThumbnailRelativePath(FALSE) . $promo_item['promoThumbnail'];
    // }else{
    //     if(!empty($promo_item['promoThumbnail'])){
    //         $promoThumbnail = $this->utils->imageUrl('promothumbnails/' . $promo_item['promoThumbnail']);
    //     }else{
    //         $promoThumbnail = $this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg');
    //     }
    // }


    var actionsHyperlink = '';
    if( ! self.disabled_show_promo_detail_on_list){
        actionsHyperlink += `<a href="javascript: void(0);" class="btn btn-sm btn-info btn-details-${_promoCmsSettingId}" data-toggle="collapse"
            onclick="Promotions.viewPromoDetailsAll(this, ${_playerId});" id="${_promoCmsSettingId}"
            data-target="#promo_${_promoCategoryId}_item_${_promoCmsSettingId}_detail">${langDetails}</a>`;
    }

    if(self.enabled_request_promo_now_on_list){
        if( _display_apply_btn_in_promo_page){
            var switchStr = '';
            switchStr += (_allow_claim_promo_in_promo_page)? '1': '0';
            switchStr += (self.promo_auto_redirect_to_deposit_page)? '1': '0';
            switch( switchStr ){ // _allow_claim_promo_in_promo_page+ promo_auto_redirect_to_deposit_page
                case '00':
                    actionsHyperlink += `<a href="javascript: void(0);"
                                            class="btn btn-sm btn-info "
                                            data-promo-cms-setting-id="${_promoCmsSettingId}"
                                            onclick="checkPromo('${_promoCmsSettingId}', '${_playerId}', '${_claim_button_url}');">${langClaimNow}</a>`;
                    break;
                case '01':
                    var claim_button_text = '';
                    if( _claim_button_name.length > 0){
                        claim_button_text = _claim_button_name;
                    }else{
                        claim_button_text = langClaimNow;
                    }
                    actionsHyperlink += `<a href="${_claim_button_url}"
                                            class="btn btn-sm btn-info ">${claim_button_text}</a>`;
                    break;
                case '10':
                    actionsHyperlink += `<a href="javascript: void(0);"
                                            class="btn btn-sm btn-info "
                                            data-promo-cms-setting-id="${_promoCmsSettingId}"
                                            onclick="checkPromo('${_promoCmsSettingId}', '${_playerId}');">${langClaimNow}</a>`;
                    break;
                case '11':
                    actionsHyperlink += `<a href="javascript: void(0);"
                                            class="btn btn-sm btn-info "
                                            data-promo-cms-setting-id="${_promoCmsSettingId}"
                                            onclick="requestPromoNow('${_promoCmsSettingId}');">${langClaimNow}</a>`;
                    break;
            }

            // if(_allow_claim_promo_in_promo_page){
            //     if(self.promo_auto_redirect_to_deposit_page){
            //         // 11
            //     }else{
            //         // 10
            //     }// EOF if(self.promo_auto_redirect_to_deposit_page){...
            // }else{
            //     if(self.promo_auto_redirect_to_deposit_page){
            //         // 01
            //     }else{
            //         // 00
            //     }
            // }// EOF if(_allow_claim_promo_in_promo_page){...

        } // EOF if(_display_apply_btn_in_promo_page){...
    } // EOF if(self.enabled_request_promo_now_on_list){...


    var tpl = null;
    tpl = `
    <div class="pr_show threepage cpt5" data-promo_item_anchor="promo_item_${_promoCmsSettingId}">
        <div class="primage">
        <img src="${_promoThumbnail}">
        </div>
        <div class="title">【${_promoName}】</div>
        <div class="description">${_promoDescription}</div>
        <div class="actions">
            ${actionsHyperlink}
        </div> <!-- EOF .actions -->
    </div> <!-- EOF .pr_show.threepage.cpt5 -->
    `;
    return tpl;
}; // EOF tpl4promotionHeaderMobi
Promotions.tpl4promotionHeader = function(_promoName = '', _tag_as_new_flag = '0', _promoThumbnailUrl){
    var self = this;
    var sampleTpl = `
<div class="promotion-header">
    <h1 class="title-name"> Rolling Paradise (AE) <span class="badge-new">ใหม่</span>
    </h1>
    <img width="377" height="199" src="/resources/images/promothumbnails/promothumbnail-629641261fa59.jpg?v=6.206.01.001" />
</div> <!-- EOF .promotion-header -->
    `;

    var tpl = null;
    if(self.hidden_player_center_promotion_page_title_and_img){
        tpl = '';
    }else{
        var _langNew = self.langs['lang.new'];
        var _langFavourite = self.langs['Favourite'];
        var _langEndSoon = self.langs['End Soon'];

        var htmlBadgeNew = '';
        if(self.enabled_multiple_type_tags_in_promotions){
            switch(_tag_as_new_flag){
                case '0': // No Tag
                    htmlBadgeNew = '';
                    break;
                case '1': // New Tag
                    htmlBadgeNew = `<span class="badge-new multiple-tag-new">${_langNew}</span>`;
                    break;
                case '2': // Favourite Tag
                    htmlBadgeNew = `<span class="badge-new multiple-tag-favourite">${_langFavourite}</span>`;
                    break;
                case '3': // End-Soon Tag
                    htmlBadgeNew = `<span class="badge-new multiple-tag-endsoon">${_langEndSoon}</span>`;
                    break;
            }
        }else if(_tag_as_new_flag){
            htmlBadgeNew = `<span class="badge-new">${_langNew}</span>`;
        }

        // var _v = '';
        // if( $('.version_info').length > 0){
        //     var _split = $('.version_info').text().split('-');
        //     if( ! $.isEmptyObject(_split)){
        //         _v = $('.version_info').text().split('-')[0];
        //     }
        // }
        // if( _v.length == 0 ){
        //     var dateTime = new Date().getTime();
        //     _v = Math.floor(dateTime / 1000);
        // }

        _promoName = _promoName.toLowerCase().replace(/^[\u00C0-\u1FFF\u2C00-\uD7FF\w]|\s[\u00C0-\u1FFF\u2C00-\uD7FF\w]/g, function(letter) {
            return letter.toUpperCase();
        });

        tpl = `
        <div class="promotion-header">
            <h1 class="title-name"> ${_promoName} ${htmlBadgeNew}
            </h1>
            <img width="377" height="199" src="${_promoThumbnailUrl}" />
        </div> <!-- EOF .promotion-header -->
        `;
    } // EOF if(self.hidden_player_center_promotion_page_title_and_img){...

    return tpl;
}; // EOF tpl4promotionHeader

Promotions.tpl4promotionBodyMobi = function( _promoCategoryId
                                            , _promoCmsSettingId
                                            , _promoDetails4lestCo
                                            , _allow_claim_promo_in_promo_page
                                            , _iframeRequestPromoUri
                                            , _claim_button_url
                                            , _claim_button_name
){
    var self = this;

    var _playerId = self.playerId;
    var langPromoCountdownRemaining = self.langs['promo_countdown.Remaining'];
    var langClaimNow = self.langs['Claim Now'];

    var sampleTpl = `
    <div id="promo_23_item_16915_detail" class="collapse">
      <div class="panel-body">
        <!-- enabled_promo_period_countdown -->
        <div class="promo_period_countdown hide">
          <p>
            <span>Remaining time:</span>
            <span class="promo_period_countdown_txt"></span>
          </p>
        </div> <!-- EOF .promo_period_countdown -->
        <div class="lestCo">
          <h3>
            <strong>SEA GAMES EVENT 2023</strong>
          </h3>
          <p>
            <strong>Periode Promosi : 5 May – 17 May 2023 00:00 – 23:59 (GMT+8) <br>Berlaku Untuk : Semua Member OLE777 <br>
              <br>Mekanisme : </strong>
          </p>
          <ol class="promo-o-list">
            <li>Mencapai minimal total deposit IDR 10,000 untuk mengikuti promo ini.</li>
            <li>Pembayaran Bonus didasarkan pada pencapaian total deposit dan total medali yang diraih oleh Tim Indonesia di SEA Games 2023.</li>
            <li>Untuk mengikuti event promo ini, member harus mengisi Google Form. <br>
              <a rel="noreferrer noopener" href="https://forms.gle/YpJ3ewB5Wb31qgRv9" target="_blank">KLIK DI SINI</a> untuk berpartisipasi.
            </li>
          </ol>
          <figure class="wp-block-table">
            <table>
              <tbody>
                <tr class="tb-head-gold">
                  <td colspan="6">THIS IS A TEST</td>
                </tr>
                <tr class="tb-head">
                  <td>MEDALI</td>
                  <td colspan="2">MIN. <br>DEPOSIT </td>
                  <td colspan="2">BONUS</td>
                  <td>TURNOVER</td>
                </tr>
                <tr>
                  <td>BRONZE</td>
                  <td colspan="2">IDR <br>10,000 </td>
                  <td colspan="2">total <br>medali <br>x IDR 2 </td>
                  <td rowspan="3">5x</td>
                </tr>
                <tr>
                  <td>SILVER</td>
                  <td colspan="2">IDR <br>50,000 </td>
                  <td colspan="2">total <br>medali <br>x IDR 4 </td>
                </tr>
                <tr>
                  <td>GOLD</td>
                  <td colspan="2">IDR <br>100,000 </td>
                  <td colspan="2">total <br>medali <br>x IDR 8 </td>
                </tr>
              </tbody>
            </table>
          </figure>
          <p>
            <em>Angka yang tertera adalah dalam ribuan (000) . Contoh : Min Deposit IDR10,000 = IDR10,000,000 (sepuluh juta rupiah)</em>
          </p>
          <p>
            <strong>Syarat dan Ketentuan Promosi:</strong>
          </p>
          <ol>
            <li>Promo berlaku untuk semua member dengan akumulasi deposit IDR 10,000 ke atas mulai 5 Mei 2023 00:00 (GMT+8) – 17 Mei 2023 23:59 (GMT+8).</li>
            <li>Anggota bisa mendapatkan bonus berdasarkan pencapaian total deposit selama periode promo. <br>Contoh : Member memiliki total deposit IDR 50,000 selama periode promo, member berhak mendapatkan bonus medali SILVER dan Tim Indonesia meraih 80 medali perak. <br>Perhitungan Pembayaran Bonus : 80 medali perak x IDR 4 = IDR 320. </li>
            <li>Bonus akan dikreditkan secara otomatis ke akun member pada 19 Mei 2023 paling lambat pukul 18:00 (GMT+8)</li>
            <li>Bonus harus dipertaruhkan sebanyak 5x di semua permainan.</li>
            <li>Penawaran Bonus ini terbatas pada 1 (satu) Nama Pengguna, Nama Pemilik Akun, Alamat, IP, Perangkat dan Browser, jika Anggota melakukan pelanggaran dalam penggunaan Bonus ini, maka OLE777 berhak untuk membatalkan Bonus bersama dengan Anggota kemenangan.</li>
            <li>Promosi ini tunduk pada Syarat dan Ketentuan Umum OLE777.</li>
          </ol>
        </div> <!-- EOF .lestCo -->
        <div class="action">
          <a href="https://player.staging.ole777idr.t1t.in/iframe_module/request_promo/16915" data-promo-cms-setting-id="16915" class="btn btn-sm btn-info claim-promo btn-claim-16915">Claim Now</a>
        </div> <!-- EOF .action -->
      </div>
    </div>
    `;

    var htmlAction = '';
    var switchStr = '';
    switchStr += (_allow_claim_promo_in_promo_page)? '1': '0';
    switchStr += (self.promo_auto_redirect_to_deposit_page)? '1': '0';
    switch(switchStr){ // allow_claim_promo_in_promo_page : promo_auto_redirect_to_deposit_page
        case '00':
            htmlAction += `<a href="javascript: void(0);"
            class="btn btn-sm btn-info "
            data-promo-cms-setting-id="${_promoCmsSettingId}"
            onclick="checkPromo('${_promoCmsSettingId}', '${_playerId}', '${_claim_button_url}');">${langClaimNow}</a>`;
            break;
        case '01':
            var claim_button_text = '';
            if( _claim_button_name.length > 0){
                claim_button_text = _claim_button_name;
            }else{
                claim_button_text = langClaimNow;
            }
            htmlAction += `<a href="${_claim_button_url}"
            data-promo-cms-setting-id="${_promoCmsSettingId}"
            class="btn btn-sm btn-info btn-claim-${promoCmsSettingId}">${claim_button_text}</a>`;
            break;
        case '10':
            htmlAction += `<a href="javascript: void(0);"
            class="btn btn-sm btn-info "
            data-promo-cms-setting-id="${_promoCmsSettingId}"
            onclick="checkPromo('${_promoCmsSettingId}', '${_playerId}');">${langClaimNow}</a>`;
            break;
        case '11':
            htmlAction += `<a href="${_iframeRequestPromoUri}"
            data-promo-cms-setting-id="${_promoCmsSettingId}"
            class="btn btn-sm btn-info claim-promo btn-claim-${_promoCmsSettingId}">${langClaimNow}</a>`;
            break;
    } // EOF switch(switchStr){...


    var tpl = `
    <div id="promo_${_promoCategoryId}_item_${_promoCmsSettingId}_detail" class="collapse">
        <div class="panel-body">
            <!-- enabled_promo_period_countdown -->
            <div class="promo_period_countdown hide">
                <p><span>${langPromoCountdownRemaining}:</span> <span class="promo_period_countdown_txt"></span></p>
            </div>
            <div class="lestCo">
                ${_promoDetails4lestCo}
            </div>
            <div class="action">
                ${htmlAction}
            </div>
        </div> <!-- EOF .panel-body -->
    </div> <!-- EOF #promo_${_promoCategoryId}_item_${_promoCmsSettingId}_detail -->
    `;
    return tpl;
};
Promotions.tpl4promotionBody = function(promoDescription, promoCmsSettingId, playerId, display_apply_btn_in_promo_page){
    var self = this;

    var langViewDetails = self.langs['View Details'];
    var langClaimNow = self.langs['Claim Now'];

    var html_btn_viewPromoDetailsAllPromoItem = '';
    if(!self.disabled_show_promo_detail_on_list){
        html_btn_viewPromoDetailsAllPromoItem = `<a href="javascript: void(0);" class="btn viewPromoDetailsAllPromoItem" id="${promoCmsSettingId}" data-playerid="${playerId}">${langViewDetails}</a>`;
    }

    var html_btn_requestPromoNowItem = '';
    if(self.enabled_request_promo_now_on_list){
        if(!!display_apply_btn_in_promo_page) { // if(!!$key['display_apply_btn_in_promo_page']) :
            html_btn_requestPromoNowItem += '<a href="javascript: void(0);" class="btn requestPromoNowItem"';

            if(self.promo_auto_redirect_to_deposit_page){
                html_btn_requestPromoNowItem += ` onclick="checkPromo('${promoCmsSettingId}', '${playerId}');" `;
            }else{
                html_btn_requestPromoNowItem += ` onclick="requestPromoNow('${promoCmsSettingId}');" `;
            } // EOF if(promo_auto_redirect_to_deposit_page){...
            html_btn_requestPromoNowItem += `> `;
            html_btn_requestPromoNowItem += `${langClaimNow}`;
            html_btn_requestPromoNowItem += ` </a> `;
        } // EOF if(!!display_apply_btn_in_promo_page) {...
    } // EOF if(self.enabled_request_promo_now_on_list){...


    html_btn_viewPromoDetailsAllPromoItem += html_btn_requestPromoNowItem;


    var tpl = `
<div class="promotion-body clearfix" data-promocmssettingid="${promoCmsSettingId}">
    <div class="col-xs-8">
        <p> ${promoDescription} </p>
    </div>
    <div class="col-xs-4 text-right">
        ${html_btn_viewPromoDetailsAllPromoItem}
    </div>
</div> <!-- EOF .promotion-body -->
    `;
    return tpl;
}; // EOF tpl4promotionBody
//
Promotions.tpl4catNoPromoMobi = function(){
    var self = this;
    var langNorec = self.langs['lang.norec'];

    var tpl = `<div class="no_data">
            <center>${langNorec}</center>
        </div>`;
        return tpl;
}; // EOF tpl4catNoPromoMobi
// .tab-content > @.tpl4catNoPromo
Promotions.tpl4catNoPromo = function(){
    var self = this;
    var langCatNoPromo = self.langs['cat.no.promo'];

    var tpl = `<br><br><br><center><p>${langCatNoPromo}</p></center>`;
    return tpl;
}; // EOF tpl4catNoPromo
//
//
Promotions.tpl4tabpanelRow = function(_promoCategoryId, html){
    // tpl4catNoPromo(): lang('cat.no.promo')
    // tpl4promotionContent(): .col-sm-6>.promotion-content
    var tpl = `
    <div role="tabpanel" class="tab-pane" id="tab${_promoCategoryId}">
        <div class="row">
            ${html}
        </div>
    </div>

    `;
    return tpl;
}; // EOF tpl4catNoPromo


Promotions._do_Uri = function (beforeSendCB, completeCB, theUri, postData){
    var _this = this;

    var _doAbort = -1; // default
    if( typeof(beforeSendCB) === 'undefined'){
        beforeSendCB = function(jqXHR, settings){};
    }
    if( typeof(completeCB) === 'undefined'){
        completeCB = function(jqXHR, textStatus){};
    }

    if( typeof(theUri) === 'undefined'){
        // theUri = _this.URIs.getRegSettings;
        _doAbort = 1; // Not setup uri.
        return
    }
    if( typeof(postData) === 'undefined'){
        postData = {};
    }
    // postData.api_key =  _this.api_key;

    // postData.withCredentials = true;
    var _xhrFields = {};
    if(postData.withCredentials == true){
        // for cross-domain requests
        _xhrFields.withCredentials = true;
    }

    if(_doAbort === -1){
        // Non verification failed, keep in default.
        var _doAbort = 0; // assign pass
    }
    if(_doAbort == 0){
        var jqXHR = $.ajax({
            type: 'POST',
            url: theUri,
            data: postData,
            xhrFields: _xhrFields,
            beforeSend: function (jqXHR, settings) {
                jqXHR._data = postData;
                jqXHR._url = theUri;

                // targetBtn$El.button('loading');
                var cloned_arguments = Array.prototype.slice.call(arguments);
                beforeSendCB.apply(_this, cloned_arguments);
                _this.safelog('_do_Uri.beforeSend.cloned_arguments:', cloned_arguments);
            },
            complete: function (jqXHR, textStatus) {
                // targetBtn$El.button('reset');
                var cloned_arguments = Array.prototype.slice.call(arguments);
                completeCB.apply(_this, cloned_arguments);
            }
        });
    }else{
        var _resp = "doAbort="+ _doAbort;
        beforeSendCB.apply(_this, [ _resp ]);
        var jqXHR = $.Deferred();
        jqXHR.then(function() {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            completeCB.apply(_this, cloned_arguments);
        });
        setTimeout(function(){
            jqXHR.resolve.apply( _this, [ _resp ]);
        }, 100);
    }

    // jqXHR.done(function (data, textStatus, jqXHR) {
    //
    // });
    return jqXHR;
}; // EOF _do_Uri

Promotions.initDebugLog = function(){
    var self = this;
    // detect dbg=1 in get params for self.safelog output.
    var query = window.location.search.substring(1);
    var qs = self.parse_query_string(query);
    if ('dbg' in qs
        && typeof (qs.dbg) !== 'undefined'
        && qs.dbg
    ) {
        self.debugLog = true;
    } else {
        self.debugLog = false;
    }
} // EOF initDebugLog
