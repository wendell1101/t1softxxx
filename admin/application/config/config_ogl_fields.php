<?php

$config['ogl_table_fields'] = [];

//=======pragmaticplay_idr1_game_logs==============
$config['ogl_table_fields']['pragmaticplay_idr1_game_logs']=[
	'enabled'=>false,
	'id_field'=>'id',
	'index_list'=>[
		['index_name'=>'idx_related_uniqueid',
		'index_field'=>'related_uniqueid',
		'unique_index'=>false,],
		['index_name'=>'idx_external_uniqueid',
		'index_field'=>'external_uniqueid',
		'unique_index'=>true,],
		['index_name'=>'idx_end_date',
		'index_field'=>'end_date',
		'unique_index'=>false,],
		['index_name'=>'idx_start_date',
		'index_field'=>'start_date',
		'unique_index'=>false,],
		['index_name'=>'idx_sbeplayerid',
		'index_field'=>'sbeplayerid',
		'unique_index'=>false,],
	],
	'fields'=>[
		'id' => array(
			'type' => 'BIGINT',
			'null' => false,
			'auto_increment' => TRUE,
		),
		'sbeplayerid' => array(
			'type' => 'INT',
			'null' => true,
		),
		'username' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'playerid' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'extplayerid' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'gameid' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'playsessionid' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'timestamp' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'referenceid' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'type' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'amount' => array(
			'type' => 'DOUBLE',
			'null' => true
		),
		'currency' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'related_uniqueid' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'last_sync_time' => array(
			'type' => 'DATETIME',
			'null' => true,
		),
	   'parent_session_id' => array(
			'type' => 'VARCHAR',
			'constraint' => '50',
			'null' => true
		),
	   'start_date' => array(
			'type' => 'DATETIME',
			'null' => true
		),
		'end_date' => array(
			'type' => 'DATETIME',
			'null' => true
		),
		'status' => array(
			'type' => 'VARCHAR',
			'constraint' => '5',
			'null' => true
		),
	   'type_game_round' => array(
			'type' => 'VARCHAR',
			'constraint' => '5',
			'null' => true
		),
	   'bet' => array(
			'type' => 'DOUBLE',
			'null' => true
		),
		'win' => array(
			'type' => 'DOUBLE',
			'null' => true
		),
	   'jackpot' => array(
			'type' => 'DOUBLE',
			'null' => true,
		),
		'md5_sum' => array(
			'type' => 'VARCHAR',
			'constraint' => '32',
			'null' => true,
		),
		'external_uniqueid' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'response_result_id' => array(
			'type' => 'VARCHAR',
			'constraint' => '100',
			'null' => true,
		),
		'after_balance'=>array(
			'type' => 'DOUBLE',
			'null' => true
		),
	],
];
//=======pragmaticplay_idr1_game_logs==============

