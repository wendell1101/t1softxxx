<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 *
 * bank details of player
 *
 * status deleted=2, active=0
 *
 * General behaviors include
 * * delete certain bank information
 * * get bank list
 * * get list of bank by a certain player
 * * get deposit bank list for a certain player
 * * get withdrawal bank list for a certain player
 * * get bank details for a player using bank details id
 * * validate if the bank account if existing when doing update
 * * check bank account number if exists
 * * adding new bank details by withdrawal
 * * checking unique bank account number
 * * get bank information of a certain player
 * * get deposit bank details of a player
 * * get withdrawal bank details of a player
 * * get default deposit bank details of a player
 * * get Withdraw bank details of a player
 * * get default bank detail of a certain player
 * * get all bank details for a certain player
 *
 *	only_allow_duplicate_one_player: one player allow duplicate withdrawal and deposit, but can't allow duplicate number in withdrawal or deposit
 *	only_allow_duplicate_one_player_any: one player allow duplicate number , no matter withdrawal or deposit
 *	not_allow_duplicate_number: don't allow any duplicate bank account number.
 *	allow_any_duplicate_number: allow any duplicate bank account number for any player.
 *
 * @category Payment Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Playerbankdetails extends BaseModel {
	const NUM_CHAR_DISPLAY = 5;

	const STATUS_ACTIVE = '0';
	const STATUS_INACTIVE = '1';
	const STATUS_DELETED = '2';
	//for dwBank field
	const DEPOSIT_BANK = '0';
	const WITHDRAWAL_BANK = '1';
	const REMEMBERED = '1';
	const VERIFIED = '1';

	//financial_account_account_limit_type
	const BY_COUNT = '1';
    const BY_TIER = '2';

	//financial_account_account_limit_range_conditions
    const RANGE_CONDITIONS_TOTAL_WITHDRAWAL = '1';
	const RANGE_CONDITIONS_TOTAL_DEPOSIT = '2';
	const RANGE_CONDITIONS_TOTAL_BETS_AMOUNT = '3';

	const ONLY_ALLOW_DUPLICATE_ONE_PLAYER='only_allow_duplicate_one_player';
	const ONLY_ALLOW_DUPLICATE_ONE_PLAYER_ANY='only_allow_duplicate_one_player_any';
    const NOT_ALLOW_DUPLICATE_NUMBER='not_allow_duplicate_number';
    const NOT_ALLOW_DUPLICATE_NUMBER_ON_SAME_BANKTYPE='not_allow_duplicate_number_on_same_banktype';
    const NOT_ALLOW_DUPLICATE_NUMBER_ON_SAME_BANKTYPE_ANY='not_allow_duplicate_number_on_same_banktype_any';
	const ALLOW_ANY_DUPLICATE_NUMBER='allow_any_duplicate_number';

	# Hard-coded bank type in banktype table
	public $BANK_TYPE_ALIPAY;
	public $BANK_TYPE_WECHAT;

	protected $tableName = 'playerbankdetails';
	protected $idField = 'playerBankDetailsId';

	public function __construct() {
		parent::__construct();

		$this->BANK_TYPE_ALIPAY = !empty($this->config->item('BANK_TYPE_ALIPAY')) ? $this->config->item('BANK_TYPE_ALIPAY') : BANK_TYPE_ALIPAY;
		$this->BANK_TYPE_WECHAT = !empty($this->config->item('BANK_TYPE_WECHAT')) ? $this->config->item('BANK_TYPE_WECHAT') : BANK_TYPE_WECHAT;
	}

	/**
	 * detail: delete a certain bank information
	 *
	 * @param int $bank_details_id bank detail id
	 * @return boolean
	 */
	public function deletePlayerBankInfo($bank_details_id) {
		$this->db->where('playerBankDetailsId', $bank_details_id);
		//$this->db->delete('playerbankdetails');

		$this->db->set('bankAccountNumber', 'concat("del_", bankAccountNumber)', false);
		return $this->db->update('playerbankdetails', array(
            // 'bankAccountNumber' => 'concat("del_", bankAccountNumber)',
            'deletedOn' => $this->utils->getNowForMysql(),
            'status' => self::STATUS_DELETED
        ));
	}

	/**
	 * detail: get bank informations filtered by given parameter
	 *
	 * @param array $data
	 * @return boolean or array
	 */
	public function getBankList($data = null) {
		$this->db->select(array('playerbankdetails.*', 'banktype.bankName', 'banktype.bank_code'));
		$this->db->from('playerbankdetails');
		$this->db->join('banktype', 'playerbankdetails.bankTypeId = banktype.bankTypeId', 'left');
		$this->db->where($data);
		$this->db->order_by('banktype.bankName', 'asc');
		$this->db->order_by('playerbankdetails.bankAccountFullName', 'asc');
		$query = $this->db->get();

		if ( ! $query->result_array()) {
			return false;
		} else {
			return $query->result_array();
		}
	}

	/**
	 * detail: get player active bank types by playerId
	 *
	 * @param int $playerId
	 * @return array
	 */
	public function getBankTypesByPlayerId($playerId, $dwbank) {
		$this->db->select('bankTypeId');
        $this->db->from($this->tableName);
		$this->db->where('playerId', $playerId);
		$this->db->where('dwbank', $dwbank);
		$this->db->where('status', self::STATUS_ACTIVE);

		$lists = $this->runMultipleRow();
		if(!empty($lists) && is_array($lists)){
			foreach($lists as $list) {
				$bankTypeIds[] = $list->bankTypeId;
			}
			return $bankTypeIds;
		}

        return NULL;
	}

	public function PlayerApprovedDepositCount($playerId) {
		$this->db->select(array('playerId','approved_deposit_count') );
		$this->db->from('player');
		$this->db->where('playerId', $playerId);
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * detail: get list of bank by a certain player
	 *
	 * @param int $playerId player id
	 * @param array $data
	 * @return boolean
	 */
	public function getPlayerBankList($playerId, $data = array()) {
		return $this->getBankList(array_merge($data, array(
			'playerbankdetails.playerId' => $playerId,
		)));
	}

	/**
	 * detail: get deposit bank list for a certain player
	 *
	 * @param int $playerId player id
	 * @return boolean
	 */
	public function getPlayerDepositBankList($playerId) {
		return $this->getPlayerBankList($playerId, array(
			'playerbankdetails.dwBank' => self::DEPOSIT_BANK,
			'IFNULL(playerbankdetails.status,\'0\') =' => self::STATUS_ACTIVE,
		));
	}

	/**
	 * detail: get withdrawal bank list for a certain player
	 *
	 * @param int $playerId player id
	 * @return boolean
	 */
	public function getPlayerWithdrawalBankList($playerId) {
		return $this->getPlayerBankList($playerId, array(
			'playerbankdetails.dwBank' => self::WITHDRAWAL_BANK,
			'IFNULL(playerbankdetails.status,\'0\') =' => self::STATUS_ACTIVE,
		));
	}

	/**
	 * detail: get bank details for a player using bank details id
	 *
	 * @param int $id bank details id
	 * @return array
	 */
	public function getPlayerBankDetailsById($id) {
		return $this->getOneRowById($id);
	}

	/**
	 * detail: validate if the bank account if existing when doing update
	 *
	 * note: parameters are mandatory to call this method
	 *
	 * @param string $newBankAcctNo
	 * @param string $prevBankAcctNo
	 * @return boolean
	 */
	public function checkBankAcctIsExistOnEdit($newBankAcctNo, $prevBankAcctNo) {

		$sql = "SELECT * FROM playerbankdetails WHERE bankAccountNumber = ? AND bankAccountNumber != ? ";
		$q = $this->db->query($sql, array($newBankAcctNo, $prevBankAcctNo));
		if ($q->num_rows() > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * detail: check bank account number if exists
	 *
	 * note: parameters mandatory to call this method
	 *
	 * @param string $accountNumber
	 * @return boolean
	 */
	public function checkBankAccountNumber($accountNumber) {
		$this->db->from($this->tableName);
		$this->db->join('player', 'playerbankdetails.playerId = player.playerId');
		$this->db->where('player.deleted_at IS NULL', null, false);
		$this->db->where('playerbankdetails.bankAccountNumber', $accountNumber);
		if ($this->db->count_all_results() === 0) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * detail: adding new bank details by withdrawal
	 *
	 * note: parameters mandatory to call this method
	 *
	 * @param array $data
	 * @return int
	 */
	public function addBankDetailsByWithdrawal($data) {
		$this->load->model(['operatorglobalsettings']);
		if (!array_key_exists('createdOn', $data)) {
			//add "now"
			$data['createdOn'] = $this->utils->getNowForMysql();
		}
		if (!array_key_exists('updatedOn', $data)) {
			//add "now"
			$data['updatedOn'] = $this->utils->getNowForMysql();
		}
		$this->db->insert('playerbankdetails', $data);
		$playerbankdetailsId = $this->db->insert_id();
		$enable_financial_account_can_be_withdraw_and_deposit_in_usdt = false;
		if($this->utils->getConfig('enable_financial_account_can_be_withdraw_and_deposit_in_usdt')){
			$data = $this->getBankCodeByBankType($data['bankTypeId']);
			if($this->utils->isCryptoCurrency($data)){
				$enable_financial_account_can_be_withdraw_and_deposit_in_usdt = true;
			}
		}

		if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_can_be_withdraw_and_deposit') || $enable_financial_account_can_be_withdraw_and_deposit_in_usdt){
			//copy to deposit/withdrawal
			$this->duplicateBankToDepositOrWithdrawal($playerbankdetailsId);
		}

		return $playerbankdetailsId;
	}

	public function addBankDetailsBare($data) {
		$this->load->model(['operatorglobalsettings']);
		if (!array_key_exists('createdOn', $data)) {
			//add "now"
			$data['createdOn'] = $this->utils->getNowForMysql();
		}
		if (!array_key_exists('updatedOn', $data)) {
			//add "now"
			$data['updatedOn'] = $this->utils->getNowForMysql();
		}
		$this->db->insert('playerbankdetails', $data);
		$playerbankdetailsId = $this->db->insert_id();

		return $playerbankdetailsId;
	}

	/**
	 * detail: checking unique bank account number
	 *
	 * note: parameters are mandatory to call this method
	 *
	 * @param string $bankAccountNumber
	 * @param string $accountType
	 * @return boolean
	 */
	public function checkUniqueBankAccountNumber($bankAccountNumber, $accountType, $player_id, $bankTypeId = NULL) {

		$this->db->select('*')
				 ->where('bankAccountNumber', $bankAccountNumber)
				 ->where('dwBank', $accountType)
				 ->where('bankTypeId', $bankTypeId);
		if($this->utils->getConfig('allow_multiplayer_to_have_existing_bank_account')){
			$this->db->where('playerId', $player_id);
		}

		$q = $this->db->get('playerbankdetails');

		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());

		if ($q->num_rows() > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * [checkBankAccountNumberUnique description]
	 * @param	string	$bankAccountNumber
	 * @param	int		$accountType		0 for deposit, 1 for withdrawal
	 * @param	int		$player_id
	 * @param	int		$bankTypeId
	 * @return	true if not unique, false of unique
	 */
	public function checkBankAccountNumberUnique($bankAccountNumber, $accountType, $player_id, $bankTypeId = NULL) {

		$this->db->from($this->tableName)
			->select('COUNT(*) AS count')
			->where('bankAccountNumber', $bankAccountNumber)
			->where('dwBank', $accountType)
			->where('bankTypeId', $bankTypeId);
		if($this->utils->getConfig('allow_multiplayer_to_have_existing_bank_account')){
			$this->db->where('playerId', $player_id);
		}

		$count = $this->runOneRowOneField('count');

		return $count > 0;
	}

	/**
	 * detail: get bank information of a certain player
	 *
	 * note: player id is mandatory to call this method
	 *
	 * @param int $player_id player id
	 * @return array
	 */
	public function getBankDetails($player_id, $condition = self::VERIFIED) {
		$this->db->select("playerbankdetails.*, banktype.bankName, banktype.bankIcon, banktype.enabled_deposit, banktype.enabled_withdrawal, banktype.payment_type_flag, banktype.bank_code")->from('playerbankdetails')
		    ->join('banktype', 'playerbankdetails.bankTypeId = banktype.bankTypeId')
		    ->where('playerbankdetails.playerId', $player_id)
			->where('playerbankdetails.dwBank', self::DEPOSIT_BANK)
		    ->where('playerbankdetails.status', self::STATUS_ACTIVE);
			if($condition == self::VERIFIED) {
				$this->db->where('playerbankdetails.verified', self::VERIFIED);
			}
		$dBank = $this->runMultipleRowArray();

        if(!empty($dBank)){
            $default_count = 0;
            foreach($dBank as &$player_bank){
                if($default_count > 0){
                    $player_bank['isDefault'] = 0;
                    continue;
                }
                if($player_bank['isDefault']){
                    $default_count++;
                }
            }

            // if($default_count === 0){
            //     $dBank[0]['isDefault'] = 1;
            // }
        }

		$this->db->select("playerbankdetails.*, banktype.bankName, banktype.bankIcon, banktype.enabled_deposit, banktype.enabled_withdrawal, banktype.payment_type_flag,banktype.bank_code")->from('playerbankdetails')
		    ->join('banktype', 'playerbankdetails.bankTypeId = banktype.bankTypeId')
		    ->where('playerbankdetails.playerId', $player_id)
			->where('playerbankdetails.dwBank', self::WITHDRAWAL_BANK)
			->where('playerbankdetails.status', self::STATUS_ACTIVE);
			if ($condition == self::VERIFIED) {
                $this->db->where('playerbankdetails.verified', self::VERIFIED);
            }
		$wBank = $this->runMultipleRowArray();

        if(!empty($wBank)){
            $default_count = 0;
            foreach($wBank as &$player_bank){
                if($default_count > 0){
                    $player_bank['isDefault'] = 0;
                    continue;
                }
                if($player_bank['isDefault']){
                    $default_count++;
                }
            }

            // if($default_count === 0){
            //     $wBank[0]['isDefault'] = 1;
            // }
        }

		$result = array(
			'deposit' => $dBank,
			'withdrawal' => $wBank,
		);

		return $result;
	}

	public function getNotDeletedBankInfoList($player_id, $options = []) {
		$this->db->select("playerbankdetails.*, banktype.bankName, banktype.bankIcon, banktype.enabled_deposit, banktype.enabled_withdrawal, banktype.payment_type_flag, banktype.status as banktypeStatus ,banktype.bank_code")
            ->from('playerbankdetails')
		    ->join('banktype', 'playerbankdetails.bankTypeId = banktype.bankTypeId')
		    ->where('playerbankdetails.playerId', $player_id)
		    ->where('playerbankdetails.dwBank', self::DEPOSIT_BANK)
		    ->where('playerbankdetails.status !=', self::STATUS_DELETED);
			if(!empty($options)){
				if(isset($options['exclude_status'])){
					$this->db->where_not_in("playerbankdetails.status", $options['exclude_status']);
				}
				if(isset($options['only_banktype_active']) && $options['only_banktype_active']){
					$this->db->where("banktype.status", 'active');
				}
                if(!empty($options['bankaccount_number'])){
                    $this->db->where("playerbankdetails.bankAccountNumber", $options['bankaccount_number']);
                }
                if(!empty($options['branch'])){
                    $this->db->where("playerbankdetails.branch", $options['branch']);
                }
                if(!empty($options['payment_type_flag'])){
                    $this->db->where("banktype.payment_type_flag", $options['payment_type_flag']);
                }
			}
		$dBank = $this->runMultipleRowArray();

        if(!empty($dBank)){
            $default_count = 0;
            foreach($dBank as &$player_bank){
                if($default_count > 0){
                    $player_bank['isDefault'] = 0;
                    continue;
                }
                if($player_bank['isDefault']){
                    $default_count++;
                }
            }

            // if($default_count === 0){
            //     $dBank[0]['isDefault'] = 1;
            // }
        }

		$this->db->select("playerbankdetails.*, banktype.bankName, banktype.bankIcon, banktype.enabled_deposit, banktype.enabled_withdrawal, banktype.payment_type_flag, banktype.status as banktypeStatus ,banktype.bank_code")
            ->from('playerbankdetails')
		    ->join('banktype', 'playerbankdetails.bankTypeId = banktype.bankTypeId')
		    ->where('playerbankdetails.playerId', $player_id)
		    ->where('playerbankdetails.dwBank', self::WITHDRAWAL_BANK)
		    ->where('playerbankdetails.status !=', self::STATUS_DELETED);
            if(!empty($options)){
				if(isset($options['exclude_status'])){
					$this->db->where_not_in("playerbankdetails.status", $options['exclude_status']);
				}
				if(isset($options['only_banktype_active']) && $options['only_banktype_active']){
					$this->db->where("banktype.status", 'active');
				}
                if(!empty($options['bankaccount_number'])){
                    $this->db->where("playerbankdetails.bankAccountNumber", $options['bankaccount_number']);
                }
                if(!empty($options['branch'])){
                    $this->db->where("playerbankdetails.branch", $options['branch']);
                }
                if(!empty($options['payment_type_flag'])){
                    $this->db->where("banktype.payment_type_flag", $options['payment_type_flag']);
                }
			}
		$wBank = $this->runMultipleRowArray();

        if(!empty($wBank)){
            $default_count = 0;
            foreach($wBank as &$player_bank){
                if($default_count > 0){
                    $player_bank['isDefault'] = 0;
                    continue;
                }
                if($player_bank['isDefault']){
                    $default_count++;
                }
            }

            // if($default_count === 0){
            //     $wBank[0]['isDefault'] = 1;
            // }
        }

		$result = array(
			'deposit' => $dBank,
			'withdrawal' => $wBank,
		);

		return $result;
	}

	/**
	 * detail: get deposit bank details of a player
	 *
	 * note: player id is mandatory to call this method
	 *
	 * @param int $playerId player id
	 * @return string
	 */
	public function getDepositBankDetail($playerId){
		$result = $this->getBankDetails($playerId);
		return $result['deposit'];
	}

	public function getAvailableDepositBankDetail($playerId){
		$result = $this->getBankDetails($playerId);

        if(empty($result['deposit'])){
            return NULL;
        }

        $filtered = [];
        foreach($result['deposit'] as $row){
            if(0 === (int)$row['enabled_deposit']){
                continue;
            }

            $enabled_custom_deposit = !empty($this->utils->getConfig('enable_deposit_custom_view'));
            $acc_full_name = !empty($row['bankAccountFullName']) ? ' - ' . $row['bankAccountFullName'] : '';

            $row['displayName'] = $enabled_custom_deposit ? lang($row['bankName']).' ('. self::getDisplayAccNum($row['bankAccountNumber']). $acc_full_name .')' : lang($row['bankName']).' ('. self::getDisplayAccNum($row['bankAccountNumber']).')';

            $filtered[] = $row;
        }

		return empty($filtered) ? NULL : $filtered;
	}

	/**
	 * detail: get withdrawal bank details of a player
	 *
	 * note: player id is mandatory to call this method
	 *
	 * @param int $playerId player id
	 * @return string
	 */
	public function getWithdrawBankDetail($playerId){
		$result = $this->getBankDetails($playerId);
		return $result['withdrawal'];
	}

	public function getAvailableWithdrawBankDetail($playerId){
		$result =  $this->queryWithdrawBank($playerId);

        if(empty($result)){
            return NULL;
        }

		return $result;
	}

	# Returns player banks without wechat and alipay
	public function getWithdrawBankOnlyDetail($playerId){
		return $this->queryWithdrawBank($playerId, 'bank');
	}

	# Returns player's saved wechat accounts
	public function getWithdrawWeChatAccountDetail($playerId){
		return $this->queryWithdrawBank($playerId, 'wechat');
	}

	# Returns player's saved alipay accounts
	public function getWithdrawAlipayAccountDetail($playerId){
		return $this->queryWithdrawBank($playerId, 'alipay');
	}

	/**
	 * Read details for player's withdrawal banks
	 * @param	int		$playerId	= Player.playerId
	 * @param  [type] $type     [description]
	 * @return [type]           [description]
	 */
	private function queryWithdrawBank($playerId, $type = NULL) {
		$this->db->select("D.*, B.bankName, B.enabled_deposit, B.enabled_withdrawal, B.bankIcon, B.bank_code")
			->from('playerbankdetails AS D')
			->join('banktype AS B', 'D.bankTypeId = B.bankTypeId')
			->where('D.playerId', $playerId)
			->where('D.dwBank', self::WITHDRAWAL_BANK)
			->where('D.verified', self::VERIFIED)
			->where('D.status', self::STATUS_ACTIVE);

		# Limit bank type if $type is given
		if('bank' == $type) {
			$this->db->where('D.bankTypeId !=', $this->BANK_TYPE_WECHAT);
			$this->db->where('D.bankTypeId !=', $this->BANK_TYPE_ALIPAY);
		} elseif ('wechat' == $type) {
			$this->db->where('D.bankTypeId', $this->BANK_TYPE_WECHAT);
		} elseif ('alipay' == $type) {
			$this->db->where('D.bankTypeId', $this->BANK_TYPE_ALIPAY);
		}

		$result = $this->runMultipleRowArray();
		return empty($result) ? array() : $result; # avoid returning null on empty result
	}

	public function getDefaultDepositBankDetail($playerId){
		$result = $this->getDefaultBankDetail($playerId);
		return $result['deposit'];
	}

	/**
	 * detail: get Withdraw bank details of a player
	 *
	 * note: player id is mandatory to call this method
	 *
	 * @param int $playerId player id
	 * @return string
	 */
	public function getDefaultWithdrawBankDetail($playerId){
		$result=$this->getDefaultBankDetail($playerId);

		return $result['withdrawal'];
	}

	/**
	 * detail: get default bank detail of a certain player
	 *
	 * note: player id is mandatory to call this method
	 *
	 * @param int $playerId player id
	 * @return array
	 */
	public function getDefaultBankDetail($playerId){
		$this->db->select('pbd.*, banktype.bankName, banktype.bankIcon, banktype.enabled_deposit, banktype.enabled_withdrawal')->from('playerbankdetails as pbd')
		    ->join('banktype', 'banktype.bankTypeId=pbd.bankTypeId')->where('playerId', $playerId)
		    ->where('dwBank', self::DEPOSIT_BANK)->where('pbd.status', self::STATUS_ACTIVE)
		    ->where('isDefault', '1')->limit(1);

		$dBank=$this->runMultipleRowArray();

		$this->db->select('pbd.*, banktype.bankName, banktype.bankIcon, banktype.enabled_deposit, banktype.enabled_withdrawal')->from('playerbankdetails as pbd')
		    ->join('banktype', 'banktype.bankTypeId=pbd.bankTypeId')->where('playerId', $playerId)
		    ->where('dwBank', self::WITHDRAWAL_BANK)->where('pbd.status', self::STATUS_ACTIVE)
		    ->where('isDefault', '1')->limit(1);
		$wBank=$this->runMultipleRowArray();

		if(empty($dBank)){
			$this->db->select('playerBankDetailsId')->from('playerbankdetails')->where('playerId', $playerId)
				->where('status', self::STATUS_ACTIVE)
				->where('dwBank', self::DEPOSIT_BANK)->limit(1);
			$playerBankDetailsId=$this->runOneRowOneField('playerBankDetailsId');
			$this->utils->debug_log('playerBankDetailsId dBank', $playerBankDetailsId);
			if(!empty($playerBankDetailsId)){
				$this->db->set('isDefault', '1')->where('playerBankDetailsId', $playerBankDetailsId);
				$this->runAnyUpdate('playerbankdetails');
				$this->db->select('pbd.*, banktype.bankName, banktype.bankIcon, banktype.enabled_deposit, banktype.enabled_withdrawal')->from('playerbankdetails as pbd')
				    ->join('banktype', 'banktype.bankTypeId=pbd.bankTypeId')->where('playerId', $playerId)
				    ->where('dwBank', self::DEPOSIT_BANK)->where('pbd.status', self::STATUS_ACTIVE)
				    ->where('isDefault', '1');

				$dBank=$this->runMultipleRowArray();
			}
		}

		if(empty($wBank)){
			$this->db->select('playerBankDetailsId')->from('playerbankdetails')->where('playerId', $playerId)
				->where('status', self::STATUS_ACTIVE)
				->where('dwBank', self::WITHDRAWAL_BANK)->limit(1);
			$playerBankDetailsId=$this->runOneRowOneField('playerBankDetailsId');
			$this->utils->debug_log('playerBankDetailsId wBank', $playerBankDetailsId);
			if(!empty($playerBankDetailsId)){
				$this->db->set('isDefault', '1')->where('playerBankDetailsId', $playerBankDetailsId);
				$this->runAnyUpdate('playerbankdetails');

				$this->db->select('pbd.*, banktype.bankName, banktype.bankIcon, banktype.enabled_deposit, banktype.enabled_withdrawal')->from('playerbankdetails as pbd')
				    ->join('banktype', 'banktype.bankTypeId=pbd.bankTypeId')->where('playerId', $playerId)
				    ->where('dwBank', self::WITHDRAWAL_BANK)->where('pbd.status', self::STATUS_ACTIVE)
				    ->where('isDefault', '1');
				$wBank=$this->runMultipleRowArray();
			}
		}

		$result = array(
			'deposit' => $dBank,
			'withdrawal' => $wBank,
		);

		return $result;
	}

	/**
	 * Will get player deposit rule
	 *
	 * @param 	int
	 * @return	array
	 */
	public function getBankDetailsById($bankDetailsId) {
		$this->db->select('D.playerBankDetailsId, D.bankTypeId, D.bankAccountFullName, D.bankAccountNumber,
			D.bankAddress, D.city, D.province, D.branch, T.bankName, D.playerId, D.dwBank, D.status AS bankDetailStatus')
			->from('playerbankdetails AS D')
			->join('banktype AS T', 'D.bankTypeId = T.bankTypeId', 'left');
		$this->db->where('D.playerbankdetailsId', $bankDetailsId);

		return $this->runOneRowArray();
	}

	/**
	 * Query playerbankdetails using bankTypeId and bankAccountNumber. Used by some withdrawal API.
	 */
	public function getBankDetailsByBankAccount($bankTypeId, $bankAccountNumber) {
		$this->db->select(
			'playerbankdetails.playerBankDetailsId,'.
			'playerbankdetails.bankTypeId,'.
			'playerbankdetails.bankAccountFullName,'.
			'playerbankdetails.bankAccountNumber,'.
			'playerbankdetails.bankAddress,'.
			'playerbankdetails.city,'.
			'playerbankdetails.province,'.
			'playerbankdetails.branch,'.
			'playerbankdetails.playerId'
			)->from('playerbankdetails');
		$this->db->where('playerbankdetails.bankTypeId', $bankTypeId);
		$this->db->where('playerbankdetails.bankAccountNumber', $bankAccountNumber);

		return $this->runOneRowArray();
	}

	public function getNotDeletedBankDetailsByBankTypeId($bankTypeId) {
		$this->db->select(
			'playerbankdetails.playerBankDetailsId,'.
			'playerbankdetails.bankTypeId,'.
			'playerbankdetails.bankAccountFullName,'.
			'playerbankdetails.bankAccountNumber,'
			)->from('playerbankdetails');
		$this->db->where('playerbankdetails.bankTypeId', $bankTypeId);
		$this->db->where('playerbankdetails.status !=', self::STATUS_DELETED);

		return $this->runMultipleRowArray();
	}

	public function getBankDetailsByBankAcc($bankAccountNumber, $method = null, $dwBank = null) {
		$result = false;
		$this->db->select(
			'playerbankdetails.playerBankDetailsId,'.
			'playerbankdetails.playerId,'.
			'playerbankdetails.bankTypeId,'.
			'playerbankdetails.bankAccountFullName,'.
			'playerbankdetails.bankAccountNumber,'.
			'playerbankdetails.bankAddress,'.
			'playerbankdetails.city,'.
			'playerbankdetails.province,'.
			'playerbankdetails.branch'
			)->from('playerbankdetails');
		if($method == 'LIKE'){
			$this->db->where("playerbankdetails.bankAccountNumber LIKE "."'".$bankAccountNumber."'");
		}else{
			$this->db->where("right(playerbankdetails.bankAccountNumber,6) = ".$bankAccountNumber);
		}

		if($dwBank == 'deposit'){
			$this->db->where('playerbankdetails.dwBank', self::DEPOSIT_BANK);
		}else if($dwBank == 'withdrawal'){
			$this->db->where('playerbankdetails.dwBank', self::WITHDRAWAL_BANK);
		}

		return $this->runMultipleRowArray();
	}

	/**
	 * add bank details by deposit
	 *
	 * @return	array
	 */
	public function addBankDetailsByDeposit($data) {
		$this->load->model(['operatorglobalsettings']);
		if (!array_key_exists('createdOn', $data)) {
			//add "now"
			$data['createdOn'] = $this->utils->getNowForMysql();
		}
		if (!array_key_exists('updatedOn', $data)) {
			//add "now"
			$data['updatedOn'] = $this->utils->getNowForMysql();
		}
		$this->db->insert('playerbankdetails', $data);
		$playerbankdetailsId = $this->db->insert_id();
		$enable_financial_account_can_be_withdraw_and_deposit_in_usdt = false;

		if($this->utils->getConfig('enable_financial_account_can_be_withdraw_and_deposit_in_usdt')){
			$data = $this->getBankCodeByBankType($data['bankTypeId']);
			if($this->utils->isCryptoCurrency($data)){
				$enable_financial_account_can_be_withdraw_and_deposit_in_usdt = true;
			}
		}

		if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_can_be_withdraw_and_deposit') || $enable_financial_account_can_be_withdraw_and_deposit_in_usdt){
			//copy to deposit/withdrawal
			$this->duplicateBankToDepositOrWithdrawal($playerbankdetailsId);
		}

		return $playerbankdetailsId;
	}

	/**
	 * create CryptoBankDetails record
	 * @param  array  $playerCryptoBankDetails
	 * @return res id
	 */
	public function addCryptoBankDetails($playerCryptoBankDetails) {
		$this->db->insert('playercryptobankdetails', $playerCryptoBankDetails);
		return $this->db->insert_id();
	}

	/**
     *
     * @param type $bank_detail_id
     * @return stdClass|boolean
     */
    public function getPlayerCryptoBankDetailById($bank_detail_id){
		$this->db->from('playercryptobankdetails');
		$this->db->where('player_bank_detailsid', $bank_detail_id);

		return $this->getOneRowById($bank_detail_id);
	}

	/**
	 * detail: get bank code
	 *
	 * @param int $bankTypeId
	 * @return	array
	 */
	public function getBankCodeByBankType($bankTypeId) {
		$this->db->select(
			'bank_code'
			)->from('banktype');
		$this->db->where('banktype.bankTypeId', $bankTypeId);

		return $this->runOneRowArray();
	}

	/**
	 * detail: get bank details
	 *
	 * @param int $playerId
	 * @return	array
	 */
	public function getDepositBankDetails($playerId) {
		$this->db->select('playerbankdetails.*,
                banktype.*,
            ')
			->from('playerbankdetails')
			->join('banktype', 'banktype.bankTypeId = playerbankdetails.bankTypeId', 'left')
			->order_by('playerbankdetails.bankAccountFullName', 'asc');
		$this->db->where('playerbankdetails.playerId', $playerId);
		$this->db->where('playerbankdetails.dwBank', self::DEPOSIT_BANK); //0 = deposit
		$this->db->where('playerbankdetails.status', self::STATUS_ACTIVE); //0 = active
		// $this->db->where('playerbankdetails.isRemember', '1'); //1 = remembered

		$query = $this->db->get();
		//var_dump($query->result_array());exit();
		return $query->result_array();
	}

	public function updateBanktypeId( $playerBankDetailsId, $bankTypeId ){
		return $this->db->where('playerBankDetailsId', $playerBankDetailsId)
						->update('playerbankdetails', array(
							'bankTypeId' => $bankTypeId
						));
	}

	public function getBankNumberValidatorMode(){
		$this->load->model(['operatorglobalsettings']);
		return $this->operatorglobalsettings->getSettingValue('bank_number_validator_mode', self::ONLY_ALLOW_DUPLICATE_ONE_PLAYER);
	}

	public function is_only_allow_duplicate_one_player(){
		return self::ONLY_ALLOW_DUPLICATE_ONE_PLAYER==$this->getBankNumberValidatorMode();
	}

	public function is_only_allow_duplicate_one_player_any(){
		return self::ONLY_ALLOW_DUPLICATE_ONE_PLAYER_ANY==$this->getBankNumberValidatorMode();
	}

	public function is_not_allow_duplicate_number(){
		return self::NOT_ALLOW_DUPLICATE_NUMBER==$this->getBankNumberValidatorMode();
	}

	public function is_allow_any_duplicate_number(){
		return self::ALLOW_ANY_DUPLICATE_NUMBER==$this->getBankNumberValidatorMode();
	}

	public function addDepositBankDetails($data) {
		if (!array_key_exists('createdOn', $data)) {
			//add "now"
			$data['createdOn'] = $this->utils->getNowForMysql();
		}
		if (!array_key_exists('updatedOn', $data)) {
			//add "now"
			$data['updatedOn'] = $this->utils->getNowForMysql();
		}
		$data['dwBank']=self::DEPOSIT_BANK;
		$data['status']=self::STATUS_ACTIVE;

		$this->db->insert('playerbankdetails', $data);
		return $playerbankdetailsId = $this->db->insert_id();
	}

	public function getBankDetailInfo($bank_details_id){
		$this->db->select('playerbankdetails.*,
                banktype.*,
            ')
			->from('playerbankdetails')
			->join('banktype', 'banktype.bankTypeId = playerbankdetails.bankTypeId', 'left')
		    ->where('playerBankDetailsId', $bank_details_id);

		return $this->runOneRowArray();
	}

	public function validate_bank_account_number($playerId, $bank_account_number, $bank_type, $bank_details_id=null, $bank_type_id = NULL){

		// $bankInfo=null;
		// if(!empty($bank_details_id)){
		// 	$bankInfo=$this->getBankDetailInfo($bank_details_id);
		// }

		log_message('debug', 'validate_bank_account_number params' , ['playerId'=>$playerId,
			'bank_type_id' => $bank_type_id, 'bank_account_number'=>$bank_account_number, 'bank_type'=>$bank_type, 'bank_details_id'=>$bank_details_id]);

		$result=false;
		$mode=$this->getBankNumberValidatorMode();

		$check_status = [];
		if($this->utils->isEnabledFeature('duplicate_bank_account_number_verify_status_active')){
            $check_status[] = self::STATUS_ACTIVE;
        }

		log_message('debug', 'getBankNumberValidatorMode:'.$mode);
		switch ($mode) {
			case self::ONLY_ALLOW_DUPLICATE_ONE_PLAYER:
				//one player allow duplicate withdrawal and deposit, but can't allow duplicate number in same type
				//search other player
				$this->db->select('playerBankDetailsId')->from('playerbankdetails')
				    ->where('playerId !=', $playerId)
				    ->where('bankAccountNumber', $bank_account_number);
				if(!empty($check_status)){
				    $this->db->where_in('status', $check_status);
                }

				if(!empty($bank_details_id)){
					$this->db->where('playerBankDetailsId !=', $bank_details_id);
				}

				$result=! $this->runExistsResult();

				$this->utils->printLastSQL();

				if($result){
					//search this player on
					$this->db->select('playerBankDetailsId')->from('playerbankdetails')
						->where('playerId', $playerId)
						->where('dwBank', $bank_type)
						->where('bankAccountNumber', $bank_account_number);

                    if(!empty($check_status)){
                        $this->db->where_in('status', $check_status);
                    }

					if(!empty($bank_details_id)){
						$this->db->where('playerBankDetailsId !=', $bank_details_id);
					}

					$result=! $this->runExistsResult();

					$this->utils->printLastSQL();

				}

				break;

			case self::ONLY_ALLOW_DUPLICATE_ONE_PLAYER_ANY:
				//one player allow duplicate number , no matter withdrawal or deposit
				$this->db->select('playerBankDetailsId')->from('playerbankdetails')
				    ->where('playerId !=', $playerId)
				    ->where('bankAccountNumber', $bank_account_number);

                if(!empty($check_status)){
                    $this->db->where_in('status', $check_status);
                }

				if(!empty($bank_details_id)){
					$this->db->where('playerBankDetailsId !=', $bank_details_id);
				}

				$result=! $this->runExistsResult();
				break;
            case self::NOT_ALLOW_DUPLICATE_NUMBER:
                //don't allow any duplicate bank account number.
                $this->db->select('playerBankDetailsId')->from('playerbankdetails')
                    ->where('bankAccountNumber', $bank_account_number);

                if(!empty($check_status)){
                    $this->db->where_in('status', $check_status);
                }

                if(!empty($bank_details_id)){
                    $this->db->where('playerBankDetailsId !=', $bank_details_id);
                }

                $result=! $this->runExistsResult();
                break;
            case self::NOT_ALLOW_DUPLICATE_NUMBER_ON_SAME_BANKTYPE:
                $this->db->select('playerBankDetailsId')->from('playerbankdetails')
                    ->where('dwBank', $bank_type)
                    ->where('bankTypeId', $bank_type_id)
                    ->where('bankAccountNumber', $bank_account_number);

                if(!empty($check_status)){
                    $this->db->where_in('status', $check_status);
                }

                if(!empty($bank_details_id)){
                    $this->db->where('playerBankDetailsId !=', $bank_details_id);
                }

                $result=! $this->runExistsResult();
                break;
            case self::NOT_ALLOW_DUPLICATE_NUMBER_ON_SAME_BANKTYPE_ANY:
                $this->db->select('playerBankDetailsId')->from('playerbankdetails')
                    ->where('bankTypeId', $bank_type_id)
                    ->where('bankAccountNumber', $bank_account_number);

                if(!empty($check_status)){
                    $this->db->where_in('status', $check_status);
                }

                if(!empty($bank_details_id)){
                    $this->db->where('playerBankDetailsId !=', $bank_details_id);
                }

                $result=! $this->runExistsResult();
                break;
			default:
				$result=true;
				break;
		}

		return $result;
	}

	public function search_id_by_bank_number($playerId, $bank_type ,$bank_account_number_prev){
		$this->db->select('playerBankDetailsId')->from('playerbankdetails')
			->where('bankAccountNumber', $bank_account_number_prev)
		    ->where('dwBank', $bank_type)
		    ->where('playerId', $playerId)
			->where('status', self::STATUS_ACTIVE);

		return $this->runOneRowOneField('playerBankDetailsId');
	}

    /**
     *
     * @author Elvis Chen
     * @since date 20170919
     * @param type $playerId
     * @param type $bank_detail_id
     * @return stdClass|boolean
     */
    public function getPlayerBankDetailById($playerId, $bank_detail_id, $dwBank = NULL){
		$this->db->where('playerId', $playerId);
        if(NULL !== $dwBank){
		    $this->db->where('dwBank', $dwBank);
        }
		return $this->getOneRowById($bank_detail_id);
    }

    /**
     *
     * @author Elvis Chen
     * @since date 20170918
     * @param type $playerId
     * @param type $bank_detail_id
     * @param type $data
     * @return boolean
     */
	public function updatePlayerBankDetails($playerId, $bank_detail_id, $data) {
		$this->db->where('playerId', $playerId);
		$this->db->where('playerBankDetailsId', $bank_detail_id);
		return $this->db->update('playerbankdetails', $data);
	}

    /**
     * @author Elvis Chen
     * @since date 20170919
     * @param int $playerId
     * @param int $bank_type
     * @param int $bank_detail_id
     * @return boolean
     */
    public function setPlayerDefaultBank($playerId, $bank_type, $bank_detail_id){
        $reset_data = [
            'isDefault' => '0'
        ];
		$this->db->where('playerId', $playerId);
		$this->db->where('dwBank', $bank_type);

		if(!$this->db->update('playerbankdetails', $reset_data)){
            return FALSE;
        }

        $data = [
            'isDefault' => '1',
            'updatedOn' => date("Y-m-d H:i:s")
        ];
		$this->db->where('playerId', $playerId);
		$this->db->where('dwBank', $bank_type);
		$this->db->where('playerBankDetailsId', $bank_detail_id);

        return $this->db->update('playerbankdetails', $data);
    }

    /**
     * @author Elvis Chen
     * @since date 20170919
     * @param int $playerId
     * @param int $bank_detail_id
     * @return boolean
     */
    public function setPlayerDefaultDepositBank($playerId, $bank_detail_id){
        return $this->setPlayerDefaultBank($playerId, self::DEPOSIT_BANK, $bank_detail_id);
    }

    /**
     * @author Elvis Chen
     * @since date 20170919
     * @param int $playerId
     * @param int $bank_detail_id
     * @return boolean
     */
    public function setPlayerDefaultWithdrawalBank($playerId, $bank_detail_id){
        return $this->setPlayerDefaultBank($playerId, self::WITHDRAWAL_BANK, $bank_detail_id);
    }

	static public function getDisplayAccNum($accNum) {
		$CI = &get_instance();
		if(strlen($accNum) <= self::NUM_CHAR_DISPLAY || $CI->utils->isEnabledFeature('show_player_complete_withdrawal_account_number')){
			return $accNum;
		}
		return '*'.substr($accNum, -self::NUM_CHAR_DISPLAY);
	}

    static public function AllowAddBankDetail($dwbank, $bank_list, &$message = '', $input_banktype_id = null, $player_id = null, &$mesg_extra = null){
		$CI = &get_instance();
		$count_bank_list = is_array($bank_list) ? count($bank_list) : 0;
		if ($CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_one_account_per_institution') && !is_null($input_banktype_id)) {
            $input_banktype = $CI->banktype->getBankTypeById($input_banktype_id);
            foreach ($bank_list as $bank) {
                if ($input_banktype->bankTypeId == $bank['bankTypeId']) {
                    $message = sprintf(lang('notify.limit.one_account_per_institution'), lang($bank['bankName'])) ;
                    return false;
                }
            }
        }

        switch ($dwbank) {
			case self::DEPOSIT_BANK:
				if($CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_deposit_account_limit_type',self::BY_COUNT) == self::BY_TIER) {
                    if (!empty($bank_list)) {
                        return self::AllowAddBankDetailByTier($dwbank, $bank_list, $message, $input_banktype_id, $player_id, $mesg_extra);
                    }
                    return true;
				} else
	        	if($CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_deposit_account_limit')){
					$limit_count = $CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_max_deposit_account_number');
					$mesg_extra = [ 'limit' => $limit_count, 'current' => $count_bank_list, 'rule' => 'fixed_limit' ];
		            if ($count_bank_list >= $limit_count) {
		                $message = sprintf(lang('notify.limit.bank.count'), $limit_count);
		                return FALSE;
		            }
	        	}
        		break;
			case self::WITHDRAWAL_BANK:
				if($CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_limit_type',self::BY_COUNT) == self::BY_TIER) {
					if(!empty($bank_list)) {
						return self::AllowAddBankDetailByTier($dwbank, $bank_list, $message, $input_banktype_id, $player_id, $mesg_extra);
					}
					return TRUE;
				} else
	        	if($CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_limit')){
		            $limit_count = $CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_max_withdraw_account_number');
		            $mesg_extra = [ 'limit' => $limit_count, 'current' => $count_bank_list, 'rule' => 'fixed_limit' ];
		            if ($count_bank_list >= $limit_count) {
		                $message = sprintf(lang('notify.limit.bank.count'), $limit_count);
		                return FALSE;
		            }
	        	}
        		break;
		}

        return TRUE;
	}

	static public function AllowAddBankDetailByTier($dwbank, $bank_list, &$message = '', $input_banktype_id = null, $_player_id = null, &$mesg_extra = null){
		$CI = &get_instance();

		if(empty($bank_list)) {
			return true;
		} else if (!empty($_player_id)) {
			$player_id = $_player_id;
		} else {
			$player_id = $bank_list[0]['playerId'];
		}
		$count_bank_list = is_array($bank_list) ? count($bank_list) : 0;
        switch ($dwbank) {
			case self::DEPOSIT_BANK:
	        	if($CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_deposit_account_limit')){
					$range_conditions = $CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_deposit_account_limit_range_conditions', self::RANGE_CONDITIONS_TOTAL_WITHDRAWAL);
					$MaximumNumberAccountSetting = $CI->operatorglobalsettings->getSettingJson('financial_account_deposit_account_limit_range_setting_list');
	        	}
        		break;
        	case self::WITHDRAWAL_BANK:
	        	if($CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_limit')){
					$range_conditions = $CI->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_limit_range_conditions', self::RANGE_CONDITIONS_TOTAL_WITHDRAWAL);
					$MaximumNumberAccountSetting = $CI->operatorglobalsettings->getSettingJson('financial_account_withdraw_account_limit_range_setting_list');
	        	}
        		break;
		}

		$condition_count = 0;
		if(!empty($range_conditions)){
			switch ($range_conditions) {
				case self::RANGE_CONDITIONS_TOTAL_DEPOSIT:
					$CI->load->model(['transactions']);
					$condition_count = $CI->transactions->getPlayerTotalDeposits($player_id);
				break;

				case self::RANGE_CONDITIONS_TOTAL_WITHDRAWAL:
					$CI->load->model(['transactions']);
					$condition_count = $CI->transactions->getPlayerTotalWithdrawals($player_id);
				break;

				case self::RANGE_CONDITIONS_TOTAL_BETS_AMOUNT:
					$CI->load->model(['game_logs']);
					list($bet, $win, $loss) = $CI->game_logs->getTotalBetsWinsLossByPlayers($player_id);
					$condition_count = $bet?:0;
				break;
			}
		}

		$limit_count = 1; // Maximum number of the bank account, defaule to 1
		if(!empty($MaximumNumberAccountSetting)){

			foreach ($MaximumNumberAccountSetting as $level) {
				if ($level['rangeTo'] !="Infinity" && $condition_count < $level['rangeTo']) {
					$limit_count = $level['noOfAccountsAllowed'];
					break;
				} elseif ($level['rangeTo'] =="Infinity") {
					$limit_count = $level['noOfAccountsAllowed'];
					break;
				} else {
					$limit_count = $level['noOfAccountsAllowed'];
				}
			}
		}
		$mesg_extra = [ 'limit' => $limit_count, 'current' => $count_bank_list, 'rule' => 'tiered_limits', 'rule_type' => $range_conditions, 'rule_value' => $condition_count ];

		if ($count_bank_list >= $limit_count) {
			$message = sprintf(lang('notify.limit.bank.count'), $limit_count);
			return false;
		}

        return TRUE;
    }

    public function importPlayerBank($external_id, $playerId, $bankTypeId, $dwBank,
    	$bankAccountFullName, $bankAccountNumber, $province, $city, $branch, $bankAddress, $createdOn, $status, &$message){

    	if (empty($playerId)) {
    		$message = "Empty playerId: [$playerId]";
    		return false;
    	}

    	if (empty($bankTypeId)) {
    		$message = "Empty bankTypeId: [$bankTypeId]";
    		return false;
    	}

    	$data = array(
    		'playerId' => $playerId,
    		'bankTypeId' => $bankTypeId,
    		'bankAccountFullName' => $bankAccountFullName,
    		'bankAccountNumber' => $bankAccountNumber,
    		'bankAddress'=> $bankAddress,
    		'city'=>$city,
    		'province'=>$province,
    		'branch'=>$branch,
    		'isDefault'=>'1',
    		'isRemember'=>'1',
    		'dwBank' => $dwBank,
    		'status' => $status,
    		'createdOn' => $createdOn,
    		'updatedOn' => $this->utils->getNowForMysql(),
    		'external_id'=> $external_id,
    	);

    	$this->db->select('playerBankDetailsId')->from('playerbankdetails')
    	->where('playerId', $playerId)->where('external_id', $external_id);
    	$playerBankDetailsId = $this->runOneRowOneField('playerBankDetailsId');

    	if (!empty($playerBankDetailsId)) {
    		$exists=true;
    		$this->db->set($data)->where('playerBankDetailsId', $playerBankDetailsId)->update('playerbankdetails');
    	} else {
    		$exists=false;
    		$this->db->set($data)->insert('playerbankdetails');
    		$playerBankDetailsId = $this->db->insert_id();
    	}

    	return $playerBankDetailsId;
    }

    public function exists_one_withdrawal_bank($playerId){
    	$this->db->select('playerBankDetailsId')->from('playerbankdetails')->where('playerId', $playerId)
    	->where('dwBank', self::WITHDRAWAL_BANK)
    	->where('status', self::STATUS_ACTIVE);

    	return $this->runExistsResult();
    }

	public function addPlayerBankDetailByAdmin($data) {
		$this->load->model(['operatorglobalsettings']);
		if (!array_key_exists('createdOn', $data)) {
			//add "now"
			$data['createdOn'] = $this->utils->getNowForMysql();
		}
		if (!array_key_exists('updatedOn', $data)) {
			//add "now"
			$data['updatedOn'] = $this->utils->getNowForMysql();
		}
		$this->db->insert('playerbankdetails', $data);
		$playerbankdetailsId = $this->db->insert_id();
		$enable_financial_account_can_be_withdraw_and_deposit_in_usdt = false;

		if($this->utils->getConfig('enable_financial_account_can_be_withdraw_and_deposit_in_usdt')){
			$data = $this->getBankCodeByBankType($data['bankTypeId']);
			if($this->utils->isCryptoCurrency($data)){
				$enable_financial_account_can_be_withdraw_and_deposit_in_usdt = true;
			}
		}

		if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_can_be_withdraw_and_deposit') || $enable_financial_account_can_be_withdraw_and_deposit_in_usdt){
			//copy to deposit/withdrawal
			$this->duplicateBankToDepositOrWithdrawal($playerbankdetailsId);
		}

		return $playerbankdetailsId;
	}

    public function duplicateBankToDepositOrWithdrawal($playerBankDetailsId){
    	$success=false;
    	if(!empty($playerBankDetailsId)){
	    	$this->db->from('playerbankdetails')->where('playerBankDetailsId', $playerBankDetailsId);
			$data = $this->runOneRowArray();

	    	if(!empty($data)){
	    		//remove id
	    		unset($data['playerBankDetailsId']);
				//change type
				if($data['dwBank']==Playerbankdetails::WITHDRAWAL_BANK){
			        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_deposit_account_default_unverified')){
			            $data['verified'] = '0';
			        }
			        else{
			        	$data['verified'] = self::VERIFIED;
			        }
					$data['dwBank']=Playerbankdetails::DEPOSIT_BANK;
					$dw = 'deposit';
				}elseif($data['dwBank']==Playerbankdetails::DEPOSIT_BANK){
			        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_default_unverified')){
			            $data['verified'] = '0';
			        }
			        else{
			        	$data['verified'] = self::VERIFIED;
			        }
					$data['dwBank']=Playerbankdetails::WITHDRAWAL_BANK;
					$dw = 'withdrawal';
				}
				$bank_list = $this->getBankDetails($data['playerId']);
				$message = '';
				$allow = self::AllowAddBankDetail($data['dwBank'], $bank_list[$dw], $message, $data['bankTypeId']);
				if($allow){
					//insert
					$success = $this->insertData('playerbankdetails', $data);
				}
	    	}
    	}

    	return $success;
    }

    /**
     * Check if given playerbankdetails is valid and belongs to player
     * @param	int		$playerBankDetailsId	== playerbankdetails.playerBankDetailsId
     * @param	int		$player_id				== player.playerId
     * @param	boolean	$deposit				true for deposit, false for withdraw account
     * @return	boolean	true if valid, false if invalid
     */
    public function isValidBankForPlayer($playerBankDetailsId, $player_id, $deposit = true) {
    	$dwBank = $deposit ? 0 : 1;
    	$this->db->from($this->tableName)
    		->select('COUNT(*) AS count')
    		->where($this->idField, $playerBankDetailsId)
    		->where('playerId', $player_id)
    		->where("dwBank = '{$dwBank}' ", null, false)
    		->where("IF(status IS NULL, '0', status) = '0'", null, false)
    	;

    	$res = $this->runOneRowOneField('count');

    	// $this->utils->printLastSQL();

    	return $res == 1 ? true : false;
    }

    /**
     * General bank account finder
     * Use with bank_type_id, bank_acc_num	- find among all players' deposit/withdraw accounts
     * Use with bank_type_id, bank_acc_num, player_id	- find among given player's all accounts
     * Use with bank_type_id, bank_acc_num, player_id, is_deposit	- find among given player's deposit/withdraw accounts
     * @param	int		$bank_type_id		the bankTypeId
     * @param	string	$bank_account_num	the bankAccountNumber
     * @param	int		$player_id			== playerbankdetails.playerId
     * @param	bool	$belong_to_player	will use 'playerId =' clause if true, 'playerId <>' otherwise
     * @param	int		$dwBank				0 for deposit, 1 for withdrawal.  Defaults to -1 (deposit/withdraw)
     *
     * @return	int		Count of accounts that matches the criteria
     */
	public function findBankAccountDuplicate($bank_type_id, $bank_account_num, $player_id = null, $belong_to_player = false, $dwBank = -1){
		$this->db->from('playerbankdetails')
			->select('COUNT(*) AS count')
			->where('bankTypeId', $bank_type_id)
			->where('bankAccountNumber', $bank_account_num)
			->where('status', self::STATUS_ACTIVE);

		if ($dwBank != -1) {
			$this->db->where('dwBank', $dwBank);
		}

		if (!empty($player_id)) {
			if ($belong_to_player) {
				$this->db->where('playerId', $player_id);
			}
			else {
				$this->db->where('playerId <>', $player_id);
			}
		}

		// $res = $this->runOneRowArray();
		$res = $this->runOneRowOneField('count');

		return $res;
	}

	/**
	 * check exist usdt account and return bank Details
	 * @param  string $playerId
	 * @param  string withdrawal or deposit
	 * @return bank Details
	 */
	public function getCryptoAccountByPlayerId($playerId, $type = 'deposit', $cryptoCurrency = null, $source = null){
		$result = array('not_exist' => $cryptoCurrency);
		$player_bank_details = $this->getBankDetails($playerId);
		$banks = $player_bank_details[$type];
		$crypto_account = 0;

        $this->utils->debug_log('getCryptoAccountByPlayerId', $banks);

        if(is_array($banks) && !empty($banks)){
        	foreach ($banks as $key => $bank_details) {
	            $this->utils->debug_log('check_bank_details', $bank_details['bank_code'], $cryptoCurrency, $bank_details);
	            if(strpos(strtoupper($bank_details['bank_code']),  $cryptoCurrency) !== false && $bank_details['status'] != self::STATUS_DELETED){
	            	$bank_details['bankName'] = lang($bank_details['bankName']);
	                $result = array($cryptoCurrency => $bank_details);
	                return $result;
	            }else{
					if($this->utils->getConfig('check_crypto_bank') && $type == "deposit" && $source == "payment"){
						if($bank_details['payment_type_flag'] == 3){
							$crypto_account += 1;
							$result = array($cryptoCurrency => '');
						}
					}
					if($crypto_account == 0){
						$result = array('not_exist' => $cryptoCurrency);
					}
	            }
	        }
        }

		return $result;
	}

	/**
	 * check exist bank code account and return bank Details
	 * @param  string $playerId
	 * @param  string withdrawal or deposit
	 * @param  string bank code
	 * @return bank Details
	 */
	public function getAccountDetailsByPlayerIdAndBankCode($playerId, $type = 'deposit', $bankCode = null){
		$result = false;
		$player_bank_details = $this->getBankDetails($playerId);
		$banks = $player_bank_details[$type];

		foreach ($banks as $key => $bank_details) {
            if(strpos(strtoupper($bank_details['bank_code']), $bankCode) !== false){
                $result = $bank_details;
                return $result;
            }
        }
		return $result;
	}

	/**
	 * check exist usdt account and return bank Details
	 * @param  string $playerId
	 * @param  string $type withdrawal or deposit
	 * @param  string $cryptoCurrency compare to bankCode
	 * @param  string $address crypto address
	 * @param  string $chainName crypto network
	 * @return mixed bank Details or null  
	 */
	public function getCryptoAccountByAddressAndChainName($playerId, $type, $cryptoCurrency, $address, $chainName){
		$this->load->model(['financial_account_setting', 'banktype']);
        $options = [
            'only_banktype_active' => true,
            'exclude_status' => [self::STATUS_INACTIVE, self::STATUS_DELETED],
            'bankaccount_number' => $address,
            'branch' => $chainName,
            'payment_type_flag' => Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO
        ];
		$player_bank_details = $this->getNotDeletedBankInfoList($playerId, $options);
		if(!empty($player_bank_details)){
			switch ($type) {
				case 'deposit':
					$banks = $player_bank_details['deposit'];
					break;
				case 'withdrawal':
					$banks = $player_bank_details['withdrawal'];
					break;
				default:
					$banks = $player_bank_details;
					break;
			}

	        $this->utils->debug_log('getCryptoAccountByAddress', $banks);
	        foreach ($banks as $bank_details) {
                if($this->banktype->isBankCodeMatchCoinIdAndChainName($cryptoCurrency, $chainName, $bank_details['bank_code'])){
                    return $bank_details;
                }
	        }
		}
		return null;
	}

	public function getPlayerDepositByBankCode($player_id, $bank_code) {
		$this->db->select("playerBankDetailsId, bank_code, bankAccountNumber, bankAccountFullName")->from('playerbankdetails')
		    ->join('banktype', 'playerbankdetails.bankTypeId = banktype.bankTypeId')
		    ->where('playerbankdetails.playerId', $player_id)
		    ->where('banktype.bank_code', $bank_code)
		    ->where('playerbankdetails.status', self::STATUS_ACTIVE)
		   	->where('playerbankdetails.dwBank', self::DEPOSIT_BANK);

		return $this->runOneRowArray();
	}

	public function getPlayerWithdrawalByBankCode($player_id, $bank_code) {
		$this->db->select("playerBankDetailsId, bank_code, bankAccountNumber, bankAccountFullName")->from('playerbankdetails')
		    ->join('banktype', 'playerbankdetails.bankTypeId = banktype.bankTypeId')
		    ->where('playerbankdetails.playerId', $player_id)
		    ->where('banktype.bank_code', $bank_code)
		    ->where('playerbankdetails.status', self::STATUS_ACTIVE)
		    ->where('playerbankdetails.dwBank', self::WITHDRAWAL_BANK);

		return $this->runOneRowArray();
	}

	/**
	 * create a pix account
	 * @param [int] $playerId
	 * @param [int] $bankTypeId
	 * @param [string] $fullName
	 * @param [string] $pixNumber
	 * @param [string] $type  referrence by Banktype model
	 * @param [int] $dwbank
	 */
	public function addPixAccount($playerId, $bankTypeId, $fullName, $pixNumber, $type, $dwbank) {
		$data = [
			'playerId' => $playerId,
			'bankTypeId' => $bankTypeId,
			'bankAccountFullName' => $fullName,
			'bankAccountNumber' => $pixNumber,
			'branch' => $type,
			'dwBank' => $dwbank,
			'bankAddress' => '',
			'city' => '',
			'province' => '',
			'verified' => '1',
			'status' => '0',
			'phone' => '',
		];

		if (!array_key_exists('createdOn', $data)) {
			//add "now"
			$data['createdOn'] = $this->utils->getNowForMysql();
		}
		if (!array_key_exists('updatedOn', $data)) {
			//add "now"
			$data['updatedOn'] = $this->utils->getNowForMysql();
		}
		$this->db->insert('playerbankdetails', $data);
		$playerbankdetailsId = $this->db->insert_id();

		return $playerbankdetailsId;
	}

	public function updatePixAccount($playerBankDetailsId, $fullName, $pixNumber) {
		$data = [
			'bankAccountFullName' => $fullName,
			'bankAccountNumber' => $pixNumber,
			'updatedOn' =>  $this->utils->getNowForMysql(),
		];

		return $this->db->where('playerBankDetailsId', $playerBankDetailsId)
					    ->update('playerbankdetails', $data);
	}

	public function getPixAccountInfo($playerId){
		$this->load->model(['banktype']);
		$pixAccountType = [banktype::PIX_TYPE_EMAIL, banktype::PIX_TYPE_PHONE, banktype::PIX_TYPE_CPF];
		$pixAccountInfo[self::DEPOSIT_BANK] = [];
		$pixAccountInfo[self::WITHDRAWAL_BANK] = [];
		foreach ($pixAccountType as $type) {
			$pixAccountInfo[self::DEPOSIT_BANK][$type] = $this->getPlayerPixInfoByType($playerId, $type, self::DEPOSIT_BANK);
			$pixAccountInfo[self::WITHDRAWAL_BANK][$type] = $this->getPlayerPixInfoByType($playerId, $type, self::WITHDRAWAL_BANK);
		}
		return $pixAccountInfo;
	}
	
	private function getPlayerPixInfoByType($playerId, $type, $transactionType) {
		$accountInfo = [];
		if($transactionType == self::DEPOSIT_BANK){
			$accountInfo = $this->getPlayerDepositByBankCode($playerId, $type);
		}
		if($transactionType == self::WITHDRAWAL_BANK){
			$accountInfo =$this->getPlayerWithdrawalByBankCode($playerId, $type);
		}
		if (!empty($accountInfo)) {
			return [
				'bankDetailsId' => $accountInfo['playerBankDetailsId'],
				'bankAccountNumber' => $accountInfo['bankAccountNumber'],
				'bankAccountFullName' => $accountInfo['bankAccountFullName']
			];
		}
		return $accountInfo;
	}

	public function autoBuildPlayerPixAccount($playerId){
		$this->load->model(['banktype', 'player_model', 'player', 'users']);
		$pixSystemInfo    = $this->utils->getConfig('pix_system_info');
		$enabledAutoBuild = $pixSystemInfo['auto_build_pix_account']['enabled'];
		$allowPixType     = $pixSystemInfo['auto_build_pix_account']['allow_type'];
		$counterAllowType = [];
		$bankDetailsIds   = [];
		if(!$enabledAutoBuild){
			return false;
		}
		foreach ($allowPixType as $type) {
			$counterAllowType[$type] = true;
		}
		$this->utils->debug_log(__METHOD__, '=====autoBuildPlayerPixAccount allow type', $counterAllowType);
		$playerInfo = $this->player_model->getPlayerDetailsById($playerId);
		$fullName = trim("{$playerInfo->lastName} {$playerInfo->firstName}");
		$playerPixAccInfo = $this->getPixAccountInfo($playerId);
		foreach($playerPixAccInfo as $transactionType => $data){
			foreach ($data as $pixType => $playerAccInfo) {
				$updateFlag = false;
				if(isset($counterAllowType[$pixType])){
					$bankTyepId  = $this->banktype->getBankTypeIdByBankcode($pixType);
					$pixNumber = '';
					switch ($pixType) {
						case Banktype::PIX_TYPE_EMAIL:
							$pixNumber = !empty($playerInfo->email)? $playerInfo->email : '';
							break;
		
						case Banktype::PIX_TYPE_PHONE:
							$pixNumber = !empty($playerInfo->contactNumber)? $playerInfo->contactNumber : '';
							break;
		
						case Banktype::PIX_TYPE_CPF:
							$pixNumber = !empty($playerInfo->cpfNumber)? $playerInfo->cpfNumber : '';
							break;
					}

					if(!empty($pixNumber) && !empty($bankTyepId)){
						if(empty($playerAccInfo)){
							$bankDetailsId = $this->addPixAccount($playerId, $bankTyepId, $fullName, $pixNumber, $pixType, (string)$transactionType);
							array_push($bankDetailsIds, $bankDetailsId);
							$updateFlag = true;
						}else{
							$isDiff = ($pixNumber != $playerAccInfo['bankAccountNumber'] || $fullName != $playerAccInfo['bankAccountFullName']);
							$bankDetailsId = $playerAccInfo['bankDetailsId'];
							if($isDiff){
								$updateSucc = $this->updatePixAccount($bankDetailsId, $fullName, $pixNumber);
								if($updateSucc){
									array_push($bankDetailsIds, $bankDetailsId);
									$updateFlag = true;
								}
							}
						}
						if($updateFlag){
							//save bank history
							$changes = array(
								'playerBankDetailsId' => $bankDetailsId,
								'changes' => "Auto build pix account, type is $pixType",
								'createdOn' => date("Y-m-d H:i:s"),
								'operator' => $this->users->getSuperAdmin()->username,
							);
							$this->player->saveBankChanges($changes);
						}
					}
				}
			}

		}
		return $bankDetailsIds;		
	}

}

///END OF FILE////////