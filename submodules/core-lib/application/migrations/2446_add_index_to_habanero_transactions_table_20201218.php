<?php

defined("BASEPATH") OR exit("No direct script access allowed");


class Migration_add_index_to_habanero_transactions_table_20201218 extends CI_Migration
{
    public function up() {
		$tables = array(
            'habanero_transactions',
            'habanero_transactions_idr1',
			'habanero_transactions_idr2',
			'habanero_transactions_idr3',
			'habanero_transactions_idr4',
			'habanero_transactions_idr5',
			'habanero_transactions_idr6',
            'habanero_transactions_idr7',
            
            'habanero_transactions_cny1',
			'habanero_transactions_cny2',
			'habanero_transactions_cny3',
			'habanero_transactions_cny4',
			'habanero_transactions_cny5',
			'habanero_transactions_cny6',
            'habanero_transactions_cny7',
            
            'habanero_transactions_myr1',
            'habanero_transactions_myr2',
            'habanero_transactions_myr3',
            'habanero_transactions_myr4',
            'habanero_transactions_myr5',
            'habanero_transactions_myr6',
            'habanero_transactions_myr7',

            'habanero_transactions_thb1',
            'habanero_transactions_thb2',
            'habanero_transactions_thb3',
            'habanero_transactions_thb4',
            'habanero_transactions_thb5',
            'habanero_transactions_thb6',
            'habanero_transactions_thb7',

            'habanero_transactions_usd1',
            'habanero_transactions_usd2',
            'habanero_transactions_usd3',
            'habanero_transactions_usd4',
            'habanero_transactions_usd5',
            'habanero_transactions_usd6',
            'habanero_transactions_usd7',

            'habanero_transactions_vnd1',
            'habanero_transactions_vnd2',
            'habanero_transactions_vnd3',
            'habanero_transactions_vnd4',
            'habanero_transactions_vnd5',
            'habanero_transactions_vnd6',
            'habanero_transactions_vnd7',

        );
        
        $this->load->model("player_model");
        foreach($tables as $table){
            if($this->utils->table_really_exists($table)){
                # add index
                $this->player_model->addIndex($table,"idx_friendlygameinstanceid","friendlygameinstanceid");
            }
        }
        
	}

	public function down() {
		
	}
}