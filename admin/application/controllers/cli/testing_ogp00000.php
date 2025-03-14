<?php

require_once dirname(__FILE__) . '/base_testing_ogp.php';

/**
 * http://admin.og.local/cli/Testing_ogp00000/index/testAll
 * http://admin.og.local/cli/Testing_ogp00000/index/testCasePlan
 * http://admin.og.local/cli/Testing_ogp00000/index/test_catchTestSampling/159440
 *
 * ~/Code/og$ php admin/public/index.php cli/Testing_ogp00000/testAll
 * testCasePlan::will start...
 * test_catchTestSampling::will start...
 * test with unit::run(): false
 * returnText(): 'test 2 in testCasePlan()'
 * Fount result, array (
 *   'playerId' => 159500,
 *   'playerUsername' => 'sz2018',
 * )
 *
 * ~/Code/og$ php admin/public/index.php cli/Testing_ogp00000/testCasePlan
 * test with unit::run(): false
 * returnText(): 'test 2 in testCasePlan()'
 *
 * ~/Code/og$ php admin/public/index.php cli/Testing_ogp00000/test_catchTestSampling/159440
 * Fount result, array (
 *   'playerId' => '159440',
 *   'playerUsername' => 'mengke2999',
 * )
 *
 */
class Testing_ogp00000 extends BaseTestingOGP {

	public function __construct() {
		parent::__construct();
	}


	/**
	 * Sample Test
	 *
	 * URI, http://admin.og.local/cli/Testing_ogp00000/index/testCasePlan
	 *
	 * Cli,
	 * php admin/public/index.php cli/Testing_ogp00000/testCasePlan
	 *
	 * @param integer $specNo
	 * @return void
	 */
	public function testCasePlan(){

		$note = 'test 1 in testCasePlan()';


		// run($test, $expected = TRUE, $test_name = 'undefined', $notes = '', &$result=false) {
		$this->unit->run(1234 // result: 1234 will be failed, 23990 will be passed.
						, 23990 // expect
						, 'test with unit::run()'
						, $note
						, $rlt
					);
		$this->returnText('test with unit::run(): '.var_export($rlt, true));

		$note = 'test 2 in testCasePlan()';
		$this->test( true // result
			, true // expect
			, __METHOD__ // title
			, $note // note
		);
		$this->returnText('returnText(): '.var_export($note, true));
	}

	/**
	 * Get Sampling Data,
	 *
	 * Whos beting during date range and everyday.
	 * The response contains player info (VIP Group F.K.) during date range and The Betted Games info.
	 *
	 * URI, http://admin.og.local/cli/Testing_ogp00000/index/test_catchTestSampling/159440
	 *
	 * The cli,
	 * php admin/public/index.php cli/Testing_ogp00000/index/test_catchTestSampling "159440"| w3m -T text/html
	 *
	 *
	 */
	public function test_catchTestSampling($playerId=null){

		$this->load->model(['player_model']);
		if( empty($playerId) ){
			$playerId = 159500;
		}

		$playerInfo = $this->player_model->getPlayerArrayById($playerId);

		$result = [];
		$result['playerId'] = $playerId;
		$result['playerUsername'] = $playerInfo['username'];

		// for html table format
		$this->test( true // result
			, true // expect
			, __METHOD__ // title
			, var_export($result, true) // note
		);
		// just output string
		$this->returnText('Fount result, '.var_export($result, true));
	} // EOF test_catchTestSampling
}