<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_change_column_reference_no_to_big_int_of_pt_v2_game_logs_20200322 extends CI_Migration {

    private $tableName='pt_v2_game_logs';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'reference_no' => array(
                    'type' => 'BIGINT',
                    'null' => true,
                ),
            );
            $this->dbforge->modify_column($this->tableName, $fields);
        }
    }

    public function down() {
    }
}