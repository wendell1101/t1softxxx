<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Failed_seamless_transactions extends BaseModel {

    const ADJUSTMENT_TYPE_INCREASE = 'increase';
    const ADJUSTMENT_TYPE_DECREASE = 'decrease';

    function __construct() {
        parent::__construct();
    }

    public $tableName = "failed_remote_common_seamless_transactions";

    public function insertTransaction($params, $custom_table = null) {
        if(!empty($custom_table)){
            $this->tableName = $custom_table;
        }

        $data = [
            'transaction_id'=>isset($params['transaction_id']) ?$params['transaction_id']:null,
            'round_id'=>isset($params['round_id']) ?$params['round_id']:null,
            'external_game_id'=>isset($params['external_game_id']) ?$params['external_game_id']:null,
            'player_id'=>isset($params['player_id']) ?$params['player_id']:null,
            'game_username'=>isset($params['game_username']) ?$params['game_username']:null,
            'amount'=>isset($params['amount']) ?$params['amount']:null,
            'balance_adjustment_type'=>isset($params['balance_adjustment_type']) ?$params['balance_adjustment_type']:null,
            'action'=>isset($params['action']) ?$params['action']:null,
            'game_platform_id'=>isset($params['game_platform_id']) ?$params['game_platform_id']:null,
            'transaction_raw_data'=>isset($params['transaction_raw_data']) ?$params['transaction_raw_data']:null,
            'remote_raw_data'=>isset($params['remote_raw_data']) ?$params['remote_raw_data']:null,
            'external_uniqueid'=>isset($params['external_uniqueid']) ?$params['external_uniqueid']:null,
            'remote_wallet_status'=>isset($params['remote_wallet_status']) ?$params['remote_wallet_status']:null,
            'transaction_date'=>isset($params['transaction_date']) ?$params['transaction_date']:null,
            'created_at'=>isset($params['created_at']) ?$params['created_at']:null,
            'updated_at'=>isset($params['updated_at']) ?$params['updated_at']:null,
            'request_id'=>isset($params['request_id']) ?$params['request_id']:null,
            'headers'=>isset($params['headers']) ?$params['headers']:null,
            'full_url'=>isset($params['full_url']) ?$params['full_url']:null,
        ];

        $inserted = $this->insertIgnoreRow($params);

        return $inserted;
    }
}