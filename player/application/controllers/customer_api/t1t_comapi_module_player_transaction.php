<?php

trait t1t_comapi_module_player_transaction {

    /**
     * Get players deposit list by date
     * OGP-28526
     *
     * @return JSON Standard JSON return object
     */
    public function getPlayersDepositListByDate() {
        $api_key = $this->input->post('api_key');
        $off_api_key = $this->input->post('off_api_key');

        if (!$this->__checkKey($api_key) && !$off_api_key) {
            return; 
        }

        try {
            $this->load->model(['transactions']);
            $date = $this->input->post('date');
            $order_by = $this->input->post('order_by');
            $order_type = $this->input->post('order_type');
            $limit = intval($this->input->post('limit'));
            $offset = intval($this->input->post('offset'));
            $show_player = intval($this->input->post('show_player'));
            $get_total_deposit = intval($this->input->post('get_total_deposit'));

            if (!$date) {
                $date = date('Y-m-d', strtotime($this->utils->getNowForMysql()));
            }

            if (!$order_type) {
                $order_type = 'desc';
            }

            $cache_key = __CLASS__ . "-" . __TRAIT__ . "-" .__FUNCTION__ . "-{$date}-{$order_by}-{$order_type}-{$limit}-{$offset}-{$show_player}-{$get_total_deposit}";
            $cached_result = $this->utils->getJsonFromCache($cache_key);

            if (!empty($cached_result)) {
                $ret = $cached_result;
                $this->utils->debug_log(__FUNCTION__, '1', 'cache_key', $cache_key, 'ret', $ret);
                return $ret;
            }

            if ($get_total_deposit) {
                switch ($order_by) {
                    case 'username':
                        $order_by = 'to_username';
                        break;
                    case 'number_of_deposit':
                        $order_by = 'number_of_deposit';
                        break;
                    case 'total_deposit':
                        $order_by = 'total_deposit';
                        break;
                    default:
                        $order_by = 'total_deposit';
                    break;
                }

                $players_deposit_list = $this->transactions->getPlayersTotalDepositByDate($date, $order_by, $order_type, $limit, $offset);
            } else {
                $fields = [
                    'to_username',
                    'amount',
                    'created_at',
                ]; 

                $where = [
                    'transaction_type' => transactions::DEPOSIT,
                    'trans_date' => $date,
                ];

                switch ($order_by) {
                    case 'username':
                        $order_by = 'to_username';
                        break;
                    case 'deposit_amount':
                        $order_by = 'amount';
                        break;
                    case 'created_at':
                        $order_by = 'created_at';
                        break;
                    default:
                        $order_by = 'amount';
                    break;
                }

                $players_deposit_list = $this->transactions->getTransactionsCustom($fields, $where, $order_by, $order_type, $limit, $offset);
            }

            $msg = 'No Data!';
            $list = [];

            if (!empty($players_deposit_list)) {
                foreach ($players_deposit_list as $key => $player) {
                    if (isset($player['to_username'])) {
                        $list['username'] = $player['to_username'];
                    }

                    if (isset($player['number_of_deposit'])) {
                        $list['number_of_deposit'] = $player['number_of_deposit'];
                    }

                    if (isset($player['total_deposit'])) {
                        $list['total_deposit'] = $player['total_deposit'];
                    }

                    if (isset($player['amount'])) {
                        $list['deposit_amount'] = $player['amount'];
                    }

                    if (isset($player['created_at'])) {
                        $list['created_at'] = $player['created_at'];
                    }

                    if (!$show_player) {
                        $str = $list['username'];
                        $username_partially_hidden = substr_replace($str, '***', 1, -2);
                        $list['username'] = $username_partially_hidden;
                    }

                    $players_deposit_list[$key] = $list;
                }

                $msg = 'Get data successfully!';
            } else {
                $players_deposit_list = null;
            }

            $ret = [
                'success' => true,
                'code' => 0,
                'mesg' => $msg,
                'result' => $players_deposit_list,
            ];

            $this->utils->debug_log(__FUNCTION__, '2', 'cache_key', $cache_key, 'ret', $ret);

            if (!empty($players_deposit_list)) {
                $ttl = $this->utils->getConfig('get_players_deposit_list_by_date_cache_ttl');
                $this->utils->saveJsonToCache($cache_key, $ret, $ttl);
            }
        } catch (Exception $ex) {
            $ex_log = [
                'code' => $ex->getCode(),
                'message' => isset($res['mesg_debug']) ? $res['mesg_debug'] : $ex->getMessage(),
            ];

            $this->comapi_log(__FUNCTION__, 'Exception', $ex_log);

            $ret = [
                'success' => false,
                'code' => $ex->getCode(),
                'mesg' => $ex->getMessage(),
                'result' => null,
            ];
        } finally {
            $this->comapi_log(__FUNCTION__, 'Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    }
} // End of trait t1t_comapi_module_player_transaction