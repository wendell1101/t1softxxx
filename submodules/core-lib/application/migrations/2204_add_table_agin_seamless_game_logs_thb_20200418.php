<?php

defined("BASEPATH") OR exit("No direct script access allowed");


class Migration_add_table_agin_seamless_game_logs_thb_20200418 extends CI_Migration
{
    private $tableName = "agin_seamless_game_logs_thb";

    public function up()
    {
        $fields = [
            "id" => [
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ],
            'billno' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
			'playername' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
			'agentcode' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
			'gamecode' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
			'netamount' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
			'bettime' => array(
				'type' => 'DATETIME',
				'null' => false,
			),
			'gametype' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
			'betamount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'validbetamount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'flag' => array(
				'type' => 'INT',
				'null' => true,
			),
			'playtype' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'tablecode' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'loginip' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'recalcutime' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'platformtype' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'remark' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'round' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'slottype' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'result' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'mainbillno' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'beforecredit' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'datatype' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'creationtime' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'playerId' => array(
				'type' => 'INT',
				'null' => true,
			),
			'logs_ID' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
			),
			'tradeNo' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'sceneId' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'SceneStartTime' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'SceneEndTime' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'Roomid' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'Roombet' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'Cost' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'Earn' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'Jackpotcomm' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'transferAmount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'previousAmount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'currentAmount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'exchangeRate' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'jackpotsettlement' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'extra' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'transferType' => array(
				'type' => 'INT',
				'null' => true,
			),
			'fishIdStart' => array(
				'type' => 'INT',
				'null' => true,
			),
			'fishIdEnd' => array(
				'type' => 'INT',
				'null' => true,
			),
			'cancelReason' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			),
			'subbillno' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
            # SBE additional info
            "response_result_id" => [
                "type" => "INT",
                "constraint" => "64",
                "null" => true
            ],
            "external_uniqueid" => [
                "type" => "VARCHAR",
                "constraint" => "150",
                "null" => true
            ],
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "md5_sum" => [
                "type" => "VARCHAR",
                "constraint" => "32",
                "null" => true
            ]
        ];

        if(! $this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add index
            $this->load->model("player_model");
			$this->player_model->addIndex($this->tableName,"idx_bettime","bettime");
			$this->player_model->addIndex($this->tableName,"idx_gametype","gametype");
			$this->player_model->addIndex($this->tableName,"idx_playername","playername");
			$this->player_model->addIndex($this->tableName,"idx_recalcutime","recalcutime");
            $this->player_model->addUniqueIndex($this->tableName,"idx_uniqueid","uniqueid");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}