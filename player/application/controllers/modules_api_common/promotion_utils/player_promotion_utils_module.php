<?php

trait player_promotion_utils_module {
	private function getPlayerPromo( $player_id // #1
                                    , $promo_type=null // #2
                                    , $promo_cms_setting_id=null // #3
                                    , $promo_category_id=null // #4
                                    , $currency=null // #5
                                    , $pagination=[] // #6
                                    , $amount=null // #7, deposit amount for deposit promo
    ) {
        $this->load->model(['promorules']);
		$result_promo_list = [];
		$result_promo_list_with_pagination = [];
		$promo_cms_list = $this->utils->getPlayerPromo("allpromo", $player_id, $promo_cms_setting_id, $promo_category_id, $pagination);
		// $promo_thumbnail_path = $this->utils->getPromoThumbnailRelativePath();
		if(!empty($promo_cms_list)) {

            $_post = []; // for Mock via POST
            $notnull_mock = $this->utils->getPromotionMock($_post, $this->utils->getConfig('promotion_mock') );

			foreach ($promo_cms_list['promo_list'] as $promo_cms_key => $promo_cms_item) {
				// if(!is_null($promo_type)) {
				// 	if($promo_type != 'deposit' && $promo_type != 'task') continue;
				// 	if($promo_type == 'deposit' && $promo_cms_item['promorule']['promoType'] != '0') continue;
				// 	if($promo_type == 'task' && $promo_cms_item['promorule']['promoType'] != '1') continue;
				// }

				$entry = [];
				$current_lang = $this->playerapi_lib->getIsoLang($this->indexLanguage);
				$multi_lang = $this->promoItemMultiLangFields($promo_cms_item, $current_lang);

                /// TODO, Applyable
                $promoCmsSettingId = $promo_cms_item['promoCmsSettingId'];
                $promorule = $this->promorules->getPromoruleByPromoCms($promoCmsSettingId);; /// @todo
                $preapplication = false;
                $playerPromoId=null;
                $dry_run=true;


                if( $amount !== null ){
                    $deposit_amount = $amount;
                    $extra_info=['debug_log'=>'', 'mock'=>$notnull_mock, 'depositAmount' => $deposit_amount];
                    list($success, $message) = $this->promorules->checkOnlyPromotionBeforeDeposit( $player_id // #1
                                                                                                , $promorule // #2
                                                                                                , $promoCmsSettingId // #3
                                                                                                , $preapplication // #4
                                                                                                , $playerPromoId // #5
                                                                                                , $extra_info // #6
                                                                                                , $dry_run // #7
                                                                                            );
                    $message=lang($message);
                    $this->utils->debug_log('--- getPlayerPromo.getPlayerPromoApplyable.56.result', $success, $message, $extra_info);
                    $entry['isApplyable'] = !empty($success)? true: false;
                }// EOF if( $amount !== null ){...
				$entry['currency'] = strtoupper($currency ?: $this->currency);
				$entry['promoId'] = $promo_cms_item['promoCmsSettingId'];
				$entry['promoCategory'] = $promo_cms_item['promo_category'];
				$entry['promoName'] = $multi_lang['promoName'];
				$entry['promoDescription'] = $multi_lang['promoDescription'];
				$entry['promoDetails'] = $multi_lang['promoDetails'];
				$entry['promoThumbnail'] = $multi_lang['promoThumbnail'];
				$entry['promoCode'] = $promo_cms_item['promo_code'];
				$entry['isNew'] = (bool)$promo_cms_item['tag_as_new_flag'];
				$entry['promoTag'] = (is_null($promo_cms_item['tag_as_new_flag']))?0:$promo_cms_item['tag_as_new_flag'];//1:new, 4:hot, 0:not tag
				$entry['startAt'] = $this->playerapi_lib->formatDateTime($promo_cms_item['promorule']['applicationPeriodStart']);
				$entry['expiredAt'] = $this->playerapi_lib->formatDateTime($promo_cms_item['promorule']['hide_date']);

				$entry['extra'] = !empty($promo_cms_item['custom_info'])?$promo_cms_item['custom_info']:null;
				$entry['enabledCountdown'] = (bool)$promo_cms_item['promo_period_countdown'];
				// $entry['fixedBonus'] = $promo_cms_item['promorule']['bonusAmount'];
				// $entry['maxBonusAmount'] = $promo_cms_item['promorule']['maxBonusAmount'];
				// $origin_promo_type = $promo_cms_item['promorule']['promoType'];
				// $entry['type'] = ($origin_promo_type == '1') ? '12' : '11';
				// Promo type, CAMPAIGN_DEPOSIT(11, “deposit”), CAMPAIGN_TASK(12, “task”), CAMPAIGN_RESCUE(13, “rescue”), CAMPAIGN_LOGIN(14, “login”), CAMPAIGN_SPECIFIC_DEPOSIT(15, “s-deposit”), CAMPAIGN_NEXT_DAY(16, “next-day”)
				// if($origin_promo_type == '0') {
				// 	$entry['taskType'] = '2';
				// 	Task type, only available for campaign task. EmailVerificationBonus(0), SmsVerificationBonus(1), IdentityVerificationBonus(2)
				// 	Enum:[ 0, 1, 2 ]
				// }
				$entry['autoJoin'] = false;
				$entry['applyButtonEnabled'] = (bool)$promo_cms_item['display_apply_btn_in_promo_page'];
				$entry['displayType'] = $this->playerapi_lib->matchOutputPromoDisplayType($promo_cms_item['hide_on_player']);
				// $entry['bonusAutoRelease'] = false;
				// $entry['currency'] = 'VND';
				// $entry['enabled'] = true;
				// $entry['minBonusAmount'] = 1;
				// $result_promo_list[] = $entry;
				$entry['sort'] = 0;
				$result_promo_list[$promo_cms_key] = $entry;
			}
		}

		if(!empty($pagination)){
			$result_promo_list_with_pagination = $promo_cms_list['pagination'];
			$result_promo_list_with_pagination['list'] = array_values($result_promo_list);
			// $result_promo_list_with_pagination['list'] = $result_promo_list;
			// $result_promo_list_with_pagination['pagination'] = $promo_cms_list['pagination'];
			return $result_promo_list_with_pagination;
		}else{
			return $result_promo_list;
		}
	}

