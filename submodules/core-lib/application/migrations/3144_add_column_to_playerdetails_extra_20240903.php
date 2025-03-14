<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_playerdetails_extra_20240903 extends CI_Migration {
    
    private $tableName = 'playerdetails_extra';

    public function up() {       
        $fields1 = array(
            'sourceIncome' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
            )
        );

        $fields2 = array(
            'natureWork' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
            )
        );
		$this->load->model('player_model');
        if($this->utils->table_really_exists($this->tableName)){
			if(!$this->db->field_exists('sourceIncome', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields1);
			}
            if(!$this->db->field_exists('natureWork', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
		}
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('sourceIncome', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'sourceIncome');
            }
            if($this->db->field_exists('natureWork', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'natureWork');
            }
        }
    }
}