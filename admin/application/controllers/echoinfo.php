<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/marketing_management.php';

class Echoinfo extends BaseController {

    const TAG_CODE_SPORTS = 'sports';
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->library(array('permissions', 'form_validation', 'template'));
	}

	function index() {
		$this->load->library('session');
		$info = $this->utils->debug_log('client ip', $this->utils->getIP(),
			'header', $this->input->request_headers(), 'user agent', $this->input->user_agent(),
			'session', $this->session->all_userdata()
		);
		// $info = 'ip:' . $this->utils->getIP() . "\nheader:" . $this->input->request_headers();
		$this->returnText($info);
	}

/*--- use to display the bet details in new table with game history : for okada sports ---*/
    function bet_details($unique_id, $url=null, $is_unsettle_logs = false){
         try{
            $this->load->model(array('game_logs','game_type_model','game_description_model'));

             $fields = array('game_description_id','bet_details','note');

             if ($is_unsettle_logs) {
                 $data = $this->game_logs->getUnsettleUniqueExternalId($unique_id, $fields);
             } else {
                 $data = $this->game_logs->getBetDetailsByUniqueid($unique_id, $fields);
             }

            $result = $this->game_type_model->getGameTagsByDescriptionId($data[0]['game_description_id']);

            $game_name = $result['game_name'];
            $data = $data[0];

            if($result['tag_code'] == self::TAG_CODE_SPORTS ){
                $is_sports = true;
            }else{
                $is_sports = null;
            }

            return $this->load->view('marketing_management/view_bet_detail', compact('data', 'url', 'game_name', 'is_sports'));
        }catch (\Illuminate\Database\QueryException $e){
            return $this->utils->debug_log('======= DB ERROR!', $e);
        }
    }

    public function pretty_print_json($str){
        $decoded_data = base64_decode(urldecode($str));
        $array_data = json_decode($decoded_data,true);
        return $this->returnJsonResult($array_data, $addOrigin = true, $origin = "*", $pretty = true);
    }

    public function print_encoded_string($str){
        $paramstr =  base64_decode(urldecode($str));
        parse_str($paramstr, $params);
        // echo "<pre>";
        // print_r($params);exit();
        
        if(isset($params['bets'])){
            if(!is_array($params['bets'])){
                $params['bets'] = json_decode($params['bets'], true);
            }
        }
        $array_data = [
            'params' => $params,
            'param_encoded' => http_build_query($params)
        ];
        return $this->returnJsonResult($array_data, $addOrigin = true, $origin = "*", $pretty = true);
    }

    function match_details($platform_id, $round, $url=null, $is_unsettle_logs = false){
        try{
            #load game api
            $api = $this->utils->loadExternalSystemLibObject($platform_id);
            $data = $api->getMatchDetailsByRound($round);
            return $this->load->view('marketing_management/view_match_detail', compact('data'));
       }catch (\Illuminate\Database\QueryException $e){
           return $this->utils->debug_log('======= DB ERROR!', $e);
       }
   }

}

///END OF FILE