	public function promoItemMultiLangFields($promo_item, $current_lang, $onlyCurrentLang = false) {
		$this->CI->load->model(['cms_model']);
		$this->utils->debug_log(__METHOD__, 'current_lang', $current_lang, $promo_item);
		$multiPromoItems = @json_decode($promo_item['promo_multi_lang'], true);
		$output_language = $this->playerapi_lib->matchOutputLanguage($current_lang);
		$check_mobile = false;
		$promoName = $promo_item['promoName'];
		$promoDescription = $promo_item['promoDescription'];
		$promoDetails = $promo_item['promoDetails'];
		$promoThumbnail = $this->playerapi_lib->ci->utils->getPromoThumbnailsUrl($promo_item['promoThumbnail'], $check_mobile);
		$ret = [
			"promoName"			=> [['lang'=>$output_language, 'content'=>$promoName]],
			'promoDescription'	=> [['lang'=>$output_language, 'content'=>$promoDescription]],
			'promoDetails'		=> [['lang'=>$output_language, 'content'=>$promoDetails]],
			'promoThumbnail'	=> [['lang'=>$output_language, 'content'=>$this->playerapi_lib->ci->utils->getSystemUrl('player', $promoThumbnail)]]
		];

		$multi_lang = $multiPromoItems['multi_lang'];
        if(empty($multi_lang) || !$this->utils->isEnabledFeature('enable_multi_lang_promo_manager')){
            return $ret;
        }
		if ($current_lang == 'cn') {
			$current_lang = language_function::PROMO_SHORT_LANG_CHINESE;
		}
        if(empty($current_lang)){
            $current_lang = language_function::PROMO_SHORT_LANG_ENGLISH;
        }

		$promo_lang_data = $multi_lang[$current_lang];

        if ($promo_lang_data['promo_title_'.$current_lang] != null) {
            $newPromoName = $promo_lang_data['promo_title_'.$current_lang];
            $ret['promoName'][0]['content'] = $newPromoName ?: $promoName;
        }

        if ($promo_lang_data['short_desc_'.$current_lang] != null) {
            $newPromoDesc = $promo_lang_data['short_desc_'.$current_lang];
            $ret['promoDescription'][0]['content'] = $newPromoDesc ?: $promoDescription;
        }

        if ($promo_lang_data['details_'.$current_lang] != null) {
            $newPromoDetails = $promo_lang_data['details_'.$current_lang];
            $newPromoDetails = $newPromoDetails ?: $promoDetails;
            $ret['promoDetails'][0]['content'] = $this->filterControlCharacters(html_entity_decode($this->CI->cms_model->decodePromoDetailItem($newPromoDetails)));
        }

        if ($promo_lang_data['banner_'.$current_lang] != null) {
            $newPromothumbnail = $promo_lang_data['banner_'.$current_lang];
            $ret['promoThumbnail'][0]['content'] = $this->playerapi_lib->ci->utils->getPromoThumbnailsUrl($newPromothumbnail, $check_mobile);
        }

		if ($onlyCurrentLang){
			return $ret;
		}

		if (!empty($multi_lang)) {
			$ret = [
				'promoName' => array_map(function ($promo_lang, $items) {
					return [
						'lang' => $this->playerapi_lib->matchOutputLanguage($promo_lang),
						'content' => $items['promo_title_'.$promo_lang]
					];
				}, array_keys($multi_lang), $multi_lang),
				'promoDescription' => array_map(function ($promo_lang, $items) {
					return [
						'lang' => $this->playerapi_lib->matchOutputLanguage($promo_lang),
						'content' => $items['short_desc_'.$promo_lang]
					];
				}, array_keys($multi_lang), $multi_lang),
				'promoDetails' => array_map(function ($promo_lang, $items) {
					$promoDetails = html_entity_decode($this->CI->cms_model->decodePromoDetailItem($items['details_'.$promo_lang]));
					return [
						'lang' => $this->playerapi_lib->matchOutputLanguage($promo_lang),
						'content' => $this->filterControlCharacters($promoDetails)
					];
				}, array_keys($multi_lang), $multi_lang),
				'promoThumbnail' => array_map(function ($promo_lang, $items) use ($check_mobile) {
					$promoThumbnail = $this->playerapi_lib->ci->utils->getSystemUrl('player', $this->playerapi_lib->ci->utils->getPromoThumbnailsUrl($items['banner_'.$promo_lang], $check_mobile));
					return [
						'lang' => $this->playerapi_lib->matchOutputLanguage($promo_lang),
						'content' => $promoThumbnail
					];
				}, array_keys($multi_lang), $multi_lang)
			];
		}

		return $ret;
	}

