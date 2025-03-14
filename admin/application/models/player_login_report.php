<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * player_login_report
 *
 *
 */
class Player_login_report extends BaseModel {
	protected $tableName = 'player_login_report';

	const LOGIN_SUCCESS = 1;
	const LOGIN_FAILED  = 2;

	const LOGIN_FROM_ADMIN  = 1;
	const LOGIN_FROM_PLAYER = 2;

    const GROUP_BY_NONE = 0; // None
    const GROUP_BY_CLIENTEND_PLAYER = 1; // Client End and Player
    const GROUP_BY_CLIENTEND_LOGINIP = 2; // Client End and Login IP
    const GROUP_BY_USERNAME_CLIENTEND_LOGINIP = 3; // Username, Login IP and Client End

	public function __construct() {
		parent::__construct();
	}

	/**
	 * overview : add player_login_report
	 * @param array $data
	 * @return array
	 */
	public function insertPlayerLoginDetails($data, $params = array()) {
		if (!empty($data)) {
			return $this->insertRow($data);
		}
		return false;
	}

	/**
	 * overview : get Player login Details
	 * @param int $playerId
	 * @return array
	 */
	public function getPlayerLoginDetails($playerId) {
		$this->db->select('*')
		->from($this->tableName)
		->where('player_id', $playerId);

		return $this->runMultipleRow();
	}

    public function getLatestPlayerLoginDetail($playerId) {
		$this->db->select('*')
		->from($this->tableName)
		->where('player_id', $playerId)
        ->order_by('id', 'DESC')
        ->limit(1)
        ;

		return $this->runOneRow();
	}

	public function getPlayerLoginIpByDate($playerId, $from, $to){
		$result = [];
		if(!empty($from) && !empty($to)){
			$this->db->distinct()
				->select('ip')
				->from($this->tableName)
				->where('player_id', $playerId)
				->where('create_at >=', $from)
				->where('create_at <=', $to);

			$result = $this->runMultipleRowOneFieldArray('ip');
		}

		return $result;
	}

	/**
	 * overview: check two player have common ip between date
	 * @param int $firstPlayerId
	 * @param int $secondPlayerId
	 * @param string $checkFrom
	 * @param string $checkTo
	 * @return array [existCommonIp, commonIpList]
	 */
	public function existCommonIpBetweenDate($firstPlayerId, $secondPlayerId, $checkFrom, $checkTo){
		$existCommonIp = false;
		$commonIpList = [];

		if(empty($firstPlayerId) || empty($secondPlayerId)){
			$this->utils->debug_log(__METHOD__ . " missing player]", ['firstPlayerId' => $firstPlayerId, 'secondPlayerId' => $secondPlayerId]);
			return [$existCommonIp, $commonIpList];
		}

		if(!empty($checkFrom) && !empty($checkTo)){
			$checkDatetimeFrom = $checkFrom . ' ' . Utils::FIRST_TIME;
			$checkDatetimeTo = $checkTo . ' ' . Utils::LAST_TIME;

			$firstPlayerIpList = $this->getPlayerLoginIpByDate($firstPlayerId, $checkDatetimeFrom, $checkDatetimeTo);
			$secondPlayerIpList = $this->getPlayerLoginIpByDate($secondPlayerId, $checkDatetimeFrom, $checkDatetimeTo);

			$commonIpList = array_intersect($firstPlayerIpList, $secondPlayerIpList);

			$this->utils->debug_log(__METHOD__ . " result", [
				'firstPlayerId' => $firstPlayerId, 'secondPlayerId' => $secondPlayerId,
				'firstPlayerIpList' => $firstPlayerIpList, 'secondPlayerIpList' => $secondPlayerIpList,
				'check_ip_from' => $checkDatetimeFrom, 'check_ip_to' => $checkDatetimeTo,
				'commonIpList' => $commonIpList
			]);
		}

		if(!empty($commonIpList)){
			$existCommonIp = true;
		}

		return [$existCommonIp, $commonIpList];
	}

