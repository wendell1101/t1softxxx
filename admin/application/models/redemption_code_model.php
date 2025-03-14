<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';
/**
 * Redemption_code_model
 * $config['enable_redemption_code_system']
 * $config['enable_redemption_code_system_in_playercenter']
 * $config['redemption_code_promo_cms_id'] 
 */
class Redemption_code_model extends BaseModel
{
    protected $table_for_category = 'redemption_code_category';
    protected $table_for_records = 'redemption_code';

    const CATEGORY_STATUS_ACTIVATED = 1;
    const CATEGORY_STATUS_DEACTIVATE = 2;
    const CATEGORY_NORMAL_FLAG = 0;
    const CATEGORY_IS_DELETED_FLAG = 1;

    const ITEM_STATUS_ACTIVATED = 1;
    const ITEM_STATUS_DEACTIVATE = 2;

    const CODE_STATUS_UNUSED = 1;
    const CODE_STATUS_USED = 2;
    const CODE_STATUS_EXPIRED = 3;
    const CODE_STATUS_PENDING = 4;

    const IS_DELETE_FLAG = 1;

    public function __construct()
    {
        parent::__construct();
    }

    public function checkRedemptionCodeEnable()
    {
        return $this->utils->getConfig('enable_redemption_code_system');
    }

    /**
     * overview : Insert the redemption code category
     *
     * detail : Insert the redemption code category
     *
     * @param array $data
     * @return int
     */
    public function insertCategory($data)
    {
        $categoryId = $this->insertData($this->table_for_category, $data);
        return $categoryId;
    }
    public function updateCategory($categoryId, $data)
    {
        return $this->db->update($this->table_for_category, $data, array(
            'id' => $categoryId,
        ));
    }
    public function softDeleteCategory($categoryId)
    {

        $this->db->where('id', $categoryId);
        $this->db->where('is_deleted', self::CATEGORY_NORMAL_FLAG);
        $this->db->set('is_deleted', self::CATEGORY_IS_DELETED_FLAG);
        $this->db->set('deleted_on', $this->utils->getNowForMysql());
        return $this->db->update($this->table_for_category);
    }
    public function softClearCodeUnderCategory($categoryId, $realDelete = false)
    {
        if (!$realDelete) {
            $this->db->where('category_id', $categoryId);
            $this->db->where('status', self::CODE_STATUS_UNUSED);
            $this->db->where('is_deleted is null');
            $this->db->set('is_deleted', self::IS_DELETE_FLAG);
            $this->db->set('deleted_on', $this->utils->getNowForMysql());
            $this->db->set('redemption_code', "CONCAT('DEL_', redemption_code)", FALSE);
            return $this->db->update($this->table_for_records);
        } else {
            return $this->realDeleteUnusedCode($categoryId);
        }
    }
    public function realDeleteUnusedCode($categoryId)
    {
        $this->db->where('category_id', $categoryId);
        $this->db->where('status', self::CODE_STATUS_UNUSED);
        return $this->runRealDelete($this->table_for_records);
    }
    public function countCodeUnderCategory($categoryId)
    {
        $this->db->select('id');
        $this->db->where('category_id', $categoryId);
        $this->db->where('is_deleted is null');
        // $this->db->from($this->table_for_records);
        $results = $this->db->count_all_results($this->table_for_records);
        $this->utils->debug_log(__METHOD__, 'valid sql', $this->db->last_query());

        return $results;
    }

    public function getPlayerDuplicateType($playerId, $categoryId, $fromDatetime, $toDatetime)
    {
        $this->db->select('id');
        $this->db->where('player_id', $playerId);
        $this->db->where('category_id', $categoryId);
        $this->db->where('status', self::CODE_STATUS_USED);
        if (!is_null($fromDatetime)) {
            $this->db->where('request_at >=', $fromDatetime);
        }
        if (!is_null($toDatetime)) {
            $this->db->where('request_at <=', $toDatetime);
        }

        $results = $this->db->count_all_results($this->table_for_records);
        // $this->utils->debug_log(__METHOD__, 'valid sql', $this->db->last_query());
        return $results;
    }

