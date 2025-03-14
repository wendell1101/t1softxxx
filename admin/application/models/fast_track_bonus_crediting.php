<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 * overview : Class Fast_track_bonus_crediting
 *
 * General behaviors include :
 *
 * * Get bonus crediting
 *
 * @version 1.0.0
 * @copyright 2013-2022 tot
 */
class Fast_track_bonus_crediting extends BaseModel {

    protected $bonust_credit_table = 'fast_track_bonus_crediting';
    protected $bonus_creat_funds_table = 'fast_track_bonus_crediting_funds';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * overview : get bonus crediting list
	 *
	 * @return array
	 */
	public function isPlayerAllowedToClaimBonus($playerId, $promorulesId) {
	    $result = false;
        $this->db->from($this->bonust_credit_table);
	    $this->db->where('playerId', $playerId);
	    $this->db->where('promorulesId', $promorulesId);
        $this->db->where('expirationDate is NULL');
        $this->db->or_where('expirationDate >', $this->utils->getNowForMysql());

        if($this->runExistsResult()){
            $result = true;
        }

        return $result;
	}

    public function addPromoToPlayer($playerId, $promorulesId, $expirationDate = null, $bonus_code, $request_params = '') {
        $result = $this->db->insert($this->bonust_credit_table, [
            'playerId' => $playerId,
            'promorulesId' => $promorulesId,
            'created_at' => $this->utils->getNowForMysql(),
            'expirationDate' => $expirationDate,
            'request_params' => $request_params,
            'bonus_code' => $bonus_code,
        ]);

        return $result;
    }

    public function requestBonusCreditFunds($playerId, $params, $bonus_type = 'cashback'){
        $this->db->insert($this->bonus_creat_funds_table, [
            'playerId' => $playerId,
            'request_params' => $params,
            'bonus_type' => $bonus_type,
            'created_at' => $this->utils->getNowForMysql()
        ]);

        return $this->db->insert_id();
    }

    public function getFirstAvailableBonusFunds($playerId, $bonus_type = 'cashback') {
        $this->db->from($this->bonus_creat_funds_table);
        $this->db->where('playerId', $playerId);
        $this->db->where('bonus_type', $bonus_type);
        $this->db->where('cashback_transaction_id is NULL');

        $bonuses = $this->runMultipleRowArray();
        if(!empty($bonuses)){
            return $bonuses[0];
        }
        return [];
    }

    public function getFirstAvailableBonus($playerId, $bonus_code) {
        $this->db->from($this->bonust_credit_table);
        $this->db->where('playerId', $playerId);
        $this->db->where('bonus_code', $bonus_code);
        $this->db->where('player_promo_id is NULL');
        $this->db->where('expirationDate is NULL');
        $this->db->or_where('expirationDate >', $this->utils->getNowForMysql());

        $bonuses = $this->runMultipleRowArray();
        if(!empty($bonuses)){
            return $bonuses[0];
        }
        return [];
    }

    public function getAllAvailableBonus($playerId) {
        $this->db->from($this->bonust_credit_table);
        $this->db->where('playerId', $playerId);
        $this->db->where('player_promo_id is NULL');
        $this->db->where('expirationDate is NULL');
        $this->db->where('bonus_code is not NULL');
        $this->db->or_where('expirationDate >', $this->utils->getNowForMysql());

        $bonuses = $this->runMultipleRowArray();
        if(!empty($bonuses)){
            return $bonuses;
        }
        return [];
    }

    public function updateBonusById($id, $player_promo_id) {
        $this->db->where('id', $id)
            ->set([
                'player_promo_id' => $player_promo_id,
            ]);

        $this->runAnyUpdate($this->bonust_credit_table);

    }

    public function updateBonusFundsById($id, $player_promo_id) {
        $this->db->where('id', $id)
            ->set([
                'cashback_transaction_id' => $player_promo_id,
            ]);

        $this->runAnyUpdate($this->bonus_creat_funds_table);

    }
}

///END OF FILE
