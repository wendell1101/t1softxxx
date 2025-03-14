<?php
require_once dirname(__FILE__) . '/base_model.php';

class Player_high_rollers_stream extends BaseModel {

	protected $tableName = 'player_high_rollers_stream';

	function __construct() {
		parent::__construct();
	}

    /**
     * overview : sync
     *
     * @param DateTime  $from
     * @param DateTime  $to
     * @return array
     */
    public function sync(\DateTime $from, \DateTime $to) {
        $fromStr = $from->format('Y-m-d H:i:') . '00';
        $toStr = $to->format('Y-m-d H:i:') . '59';

        $params=[$fromStr, $toStr];
        $t=time();
        $sql = <<<EOD
SELECT
    gl.player_id,
    gl.start_at,
    gl.end_at,
    gl.bet_amount,
    gl.result_amount,
    gl.game_platform_id,
    gl.table as round,
    gl.game_type_id,
    p.username as player_username,
    gd.english_name as game,
    md5(CONCAT( gl.external_uniqueid, gl.player_id, gl.game_platform_id )) external_uniqueid,
    gl.game_description_id,
    gt.game_type_lang as game_type,
    gd.game_code
FROM
    game_logs AS gl
LEFT JOIN player as p ON gl.player_id = p.playerId
LEFT JOIN game_description as gd ON gl.game_description_id = gd.id AND gd.game_platform_id = gl.game_platform_id
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE
    flag = 1
    AND end_at >= ?
    AND end_at <= ?
ORDER BY
    bet_at ASC
EOD;
        $rows=$this->runRawSelectSQLArray($sql, $params);
        if(!empty($rows)){
            $round_arrays = [];
            foreach($rows as $key => $row){
               $round_arrays[$row['player_id'] . '-' . $row['round']][] = $row;
            }

            if(!empty($round_arrays)){
                unset($rows);
                $rows = [];
                foreach ($round_arrays as $key => $round_value) {
                    $count = count($round_value);
                    if($count > 1){
                        $total_bet = 0;
                        $total_result_amount = 0;
                        foreach ($round_value as $iround_value) {
                            $total_bet += $iround_value['bet_amount'];
                            $total_result_amount += $iround_value['result_amount'];
                        }
                        if($total_result_amount > 0){
                            $end_array = end($round_value);
                            $end_array['bet_amount'] = $total_bet;
                            $end_array['result_amount'] = $total_result_amount;
                            $rows[] = $end_array;
                        }
                    } else {
                        $data = $round_value[0];
                        if($data['result_amount'] > 0){
                            $rows[] = $data;
                        }
                    }
                }
            }
        }


        $this->utils->info_log('get rows from game_logs', count($rows), $params, 'cost', (time()-$t));
        list($insertRows, $updatedRows) = $this->generateInsertForPlayerHighRollersStream($rows);
        unset($rows);
        $t=time();
        $cnt=0;
        $limit=500;
        $success = true;
        if(!empty($insertRows)){
            $success=$this->runBatchInsertWithLimit($this->db, $this->tableName, $insertRows, $limit, $cnt);
        }

        $this->utils->info_log('insert into player_high_rollers_stream', $cnt, $params, 'cost', (time()-$t));
        $this->deleteAndLimitTable();
        return $success;
    }

    private function generateInsertForPlayerHighRollersStream($originalRows){
        $uniqueidValues=array_column($originalRows, 'external_uniqueid');
        $afterUniqueArr=array_unique($uniqueidValues);
        $limit=500;
        $arr = array_chunk($uniqueidValues, $limit);
        $existsRows = [];
        $insertRows = [];
        $updateRows = [];
        foreach ($arr as $data) {
            $data= $this->utils->convertArrayItemsToString($data);
            $this->db->select('external_uniqueid')
                ->from('player_high_rollers_stream')->where_in('external_uniqueid', $data);
            $tmpRows = $this->runMultipleRowArray();
            $tmpRows=array_column($tmpRows, 'external_uniqueid');
            if(!empty($tmpRows)){
                $existsRows=array_merge($existsRows, $tmpRows);
            }
            unset($tmpRows);
        }

        if(!empty($originalRows)){
            foreach ($originalRows as $key => $originalRow) {
                if(!in_array($originalRow['external_uniqueid'], $existsRows)){
                    $insertRows[] = $originalRow;
                } else {
                    // $updateRows[] = $originalRow;
                }
            }
        }
        return [$insertRows, $updateRows];
    }

