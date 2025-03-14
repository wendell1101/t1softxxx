<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Cms_navigation_game_platform extends BaseModel {

    private $tableName = "cms_navigation_game_platform";

    function __construct() {
        parent::__construct();
    }


    public function findById($id) {
        $this->db->where('id', $id);
        $this->db->from($this->tableName);
        return $this->runOneRowArray();
    }

    public function findGamePlatformsByNavigationSettingId($id) {
        $this->db->where('navigation_setting_id', $id);
        $this->db->from($this->tableName)->order_by('status', 'desc')->order_by('order', 'desc');
        return $this->runMultipleRowArray();
    }

    public function updateById($id, $params) {
        $this->db->where('id', $id);
        $updated = $this->db->update($this->tableName, $params);

        return $updated;
    }

    public function deleteOldIconById($id) {
        $game_platform = $this->findById($id);
        if($game_platform['icon'] != null) {
            $path = $this->utils->getUploadPath();
            $this->utils->addSuffixOnMDB($path);
            $path .= '/cms_game_platforms';
            if(file_exists($path . '/' . $game_platform['icon'])) {
                unlink($path . '/' . $game_platform['icon']);
            }
        }
    }

    public function insertMissingGamePlatform($params) {
        $game_platforms = $this->getGamePlatforms();

        $result = [];
        foreach($game_platforms as $game_platform) {
            $result[$game_platform['navigation_setting_id']][$game_platform['game_platform_id']] = $game_platform;
        }

        $game_platforms = $result;
        unset($result);

        $success = true;
        foreach ($params as $param) {
            if(
                !isset($game_platforms[$param['navigation_setting_id']])
                || (isset($game_platforms[$param['navigation_setting_id']]) && !array_key_exists($param['game_platform_id'], $game_platforms[$param['navigation_setting_id']]))
                ) {
                $success = $this->db->insert($this->tableName, $param);
                if(!$success) {
                    break;
                }
            }
        }
        return $success;
    }

    public function getGamePlatforms() {
        $this->db->from($this->tableName)->order_by('status', 'desc')->order_by('order', 'desc');
        $game_platforms = $this->runMultipleRowArray();
        return $game_platforms;
    }
}