	/**
	* Summary of checkRedemptionCodeConfig
	* config enable_redemption_code_system bool
	* config enable_static_redemption_code_system bool
	* config enable_redemption_code_system_in_playercenter bool
	* config redemption_code_promo_cms_id int from promo cms id
	* @return bool
	*/
	public function isRedemptionCodeEnabled(){
		if(!($this->utils->getConfig('enable_redemption_code_system')||$this->utils->getConfig('enable_static_redemption_code_system'))){
			return false;
		}
		if(empty($this->utils->getConfig('fallback_currency_for_redemption_code'))){
			return false;
		}
		return true;
	}

	public function getRedemptioncodePromocmsid($currency, $type = 'stander'){
		// $config['fallback_currency_for_redemption_code']['stander'] = [
		// 	"cny" =>["cms_id" => 9,],
		// ];
		$setting = $this->utils->getConfig('fallback_currency_for_redemption_code');
		if(empty($setting)){
			return false;
		}
		if(!array_key_exists($type, $setting)){
			return false;
		}
		$currency = strtolower($currency);
		if (array_key_exists($currency, $setting[$type])) {
			return $this->utils->safeGetArray($setting[$type][$currency], 'cms_id',  false);
		}
		return false;
	}

	public function filterControlCharacters($input) {
		$filteredInput = filter_var($input, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
		return $filteredInput;
	}
}
