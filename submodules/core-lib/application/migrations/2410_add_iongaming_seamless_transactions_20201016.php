<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_iongaming_seamless_transactions_20201016 extends CI_Migration {

    private $tableName = [
        'iongaming_seamless_transactions',
        'iongaming_seamless_transactions_idr1',
        'iongaming_seamless_transactions_idr2',
    ];

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'int',
                'auto_increment' => TRUE,
                'unsigned' => TRUE,
            ),
            'RefNo' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'SeqNo' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'OrderId' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'AccountId' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'ProductType' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'OrderTime' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'Stake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'WinningStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'PlayerWinLoss' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'SettlementStatus' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'GameId' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'GameStartTime' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'SettleTime' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'TableName' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'GroupBetOptions' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'BetOptions' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'Ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'IsCommission' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'Timestamp' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'Guid' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'transaction_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'before_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            )
        );

        foreach ($this->tableName as $tableName) {
            if(!$this->utils->table_really_exists($tableName)){

                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table($tableName);

                $this->load->model('player_model');
                $this->player_model->addUniqueIndex($tableName, 'idx_external_uniqueid', 'external_uniqueid');
                $this->player_model->addIndex($tableName, 'idx_OrderId', 'OrderId');
                $this->player_model->addIndex($tableName, 'idx_RefNo', 'RefNo');
                $this->player_model->addIndex($tableName, 'idx_SeqNo' , 'SeqNo');
                $this->player_model->addIndex($tableName, 'idx_AccountId', 'AccountId');
            }
        }


    }

    public function down() {
        foreach ($this->tableName as $tableName) {
            $this->dbforge->drop_table($tableName);
        }
    }
}