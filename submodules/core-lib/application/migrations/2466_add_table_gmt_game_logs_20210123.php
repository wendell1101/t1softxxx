<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_gmt_game_logs_20210123 extends CI_Migration
{

    private $tableName = "gmt_game_logs";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "game_username" => array(
                "type" => "VARCHAR",
                "constraint" => "36",
                "null" => false
            ),
            "total_bet" => array(
                "type" => "DOUBLE",
                "null" => false
            ),
            "total_win" => array(
                "type" => "DOUBLE",
                "null" => false
            ),
            "balance" => array(
                "type" => "DOUBLE",
                "null" => false
            ),
            "round_start_ts" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "round_end_ts" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'round_start_ts_parsed' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'round_end_ts_parsed' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            "game_id" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "round_id" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "game_type" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "game_platform_id" => array(
                "type" => "INT",
                "null" => true
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
            $this->player_model->addIndex($this->tableName,"idx_game_username","game_username");
            $this->player_model->addIndex($this->tableName,"idx_round_start_ts_parsed","round_start_ts_parsed");
            $this->player_model->addIndex($this->tableName,"idx_round_end_ts_parsed","round_end_ts_parsed");
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