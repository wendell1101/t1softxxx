<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_pgsoft_seamless_game_logs_20210121 extends CI_Migration
{
    private $tableName = "pgsoft_seamless_game_logs";

    public function up()
    {
        $fields = [
            "id" => [
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ],
            "UserId" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true,                
            ],
            "UserName" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "OrderTime" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "TransGuid" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => false
            ],
            "Stake" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "Winlost" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "TurnOver" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "Currency" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "ProviderId" => [
                "type" => "VARCHAR",
                "constraint" => "16",
                "null" => true
            ],
            "ParentId" => [
                "type" => "VARCHAR",
                "constraint" => "16",
                "null" => true
            ],
            "GameId" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => false
            ],
            "ProductType" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "GameType" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "TableName" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "PlayType" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "ExtraData" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "ModifyDate" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "WinloseDate" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "Status" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "ProviderStatus" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "CancelledStake" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "transaction_status" => [
                "type" => "VARCHAR",
                "constraint" => "16",
                "null" => true
            ],
            "transaction_type" => [
                "type" => "VARCHAR",
                "constraint" => "16",
                "null" => true
            ],
            "start_at" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "end_at" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "before_balance" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "after_balance" => [
                "type" => "DOUBLE",
                "null" => true
            ],

            # SBE additional info
            "response_result_id" => [
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ],
            "external_uniqueid" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "md5_sum" => [
                "type" => "VARCHAR",
                "constraint" => "32",
                "null" => true
            ]
        ];

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_UserName","UserName");
            $this->player_model->addIndex($this->tableName,"idx_UserId","UserId");
            $this->player_model->addIndex($this->tableName,"idx_OrderTime","OrderTime");
            $this->player_model->addIndex($this->tableName,"idx_TransGuid","TransGuid");
            $this->player_model->addIndex($this->tableName,"idx_ModifyDate", "ModifyDate");
            $this->player_model->addIndex($this->tableName,"idx_WinloseDate", "WinloseDate");
            $this->player_model->addIndex($this->tableName,"idx_start_at", "start_at");
            $this->player_model->addIndex($this->tableName,"idx_end_at", "end_at");
            $this->player_model->addIndex($this->tableName,"idx_updated_at", "updated_at");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}