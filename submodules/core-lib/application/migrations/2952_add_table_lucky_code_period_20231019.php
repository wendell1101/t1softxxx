<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_lucky_code_period_20231019 extends CI_Migration
{
    private $tableName = 'lucky_code_period';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'period_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ),
            'start_date' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'end_date' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'remark' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'last_sync_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'status' => array(
                'type' => 'INT',
                'null' => false,
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);
            
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_start_date','start_date');
            $this->player_model->addIndex($this->tableName,'idx_end_date','end_date');
            $this->player_model->addIndex($this->tableName,'idx_period_name','period_name');
        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}