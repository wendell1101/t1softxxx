<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_pp_incomplete_games_20220419 extends CI_Migration {

    private $tableName = 'pp_incomplete_games';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'playerId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'gameId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'playSessionID' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'betAmount' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'dataType' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),
            'username_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '110',
                'null' => true,
            ),

            # SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if(!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_playerId', 'playerId');
            $this->player_model->addIndex($this->tableName, 'idx_gameId', 'gameId');
            $this->player_model->addIndex($this->tableName, 'idx_playSessionID', 'playSessionID');
            $this->player_model->addIndex($this->tableName, 'idx_username_key', 'username_key');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if($this->db->table_exist($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
