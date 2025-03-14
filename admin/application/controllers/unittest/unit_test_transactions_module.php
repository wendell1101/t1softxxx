<?php

trait unit_test_transactions_module{

    public function unit_test_transactions_createDepositTransaction(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions', 'sale_order']);
        $row=$this->unit_test_sale_order_helper_search_first_order();
        $playerId=$row['player_id'];

        $flag = Transactions::MANUAL;
        $adminUserId=1;
        $saleOrder=$this->sale_order->getSaleOrderById($row['id']);
        $beforeBalance=null;
        $totalBeforeBalance=null;
        $success=$this->dbtransOnly(function()
                use($saleOrder, $adminUserId, $beforeBalance, $flag, $totalBeforeBalance) {
            $success=!!$this->transactions->createDepositTransaction($saleOrder, $adminUserId, $beforeBalance,
                $flag, $totalBeforeBalance);
            $this->assertFalse($success, 'createDepositTransaction without lock');
            return $success;
        });
        $this->assertFalse($success, 'createDepositTransaction without lock');

        $success=$this->lockAndTransForPlayerBalance($playerId, function()
                use($saleOrder, $adminUserId, $beforeBalance, $flag, $totalBeforeBalance){
            $success=!!$this->transactions->createDepositTransaction($saleOrder, $adminUserId, $beforeBalance,
                $flag, $totalBeforeBalance);
            $this->assertTrue($success, 'createDepositTransaction with lock');
            return $success;
        });
        $this->assertTrue($success, 'createDepositTransaction with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_createDepositTransactionByAgent(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions']);
        //first player
        $this->db->select('playerId')->from('player')->where('deleted_at is null', null, false)->limit(1);
        $row=$this->transactions->runOneRowArray();
        $playerId=$row['playerId'];
        //first agent
        $this->db->select('agent_id')->from('agency_agents')->where('status','active')->limit(1);
        $row=$this->transactions->runOneRowArray();
        $amount=10;
        $agentId=$row['agent_id'];
        $success=$this->dbtransOnly(function()
                use($playerId, $amount, $agentId) {
            $success=!!$this->transactions->createDepositTransactionByAgent($playerId, $amount, $agentId);
            $this->assertFalse($success, 'createDepositTransactionByAgent without lock');
            return $success;
        });
        $this->assertFalse($success, 'createDepositTransactionByAgent without lock');

