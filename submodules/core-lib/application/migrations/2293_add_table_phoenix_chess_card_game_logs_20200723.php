<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_phoenix_chess_card_game_logs_20200723 extends CI_Migration
{

    private $tableName = "phoenix_chess_card_game_logs";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "userid" => array(
                "type" => "INT",
                "null" => false
            ),
            "account" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => false
            ),
            "classify" => array(
                "type" => "VARCHAR",
                "constraint" => "250",
                "null" => true
            ),
            "gamename" => array(
                "type" => "VARCHAR",
                "constraint" => "250",
                "null" => true
            ),
            "roomname" => array(
                "type" => "VARCHAR",
                "constraint" => "250",
                "null" => true
            ),
            "tableid" => array(
                "type" => "INT",
                "null" => true
            ),
            "gamestarttime" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "gameendtime" => array(
                "type" => "DATETIME",
                "null" => false
            ),
            "winlosemoney" => array(
                "type" => "DOUBLE",
                "null" => false
            ),
            "tax" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            'bodyleftmoney' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'logflag' => array(
                'type' => 'VARCHAR',
                'constraint' => '250',
                'null' => true,
            ),
            'chips' => array(
                'type' => 'DOUBLE',
                'null' => false,
            ),
            'jp' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'jpttype' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'jpcontribution' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'chipsEx' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'aftertaxmoney' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),


            # SBE additional info
            "response_result_id" => array(
                "type" => "INT",
                "null" => true
            ),
            "external_uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_account","account");
            $this->player_model->addIndex($this->tableName,"idx_logflag","logflag");
            $this->player_model->addIndex($this->tableName,"idx_gameendtime","gameendtime");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}