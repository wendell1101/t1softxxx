<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_betf_game_logs_20220119 extends CI_Migration {

    private $tableName = 'betf_game_logs';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'betTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'betAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'betType' => array(
                'type' => 'INT',
                'constraint' => '50',
                'null' => true,
            ),
            'ipAddress' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'merchantUserId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'orderNo' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'payoutTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'rebateAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'rebateTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'sportCategoryName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'status' => array(
                'type' => 'VARCHAR',
                'constraint' => '15',
                'null' => true,
            ),
            'userName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'awayTeamName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'betScore' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'homeTeamName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'isLive' => array(
                'type' => 'VARCHAR',
                'constraint' => '15',
                'null' => true,
            ),
            'marketName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'matchResult' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'matchTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'odds' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'oddsType' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'outcomeName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'specifier' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'subOrderStatus' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'tournamentName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
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

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_tournamentName', 'tournamentName');
            $this->player_model->addIndex($this->tableName, 'idx_orderNo', 'orderNo');
            $this->player_model->addIndex($this->tableName, 'idx_merchantUserId', 'merchantUserId');
            $this->player_model->addIndex($this->tableName, 'idx_betTime', 'betTime');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}