<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_on_bbin_game_logs_20210604 extends CI_Migration {

    private $tableName = 'bbin_game_logs';

    public function up() {

        $fields = array(
            'jackpot_details' => array(
                'type' => 'text',            
                'null' => true,
            ),
            "jp_type_id" => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            "jp_amount" => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('jackpot_details', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('jackpot_details', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'jackpot_details');
            }
            if($this->db->field_exists('jp_type_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'jp_type_id');
            }
            if($this->db->field_exists('jp_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'jp_amount');
            }
        }
        
    }
}
