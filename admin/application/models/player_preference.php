<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 * Handles the following player preferences:
 * * auto_transfer: Value = 0 / 1, controls whether transfer all money to game upon game launch
 */
class Player_preference extends BaseModel {

    private $tableName = 'player_preference';

    public function __construct() {
        parent::__construct();
    }

    public function isAutoTransferOnGameLaunch($playerId) {
        $playerPreference = $this->getPlayerPreference($playerId);
        if(empty($playerPreference) || !array_key_exists('auto_transfer', $playerPreference)) {
            return false;
        }
        return $playerPreference['auto_transfer'] == 1;
    }

    public function savePlayerPreference($playerId, $preference, $myfavorites = []) {
        $this->utils->deleteCache($this->getCacheKey($playerId));
        $inputPreferenceString = json_encode($preference);
        $inputMyfavoritesString = json_encode($myfavorites);
        $this->utils->debug_log("Saving player preference [$inputPreferenceString] for player [$playerId]");
        if(!$this->hasPlayerPreference($playerId)) {
            $this->db->insert($this->tableName,
                array(
                    'player_id' => $playerId,
                    'preference' => $inputPreferenceString,
                    'myfavorites' => $inputMyfavoritesString,
                    'created_at' => $this->utils->getNowForMysql(),
                    'updated_at' => $this->utils->getNowForMysql(),
                )
            );
        } else {
            $this->db->update($this->tableName,
                array(
                    'preference' => $inputPreferenceString,
                    'myfavorites' => $inputMyfavoritesString,
                    'updated_at' => $this->utils->getNowForMysql(),
                ),
                array('player_id' => $playerId)
            );
        }
    }

    public function getPlayerPreference($playerId) {
        $playerPreference = $this->utils->getJsonFromCache($this->getCacheKey($playerId));
        if(empty($playerPreference)) {
            $query = $this->db->get_where($this->tableName, array('player_id' => $playerId));
            $result = $query->result_array();
            if(empty($result) || empty($result[0]) || !array_key_exists('preference', $result[0])) {
                return $this->getDefaultPreference();
            } else {
                $playerPreference = json_decode($result[0]['preference'], true);
                $this->utils->saveJsonToCache($this->getCacheKey($playerId), $playerPreference);
            }
        }
        return $playerPreference;
    }

    public function getPlayerMyFavorites($playerId) {
        $query = $this->db->get_where($this->tableName, array('player_id' => $playerId));

        $result = $this->getOneRowArray($query);
        return (empty($result)) ? [] : @json_decode($result['myfavorites'], TRUE);
    }

    public function updatePlayerMyFavorites($playerId, $game_list, $sort = []){
        $current_myfavorites = $this->getPlayerMyFavorites($playerId);

        foreach($current_myfavorites as $game_id => $game_myfavorite){
            foreach($game_list as $game){
                if($game_id == $game['id']){
                    continue;
                }

                unset($current_myfavorites[$game_id]);
            }
        }

        foreach($game_list as $game){
            if(isset($current_myfavorites[$game['id']])){
                continue;
            }

            $current_myfavorites[$game['id']] = [
                // TODO
            ];
        }

        $player_myfavorite_limit_count = $this->utils->getConfig('player_myfavorite_limit_count');

        if(count($current_myfavorites) > $player_myfavorite_limit_count){
            $remove_keys = array_slice(array_keys($current_myfavorites), 0, count($current_myfavorites) - $player_myfavorite_limit_count);
            foreach($remove_keys as $key){
                unset($current_myfavorites[$key]);
            }
        }

        return $this->savePlayerPreference($playerId, $this->getPlayerPreference($playerId), ((empty($game_list)) ? [] : $current_myfavorites));
    }

    private function hasPlayerPreference($playerId) {
        $query = $this->db->get_where($this->tableName, array('player_id' => $playerId));
        return $query->num_rows() > 0;
    }

    private function getDefaultPreference() {
        return $this->utils->getConfig('default_player_preference');
    }

    private function getCacheKey($name) {
        return PRODUCTION_VERSION."|$this->tableName|$name";
    }

    public function storePrefItem($player_id, $pref_key, $pref_val) {
        $pref = $this->getPlayerPreference($player_id);
        $pref[$pref_key] = $pref_val;
        $this->savePlayerPreference($player_id, $pref);
    }

    public function getPlayerDisabledWithdrawalUntilByPlayerId($playerId) {
		$this->db->select('disabled_withdrawal_until')->from($this->tableName)->where('player_id', $playerId);
		$result = $this->runOneRowArray();

        if(empty($result)) {
            $result = [];
        }

        return $result;
	}

