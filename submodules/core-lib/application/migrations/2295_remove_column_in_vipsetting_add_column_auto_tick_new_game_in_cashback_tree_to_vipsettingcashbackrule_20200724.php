<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_remove_column_in_vipsetting_add_column_auto_tick_new_game_in_cashback_tree_to_vipsettingcashbackrule_20200724 extends CI_Migration
{
    private $vipsettingcashbackrule = 'vipsettingcashbackrule';
    private $vipsetting = 'vipsetting';

    public function up() {

        $fieldsToAdd = array(
            'auto_tick_new_game_in_cashback_tree' => array(
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            ),
        );

        # add field
        if($this->utils->table_really_exists($this->vipsettingcashbackrule)){
            if(!$this->db->field_exists('auto_tick_new_game_in_cashback_tree', $this->vipsettingcashbackrule)){
                $this->dbforge->add_column($this->vipsettingcashbackrule, $fieldsToAdd);
            }
        }

        # remove field
        if($this->utils->table_really_exists($this->vipsetting)){
            if($this->db->field_exists('auto_tick_new_game_in_cashback_tree', $this->vipsetting)){
                $this->dbforge->drop_column($this->vipsetting, 'auto_tick_new_game_in_cashback_tree');
            }
        }
    }

    public function down() {}
}