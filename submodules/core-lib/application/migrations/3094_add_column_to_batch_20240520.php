<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_column_to_batch_20240520 extends CI_Migration {
    private $tableName = 'batch';

    public function up() {
        $fields1 = array(
            'status' => array(
                'type' => 'INT',
                'null' => true,
                'default' => '1'
            )
        );
		$fields2 = array(
            'delete_at' => array(
                'type' => 'DATETIME',
				'null' => true,
            )
        );
        $fields3 = array(
            'delete_by' => array(
                'type' => 'INT',
                'null' => true,
            )
        );

        $this->load->model('player_model');
		if($this->utils->table_really_exists($this->tableName)){
			if(!$this->db->field_exists('status', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields1);
                $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
			}
            if(!$this->db->field_exists('delete_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
            if(!$this->db->field_exists('delete_by', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields3);
            }
		}
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('status', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'status');
            }
            if($this->db->field_exists('delete_at', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'delete_at');
            }
            if($this->db->field_exists('delete_by', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'delete_by');
            }
		}
	}
}