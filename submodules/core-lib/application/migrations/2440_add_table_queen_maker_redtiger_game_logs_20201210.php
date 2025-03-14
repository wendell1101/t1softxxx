<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_queen_maker_redtiger_game_logs_20201210 extends CI_Migration
{

    private $tableName = "queen_maker_redtiger_game_logs";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "roundstart" => array(
                "type" => "VARCHAR",
                "constraint" => "36",
                "null" => false
            ),
            "roundend" => array(
                "type" => "VARCHAR",
                "constraint" => "36",
                "null" => false
            ),
            "roundid" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "ugsroundid" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "roundstatus" => array(
                "type" => "VARCHAR",
                "constraint" => "16",
                "null" => true
            ),
            "userid" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "username" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "playertype" => array(
                "type" => "INT",
                "null" => true
            ),
            "riskamt" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "winamt" => array(
                "type" => "DOUBLE",
                "null" => false
            ),
            "beforebal" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            'postbal' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'cur' => array(
                "type" => "VARCHAR",
                "constraint" => "8",
                "null" => true
            ),
            'gameprovider' => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'gameprovidercode' => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'gamename' => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'gameid' => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'platformtype' => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'ipaddress' => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'turnover' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'validbet' => array(
                'type' => 'DOUBLE',
                'null' => false,
            ),
            'jackpotcontribution' => array(
                'type' => 'DOUBLE',
                'null' => false,
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
            $this->player_model->addIndex($this->tableName,"idx_queen_maker_redtiger_userid","userid");
            $this->player_model->addIndex($this->tableName,"idx_queen_maker_redtiger_username","username");
            $this->player_model->addIndex($this->tableName,"idx_queen_maker_redtiger_roundstart","roundstart");
            $this->player_model->addIndex($this->tableName,"idx_queen_maker_redtiger_roundend","roundend");
            $this->player_model->addUniqueIndex($this->tableName,"idx_queen_maker_redtiger_external_uniqueid","external_uniqueid");
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}