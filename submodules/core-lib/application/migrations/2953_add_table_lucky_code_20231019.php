<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_lucky_code_20231019 extends CI_Migration
{
    private $tableName = 'lucky_code';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'period_id' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ),
            'player_id' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ),
            'trans_type' => array(
                'type' => 'INT',
				'constraint' => '12',
				'null' => false,
            ),
            'trans_id' => array(
                'type' => 'INT',
				'constraint' => '12',
				'null' => false,
            ),
            'code' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ),
            'remark' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'sale_order_settled_time' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
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
            $this->player_model->addIndex($this->tableName,'idx_period_id','period_id');
            $this->player_model->addIndex($this->tableName,'idx_trans_id','trans_id');
            $this->player_model->addIndex($this->tableName,'idx_sale_order_settled_time','sale_order_settled_time');
            $this->player_model->addIndex($this->tableName,'idx_code','code');
            $this->player_model->addIndex($this->tableName,'idx_trans_type','trans_type');
            $this->player_model->addIndex($this->tableName,'idx_created_at','created_at');
            $this->player_model->addIndex($this->tableName,'idx_player_id','player_id');
            $this->player_model->addIndex($this->tableName,'idx_status','status');

        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}