<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_fbsports_seamless_wallet_game_records_20240619 extends CI_Migration {

    private $tableName = 'fbsports_seamless_wallet_game_records';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'cashOutOrderId' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'orderId' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'rejectReason' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'rejectReasonStr' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'userId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'merchantId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'merchantUserId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'exchangeRate' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'seriesType' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'betType' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'allUp' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'allUpAlive' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'orderStakeAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'stakeAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'liabilityStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'settleAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'orderStatus' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'payStatus' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'oddsChange' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'device' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'cashoutTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'betTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'settleTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'createTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'modifyTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'cancelTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'lastModifyTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'remark' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'thirdRemark' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'relatedId' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'maxWinAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'loseAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'rollBackCount' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'itemCount' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'seriesValue' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'betNum' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'cashOutTotalStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'cashOutStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'liabilityCashoutStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'cashOutPayoutStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'acceptOddsChange' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'reserveId' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'cashOutCount' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'unitStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'reserveVersion' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'betList' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'maxStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'validSettleStakeAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'validSettleAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'cashOutCancelStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'cancelReasonCode' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'cancelCashOutAmountTo' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'unitCashOutPayoutStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'walletType' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'version' => array(
                'type' => 'INT',
                'null' => true,
            ),
            # SBE additional info
            'request_id' => array( 
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
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
            $this->player_model->addIndex($this->tableName, 'idx_merchantUserId', 'merchantUserId');
            $this->player_model->addIndex($this->tableName, 'idx_userId', 'userId');
            $this->player_model->addIndex($this->tableName, 'idx_settleTime', 'settleTime');
            $this->player_model->addIndex($this->tableName, 'idx_createTime', 'createTime');
            $this->player_model->addIndex($this->tableName, 'idx_modifyTime', 'modifyTime');
            $this->player_model->addIndex($this->tableName, 'idx_cancelTime', 'cancelTime');
            $this->player_model->addIndex($this->tableName, 'idx_cancelTime', 'cancelTime');
            $this->player_model->addIndex($this->tableName, 'idx_orderId', 'orderId');
            $this->player_model->addIndex($this->tableName, 'idx_cashOutOrderId', 'cashOutOrderId');
            
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}