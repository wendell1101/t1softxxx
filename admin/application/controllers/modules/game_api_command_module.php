<?php

trait game_api_command_module {

    public function get_game_result_by_request_id($game_platform_id, $request_id) {
        $game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        if (!empty($game_api)) {
            switch ($game_platform_id) {
                case SBTECH_API:
                    $api_method_name = 'getbetbyPurchaseID';
                    break;
                case SBTECH_BTI_API:
                    $api_method_name = 'getbetbyPurchaseID';
                    break;
                default:
                    $api_method_name = null;
                    break;
            }

            if (!empty($api_method_name)) {
                if (method_exists($game_api, $api_method_name)) {
                    $result = $game_api->$api_method_name($request_id);
                    $this->utils->info_log(__FUNCTION__, 'game_platform_id', $game_platform_id, 'result of ' . $api_method_name, $result);
                } else {
                    $this->utils->error_log('API METHOD FOUND API', $api_method_name);
                }
            }
        } else {
            $this->utils->error_log('NOT FOUND API', $game_platform_id);
        }
    }


    public function upload_game_provider_images_to_sftp_server($game_platform_id, $use_curl = 'true', $verify_peer = 'false') {
        // load game api
        $game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $use_curl = $use_curl == 'true';
        $verify_peer = $verify_peer == 'true';

        if (!empty($game_api)) {
            $sftp_config = $game_api->sftp_game_images_config;
            $remote_path = rtrim($game_api->sftp_game_images_remote_path, '/');
            $result = $game_api->queryGameListFromGameProvider();

            // check if not implemented
            if (isset($result['unimplemented']) && $result['unimplemented']) {
                $this->utils->error_log('API not implemented', $game_platform_id);
                return false;
            }

            if (isset($result['success']) && $result['success']) {
                //-- include SFTP library
                set_include_path(APPPATH.'libraries/unencrypt/phpseclib');
                include('Net/SFTP.php');

                //connecting server
                $this->utils->info_log("Connecting to {$sftp_config['hostname']} via SFTP...");

                // Create an SFTP connection
                $sftp = new Net_SFTP($sftp_config['hostname'], $sftp_config['port']);

                // login sftp
                if (!$sftp->login($sftp_config['username'], $sftp_config['password'])) {
                    $this->utils->error_log('Login Failed', $game_platform_id);
                    return false;
                }

                // check if directory existing. If not, create directory
                if (!$sftp->is_dir($remote_path)) {
                    $this->utils->info_log('Creating directory...', $remote_path);
                    $sftp->chdir($remote_path);
                    $sftp->mkdir($remote_path, 07777);
                    $this->utils->info_log('Directory created successfully', $remote_path);
                }

                // check if game platform id directory existing. If not, create game platform id directory
                $game_platform_id_directory_path = $remote_path . "/$game_platform_id/";
                if (!$sftp->is_dir($game_platform_id_directory_path)) {
                    $this->utils->info_log('Creating platform id directory...', $game_platform_id_directory_path);
                    $sftp->chdir($game_platform_id_directory_path);
                    $sftp->mkdir($game_platform_id_directory_path, 07777);
                    $this->utils->info_log('Platform id directory has been created successfully', $game_platform_id_directory_path);
                }

                // register game platform here
                switch ($game_platform_id) {
                    case BNG_SEAMLESS_GAME_API:
                        if (isset($result['games']) && !empty($result['games'])) {
                            foreach ($result['games'] as $game_list) {
                                foreach ($game_list['i18n'] as $language => $value) {
                                    $game_image_url = !empty($value['banner_path']) ? 'https:' . $value['banner_path'] : null;

                                    if (!empty($game_image_url)) {
                                        $image_content = $this->getImageContent($game_image_url, $use_curl, $verify_peer);
                                        $extension = pathinfo(parse_url($game_image_url)['path'], PATHINFO_EXTENSION);
                                        $game_image_name = $game_list['game_id'] . "_{$language}." . $extension;

                                        // check if language directory existing. If not, create language directory
                                        $language_directory_path = rtrim($game_platform_id_directory_path, '/') . "/{$language}/";
                                        if (!$sftp->is_dir($language_directory_path)) {
                                            // creating directory
                                            $this->utils->info_log('Creating language directory...', $language_directory_path);
                                            $sftp->chdir($language_directory_path);
                                            $sftp->mkdir($language_directory_path, 07777);
                                            $this->utils->info_log('Language directory has been created successfully', $language_directory_path);
                                        }

                                        // upload file
                                        if ($sftp->is_dir($language_directory_path)) {
                                            $this->utils->info_log('Uploading image...', 'game_image_name', $game_image_name, 'directory', $language_directory_path);
                                            $sftp->put($language_directory_path . $game_image_name, $image_content); // FTP_BINARY, NET_SFTP_LOCAL_FILE
    
                                            $info = [
                                                'game_image_name' => $game_image_name,
                                                'game_image_url' => $game_image_url,
                                            ];

                                            $this->utils->info_log('Image uploaded successfully', 'info', $info, $game_platform_id);
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        $this->utils->error_log('Game api not registered', $game_platform_id);
                        return false;
                }
            } else {
                $this->utils->error_log('Failed to load game list', $game_platform_id);
                return false;
            }
        } else {
            $this->utils->error_log('Game api not found', $game_platform_id);
            return false;
        }
    }

    public function getImageContent($url, $use_curl = true, $verify_peer = false) {
        if ($use_curl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_peer);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.1 Safari/537.11');
    
            // Execute the cURL request
            $image_content = curl_exec($ch);
            $rescode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
            // Close the cURL session
            curl_close($ch);

            $this->utils->info_log(__METHOD__, 'using curl');
        } else {
            if ($verify_peer) {
                $image_content = file_get_contents($url);
            } else {
                $context_options = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ];

                $image_content = file_get_contents($url, false, stream_context_create($context_options));
            }

            $this->utils->info_log(__METHOD__, 'using file_get_contents');
        }

        // Output the image content
        return $image_content;
    }

    public function query_incomplete_game_by_username($username, $gamePlatformId=null){
        set_time_limit(0);

        $today=date('Ymd');
        $this->utils->initRespTableByDate($today);

        $apiList=null;
        if(empty($gamePlatformId)){
            $apiList=$this->utils->getConfig('exists_incomplete_game_api_list');
            //remove inactive api
            $this->load->model(['external_system']);
            $apiList=$this->external_system->filterActiveGameApi($apiList);
        }else{
            $apiList=[$gamePlatformId];
        }
        $this->utils->info_log('=========start query_incomplete_game_by_username============================', $apiList);
        if(empty($apiList)){
            $this->utils->debug_log('no any available api', $gamePlatformId, $apiList);
            return;
        }
        foreach ($apiList as $apiId) {
            $rlt=null;
            $api=$this->utils->loadExternalSystemLibObject($apiId);
            if(!empty($api)){
                $this->utils->debug_log('queryIncompleteGames', $api->getPlatformCode());
                $rlt=$api->queryIncompleteGames($username);
                $this->utils->info_log('result of queryIncompleteGames', $rlt);
            }else{
                $this->utils->error_log('NOT FOUND API', $apiId);
            }
            $this->utils->info_log('apiId', $apiId, 'result', $rlt);
        }

        $this->utils->info_log('=========end query_incomplete_game_by_username============================', $apiList);
    }

    public function cancelGameRound($game_platform_id, $game_username, $round_id, $game_code = '_null'){
        $result = [
            'success' => false,
            'message' => null,
        ];

        $gameApi = $this->utils->loadExternalSystemLibObject($game_platform_id);

        if ($gameApi) {
            $params = [
                'game_username' => $game_username,
                'round_id' => $round_id,
                'game_code' => $game_code,
            ];

            $result = $gameApi->cancelGameRound($params);
        } else {
            $this->utils->error_log(__METHOD__, 'NOT FOUND API', $gameApi);
        }

        $this->utils->info_log(__METHOD__, 'game_platform_id', $game_platform_id, 'result', $result);

        return $result;
    }
}