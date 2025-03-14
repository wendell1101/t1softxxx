<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_sms_api_sending_record_20210929 extends CI_Migration
{

    private $tableName = "sms_api_sending_record";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            'contactNumber' => array(
                'type' => 'varchar',
                'constraint' => '20',
                'null' => false,
            ),
            "sessionId" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            'code' => array(
                'type' => 'VARCHAR',
                'constraint' => '6',
                'null' => false,
            ),
            'smsApiUsage' => array(
                'type' => 'VARCHAR',
                'null' => false,
                'default' => 'default',
                'constraint' => 32,
            ),
            'smsApiName' => array(
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => 50,
            ),
            'ip' => array(
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => 50,
            ),
            'playerId' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'createTime DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            // # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_contactNumber', 'contactNumber');
            $this->player_model->addIndex($this->tableName, 'idx_sessionId', 'sessionId');
            $this->player_model->addIndex($this->tableName, 'idx_code', 'code');
            $this->player_model->addIndex($this->tableName, 'idx_smsApiUsage', 'smsApiUsage');
            $this->player_model->addIndex($this->tableName, 'idx_smsApiName', 'smsApiName');
            $this->player_model->addIndex($this->tableName, 'idx_playerId', 'playerId');
            $this->player_model->addIndex($this->tableName, 'idx_createTime', 'createTime');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}