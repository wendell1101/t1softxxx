<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_sales_agent_20230830 extends CI_Migration
{
    private $tableName = 'player_sales_agent';

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
                'constraint' => '10',
                'null' => true
            ),
            'sales_agent_id' => array(
                'type' => 'INT',
                'null' => false,
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
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_sales_agent_id', 'sales_agent_id');
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