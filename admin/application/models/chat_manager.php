<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Depositpromo
 *
 * This model represents promo type.
 *
 */

class Chat_manager extends BaseModel {

	protected $tableName = 'player_chat_access_tokens';

	function __construct() {
		parent::__construct();
	}

    public function createChatToken($data) {
        return $this->runInsertDataWithBoolean($this->tableName, $data);
    }

    public function getChatToken($username, $chatRoomId) {
        $this->db->from($this->tableName)
                 ->where('user_id', $username)
                 ->where('room_id', $chatRoomId)
                 ->where('revoked', self::DB_FALSE);
        return $this->runOneRowOneField('id');
    }

    public function getChatTokenLang($token){
        $this->db->from($this->tableName)
                 ->where('id', $token)
                 ->where('revoked', self::DB_FALSE);
        return $this->runOneRowOneField('language');
    }

    public function getPlayerIdByToken($token){
        $this->db->from($this->tableName)
                 ->where('id', $token)
                 ->where('revoked', self::DB_FALSE);
        return $this->runOneRowOneField('user_id');
    }

    public function getChatTokenByToken($token){
        $this->db->from($this->tableName)
                 ->where('id', $token)
                 ->where('revoked', self::DB_FALSE);
        return $this->runOneRowArray();
    }

    public function revokeChatToken($username){
        $this->db->where('user_id', $username);
        $this->db->where('revoked', self::DB_FALSE);
		$this->db->update($this->tableName, ['revoked' => self::DB_TRUE]);
        // return $this->db->affected_rows() > 0;
    }
}
