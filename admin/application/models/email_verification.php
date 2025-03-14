<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * SMS Verification
 */
class Email_verification extends BaseModel
{
    protected $tableName = 'email_verification_report';

    const SENDING_STATUS_INPROGRESS = 0;
    const SENDING_STATUS_SUCCESS = 1;
    const SENDING_STATUS_FAILED = 2;

    const QUEUE_STATUS_NEW_JOB = 2;
    const QUEUE_STATUS_DONE = 3;
    const QUEUE_STATUS_READ = 4;
    const QUEUE_STATUS_ERROR = 5;
    const QUEUE_STATUS_STOPPED = 6;


    public function __construct()
    {
        parent::__construct();
    }

    public function getEmailTemplateOptions()
    {
        $options = ['player_verify_email', 'player_forgot_login_password'];
        $query = $this->db->query('SELECT DISTINCT email_template FROM email_verification_report');
        $result = $query->result_array();
        foreach ($result as $option) {
            if(in_array($option['email_template'], $options)){
                continue;
            }
            $options[] = $option['email_template'];
        }
        return $options;
    }

    public function recordReport($player_id=null, $email_address=null, $email_template, $verification_code, $job_token=null, $sending_status=self::SENDING_STATUS_INPROGRESS)
    {
        if (empty($email_address) || empty($verification_code)) {
            return false;
        }
        $entry = array(
            'player_id'	        => $player_id,
            'email_address'		=> $email_address,
            'email_template'	=> $email_template,
            'verification_code'	=> $verification_code,
            'job_token'         => $job_token,
            'sending_status'    => $sending_status,
            'created_at'        => $this->utils->getNowForMysql(),
        );
        $is_insert = $this->db->insert($this->tableName, $entry);
        if ($is_insert) {
            return $this->db->insert_id();
        }
        return false;
    }

    public function updateSendingStatusToSuccess($record_id)
    {
        $this->db->where('id', $record_id);
        $this->db->update($this->tableName, array('sending_status' => SELF::SENDING_STATUS_SUCCESS));
        return $this->db->affected_rows();
    }

    public function updateSendingStatusToFailed($record_id)
    {
        $this->db->where('id', $record_id);
        $this->db->update($this->tableName, array('sending_status' => SELF::SENDING_STATUS_FAILED));
        return $this->db->affected_rows();
    }

    public function syncQueueResult($request = array())
    {
        $this->load->library('data_tables');
        $input = $this->data_tables->extra_search($request);
        if (isset($input['date_from'], $input['date_to'])) {
            $where = sprintf($this->tableName.".created_at BETWEEN '%s' AND '%s'", $input['date_from'], $input['date_to']);
            $this->db->where($where);
        }
        if (isset($input['emailAddress'])) {
            $where = sprintf($this->tableName.".email_address LIKE '%s'", '%'.$input['emailAddress'].'%');
            $this->db->where($where);
        }
        if (isset($input['emailTemplate']) && $input['emailTemplate']!='') {
            $where = sprintf($this->tableName.".email_template = '%s'", $input['emailTemplate']);
            $this->db->where($where);
        }

        if (isset($input['verificationCode'])) {
            $where = sprintf($this->tableName.".verification_code = '%s'", $input['verificationCode']);
            $this->db->where($where);
        }

        $this->db->select('email_verification_report.id, email_verification_report.job_token');
        $this->db->select('queue_results.result as job_result, queue_results.status as queue_status');
        $this->db->join('queue_results', 'queue_results.token= email_verification_report.job_token COLLATE utf8_general_ci', 'LEFT');
        $this->db->where('sending_status', self::SENDING_STATUS_INPROGRESS);
        $query = $this->db->get($this->tableName);
        $res =  $query->result_array();
        foreach ($res as $key => $reocrd) {
            if (empty($reocrd['job_token'])) {
                $this->updateSendingStatusToFailed($reocrd['id']);
                continue;
            }
            switch ($reocrd['queue_status']) {
                case self::QUEUE_STATUS_DONE:
                    $this->updateSendingStatusToSuccess($reocrd['id']);
                    break;
                case self::QUEUE_STATUS_ERROR:
                    $this->updateSendingStatusToFailed($reocrd['id']);
                    break;
            }
        }
        return true;
    }

