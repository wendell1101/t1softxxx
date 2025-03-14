<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_imesb_game_logs_20200420 extends CI_Migration
{

    private $tableName = "imesb_game_logs";

    public function up()
    {
        $fields = [
            "id" => [
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ],
            "betid" => [
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ],
            "betdate" => [
                "type" => "DATETIME",
                "null" => true
            ],
            "lastupdated" => [
                "type" => "DATETIME",
                "null" => true
            ],
            "membercode" => [
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ],
            "oddstype" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "odds" => [
                "type" => "DOUBLE",
                "null" => true,
            ],
            "currency" => [
                "type" => "VARCHAR",
                "constraint" => "10",
                "null" => true
            ],
            "stake" => [
                "type" => "DOUBLE",
                "null" => true,
            ],
            "result" => [
                "type" => "DOUBLE",
                "null" => true,
            ],
            "isparlay" => [
                "type" => "VARCHAR",
                "constraint" => "10",
                "null" => true
            ],
            "issettled" => [
                "type" => "VARCHAR",
                "constraint" => "10",
                "null" => true
            ],
            "iscancelled" => [
                "type" => "VARCHAR",
                "constraint" => "10",
                "null" => true
            ],
            "settlementtime" => [
                "type" => "DATETIME",
                "null" => true
            ],
            "bettingchannel" => [
                "type" => "VARCHAR",
                "constraint" => "10",
                "null" => true
            ],
            "betdetails" => [
                "type" => "TEXT",
                "null" => true
            ],
            "sportsid" => [
                "type" => "INT",
                "null" => true
            ],
            "sportsname" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "matchid" => [
                "type" => "INT",
                "null" => true
            ],
            "leagueid" => [
                "type" => "INT",
                "null" => true
            ],
            "leaguename" => [
                "type" => "VARCHAR",
                "constraint" => "300",
                "null" => true
            ],
            "baseleagueid" => [
                "type" => "INT",
                "null" => true
            ],
            "baseleaguename" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "baseleagueabbr" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "hometeamid" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "hometeamname" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "hometeamabbr" => [
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ],
            "awayteamid" => [
                "type" => "INT",
                "null" => true
            ],
            "awayteamname" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ],
            "awayteamabbr" => [
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ],
            "selection" => [
                "type" => "INT",
                "null" => true
            ],
            "gameorder" => [
                "type" => "INT",
                "null" => true
            ],            
            "gametypecode" => [
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ],
            "matchtype" => [
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ],
            "winlose" => [
                "type" => "DOUBLE",
                "null" => true
            ],
            "issettled" => [
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ],
            "iscancelled" => [
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ],
            "homescore" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "awayscore" => [
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ],
            "matchdatetime" => [
                "type" => "DATETIME",
                "null" => true
            ],
            "canceltype" => [
                "type" => "INT",
                "null" => true
            ],
            "handicap" => [
                "type" => "INT",
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
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        ];

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_sportsid","sportsid");
            $this->player_model->addIndex($this->tableName,"idx_membercode","membercode");
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