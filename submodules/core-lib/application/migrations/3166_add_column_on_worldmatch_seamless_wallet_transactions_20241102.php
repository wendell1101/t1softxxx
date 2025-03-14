<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_on_worldmatch_seamless_wallet_transactions_20241102 extends CI_Migration {
    private $tableName = 'worldmatch_seamless_wallet_transactions';

    public function up()
    {
        # Add column
        $field = array(
            'bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        $field2 = array(
            'payout_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('bet_amount', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }

        if(!$this->db->field_exists('payout_amount', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field2);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'bet_amount');
        $this->dbforge->drop_column($this->tableName, 'payout_amount');
    }
}
