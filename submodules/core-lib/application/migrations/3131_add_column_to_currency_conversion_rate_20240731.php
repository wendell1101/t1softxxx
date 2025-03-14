<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_currency_conversion_rate_20240731 extends CI_Migration {
    
    private $tableName = 'currency_conversion_rate';
    
    public function up() {
        $fields1 = array(
            'status' => array(
                'type' => 'INT',
                'null' => true,
            )
        );
		$fields2 = array(
            'createdBy' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE,
            )
        );
        $fields3 = array(
            'updatedBy' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE,
            )
        );
        $fields4 = array(
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => FALSE,
            ),
        );

        $this->load->model('player_model');
		if($this->utils->table_really_exists($this->tableName)){
			if(!$this->db->field_exists('status', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields1);
			}
            if(!$this->db->field_exists('createdBy', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
            if(!$this->db->field_exists('updatedBy', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields3);
            }
            if(!$this->db->field_exists('updatedAt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields4);
            }
		}
	}

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('status', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'status');
            }
            if($this->db->field_exists('createdBy', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'createdBy');
            }
            if($this->db->field_exists('updatedBy', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'updatedBy');
            }
            if($this->db->field_exists('updatedAt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'updatedAt');
            }
        }
    }
}