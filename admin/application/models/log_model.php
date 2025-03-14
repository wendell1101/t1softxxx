<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Log_model extends BaseModel {

	function __construct() {
		parent::__construct();
	}

	function recordAffLog($data=null) {

		$this->load->library(array('user_agent'));

		$affId=$this->session->userdata('affiliateId');
		$username=$this->session->userdata('affiliateUsername');
		$uri=current_url();
		$class = $this->router->class;
		$method = $this->router->method;
		$referrer=$this->agent->referrer();
		$ip=$this->utils->getIP();

		if(empty($username)){
			$username=null;
		}
		if(empty($affId)){
			$affId=null;
		}

		$data=[
			'username' => $username,
			'affiliate_id'=> $affId,
			'uri' => $uri,
			'management' => $class,
			'action' => $method,
			'ip' => $ip,
			'referrer' => $referrer,
			'data' => $this->utils->encodeJson($data),
			'updated_at' => date("Y-m-d H:i:s"),
			'status' => self::DB_TRUE,
		];

		$insert_id = $this->insertData('aff_logs', $data);
        $this->session->set_userdata('aff_log_id', $insert_id);
        return $insert_id;
	}

    /**
     * function : updateAffLogById
     * Update aff log by id 
     * 
     * @param $data
     */
    function updateAffLogById($data= null) {


        $id=$this->session->userdata('aff_log_id');
		$affId=$this->session->userdata('affiliateId');
		$username=$this->session->userdata('affiliateUsername');

        $updateData = [
            'username' => $username,
            'affiliate_id'=> $affId,
        ];

        $this->updateData('id', $id, 'aff_logs', $updateData);
    }
}
