<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_pt_v3_game_logs_20211029 extends CI_Migration
{
    private $tableName = 'pt_v3_game_logs';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ),
            'playername' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'windowcode' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'gameid' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'gamecode' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'gametype' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'gamename' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'sessionid' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'currencycode' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'win' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'progressivebet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'progressivewin' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'currentbet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'gamedate' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'livenetwork' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            # SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'null' => true
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if(!$this->utils->table_really_exists($this->tableName))
        {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_playername', 'playername');
            $this->player_model->addIndex($this->tableName, 'idx_gameid', 'gameid');
            $this->player_model->addIndex($this->tableName, 'idx_gamecode', 'gamecode');
            $this->player_model->addIndex($this->tableName, 'idx_gamedate', 'gamedate');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName))
        {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}