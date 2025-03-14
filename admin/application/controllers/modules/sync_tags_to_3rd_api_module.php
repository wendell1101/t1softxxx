<?php

trait sync_tags_to_3rd_api_module
 {

    /**
     * call 3rd api about sync tags
     *
     * @param array $customers
     * @param array $params For collect about the params of the request.
     * @param array $json_result For collect about the response of the request.
     * @return bool
     */
	public function call_sync_tags_to_3rd_api($customers = [], &$params = null, &$json_result = null, &$url = null){
		$sync_tags_to_3rd_api = $this->config->item('sync_tags_to_3rd_api');
        $json_result = [];

        if( empty($sync_tags_to_3rd_api) ){
            $this->utils->debug_log('OGP-28862.19.ignore httpCall() for empty config, sync_tags_to_3rd_api');
            return false;
        }
		// set_time_limit($default_sync_game_logs_max_time_second);

        $config = [];
        $config['is_post'] = true; // use POST method
        $config['post_json'] = true; // POST data via json
        $config['is_result_json'] = true;
        $config['timeout_second'] = 3;
        $config['connect_timeout'] = 3;
        $config['header_array'] = [];

        $url = $sync_tags_to_3rd_api['uri'];
        $params = $sync_tags_to_3rd_api['params'];

        if( ! empty($customers ) ){
            $params['customers'] = [];
            foreach( $customers as $username => $tag_list){
                $params['customers'][$username] = $tag_list;
            }

            list( $header
                , $content
                , $statusCode
                , $statusText
                , $errCode
                , $error
                , $resultObj ) = $this->utils->httpCall($url, $params, $config, $initSSL=null);
            $result = $content;
            $json_result = json_decode($result,true);
            $this->utils->debug_log('OGP-28862.50.called httpCall(), url:', $url, 'params:', $params, 'json_result:', $json_result);
        }else{
            $this->utils->debug_log('OGP-28862.51.cancel httpCall() for empty customers');
        }

        if( ! empty($json_result['code'])
            && $json_result['code'] == '200'
        ){
            return true;
        }else{
            return false;
        }

	} // EOF call_sync_tags_to_3rd_api


    public function call_sync_tags_to_3rd_api_with_player_id_list( $player_id_list = []
                                                                    , $source_token = ''
                                                                    , &$params = null
                                                                    , &$json_result = null
                                                                    , &$uri = null
    ){
        $this->load->model(['player_model']);

        $customers = [];
        if( ! empty($player_id_list) ){
            foreach($player_id_list as $playerId){
                $_tag_id_list = $this->player_model->getPlayerTags($playerId);
                $_row = $this->player_model->getPlayerUsername($playerId);
                $_data = [];

                $_data['username'] = $_row['username'];
                $_data['tag'] = array_column($_tag_id_list, 'tagName');
                array_push($customers, $_data);
            }// EOF foreach($player_id_list as $playerId){...
        }
        return $this->call_sync_tags_to_3rd_api($customers, $params, $json_result, $uri);
    }// EOF call_sync_tags_to_3rd_api_with_player_id_list

    public function call_sync_tags_to_3rd_api_with_csv_file( $csv_file_of_bulk_import_playertag = ''
                                                            , $source_token = ''
                                                            , &$params = null
                                                            , &$json_result = null
                                                            , &$uri = null
    ){
        $this->load->model(['player_model']);

        $import_player_csv_header=[ 'Username'
                                    , 'Action'
                                    , 'Tag'
                                    , 'Status'
                                    , 'Reason'
                                    , 'Before Adjustment'
                                    , 'Changes'
                                    , 'After Adjustment'
                                ];

        $player_id_list = [];
        if( ! empty($csv_file_of_bulk_import_playertag) ){
            $csv_file = $csv_file_of_bulk_import_playertag;
            $ignore_first_row = true;
            $cnt = 0;
            $message = '';
            $controller = $this;

            $this->utils->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
                use($controller, $import_player_csv_header, &$player_id_list) {
                    $row = array_combine($import_player_csv_header, $csv_row);
                    $player_id = 0;
                    $_player = $controller->player_model->getPlayerByUsername( trim($row['Username']) );
                    if( ! empty($_player->playerId)
                        && strpos($row['Status'], 'SUCCESS') !== false
                    ){
                        array_push($player_id_list, $_player->playerId);
                    }
            });
        } // EOF if( ! empty($csv_file_of_bulk_import_playertag) ){...

        return $this->call_sync_tags_to_3rd_api_with_player_id_list($player_id_list, $source_token, $params, $json_result, $uri);
    }// EOF call_sync_tags_to_3rd_api_with_csv_file


    public function call_sync_tags_to_3rd_api_with_player_id_list_from_queue($token = null){
        $data=$this->initJobData($token);
        $token = $data['token'];
        $params = json_decode($data['full_params'], true);
        $this->utils->debug_log('load from queue:', $token, $params, 'JobData:', $data);

        $_params = null;
        $_json_result = null;
        $_uri = null;

        $response = [];
        if( ! empty($params['player_id_list']) ){
            $source_token = '';
            $response['is_call_done'] = $this->call_sync_tags_to_3rd_api_with_player_id_list($params['player_id_list'], $source_token, $_params, $_json_result, $_uri);
        }else if( ! empty($params['source_csv_file']) ){
            $source_token = $token;
            $response['is_call_done'] = $this->call_sync_tags_to_3rd_api_with_csv_file($params['source_csv_file'], $source_token, $_params, $_json_result, $_uri);
        }


        if(! empty($params['source_token']) ){
            $response['source_token'] =  $params['source_token'];
        }
        if(! empty($_params) ){
            $response['params'] =  $_params;
        }
        if(! empty($_json_result) ){
            $response['json_result'] =  $_json_result;
        }
        if(! empty($_uri) ){
            $response['api_uri'] =  $_uri;
        }

        $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $response ], true);
    } // EOF call_sync_tags_to_3rd_api_with_player_id_list_from_queue
}