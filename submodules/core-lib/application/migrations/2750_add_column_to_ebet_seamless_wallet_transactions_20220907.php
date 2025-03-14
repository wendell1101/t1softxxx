<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_ebet_seamless_wallet_transactions_20220907 extends CI_Migration {

    private $tableName = 'ebet_seamless_wallet_transactions';

    public function up() {
        $fields = [
            'seqNo' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'betType' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'odds' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
            'validBet' => array(
                'type' => 'DOUBLE',
                'null' => true
            )

        ];

        if(!$this->db->field_exists('seqNo', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_seqno', 'seqNo');
        }
    }

    public function down() {
        if($this->db->field_exists('seqNo', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'seqNo');
        }
    }
}