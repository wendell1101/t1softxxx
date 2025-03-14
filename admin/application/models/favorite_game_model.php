<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 * Class Favorite_game_model
 *
 */
class Favorite_game_model extends BaseModel {

	protected $table = 'favorite_game';

	public function added_to_favorites($player_id, $url) {
		$this->db->where('player_id', $player_id);
		$this->db->where('url', $url);
		return $this->db->count_all_results('favorite_game') > 0;
	}

	public function get_favorites($player_id) {
		$this->db->select('name');
		$this->db->select('url');
		$this->db->select('image as imageUrl');
		$this->db->where('player_id', $player_id);
		$query = $this->db->get('favorite_game');
		return $query->result_array();
	}

	public function get_player_favorites($player_id) {
		$this->db->select('*');
		$this->db->select('url');
		$this->db->select('image as imageUrl');
		$this->db->where('player_id', $player_id);
		$this->db->where('game_description_id <> 0 AND game_description_id is not null');
		$query = $this->db->get('favorite_game');
		return $query->result_array();
	}

	public function add_to_favorites($data) {
		$success = $this->db->insert('favorite_game', $data);
		return $success;
	}

	public function remove_from_favorites($player_id ,$url) {
		$this->db->where('player_id', $player_id);
		$this->db->where('url', $url);
		$success = $this->db->delete('favorite_game');
		return $success;
	}

	/**
	 * Checks record existence by game_platform_id + external_game_id, OGP-23167
	 * Note: column external_game_id was added in migration 2584, 2021/08
	 * @param	int		$player_id			== favorite_game.player_id
	 * @param	int		$game_platform_id	== favorite_game.game_platform_id
	 * @param	string	$external_game_id	== favorite_game.external_game_id
	 * @return	true if exists, otherwise false
	 */
	public function exists_by_platform_ext_game_id($player_id, $game_platform_id, $external_game_id) {
		$this->db->from($this->table)
			->where('game_platform_id', $game_platform_id)
			->where('external_game_id', $external_game_id)
			->where('player_id', $player_id)
		;

		$count = $this->db->count_all_results();

		$exists = $count > 0;

		return $exists;
	}

	/**
	 * Removes record by game_platform_id + external_game_id, OGP-23167
	 * @param	int		$player_id			== favorite_game.player_id
	 * @param	int		$game_platform_id	== favorite_game.game_platform_id
	 * @param	string	$external_game_id	== favorite_game.external_game_id
	 * @return	int		number of affected rows
	 */
	public function remove_by_platform_ext_game_id($player_id, $game_platform_id, $external_game_id) {
		$this->db->from($this->table)
			->where('game_platform_id', $game_platform_id)
			->where('external_game_id', $external_game_id)
			->where('player_id', $player_id)
		;

		$del_res = $this->db->delete($this->table);

		$row_res = $this->db->affected_rows();

		return $row_res;
	}

	/**
	 * Removes record by player_id + id (primary key), OGP-23167
	 * @param	int		$player_id	== favorite_game.player_id
	 * @param	int		$id			== favorite_game.id
	 * @return	int		number of affected rows
	 */
	public function remove_by_player_id_prim_key($player_id, $id) {
		$this->db->from($this->table)
			->where('id', $id)
			->where('player_id', $player_id)
		;

		$del_res = $this->db->delete($this->table);

		$row_res = $this->db->affected_rows();

		return $row_res;
	}

	public function get_favorite_list($player_id, $limit) {
		// query
		$sql=<<<EOD
select gd.game_platform_id as gamePlatformId,
external_system.maintenance_mode as underMaintenance,
gd.external_game_id as gameUniqueId,
gd.game_name as gameName,
'' as tags,
0 as onlineCount,
'' as bonusTag,
0 as pcEnable,
0 as mobileEnable,
0 as demoEnable,
'' as gameImgUrl,
'' as playerImgUrl,
gd.flash_enabled, gd.mobile_enabled, gd.html_five_enabled, gd.demo_link, gd.game_code,
gd.screen_mode,
gd.attributes,
gd.rtp

from favorite_game
join game_description as gd on gd.id=favorite_game.game_description_id
left join external_system on external_system.id=gd.game_platform_id
where favorite_game.player_id=?
and gd.status=?
limit ?
EOD;

		$rows=$this->runRawArraySelectSQL($sql, [$player_id, self::DB_TRUE, $limit]);
		return $rows;
	}

}

///END OF FILE