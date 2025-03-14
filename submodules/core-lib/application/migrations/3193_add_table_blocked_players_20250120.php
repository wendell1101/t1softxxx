<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_blocked_players_20250120 extends CI_Migration {

    private $tableName = 'blocked_players';
    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'player_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'reason' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'blocked_by_admin_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'blocked_by_admin_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_username', 'player_username');
            $this->player_model->addIndex($this->tableName, 'idx_blocked_by_admin_id', 'blocked_by_admin_id');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}