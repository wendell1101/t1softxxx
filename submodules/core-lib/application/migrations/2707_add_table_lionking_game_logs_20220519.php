<?php
defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_lionking_game_logs_20220519 extends CI_Migration {
    private $tableName = 'lionking_game_logs';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ),
            'loginId' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'totalBet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'totalWin' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'betDetail' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'winDetail' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'orderId' => array(
                'type' => 'INT',
                'constraint' => '64',
                'null' => true,
            ),
            'actionDate' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'creationDate' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'gameName' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'validCommission' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'validBet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'validWin' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'mjpWin' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'mjpComm' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),

            # SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'null' => true
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
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
            $this->player_model->addIndex($this->tableName, 'idx_loginId', 'loginId');
            $this->player_model->addIndex($this->tableName, 'idx_orderId', 'orderId');
            $this->player_model->addIndex($this->tableName, 'idx_actionDate', 'actionDate');
            $this->player_model->addIndex($this->tableName, 'idx_creationDate', 'creationDate');
            $this->player_model->addIndex($this->tableName, 'idx_gameName', 'gameName');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if($this->db->table_exist($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}