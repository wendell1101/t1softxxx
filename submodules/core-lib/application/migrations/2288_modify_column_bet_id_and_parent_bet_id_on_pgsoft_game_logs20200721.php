<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_bet_id_and_parent_bet_id_on_pgsoft_game_logs20200721 extends CI_Migration {

    private $tableName='pgsoft_game_logs';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'betid' => array(
                    'type' => 'BIGINT',
                    'null' => true
                ),
                'parentbetid' => array(
                    'type' => 'BIGINT',
                    'null' => true
                ),
            );
            if($this->db->field_exists('betid', $this->tableName) && $this->db->field_exists('parentbetid', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
    }
}