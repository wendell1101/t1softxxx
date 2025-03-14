<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_drop_betf_game_logs_field_20220121 extends CI_Migration {

    private $tableName = 'betf_game_logs';

    public function up()
    {
        $fields = array(
            'subOrderList' => array(
                'type' => 'JSON',
                'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('subOrderList', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
            if($this->db->field_exists('awayTeamName', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'awayTeamName');
            }
            if($this->db->field_exists('betScore', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'betScore');
            }
            if($this->db->field_exists('homeTeamName', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'homeTeamName');
            }
            if($this->db->field_exists('isLive', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'isLive');
            }
            if($this->db->field_exists('marketName', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'marketName');
            }
            if($this->db->field_exists('matchResult', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'matchResult');
            }
            if($this->db->field_exists('matchTime', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'matchTime');
            }
            if($this->db->field_exists('odds', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'odds');
            }
            if($this->db->field_exists('oddsType', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'oddsType');
            }
            if($this->db->field_exists('outcomeName', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'outcomeName');
            }
            if($this->db->field_exists('specifier', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'specifier');
            }
            if($this->db->field_exists('subOrderStatus', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'subOrderStatus');
            }
            if($this->db->field_exists('tournamentName', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'tournamentName');
            }
        }
    }

    public function down() {

    }
}