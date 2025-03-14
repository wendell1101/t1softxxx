<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_width_hogamingseamless_game_logs_202005121430 extends CI_Migration {

    private $tableName='hogamingseamless_game_logs';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'bet_no' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => true,
                ),
            );
            $this->dbforge->modify_column($this->tableName, $fields);
        }
    }

    public function down() {
      // Cannot rollback due to data truncation
    }
}