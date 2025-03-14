<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_wickets9_game_logs_20211208 extends CI_Migration {

    private $tableName = 'wickets9_game_logs';

    public function up() 
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'userId' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'betId' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'currencyType' => array(
                'type' => 'INT',
                'constraint' => '2',
                'null' => true,
            ),
            'betStatus' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'eventId' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'eventTypeName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'eventName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'matchStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'matchAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'profitLoss' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
            'betPlaced' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'matchSettledDate' => array(
                'type' => 'DATETIME',
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
            $this->player_model->addIndex($this->tableName, 'idx_eventTypeName', 'eventTypeName');
            $this->player_model->addIndex($this->tableName, 'idx_betId', 'betId');
            $this->player_model->addIndex($this->tableName, 'idx_userId', 'userId');
            $this->player_model->addIndex($this->tableName, 'idx_betPlaced', 'betPlaced');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}