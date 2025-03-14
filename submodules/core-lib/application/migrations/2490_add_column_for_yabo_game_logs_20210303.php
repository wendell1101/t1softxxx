<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_yabo_game_logs_20210303 extends CI_Migration {

    private $tableName='yabo_gamelogs';

    public function up() {
        $column = array(
            'rewardAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            )
        );

        $column2 = array(
            'rewardType' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('rewardAmount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column);
            }
            if(!$this->db->field_exists('rewardType', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column2);
            }
        }
    }

    public function down() {
        if($this->db->field_exists('rewardAmount', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'rewardAmount');
        }
        if($this->db->field_exists('rewardType', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'rewardType');
        }
    }
}