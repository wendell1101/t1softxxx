<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_center_api_cool_down_time_20220406 extends CI_Migration {

	private $tableName = 'player_center_api_cool_down_time';

	public function up() {
		$fields=array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'class' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'method' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'cool_down_sec' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0,
            ),
            'cache_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '255', // class-method-username-cool_down_sec
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_class' , 'class');
            $this->player_model->addIndex($this->tableName,'idx_method' , 'method');
            $this->player_model->addIndex($this->tableName,'idx_username' , 'username');
            $this->player_model->addIndex($this->tableName,'idx_cool_down_sec' , 'cool_down_sec');
            $this->player_model->addIndex($this->tableName,'idx_cache_key' , 'cache_key');
            $this->player_model->addIndex($this->tableName,'idx_created_at' , 'created_at');
            $this->player_model->addIndex($this->tableName,'idx_updated_at' , 'updated_at');
        }
	}

	public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
