<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sbobet_seamless_game_transactions_20230623 extends CI_Migration
{
    private $tableName1 = 'sbobet_seamless_game_transactions';
    private $tableName2 = 'sbobet_seamless_game_logs';

    public function up()
    {
        $fields = array(
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'gpid' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName1)){
            if(!$this->db->field_exists('game_platform_id', $this->tableName1)){
                $this->dbforge->add_column($this->tableName1, $fields);
            }
        }

        if($this->utils->table_really_exists($this->tableName2)){
            if(!$this->db->field_exists('game_platform_id', $this->tableName2)){
                $this->dbforge->add_column($this->tableName2, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName1)){
            if($this->db->field_exists('game_platform_id', $this->tableName1)){
                $this->dbforge->drop_column($this->tableName1, 'game_platform_id');
            }
            if($this->db->field_exists('gpid', $this->tableName1)){
                $this->dbforge->drop_column($this->tableName1, 'gpid');
            }
            if($this->db->field_exists('game_id', $this->tableName1)){
                $this->dbforge->drop_column($this->tableName1, 'game_id');
            }
        }

        if($this->utils->table_really_exists($this->tableName2)){
            if($this->db->field_exists('game_platform_id', $this->tableName2)){
                $this->dbforge->drop_column($this->tableName2, 'game_platform_id');
            }
            if($this->db->field_exists('gpid', $this->tableName2)){
                $this->dbforge->drop_column($this->tableName2, 'gpid');
            }
            if($this->db->field_exists('game_id', $this->tableName1)){
                $this->dbforge->drop_column($this->tableName1, 'game_id');
            }
        }
    }
}
