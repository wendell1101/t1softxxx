<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_nttech_v2_related_table_20220120 extends CI_Migration 
{
    private $tables = [
        'king_maker_thb2_game_logs',
    ];

    public function up() 
    {
       foreach ($this->tables as $table) {
           $field1 = array(
                'tipInfo' => array(
                    'type' => 'JSON',
                    'null' => true,
                )
            );

            $field2 = array(
                'tip' => array(
                    'type' => 'DOUBLE',
                    'null' => true,
                )
            );

            if($this->utils->table_really_exists($table))
            {
                $this->load->model('player_model');
                if(!$this->db->field_exists('tipInfo', $table))
                {
                    $this->dbforge->add_column($table, $field1);
                }

                if(!$this->db->field_exists('tip', $table))
                {
                    $this->dbforge->add_column($table, $field2);
                }
            }
       }     
    }

    public function down() 
    {
        foreach ($this->tables as $table) {
            if($this->utils->table_really_exists($table))
            {
                if($this->db->field_exists('tipInfo', $table))
                {
                    $this->dbforge->drop_column($table, 'tipInfo');
                }
                if($this->db->field_exists('tip', $table)){
                    $this->dbforge->drop_column($table, 'tip');
                }
            }
        }
    }
}
