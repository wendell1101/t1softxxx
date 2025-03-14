<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_unique_id_of_tcg_game_draw_results_20200327 extends CI_Migration {

    private $tableName='tcg_game_draw_results';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'unique_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => true,
                ),
            );
            $this->dbforge->modify_column($this->tableName, $fields);
        }
    }

    public function down() {
    }
}