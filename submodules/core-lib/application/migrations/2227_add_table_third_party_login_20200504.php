<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_table_third_party_login_20200504 extends CI_Migration {

    private $tableName = 'third_party_login';

    public function up() {
        $fields = array(
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'uuid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ),
            'access_ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '15',
                'null' => false,
            ),
            'status' => array(
                'type' => 'VARCHAR',
                'constraint' => '15',
                'null' => false,
            ),
            'third_party_user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'error_note' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'extra_info' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
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
            $this->player_model->addIndex($this->tableName, 'idx_third_party_user_id', 'third_party_user_id');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_uuid', 'uuid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
