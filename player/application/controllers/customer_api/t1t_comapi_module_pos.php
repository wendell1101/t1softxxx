<?php

trait t1t_comapi_module_pos {

    public function getPosBetDetails() {
        $api_key = $this->input->post('api_key');

	    if (!$this->__checkKey($api_key)) { return; }

        $ret = $res = [];
        $msg = 'No Data!';
        $game_records = null;

        try {
            $this->load->model(['pos_player_latest_game_logs', 'pos_bet_extra_info']);

            $date_from = $this->input->post('date_from');
            $date_end = $this->input->post('date_end');
            $token = trim($this->input->post('token', 1));
            $username = trim($this->input->post('username', 1));
            $bet_number = $this->input->post('bet_number'); // optional
            $game_platform_id = intval($this->input->post('game_platform_id')); // optional

            $order_by = $this->input->post('order_by'); // optional
            $order_type = $this->input->post('order_type'); // optional
            $limit = intval($this->input->post('limit')); // optional
            $offset = intval($this->input->post('offset')); // optional

            $default_params = $this->utils->getConfig('get_pos_bet_details_default_params');

            if (empty($order_by) && isset($default_params['order_by'])) {
                $order_by = $default_params['order_by'];
            }

            if (empty($order_type) && isset($default_params['order_type'])) {
                $order_type = $default_params['order_type'];
            }

            if (empty($limit) && isset($default_params['limit'])) {
                $limit = $default_params['limit'];
            }

            if (empty($offset) && isset($default_params['offset'])) {
                $offset = $default_params['offset'];
            }

            $ret = [
                'success' => true,
                'code' => 0,
                'mesg' => $msg,
                'result' => $game_records,
            ];

            if (empty($date_from)) {
                throw new Exception('Date from is required', self::CODE_DATE_FROM_REQUIRED);
            }

            if (empty($date_end)) {
                throw new Exception('Date end is required', self::CODE_DATE_TO_REQUIRED);
            }

            $playerId = $this->player_model->getPlayerIdByUsername($username);

            if (empty($playerId)) {
                throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($playerId, $token)) {
                throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
            }

            $game_records = $this->pos_player_latest_game_logs->getPosBetDetails($date_from, $date_end, $playerId, $bet_number, $game_platform_id, $order_by, $order_type, $limit, $offset);

            $this->utils->debug_log(__FUNCTION__, 'result game_records', $game_records);

            $length = 0;

            if (!empty($game_records)) {
                $game_records = $this->rebuildGameRecords($game_records);
                $count_records = count($game_records);

                usort($game_records, function ($a, $b) {
                    return strnatcmp($a['bet_at'], $b['bet_at']);
                });
    
                if ($count_records > $limit) {
                    $length = $count_records - $limit;
                    array_splice($game_records, 0, $length);
                }

                if (empty($bet_number)) {
                    $new_game_records = [];

                    foreach ($game_records as $key => $data) {
                        if ($this->pos_bet_extra_info->isPosRecordExist(['bet_number' => $data['bet_number']])) {
                            unset($game_records[$key]);
                        } else {
                            array_push($new_game_records, $game_records[$key]);
                        }
                    }

                    $game_records = $new_game_records;
                }

                $new_count_records = count($game_records);
            } else {
                $count_records = $new_count_records = 0;
                $game_records = null;
            }

            if (!empty($game_records)) {
                $msg = 'Get data successfully!';
            }

            $ret = [
                'success' => true,
                'code' => 0,
                'mesg' => $msg,
                'result' => $game_records,
            ];

            $extra_info = [
                'order_by' => $order_by,
                'order_type' => $order_type,
                'limit' => $limit,
                'offset' => $offset,
                'count_records' => $count_records,
                'substract length' => $length,
                'new_count_records' => $new_count_records,
            ];

            $this->utils->debug_log(__FUNCTION__ . '1', 'extra_info', $extra_info, 'costMs', $this->utils->getCostMs(), 'ret', $ret);

            if (!empty($game_records)) {
                if (empty($bet_number)) {
                    foreach ($game_records as $data) {
                        if (!$this->pos_bet_extra_info->isPosRecordExist(['bet_number' => $data['bet_number']])) {
                            $this->pos_bet_extra_info->save('insert', ['bet_number' => $data['bet_number']]);
                            
                            $exta_data = [
                                'extra_info' => json_encode([
                                    'is_saved' => true,
                                ]),
                            ];

                            $this->pos_player_latest_game_logs->save('update', $exta_data, 'bet_number', $data['bet_number']);
                        }
                    }
                }
            }
        } catch (Exception $ex) {
            $ex_log = [
                'code' => $ex->getCode(),
                'message' => isset($res['mesg_debug']) ? $res['mesg_debug'] : $ex->getMessage(),
            ];

            $this->comapi_log(__FUNCTION__, 'Catch Exception', $ex_log);

            $ret = [
                'success' => false,
                'code' => $ex->getCode(),
                'mesg' => $ex->getMessage(),
                'result' => null,
            ];
        } finally {
            $this->comapi_log(__FUNCTION__, 'Finally Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    }

    private function rebuildGameRecords($game_records = []) {
        $new_records = [];

        if (!empty($game_records)) {
            foreach ($game_records as $game_record) {
                $record['game_platform_id'] = isset($game_record['game_platform_id']) ? $game_record['game_platform_id'] : null;
                $record['bet_number'] = isset($game_record['bet_number']) ? $game_record['bet_number'] : null;
                $record['game_name'] = isset($game_record['game_name']) ? $game_record['game_name'] : null;
                $record['bet_amount'] = isset($game_record['bet_amount']) ? $this->utils->formatCurrencyStyle($game_record['bet_amount']) : null;
                $record['bet_details'] = isset($game_record['bet_details']) ? json_decode($game_record['bet_details'], true) : null;
                $record['payout_amount'] = isset($game_record['payout_amount']) ? $game_record['payout_amount'] : null;
                $record['bet_at'] = isset($game_record['bet_at']) ? date('H:i:s Y-m-d', strtotime($game_record['bet_at'])) : null;

                array_push($new_records, $record);
            }
        }

        return $new_records;
    }

    public function savePosRealPlayerInfo() {
        $api_key = $this->input->post('api_key');

	    if (!$this->__checkKey($api_key)) { return; }

        $ret = $res = $data = [];
        $msg = 'Failed save player info!';

        try {
            $this->load->model(['pos_bet_extra_info']);

            $token = trim($this->input->post('token', 1));
            $username = trim($this->input->post('username', 1));
            $pos_bet_number = $this->input->post('pos_bet_number');
            $pos_real_player_name = $this->input->post('pos_real_player_name');
            $pos_real_player_phone_number = $this->input->post('pos_real_player_phone_number');
            $pos_real_player_id_number = $this->input->post('pos_real_player_id_number');

            $ret = [
                'success' => true,
                'code' => 0,
                'mesg' => $msg,
                'result' => $data,
            ];

            $data = [
                'bet_number' => $pos_bet_number,
                'player_name' => $pos_real_player_name,
                'phone_number' => $pos_real_player_phone_number,
                'id_number' => $pos_real_player_id_number,
            ];

            $playerId = $this->player_model->getPlayerIdByUsername($username);

            if (empty($playerId)) {
                throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($playerId, $token)) {
                throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
            }

            $result = $this->pos_bet_extra_info->save('update', $data, 'bet_number', $pos_bet_number, true);

            $this->utils->debug_log(__FUNCTION__. '1', 'result', $result, 'data', $data);

            if ($result) {
                $msg = 'Data saved successfully!';
                $this->utils->debug_log(__FUNCTION__. '2', 'save successfully', 'result', $result, 'data', $data);
            } else {
                if ($this->pos_bet_extra_info->isPosRecordExist(['bet_number' => $pos_bet_number])) {
                    $msg = 'Data saved successfully!';
                    $this->utils->debug_log(__FUNCTION__. '3', 'already saved', 'result', $result, 'data', $data);
                } else {
                    $this->utils->debug_log(__FUNCTION__. '4', 'bet number not exists', 'result', $result, 'data', $data);
                    throw new Exception($msg, self::CODE_SAVE_INFO_FAILED);
                }
            }

            $ret = [
                'success' => true,
                'code' => 0,
                'mesg' => $msg,
                'result' => $data,
            ];
        } catch (Exception $ex) {
            $ex_log = [
                'code' => $ex->getCode(),
                'message' => isset($res['mesg_debug']) ? $res['mesg_debug'] : $ex->getMessage(),
            ];

            $this->comapi_log(__FUNCTION__, 'Catch Exception', $ex_log);

            $ret = [
                'success' => false,
                'code' => $ex->getCode(),
                'mesg' => $ex->getMessage(),
                'result' => null,
            ];
        } finally {
            $this->comapi_log(__FUNCTION__, 'Finally Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    }
}

?>