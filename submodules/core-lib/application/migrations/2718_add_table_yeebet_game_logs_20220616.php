<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_yeebet_game_logs_20220616 extends CI_Migration {

    private $tableName = 'yeebet_game_logs';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'gameid' => array(
                'type' => 'INT',
                'constraint' => '50',
                'null' => true,
            ),
            'createtime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'serialnumber' => array(
                'type' => 'INT',
                'constraint' => '50',
                'null' => true,
            ),
            'userstatus' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'betpoint' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'betodds' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'userid' => array(
                'type' => 'INT',
                'constraint' => '50',
                'null' => true,
            ),
            'commamount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'gameroundid' => array(
                'type' => 'INT',
                'constraint' => '50',
                'null' => true,
            ),
            'uid' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'settletime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'gameresult' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'winlost' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'gametype' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'state' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'gameno' => array(
                'type' => 'INT',
                'constraint' => '50',
                'null' => true,
            ),
            'bettype' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'cid' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'betamount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),

            # SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
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

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_gameid', 'gameid');
            $this->player_model->addIndex($this->tableName, 'idx_gameno', 'gameno');
            $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            $this->player_model->addIndex($this->tableName, 'idx_createtime', 'createtime');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}