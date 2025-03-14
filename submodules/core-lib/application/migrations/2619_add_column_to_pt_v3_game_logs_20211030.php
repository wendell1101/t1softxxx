<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_pt_v3_game_logs_20211030 extends CI_Migration 
{
    private $tableName = 'pt_v3_game_logs';

    public function up() 
    {
       

        $field = array(
            'exitgame' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            )
        );

        $field1 = array(
            'shortname' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName))
        {
            $this->load->model('player_model');
            if(!$this->db->field_exists('exitgame', $this->tableName))
            {
                $this->dbforge->add_column($this->tableName, $field);
            }

            if(!$this->db->field_exists('shortname', $this->tableName))
            {
                $this->dbforge->add_column($this->tableName, $field1);
                $this->player_model->addIndex($this->tableName, 'idx_shortname', 'shortname');
            }
        }
    }

    public function down() 
    {
        if($this->utils->table_really_exists($this->tableName))
        {
            if($this->db->field_exists('exitgame', $this->tableName))
            {
                $this->dbforge->drop_column($this->tableName, 'exitgame');
            }
            if($this->db->field_exists('shortname', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'shortname');
            }
        }
    }
}