    public function getCategoryStatus($categoryId)
    {
        $query = $this->db->get_where($this->table_for_category, array('id' => $categoryId));
        return $this->getOneRowOneField($query, 'status');
    }
    public function getCategory($categoryId, $field = false)
    {
        $query = $this->db->get_where($this->table_for_category, array('id' => $categoryId));
        if ($field) {
            return $this->getOneRowOneField($query,  $field);
        } else {
            // return $query->result_array();
            return $this->getOneRowArray($query);
        }
    }
    public function getAllCategoryTypeName($exclud_deleted_type = false, $had_code = true)
    {
        if ($exclud_deleted_type) {
            $this->db->where('is_deleted <>', self::IS_DELETE_FLAG);
        }
        if ($had_code) {
            $this->db->where('quantity >', 0);
        }
        $query = $this->db->get($this->table_for_category);
        return $query->result_array();
    }
    public function getCategoryExpiration()
    {
    }
    public function checkCategoryNameExist($category_name)
    {
        $this->db->from($this->table_for_category);
        $this->db->where('category_name', $category_name);
        return $this->db->count_all_results();
    }
    public function getItemField($itemId, $field = false)
    {
        $query = $this->db->get_where($this->table_for_records, array('id' => $itemId));
        if ($field) {
            return $this->getOneRowOneField($query,  $field);
        } else {
            // return $query->result_array();
            return $this->getOneRowArray($query);
        }
    }
    public function insertItem($data)
    {
        $categoryId = $this->insertData($this->table_for_records, $data);
        return $categoryId;
    }
    public function updateItem($itemId, $data)
    {
        return $this->db->update($this->table_for_records, $data, array(
            'id' => $itemId,
        ));
    }

    public function setAssignedCode($itemId, $playerId)
    {
        $this->db->update($this->table_for_records, 
        array(
            'player_id' => $playerId,
            'status' => self::CODE_STATUS_PENDING
        )
        , array(
            'id' => $itemId,
            'player_id' => null,
            'status' => self::CODE_STATUS_UNUSED
        ),1);
        $updated_status = $this->db->affected_rows();
        if($updated_status) {
            return $itemId;
        }
        return false;
    }

    public function setUsedCode($itemId, $playerId)
    {
        $this->db->update($this->table_for_records, 
        array(
            'player_id' => $playerId,
            'status' => self::CODE_STATUS_USED
        )
        , array(
            'id' => $itemId,
            'player_id' => null,
            'status' => self::CODE_STATUS_PENDING
        ),1);
        $updated_status = $this->db->affected_rows();
        if($updated_status) {
            return $itemId;
        }
        return false;
    }

    public function releaseAssignedCode($itemId, $playerId)
    {
        $this->db->update($this->table_for_records, 
        array(
            "player_id" => null,
            "request_at" => null,
            "status" => self::CODE_STATUS_UNUSED,
            "promo_cms_id" => null,
        )
        , array(
            'id' => $itemId,
            'player_id' => $playerId,
            'status' => self::CODE_STATUS_PENDING
        ),1);
        $updated_status = $this->db->affected_rows();
        if($updated_status) {
            return $itemId;
        }
        return false;
    }

    public function getDetailsByCode($redemption_code, $status = self::CODE_STATUS_UNUSED, $current_code_id = null)
    {
        return $this->getCodeDetails($redemption_code, null, $current_code_id, $status);
    }

    public function getPlayerPendingCode($redemption_code, $playerId, $current_code_id = null)
    {
        return $this->getCodeDetails($redemption_code, $playerId, $current_code_id, self::CODE_STATUS_PENDING);
    }
    public function getCodeDetails($redemption_code, $playerId=null, $current_code_id=null, $code_status = null){
        $this->db->select('redemption_code.id, redemption_code.redemption_code, redemption_code.player_id');
        $this->db->select('redemption_code.status code_status, redemption_code.promo_cms_id');
        $this->db->select('category.id category_id, category.category_name, category.withdrawal_rules, category.bonus, category.expires_at, category.status category_status');
        $this->db->select('category.valid_forever');
        $this->db->where('redemption_code.redemption_code', $redemption_code);
        $this->db->join('redemption_code_category category', 'category.id = redemption_code.category_id');
        if(!empty($playerId)){
            $this->db->where('redemption_code.player_id', $playerId);
        }
        if(!empty($current_code_id)){
            $this->db->where('redemption_code.id', $current_code_id);
        }
        if(!empty($code_status)) {
            $this->db->where('redemption_code.status', $code_status);
        }
        $this->db->limit(1);
        $qry = $this->db->get($this->table_for_records);
        return $this->getOneRowArray($qry);
    }

    public function checkRedemptionCodeExist($redemption_code)
    {
        $this->db->from($this->table_for_records);
        $this->db->where('redemption_code', $redemption_code);
        return $this->db->count_all_results();
    }

    public function countUsedCode($categoryId)
    {
        $this->db->select('id');
        $this->db->where('category_id', $categoryId);
        $this->db->where('status', self::CODE_STATUS_USED);
        $results = $this->db->count_all_results($this->table_for_records);
        $this->utils->debug_log(__METHOD__, 'valid sql', $this->db->last_query());
        return $results;
    }

