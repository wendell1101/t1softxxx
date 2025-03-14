<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_total_score_20220414 extends CI_Migration {

    private $tableName = 'total_score';

    public function up() {
        $fields = array(
            'rank_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('rank_name', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
            if($this->db->field_exists('rank_name', $this->tableName)){                                
                $this->player_model->addIndex($this->tableName,'idx_rank_name','rank_name');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('rank_name', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'rank_name');
            }
        }
    }
}