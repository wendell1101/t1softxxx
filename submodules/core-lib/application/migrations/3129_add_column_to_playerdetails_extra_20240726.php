<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_playerdetails_extra_20240726 extends CI_Migration {
    
    private $tableName = 'playerdetails_extra';

    public function up() {
        $fields1 = array(
            'isInterdicted' => array(
                'type' => 'INT',
                'null' => true,
            )
        );
		$fields2 = array(
            'isInjunction' => array(
                'type' => 'INT',
                'null' => true,
            )
        );
        $fields3 = array(
            'storeCode' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
            )
        );
        
		$this->load->model('player_model');
        if($this->utils->table_really_exists($this->tableName)){
			if(!$this->db->field_exists('isInterdicted', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields1);
			}
            if(!$this->db->field_exists('isInjunction', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
            if(!$this->db->field_exists('storeCode', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields3);
            }           
		}
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('isInterdicted', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'isInterdicted');
            }
            if($this->db->field_exists('isInjunction', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'isInjunction');
            }
            if($this->db->field_exists('storeCode', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'storeCode');
            }
        }
    }
}