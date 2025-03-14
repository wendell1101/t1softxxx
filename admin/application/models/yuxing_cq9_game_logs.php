<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class yuxing_cq9_game_logs extends Base_game_logs_model {

	function __construct() {
		parent::__construct();
	}

	protected $tableName = "yuxing_cq9_game_logs";

    public function getAvailableRows($rows) {
        $columns = array(
            'id'
        );
        $filter = $this->utils->array_column_concat_multi($rows,$columns);
        $this->CI->utils->debug_log('filter synclogs', $filter);
        $this->db->select('external_uniqueid')->from($this->tableName)->where_in('external_uniqueid', $filter);
        $existsRow = $this->runMultipleRowArray();
        $this->CI->utils->debug_log('existsRow synclogs', $existsRow);
        $availableRows = null;
        if (!empty($existsRow)) {
            $existsId = array_column($existsRow, 'external_uniqueid');
            $availableRows = array();
            foreach ($rows as $row) {
                $uniqueId = $row['roundid'];
                if (!in_array($uniqueId, $existsId)) {
                    $availableRows[] = $row;
                }
            }
        }else{
            $availableRows = $rows;
        }

        return $availableRows;
    }
	
  public function getGameLogStatistics($dateFrom, $dateTo) {
    $sql = <<<EOD
        SELECT
            yuxing_cq9_game_logs.status,
            yuxing_cq9_game_logs.username,
            yuxing_cq9_game_logs.channel,
            yuxing_cq9_game_logs.agent,
            yuxing_cq9_game_logs.createtime,
            yuxing_cq9_game_logs.groupfor,
            yuxing_cq9_game_logs.gametype,
            yuxing_cq9_game_logs.roomid,
            yuxing_cq9_game_logs.tableid,
            yuxing_cq9_game_logs.roundid,
            yuxing_cq9_game_logs.betamount,
            yuxing_cq9_game_logs.validbetamount,
            yuxing_cq9_game_logs.betpoint,
            yuxing_cq9_game_logs.odds,
            yuxing_cq9_game_logs.money,
            yuxing_cq9_game_logs.servicemoney,
            yuxing_cq9_game_logs.begintime,
            yuxing_cq9_game_logs.endtime,
            yuxing_cq9_game_logs.isbanker,
            yuxing_cq9_game_logs.gameinfo,
            yuxing_cq9_game_logs.gameresult,
            yuxing_cq9_game_logs.jp,
            yuxing_cq9_game_logs.info1,

            yuxing_cq9_game_logs.external_uniqueid,
            yuxing_cq9_game_logs.response_result_id,
            game_provider_auth.player_id
            FROM
            `yuxing_cq9_game_logs`
            JOIN `game_provider_auth`
                ON (
                `yuxing_cq9_game_logs`.`username` = `game_provider_auth`.`login_name`
                )
            WHERE (
                yuxing_cq9_game_logs.createtime >= ?
                AND yuxing_cq9_game_logs.createtime <= ?
            )
EOD;
  
    $query = $this->db->query($sql, array(
        $dateFrom,
        $dateTo,
    ));
    return $this->getMultipleRow($query);
  }

}

///END OF FILE///////