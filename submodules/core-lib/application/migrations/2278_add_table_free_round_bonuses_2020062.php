<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_free_round_bonuses_2020062 extends CI_Migration
{

    private $tableName = "free_round_bonuses";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "player_id" => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => false
            ),
            'game_platform_id' => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => false
            ),
            'free_rounds' => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => false
            ),
            "transaction_id" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => false
            ),
            "currency" => array(
                "type" => "VARCHAR",
                "constraint" => "3",
                "null" => false
            ),
            "expired_at" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "extra" => array(
                "type" => "TEXT",
                "null" => true
            ),
            'status' => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => false
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_player_id","player_id");
            $this->player_model->addIndex($this->tableName,"idx_transaction_id","transaction_id");
            $this->player_model->addIndex($this->tableName,"idx_game_platform_id","game_platform_id");
        }
    }

    public function down()

    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}