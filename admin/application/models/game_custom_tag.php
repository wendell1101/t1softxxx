<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

/**
 * General behaviors include :
 *
 * * Get game custom tag data by id, tag code
 *
 * @category Game Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_custom_tag extends BaseModel {

	public function __construct() {
		parent::__construct();
	}

	protected $tableName = "game_custom_tag";

    public function getGameTagByTagCode($code) {
		$qry = $this->db->get_where($this->tableName, array('tag_code' => $code));
		return $this->getOneRowArray($qry);
	}

    public function getGameIdByTags($tag){
		$this->db->select('game_custom_tag.game_custom_tag.id tag_id, game_tag_list.game_description_id, game_tag_list.game_order');
		$this->db->where('game_custom_tag.tag_code', $tag);
		$this->db->where('game_custom_tag.status', self::STATUS_NORMAL);
		$this->db->join('game_tag_list', 'game_tag_list.tag_id = game_custom_tag.tag_id');
		$query = $this->db->get('game_custom_tag');
		$result= $query->result_array();
        $ids = [];

        foreach($result as $row){
            $ids[] = $row['game_description_id'];
        }

        return $ids;
    }

}

///END OF FILE///////