        $success=$this->lockAndTransForPlayerBalance($playerId, function()
                use($playerId, $amount, $agentId){
            $result=$this->transactions->createDepositTransactionByAgent($playerId, $amount, $agentId);
            $success=!!$result;
            $this->assertTrue($success, 'createDepositTransactionByAgent with lock');
            return $success;
        });
        $this->assertTrue($success, 'createDepositTransactionByAgent with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_createWithdrawTransactionByAgent(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions']);
        //first player
        $this->db->select('playerId')->from('player')->where('deleted_at is null', null, false)->limit(1);
        $row=$this->transactions->runOneRowArray();
        $playerId=$row['playerId'];
        //first agent
        $this->db->select('agent_id')->from('agency_agents')->where('status','active')->limit(1);
        $row=$this->transactions->runOneRowArray();
        $amount=10;
        $agentId=$row['agent_id'];
        $success=$this->dbtransOnly(function()
                use($playerId, $amount, $agentId) {
            $success=!!$this->transactions->createWithdrawTransactionByAgent($playerId, $amount, $agentId);
            $this->assertFalse($success, 'createWithdrawTransactionByAgent without lock');
            return $success;
        });
        $this->assertFalse($success, 'createWithdrawTransactionByAgent without lock');

        $success=$this->lockAndTransForPlayerBalance($playerId, function()
                use($playerId, $amount, $agentId){
            $result=$this->transactions->createWithdrawTransactionByAgent($playerId, $amount, $agentId);
            $success=!!$result;
            $this->assertTrue($success, 'createWithdrawTransactionByAgent with lock');
            return $success;
        });
        $this->assertTrue($success, 'createWithdrawTransactionByAgent with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_depositToAff(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions', 'sale_order']);

        $row=$this->unit_test_affiliate_helper_search_first_affiliate();

        $affId=$row['affiliateId'];
        $amount=10;
        $extraNotes=null;
        $success=$this->dbtransOnly(function()
                use($affId, $amount, $extraNotes) {
            $success=!!$this->transactions->depositToAff($affId, $amount, $extraNotes);
            $this->assertFalse($success, 'depositToAff without lock');
            return $success;
        });
        $this->assertFalse($success, 'depositToAff without lock');

        $success=$this->lockAndTrans(Utils::LOCK_ACTION_AFF_BALANCE, $affId, function()
                use($affId, $amount, $extraNotes){
            $success=!!$this->transactions->depositToAff($affId, $amount, $extraNotes);
            $this->assertTrue($success, 'depositToAff with lock');
            return $success;
        });
        $this->assertTrue($success, 'depositToAff with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_withdrawFromAff(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions', 'sale_order']);

        $row=$this->unit_test_affiliate_helper_search_first_affiliate();

        $affId=$row['affiliateId'];
        $amount=10;
        $extraNotes=null;
        $success=$this->dbtransOnly(function()
                use($affId, $amount, $extraNotes) {
            $success=!!$this->transactions->withdrawFromAff($affId, $amount, $extraNotes);
            $this->assertFalse($success, 'withdrawFromAff without lock');
            return $success;
        });
        $this->assertFalse($success, 'withdrawFromAff without lock');

        $success=$this->lockAndTrans(Utils::LOCK_ACTION_AFF_BALANCE, $affId, function()
                use($affId, $amount, $extraNotes){
            $success=!!$this->transactions->withdrawFromAff($affId, $amount, $extraNotes);
            $this->assertTrue($success, 'withdrawFromAff with lock');
            return $success;
        });
        $this->assertTrue($success, 'withdrawFromAff with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_manualAddBalanceAff(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions', 'sale_order']);

        $row=$this->unit_test_affiliate_helper_search_first_affiliate();

        $affId=$row['affiliateId'];
        $amount=10;
        $extraNotes=null;
        $success=$this->dbtransOnly(function()
                use($affId, $amount, $extraNotes) {
            $success=!!$this->transactions->manualAddBalanceAff($affId, $amount, $extraNotes);
            $this->assertFalse($success, 'manualAddBalanceAff without lock');
            return $success;
        });
        $this->assertFalse($success, 'manualAddBalanceAff without lock');

        $success=$this->lockAndTrans(Utils::LOCK_ACTION_AFF_BALANCE, $affId, function()
                use($affId, $amount, $extraNotes){
            $success=!!$this->transactions->manualAddBalanceAff($affId, $amount, $extraNotes);
            $this->assertTrue($success, 'manualAddBalanceAff with lock');
            return $success;
        });
        $this->assertTrue($success, 'manualAddBalanceAff with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_manualSubtractBalanceAff(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions', 'sale_order']);

        $row=$this->unit_test_affiliate_helper_search_first_affiliate();

        $affId=$row['affiliateId'];
        $amount=10;
        $extraNotes=null;
        $success=$this->dbtransOnly(function()
                use($affId, $amount, $extraNotes) {
            $success=!!$this->transactions->manualSubtractBalanceAff($affId, $amount, $extraNotes);
            $this->assertFalse($success, 'manualSubtractBalanceAff without lock');
            return $success;
        });
        $this->assertFalse($success, 'manualSubtractBalanceAff without lock');

        $success=$this->lockAndTrans(Utils::LOCK_ACTION_AFF_BALANCE, $affId, function()
                use($affId, $amount, $extraNotes){
            $success=!!$this->transactions->manualSubtractBalanceAff($affId, $amount, $extraNotes);
            $this->assertTrue($success, 'manualSubtractBalanceAff with lock');
            return $success;
        });
        $this->assertTrue($success, 'manualSubtractBalanceAff with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_agentTransferBalanceToBindingPlayer(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions']);
        //first player
        $this->db->select('playerId')->from('player')->where('deleted_at is null', null, false)->limit(1);
        $row=$this->transactions->runOneRowArray();
        $playerId=$row['playerId'];
        //first agent
        $this->db->select('agent_id')->from('agency_agents')->where('status','active')->limit(1);
        $row=$this->transactions->runOneRowArray();
        $amount=10;
        $agentId=$row['agent_id'];
        $walletType='main';
        $success=$this->dbtransOnly(function()
                use($playerId, $amount, $agentId, $walletType) {
            $success=$this->transactions->agentTransferBalanceToBindingPlayer($agentId, $playerId, $amount, $walletType);
            $this->assertFalse($success, 'without lock');
            return $success;
        });
        $this->assertFalse($success, 'without lock');

        $success=$this->lockAndTransForPlayerBalanceAndAgencyCredit($playerId, $agentId, function()
                use($playerId, $amount, $agentId, $walletType){
            $result=$this->transactions->agentTransferBalanceToBindingPlayer($agentId, $playerId, $amount, $walletType);
            $success=!!$result;
            $this->assertTrue($success, 'with lock');
            return $success;
        });
        $this->assertTrue($success, 'with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_agentTransferBalanceFromBindingPlayer(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions']);
        //first player
        $this->db->select('playerId')->from('player')->where('deleted_at is null', null, false)->limit(1);
        $row=$this->transactions->runOneRowArray();
        $playerId=$row['playerId'];
        //first agent
        $this->db->select('agent_id')->from('agency_agents')->where('status','active')->limit(1);
        $row=$this->transactions->runOneRowArray();
        $amount=10;
        $agentId=$row['agent_id'];
        $walletType='main';
        $success=$this->dbtransOnly(function()
                use($playerId, $amount, $agentId, $walletType) {
            $success=$this->transactions->agentTransferBalanceFromBindingPlayer($agentId, $playerId, $amount, $walletType);
            $this->assertFalse($success, 'without lock');
            return $success;
        });
        $this->assertFalse($success, 'without lock');

        $success=$this->lockAndTransForPlayerBalanceAndAgencyCredit($playerId, $agentId, function()
                use($playerId, $amount, $agentId, $walletType){
            $result=$this->transactions->agentTransferBalanceFromBindingPlayer($agentId, $playerId, $amount, $walletType);
            $success=!!$result;
            $this->assertTrue($success, 'with lock');
            return $success;
        });
        $this->assertTrue($success, 'with lock');

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_getLastDepositAmount(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions', 'sale_order']);

        //first player
        $this->db->select('playerId')->from('player')->where('deleted_at is null', null, false)->limit(1);
        $row=$this->transactions->runOneRowArray();
        $playerId=$row['playerId'];

        $amount=$this->transactions->getLastDepositAmount($playerId);
        $this->utils->printLastSQL();
        $this->utils->debug_log('getLastDepositAmount', $playerId, $amount);
        $this->assertTrue($amount!==null);

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_getLastDepositDate(){
        $this->utils->printToConsole('start '.__METHOD__);
        $this->load->model(['transactions', 'sale_order']);

        //first player
        $this->db->select('playerId')->from('player')->where('deleted_at is null', null, false)->limit(1);
        $row=$this->transactions->runOneRowArray();
        $playerId=$row['playerId'];

        $date=$this->transactions->getLastDepositDate($playerId);
        $this->utils->printLastSQL();
        $this->utils->debug_log('getLastDepositDate', $playerId, $date);
        $this->assertTrue($date!==null);

        $this->printAssertionSummary();
    }

    public function unit_test_transactions_all(){
        $this->unit_test_transactions_createDepositTransaction();
        $this->unit_test_transactions_createDepositTransactionByAgent();
        $this->unit_test_transactions_createWithdrawTransactionByAgent();
        $this->unit_test_transactions_depositToAff();
        $this->unit_test_transactions_withdrawFromAff();
        $this->unit_test_transactions_manualAddBalanceAff();
        $this->unit_test_transactions_manualSubtractBalanceAff();
        $this->unit_test_transactions_agentTransferBalanceToBindingPlayer();
        $this->unit_test_transactions_agentTransferBalanceFromBindingPlayer();
    }

    public function unit_test_transactions_all_without_agent(){
        $this->unit_test_transactions_createDepositTransaction();
        $this->unit_test_transactions_depositToAff();
        $this->unit_test_transactions_withdrawFromAff();
        $this->unit_test_transactions_manualAddBalanceAff();
        $this->unit_test_transactions_manualSubtractBalanceAff();
    }
}