    public function disableWithdrawalUntilByPlayerId($playerId, $disabled_withdrawal_until) {
        $this->db->select('player_id')->from($this->tableName)->where('player_id', $playerId);
        $query_result = $this->runOneRowArray();

        if(!empty($query_result)) {
            $this->db->where('player_id', $playerId)->update($this->tableName, ['updated_at' => $this->utils->getNowForMysql(), 'disabled_withdrawal_until' => $disabled_withdrawal_until]);
        }else{
            $this->db->insert($this->tableName, ['player_id' => $playerId,
                                                'created_at' => $this->utils->getNowForMysql(),
                                                'updated_at' => $this->utils->getNowForMysql(),
                                                'disabled_withdrawal_until' => $disabled_withdrawal_until
                                            ]);
        }
	}

    public function enablePlayerWithdrawalByCurrentDate($current_datetime) {
        $this->load->model(['player_model']);
        $this->db->select('player_id')->from($this->tableName)->where('disabled_withdrawal_until <=', $current_datetime);
        $query_results = $this->runMultipleRowArray();

        $player_ids = [];
        if(!empty($query_results)) {
            foreach($query_results as $key => $result_player_ids) {
                foreach($result_player_ids as $key => $player_id) {
                    array_push($player_ids, $player_id);
                }
            }

            $this->player_model->enableWithdrawalByPlayerIds($player_ids);

            $this->db->where_in('player_id', $player_ids)->set(['updated_at' => $this->utils->getNowForMysql(), 'disabled_withdrawal_until' => null]);
            return $this->runAnyUpdateWithResult($this->tableName);
        }

        return 0;
	}

    public function getPlayerPreferenceDetailsByPlayerId($playerId) {
		$this->db->select('id, player_id, preference, myfavorites, disabled_withdrawal_until, created_at, updated_at')->from($this->tableName)->where('player_id', $playerId);
		$query = $this->db->get();

        if(!$query->row_array()) {
            return false;
        }else{
            return $query->row_array();
        }
    }

    public function checkPlayerWithdrawalUntilByPlayerId($playerId) {
        $this->load->model(['player_model']);

        $player = $this->player_model->getPlayer(['playerId' => $playerId]);
        $playerPreferenceDetails =  $this->getPlayerPreferenceDetailsByPlayerId($playerId);
        $currentDate = date('Y-m-d H:i:s');
        $disable_until_datetime = null;
        $updatedBy = lang('Auto Update (Computer)');
        $management = 'player_management';
        $action = 'Withdrawal Status';
        $status = 'enable';

        $data = [
            'playerId' => $playerId,
            'changes' => lang('Enable Player Withdrawal'),
            'createdOn' => $currentDate,
            'operator' => $updatedBy,
        ];

        if(!empty($playerPreferenceDetails['disabled_withdrawal_until']) && $playerPreferenceDetails['disabled_withdrawal_until'] <= $currentDate) {
            $result = $this->player_model->enableWithdrawalByPlayerId($playerId);
            $this->disableWithdrawalUntilByPlayerId($playerId,  $disable_until_datetime);
            $this->utils->debug_log(__METHOD__ . ' Player withdrawal enabled', $result);
            $this->db->insert('playerupdatehistory', $data);
            $this->utils->recordAction($management, $action, $updatedBy . " " . $status." withdrawal of player " . $player['username']);
        }
    }

    /**
     * Get the player_preference.username_on_register, default as player.username
     *
     * @param integer $playerId The player.playerId
     * @return string The username_on_register String.
     */
    public function getUsernameOnRegisterByPlayerId($playerId) {
        $this->load->model(['player_model']);

        $username_on_register = null;
		$this->db->select('username_on_register')->from($this->tableName)->where('player_id', $playerId);
		$result = $this->runOneRowArray();
        if( ! empty($result)) {
            if( ! empty($result['username_on_register']) ){
                $username_on_register = $result['username_on_register'];
            }
        }
        if( empty($username_on_register) ){
            $player = $this->player_model->getPlayer(['playerId' => $playerId]);
            if( ! empty($player['username']) ){
                $username_on_register = $player['username'];
            }
        }
        return $username_on_register;
	}


    /**
     * Just store (update) the username_on_register in table.
     *
     * @param string $username_on_register
     * @param integer $playerId
     * @return bool It always be true.
     */
    public function storeUsernameOnRegister($username_on_register, $playerId){

        $row = $this->getPlayerPreferenceDetailsByPlayerId($playerId);
        if( $row == false ){
            // Not found data by playerId
            $current_myfavorites = $this->getPlayerMyFavorites($playerId);
            $game_list = [];
            $this->savePlayerPreference($playerId, $this->getPlayerPreference($playerId), ((empty($game_list)) ? [] : $current_myfavorites));
        }

        $this->db->update($this->tableName,
            array(
                'username_on_register' => $username_on_register,
                'updated_at' => $this->utils->getNowForMysql(),
            ),
            array('player_id' => $playerId)
        );
        return true;
    }

}
