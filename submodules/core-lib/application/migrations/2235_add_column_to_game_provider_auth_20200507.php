<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_column_to_game_provider_auth_20200507 extends CI_Migration {

	private $tableName = 'game_provider_auth';

	public function up() {
        $fields = array(
            'external_category' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('external_category', $this->tableName)){
            $this->load->model('player_model');
            $this->dbforge->add_column($this->tableName, $fields);
            $this->player_model->addIndex($this->tableName, 'idx_external_category', 'external_category');
        }
	}

	public function down() {        
        if($this->db->field_exists('external_category', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'external_category');
        }
	}
}