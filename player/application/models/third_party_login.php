<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Third_Party_Login
 *
 */

class Third_party_login extends CI_Model {

    protected $tableName = 'third_party_login';
    protected $line_tableName = 'line_players';
    protected $facebook_tableName = 'facebook_players';
    protected $google_tableName = 'google_players';
    protected $ole_sso_tableName = 'ole_sso_player';
    protected $ole_auth_tableName = 'ole_auth_player';

    const THIRD_PARTY_LOGIN_TYPE_LINE = 'Line';
    const THIRD_PARTY_LOGIN_TYPE_FACEBOOK = 'Facebook';
    const THIRD_PARTY_LOGIN_TYPE_GOOGLE = 'Google';
    const THIRD_PAETY_LOGIN_TYPE_OLE = 'OLE';


    const THIRD_PARTY_LOGIN_STATUS_REQUEST = 0;
    const THIRD_PARTY_LOGIN_STATUS_AUTH    = 1;
    const THIRD_PARTY_LOGIN_STATUS_SUCCESS = 2;
    const THIRD_PARTY_LOGIN_STATUS_FAILED  = 3;

    function __construct() {
        parent::__construct();
    }

    public function insertThirdPartyLogin($uuid, $ip, $status, $extra_info = null, $pre_register_form = null) {
        $data['uuid']       = $uuid;
        $data['access_ip']   = $ip;
        $data['status']     = $status;
        $data['extra_info'] = $extra_info;
        $data['pre_register_form'] = $pre_register_form;
        return $this->db->insert($this->tableName, $data);
    }

    public function getThirdPartyLoginByUuid($uuid) {
        $this->db->from($this->tableName);
        $this->db->where('uuid', $uuid);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function updateThirdPartyLoginByUuid($uuid, $data) {
        $this->db->where('uuid', $uuid);
        $this->db->update($this->tableName, $data);
    }

    // ===== Line login =====
    public function insertLinePlayers($data) {
        return $this->db->insert($this->line_tableName, $data);
    }

    public function updateLinePlayersByUserId($line_user_id, $data) {
        $this->db->where('line_user_id', $line_user_id);
        $this->db->update($this->line_tableName, $data);
    }

    public function getLinePlayersByUserId($line_user_id) {
        $this->db->from($this->line_tableName);
        $this->db->where('line_user_id', $line_user_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function getLineInfoByPlayerId($player_id) {
        $this->db->from($this->line_tableName);
        $this->db->where('player_id', $player_id);
        $query = $this->db->get();
        return $query->row();

    }
    // ===== Line login End =====

    // ===== Facebook login =====
    public function insertFacebookPlayers($data) {
        return $this->db->insert($this->facebook_tableName, $data);
    }

    public function updateFacebookPlayersByUserId($facebook_user_id, $data) {
        $this->db->where('facebook_user_id', $facebook_user_id);
        $this->db->update($this->facebook_tableName, $data);
    }

    public function getFacebookPlayersByUserId($facebook_user_id) {
        $this->db->from($this->facebook_tableName);
        $this->db->where('facebook_user_id', $facebook_user_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function getFacebookInfoByPlayerId($player_id) {
        $this->db->from($this->facebook_tableName);
        $this->db->where('player_id', $player_id);
        $query = $this->db->get();
        return $query->row();

    }
    // ===== Facebook login End =====

    // ===== Google login =====
    public function insertGooglePlayers($data) {
        return $this->db->insert($this->google_tableName, $data);
    }

    public function updateGooglePlayersByUserId($google_user_id, $data) {
        $this->db->where('google_user_id', $google_user_id);
        $this->db->update($this->google_tableName, $data);
    }

    public function getGooglePlayersByUserId($google_user_id) {
        $this->db->from($this->google_tableName);
        $this->db->where('google_user_id', $google_user_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function getGoogleInfoByPlayerId($player_id) {
        $this->db->from($this->google_tableName);
        $this->db->where('player_id', $player_id);
        $query = $this->db->get();
        return $query->row();
    }
    // ===== Google login End =====

    // ===== OLE Login =====
    public function getPlayerByOleId($ole_user_id){
        $this->db->from($this->ole_auth_tableName);
        $this->db->where('ole_user_id', $ole_user_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function getPlayerByPlayerId($table, $player_id){
        $this->db->from($table);
        $this->db->where('player_id', $player_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function insertOlePlayers($table, $data) {
        return $this->db->insert($table, $data);
    }

    public function updatePlayersByPlayerId($table, $playerId, $data){
        if($table == $this->ole_sso_tableName){
            $this->db->where('player_id', $playerId);
        }else if($table == $this->ole_auth_tableName){
            $this->db->where('ole_user_id', $playerId);
        }
        return $this->db->update($table, $data);
    }
    
    public function getPlayerByAccessToken($table, $access_token, $date = null){
        $this->db->from($table);
        $this->db->where('access_token', $access_token);
        if($table == $this->ole_sso_tableName){
            $this->db->where('status', 1);
            $this->db->where('expiration_date > ', $date);
        }
        $query = $this->db->get();
        return $query->row_array();
    }
    // ===== OLE Login End =====
}