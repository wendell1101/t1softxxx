<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/common_seamless_wallet_transactions.php";

class mpoker_seamless_wallet_transactions extends Common_seamless_wallet_transactions
{
    public $tableName = "mpoker_seamless_wallet_transactions";
}
