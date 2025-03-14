<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_tables_for_player_accumulated_amounts_log_20211227 extends CI_Migration
{
    private $table = 'player_accumulated_amounts_log';


    public function up()
    {
        # total_score
        $fields = array(
            'id' => ['type' => 'BIGINT', 'null' => false, 'auto_increment' => true],
            'player_id' => ['type' => 'INT', 'null' => false],
            'begin_datetime' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
            'end_datetime' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
            'accumulated_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '200', // bet / deposit
				'null' => true,
			),
            'query_token' => array(
				'type' => 'VARCHAR',
				'constraint' => '200', // in_level_1234
				'null' => true,
			),
            'amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'is_met' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        );

        if (!$this->utils->table_really_exists($this->table)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->table);

            $this->load->model('player_model'); # Any model class will do
            $this->player_model->addIndex($this->table, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->table, 'idx_begin_datetime', 'begin_datetime');
            $this->player_model->addIndex($this->table, 'idx_end_datetime', 'end_datetime');
            $this->player_model->addIndex($this->table, 'idx_accumulated_type', 'accumulated_type');
            $this->player_model->addIndex($this->table, 'idx_query_token', 'query_token');
            $this->player_model->addIndex($this->table, 'idx_is_met', 'is_met');
            $this->player_model->addIndex($this->table, 'idx_updated_at', 'updated_at');
            $this->player_model->addIndex($this->table, 'idx_created_at', 'created_at');
        }




    }

    public function down(){
        if($this->db->table_exists($this->table)){
            $this->dbforge->drop_table($this->table);
        }
    }
}