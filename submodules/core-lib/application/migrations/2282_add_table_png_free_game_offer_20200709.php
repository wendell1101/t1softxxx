<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_png_free_game_offer_20200709 extends CI_Migration
{

    private $tableName = "png_free_game_offer";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "UserId" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "Username" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            'Line' => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ),
            'Coins' => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ),
            "Denomination" => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            "Rounds" => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ),
            "ExpireTime" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "Turnover" => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ),
            "FreegameExternalId" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "RequestId" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "GameIdList" => array(
                'type' => 'TEXT',
                "null" => true
            ),
            "GameNameList" => array(
                'type' => 'TEXT',
                "null" => true
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            ),
            'status' => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
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
            $this->player_model->addIndex($this->tableName,"idx_UserId","UserId");
            $this->player_model->addIndex($this->tableName,"idx_FreegameExternalId","FreegameExternalId");
            $this->player_model->addIndex($this->tableName,"idx_RequestId","RequestId");
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}