<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_fast_track_bonus_crediting_20201202 extends CI_Migration {

    private $tableName = 'fast_track_bonus_crediting';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true,
                'unsigned' => true,
            ],
            'playerId' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false,
            ],
            'promorulesId' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'expire_date' => [
                'type' => 'DATETIME',
                'null' => false,
            ]
        ];

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id',TRUE);
            $this->dbforge->create_table($this->tableName);

            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_created_at","created_at");
            $this->player_model->addIndex($this->tableName,"idx_expire_date","expire_date");
        }
    }

    public function down()
    {
        if($this->utils->table_really_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }        
    }
}