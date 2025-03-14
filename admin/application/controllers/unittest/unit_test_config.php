<?php

$config=[
    'unit_test_base_model_queryPlanSelectorByExplain'=>[
        'from'=>'2019-07-02 00:00:00',
        'to'=>'2019-07-02 23:59:59',
    ] ,
    'unit_test_player_oauth2'=>[
        'client_name'=>'T1SBE-player',
        'client_secret'=>'T1SBE-rocks',
        'test_player'=>'test002',
        'test_password'=>'123456',
        'api_url'=>'http://player.og.local/playerapi',
    ],
    // sample config for unit_test_abstract_payment_api_module
    // 	'unit_test_abstract_payment_api'	=> [
    //    'player_id'	=> 112
    //	]
];

if (file_exists(dirname(__FILE__) . '/unit_test_config_local.php')) {
    require_once dirname(__FILE__) . '/unit_test_config_local.php';
}

