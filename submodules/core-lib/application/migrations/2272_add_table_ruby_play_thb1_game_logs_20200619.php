<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_ruby_play_thb1_game_logs_20200619 extends CI_Migration
{

    private $tableName = "ruby_play_thb1_game_logs";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "sessionToken" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "playerId" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "currencyCode" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "gameId" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "roundId" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "transactionId" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "deviceType" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "gameRoundEnd" => array(
                "type" => "TINYINT",
                "null" => true
            ),
            "referenceTransactionId" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'action' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'before_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'after_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'start_at' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'end_at' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),

            
            # SBE additional info
            "response_result_id" => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ),
            "external_uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
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
            $this->player_model->addIndex($this->tableName,"idx_playerId","playerId");
            $this->player_model->addIndex($this->tableName,"idx_transactionId","transactionId");
            $this->player_model->addIndex($this->tableName,"idx_referenceTransactionId","referenceTransactionId");
            $this->player_model->addIndex($this->tableName,"idx_roundId","roundId");
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