    public function browserType_to_clientEnd($browser_type){
        $this->load->model(array('http_request'));
        $client_end = lang('N/A');
        switch ($browser_type) {
            case Http_request::HTTP_BROWSER_TYPE_PC:
                $client_end = lang('PC');
                break;
            case Http_request::HTTP_BROWSER_TYPE_MOBILE:
                $client_end = lang('MOBILE');
                break;
            case Http_request::HTTP_BROWSER_TYPE_IOS:
                $client_end = lang('APP IOS');
                break;
            case Http_request::HTTP_BROWSER_TYPE_ANDROID:
                $client_end = lang('APP ANDROID');
                break;
        }
        return $client_end;
    }

    public function existsPlayerLoginByApp($playerId){
        $this->load->model(array('http_request'));
        $allow_browser_type = [Http_request::HTTP_BROWSER_TYPE_IOS, Http_request::HTTP_BROWSER_TYPE_ANDROID];

        $this->db->select('*')
                 ->from($this->tableName)
                 ->where('player_id', $playerId)
                 ->where_in('browser_type', $allow_browser_type);

        return $this->runExistsResult();
    }

    public function getPlayerLoginCountByDate($date = null) {
    	if(empty($date)){
    		//defult all day
			$date = date('Y-m-d 00:00:00');
    	}

    	$rows = [];
		$this->db->select('*')
		->from($this->tableName)
		->where("create_at >= ", $date);

		$rows = $this->runMultipleRow();
		return count($rows);
	}

	public function getAllFailedLoginAttempts($playerId=null , $playerIdClauseList=null, $from=null, $to=null, $limit = null, $page = null){
		$result = $this->getDataWithAPIPagination('player_login_report', function() use($playerId, $playerIdClauseList, $from, $to) {
			$this->db->select('player_login_report.id,
								player_login_report.player_id playerId,
								player_login_report.create_at AS date ,
								player_login_report.login_result AS login_status,
								player_login_report.player_status AS account_status,
								player_login_report.referrer,
								player_login_report.ip,
								player_login_report.device,
								player_login_report.login_from AS login_from,
								player_login_report.browser_type AS client_end,
								player_login_report.player_id AS tag');

            if (!empty($playerId)) {
				$this->db->where('player_login_report.player_id', $playerId);
            }

            if( ! empty($playerIdClauseList) ){
                $this->db->where_in('player_login_report.player_id', $playerIdClauseList);
            }

            if (!empty($from) && !empty($to)) {
                $this->db->where('player_login_report.create_at BETWEEN ' . $this->db->escape($from) . ' AND ' . $this->db->escape($to) . '');
            }

            $this->db->where('player_login_report.login_result', self::LOGIN_FAILED);
            $this->db->where('player_login_report.player_status', player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT);
            $this->db->order_by('player_login_report.create_at', 'desc');
        }, $limit, $page);

        $this->CI->utils->debug_log(__METHOD__, 'result', $result);

        foreach($result['list'] as &$entry) {
            $username = $this->player_model->getUsernameById($entry['playerId']);
            $entry['username'] = $username;
            $entry['id'] = (int)$entry['id'];
            $entry['tag'] = $this->player_model->player_tagged_list($entry['tag']);
            $entry['login_status'] = $entry['login_status'] == self::LOGIN_SUCCESS ? lang("player_login_report_login_success") : lang("player_login_report_login_failed");

            $account_status = '';
            switch($entry['account_status']){
	            case 0:
	                $account_status = '<span class="text-success">' .lang('status.normal').'</span>';
	                break;
	            case 1:
	                $account_status = '<span class="text-danger">' .lang('Blocked').'</span>';
	                break;
	            case 5:
	                $account_status = '<span class="text-danger">' .lang('Suspended').'</span>';
	                break;
	            case 7:
	                $account_status = '<span class="text-muted">' .lang('Self Exclusion').'</span>';
	                break;
	            case 8:
	                $account_status = '<span class="text-danger">' .lang('Failed Login Attempt').'</span>';
	                break;
	        }

            $entry['account_status'] = strip_tags($account_status);
            $entry['login_from'] = $entry['login_from'] == self::LOGIN_FROM_ADMIN ? lang("player_login_report_login_from_admin") : lang("player_login_report_login_from_player");
            $entry['client_end'] = !empty($entry['client_end']) ? $this->player_login_report->browserType_to_clientEnd($entry['client_end']) : lang('N/A');
            unset($entry['playerId']);
        }
        return $result;
	}
}
