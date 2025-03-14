<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_duplicate_contactnumber_20230809 extends CI_Migration
{
    private $tableName = 'player_duplicate_contactnumber';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'player_id' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ),
            'duplicate_user' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ),
            'contact_number' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
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
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_contact_number', 'contact_number');
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