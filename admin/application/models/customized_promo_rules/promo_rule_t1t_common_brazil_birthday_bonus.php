<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Birthday Bonus
 *
 * OGP-30950
 *
 * Bonus condition && Bonus release:
    {
        "class": "promo_rule_t1t_common_brazil_birthday_bonus",
        "bonus_settings": {
            "VIP2":  { "bonus": 2    },
            "VIP3":  { "bonus": 10   },
            "VIP4":  { "bonus": 20   },
            "VIP5":  { "bonus": 30   },
            "VIP6":  { "bonus": 50   },
            "VIP7":  { "bonus": 100  },
            "VIP8":  { "bonus": 200  },
            "VIP9":  { "bonus": 300  },
            "VIP10": { "bonus": 500  },
            "VIP11": { "bonus": 1000 },
            "VIP12": { "bonus": 2000 },
            "VIP13": { "bonus": 5000 }
        }
    }
 */
class Promo_rule_t1t_common_brazil_birthday_bonus extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        return 'Promo_rule_t1t_common_brazil_birthday_bonus';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     * condition
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){

        $errorMessageLang = null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

        if(array_key_exists('bonus_amount',$result)){
            unset($result['bonus_amount']);
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

        $errorMessageLang = null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

        return $result;
    }

    protected function inBirthMonth($description){
        $this->load->model(['player_model']);
        $today        = $this->utils->getTodayForMysql();
        $player       = $this->player_model->getPlayerInfoDetailById($this->playerId);
        $birthdate    = !empty($description['birthdate']) ? $description['birthdate'] : $player['birthdate'];
        $thisMonthDay = !empty($description['thisMonthDay']) ? $description['thisMonthDay'] : $today;
        $inBirthMonth = false;

        if(!empty($thisMonthDay)){
            $date = new DateTime($thisMonthDay);
            $currentMonth = $date->format('m');
            $currentDay = $date->format('d');
        }

        if(!empty($birthdate)){
            $date = new DateTime($birthdate);
            $birthMonth = $date->format('m');
            $birthDay = $date->format('d');

            if($currentMonth == $birthMonth && $currentDay == $birthDay){
                $inBirthMonth = true;
            }
        }

        if(!$inBirthMonth){
            $this->appendToDebugLog('not right month',['inBirthMonth'=>$inBirthMonth, 'current'=>"$currentMonth $currentDay", 'birth'=> "$birthMonth $birthDay"]);
        }else{
            $this->appendToDebugLog('in valid month', ['inBirthMonth'=>$inBirthMonth, 'current'=>"$currentMonth $currentDay", 'birth'=> "$birthMonth $birthDay"]);
        }

        return $inBirthMonth;
    }

    private function checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $bonus_settings = $description['bonus_settings'];
        $inBirthMonth = $this->inBirthMonth($description);

        $this->appendToDebugLog('player level id',['Id'=>$this->levelId]);

        if(array_key_exists($this->levelId, $bonus_settings)){
            if($inBirthMonth){
                $success = true;
                $bonus_amount = $bonus_settings[$this->levelId]['bonus'];
            }else{
                $errorMessageLang = 'Sorry, Birthday bonus is not yet ready';
            }
        }else{
            $errorMessageLang = 'notify.35';
        }

        return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
