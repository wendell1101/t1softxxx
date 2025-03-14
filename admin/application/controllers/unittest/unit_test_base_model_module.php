<?php

trait unit_test_base_model_module{

    public function unit_test_base_model_reachedIpLimitHourlyBy(){
        $this->utils->printToConsole('start '.__METHOD__);
        $ip='192.168.1.1';
        $type='register';
        $this->load->model(['player_model']);

        $this->assertTrue($this->player_model->clearIpLimitBy($ip));

        //first time
        $reached=$this->player_model->reachedIpLimitHourlyBy($ip, $type, $err);
        $this->assertNotNull($reached);
        $this->assertFalse($reached);

        for ($i=0; $i < 9; $i++) {
            $reached=$this->player_model->reachedIpLimitHourlyBy($ip, $type, $err);
            $this->assertNotNull($reached);
            $this->assertFalse($reached);
        }

        $reached=$this->player_model->reachedIpLimitHourlyBy($ip, $type, $err);
        $this->assertNotNull($reached);
        $this->assertTrue($reached);

        $ipLimit=$this->player_model->readIpLimitBy($ip);
        $this->utils->debug_log('read ip limit', $ipLimit);

        $hourKey=date('YmdH');
        $this->assertNotEmpty($ipLimit);
        $this->assertEquals(11, $ipLimit['register'][$hourKey]);
        $this->printAssertionSummary();

    }

    public function unit_test_base_model_queryExplainRows(){
        $this->utils->printToConsole('start '.__METHOD__);

        $this->load->model(['player_model']);
        $sql='select * from game_logs where flag=1';
        $params=[];
        $cnt=$this->player_model->queryExplainRows($sql, $params, 'game_logs');
        $this->utils->debug_log('explain rows', $cnt);
        $this->assertTrue($cnt>0);

        $this->printAssertionSummary();
    }

    public function unit_test_base_model_queryPlanSelectorByExplain(){
        $this->utils->printToConsole('start '.__METHOD__);

        $config=$this->unit_test_config['unit_test_base_model_queryPlanSelectorByExplain'];

        $this->load->model(['player_model']);
        // $sql='select * from game_logs where flag=1';
        $params=[$config['from'], $config['to']];
        $sqlPlans=[
            [
            'sql'=>'select * from game_logs use index(idx_updated_at) where end_at>? and end_at<?',
            'params'=>$params,
            'mainTable'=>'game_logs',
            ],
            [
            'sql'=>'select * from game_logs use index(idx_end_at) where end_at>? and end_at<?',
            'params'=>$params,
            'mainTable'=>'game_logs',
            ],
        ];
        $result=$this->player_model->queryPlanSelectorByExplain($sqlPlans);
        $this->utils->debug_log('queryPlanSelectorByExplain result', $result);

        $this->assertNotNull($result);
        $this->assertEquals($sqlPlans[1]['sql'], $result['sql']);

        $this->printAssertionSummary();
    }


    public function unit_test_base_model_all(){
        $this->unit_test_base_model_reachedIpLimitHourlyBy();
        $this->unit_test_base_model_queryExplainRows();
    }

    protected function unit_test_sale_order_helper_search_first_order(){
        //search available order
        $this->db->select('id, status, player_id, payment_account_id')->from('sale_orders')->limit(1);
        $row=$this->sale_order->runOneRowArray();
        $this->assertNotEmpty($row, 'not found any order');
        return $row;
    }

    protected function unit_test_sale_order_helper_search_order_status($id){
        $this->db->select('id, status')->from('sale_orders')->where('id', $id);
        $newRow=$this->sale_order->runOneRowArray();
        return intval($newRow['status']);
    }

    protected function unit_test_affiliate_helper_search_first_affiliate(){
        $this->db->select('affiliateId, username')->from('affiliates')->limit(1);
        $row=$this->sale_order->runOneRowArray();
        $this->assertNotEmpty($row, 'not found any affiliate');
        return $row;
    }

    public function unit_test_base_model_dbtransOnly(){
        //success
        $sql='update game set game="'.random_string().'" where gameId=1';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->dbtransOnly(function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            return $rlt!==false;
        });
        $this->utils->info_log('success result', $success, 'trans_status', $this->db->trans_status());
        $this->assertTrue($success);

        //return false
        $sql='update game set game="'.random_string().'" where gameId=1';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->dbtransOnly(function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            return false;
        });
        $this->utils->info_log('return false result', $success, 'trans_status', $this->db->trans_status());
        $this->assertFalse($success);

        //throw exception
        $sql='update game set game="'.random_string().'" where gameId=1';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->dbtransOnly(function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            throw new Exception('test');
            return true;
        });
        $this->utils->info_log('throw exception result', $success, 'trans_status', $this->db->trans_status());
        $this->assertFalse($success);

        //wrong sql
        $sql='wrong sql';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->dbtransOnly(function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            return $rlt!==false;
        });
        $this->utils->info_log('wrong sql result', $success, 'trans_status', $this->db->trans_status());
        $this->assertFalse($success);

        //success again
        $sql='update game set game="'.random_string().'" where gameId=1';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->dbtransOnly(function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            return $rlt!==false;
        });
        $this->utils->info_log('success again result', $success, 'trans_status', $this->db->trans_status());
        $this->assertTrue($success);

    }

    public function unit_test_base_model_lockAndTrans(){
        //success
        $sql='update game set game="'.random_string().'" where gameId=1';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, '-1', function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            return $rlt!==false;
        });
        $this->utils->info_log('success result', $success, 'trans_status', $this->db->trans_status());
        $this->assertTrue($success);

        //return false
        $sql='update game set game="'.random_string().'" where gameId=1';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, '-1', function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            return false;
        });
        $this->utils->info_log('return false result', $success, 'trans_status', $this->db->trans_status());
        $this->assertFalse($success);

        //throw exception
        $sql='update game set game="'.random_string().'" where gameId=1';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, '-1', function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            throw new Exception('test');
            return true;
        });
        $this->utils->info_log('throw exception result', $success, 'trans_status', $this->db->trans_status());
        $this->assertFalse($success);

        //wrong sql
        $sql='wrong sql';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, '-1', function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            return $rlt!==false;
        });
        $this->utils->info_log('wrong sql result', $success, 'trans_status', $this->db->trans_status());
        $this->assertFalse($success);

        //success again
        $sql='update game set game="'.random_string().'" where gameId=1';
        $this->utils->debug_log('TryUpdate', $sql, 'trans_status', $this->db->trans_status());
        $success=$this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, '-1', function() use($sql){
            $rlt=$this->db->query($sql);
            $this->utils->debug_log('run sql, result', $rlt);
            return $rlt!==false;
        });
        $this->utils->info_log('success again result', $success, 'trans_status', $this->db->trans_status());
        $this->assertTrue($success);

    }

}
