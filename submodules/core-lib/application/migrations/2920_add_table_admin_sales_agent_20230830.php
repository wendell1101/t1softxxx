<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_admin_sales_agent_20230830 extends CI_Migration
{
    private $tableName = 'admin_sales_agent';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ),
            'status' => array(
                'type' => 'SMALLINT',
                'null' => false,
                'default' => 1
            ),
            'chat_platform1' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ),
            'chat_platform2' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_by' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
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
            $this->player_model->addIndex($this->tableName, 'idx_user_id', 'user_id');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
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