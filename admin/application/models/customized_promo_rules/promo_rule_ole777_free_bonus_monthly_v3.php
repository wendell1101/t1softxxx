<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';
require_once dirname(__FILE__) .'/promo_rule_ole777_free_bonus_monthly.php';

/**
 *
 * 存送优惠
 *
 * OGP-31240
 *
 * 每月m-n号内，允许玩家申请一次免费奖金

condition:
{
    "class": "promo_rule_ole777_free_bonus_monthly_v3",
    "allowed_date":{
        "start": "21",
        "end": "end_of_the_month"
    },
	"// QA Testing": "(can add specific date):",
    "// verified_phone": true,
    "// verified_email": true,
    "// completed_player_info": true,
	"// force_date": true,
	"// last_month_deposit_fromDatetime": "2020-03-10 00:00:00",
	"// last_month_deposit_toDatetime": "2020-03-17 23:59:59",
	"// EOF": "QA",
    "bonus_settings":{
        "70" : { "bonus_amount": 77
				, "last_month_deposit_amont":1000
		},
        "71" : { "bonus_amount": 177
				, "last_month_deposit_amont":2000
		},
        "72" : { "bonus_amount": 277
				, "last_month_deposit_amont":5000
		},
        "73" : { "bonus_amount": 477
				, "last_month_deposit_amont":10000
		},
        "74" : { "bonus_amount": 577
				, "last_month_deposit_amont":20000
		},
        "75" : { "bonus_amount": 977
				, "last_month_deposit_amont":60000
		},
        "76" : { "bonus_amount": 1477
				, "last_month_deposit_amont":100000
		},
        "77" : { "bonus_amount": 2777
				, "last_month_deposit_amont":200000
		},
        "78" : { "bonus_amount": 4777
				, "last_month_deposit_amont":600000
		},
        "79" : { "bonus_amount": 6777
				, "last_month_deposit_amont":1000000
		},
        "80" : { "bonus_amount": 9777
				, "last_month_deposit_amont":2000000
		}
    }
}

Promo Manager Mock For Class:
{
    "get_date_type_now":"2021-09-22 12:23:55", // use the input,"get_date_type_now" of the form
	"sum_deposit_amount":12345 // total deposit of last month, use the input,"sum_deposit_amount" of the form
}
 *
 *
 *
 */
class Promo_rule_ole777_free_bonus_monthly_v3 extends Promo_rule_ole777_free_bonus_monthly{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_free_bonus_monthly_v3';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => TRUE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){


		$bonus_settings=$description['bonus_settings'];
		$allowed_date=$description['allowed_date'];
		$force_date = empty($description['force_date']) ? false: true;
		$last_month_deposit_fromDatetime = empty($description['last_month_deposit_fromDatetime'])? '': $description['last_month_deposit_fromDatetime'];
		$last_month_deposit_toDatetime = empty($description['last_month_deposit_toDatetime'])? '': $description['last_month_deposit_toDatetime'];

        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $completed_player_info = $this->completed_player_info($description);
        $check_last_month_deposit = $this->check_last_month_deposit($description);
		$isAllowedDate = $this->isAllowedDate($allowed_date);
		if($isAllowedDate){
            if($verified_email){
                if($verified_phone){
                    if($completed_player_info){
                        $thegetMaxButNotOverSetting = $this->getMaxButNotOverSetting($bonus_settings, $force_date, $last_month_deposit_fromDatetime, $last_month_deposit_toDatetime, $check_last_month_deposit);
                    }else{
                        $thegetMaxButNotOverSetting = ['msg4dev' => 'not complete player info', 'issueCaseNo' => self::_NOT_COMPLETE_PLAYER_INFO]; // notCompletePlayerInfo
                    }
                }else{
                    $thegetMaxButNotOverSetting = ['msg4dev' => 'not validate phone', 'issueCaseNo' => self::_NOT_VALIDATE_PHONE]; // notValidatePhone
                }
            }else{
                $thegetMaxButNotOverSetting = ['msg4dev' => 'not validate email', 'issueCaseNo' => self::_NOT_VALIDATE_EMAIL]; // notValidateEmail
            }
		}else{
			$thegetMaxButNotOverSetting = [];
			$thegetMaxButNotOverSetting['msg4dev'] = 'not met by Allowed Date.';
			$thegetMaxButNotOverSetting['issueCaseNo'] = self::_NOT_RIGHT_DATE;
		}
		$issueCaseNo = $thegetMaxButNotOverSetting['issueCaseNo'];
		$this->appendToDebugLog('issueCaseNo in runBonusConditionChecker():', ['issueCaseNo'=>$issueCaseNo]);

