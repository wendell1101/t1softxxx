<?php
require_once dirname(__FILE__) . '/base_model.php';

class Pos_player_latest_game_logs extends BaseModel {

    protected $tableName = 'pos_player_latest_game_logs';
    public $CI;

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['game_logs', 'pos_bet_extra_info']);
    }

    /**
     * overview : sync
     *
     * @param DateTime 	$from
     * @param DateTime 	$to
     * @param int		$player_id
     * @return array
     */
    public function sync(\DateTime $from, \DateTime $to, $player_id = null, $game_platform_id = null) {
        $adjust_datetime_minutes = $this->CI->utils->getConfig('sync_pos_player_latest_game_logs_modify_date_from_minutes');
        $from->modify('-' . $adjust_datetime_minutes . ' minutes');
        $date_from = $from->format('Y-m-d H:i:') . '00';
        $date_to = $to->format('Y-m-d H:i:') . '59';
        $bet_number = $order_by = $order_type = null;
        $limit = 500;
        $offset = 0;
        $success = true;
        $data_count = 0;
        $inserted = 0;
        $updated = 0;
        $by_datetime = 'updated_at';

        $this->CI->utils->debug_log(__FUNCTION__, 'date_time_adjustment ===========>', $adjust_datetime_minutes, $date_from);

        $result = [
            'success' => $success,
            'data_count' => $data_count,
            'inserted' => $inserted,
            'updated' => $updated,
        ];

        if (empty($game_platform_id)) {
            $game_platform_id = $this->CI->utils->getConfig('sync_pos_player_latest_game_logs_by_game_platform_ids');
        }

        $game_logs = $this->CI->game_logs->getGameLogsBetDetails($date_from, $date_to, $player_id, $bet_number, $game_platform_id, $order_by, $order_type, $limit, $offset, true, $by_datetime);
        $this->utils->debug_log('game_logs', 'last_query', $this->db->last_query());

        $game_logs_unsettle = $this->CI->game_logs->getGameLogsBetDetails($date_from, $date_to, $player_id, $bet_number, $game_platform_id, $order_by, $order_type, $limit, $offset, false, $by_datetime);
        $this->utils->debug_log('game_logs_unsettle', 'last_query', $this->db->last_query());

        $fetched_data = array_merge($game_logs, $game_logs_unsettle);
        $game_records = $this->rebuildGameRecords($fetched_data);

        if (!empty($game_records)) {
            $data_count = count($game_records);

            foreach ($game_records as $data) {
                $bet_number = isset($data['bet_number']) ? $data['bet_number'] : null;
                // $md5_sum = isset($data['md5_sum']) ? $data['md5_sum'] : null;
                $data['md5_sum'] = $md5_sum = $this->processMd5Sum($data);

                if ($this->isRecordExist($this->tableName, ['bet_number' => $bet_number])) {
                    $md5_sum_in_db = $this->getMd5SumByBetNumber($bet_number);

                    if ($md5_sum != $md5_sum_in_db) {
                        $update_result = $this->save('update', $data, 'bet_number', $bet_number, true);

                        if ($update_result) {
                            $updated++;
                        } else {
                            $this->CI->utils->debug_log(__FUNCTION__, 'update result false', 'update_result', $update_result);
                            break;
                        }
                    }
                } else {
                    $inserted_result = $this->save('insert', $data);

                    if ($inserted_result) {
                        $inserted++;
                    } else {
                        $this->CI->utils->debug_log(__FUNCTION__, 'insert result false', 'inserted_result', $inserted_result);
                        break;
                    }
                }
            }
        }

        $result = [
            'success' => $success,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'data_count' => $data_count,
            'inserted' => $inserted,
            'updated' => $updated,
        ];

        $this->CI->utils->debug_log(__FUNCTION__, 'final_result', 'result', $result);
        return $result;
    }

    private function rebuildGameRecords($game_records = []) {
        $new_records = $record =[];
        $game_api = null;

        if (!empty($game_records)) {
            foreach ($game_records as $game_record) {
                if (isset($game_record['game_platform_id'])) {
                    $game_api = $this->CI->utils->loadExternalSystemLibObject($game_record['game_platform_id']);
                }

                $bet_details = $game_api->preprocessBetDetails($game_record, $game_record['game_type']);
                $record['bet_number'] = isset($game_record['bet_number']) ? $game_record['bet_number'] : null;
                $record['player_id'] = isset($game_record['player_id']) ? $game_record['player_id'] : null;
                $record['game_platform_id'] = isset($game_record['game_platform_id']) ? $game_record['game_platform_id'] : null;
                $record['bet_amount'] = isset($game_record['bet_amount']) ? $game_record['bet_amount'] : null;
                $record['payout_amount'] = isset($game_record['payout_amount']) ? $game_record['payout_amount'] : null;
                $record['bet_details'] = !empty($bet_details) ? json_encode($bet_details) : null;
                $record['bet_at'] = isset($game_record['bet_at']) ? $game_record['bet_at'] : null;
                $record['end_at'] = isset($game_record['end_at']) ? $game_record['end_at'] : null;
                $record['game_username'] = isset($game_record['game_username']) ? $game_record['game_username'] : null;
                $record['game_type'] = isset($game_record['game_type']) ? $game_record['game_type'] : null;
                $record['game_name'] = isset($game_record['game_name']) ? $game_record['game_name'] : null;
                $record['game_code'] = isset($game_record['game_code']) ? $game_record['game_code'] : null;
                $record['round_id'] = isset($game_record['round_id']) ? $game_record['round_id'] : null;
                $record['game_type_id'] = isset($game_record['game_type_id']) ? $game_record['game_type_id'] : null;
                $record['game_description_id'] = isset($game_record['game_description_id']) ? $game_record['game_description_id'] : null;
                $record['md5_sum'] = isset($game_record['md5_sum']) ? $game_record['md5_sum'] : null;

                if ($this->isPlayerPosTagExist($record['player_id'])) {
                    array_push($new_records, $record);
                }
            }
        }

        return $new_records;
    }

    public function isPlayerPosTagExist($player_id) {
        $pos_tag_name = $this->utils->getConfig('pos_tag_name');
        $result = false;

        if (!empty($pos_tag_name)) {
            $this->load->model('player_model');
            $tag_id = $this->player_model->getTagIdByTagName($pos_tag_name);
            $result = $this->player_model->isPlayerTagExist($player_id, $tag_id);
        }

        return $result;
    }

    public function processMd5Sum($data) {
        return md5(json_encode($data));
    }

    public function getMd5SumByBetNumber($bet_number) {
        $this->db->select('md5_sum')->from($this->tableName)->where('bet_number', $bet_number);
        return $this->runOneRowOneField('md5_sum');
    }

    public function save($query_type, $data = [], $field_name = null, $field_value = null, $update_with_result = false) {
        return $this->insertOrUpdateData($this->tableName, $query_type, $data, $field_name, $field_value, $update_with_result);
    }

    public function getPosBetDetails($date_from, $date_to, $player_id = null, $bet_number = null, $game_platform_id = 0, $order_by = 'bet_at', $order_type = 'desc', $limit = 10, $offset = 0) {
        $fields = [
            'bet_number',
            'player_id',
            'game_platform_id',
            'bet_amount',
            'payout_amount',
            'bet_details',
            'bet_at',
            'end_at',
            'game_username',
            'game_type',
            'game_name',
            'game_code',
            'round_id',
        ];

        $selected_fields = implode(',', $fields);

        $this->db->select($selected_fields)->from($this->tableName)->where("bet_at BETWEEN '{$date_from}' AND '{$date_to}'");

        if (!empty($bet_number)) {
            $this->db->where('bet_number', $bet_number);
        }

        if (!empty($player_id)) {
            $this->db->where('player_id', $player_id);
        }

        if (!empty($game_platform_id)) {
            if (is_array($game_platform_id)) {
                $this->db->where_in('game_platform_id', $game_platform_id);
            } else {
                $this->db->where('game_platform_id', $game_platform_id);
            }
        }

        if (!empty($order_by)) {
            $this->db->order_by($order_by, $order_type);
        }

        if (!empty($limit)) {
            $this->db->limit($limit, $offset);
        }

        return $this->runMultipleRowArray();
    }

}

/////end of file///////
?>