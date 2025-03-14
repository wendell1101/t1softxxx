<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

/**
 * General behaviors include :
 *
 * * Get game tag list data by id, tag code
 *
 * @category Game Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_tag_list extends BaseModel {

	public function __construct() {
		parent::__construct();
	}

	protected $tableName = "game_tag_list";

    public function getTagByGameIdAndTagId($gameId, $tagId, $status=1) {
		$qry = $this->db->get_where($this->tableName, array('game_description_id' => $gameId, 'tag_id' => $tagId, 'status' => $status));
		return $this->getOneRowArray($qry);
	}

    public function getTagById($id) {
		$qry = $this->db->get_where($this->tableName, array('id' => $id));
		return $this->getOneRowArray($qry);
	}

    public function getTagByTagIdAndOrder($tagId, $order, $status=1) {
		$qry = $this->db->get_where($this->tableName, array('game_order' => $order, 'tag_id' => $tagId, 'status' => $status));
		return $this->getOneRowArray($qry);
	}

    public function deleteTag($id) {
        if(empty($id)){
            return false;
        }
        return $this->db->delete($this->tableName, array('id' => $id));
	}

    public function getLastInsertedGameTagByGameDescriptionId($game_description_id, $order_by = 'game_order', $order_type = 'desc') {
        $this->db->from($this->tableName)
        ->where('game_description_id', $game_description_id)
        ->order_by($order_by, $order_type);

        return $this->runOneRowArray();
    }

    public function getGameTagListByGameDescriptionId($game_description_id, $status = 1) {
        $this->db->select('gtl.*, gt.tag_code')
        ->from("{$this->tableName} AS gtl")
        ->join('game_tags AS gt', 'gtl.tag_id = gt.id', 'left')
        ->where('game_description_id', $game_description_id);

        if ($status == self::STATUS_DISABLED) {
            $this->db->where('status', self::STATUS_DISABLED);
        } else {
            $this->db->where('status', self::STATUS_NORMAL);
        }

        return $this->runMultipleRowArray();
    }

    public function getGameTagListByGameDescriptionIds($game_description_ids, $status = 1) {
        $this->db->select('gtl.*, gt.tag_code')
        ->from("{$this->tableName} AS gtl")
        ->join('game_tags AS gt', 'gtl.tag_id = gt.id', 'left')
        ->where_in('game_description_id', $game_description_ids);

        return $this->runMultipleRowArray();
    }

    public function updateSelectedGameTagListStatus($game_description_id, $tag_id, $data) {
        $this->db->where('game_description_id', $game_description_id)
        ->where('tag_id', $tag_id)
        ->update($this->tableName, $data);
    }

    public function disableNotInSelectedGameTagListByGameDescriptionId($game_description_id, $game_tags) {
        $data = [
            'status' => self::STATUS_DISABLED,
        ];

        $this->db->where('game_description_id', $game_description_id)
        ->where_not_in('tag_id', $game_tags)
        ->update($this->tableName, $data);
    }

    public function deleteNotSelectedGameTagListByGameDescriptionId($game_description_id, $game_tags) {
        $this->db->where('game_description_id', $game_description_id)
        ->where_not_in('tag_id', $game_tags)
        ->delete($this->tableName);
    }

    public function getGameTagListByTagId($tag_id, $status = 1) {
        $this->db->from($this->tableName)->where('tag_id', $tag_id);

        if ($status == self::STATUS_DISABLED) {
            $this->db->where('status', self::STATUS_DISABLED);
        } else {
            $this->db->where('status', self::STATUS_NORMAL);
        }

        return $this->runMultipleRowArray();
    }

    public function tagNewGame($game_description_id, $expired_at = null) {
        $new_tag_code = $this->utils->getConfig('game_tag_code_for_new_release');
        $now = $this->utils->getNowForMysql();

        $this->db->from('game_tags')->where(['tag_code' => $new_tag_code]);
        $tag_id = $this->runOneRowOneField('id');

        if (!$tag_id) {
            $tag_name_arr = [
                '1' => $new_tag_code,
                '2' => $new_tag_code,
                '3' => $new_tag_code,
                '4' => $new_tag_code,
                '5' => $new_tag_code
            ];
    
            $game_tag_new_data = [
                'tag_code' => $new_tag_code,
                'tag_name' => '_json:'.json_encode($tag_name_arr),
                'created_at' => $now,
                'is_custom' => true
            ];

            $tag_id = $this->runInsertData('game_tags', $game_tag_new_data);
        }

        if ($tag_id) {
            $this->db->where(['id' => $game_description_id])->set(['flag_new_game' => 1]);
            $is_updated = $this->runAnyUpdate('game_description');

            if ($is_updated) {
                $this->utils->debug_log(__METHOD__, 'game tagged as new successfully');

                $data = [
                    'tag_id' => $tag_id,
                    'game_description_id' => $game_description_id,
                    'status' => 1,
                    'game_order' => 0,
                    'expired_at' => $expired_at,
                ];

                $is_inserted = $this->runInsertData('game_tag_list', $data);

                if ($is_inserted) {
                    $this->utils->debug_log(__METHOD__, 'game tag new game save successfully');
                    return true;
                }
            } else {
                $this->utils->debug_log(__METHOD__, 'flag new game update failed');
                return false;
            }
        }
        
        $this->utils->debug_log(__METHOD__, 'game tag not found');
        return false;
    }

    public function checkBeforeInsertGameTagList($game_tag_id, $game_description_ids, $tag_game_order = 0, $tag_status = 1) {
        foreach ($game_description_ids as $game_description_id) {
            try {
                $existingRecord = $this->db->where('game_description_id', $game_description_id)
                                   ->where('tag_id', $game_tag_id)
                                   ->get($this->tableName)
                                   ->row();

                if (!$existingRecord) {
                    $this->db->query("INSERT INTO {$this->tableName} (game_description_id, tag_id, game_order, `status`) VALUES (?, ?, ?, ?)", [
                        $game_description_id,
                        $game_tag_id,
                        $tag_game_order,
                        $tag_status,
                    ]);
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    public function addToGameTagList($tag_id, $game_description_id, $status = 1, $game_order = 0, $expired_at = null, $run_update = false, $db = null) {
        if(!empty($db)){
            $this->db = $db;
        }
        $added = $this->isRecordExist($this->tableName, ['tag_id' => $tag_id, 'game_description_id' => $game_description_id]);

        if (!$added) {
            $data = [
                'tag_id' => $tag_id,
                'game_description_id' => $game_description_id,
                'status' => $status,
                'game_order' => $game_order,
                'expired_at' => $expired_at,
            ];

            $added = $this->runInsertData('game_tag_list', $data);
        } else {
            if($run_update){
                $this->db->where("tag_id", $tag_id);
                $this->db->where("game_description_id", $game_description_id);
                $success =  $this->runUpdate([
                    'status' => $status, 
                    'game_order' => $game_order,
                    'expired_at' => $expired_at,
                ]);
            }
        }

        return $added ? true : false;
    }

    public function addToGameTagListByGameType($game_description_id, $game_type_id){
        $this->db->select("game_tag_id")->from("game_type");
        $this->db->where("id",$game_type_id);
        $tag_id = $this->runOneRowOneField('game_tag_id');
        if($tag_id){
            return $this->addToGameTagList($tag_id, $game_description_id);
        }
        return false;
    }
}

///END OF FILE///////