		if($issueCaseNo == self::_GOT_MATCHING_SETTING){
			$result = parent::runBonusConditionChecker($description, $extra_info, $dry_run);
		}else{
			// handle some issue cases.
			$success = false;
			$issueCaseNo = $thegetMaxButNotOverSetting['issueCaseNo'];
			$errorMessageLang = $this->getMessageLangByIssueCaseNo($issueCaseNo);
			$result = ['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
		}

		return $result;
	}

	/**
	 * generate withdrawal condition
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
	 */
	protected function generateWithdrawalCondition($description, &$extra_info, $dry_run){
        return $this->returnUnimplemented();
	}

    /**
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateTransferCondition($description, &$extra_info, $dry_run){
        return $this->returnUnimplemented();
    }

	/**
	 * release bonus
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
	 */
	protected function releaseBonus($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;
		$bonus_amount=0;

		$bonus_settings=$description['bonus_settings'];
		$allowed_date=$description['allowed_date'];

		$force_date = empty($description['force_date']) ? false: true;
		$last_month_deposit_fromDatetime = empty($description['last_month_deposit_fromDatetime'])? '': $description['last_month_deposit_fromDatetime'];
		$last_month_deposit_toDatetime = empty($description['last_month_deposit_toDatetime'])? '': $description['last_month_deposit_toDatetime'];

        $verified_phone = $this->isVerifiedPhone($description);
        $verified_email = $this->isVerifiedEmail($description);
        $completed_player_info = $this->completed_player_info($description);
        $check_last_month_deposit = $this->check_last_month_deposit($description);
		$isAllowedDate = $this->isAllowedDate($allowed_date);
		if($isAllowedDate){
            if($verified_email){
                if($verified_phone){
                    if($completed_player_info){
                        $thegetMaxButNotOverSetting = $this->getMaxButNotOverSetting($bonus_settings, $force_date, $last_month_deposit_fromDatetime, $last_month_deposit_toDatetime, $check_last_month_deposit);
                    }else{
                        $thegetMaxButNotOverSetting = ['msg4dev' => 'not complete player info', 'issueCaseNo' => self::_NOT_COMPLETE_PLAYER_INFO]; // notCompletePlayerInfo
                    }
                }else{
                    $thegetMaxButNotOverSetting = ['msg4dev' => 'not validate phone', 'issueCaseNo' => self::_NOT_VALIDATE_PHONE]; // notValidatePhone
                }
            }else{
                $thegetMaxButNotOverSetting = ['msg4dev' => 'not validate email', 'issueCaseNo' => self::_NOT_VALIDATE_EMAIL]; // notValidateEmail
            }
		}else{
			$thegetMaxButNotOverSetting = [];
			$thegetMaxButNotOverSetting['msg4dev'] = 'not met by Allowed Date.';
			$thegetMaxButNotOverSetting['issueCaseNo'] = self::_NOT_RIGHT_DATE;
		}

		$issueCaseNo = $thegetMaxButNotOverSetting['issueCaseNo'];
		if($issueCaseNo === self::_GOT_MATCHING_SETTING){
			$success = true;
			$errorMessageLang = null;
			$bonus_amount = $thegetMaxButNotOverSetting['bonus_amount'];
		}else{
			$errorMessageLang = $this->getMessageLangByIssueCaseNo($issueCaseNo);
		}

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount];
		return $result;
	} // EOF releaseBonus

	/**
	 * Get the error Message by the issue case no.
	 *
	 * @param integer $issueCaseNo The issue No.
	 * @return string $errorMessageLang The text string.
	 */
	function getMessageLangByIssueCaseNo($issueCaseNo){
		switch($issueCaseNo){
			case(self::_GOT_MATCHING_SETTING):
				$errorMessageLang = '';
				break;
			case(self::_BONUS_SETTINGS_IS_EMPTY):
			case(self::_NOT_MET_THE_LOWEST_SETTING): // ok
				$errorMessageLang = lang('notify.no_settings_are_reached');
				break;
			case(self::_NOT_RIGHT_GROUP_LEVEL):
				// The promo detail will display "Your player level is not valid in this promo", while handle by the edit promo rule page.
				// The promo detail will display "Not right group level" handle by this class, and Not found the level in bonus_settings.
				$errorMessageLang = lang('Not right group level');
				break;
			case(self::_NOT_RIGHT_DATE):
				$errorMessageLang = lang('Not right date'); //ok
				break;
            case(self::_NOT_VALIDATE_PHONE):
                $errorMessageLang = lang('promo.rule_is_player_verified_mobile'); //ok
                break;
            case(self::_NOT_VALIDATE_EMAIL):
                $errorMessageLang = lang('promo.rule_is_player_verified_email'); //ok
                break;
            case(self::_NOT_COMPLETE_PLAYER_INFO):
                $errorMessageLang = lang('notify.93'); //ok
                break;

		}
		return $errorMessageLang;
	} // EOF getMessageLangByIssueCaseNo

	/**
	 * detect the date for the setting,"allowed_date" of JSON
	 *
	 * @param array $allowed_date The allowed_date array after json decode.
	 * "allowed_date":{
	 *         "start": "21",
	 *         "end": "25"
	 *     },
	 * @return boolean If true, it means currect data is allowed.
	 */
	function isAllowedDate($allowed_date){
		$today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }
		
		// Y-m-d
		$d=new DateTime($today);
		$currentDate=$d->format('Y-m-d');

		if(!empty($allowed_date['from_date']) && !empty($allowed_date['end_date'])){
			// date range
			$minDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['from_date']));
			$maxDate=$this->utils->formatDateForMysql(new DateTime($allowed_date['end_date']));
		}else{
			// repeat day range
			if(!empty($allowed_date['start']) && !empty($allowed_date['end']) && ($allowed_date['end'] == 'end_of_the_month')){
				$minDate = $d->format('Y-m-').$allowed_date['start'];
				$maxDate = $d->format('Y-m-t');
			}else{
				$minDate = $d->format('Y-m-').$allowed_date['start'];
				$maxDate = $d->format('Y-m-').$allowed_date['end'];
			}
		}

		$isAllowedRelease = null;
		if($currentDate>=$minDate && $currentDate<=$maxDate){
			$isAllowedRelease = true;
		}else{
			$isAllowedRelease = false;
			// $errorMessageLang = lang('Not right date');
		}

		$this->appendToDebugLog('get bonus setting', ['isAllowedRelease:' => $isAllowedRelease
			, 'currentDate:' => $currentDate
			, '$minDate:' => $minDate
			, '$maxDate:' => $maxDate
		 ] );

		return $isAllowedRelease;
	}// EOF isAllowedDate

	/// for confirm the issue cases by No.
	// Just greater than zero and unique in the section, the current number  referenced to line.
	const _GOT_MATCHING_SETTING = 0;
	const _NOT_MET_THE_LOWEST_SETTING = 323;
	const _NOT_RIGHT_GROUP_LEVEL = 324;
	const _BONUS_SETTINGS_IS_EMPTY = 325;
	const _NOT_RIGHT_DATE = 326;
	const _NOT_VALIDATE_PHONE = 327;
	const _NOT_VALIDATE_EMAIL = 328;
	const _NOT_COMPLETE_PLAYER_INFO = 329;
	/**
	 * Get the setting by the deposit amount of last month from bonus_settings of JSON.
	 *
	 * @param array $bonus_settings The bonus_settings element after json decode.
	 * @param boolean $force_date For QA test the deposit amount of last month,
	 * 					If it is true, the total deposit amount will be from $last_month_deposit_fromDatetime to $last_month_deposit_toDatetime.
	 * @param string $last_month_deposit_fromDatetime For QA test the deposit amount. the default is the begin of last month.
	 * @param string $last_month_deposit_toDatetime For QA test the deposit amount. the default is the end of last month.
	 * @return void
	 */
	function getMaxButNotOverSetting( $bonus_settings
								, $force_date = false
								, $last_month_deposit_fromDatetime = ''
								, $last_month_deposit_toDatetime = ''
                                , $check_last_month_deposit = true
	){
		$result = [];

		$endAt=$this->utils->getNowForMysql();
		$levelId=$this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);

		if(array_key_exists($levelId, $bonus_settings)){
			$setting = $bonus_settings[$levelId];

			if($check_last_month_deposit){
                // total deposit of last month
                $first_datetime_of_last_month = $this->callHelper('get_date_type', [ self::DATE_LAST_MONTH_START ] ); // $this->get_date_type
                $end_datetime_of_last_month = $this->callHelper('get_date_type', [ self::DATE_LAST_MONTH_END ] ); // $this->get_date_type
                $from_datetime = $first_datetime_of_last_month;
                $to_datetime = $end_datetime_of_last_month;

                if( ! empty($force_date) ){
                    if( ! empty($last_month_deposit_fromDatetime) ){
                        if( $this->utils->validateDate($last_month_deposit_fromDatetime) ){
                            $from_datetime = $last_month_deposit_fromDatetime;
                        }else{
                            $this->appendToDebugLog('[ERR]last_month_deposit_toDatetime validateDate Failed:',['last_month_deposit_fromDatetime:' => $last_month_deposit_fromDatetime]);
                        }
                    }
                    if( ! empty($last_month_deposit_toDatetime) ){
                        if( $this->utils->validateDate($last_month_deposit_toDatetime) ){
                            $to_datetime = $last_month_deposit_toDatetime;
                        }else{
                            $this->appendToDebugLog('[ERR]last_month_deposit_toDatetime validateDate Failed:',['last_month_deposit_toDatetime:' => $last_month_deposit_toDatetime]);
                        }
                    }
                } // EOF if( ! empty($force_date) ){...
                $min_amount = 0;

                $last_month_deposit = $this->callHelper('sum_deposit_amount',[$from_datetime, $to_datetime, $min_amount]); // $this->sum_deposit_amount()
                $last_month_deposit = empty($last_month_deposit) ? 0 : $last_month_deposit;
                $this->appendToDebugLog('last_month_deposit with sum_deposit_amount():', ['last_month_deposit'=>$last_month_deposit, 'from_datetime'=>$from_datetime, 'to_datetime'=>$to_datetime]);

                if(!empty($setting['last_month_deposit_amont'])){
                    if( $setting['last_month_deposit_amont'] > $last_month_deposit ){
                        // The deposit amount last month did Not reach the setting, last_month_deposit_amont.
                        if( ! empty($bonus_settings) ){
                            $theMaxButNotOverSetting = [];
                            array_walk($bonus_settings, function($theSetting, $theLevelId) use (&$theMaxButNotOverSetting, $last_month_deposit){
                                // $theSetting['last_month_deposit_amont']
                                if( empty($theMaxButNotOverSetting) ){
                                    $theMaxButNotOverSetting = $theSetting;
                                }
                                if($last_month_deposit >= $theSetting['last_month_deposit_amont']){
                                    if($theSetting['last_month_deposit_amont'] > $theMaxButNotOverSetting['last_month_deposit_amont']){
                                        $theMaxButNotOverSetting = $theSetting;
                                    }
                                }
                            }); // EOF array_walk()
                            $setting = $theMaxButNotOverSetting; // override
                        }else{
                            $result['msg4dev'] = 'bonus_settings is empty';
                            // same as _NOT_MET_THE_LOWEST_SETTING
                            $result['issueCaseNo'] = self::_BONUS_SETTINGS_IS_EMPTY; // 265; // _BONUS_SETTINGS_IS_EMPTY
                            // $errorMessageLang = lang('bonus_settings is empty');
                        } // EOF if( ! empty($bonus_settings) ){...
                    }

                    // To filter that is not reach the lowest setting.
                    if($setting['last_month_deposit_amont'] > $last_month_deposit ){
                        $setting = [];
                        $result['msg4dev'] = 'not met the lowest setting';
                        $result['issueCaseNo'] = self::_NOT_MET_THE_LOWEST_SETTING; // notMetTheLowestSetting
                    }else{
                        $result['setting'] = $setting;
                        $result['msg4dev'] = 'It got matching setting';
                        $result['issueCaseNo'] = self::_GOT_MATCHING_SETTING; // gotMatchingSetting
                        $result['bonus_amount'] = $setting['bonus_amount'];
                    }
                }else{
                    $setting = [];
                    $result['msg4dev'] = 'not met the lowest setting';
                    $result['issueCaseNo'] = self::_NOT_MET_THE_LOWEST_SETTING; // notMetTheLowestSetting
                }

                $this->appendToDebugLog('The setting will be applied,', ['setting' => $setting
                    , 'last_month_deposit:' => $last_month_deposit
                ]);
            }else{
                $result['setting'] = $setting;
                $result['msg4dev'] = 'It got matching setting';
                $result['issueCaseNo'] = self::_GOT_MATCHING_SETTING; // gotMatchingSetting
                $result['bonus_amount'] = $setting['bonus_amount'];

                $this->appendToDebugLog('The setting will be applied,', ['setting' => $setting]);
            }

		}else{
			$result['msg4dev'] = 'Not right group level';
			$result['issueCaseNo'] = self::_NOT_RIGHT_GROUP_LEVEL; // _NOT_RIGHT_GROUP_LEVEL
            // $errorMessageLang = lang('Not right group level');
        }
		return $result;
	} // EOF getMaxButNotOverSetting

    protected function isVerifiedPhone($description){
        $verified_phone = true;

        if(!empty($description['verified_phone']) && $description['verified_phone']){
            $verified_phone = $this->player_model->isVerifiedPhone($this->playerId);
        }

        if(!$verified_phone){
            $this->appendToDebugLog('not verified phone',['verified_phone'=>$verified_phone]);
        }

        return $verified_phone;
    }

    protected function isVerifiedEmail($description){
        $verified_email = true;

        if(!empty($description['verified_email']) && $description['verified_email']){
            $verified_email = $this->player_model->isVerifiedEmail($this->playerId);
        }

        if(!$verified_email){
            $this->appendToDebugLog('not verified email',['verified_email'=>$verified_email]);
        }

        return $verified_email;
    }

    protected function completed_player_info($description){
        $completed_player_info = true;

        if(!empty($description['completed_player_info']) && $description['completed_player_info']){
            $conditionResult = $this->player_model->getPlayerAccountInfoStatus($this->playerId);
            $completed_player_info = $conditionResult['status'];
        }

        if(!$completed_player_info){
            $conditionResultMissingFields = !empty($conditionResult['missing_fields']) ? $conditionResult['missing_fields'] : NULL;
            $this->appendToDebugLog('not complete player info',['missing_fields'=>$conditionResultMissingFields]);
        }

        return $completed_player_info;
    }

    protected function check_last_month_deposit($description){
	    $check_last_month_deposit = true;

	    if(isset($description['check_last_month_deposit'])){
            $check_last_month_deposit = $description['check_last_month_deposit'];
        }

        $this->appendToDebugLog('check last month deposit result',['result'=>$check_last_month_deposit]);

        return $check_last_month_deposit;
    }
	
}

