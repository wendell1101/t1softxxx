<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_modify_jumbo_gamelogs_20201019 extends CI_Migration
{
    private $tables = ['jumb_game_logs_idr1', 'jumb_game_logs_cny1', 'jumb_game_logs_thb1', 'jumb_game_logs_usd1', 'jumb_game_logs_vnd1', 'jumb_game_logs_myr1'];

    public function up() {

        $fields = array(
            'PlayerId' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'Username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'seqNo' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'mtype' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'gameDate' => array(
                'type' => 'VARCHAR',
                'constraint' => '19',
                'null' => true,
            ),
            'bet' => array(
                'type' => 'double',
                'null' => true,
            ),
            'win' => array(
                'type' => 'double',
                'null' => true,
            ),
            'total' => array(
                'type' => 'double',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '2',
                'null' => true,
            ),
            'jackpot' => array(
                'type' => 'double',
                'null' => true,
            ),
            'jackpotContribute' => array(
                'type' => 'double',
                'null' => true,
            ),
            'denom' => array(
                'type' => 'double',
                'null' => true,
            ),
            'lastModifyTime' => array(
                'type' => 'VARCHAR',
                'constraint' => '19',
                'null' => true,
            ),
            'gameName' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'playerIp' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'clientType' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'hasFreegame' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'hasGamble' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'systemTakeWin' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'err_text' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
        );

        foreach ($this->tables as $table) {
            if($this->utils->table_really_exists($table)) {
                if ($this->db->field_exists('PlayerId', $table) && $this->db->field_exists('Username', $table) && $this->db->field_exists('seqNo', $table)
                 && $this->db->field_exists('mtype', $table) && $this->db->field_exists('gameDate', $table) && $this->db->field_exists('bet', $table) && $this->db->field_exists('win', $table)
                 && $this->db->field_exists('total', $table) && $this->db->field_exists('currency', $table) && $this->db->field_exists('jackpot', $table) 
                 && $this->db->field_exists('jackpotContribute', $table) && $this->db->field_exists('denom', $table) && $this->db->field_exists('lastModifyTime', $table) 
                 && $this->db->field_exists('gameName', $table) && $this->db->field_exists('playerIp', $table) && $this->db->field_exists('clientType', $table) 
                 && $this->db->field_exists('hasFreegame', $table) && $this->db->field_exists('hasGamble', $table) && $this->db->field_exists('systemTakeWin', $table) 
                 && $this->db->field_exists('err_text', $table)) {
                    $this->dbforge->modify_column($table, $fields);
                }
            }
        }
    }

    public function down() {
    }
}