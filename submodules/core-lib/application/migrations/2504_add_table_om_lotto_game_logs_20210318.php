<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_om_lotto_game_logs_20210318 extends CI_Migration
{

    private $tableName = "om_lotto_game_logs";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "bet_id" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => false
            ),
            'bet_datetime' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'updated_datetime' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'bet_status_id' => array(
                'type' => 'TINYINT',
                'null' => true,
            ),
            'bet_result_id' => array(
                'type' => 'TINYINT',
                'null' => true,
            ),
            "member_user_code" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "member_currency_code" => array(
                "type" => "VARCHAR",
                "constraint" => "10",
                "null" => true
            ),
            "total_stake" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "valid_stake" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "member_result_amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            'bet_type_id' => array(
                'type' => 'TINYINT',
                'null' => true,
            ),
            "selection_type_code" => array(
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ),
            "odds" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            'odds_string' => array(
                "type" => "TEXT",
                "null" => true		
            ),
            'event_display_date_time' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'game_type' => array(
                'type' => 'TINYINT',
                'null' => true,
            ),
            "event_id" => array(
                "type" => "INT",
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
            $this->player_model->addIndex($this->tableName,"idx_member_user_code","member_user_code");
            $this->player_model->addIndex($this->tableName,"idx_bet_datetime","bet_datetime");
            $this->player_model->addIndex($this->tableName,"idx_updated_datetime","updated_datetime");
            $this->player_model->addIndex($this->tableName,"idx_game_type","game_type");
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