<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_point_transactions_20201202 extends CI_Migration {

    private $tableName='point_transactions';  

	public function up() {
		$fields = array(
            'date_hour' => array(
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ),
		);
		
		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('date_hour', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);

                # add Index
                $this->load->model(['player_model']);
                $this->player_model->addIndex($this->tableName,'idx_pointtransactions_datehour','date_hour');
            }            
        }
	}

	public function down() {
		if( $this->utils->table_really_exists($this->tableName)){            
            if($this->db->field_exists('date_hour', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'date_hour');
            }
        }
	}
}