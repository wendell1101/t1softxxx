<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_playerdetails_extra_20231024 extends CI_Migration
{
    private $tableName = 'playerdetails_extra';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'playerId' => array(
                'type' => 'INT',
                'unsigned' => true,
            ),
            'middleName' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'maternalName' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'issuingLocation' => array(
                'type' => 'varchar',
                'constraint' => '120',
                'null' => true,
            ),
            'issuanceDate' => array(
                'type' => 'DATE',
                'null' => true,
            ),
            'expiryDate' => array(
                'type' => 'DATE',
                'null' => true,
            ),
            'isPEP' => array(
                'type' => 'INT',
				'null' => false,
				'default' => 0,
            ),
            'acceptCommunications' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_playerId', 'playerId');
        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}