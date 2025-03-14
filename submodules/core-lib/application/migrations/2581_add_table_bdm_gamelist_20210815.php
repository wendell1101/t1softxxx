<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_bdm_gamelist_20210815 extends CI_Migration
{

    private $tableName = "bdm_gamelist";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "game_type" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "game_code" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "game_name" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "game_alias" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "specials" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "supported_platforms" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "order" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "default_width" => array(
                "type" => "SMALLINT",
                "null" => true
            ),
            "default_height" => array(
                "type" => "SMALLINT",
                "null" => true
            ),
            "image1" => array(
                "type" => "VARCHAR",
                "constraint" => "300",
                "null" => true
            ),
            #default
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