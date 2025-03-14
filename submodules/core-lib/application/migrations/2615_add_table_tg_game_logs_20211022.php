<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_tg_game_logs_20211022 extends CI_Migration {

    private $tableName = 'tg_game_logs';

    public function up() 
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true,
            ),
            'c_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'c_idx' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'c_casino' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'c_table_idx' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'c_shoe_idx' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'c_game_idx' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'c_bet_type' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
            ),
            'c_bet_result' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
            ),
            'c_result_money' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'c_bet_money' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'c_after_money' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'c_reg_date' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'pc1' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'pc2' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'pc3' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'bc1' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'bc2' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'bc3' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'c_game_result' => array(
                'type' => 'INT',
                'constraint' => '20',
                'null' => true
            ),
            'pp' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true
            ),
            'bp' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true
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

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_c_idx', 'c_idx');
            $this->player_model->addIndex($this->tableName, 'idx_c_casino', 'c_casino');
            $this->player_model->addIndex($this->tableName, 'idx_c_id', 'c_id');
            $this->player_model->addIndex($this->tableName, 'idx_c_reg_date', 'c_reg_date');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}