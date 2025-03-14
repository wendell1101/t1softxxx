<?php

defined("BASEPATH") OR exit("No direct script access allowed");


class Migration_add_table_sa_gaming_seamless_game_logs_20200415 extends CI_Migration
{
    private $tableName = "sa_gaming_seamless_game_logs";

    public function up()
    {
        $fields = [
            "id" => [
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ],
            "username" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],            
            "currency" => [
                "type" => "VARCHAR",
                "constraint" => "6",
                "null" => true
            ],            
            "txnid" => [
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ],            
            "txn_reverse_id" => [
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ],            
            "timestamp" => [
				'type' => 'TIMESTAMP',
				'null' => true,
            ],            
            "ip" => [
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ],            
            "gametype" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],            
            "platform" => [
				'type' => 'TINYINT(1)',
				'null' => true
            ],            
            "hostid" => [
				'type' => 'INT',
				'null' => true
            ],            
            "gameid" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],            
            "betdetails" => [
				'type' => 'TEXT',
				'null' => true
            ],            
            "calltype" => [
                'type' => 'VARCHAR',
                "constraint" => "10",
				'null' => true
            ],            
            "amount" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "bet_amount" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "real_bet_amount" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "result_amount" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "balance_before" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "balance_after" => [
                "type" => "DOUBLE",
                "null" => true
            ],            
            "payouttime" => [
                "type" => "DATETIME",
                "null" => true
            ],
            # SBE additional info
            "response_result_id" => [
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ],
            "external_unique_id" => [
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
            $this->player_model->addIndex($this->tableName,"idx_username","username");
            $this->player_model->addUniqueIndex($this->tableName,"idx_transaction_id","txnid");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_unique_id","external_unique_id");
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}