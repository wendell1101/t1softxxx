<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_fastwin_outlet_20240829 extends CI_Migration
{
    private $tableName = 'fastwin_outlet';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'networkcode' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => false
            ),
            'encryptcode' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'unique' => true,
                'null' => false
            ),
            'outlet' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true
            ),
            'displayname' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true
            ),
            'outletaddress' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'extra_info' => array(
                'type' => 'JSON',
                'null' => true
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_networkcode', 'networkcode');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_encryptcode', 'encryptcode');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');

        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
