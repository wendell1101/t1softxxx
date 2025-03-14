<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_table_ethereum_players_20240507 extends CI_Migration {
    private $tableName = 'ethereum_players';

    public function up() {

        $fields = array(
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'crypto_address' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
            ],
            'player_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'constraint' => '10',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
        );


        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_crypto_address', 'crypto_address');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_player_id', 'player_id');
        }
    }

    public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}

}
