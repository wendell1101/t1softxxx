<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Cms_navigation_settings extends BaseModel {

    private $tableName = "cms_navigation_settings";

    function __construct() {
        parent::__construct();
    }

    public function findById($id) {
        $this->db->where('id', $id);
        $this->db->from($this->tableName);
        return $this->runOneRowArray();
    }

    public function updateById($id, $params) {
        $this->db->where('id', $id);
        $updated = $this->db->update($this->tableName, $params);

        return $updated;
    }

    public function deleteOldIconById($id) {
        $game_type = $this->findById($id);
        if($game_type['icon'] != null) {
            $path = $this->utils->getUploadPath();
            $this->utils->addSuffixOnMDB($path);
            $path .= '/cms_game_types';
            if(file_exists($path . '/' . $game_type['icon'])) {
                unlink($path . '/' . $game_type['icon']);
            }
        }
    }

    public function insertMissingGameType($params) {
        $game_types = $this->getGameTypes();
        $game_types = array_column($game_types, null, 'game_type_code');
        $success = true;
        foreach ($params as $param) {
            if(!array_key_exists($param['game_type_code'], $game_types)) {
                $success = $this->db->insert($this->tableName, $param);
                if(!$success) {
                    break;
                }
            }
        }
        return $success;
    }

    public function getGameTypes($status = NULL) { // add default value in status so that it will not affect the other module like compapi_settings_cache.php
        $this->db->from($this->tableName)->order_by('status', 'desc')->order_by('order', 'desc');

        if($status!==null) {
            $this->db->where('status',$status);
        }

        return $this->runMultipleRowArray();
    }
}