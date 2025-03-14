<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_Add_table_pretty_gaming_table_20200812 extends CI_Migration
{

    private $gamelogsTableName = "pretty_gaming_gamelogs";

    public function up()
    {

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
    }

    public function down()
    {
        if($this->db->table_exist($this->gamelogsTableName)){
            $this->dbforge->drop_table($this->gamelogsTableName);
        }
    }
}