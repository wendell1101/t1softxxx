<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_sv388_seamless_wallet_transactions_20230428 extends CI_Migration
{

    private $tableName = "sv388_seamless_wallet_transactions";

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'platformTxId' => [
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ],
            "userId" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "currency" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "platform" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            
            "gameType" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "gameCode" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "gameName" => [
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ],
            "betType" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "betAmount" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "winAmount" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "betTime" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "roundId" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "gameInfo" => [
                "type" => "VARCHAR",
                "constraint" => "300",
                "null" => true
            ],
            "action" => [
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
            "voidType" => [
                "type" => "VARCHAR",
                "constraint" => "16",
                "null" => true
            ],
            'cancel_before' => [
                "type" => "DOUBLE",
                "null" => true
            ],
            'cancel_after' => [
                "type" => "DOUBLE",
                "null" => true
            ],
            'tip_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'action_status' => [
                "type" => "TINYINT",
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
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ]
        ];
       
        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_userId","userId");
            $this->player_model->addIndex($this->tableName,"idx_roundId","roundId");
            $this->player_model->addIndex($this->tableName,'idx_platformTxId','platformTxId');
            $this->player_model->addIndex($this->tableName, 'idx_gameCode', 'gameCode');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName,'idx_updated_at','updated_at');
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
