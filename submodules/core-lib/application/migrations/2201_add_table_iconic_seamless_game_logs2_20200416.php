<?php

defined("BASEPATH") OR exit("No direct script access allowed");


class Migration_add_table_iconic_seamless_game_logs2_20200416 extends CI_Migration
{
    private $tableName = "original_iconic_seamless_game_logs";

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
            "orig_created_at" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "orig_updated_at" => [
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ],
            "win" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "bet" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "status" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "parent_id" => [
                "type" => "INT",
                "constraint" => "11",
                "null" => true  
            ],
            "parent" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "player_id" => [
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ],
            "player" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "game_id" => [
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ],
            "game" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "game_type" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "product_id" => [
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ],
            "currency" => [
                "type" => "VARCHAR",
                "constraint" => "10",
                "null" => true
            ],
            "valid_bet" => [
                "type" => "DOUBLE",
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
            $this->player_model->addIndex($this->tableName,"idx_game_id","game_id");    
            $this->player_model->addIndex($this->tableName,"idx_player","player");         
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