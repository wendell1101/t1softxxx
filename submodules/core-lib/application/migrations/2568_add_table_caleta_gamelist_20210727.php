<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_caleta_gamelist_20210727 extends CI_Migration
{

    private $tableName = "caleta_gamelist";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "url_thumb" => array(
                "type" => "VARCHAR",
                "constraint" => "300",
                "null" => true
            ),
            "url_background" => array(
                "type" => "VARCHAR",
                "constraint" => "300",
                "null" => true
            ),
            "product" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "platforms" => array(
                "type" => "JSON",
                "null" => true
            ),
            "name" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "game_id" => array(
                "type" => "SMALLINT",
                "null" => true
            ),
            "game_code" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "enabled" => array(
                "type" => "BOOLEAN",
                "null" => true
            ),
            "category" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "blocked_countries" => array(
                "type" => "JSON",
                "null" => true
            ),
            "freebet_support" => array(
                "type" => "BOOLEAN",
                "null" => true
            ),
            "external_uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true,
            ),
            "md5_sum" => array(
                "type" => "VARCHAR",
                "constraint" => "32",
                "null" => true,
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false,
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, "idx_game_code", "game_code");
            $this->player_model->addIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid',true);
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}