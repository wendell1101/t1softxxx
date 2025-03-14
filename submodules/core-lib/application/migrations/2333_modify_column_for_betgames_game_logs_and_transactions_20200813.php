<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_betgames_game_logs_and_transactions_20200813 extends CI_Migration {

    private $tableName='betgames_wallet_transactions';
    private $tableNameGameLogs='betgames_game_logs';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $field = array(
                'bet' => array(
                    'type' => 'TEXT',
                    'null' => true,
                ),
            );
            if($this->db->field_exists('bet', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
        if($this->utils->table_really_exists($this->tableNameGameLogs)){
            $field = array(
                'bet' => array(
                    'type' => 'TEXT',
                    'null' => true,
                ),
            );
            if($this->db->field_exists('bet', $this->tableNameGameLogs)){
                $this->dbforge->modify_column($this->tableNameGameLogs, $field);
            }
        }
    }

    public function down() {
    }
}