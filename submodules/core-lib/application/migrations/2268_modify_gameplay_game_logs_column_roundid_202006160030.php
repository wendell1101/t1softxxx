<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_gameplay_game_logs_column_roundid_202006160030 extends CI_Migration {

    private $tableName='gameplay_game_logs';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'bundle_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => '20',
                    'null' => true,
                ),
            );
            $this->dbforge->modify_column($this->tableName, $fields);
        }
    }

    public function down() {
    }
}