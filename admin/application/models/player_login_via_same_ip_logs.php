<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 */
class Player_login_via_same_ip_logs extends BaseModel {
    private $tableName = 'player_login_via_same_ip_logs';

    const _operator_setting_name4detected_tag_id = 'detected_tag_id_in_view_player_login_via_same_ip';

    public function __construct() {
        parent::__construct();
    }

    public function get($id, $field = 'id') {
        $this->db->where($field, $id);
        $qry = $this->db->get($this->tableName);
        return $this->getMultipleRowArray($qry);
	} // EOF get

    public function getList($id_list = [], $field = 'id') {
        if( ! empty($id_list) ){
            $this->db->where_in($field, $id_list);
            $qry = $this->db->get($this->tableName);
            return $this->getMultipleRowArray($qry);
        }else{
            return [];
        }
	} // EOF getList

    /**
     * Create a data
     *
     * @param array $data The field-value array.
     * @return integer The inseted id.
     */
    public function create($data = []){
        return $this->insertData($this->tableName, $data);
    } // EOF create

    /**
     * Update Extra Applied
     *
     * @param integer $id The P.K.
     * @param array $data The field-value array.
     * @return bool The updated result, If it's true, it means the update is success, otherwise it fails.
     */
    public function update($id, $data){
        $result = $this->db->update($this->tableName, $data, ['id' => $id]);
        return ($result) ? TRUE : FALSE;
    } // EOF update


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

        # START DEFINE COLUMNS #################################################################################################################################################
        $i = 0;
        $columns = [];

        $columns[] = array(
            // 'dt' => $i++,
            'alias' => 'id',
            'select' => $this->tableName. '.id',
            'name' => lang('ID'),
            'formatter' => function($d, $row) use ($is_export){
                return $d;
            },
        );
        $columns[] = array(
            // 'dt' => $i++,
            'alias' => 'player_id',
            'select' => $this->tableName. '.player_id',
            // 'name' => lang('Dispatch Order'),
            // 'formatter' => function($d, $row) use ($is_export){
            //     return $d;
            // },
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'ip',
            'select' => $this->tableName. '.ip',
            'name' => lang('view_player_login_via_same_ip.ip'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'create_at',
            'select' => $this->tableName. '.create_at',
            'name' => lang('view_player_login_via_same_ip.create_at'),
        );


        $columns[] = array(
            'dt' => $i++,
            'alias' => 'Username',
            'select' => $this->tableName. '.username',
            'name' => lang('view_player_login_via_same_ip.username'),
            'formatter' => function($d, $row) use ($is_export){
                if($is_export) {
                    $formatted = $d;
                }else{
                    $id = $row['id'];
                    $uri = site_url('player_management/userInformation/'.$row['player_id']);
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
            'alias' => 'logged_in_at',
            'select' => $this->tableName. '.logged_in_at',
            'name' => lang('view_player_login_via_same_ip.logged_in_at'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'login_result',
            'select' => $this->tableName. '.login_result',
            'name' => lang('view_player_login_via_same_ip.login_result'),
            'formatter' => function($d, $row) use ($is_export){
                if($d == 1){
                    $d = lang('Success');
                }else if($d == 0){
                    $d = lang('Failed');
                }
                return $d;
            },
        );

//         $columns[] = array(
//             'dt' => $i++,
//             'alias' => 'action',
//             'select' => $this->tableName. '.id',
//             'name' => lang('lang.action'),
//             'formatter' => function($d, $row) use ($is_export){
//                 $formatted = '';
//                 $lang4delete = lang('cms.delete');
//                 $lang4edit = lang('cms.edit');
//                 $id = $d;
//                 // @todo OGP-18088, HTML script should moved into the js file.
//                 $html = <<<EOF
// <span tabindex="0" data-toggle="tooltip" title="$lang4edit"  data-placement="top">
//     <button type="button"class="btn btn-default btn-xs editWithdrawalDefinition" >
//         <span class="glyphicon glyphicon-edit" data-detail-id="$id" >
//         </span>
//     </button>
// </span>
// <span tabindex="0" data-toggle="tooltip" title="$lang4delete"  data-placement="top">
//     <button type="button" class="btn btn-default btn-xs deleteWithdrawalDefinition">
//         <span class="glyphicon glyphicon-trash" data-detail-id="$id" >
//         </span>
//     </button>
// </span>
// EOF;
//                 $formatted .= $html;
//                 return $formatted;
//             },
//         );
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
                $where[] = $this->tableName. ".username like ?";
                $values[] = '%'.  $input['username']. '%';
            }else if ($input['search_by'] == '2') { // search_by_exact
                $where[] = $this->tableName. ".username = ?";
                $values[] = $input['username'];
            }
        }

        if (isset($input['created_at_enabled_date'], $input['created_at_date_from'], $input['created_at_date_to'])
            && ! empty($input['created_at_enabled_date'])
        ) {
            $where[] = $this->tableName. ".create_at BETWEEN ? AND ? ";
            $values[] = $input['created_at_date_from'];
            $values[] = $input['created_at_date_to'];
        }

        if (isset($input['logged_in_at_enabled_date'], $input['logged_in_at_date_from'], $input['logged_in_at_date_to'])
            && ! empty($input['logged_in_at_enabled_date'])
        ) {
            $where[] = $this->tableName. ".logged_in_at BETWEEN ? AND ? ";
            $values[] = $input['logged_in_at_date_from'];
            $values[] = $input['logged_in_at_date_to'];
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
    } // EOF list


    public function getTagIdByTagNameDetectedOfConfig(){
		$this->load->model(['player_model']);
		$defaultTagId = 0;
		$moniter_player_login_via_same_ip = $this->utils->getConfig('moniter_player_login_via_same_ip');
		$tag_name_detected = $moniter_player_login_via_same_ip['tag_name_detected'];
		$configTagId = $this->player_model->getTagIdByTagName($tag_name_detected);
		if( ! empty($configTagId) ){
			$defaultTagId = $configTagId;
		}
		return $defaultTagId;
	} // EOF getTagIdByTagNameDetectedOfConfig

}
