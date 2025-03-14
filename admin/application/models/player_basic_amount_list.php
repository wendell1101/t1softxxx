<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Player_basic_amount_list extends BaseModel {

    protected $tableName = "player_basic_amount_list";

    CONST DATE_MODE_CREATED = 'created';
    CONST DATE_MODE_UPDATED = 'updated';

    CONST SYNC_RESULT_CODE_INSERTED_COMPLETE = 1;
    CONST SYNC_RESULT_CODE_EDITED_COMPLETE = 2;
    CONST SYNC_RESULT_CODE_USERNAME_NOT_EXIST = 3;
    CONST SYNC_RESULT_CODE_UNKNOWN_ERROR = 4;
    CONST SYNC_RESULT_CODE_INVALID_BET_AMOUNT = 5;
    CONST SYNC_RESULT_CODE_INVALID_DEPOSIT_AMOUNT = 6;
    CONST SYNC_RESULT_CODE_EMPTY_DATA = 7;
    CONST SYNC_RESULT_CODE_USERNAME_IS_REQUIRED= 8;


    function __construct() {
        parent::__construct();
    }

    public function getDataListByField($field_value, $field_name = 'player_username') {
		$this->db->from($this->tableName);
		$this->db->where($field_name, $field_value);
		return $this->runMultipleRowArray();
	}

    /**
     * Get the Reason by SYNC_RESULT_CODE
     *
     * @param integer $result_code
     * @return string the message after lang().
     */
    public function getReasonBySyncResultCode($result_code){
        switch($result_code){
            case Player_basic_amount_list::SYNC_RESULT_CODE_INSERTED_COMPLETE:
            case Player_basic_amount_list::SYNC_RESULT_CODE_EDITED_COMPLETE:
                $reason = lang('Done');
                break;
            case Player_basic_amount_list::SYNC_RESULT_CODE_USERNAME_IS_REQUIRED:
                $reason = lang('Username is required');
                break;
            case Player_basic_amount_list::SYNC_RESULT_CODE_USERNAME_NOT_EXIST:
                $reason = lang('Username does not exist');
                break;
            case Player_basic_amount_list::SYNC_RESULT_CODE_INVALID_BET_AMOUNT:
                $reason = lang('Bet amount value has something wrong');
                break;
            case Player_basic_amount_list::SYNC_RESULT_CODE_INVALID_DEPOSIT_AMOUNT:
                $reason = lang('Deposit amount value has something wrong');
                break;
            case Player_basic_amount_list::SYNC_RESULT_CODE_EMPTY_DATA:
                $reason = lang('The data is empty');
                break;

            default:
            case Player_basic_amount_list::SYNC_RESULT_CODE_UNKNOWN_ERROR:
                $reason = lang('error.default.message');
                break;
        }
        return $reason;
    }
    /**
     * Sync Amounts By Player Username OR By another field
     * Insert / Update the amounts by player_username
     *
     *
     * @param array $data
     * @return array The results array, the format as followings,
     * - $results['is_done'] bool If Insert / Update action complete, it will be true.
     * - $results['code'] integer For check the result case.
     * - $results['before_row'] array The row data array before action, under $results['is_done']=true.
     * - $results['after_row'] array The row data array after action, under $results['is_done']=true.
     * - $results['affected_rows'] integer The affected rows count, under edit action.
     * - $results['insert_id'] integer The P.K. of the inserted data, under insert action.
     */
    public function syncAmountsByUsername($data, $detect_exist_field_name = 'player_username', $is_only_username_exist = false) {
        $this->load->model(['player_model']);
        $results = [];
        $results['is_done'] = null; // initial value
        $field_name = $detect_exist_field_name;

        if( !empty($data)){

            if($is_only_username_exist){
                if( ! empty($data['player_username']) ){
                    $isUsernameExist = $this->player_model->getPlayerArrayByUsername($data['player_username']);
                    if( ! $isUsernameExist ){
                    // if( ! is_numeric($data['total_bet_amount']) ){
                        $results['is_done'] = false;
                        $results['code'] = self::SYNC_RESULT_CODE_USERNAME_NOT_EXIST;
                    }
                }else{
                    $results['is_done'] = false;
                    $results['code'] = self::SYNC_RESULT_CODE_USERNAME_IS_REQUIRED;
                }
            }

            if( ! empty($data['total_bet_amount']) ){
                if( ! is_numeric($data['total_bet_amount']) ){
                    $results['is_done'] = false;
                    $results['code'] = self::SYNC_RESULT_CODE_INVALID_BET_AMOUNT;
                }
            }

            if( ! empty($data['total_bet_amount']) ){
                if( ! is_numeric($data['total_deposit_amount']) ){
                    $results['is_done'] = false;
                    $results['code'] = self::SYNC_RESULT_CODE_INVALID_DEPOSIT_AMOUNT;
                }
            }
            if( empty($data) ){
                $results['is_done'] = false;
                $results['code'] = self::SYNC_RESULT_CODE_EMPTY_DATA;
            }

            if($results['is_done'] === null){
                $isExist = $this->isFieldExist($field_name, $data[$field_name]);

                if($isExist){
                    $this->db->where($field_name, $data[$field_name])
                        ->from($this->tableName);
                    $results['before_row'] = $this->runOneRowArray();

                    $data['updated_at'] = $this->utils->getNowForMysql();
                    $this->db->where($field_name, $data[$field_name])
                            ->update($this->tableName, $data);
                    $results['affected_rows'] = $this->db->affected_rows();
                    $results['code'] = self::SYNC_RESULT_CODE_EDITED_COMPLETE;
                    $results['is_done'] = true;
                }else{
                    $results['before_row'] = [];

                    $results['insert_id'] = $this->insertData($this->tableName, $data);
                    if( !empty($results['insert_id'] ) ){
                        $results['code'] = self::SYNC_RESULT_CODE_INSERTED_COMPLETE;
                        $results['is_done'] = true;
                    }
                }

                if($results['is_done']){
                    $this->db->where($field_name, $data[$field_name])
                        ->from($this->tableName);
                    $results['after_row'] = $this->runOneRowArray();
                }else if($results['is_done'] === null){
                    $results['code'] = self::SYNC_RESULT_CODE_UNKNOWN_ERROR;
                }
            } // EOF if($results['is_done'] === null){...

        }else{
            $results['is_done'] = false;
            $results['code'] = self::SYNC_RESULT_CODE_EMPTY_DATA;
        }
        return $results;
    } // EOF syncAmountsByUsername

    /**
     * Check if field exist
     * @param string $key
     * @param mixed $value
     *
     * @return boolean
     */
    public function isFieldExist($key=null,$value=null)
    {
        if(! is_null($key) && !is_null($value)){

            $this->db->from($this->tableName)
                ->where($key,$value);

            return $this->runExistsResult();
        }
        return false;
    }

    /**
     * The Adjusted Deposits / Game Totals List
     *
     * @param array $request
     * @param array $permissions
     * @param boolean $is_export
     *
     * @example The example source-code for source of $permissions and $is_export.
     * <code>
     *  $is_export = true;
     *  $permissions=$this->getContactPermissions();
     *
     *  $funcName='player_analysis_report';
     *  $callerType=Queue_result::CALLER_TYPE_SYSTEM;
     *  $caller=0;
     *  $state='';
     *
     *  $extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];
     *
     *  $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
     *
     * </code>
     * @return void
     */
    public function dataTablesList($request, $permissions, $is_export = false){
        $this->load->model(['wallet_model','operatorglobalsettings']);

        # START DEFINE COLUMNS #################################################################################################################################################
        $i = 0;
        $columns = [];

        // params: id, lang(Edit), id, lang(Delete),
        $action_html_format =<<<EOF
<div class="btn btn-default btn-scooter btn-xs btn_edit" data-data_id="%s">
<textarea class="hide json_data">
%s
</textarea>
<i class="glyphicon glyphicon-pencil" data-placement="top" data-toggle="tooltip"></i>
%s
</div>
<div class="btn btn-default btn-danger btn-xs btn_delete" data-data_id="%s" >
<i class="glyphicon glyphicon-remove" data-placement="top" data-toggle="tooltip"></i>
%s
</div>
EOF;

        if(!$is_export){
            if( ! empty($permissions['modified_adjusted_deposits_game_totals'] ) ){
                $columns[] = array(
                    'dt' => $i++,
                    'alias' => 'id',
                    'select' => $this->tableName. '.id',
                    'name' => lang('sys.action'),
                    'formatter' => function($d, $row) use ($is_export, $action_html_format){
                        $row_json = json_encode($row);
                        $formated =  sprintf($action_html_format, $d, $row_json, lang('Edit'), $d, lang('Delete'));
                        return $formated;
                    },
                );
            }
        }

        $columns[] = array(
            'alias' => 'id',
            'select' => $this->tableName. '.id',
            'name' => lang('ID'),
            'formatter' => function($d, $row) use ($is_export){
                return $d;
            },
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'created_at',
            'select' => $this->tableName. '.created_at',
            'name' => lang('Created At'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'updated_at',
            'select' => $this->tableName. '.updated_at',
            'name' => lang('Updated At'),
        );

        $_this = $this;
        $columns[] = array(
            'dt' => $i++,
            'alias' => 'player_username',
            'select' => $this->tableName. '.player_username',
            'name' => lang('Username'),
            'formatter' => function($d, $row) use ($is_export, &$_this) {
                $_this->load->model('player_model');
                $player_id = $_this->player_model->getPlayerIdByUsername($d);
                if($is_export || empty($player_id)) { // "|| true" 需要「是否有會員存在？」功能
                    $formatted = $d;
                }else{
                    $id = $row['id'];
                    $uri = site_url('player_management/userInformation/'. $player_id);
                    $html = <<<EOF
<a href="$uri" class="view_user_information">
    $d
</a>
EOF;
                    $formatted = $html;
                }
                return $formatted;
            },
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_bet_amount',
            'select' => $this->tableName. '.total_bet_amount',
            'name' => lang('Bet Amount'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_deposit_amount',
            'select' => $this->tableName. '.total_deposit_amount',
            'name' => lang('Deposit Amount'),
        );

        # END DEFINE COLUMNS #################################################################################################################################################

        $table = $this->tableName;
        $joins = array();

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();

        $this->load->library('data_tables');
        $input = $this->data_tables->extra_search($request);

        if (isset($input['username'], $input['search_by'])
            && ! empty($input['username'])
        ){
            if ($input['search_by'] == '1') { // search_by_similar
                $where[] = $this->tableName. ".player_username like ?";
                $values[] = '%'.  $input['username']. '%';
            }else if ($input['search_by'] == '2') { // search_by_exact
                $where[] = $this->tableName. ".player_username = ?";
                $values[] = $input['player_username'];
            }
        }else if (! empty($input['username']) ){
            $where[] = $this->tableName. ".player_username = ?";
            $values[] = $input['username'];
        }


        if (isset($input['is_enabled_date'], $input['date_mode'], $input['start_date'], $input['end_date'])
            && ! empty($input['is_enabled_date'])
        ) {
            if($input['date_mode'] == Player_basic_amount_list::DATE_MODE_CREATED){
                $where[] = $this->tableName. ".created_at BETWEEN ? AND ? ";
            }else if($input['date_mode'] == Player_basic_amount_list::DATE_MODE_UPDATED){
                $where[] = $this->tableName. ".updated_at BETWEEN ? AND ? ";
            }
            $values[] = $input['start_date'];
            $values[] = $input['end_date'];
        }

        if (isset($input['bet_amount_less_equal']) && $input['bet_amount_less_equal'] != '' ){
            $where[] = $this->tableName. ".total_bet_amount <= ?";
            $values[] = $input['bet_amount_less_equal'];
        }
        if (isset($input['bet_amount_greater_equal']) && $input['bet_amount_greater_equal'] != '' ){
            $where[] = $this->tableName. ".total_bet_amount >= ?";
            $values[] = $input['bet_amount_greater_equal'];
        }

        if (isset($input['deposit_amount_less_equal']) && $input['deposit_amount_less_equal'] != '' ){
            $where[] = $this->tableName. ".total_deposit_amount <= ?";
            $values[] = $input['deposit_amount_less_equal'];
        }
        if (isset($input['deposit_amount_greater_equal']) && $input['deposit_amount_greater_equal'] != '' ){
            $where[] = $this->tableName. ".total_deposit_amount >= ?";
            $values[] = $input['deposit_amount_greater_equal'];
        }


        # END PROCESS SEARCH FORM #################################################################################################################################################

        if($is_export){
            $this->data_tables->options['is_export']=true;
                    // $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }
        // $external_order = [['column' => 2, 'dir' => 'asc']];
        $external_order = [];
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, [], [], true, $external_order);

        if($is_export){
            //drop result if export
            return $csv_filename;
        }

        /// When export via queue, the warning appear in the error log.
        // - E_WARNING: Illegal string offset 'last_query'
        // That is why the code had Moved to here.
        $sql = $this->data_tables->last_query;
        $result['last_query'] = $sql;

        return $result;
    } // EOF dataTablesList


}