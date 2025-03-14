<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_whitelabel_game_logs_column_league_202008111430 extends CI_Migration {

    private $tableName='whitelabel_game_logs';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'league' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '500',
                    'null' => true,
                ),
            );
            $this->dbforge->modify_column($this->tableName, $fields);
        }
    }

    public function down() {
      // no action needed as data truncation may occur
    }
}