    public function getAllCategory($request, $is_export = false)
    {
        $this->load->library(['data_tables', 'permissions']);
        $input = $this->data_tables->extra_search($request);
        $manage_redemption_code_category = $this->permissions->checkPermissions('manage_redemption_code_category') ? TRUE : FALSE;
        $i = 0;

        $columns = array(
            array(
                // 'dt' => $i++,
                'alias' => 'is_deleted',
                'select' => 'category.is_deleted',
                'name'    => lang('redemptionCode.is_deleted'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'id',
                'select' => 'category.id',
                'name'    => lang('redemptionCode.id'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'name',
                'select' => 'category.category_name',
                'name'    => lang('redemptionCode.name'),
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {

                        return $d;
                    } else {

                        if ($row['is_deleted'] == self::CATEGORY_IS_DELETED_FLAG) {
                            return "<del>$d</del>";
                        }

                        return "<a href='/marketing_management/redemptionCodeList?codeType={$row['id']}&codeStatus=1' target='_blank' class='type_name'>$d</a>";
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'quantity',
                'select' => 'category.quantity',
                'name'    => lang('redemptionCode.quantity'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $count = $this->countUsedCode($row['id']);
                    $total = $d ?: 0;
                    $left = $total - $count;
                    if ($is_export) {
                        return lang('redemptionCode.quantity') . ": $total |" . lang('redemptionCode.left_quantity') . ": $left";
                    }
                    return lang('redemptionCode.quantity') . ": $total <br>" . lang('redemptionCode.left_quantity') . ": $left";
                },
            ),
            array(
                // 'dt' => $i++,
                'alias' => 'used_quantity',
                'select' => 'category.quantity',
                'name'    => lang('redemptionCode.used_quantity'),
                'formatter' => function ($d) use ($is_export) {
                    return $d ?: 0;
                },
            ),
            array(
                // 'dt' => $i++,
                'alias' => 'left_quantity',
                'select' => 'category.quantity',
                'name'    => lang('redemptionCode.left_quantity'),
                'formatter' => function ($d) use ($is_export) {
                    return $d ?: 0;
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'bonus',
                'select' => 'category.bonus',
                'name'    => lang('redemptionCode.bonus'),
                'formatter' => function ($d) use ($is_export) {
                    return $d ?: 0;
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'applyLimit',
                'select' => 'category.withdrawal_rules',
                'name'    => lang('redemptionCode.applyLimit'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $withdraw_condition = json_decode($d, true);
                    $bonusApplicationLimitDefineds = [
                        Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE => lang('None'),
                        Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY => lang('Daily'),
                        Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY => lang('Weekly'),
                        Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY => lang('Monthly'),
                        Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY => lang('Yearly'),
                    ];

                    if (array_key_exists('bonusApplicationLimit', $withdraw_condition)) {
                        $bonusApplicationLimit = $withdraw_condition['bonusApplicationLimit'];
                        if ($bonusApplicationLimit['bonusReleaseTypeOptionByNonSuccessionLimitOption'] == 0 || $bonusApplicationLimit['limitCnt'] == 0) {
                            return lang('cms.noLimit');
                        } else {
                            $display_string = '';
                            if ($bonusApplicationLimit['bonusApplicationLimitDateType'] != Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE) {

                                $display_string .= $bonusApplicationLimitDefineds[$bonusApplicationLimit['bonusApplicationLimitDateType']] . '</br>';
                            }

                            $display_string .= lang('cms.withLimit') . ' ' . $bonusApplicationLimit['limitCnt'];
                            return  $display_string;
                        }
                    } else {
                        return lang('cms.noLimit');
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'withdrawal_rules',
                'select' => 'category.withdrawal_rules',
                'name'    => lang('redemptionCode.withdraw_condition'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $withdraw_condition = json_decode($d, true);
                    // withdrawal conditions for bet
                    if (isset($withdraw_condition['withdrawRequirementBettingConditionOption'])) {

                        switch ($withdraw_condition['withdrawRequirementBettingConditionOption']) { //withdrawRequirementBettingConditionOption
                            case Promorules::WITHDRAW_CONDITION_TYPE_BONUS_TIMES:
                                $betting_condition = lang('cms.betAmountCondition2') . ' ' . $withdraw_condition['withdrawReqBonusTimes'];

                                break;
                            case Promorules::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
                                $betting_condition = lang('cms.betAmount') . ' (&#8805;) ' . $withdraw_condition['withdrawReqBetAmount'];

                                break;
                            case Promorules::WITHDRAW_CONDITION_TYPE_NOTHING:
                            default:
                                $betting_condition = lang('cms.no_any_withdraw_condtion');
                                break;
                        }
                    }
                    // if (isset($withdraw_condition['withdrawRequirementDepositConditionOption'])) {

                    //     switch ($withdraw_condition['withdrawRequirementDepositConditionOption']) { //withdrawRequirementDepositConditionOption
                    //         case Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT:
                    //             $deposit_condition = lang('cms.greaterThan') .' '. $withdraw_condition['withdrawReqDepMinLimit'];
                    //             break;
                    //         case Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION:
                    //             $deposit_condition = lang('cms.totalDepAmountSinceRegistration') . '(&#8805;) ' . $withdraw_condition['withdrawReqDepMinLimitSinceRegistration'];
                    //             break;
                    //         case Promorules::DEPOSIT_CONDITION_TYPE_NOTHING:
                    //         default:                                
                    //             $deposit_condition = lang('cms.no_any_deposit_condtion');
                    //             break;
                    //     }
                    // }

                    // return !$is_export ? "<ul> <li>$betting_condition</li> <li>$deposit_condition</li> <ul>" : "$betting_condition / $deposit_condition";
                    return $betting_condition;
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'created_at',
                'select' => 'category.created_at',
                'name'    => lang('redemptionCode.create_at'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'expires_at',
                'select' => 'category.expires_at',
                'name'    => lang('redemptionCode.apply_expire_time'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $valid_forever = $row['valid_forever'];
                    return $row['valid_forever'] == "1" ? lang('redemptionCode.validForever') : $d;
                },
            ),
            array(
                // 'dt' => $i++,
                'alias' => 'valid_forever',
                'select' => 'category.valid_forever',
                'name'    => lang('redemptionCode.validForever'),
            ),
            array(
                'dt' => null, //$i++,
                'alias' => 'allow_duplicate_apply',
                'select' => 'category.id',
                'name'    => lang('redemptionCode.allow_duplicate_apply'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'status',
                'select' => 'category.status',
                'name'    => lang('redemptionCode.status'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $staus_map = [
                        redemption_code_model::CATEGORY_STATUS_ACTIVATED => '<p class="text-success"><i class="glyphicon glyphicon-ok-circle"></i>' . lang('redemptionCode.categoryActive') . '</p>',
                        redemption_code_model::CATEGORY_STATUS_DEACTIVATE => '<p class="text-danger"><i class="glyphicon glyphicon-ban-circle"></i>' . lang('redemptionCode.categoryDeactive') . '</p>',
                    ];
                    if ($is_export) {
                        $staus_map = [
                            redemption_code_model::CATEGORY_STATUS_ACTIVATED => lang('redemptionCode.categoryActive'),
                            redemption_code_model::CATEGORY_STATUS_DEACTIVATE => lang('redemptionCode.categoryDeactive'),
                        ];
                    }
                    return isset($staus_map[$d]) ? $staus_map[$d] : lang('lang.norecyet');
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'notes',
                'select' => 'category.notes',
                'name'    => lang('redemptionCode.note'),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return (!$d) ? lang('lang.norecyet') : $d;
                    } else {
                        return (!$d) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : $d;
                    }
                },
            ),
            // array(
            //     // 'dt' => $i++,
            //     'alias' => 'action_logs',
            //     'select' => 'category.action_logs',
            //     'name'    => lang('redemptionCode.action_logs'),
            //     'formatter' => function ($d) use ($is_export) {
            //         if ($is_export) {
            //             return (!$d) ? lang('lang.norecyet') : $d;
            //         } else {
            //             return (!$d) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : $d;
            //         }
            //     },
            // ),
            array(
                'dt' => ($is_export || !$manage_redemption_code_category) ? null : $i++,
                'alias' => 'actions',
                'select' => 'category.id',
                'name'    => lang('redemptionCode.actions'),
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($row['is_deleted'] == self::CATEGORY_IS_DELETED_FLAG) {
                        return '';
                    }
                    if (!$is_export) {
                        $content = '';
                        $categoryId = $d;
                        $switch_btn_template = '<div class="action-item active-btn" >
													<input type="checkbox" class="switch_checkbox"
															data-on-text="%s"
															data-off-text="%s"
															data-category_id="%s"
															%s
													/>
												</div>';

                        $is_active = ($row['status'] == redemption_code_model::CATEGORY_STATUS_ACTIVATED) ? 'checked' : '';
                        $switch_btn = sprintf(
                            $switch_btn_template,
                            lang('redemptionCode.categoryActive'),
                            lang('redemptionCode.categoryDeactive'),
                            $categoryId,
                            $is_active
                        );

                        $manage_btn_group = '';
                        $generate_redemption_code_btn = '';
                        if (!$is_active) {
                            $edit_btn_template = '<div class="action-item">
                                                    <a class="btn btn-scooter btn-xs editCategoryBtn" href="javascript:void(0);" data-category_id="%s"><i class="glyphicon glyphicon-cog"></i> ' . lang('Edit') . '</a>
                                                  </div>';
                            $edit_btn = sprintf(
                                $edit_btn_template,
                                $categoryId
                            );

                            $delete_btn = '';
                            $delete_btn_template = '<div class="action-item">
                                                        <a class="btn btn-danger btn-xs clearCodeBtn" href="/marketing_management/ClearUnusingCodeByCateId/' . $categoryId . '" data-category_id="%s"><i class="glyphicon glyphicon-remove"></i> ' . lang('redemptionCode.clearCode') . '</a>
                                                    </div>';
                            $delete_btn = sprintf(
                                $delete_btn_template,
                                $categoryId
                            );

                            $delete_type_btn = '';
                            $delete_type_btn_template = '<div class="action-item">
                                                        <a class="btn btn-danger btn-xs deleteTypeBtn" href="/marketing_management/deleteTypeAndClearUnusingCode/' . $categoryId . '" data-category_id="%s"><i class="glyphicon glyphicon-remove"></i> ' . lang('redemptionCode.deleteType') . '</a>
                                                    </div>';
                            $delete_type_btn = sprintf(
                                $delete_type_btn_template,
                                $categoryId
                            );

                            $manage_btn_group = '<hr>' . $edit_btn . $delete_btn . $delete_type_btn;
                        }
                        $generate_redemption_code_btn_template = '<div class="action-item">
                                                                    <a class="btn btn-linkwater btn-xs generateCodeBtn" href="javascript:void(0);" data-category_id="%s"><i class="glyphicon glyphicon-plus"></i> ' . lang('redemptionCode.generateCode') . '</a>
                                                                </div>';
                        $generate_redemption_code_btn = sprintf(
                            $generate_redemption_code_btn_template,
                            $categoryId
                        );

                        $content .= $switch_btn . $generate_redemption_code_btn . $manage_btn_group;

                        return $content;
                    }
                },
            ),
        );

        $table = 'redemption_code_category category';
        $where = [];
        $values = [];
        $joins = [
            // 'redemption_code code'     => 'category.id = code.category_id',
        ];

        $where[] = "category.is_deleted = ?";
        $values[] = self::CATEGORY_NORMAL_FLAG;

        $group_by = [];

        if ($is_export) {
            $this->data_tables->options['is_export'] = true;
            if (empty($csv_filename)) {
                $csv_filename = $this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename'] = $csv_filename;
        }

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

        if ($is_export) {
            //drop result if export
            return $csv_filename;
        }

        return $result;
    }

    public function getRedemptionCodeList($request, $is_export = false)
    {
        $this->load->library('data_tables');
        $this->load->helper(['player_helper']);
        $input = $this->data_tables->extra_search($request);
        $currentTime = $this->utils->getNowForMysql();
        $i = 0;

        $columns = array(
            array(
                // 'dt' => $i++,
                'alias' => 'current_withdrawal_rules',
                'select' => 'redemption_code.current_withdrawal_rules',
                'name'    => lang('redemptionCode.current_withdrawal_rules'),
            ),
            array(
                // 'dt' => $i++,
                'alias' => 'current_bonus',
                'select' => 'redemption_code.current_bonus',
                'name'    => lang('redemptionCode.current_bonus'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'id',
                'select' => 'redemption_code.id',
                'name'    => lang('redemptionCode.id'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'name',
                'select' => 'category.category_name',
                'name'    => lang('redemptionCode.categoryName'),
                'formatter' => function ($d, $row) use ($is_export) {

                    $statusStr = $d;
                    // if ($row['category_status'] == redemption_code_model::CATEGORY_STATUS_DEACTIVATE) {
                    //     if($is_export){

                    //         $statusStr .= ' (' . lang('redemptionCode.categoryDeactive') . ')';
                    //     } else {
                    //         $statusStr .= '</br><p class="text-danger"><i class="glyphicon glyphicon-ban-circle"></i>'.lang('redemptionCode.categoryDeactive'). '</p>';
                    //     }
                    // }
                    return $statusStr;
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'redemption_code',
                'select' => 'redemption_code.redemption_code',
                'name'    => lang('redemptionCode.redemptionCode'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'bonus',
                'select' => 'category.bonus',
                'name'    => lang('redemptionCode.bonus'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $bonus = $d;
                    if ($row['status'] == self::CODE_STATUS_USED) {
                        $bonus = $row['current_bonus'];
                    }
                    return $bonus ?: 0;
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'withdrawal_rules',
                'select' => 'category.withdrawal_rules',
                'name'    => lang('redemptionCode.withdraw_condition'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $_withdraw_condition = $d;
                    if ($row['status'] == self::CODE_STATUS_USED) {
                        $_withdraw_condition = $row['current_withdrawal_rules'];
                    }
                    $withdraw_condition = json_decode($_withdraw_condition, true);
                    // withdrawal conditions for bet
                    if (isset($withdraw_condition['withdrawRequirementBettingConditionOption'])) {

                        switch ($withdraw_condition['withdrawRequirementBettingConditionOption']) { //withdrawRequirementBettingConditionOption
                            case Promorules::WITHDRAW_CONDITION_TYPE_BONUS_TIMES:
                                $betting_condition = lang('cms.betAmountCondition2') . ' ' . $withdraw_condition['withdrawReqBonusTimes'];

                                break;
                            case Promorules::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
                                if ($is_export) {
                                    $betting_condition = lang('cms.betAmount') . ' >= ' . $withdraw_condition['withdrawReqBetAmount'];
                                } else {

                                    $betting_condition = '(&#8805;) ' . lang('cms.betAmount') . ' ' . $withdraw_condition['withdrawReqBetAmount'];
                                }

                                break;
                            case Promorules::WITHDRAW_CONDITION_TYPE_NOTHING:
                            default:
                                $betting_condition = lang('cms.no_any_withdraw_condtion');
                                break;
                        }
                    }
                    // if (isset($withdraw_condition['withdrawRequirementDepositConditionOption'])) {

                    //     switch ($withdraw_condition['withdrawRequirementDepositConditionOption']) { //withdrawRequirementDepositConditionOption
                    //         case Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT:
                    //             $deposit_condition = lang('cms.greaterThan') .' '. $withdraw_condition['withdrawReqDepMinLimit'];
                    //             break;
                    //         case Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION:
                    //             $deposit_condition = lang('cms.totalDepAmountSinceRegistration') . '(&#8805;) ' . $withdraw_condition['withdrawReqDepMinLimitSinceRegistration'];
                    //             break;
                    //         case Promorules::DEPOSIT_CONDITION_TYPE_NOTHING:
                    //         default:                                
                    //             $deposit_condition = lang('cms.no_any_deposit_condtion');
                    //             break;
                    //     }
                    // }

                    // return !$is_export ? "<ul> <li>$betting_condition</li> <li>$deposit_condition</li> <ul>" : "$betting_condition / $deposit_condition";
                    return $betting_condition;
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'created_at',
                'select' => 'redemption_code.created_at',
                'name'    => lang('redemptionCode.create_at'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'expires_at',
                'select' => 'category.expires_at',
                'name'    => lang('redemptionCode.apply_expire_time'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $valid_forever = $row['valid_forever'];
                    return $row['valid_forever'] == "1" ? lang('redemptionCode.validForever') : $d;
                },
            ),
            array(
                // 'dt' => $i++,
                'alias' => 'valid_forever',
                'select' => 'category.valid_forever',
                'name'    => lang('redemptionCode.validForever'),
            ),
            array(
                // 'dt' => $i++,
                'alias' => 'player_id',
                'select' => 'redemption_code.player_id',
                'name'    => lang('player id'),
                // 'formatter' => function ($d) use ($is_export) {
                //     return $d ?: '-';
                // },
            ),
            array(
                'dt' => $i++,
                'alias' => 'username',
                'select' => 'player.username',
                'name'    => lang('player.01'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $col_content = '-';
                    if (!empty($d) && !empty($row['player_id'])) {
                        $tag_content = player_tagged_list($row['player_id'], $is_export);
                        if ($is_export) {
                            $col_content = "$d ($tag_content)";
                        } else {
                            $_player_href = '<a href="%s" target="_blank">%s</a>';
                            $player_href = sprintf($_player_href, site_url('/player_management/userInformation/' . $row['player_id']), $d);
                            $col_content = "$player_href <br><br> $tag_content";
                        }
                    }
                    return $col_content;
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'apply_at',
                'select' => 'redemption_code.request_at',
                'name'    => lang('redemptionCode.redemptionTime'),
                'formatter' => function ($d) use ($is_export) {
                    return $d ?: '-';
                },
            ),
            array(
                // 'dt' => $i++,
                'alias' => 'category_status',
                'select' => 'category.status',
                'name'    => lang('redemptionCode.status'),
                // 'formatter' => function ($d, $row) use ($is_export) {
                //     $staus_map = [
                //         redemption_code_model::CATEGORY_STATUS_ACTIVATED => lang('redemptionCode.categoryActive'),
                //         redemption_code_model::CATEGORY_STATUS_DEACTIVATE => lang('redemptionCode.categoryDeactive'),
                //     ];
                //     return isset($staus_map[$d]) ? $staus_map[$d] : lang('lang.norecyet');
                // },
            ),
            array(
                'dt' => $i++,
                'alias' => 'status',
                'select' => 'redemption_code.status',
                'name'    => lang('redemptionCode.status'),
                'formatter' => function ($d, $row) use ($is_export, $currentTime) {
                    $currentStatus = $d;
                    $valid_forever = $row['valid_forever'];
                    $staus_map = [
                        redemption_code_model::CODE_STATUS_UNUSED => lang('redemptionCode.codeUnused'),
                        redemption_code_model::CODE_STATUS_USED => lang('redemptionCode.codeUsed'),
                        redemption_code_model::CODE_STATUS_EXPIRED => lang('redemptionCode.codeExpired')
                    ];
                    if ($currentStatus != redemption_code_model::CODE_STATUS_USED && $valid_forever != 1) {
                        $expAt = $row['expires_at'];
                        if ($currentTime > $expAt) {
                            $currentStatus = redemption_code_model::CODE_STATUS_EXPIRED;
                        }
                    }
                    $statusStr = isset($staus_map[$currentStatus]) ? $staus_map[$currentStatus] : lang('lang.norecyet');
                    // if ($row['category_status'] == redemption_code_model::CATEGORY_STATUS_DEACTIVATE) {
                    //     $statusStr .= ' ( ' . lang('redemptionCode.categoryDeactive') . ' )';
                    // }
                    return $statusStr;
                },
            ),
            array(
            'dt' => $i++,
            'alias' => 'notes',
            'select' => 'redemption_code.notes',
            'name'    => lang('redemptionCode.note'),
            'formatter' => function ($d) use ($is_export) {
                if ($is_export) {
                    return (!$d) ? lang('lang.norecyet') : $d;
                } else {
                    return (!$d) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : $d;
                }
            },
            ),
            // array(
            // 'dt' => $i++,
            // 'alias' => 'action_logs',
            // 'select' => 'redemption_code.action_logs',
            // 'name'    => lang('redemptionCode.action_logs'),
            // 'formatter' => function ($d) use ($is_export) {
            //     if ($is_export) {
            //         return (!$d) ? lang('lang.norecyet') : $d;
            //     } else {
            //         return (!$d) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : $d;
            //     }
            // },
            // ),
            // array(
            // 'dt' => $is_export ? null : $i++,
            // 'alias' => 'actions',
            // 'select' => 'redemption_code.id',
            // 'name'    => lang('redemptionCode.actions'),
            // 'formatter' => function ($d, $row) use ($is_export) {
            //     if (!$is_export) {
            //         $content = '';
            //         $id = $d;
            //         $switch_btn_template = '<div class="action-item active-btn" >
            //         							<input type="checkbox" class="switch_checkbox"
            //         									data-on-text="%s"
            //         									data-off-text="%s"
            //         									data-handle-width="60"
            //         									data-item_id="%s"
            //         									%s
            //         							/>
            //         						</div>';

            //         $is_active = ($row['status'] == redemption_code_model::ITEM_STATUS_ACTIVATED) ? 'checked' : '';
            //         $switch_btn = sprintf(
            //             $switch_btn_template,
            //             lang('redemptionCode.categoryActive'),
            //             lang('redemptionCode.categoryDeactive'),
            //             $id,
            //             $is_active
            //         );

            //         $edit_btn_template = '<div class="action-item">
            //         						<a class="btn btn-scooter btn-xs editCategoryBtn" href="javascript:void(0);" data-item_id="%s">Edit</a>
            //         					  </div>';
            //         $edit_btn = sprintf(
            //             $edit_btn_template,
            //             $id
            //         );
            //         $content .= $switch_btn . $edit_btn;

            //         return $content;
            //     }
            // },
            // ),
        );

        $table = 'redemption_code';
        $where = [];
        $values = [];
        $joins = [
            'redemption_code_category category' => 'category.id = redemption_code.category_id',
            'player' => 'player.playerId = redemption_code.player_id',
            // 'playertag' => 'player.playerId = redemption_code.player_id',
        ];

        $group_by = [];
        $where[] = "redemption_code.is_deleted is null";
        if (isset($input['codeType']) && $input['codeType'] != 'All') {
            $where[]  = "category.id = ?";
            $values[] = (int)$input['codeType'];
        }

        if (isset($input['redemptionCode'])) {
            $where[]  = "redemption_code.redemption_code = ?";
            $values[] = $input['redemptionCode'];
        }

        if (isset($input['username'])) {
            $where[]  = "player.username = ?";
            $values[] = $input['username'];
        }

        if (isset($input['tag_list_included'])) {
            $tag_list = $input['tag_list_included'];
            $is_include_notag = null;
            if (is_array($tag_list)) {
                $notag = array_search('notag', $tag_list);
                if ($notag !== false) {
                    unset($tag_list[$notag]);
                    $is_include_notag = true;
                } else {
                    $is_include_notag = false;
                }
            } elseif ($tag_list == 'notag') {
                $tag_list = null;
                $is_include_notag = true;
            }

            $where_fragments = [];
            if ($is_include_notag) {
                $where_fragments[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag)';
            }

            if (!empty($tag_list)) {
                $tagList = is_array($tag_list) ? implode(',', $tag_list) : $tag_list;
                $where_fragments[] =  'player.playerId IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN (' . $tagList . '))';
            }
            if (!empty($where_fragments)) {
                $where_fragments = implode(' OR ', $where_fragments);
                $where[] = "($where_fragments)";
            }
        } // EOF if (isset($input['tag_list_included'])) {...

        if (isset($input['bonus'])) {
            $_where_fragments = "category.bonus %s ?";
            $bonusRange = isset($input['bonusRange']) ? $input['bonusRange'] : 'equalTo';

            switch ($bonusRange) {
                case 'greaterThanOrEqualTo':
                    $where_fragments = sprintf($_where_fragments, '>=');
                    break;

                case 'lessThanOrEqualTo':
                    $where_fragments = sprintf($_where_fragments, '<=');
                    break;

                case 'lessThan':
                    $where_fragments = sprintf($_where_fragments, '<');
                    break;

                case 'greaterThan':
                    $where_fragments = sprintf($_where_fragments, '>');
                    break;

                case 'equalTo':
                default:
                    $where_fragments = sprintf($_where_fragments, '=');
                    break;
            }

            $where[]  = $where_fragments;
            $values[] = $input['bonus'];
        }

        // search_apply_date == on
        $enable_apply_date = (isset($input['search_apply_date']) && $input['search_apply_date'] == '1');
        if ($enable_apply_date && isset($input['apply_date_from'], $input['apply_date_to'])) {

            // $input['last_login_date_from'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['last_login_date_from']);
            // $input['last_login_date_to'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['last_login_date_to']);

            $where[] = "redemption_code.request_at >= ?";
            $values[] = $input['apply_date_from'];
            $where[] = "redemption_code.request_at <= ?";
            $values[] = $input['apply_date_to'];
        }

        // search_create_date == on
        $enable_create_date = (isset($input['search_create_date']) && $input['search_create_date'] == '1');
        if ($enable_create_date && isset($input['create_date_from'], $input['create_date_to'])) {

            // $input['last_login_date_from'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['last_login_date_from']);
            // $input['last_login_date_to'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['last_login_date_to']);

            $where[] = "redemption_code.created_at >= ?";
            $values[] = $input['create_date_from'];
            $where[] = "redemption_code.created_at <= ?";
            $values[] = $input['create_date_to'];
        }

        if (isset($input['codeStatus']) && $input['codeStatus'] != 'All') {
            switch ($input['codeStatus']) {
                    // redemption_code_category.expires_at
                case self::CODE_STATUS_UNUSED:
                    $where[] = "redemption_code.status = ?";
                    $values[] = $input['codeStatus'];
                    $where[] = "(valid_forever = 1 OR (category.expires_at is not null AND category.expires_at >= ?))";
                    $values[] = $currentTime;
                    break;

                case self::CODE_STATUS_USED:
                    $where[] = "redemption_code.status = ?";
                    $values[] = $input['codeStatus'];
                    break;

                case self::CODE_STATUS_EXPIRED:
                    $where[] = "redemption_code.status != ? ";
                    $values[] = self::CODE_STATUS_USED;
                    $where[] = "(category.expires_at is not null AND category.expires_at < ?)";
                    $values[] = $currentTime;
                    break;
            }
        }


        if ($is_export) {
            $this->data_tables->options['is_export'] = true;
            if (empty($csv_filename)) {
                $csv_filename = $this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename'] = $csv_filename;
        }

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

        if ($is_export) {
            //drop result if export
            return $csv_filename;
        }

        return $result;
    }
}
