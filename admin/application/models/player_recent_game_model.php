<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * player recent game
 * id, player_id, game_description_id, created_at
 *
 * @category player
 * @version 1.0.0
 * @copyright tot
 */
class Player_recent_game_model extends BaseModel {

	protected $tableName = 'player_recent_games';
	protected $idField = 'id';

	public function __construct() {
		parent::__construct();
		$this->load->model(['player_model']);
	}

	public function queryRecentGame($playerId, $limit){
		$tableName=$this->tableName;
		// flag_show_in_site=1, because only allow available game
		$sql=<<<EOD
select gd.game_platform_id as gamePlatformId,
external_system.maintenance_mode as underMaintenance,
gd.external_game_id as gameUniqueId,
gd.game_name as gameName,
JSON_ARRAYAGG(game_tags.tag_code) as tags,
0 as onlineCount,
'' as bonusTag,
0 as pcEnable,
0 as mobileEnable,
0 as demoEnable,
'' as gameImgUrl,
gd.flash_enabled, gd.mobile_enabled, gd.html_five_enabled, gd.demo_link, gd.game_code,
gd.screen_mode,
gd.attributes,
gd.rtp

from {$tableName} as prg
join game_description as gd on gd.id=prg.game_description_id
left join game_tag_list on game_tag_list.game_description_id=gd.id
left join game_tags on game_tags.id=game_tag_list.tag_id
left join external_system on external_system.id=gd.game_platform_id
where prg.player_id=?
and gd.flag_show_in_site=?
group by gd.id
order by prg.created_at desc
limit ?
EOD;

		$rows=$this->runRawArraySelectSQL($sql, [intval($playerId), self::DB_TRUE, $limit]);
		return $rows;
	}

	public function searchRecentGame($playerId, $gameDescriptionId){
		$this->db->select('id')->from($this->tableName)
			->where('player_id', $playerId)
			->where('game_description_id', $gameDescriptionId);
		return $this->runOneRowOneField('id');
	}

	public function addRecentGame($playerId, $gameDescriptionId){
		$data=[
			'player_id'=>$playerId,
			'game_description_id'=>$gameDescriptionId,
			'created_at'=>$this->utils->getNowForMysql(),
		];
		$id=$this->searchRecentGame($playerId, $gameDescriptionId);
		if(empty($id)){
			// insert
			return $this->runInsertData($this->tableName, $data);
		}else{
			// update
			$this->db->set($data)->where('id', $id);
			return $this->runAnyUpdate($this->tableName);
		}
	}

	public function countRecentGame($playerId){
		$this->db->select('count(*) as cnt', false)
			->from($this->tableName)
			->where('player_id', $playerId);
		return $this->runOneRowOneField('cnt');
	}

	public function clearRecentGame($playerId){
		// keep last
		$keep_last_recent_games=$this->utils->getConfig('keep_last_recent_games', 20);
		if($keep_last_recent_games>0){
			$this->db->select('id')->from($this->tableName)
				->where('player_id', $playerId)
				->order_by('created_at asc');
			$rows=$this->runMultipleRowArray();
			if(!empty($rows)){
				// get values
				$idArr=array_column($rows, 'id');
				$size=count($idArr);
				$this->utils->debug_log('get '.$size.' recent games, keep '.$keep_last_recent_games);
				if($size>$keep_last_recent_games){
					// cut other
					$delArr=array_slice($idArr, 0, $size-$keep_last_recent_games);
					$this->utils->debug_log('will delete '.count($delArr));
					// clear expired
					$this->db->where_in('id', $delArr);
					return $this->runRealDelete($this->tableName);
				}
			}
		}

		return true;
	}

}
