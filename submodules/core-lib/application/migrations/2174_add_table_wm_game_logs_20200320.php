<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_wm_game_logs_20200320 extends CI_Migration {

    private $tableName = 'wm_game_logs';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'auto_increment' => TRUE,
                'unsigned' => TRUE,
            ),
            'user' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ),
            'betid' => array(
                'type' => 'int',
                'unsigned' => TRUE,
            ),
            'betTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'validbet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'water' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'result' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'betResult' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ),
            'winLoss' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'gid' => array(
                'type' => 'int',
                'unsigned' => TRUE,
            ),
            'ip' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ),
            'round' => array(
                'type' => 'int',
                'unsigned' => TRUE,
            ),
            'subround' => array(
                'type' => 'int',
                'unsigned' => TRUE,
            ),
            'tableId' => array(
                'type' => 'int',
                'unsigned' => TRUE,
            ),
            'commission' => array(
                'type' => 'int',
                'unsigned' => TRUE,
            ),
            'gameResult' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ),
            'gname' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ),
            'reset' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ),
            'settime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),

            // additional info
            'player_id' => array(
                'type' => 'int',
                'unsigned' => TRUE,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_betid', 'betid');
            $this->player_model->addIndex($this->tableName, 'idx_round', 'round');
            $this->player_model->addIndex($this->tableName, 'idx_tableId', 'tableId');
            $this->player_model->addIndex($this->tableName, 'idx_md5_sum', 'md5_sum');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}