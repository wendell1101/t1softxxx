<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_amd_seamless_game_logs_20210107 extends CI_Migration
{
    private $tableName = "amb_seamless_game_logs";

    public function up()
    {
        $fields = [
            "id" => [
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ],
            "orig_id" => [
                "type" => "BIGINT",
                "null" => true,                
            ],
            "username" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "game_name" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "categories" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "categories" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "timestamp" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "timestamp_parsed" => [
                "type" => "DATETIME",                
                "null" => true
            ],
            "round_id" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "room_id" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "uuid" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => false
            ],
            "bet" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "turnover" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "winlose" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "commision" => [
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
            $this->player_model->addIndex($this->tableName,"idx_game_name","game_name");    
            $this->player_model->addIndex($this->tableName,"idx_username","username");    
            $this->player_model->addIndex($this->tableName,"idx_round_id","round_id");         
            $this->player_model->addIndex($this->tableName,"idx_timestamp_parsed","timestamp_parsed");
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	                 
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