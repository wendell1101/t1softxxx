<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_Add_table_pretty_gaming_tables_20200720 extends CI_Migration
{

    private $gamelogsTableName = "pretty_gaming_seamless_api_gamelogs";
    private $transactionTableName = "pretty_gaming_seamless_api_transaction";

    private $gamelogsTableNameTHB1 = "pretty_gaming_seamless_api_gamelogs_thb1";
    private $transactionTableNameTHB1 = "pretty_gaming_seamless_api_transaction_thb1";

    public function up()
    {
        $transaction_fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "playerUsername" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "ticketId" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            'type' => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            'currency' => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "gameId" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                'null' => true,
            ),
            "totalBetAmt" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "totalPayOutAmt" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "winLoseTurnOver" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "txtList" => array(
                "type" => "TEXT",
                "null" => true
            ),
            "status" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "result" => array(
                'type' => 'TEXT',
                "null" => true
            ),
            "createDate" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "requestDate" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "external_uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "api_request" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
        );


        $gamelogs_fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "validAmt" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "payOutCom" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "payOutBet" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "winLose" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "payOutAmt" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "betStatus" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "status" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "_id" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "memberId" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "username" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "memberUsername" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "currency" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "ticketId" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "type" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "gameId" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "tableId" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "round" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "commissionRate" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "payOutRate" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "betPosition" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "betAmt" => array(
                'type' => 'DOUBLE',
                "null" => true
            ),
            "ip" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "updateDate" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "createDate" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "__v" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "result" => array(
                'type' => 'TEXT',
                "null" => true
            ),
            "external_uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
        );

        if(!$this->db->table_exists($this->transactionTableName)){
            $this->dbforge->add_field($transaction_fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->transactionTableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->transactionTableName,"idx_playerUsername","playerUsername");
            $this->player_model->addIndex($this->transactionTableName,"idx_ticketId","ticketId");
            $this->player_model->addIndex($this->transactionTableName,"idx_status","status");
            $this->player_model->addIndex($this->transactionTableName,"idx_md5_sum","md5_sum");
            $this->player_model->addUniqueIndex($this->transactionTableName, 'idx_pretty_external_unique_id', 'external_uniqueid');
        }

        if(!$this->db->table_exists($this->transactionTableNameTHB1)){
            $this->dbforge->add_field($transaction_fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->transactionTableNameTHB1);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->transactionTableNameTHB1,"idx_playerUsernameTHB1","playerUsername");
            $this->player_model->addIndex($this->transactionTableNameTHB1,"idx_ticketIdTHB1","ticketId");
            $this->player_model->addIndex($this->transactionTableNameTHB1,"idx_statusTHB1","status");
            $this->player_model->addIndex($this->transactionTableNameTHB1,"idx_md5_sumTHB1","md5_sum");
            $this->player_model->addUniqueIndex($this->transactionTableNameTHB1, 'idx_prettythb1_external_unique_id', 'external_uniqueid');
        }

        if(!$this->db->table_exists($this->gamelogsTableName)){
            $this->dbforge->add_field($gamelogs_fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->gamelogsTableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->gamelogsTableName,"idx_memberUsername","memberUsername");
            $this->player_model->addIndex($this->gamelogsTableName,"idx__id","_id");
            $this->player_model->addIndex($this->gamelogsTableName,"idx_ticketId","ticketId");
            $this->player_model->addIndex($this->gamelogsTableName,"idx_type","type");
            $this->player_model->addIndex($this->gamelogsTableName,"idx_status","status");
            $this->player_model->addIndex($this->gamelogsTableName,"idx_md5_sum","md5_sum");
            $this->player_model->addUniqueIndex($this->gamelogsTableName, 'idx_pretty_external_unique_id', 'external_uniqueid');
        }

        if(!$this->db->table_exists($this->gamelogsTableNameTHB1)){
            $this->dbforge->add_field($gamelogs_fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->gamelogsTableNameTHB1);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->gamelogsTableNameTHB1,"idx_memberUsernameTHB1","memberUsername");
            $this->player_model->addIndex($this->gamelogsTableNameTHB1,"idx__id","_id");
            $this->player_model->addIndex($this->gamelogsTableNameTHB1,"idx_ticketIdTHB1","ticketId");
            $this->player_model->addIndex($this->gamelogsTableNameTHB1,"idx_type","type");
            $this->player_model->addIndex($this->gamelogsTableNameTHB1,"idx_statusTHB1","status");
            $this->player_model->addIndex($this->gamelogsTableNameTHB1,"idx_md5_sumTHB1","md5_sum");
            $this->player_model->addUniqueIndex($this->gamelogsTableNameTHB1, 'idx_prettythb1_external_unique_id', 'external_uniqueid');
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->transactionTableName)){
            $this->dbforge->drop_table($this->transactionTableName);
        }
        if($this->db->table_exist($this->transactionTableNameTHB1)){
            $this->dbforge->drop_table($this->transactionTableNameTHB1);
        }
        if($this->db->table_exist($this->gamelogsTableName)){
            $this->dbforge->drop_table($this->gamelogsTableName);
        }
        if($this->db->table_exist($this->gamelogsTableNameTHB1)){
            $this->dbforge->drop_table($this->gamelogsTableNameTHB1);
        }
    }
}