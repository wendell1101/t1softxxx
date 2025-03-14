<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_table_aff_dashboard_20200602 extends CI_Migration {

    private $tableName = 'aff_dashboard';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
			],
            'aff_id' => [
                'type' => 'INT',
			],
			'ident' => [
				'type' => 'varchar',
				'constraint' => 10,
				'null' => true
			],
            'contents' => [
                'type' => 'json',
                'null' => true,
			],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
			],
			'updated_at DATETIME' => [
				'null' => true
			]
		];


        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            // Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'index_aff_id', 'aff_id');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