    public function listVerificationCodes($request, $is_export = false)
    {
        // $this->tableName = 'player';
        $this->load->library('data_tables');
		$this->email_verification->syncQueueResult($request);
        $loggedUserId = $this->authentication->getUserId();
        $user = $this->users->getUserById($loggedUserId);
        $allow_checking_verification_code = $this->users->isAuthorizedauthorizedViewVerificationCode($user['username'], 'authorized_view_email_verification_code');

        $i = 0;
        $columns = array(
            array(
                'alias' => 'playerId',
                'select' => $this->tableName.".player_id",
            ),
            array(
                'alias' => 'date',
                'select' => $this->tableName.'.created_at',
                'name' => lang('Date'),
                'dt' => $i++,
            ),
            array(
                'alias' => 'player_sername',
                'select' => "player.username",
                'name' => lang('Player Username'),
                'dt' => $i++,
                'formatter'=> function ($d, $row) {
                    return '<a href="/player_management/userInformation/' . $row['playerId'] . '">' . $d . '</a>';
                }
            ),
            array(
                'alias' => 'email_address',
                'select' => $this->tableName.".email_address",
                'name' => lang('Email Address'),
                'dt' => $i++,
            ),
            array(
                'alias' => 'email_template',
                'select' => $this->tableName.".email_template",
                'name' => lang('email_template'),
                'dt' => $i++,
                // 'formatter'=> function($d){
                //     return 'Email Template';
                // }
            ),
            array(
                'alias' => 'verification_code',
                'select' => $this->tableName.".verification_code",
                'name' => lang('Verification Code'),
                'dt' => $i++,
                'formatter' => function($d) use ($allow_checking_verification_code){
					if(!$allow_checking_verification_code){
						return '******';
					}
					return $d;
				}
            ),
            array(
                'alias' => 'status',
                'select' => $this->tableName.".sending_status",
                'name' => lang('Status'),
                'dt' => $i++,
                'formatter'=> function ($d) {
                    switch ($d) {
                        case self::SENDING_STATUS_SUCCESS:
                            return lang('Sent Successfully');
                            break;
                        case self::SENDING_STATUS_FAILED:
                            return lang('Sending Failed');
                            break;
                        case self::SENDING_STATUS_INPROGRESS:
                            return lang('Sending In Progress');
                            break;
                        default:
                            return '-';
                            break;
                    }
                }
            ),
            array(
                'alias' => 'verify_status',
                'select' => $this->tableName.".verify_status",
                'name' => lang('Verify_Status'),
            )
        );
        $input = $this->data_tables->extra_search($request);
        $where = array();
        $values = array();
        $joins = array();
        $joins['player'] = $this->tableName.'.player_id = player.playerId';
        $group_by = array();
        $having = array();

        if (isset($input['date_from'], $input['date_to'])) {
            $where[] = $this->tableName.".created_at BETWEEN ? AND ?";
            $values[] = $input['date_from'];
            $values[] = $input['date_to'];
        }

        if (isset($input['username'])) {
            $where[] = "player.username LIKE ?";
            $values[] = '%' . $input['username'] . '%';
        }

        if (isset($input['emailAddress'])) {
            $where[] = $this->tableName.'.email_address LIKE ?';
            $values[] = '%' . $input['emailAddress'] . '%';
        }
        if (isset($input['emailTemplate']) && $input['emailTemplate']!='' ) {
            $where[] = $this->tableName.'.email_template = ?';
            $values[] = $input['emailTemplate'];
        }

        if (isset($input['verificationCode'])) {
            $where[] = $this->tableName.'.verification_code = ?';
            $values[] = $input['verificationCode'];
        }

        if (isset($input['sendingStatus']) && $input['sendingStatus']!='' ) {
            $where[] = $this->tableName.'.sending_status = ?';
            $values[] = $input['sendingStatus'];
        }
        return $this->data_tables->get_data($request, $columns, $this->tableName, $where, $values, $joins, $group_by, $having, false);
    }
}
