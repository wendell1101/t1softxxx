<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class player_crypto_wallet_info extends BaseModel {
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;
	public function __construct() {
		parent::__construct();
	}

	protected $tableName = 'player_crypto_wallet_info';

    public function getPlayerCryptoWallets($playerId) {
        $this->db->from($this->tableName);
        $this->db->where('playerId', $playerId);
        $this->db->where('status !=', self::STATUS_DELETE);
        return $this->runMultipleRowArray();
    }

    public function getPlayerCryptoWalletByChain($playerId, $chain, $token) {
        $this->db->from($this->tableName);
        $this->db->where('playerId', $playerId);
        $this->db->where('chain', $chain);
        $this->db->where('token', $token);
        $this->db->where('status !=', self::STATUS_DELETE);
		return $this->runOneRowArray();
    }

    public function getPlayerCryptoAddressForAPI($playerId, $token = '') {
        $this->db->from($this->tableName);
        $this->db->where('playerId', $playerId);
        $this->db->where('status !=', self::STATUS_DELETE);
    
        if(!empty($token)){
            $this->db->where('token', $token);
        }

        $rows = $this->runMultipleRowArray();
        if(!empty($rows)){
            foreach($rows as $row){
                $data['coinId'] = $row['token'];
                $data['chains'][] = [
                    'chainName' => $row['chain'],
                    'address' => $row['address'],
                    'allowDeposit' => true,
                    'allowWithdrawal' => true,
                ];
            }
            return $data;
        }
        return null;
    }

    public function checkExistedAddress($address) {
        $this->db->from($this->tableName);
        $this->db->where('address', $address);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
    }

    public function updateCryptoWalletInfo($id, $data) {
        $this->db->where('id', $id);
        $this->db->update($this->tableName, $data);
        return $this->db->affected_rows();
    }

    public function insertCryptoWalletInfo($playerId, $cryptoInfo){
        $insertData = [
            'playerId' => $playerId,
            'token' => $cryptoInfo['token'],
            'chain' => $cryptoInfo['chain'],
            'network' => $cryptoInfo['network'],
            'address' =>  $cryptoInfo['address'],
            'externalSystemId' => $this->config->item('crypto_currency_use_api'),
            'status' => Player_crypto_wallet_info::STATUS_ACTIVE,
        ];
        return $this->insertData($this->tableName, $insertData);
    }

    public function insertCryptoWalletInfoFromAPI($playerId, $data) {
        if(empty($playerId) || !is_array($data) || empty($data)){
            return false;
        }   
        $cryptoWalletIds = [];
        $externalSystemId = $this->config->item('crypto_currency_use_api');
        $wapperData = [];
        $wapperData['playerId'] = $playerId;
        $wapperData['externalSystemId'] = $externalSystemId;
        $wapperData['status'] = self::STATUS_ACTIVE;
        
        if(empty($data['chains']) || !is_array($data['chains']) ){
            return false;
        }
        $wapperData['token'] = $data['coinId'];   
        foreach($data['chains'] as $chain){
            $wapperData['chain'] = $chain['chainName'];
            $wapperData['address'] = $chain['address'];
            $wapperData['network'] = $this->getNetworkWithChain($wapperData['token'], $chain['chainName']);
            $this->db->insert($this->tableName, $wapperData);
            array_push($cryptoWalletIds, $this->db->insert_id());
        }

        if(!empty($cryptoWalletIds)){
            return $cryptoWalletIds;
        }else{
            return false;
        }
    }

    public function getNetworkWithChain($token, $chain){
        $mappingNetwork = [
            CRYPTO_CURRENCY_COIN_USDT => [
                CRYPTO_CURRENCY_CHAIN_ETH => CRYPTO_NETWORK_ERC20,
                CRYPTO_CURRENCY_CHAIN_TRON => CRYPTO_NETWORK_TRC20,
            ],
        ];

        if(isset($mappingNetwork[$token][$chain])){
            return $mappingNetwork[$token][$chain];
        }
        return '';
    }
}