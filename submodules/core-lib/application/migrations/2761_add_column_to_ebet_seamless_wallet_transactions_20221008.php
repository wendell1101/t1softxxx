<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_ebet_seamless_wallet_transactions_20221008 extends CI_Migration {

    private $tableName = 'ebet_seamless_wallet_transactions';

    public function up() {
        $fields = [
            'player_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
        ];

        if(!$this->db->field_exists('player_username', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('player_username', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'player_username');
        }
    }
}