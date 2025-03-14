<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * middle_exchange_rate_log
 *
 */
class Middle_exchange_rate_log extends BaseModel {
    protected $tableName = 'middle_exchange_rate_log';

    public function __construct() {
        parent::__construct();
    }


    /**
     * Add a record
     *
     * @param array $params the fields of the table,"dispatch_withdrawal_definition".
     * @return void
     */
    public function add($params, $db = null) {

        $nowForMysql = $this->utils->getNowForMysql();
        $data['created_at'] = $nowForMysql;
        $data['updated_at'] = $nowForMysql;
        $data = array_merge($data, $params);
        return $this->insertRow($data, $db);
    } // EOF add

    /**
     * Update record by id
     *
     * @param integer $id
     * @param array $data The fields for update.
     * @return boolean|integer The affected_rows.
     */
    public function update($id, $data = array() ) {
        $nowForMysql = $this->utils->getNowForMysql();
        $data['updated_at'] = $nowForMysql;
        return $this->updateRow($id, $data);
    }// EOF update

    /**
     * Delete a record by id(P.K.)
     *
     * @param integer $id The id field.
     * @return boolean Return true means delete the record completed else false means failed.
     */
    public function delete($id){
        $this->db->where('id', $id);
        return $this->runRealDelete($this->tableName);
    }// EOF delete

    /**
     * Get a record by id(P.K.)
     *
     * @param integer $id The id field.
     * @return array The field-value of the record.
     */
    public function getDetailById($id) {
        $this->db->select('*')
                ->from($this->tableName)
                ->where('id', $id);

        $result = $this->runOneRowArray();

        return $result;
    }// EOF getDetailById


    /**
     * Get the rows for withdrawal_risk_api_module::processPreChecker()
     *
     * @param boolean $getEnabledOnly filter the inactived rows.
     * @param string $order_by_field The field name.
     * @param string $order_by order asc/desc.
     * @return array The rows.
     */
    public function getDetailList($getEnabledOnly = true, $order_by_field='dispatch_order', $order_by ='asc'){
        $this->db->select('*')
                ->from($this->tableName);
        if($getEnabledOnly){
            $this->db->where('status', BaseModel::DB_TRUE);
        }

        $this->db->order_by($order_by_field, $order_by);
        $result = $this->runMultipleRowArray();

        return $result;
    }// EOF getDetailList




    /**
     * The dispatch_withdrawal_definition List
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

        $_this = $this;
        // $customStageEnabledList = [];
        // $customStage = $this->utils_buildCustomStageArray($customStageEnabledList);
        // $customWithdrawalProcessingStage = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
        # START DEFINE COLUMNS #################################################################################################################################################
        $i = 0;
        $columns = [];

        $columns[] = array(
            // 'dt' => $i++,
            'alias' => 'id',
            'select' => $this->tableName. '.id',
            // 'name' => lang('ID'),
            'formatter' => function($d, $row) use ($is_export){
                return $d;
            },
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'updated_at',
            'select' => $this->tableName. '.updated_at',
            'name' => lang('merl.updated_at'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'rate',
            'select' => $this->tableName. '.rate',
            'name' => lang('Middle Exchange Rate'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'updated_by',
            'select' => $this->tableName. '.updated_by',
            'name' => lang('merl.updated_by'),
            'formatter' => function($d, $row) use ($is_export, $_this){
                $updatedByUserId = $d;
                return $_this->users->getUsernameById($updatedByUserId);
            },
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'status',
            'select' => $this->tableName. '.status',
            'name' => lang('lang.status'),
            'formatter' => function($d, $row) use ($is_export){
                if($d == self::DB_TRUE){
                    $formatted = lang('lang.activate');
                }else{
                    $formatted = lang('lang.deactivate');
                }
                return $formatted;
            },
        );


        # END DEFINE COLUMNS #################################################################################################################################################

        $table = $this->tableName;
        $joins = array();

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();

        $this->load->library('data_tables');
        $input = $this->data_tables->extra_search($request);
        // if (isset($input['name'])) {
        //     $where[] = $this->tableName. ".name like ?";
        //     $values[] = $input['name'];
        // }
        # END PROCESS SEARCH FORM #################################################################################################################################################

        if($is_export){
            $this->data_tables->options['is_export']=true;
                    // $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }

        $external_order = [['column' => 0, 'dir' => 'desc']];

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, [], [], true, $external_order);

        if($is_export){
                    //drop result if export
            return $csv_filename;
        }


        return $result;
    } // EOF list



}

///END OF FILE////////