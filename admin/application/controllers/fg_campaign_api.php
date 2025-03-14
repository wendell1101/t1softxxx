<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Fg_campaign_api extends BaseController {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('external_system','common_game_free_spin_campaign'));
    }

    /**
     * Entry point
     *
     *
     * @param int $apiId
     * @param string $currency
     * @param string $method
     *
    */
    public function index($method) {
        $this->method = $method;
        $input = file_get_contents('php://input');
        $this->request = json_decode($input, true);
        // echo "<pre>";print_r($this->request);

        $this->currency = isset($this->request['currency']) ? strtolower($this->request['currency']) : null;
        $this->apiId = isset($this->request['gamePlatformId']) ? $this->request['gamePlatformId'] : null;

        if(!$this->getCurrencyAndValidateDB()){
            $result = array("success" => false, "desc" => "Invalid currency.");
            return $this->returnJsonResult($result);
        }
        
        if(!$this->external_system->isGameApiActive($this->apiId) || $this->external_system->isGameApiMaintenance($this->apiId)) {
            $this->utils->debug_log('FG is inactive/maintenance (Error Response Result)');
            $result = array("success" => false, "desc" => "Game on maintenance or invalid Api Id");
            return $this->returnJsonResult($result);
        }
        $this->api = $this->utils->loadExternalSystemLibObject($this->apiId);

        if(!method_exists($this, $method)) {
            $result = array("success" => false, "desc" => "Method not exist.");
            return $this->returnJsonResult($result);
        }

        $this->$method(); 
    }

    /**
     * getCurrencyAndValidateDB
     *
     * @return [type]            [description]
     */
    private function getCurrencyAndValidateDB() {
        if(isset($this->currency) && !empty($this->currency)) {
            # Get Currency Code for switching of currency and db forMDB
            $is_valid=$this->validateCurrencyAndSwitchDB();
            return $is_valid;
        } else {
            return false;
        }
    }

    protected function validateCurrencyAndSwitchDB(){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        if(empty($this->currency)){
            return false;
        }else{
            //validate currency name
            if(!$this->utils->isAvailableCurrencyKey($this->currency)){
                //invalid currency name
                return false;
            }else{
                //switch to target db
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($this->currency);
                return true;
            }
        }
    }

    public function add_campaign_member(){
        $result = $this->api->addCampaignMember($this->request);
        $success = isset($result['success']) && $result['success'] ? true : false;
        $output = array(
            "success" => $success,
            "desc" => $success ? "Success" : "Failed. Something went wrong."
        );
        $this->returnJsonResult($output);
    }

    public function get_player_available_campaign(){
        $game_platform_id = $this->request['gamePlatformId'];
        $player_id = $this->request['playerId'];
        $campaignIds = $this->common_game_free_spin_campaign->getPlayerAvailableCampaign($player_id, $game_platform_id);
        $output = array(
            "success" => true,
            "campaignIds" => $campaignIds
        );
        $this->returnJsonResult($output);
    }

    public function check_player_if_exist_on_campaign(){
        $campaign_id = $this->request['campaignId'];
        $player_id = $this->request['playerId'];
        $exist = $this->common_game_free_spin_campaign->checkPlayerIfExistOnCampaign($campaign_id, $player_id);
        $output = array(
            "success" => true,
            "exist" => $exist
        );
        $this->returnJsonResult($output);
    }

    public function get_available_campaign_for_new_player(){
        $campaignIds = $this->common_game_free_spin_campaign->getAvailableCampaignForNewPlayer($this->apiId);
        $output = array(
            "success" => true,
            "campaignIds" => $campaignIds
        );
        $this->returnJsonResult($output);
    }

    public function get_available_campaign(){
        $campaignIds = $this->common_game_free_spin_campaign->getAvailableCampaign($this->apiId);
        $output = array(
            "success" => true,
            "campaignIds" => $campaignIds
        );
        $this->returnJsonResult($output);
    }

    public function create_campaign(){
        
        $success = false;
        $result = $this->api->createCampaign($this->request);
        $id = null;
        if($result['success']){

            $campaign = isset($result['data']) ? $result['data'] : null;
            $dataToInsert = array(
                "game_platform_id" => $this->apiId,
                "campaign_id" => isset($campaign['id']) ? $campaign['id'] : null,
                "name" => isset($campaign['name']) ? $campaign['name'] : null,
                "num_of_games" => isset($campaign['num_of_games']) ? $campaign['num_of_games'] : null,
                "status" => isset($campaign['status']) ? $campaign['status'] : null,
                "currency" => isset($campaign['currency']) ? $campaign['currency'] : null,
                "start_time" => isset($campaign['start_time']) ? $campaign['start_time'] : null,
                "end_time" => isset($campaign['end_time']) ? $campaign['end_time'] : null,
                "is_for_new_player" => isset($this->request['newPlayerOnly']) ? $this->request['newPlayerOnly'] : null,
                "extra" => json_encode($result),
                # SBE additional info
                "response_result_id" => isset($result['response_result_id']) ? $result['response_result_id'] : null,
                "external_uniqueid" => $this->apiId."-".$campaign['id'],
            );
            $success = $this->common_game_free_spin_campaign->insertData('common_game_free_spin_campaign',$dataToInsert);
            if($success){
                if(isset($campaign['id'])){
                    $id = $campaign['id'];
                    $this->formatArray($id, $this->request['players'], 'player_id');
                    $this->formatArray($id, $this->request['games'], 'external_game_id');
                    $this->formatArray($id, $this->request['vipsettings'], 'vip_setting_id');

                    $this->common_game_free_spin_campaign->runBatchInsertWithLimit($this->db, 'free_spin_campaign_players', $this->request['players']);
                    $this->common_game_free_spin_campaign->runBatchInsertWithLimit($this->db, 'free_spin_campaign_games', $this->request['games']);
                    $this->common_game_free_spin_campaign->runBatchInsertWithLimit($this->db, 'free_spin_campaign_viplevels', $this->request['vipsettings']);
                }
            }
        } else {
            $result = array(
                "error" => array(
                    "message" => "Failed. Something went wrong." 
                )
            );
        }
        $output = array(
            "id" => $id,
            "success" => $success ? true : false,
            "desc" => $success ? "Success" : "Failed. Something went wrong.",
            "result" => $result
        );
        $this->returnJsonResult($output);
        
    }

    public function formatArray($campaign_id, &$array, $index_name){
        if(!empty($array)){
            foreach ($array as $key => $value) {
                $array[$key] = array(
                    "campaign_id" => $campaign_id,
                    "{$index_name}" => $value
                );
            }
        }
    }

    public function get_campaign_details(){
        $campaign_id = $this->request['campaignId'];
        $campaign_details = $this->common_game_free_spin_campaign->getCampaignDetails($this->apiId, $campaign_id);
        $output = array(
            "success" => true,
            "result" => $campaign_details
        );
        $this->returnJsonResult($output);
    }

    public function update_campaign(){
        
        $success = false;
        $from = strtotime($this->request['from']);
        $today = $this->utils->getTimestampNow();
        $this->request['running'] = ($today >= $from) ? true : false;
        $result = $this->api->updateCampaign($this->request);
        $id = null;
        if($result['success']){

            $campaign = isset($result['data']) ? $result['data'] : null;
            $dataToUpdate = array(
                "game_platform_id" => $this->apiId,
                "campaign_id" => isset($campaign['id']) ? $campaign['id'] : null,
                "name" => isset($campaign['name']) ? $campaign['name'] : null,
                "num_of_games" => isset($campaign['num_of_games']) ? $campaign['num_of_games'] : null,
                "status" => isset($campaign['status']) ? $campaign['status'] : null,
                "currency" => isset($campaign['currency']) ? $campaign['currency'] : null,
                "start_time" => isset($campaign['start_time']) ? $campaign['start_time'] : null,
                "end_time" => isset($campaign['end_time']) ? $campaign['end_time'] : null,
                "version" => isset($campaign['version']) ? $campaign['version'] : null,
                "is_for_new_player" => isset($this->request['newPlayerOnly']) ? $this->request['newPlayerOnly'] : null,
                "extra" => json_encode($result),
                # SBE additional info
                "response_result_id" => isset($result['response_result_id']) ? $result['response_result_id'] : null,
                "external_uniqueid" => $this->apiId."-".$campaign['id'],
            );
            $success = $this->common_game_free_spin_campaign->updateData('external_uniqueid', $dataToUpdate['external_uniqueid'], 'common_game_free_spin_campaign', $dataToUpdate);
            if($success){
                if(isset($campaign['id'])){
                    $id = $campaign['id'];
                    $this->common_game_free_spin_campaign->runCampaignGameDelete($id);
                    $this->common_game_free_spin_campaign->runCampaignPlayerDelete($id);
                    $this->common_game_free_spin_campaign->runCampaignVipDelete($id);
                    $this->formatArray($id, $this->request['players'], 'player_id');
                    $this->formatArray($id, $this->request['games'], 'external_game_id');
                    $this->formatArray($id, $this->request['vipsettings'], 'vip_setting_id');

                    $this->common_game_free_spin_campaign->runBatchInsertWithLimit($this->db, 'free_spin_campaign_players', $this->request['players']);
                    $this->common_game_free_spin_campaign->runBatchInsertWithLimit($this->db, 'free_spin_campaign_games', $this->request['games']);
                    $this->common_game_free_spin_campaign->runBatchInsertWithLimit($this->db, 'free_spin_campaign_viplevels', $this->request['vipsettings']);
                }
            }
        }
        $output = array(
            "id" => $id,
            "success" => $success ? true : false,
            "desc" => $success ? "Success" : "Failed. Something went wrong.",
            "result" => $result
        );
        $this->returnJsonResult($output);
        
    }
}
