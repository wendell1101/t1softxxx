<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_changeover_request_20240712 extends CI_Migration {
    private $tableName = 'player_changeover_request';
    public function up()
    {
        $fields = [
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'playerId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'affiliateId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'newAffiliateId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'status' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'default' => 0,
                'null' => FALSE
            ),
            'requestDate DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => FALSE,
            ),
            'approvalDate' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'rejectionDate' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'completionDate' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'createdBy' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'updateBy' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => FALSE,
            ),
            'remarks' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            )
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('playerId', false);
            $this->dbforge->add_key('affiliateId', false);
            $this->dbforge->add_key('newAffiliateId', false);
            $this->dbforge->add_key('status', false);
            $this->dbforge->add_key('requestDate', false);
            $this->dbforge->create_table($this->tableName);
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
