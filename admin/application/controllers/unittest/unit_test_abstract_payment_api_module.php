<?php

trait unit_test_abstract_payment_api_module {

    /**
     * Precations:
     * 1. Set up DUMMY_PAYMENT_API (id=9999) in SBE
     * 2. Set $config['RUNTIME_ENVIRONMENT'] = 'local' in config
     * @return [type] [description]
     */
    public function unit_test_payapi_approveSalesOrder() {
        $verbose = 0;
        $this->utils->printToConsole('Starting ' . __METHOD__);
        $this->load->library([ 'user_agent' ]);
        $this->load->model([ 'sale_order' ]);

        $ut_pay_conf = $this->utils->safeGetArray($this->unit_test_config, 'unit_test_abstract_payment_api');
        // $this->utils->printToConsole("ut_pay_conf");
        // print_r($ut_pay_conf);

        if (empty($ut_pay_conf) || empty($ut_pay_conf['player_id'])) {
            $this->utils->printToConsole('Stopping', 1);
            $this->utils->printToConsole("Please setup following items in unit_test_config_local.php\n  'unit_test_abstract_payment_api' => [\n     'player_id' => (test_player_id)\n   ]\nand try again");
            return;
        }

        $runtime_env = $this->utils->getConfig('RUNTIME_ENVIRONMENT');
        $this->utils->printToConsole("RUNTIME_ENVIRONMENT = {$runtime_env}");

        // if ($runtime_env != 'local') {
        //     $this->utils->printToConsole('Stopping', 1);
        //     $this->utils->printToConsole("Please setup up\n  \$config['RUNTIME_ENVIRONMENT'] = 'local';\nin config_secret_local.php and try again");
        //     return;
        // }

        $test_order = [
            'player_id' => $ut_pay_conf['player_id'] ,
            'amount'    => mt_rand(10100, 29999) / 100 ,
            'time'      => $this->utils->getNowForMysql() ,
            'system_id' => DUMMY_PAYMENT_API ,
            'callback_message_success'  => 'SUCCESS'
        ];

        // Assertion practice
        // $this->assertEquals(1, 1);

        // 1: Load API
        // DUMMY_PAYMENT_API (id=9999, Payment_api_dummy)
        $pay = $this->utils->loadExternalSystemLibObject($test_order['system_id']);
        if (empty($pay)) {
            $this->utils->printToConsole('Stopping', 1);
            $this->utils->printToConsole("Please make sure\n1. DUMMY_PAYMENT_API (ID=9999) is configured in SBE\n2. \$config['RUNTIME_ENVIRONMENT'] = 'local' is set in config file\nthen try again");
            return;
        }
        $pay->unit_test_mode = true;
        $this->assertTrue($pay->unit_test_mode, 'Failing - failed to set Payment_api_dummy->unit_test_mode = true');

        $api_platform_code = $pay->getPlatformCode();
        $api_prefix = $pay->getPrefix();
        $this->assertEquals($api_platform_code, DUMMY_PAYMENT_API);
        $this->assertEquals($api_prefix, 'dummy');

        // 2: Create mock order
        /*
         * $orderId = $api->createSaleOrder($player_id, $deposit_amount, $player_promo_id, $extra_info_order,
         * $sub_wallet_id, $group_level_id, true, $player_deposit_reference_no, $deposit_time, $promo_info);
         */
        $order_id = $pay->createSaleOrder($test_order['player_id'], $test_order['amount'], null, null, null, null, true, null, $test_order['time'], null);
        $this->utils->printToConsole("Mock order created");
        $this->utils->printToConsole("order_id = {$order_id}");
        $this->utils->printToConsole("amount = {$test_order['amount']}");
        $this->utils->printToConsole("player_id = {$test_order['player_id']}");
        if (empty($order_id)) {
            $this->utils->printToConsole('Stopping', 1);
            $this->utils->printToConsole('Error creating sale order');
            return;
        }
        $this->assertTrue(is_numeric($order_id));

        // 3: Load initial order
        $sale_order = $this->sale_order->getSaleOrderById($order_id);
        $this->assertNotEmpty($sale_order);
        if ($verbose) {
            $this->utils->printToConsole("sale_order before");
            print_r($sale_order);
        }

        // Assertions for initial sale_order
        $this->assertStrEquals($sale_order->player_id, $test_order['player_id']);
        $this->assertStrEquals($sale_order->system_id, $test_order['system_id']);
        $this->assertStrEquals($sale_order->amount, $test_order['amount']);
        $this->assertStrEquals($sale_order->status, Sale_order::STATUS_PROCESSING);
        $this->assertNotEmpty($sale_order->secure_id);

        // 4: Mock callback from server
        $res_cbs = $pay->callbackFromServer($order_id, []);
        /**
         * Expected return:
         * [
         *     'success'     => '' ,
         *     'next_url'    => '' ,
         *     'message'     => SUCCESS
         * ]
         */
        $this->utils->printToConsole("callbackFromServer() return");
        print_r($res_cbs);
        $this->assertStrEquals($test_order['callback_message_success'], $res_cbs['message']);

        // 5: Load order after
        $sale_order_after = $this->sale_order->getSaleOrderById($order_id);
        $this->assertNotEmpty($sale_order_after);
        if ($verbose) {
            $this->utils->printToConsole("sale_order after");
            print_r($sale_order_after);
        }

        $this->assertStrEquals($sale_order_after->status, Sale_order::STATUS_SETTLED);
        $this->assertNotEmpty($sale_order_after->external_order_id);
        $this->assertNotEmpty($sale_order_after->response_result_id);
        $this->assertNotEmpty($sale_order_after->processed_by);
        $this->assertNotEmpty($sale_order_after->process_time);
        $this->assertNotEmpty($sale_order_after->processed_approved_time);
        $this->assertNotEmpty($sale_order_after->detail_status);

        // finish: Mandated
        $this->printAssertionSummary();
    }

    public function unit_test_abstract_payment_api_all() {
        $this->unit_test_payapi_approveSalesOrder();
    }

}