    private function deleteAndLimitTable(){
        $defaul_limit = 1000;
        $limit_config = $this->utils->getConfig('limit_player_high_rollers_stream');
        $limit = !empty($limit_config) ? $limit_config : $defaul_limit;
        // $query = $this->db->get('player_high_rollers_stream');
        // $total_count = $query->num_rows();
        $total_count = $this->db->count_all('player_high_rollers_stream');
        if($total_count > $limit){
            $limit_delete = $total_count - $limit;

            $this->db->order_by('id');
            $this->db->limit($limit_delete);
            $this->db->where("player_id IS NOT NULL");
            $this->db->delete($this->tableName);
            $this->utils->info_log('running deleteAndLimitTable', $limit_delete, 'qry', $this->db->last_query());
        }
    }

    public function get_player_latest_game_logs_high_rollers($limit = 10){
        $this->db->from("player_high_rollers_stream gl");
        $this->db->join('player as p', 'gl.player_id = p.playerId', 'left');
        $this->db->join('game_description as gd', 'gl.game_description_id = gd.id', 'left');
        // $this->db->join('game_type as gt', 'gl.game_type_id = gt.id', 'left');
        $this->db->select([
            'p.username as playerUsername',
            'CONCAT(gl.game_platform_id,"-",gl.external_uniqueid) as uniqueId',
            'CONCAT(gl.game_platform_id,"-", gd.external_game_id) as virtualGameId',
            'gd.english_name as gameName',
            'gl.start_at as betTime',
            'gl.end_at as payoutTime',
            'gl.bet_amount as realBetAmount',
            'gl.result_amount + gl.bet_amount as payoutAmount',
            'gl.result_amount as resultAmount',
            '((gl.result_amount + gl.bet_amount)/gl.bet_amount) as multiplier'
        ]);
        $this->db->limit($limit);
        $this->db->order_by('gl.end_at DESC');
        $res = $this->runMultipleRowArray();
        $this->utils->debug_log(__METHOD__, 'glbv2.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'glbv2.res', $res);

        return $res;
    }

    public function get_latest_high_rollers($limit = 20){
        $is_enabled_fake_data = $this->utils->getConfig('enable_latest_high_rollers_fake_data');
        $fake_data_config = $this->utils->getConfig('latest_high_rollers_fake_data');
        $number_of_fake_data = isset($fake_data_config['number_of_fake_data']) ? $fake_data_config['number_of_fake_data'] : 10;
        $fake_username_prefix = isset($fake_data_config['fake_username_prefix']) ? $fake_data_config['fake_username_prefix'] : '';
        $random_game_tag_code = isset($fake_data_config['random_game_tag_code']) ? $fake_data_config['random_game_tag_code'] : 'top';
        $min_result_amount = isset($fake_data_config['range']['min_result_amount']) ? $fake_data_config['range']['min_result_amount'] : 0;

        $this->db->from("player_high_rollers_stream gl");
        $this->db->join('player as p', 'gl.player_id = p.playerId', 'left');
        $this->db->join('game_description as gd', 'gl.game_description_id = gd.id', 'left');
        $this->db->select([
            'gl.player_username as playerUsername',
            'CONCAT(gl.game_platform_id,"-",gl.external_uniqueid) as uniqueId',
            'CONCAT(gl.game_platform_id,"-", gd.external_game_id) as virtualGameId',
            'gd.english_name as gameName',
            'gl.start_at as betTime',
            'gl.end_at as payoutTime',
            'gl.bet_amount as realBetAmount',
            'gl.result_amount + gl.bet_amount as payoutAmount',
            'gl.result_amount as resultAmount',
            '((gl.result_amount + gl.bet_amount)/gl.bet_amount) as multiplier'
        ]);

        #OGP-33232
        #$config['enable_get_latest_high_rollers_weekly']
        $is_weekly  = $this->utils->getConfig('enable_get_latest_high_rollers_weekly');
        $interval   = "gl.end_at > now() - interval 24 hour";
        if($is_weekly){
            $interval = "gl.end_at > now() - INTERVAL 7 DAY";
        }
        $this->db->where($interval);

        if ($is_enabled_fake_data && !empty($min_result_amount)) {
            $this->db->where("gl.result_amount >= {$min_result_amount}");
        }

        $this->db->limit($limit);
        $this->db->order_by('gl.result_amount DESC');
        $res = $this->runMultipleRowArray();
        $this->utils->debug_log(__METHOD__, 'glbv2.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'glbv2.res', $res);

        if ($is_enabled_fake_data) {
            if (!empty($res)) {
                $number_of_fake_data -= count($res);
            }

            if ($number_of_fake_data > 0) {
                $res = array_merge($res, $this->generateFakeData($number_of_fake_data, $fake_username_prefix, $random_game_tag_code));
                // override order by
                $this->utils->usortDescByArraykeyValues($res, 'resultAmount');
            }
        }

        return $res;
    }

    public function randomGame($tag_code = null) {
        $this->db->select('gd.game_platform_id, gd.external_game_id, gd.english_name');
        $this->db->from('game_description as gd');
        $this->db->join('game_tag_list as gtl', 'gd.id = gtl.game_description_id', 'left');
        $this->db->join('game_tags as gt', 'gtl.tag_id = gt.id', 'left');
        $this->db->where("gd.external_game_id != 'unknown'");

        if (!empty($tag_code)) {
            $this->db->where(['gt.tag_code' => $tag_code]);
        }

        $this->db->order_by('rand()');

        return $this->runOneRowArray();
    }

    public function generateFakeData($number_of_fake_data = 10, $fake_username_prefix = '', $game_tag_code = 'top', $multiplier = 2, $show_player = true) {
        $fake_data_config = $this->utils->getConfig('latest_high_rollers_fake_data');
        $this->utils->debug_log(__METHOD__, 'config', 'latest_high_rollers_fake_data', $fake_data_config);

        $fake_usernames = $fake_data_config['fake_usernames'];
        $currency_info = $this->utils->getCurrentCurrency();
        $fake_data = [];

        for ($i = 0; $i < $number_of_fake_data; $i++) {
            $random_fake_name = $this->utils->randomUsername($fake_username_prefix, $show_player, false, $fake_usernames);
            $random_game = $this->randomGame($game_tag_code);

            if (empty($random_game)) {
                // will get random game without game tag code
                $this->utils->debug_log(__METHOD__, "empty {$game_tag_code} random game, will get random game without game tag code");
                $random_game = $this->randomGame(null);
            }

            $bet_amount = rand($fake_data_config['range']['min_result_amount'], $fake_data_config['range']['max_result_amount']);
            $payout_amount = $bet_amount * $multiplier;
            $result_amount = $payout_amount - $bet_amount;

            $interval = rand(1, 60);
            $payout_interval = $interval + 1;

            $fake_data[$i] = [
                'playerUsername' => $random_fake_name,
                'uniqueId' => $this->getSecureId($this->tableName, 'external_uniqueid', true, 'fake-'),
                'virtualGameId' => $random_game['game_platform_id'] . '-' . $random_game['external_game_id'],
                'gameName' => $random_game['english_name'],
                'betTime' => $this->utils->modifyDateTime($this->utils->getNowForMysql(), "+{$interval} SECOND"),
                'payoutTime' => $this->utils->modifyDateTime($this->utils->getNowForMysql(), "+{$payout_interval} SECOND"),
                'realBetAmount' => $bet_amount,
                'payoutAmount' => $payout_amount,
                'resultAmount' => $result_amount,
                'multiplier' => $multiplier,
            ];

            if (!empty($currency_info['currency_code'])) {
                $fake_data[$i]['currency'] = $currency_info['currency_code'];
            }
        }

        return $fake_data;
    }

    public function get_high_rollers($params = [
        'limit' => 10,
    ]) {
        $columns = [
            'game_logs.game_platform_id',
            'game_logs.start_at',
            'game_logs.end_at',
            'game_logs.bet_amount',
            'game_logs.result_amount + game_logs.bet_amount AS win_amount',
            'game_logs.result_amount',
            '((game_logs.result_amount + game_logs.bet_amount) / game_logs.bet_amount) AS multiplier',
            'game_logs.external_uniqueid as external_unique_id',
            'player.username AS player_username',
            'game_description.game_name',
            'game_description.external_game_id',
            'game_type.game_type_code AS game_type',
            'external_system.system_code AS game_platform_name',
        ];

        $columns = implode(",", $columns);

        $this->db->select($columns);
        $this->db->from("player_high_rollers_stream AS game_logs");
        $this->db->join('player', 'game_logs.player_id = player.playerId', 'LEFT');
        $this->db->join('external_system', 'game_logs.game_platform_id = external_system.id', 'LEFT');
        $this->db->join('game_description', 'game_logs.game_description_id = game_description.id', 'LEFT');
        $this->db->join('game_type', 'game_logs.game_type_id = game_type.id', 'LEFT');
        $this->db->order_by('game_logs.end_at DESC');

        if (!empty($params)) {
            if (!empty($params['game_platform_id'])) {
                $this->db->where(['game_logs.game_platform_id' => $params['game_platform_id']]);
            }

            if (!empty($params['game_code']) || !empty($params['external_game_id'])) {
                if (!empty($params['game_code'])) {
                    $game_code = $params['game_code'];
                }

                if (!empty($params['external_game_id'])) {
                    $game_code = $params['external_game_id'];
                }

                $this->db->where(['game_description.external_game_id' => $game_code]);
            }

            if (!empty($params['game_type'])) {
                $this->db->where(['game_type.game_type_code' => $params['game_type']]);
            }

            if (!empty($params['date_from']) && !empty($params['date_to'])) {
                $this->db->where("game_logs.start_at BETWEEN '{$params['date_from']}' AND '{$params['date_to']}'");
            }

            if (!empty($params['limit'])) {
                $this->db->limit($params['limit']);
            } else {
                $this->db->limit(10);
            }
        }

        $result = $this->runMultipleRowArray();

        $this->utils->debug_log(__METHOD__, 'glbv2.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'glbv2.res', $result);

        return $result;
    }

    public function get_high_payout_games($tag = null, $limit = 10){
        if(empty($limit)){
            $limit = 10;
        }

        $this->db->from("player_high_rollers_stream gl");
        $this->db->join('player as p', 'gl.player_id = p.playerId', 'left');
        $this->db->join('game_description as gd', 'gl.game_description_id = gd.id', 'left');
        if(!empty($tag)){
            $this->db->join('game_tag_list as gtl', 'gd.id = gtl.game_description_id', 'left');
            $this->db->join('game_tags as gt', 'gtl.tag_id = gt.id', 'left');
            $this->db->where(['gt.tag_code' => $tag]);
        }
        
        $this->db->select([
            'p.username as playerUsername',
            'CONCAT(gl.game_platform_id,"-",gl.external_uniqueid) as uniqueId',
            'CONCAT(gl.game_platform_id,"-", gd.external_game_id) as virtualGameId',
            'gd.english_name as gameName',
            'MAX(gl.result_amount) as resultAmount'
        ]);
        $this->db->group_by('gl.game_description_id');
        $this->db->limit($limit);
        $this->db->order_by('resultAmount DESC');
        $res = $this->runMultipleRowArray();
        
        $this->utils->debug_log(__METHOD__, 'glbv2.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'glbv2.res', $res);
        return $res;
    }
}

/////end of file///////