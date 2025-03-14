<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Gets platform code
 * * Login/logout to the website
 * * Create Player
 * * Update Player's info
 * * Delete Player
 * * Block/Unblock Player
 * * Deposit to Game
 * * Withdraw from Game
 * * Check Player's balance
 * * Check Game Records
 * * Computes Total Betting Amount
 * * Check Transaction
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Get BBIN Records
 * * Extract xml record
 * * Synchronize Game Records
 * * Check Player's Balance
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Game_api_bbin extends Abstract_game_api {

	private $bbin_api_url;
	private $bbin_mywebsite;
	private $bbin_create_member;
	private $bbin_login_member;
	private $bbin_logout_member;
	private $bbin_check_member_balance;
	private $bbin_transfer;
	private $bbin_getbet;
	private $bbin_uppername;
	private $url_default;
	private $url_login;
	private $conversion_rate;
	private $bbin_play_game;
	private $bbin_casino_event_game;
	private $bbin_live_event_game;
	private $use_new_version;
	private  $bbin_check_transfer;

	private $default_fisharea_game_code;

	#mobile
	private  $enable_mobile_api;
	private  $bbin_mobile_api_url;
	private  $bbin_mobile_api_create_user_endpoint;
	private  $bbin_mobile_api_change_password_endpoint;
	private  $bbin_mobile_site_id;
	private  $bbin_mobile_keyb;
	private  $bbin_demo_link;

    public $enable_pulling_wagers_record_by_76; # Get bet record of XBB Casino.

	const START_PAGE = 0;
	const ITEM_PER_PAGE = 500;
	const API_BUSY = 44003;
	const SYSTEM_MAINTENANCE = 44444;
	const BBIN_BATTLE = 66;

	//'JLQ3',
	const DEFAULT_LOTTERY_KINDS=[
		'LT', 'BBQL', 'BBLT', 'BBRB', 'BB3D', 'BJ3D','PL3D',
		'SH3D', 'BBHL', 'BBAD', 'BBGE', 'LDDR', 'LDRS', 'BBLM',
		'LKPA', 'BCRA', 'BCRB', 'BCRC', 'BCRD', 'BCRE',
		'BJPK', 'BBPK', 'RDPK', 'GDE5', 'JXE5', 'SDE5',
		'CQSC', 'XJSC', 'TJSC', 'JSQ3', 'AHQ3', 'BBQK',
		'BBKN', 'CAKN', 'BJKN', 'CQSF', 'TJSF', 'GXSF',
		'CQWC', 'OTHER','LK28'];

	const BBIN_GAME_PROPERTY = array(
		'bb_sports' => array('game_kind' => 1, 'lose_type' => 'L', 'game_type_name' => 'bb_sports', 'game_type_id' => 33),
		'lottery' => array('game_kind' => 12, 'lose_type' => 'L', 'game_type_name' => 'lottery', 'game_type_id' => 34),
	// '3d_hall' => array('game_kind' => 15, 'lose_type' => '200', 'game_type_name' => '3d_hall', 'game_type_id' => 35),
		'live' => array('game_kind' => 3, 'lose_type' => '200', 'game_type_name' => 'live', 'game_type_id' => 36),
		'casino' => array('game_kind' => 5, 'lose_type' => '200', 'game_type_name' => 'casino', 'game_type_id' => 37),
		'fish_hunter2' => array('game_kind' => 30, 'lose_type' => 'L', 'game_type_name' => 'fishing', 'game_type_id' => 30598),
		'fish_hunter' => array('game_kind' => 30, 'lose_type' => 'L', 'game_type_name' => 'fishing', 'game_type_id' => 30599),
		'mammon_fishing' => array('game_kind' => 30, 'lose_type' => 'L', 'game_type_name' => 'fishing', 'game_type_id' => 30596),
		'fishing_master' => array('game_kind' => 38, 'lose_type' => 'L', 'game_type_name' => 'fishing', 'game_type_id' => 38001),
		'fishing_master2' => array('game_kind' => 38, 'lose_type' => 'L', 'game_type_name' => 'fishing', 'game_type_id' => 38002),
		'golden_boy_fishing' => array('game_kind' => 38, 'lose_type' => 'L', 'game_type_name' => 'fishing', 'game_type_id' => 30593),
		'demon_buster_fishing' => array('game_kind' => 30, 'lose_type' => 'L', 'game_type_name' => 'fishing', 'game_type_id' => 30595),
        'xbb_live_games' => array('game_kind' => 75, 'lose_type' => 'L', 'game_type_name' => 'xbb', 'game_type_id' => 30593),
        'xbb_casino' => array('game_kind' => 76, 'lose_type' => 'L', 'game_type_name' => 'xbb', 'game_type_id' => 76062),
	);

	const BBIN_GAMETYPE = array('33' => 'ball', '34' => 'Ltlottery', '35' => '3DHall', '3' => 'live', '37' => 'game',
		'fish' => 'fisharea', 'fish_hunter' => 1, 'fish_hunter2' => '30599', 'fishing_master' => 2,'fishevent' => 'fishevent','casinoevent' => 'casinoevent','liveevent' => 'liveevent','5' => 'game', '30' => 'fisharea', '38' => 'fisharea', '1' => 'ball', '12' => 'Ltlottery');
	const GAME_FISHHUNTER = 1;
	const GAME_FISHMASTER = 2;
	const GAME_SLOTS = 3;
	const GAME_FISHAREA = 'fisharea';
	const GAME_SPORTS = 'ball';
	const BBIN_GAME_TYPE_BBP_CASINO = "BBP_CASINO";
	const EXIT_OPT_REDIRECT = 2;

	const EVENT_CASINO = "casinoevent";
	const EVENT_LIVE = "liveevent";
	const EVENT_FISHING = "fishevent";
	const FISHING_GAME = array('fish_hunter' => 'WagersRecordBy30', 'fishing_master' => 'WagersRecordBy38');

	const MD5_FIELDS_FOR_ORIGINAL=['WagersID', 'BetAmount', 'WagersDate', 'Payoff', 'UserName', 'Result', 'PayoutTime', 'IsPaid','ResultType','ModifiedDate',"GameType"];
	const MD5_FLOAT_AMOUNT_FIELDS=['BetAmount', 'Payoff',];

	const MD5_FIELDS_FOR_MERGE=['wagers_id', 'bet_amount', 'wagers_date', 'payoff', 'username','result', 'payout_time', 'is_paid','flag',"game_type","jp_amount"];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount', 'payoff', 'jp_amount'];

	# FOR CASINO EVENT
	const MD5_FIELDS_FOR_ORIGINAL_CASINO_EVENT=['ID', 'CreateTime', 'Amount', 'UserName'];
	const MD5_FLOAT_AMOUNT_FIELDS_CASINO_EVENT=['Amount'];
	const GAMETYPE_FISHING = [30599,38001,30598];
	const API_syncJackpotRecords = "syncJackpotRecords";

	# FOR LIVE DEALER
	const GAME_TYPE_BACCARAT = 3001;
	const GAME_TYPE_DRAGON_TIGER = 3003;
	const GAME_TYPE_3_FACE = 3005;
	const GAME_TYPE_WENZHOU = 3006;
	const GAME_TYPE_ROULETTE = 3007;
	const GAME_TYPE_SIC_BO = 3008;
	const GAME_TYPE_SE_DIE = 3011;
	const GAME_TYPE_BULL_BULL = 3012;
	const GAME_TYPE_FAN_TAN = 3015;
	const GAME_TYPE_FPC_DICE = 3016;
	const GAME_TYPE_INSURANCE_BACCARAT = 3017;
	const GAME_TYPE_3_CARD_POKER = 3018;
	const GAME_TYPE_HILO = 3021;
	const GAME_TYPE_BC_BACCARAT = 3025;
	const GAME_TYPE_BC_DRAGON_TIGER = 3026;
	const GAME_TYPE_BC_INSURANCE_BACCARAT = 3027;
	const GAME_TYPE_BC_ROULETTE = 3028;
	const GAME_TYPE_BC_3_CARD_POKER = 3029;
	const GAME_TYPE_BC_3_FACE = 3030;
	const GAME_TYPE_BC_BULL_BULL = 3031;
	const GAME_TYPE_BC_SE_DIE = 3032;
	const GAME_TYPE_BC_SIC_BO = 3033;
	const GAME_TYPE_BC_FPC_DICE = 3034;
	const GAME_TYPE_BC_HILO = 3036;
	const GAME_TYPE_BC_FAN_TAN = 3037;
	const GAME_TYPE_BC_WENZHOU = 3038;
	const GAME_TYPE_BC_FDC_DICE_VN = 3039;
	const GAME_TYPE_BC_FDC_DICE_TH = 3040;

	const GAME_RULES_PROPERTY = array(
		self::GAME_TYPE_BACCARAT => array(1 => "Banker", 2 => "Player", 3 => "Tie", 4 => "Banker Pair", 5 => "Player Pair", 6 => "Big",
									7 => "Small", 12 => "Either Pair", 13 => "Perfect Pair", 14 => "Banker(No Commission)", 15 => "Super Six(No Commission)"),
		self::GAME_TYPE_DRAGON_TIGER => array(1 => "Tiger", 2 => "Dragon", 3 => "Tie", 4 => "Tiger Odd", 5 => "Tiger Even",
									6 => "Dragon Odd", 7 => "Dragon Even", 8 => "Tiger Black", 9 => "Tiger Red", 10 => "Dragon Black", 11 => "Dragon Red"),
		self::GAME_TYPE_3_FACE => array(1 => "Player 1 Win", 2 => "Player 1 Lose", 3 => "Player 1 Tie", 4 => "Player 1 (3 Face)", 5 => "Player 1 Pair Plus",
										6 => "Player 2 Win", 7 => "Player 2 Lose", 8 => "Player 2 Tie", 9 => "Player 2 (3 Face)", 10 => "Player 2 Pair Plus",
										11 => "Player 3 Win", 12 => "Player 3 Lose", 13 => "Player 3 Tie", 14 => "Player 3 (3 Face)", 15 => "Player 3 Pair Plus", 16 => "Banker Pair Plus"),
		self::GAME_TYPE_WENZHOU => array(1 => "Player 1 Win", 2 => "Player 1 Lose", 3 => "Player 2 Win", 4 => "Player 2 Lose", 5 => "Player 3 Win", 6 => "Player 3 Lose"),
		self::GAME_TYPE_ROULETTE => array(0=>"Straight Up(0)",1=>"Straight Up(1)",2=>"Straight Up(2)",3=>"Straight Up(3)",4=>"Straight Up(4)",5=>"Straight Up(5)",6=>"Straight Up(6)",7=>"Straight Up(7)",8=>"Straight Up(8)",9=>"Straight Up(9)",10=>"Straight Up(10)",11=>"Straight Up(11)",12=>"Straight Up(12)",13=>"Straight Up(13)",14=>"Straight Up(14)",
									15=>"Straight Up(15)",16=>"Straight Up(16)",17=>"Straight Up(17)",18=>"Straight Up(18)",19=>"Straight Up(19)",20=>"Straight Up(20)",21=>"Straight Up(21)",22=>"Straight Up(22)",23=>"Straight Up(23)",24=>"Straight Up(24)",25=>"Straight Up(25)",26=>"Straight Up(26)",27=>"Straight Up(27)",28=>"Straight Up(28)",29=>"Straight Up(29)",30=>"Straight Up(30)",
									31=>"Straight Up(31)",32=>"Straight Up(32)",33=>"Straight Up(33)",34=>"Straight Up(34)",35=>"Straight Up(35)",36=>"Straight Up(36)",37=>"Split(0,1)",38=>"Split(0,2)",39=>"Split(0,3)",40=>"Split(1,2)",41=>"Split(1,4)",42=>"Split(2,3)",43=>"Split(2,5)",44=>"Split(3,6)",
									45=>"Split(4,5)",46=>"Split(4,7)",47=>"Split(5,6)",48=>"Split(5,8)",49=>"Split(6,9)",50=>"Split(7,8)",51=>"Split(7,10)",52=>"Split(8,9)",53=>"Split(8,11)",54=>"Split(9,12)",55=>"Split(10,11)",56=>"Split(10,13)",57=>"Split(11,12)",58=>"Split(11,14)",59=>"Split(12,15)",60=>"Split(13,14)",61=>"Split(13,16)",62=>"Split(14,15)",63=>"Split(14,17)",64=>"Split(15,18)",65=>"Split(16,17)",66=>"Split(16,19)",67=>"Split(17,18)",
									68=>"Split(17,20)",69=>"Split(18,21)",70=>"Split(19,20)",71=>"Split(19,22)",72=>"Split(20,21)",73=>"Split(20,23)",74=>"Split(21,24)",75=>"Split(22,23)",76=>"Split(22,25)",77=>"Split(23,24)",78=>"Split(23,26)",79=>"Split(24,27)",80=>"Split(25,26)",81=>"Split(25,28)",82=>"Split(26,27)",83=>"Split(26,29)",84=>"Split(27,30)",85=>"Split(28,29)",86=>"Split(28,31)",87=>"Split(29,30)",88=>"Split(29,32)",
									89=>"Split(30,33)",90=>"Split(31,32)",91=>"Split(31,34)",92=>"Split(32,33)",93=>"Split(32,35)",94=>"Split(33,36)",95=>"Split(34,35)",96=>"Split(35,36)",97=>"Street(1,2,3)",98=>"Street(4,5,6)",99=>"Street(7,8,9)",100=>"Street(10,11,12)",101=>"Street(13,14,15)",102=>"Street(16,17,18)",103=>"Street(19,20,21)",104=>"Street(22,23,24)",105=>"Street(25,26,27)",106=>"Street(28,29,30)",107=>"Street(31,32,33)",
									108=>"Street(34,35,36)",109=>"Triple(0,1,2)",110=>"Triple(0,2,3)",111=>"Corner(1,2,4,5)",112=>"Corner(2,3,5,6)",113=>"Corner(4,5,7,8)",114=>"Corner(5,6,8,9)",115=>"Corner(7,8,10,11)",116=>"Corner(8,9,11,12)",117=>"Corner(10,11,13,14)",118=>"Corner(11,12,14,15)",119=>"Corner(13,14,16,17)",120=>"Corner(14,15,17,18)",121=>"Corner(16,17,19,20)",122=>"Corner(17,18,20,21)",123=>"Corner(19,20,22,23)",124=>"Corner(20,21,23,24)",
									125=>"Corner(22,23,25,26)",126=>"Corner(23,24,26,27)",127=>"Corner(25,26,28,29)",128=>"Corner(26,27,29,30)",129=>"Corner(28,29,31,32)",130=>"Corner(29,30,32,33)",131=>"Corner(31,32,34,35)",132=>"Corner(32,33,35,36)",133=>"Four(0,1,2,3)",134=>"Line(1,2,3,4,5,6)",135=>"Line(4,5,6,7,8,9)",136=>"Line(7,8,9,10,11,12)",137=>"Line(10,11,12,13,14,15)",138=>"Line(13,14,15,16,17,18)",139=>"Line(16,17,18,19,20,21)",140=>"Line(19,20,21,22,23,24)",
									141=>"Line(22,23,24,25,26,27)",142=>"Line(25,26,27,28,29,30)",143=>"Line(28,29,30,31,32,33)",144=>"Line(31,32,33,34,35,36)",145=>"Column1",146=>"Column2",147=>"Column3",148=>"Dozen1",149=>"Dozen2",150=>"Dozen3",151=>"Red/Black(Red)",152=>"Red/Black(Black)",153=>"Odd/Even(Odd)",154=>"Odd/Even(Even)",155=>"High/Low(1-18)",156=>"High/Low(19-36)"),
		self::GAME_TYPE_SIC_BO => array(1=>'Big/Small(Small)',2=>'Big/Small(Big)',4=>'Points/4Point',5=>'Points/5Point',6=>'Points/6Point',7=>'Points/7Point',8=>'Points/8Point',9=>'Points/9Point',10=>'Points/10Point',11=>'Points/11Point',12=>'Points/12Point',13=>'Points/13Point',14=>'Points/14Point',15=>'Points/15Point',16=>'Points/16Point',17=>'Points/17Point',18=>'Two Dice Combination(1,2)',19=>'Two Dice Combination(1,3)',20=>'Two Dice Combination(1,4)',21=>'Two Dice Combination(1,5)',
									22=>'Two Dice Combination(1,6)',23=>'Two Dice Combination(2,3)',24=>'Two Dice Combination(2,4)',25=>'Two Dice Combination(2,5)',26=>'Two Dice Combination(2,6)',27=>'Two Dice Combination(3,4)',28=>'Two Dice Combination(3,5)',29=>'Two Dice Combination(3,6)',30=>'Two Dice Combination(4,5)',31=>'Two Dice Combination(4,6)',32=>'Two Dice Combination(5,6)',33=>'Specific Double(1,1)',34=>'Specific Double(2,2)',35=>'Specific Double(3,3)',36=>'Specific Double(4,4)',37=>'Specific Double(5,5)',38=>'Specific Double(6,6)',39=>'Specific Triples(1,1,1)',40=>'Specific Triples(2,2,2)',41=>'Specific Triples(3,3,3)',42=>'Specific Triples4,4,4)',43=>'Specific Triples(5,5,5)',44=>'Specific Triples(6,6,6)',45=>'Any Triple',46=>'One of A Kind(1)',47=>'One of A Kind(2)',48=>'One of A Kind(3)',49=>'One of A Kind(4)',50=>'One of A Kind(5)',51=>'One of A Kind(6)',52=>'Odd/Even(Odd)',53=>'Odd/Even(Even)'),
		self::GAME_TYPE_SE_DIE => array ( 1 => '4 white', 2 => '4 red', 3 => '3 white 1 red', 4 => '3 red 1 white', 5 => 'Odd', 6 => 'Even'),
		self::GAME_TYPE_BULL_BULL => array ( 1 => 'Player 1 Equal', 2 => 'Player 1 Double', 3 => 'Player 1 Prepaid Amount', 4 => 'Player 2 Equal', 5 => 'Player 2 Double', 6 => 'Player 2 Prepaid Amount', 7 => 'Player 3 Equal', 8 => 'Player 3 Double', 9 => 'Player 3 Prepay Amount' ),
		self::GAME_TYPE_FAN_TAN => array ( 1 => 'One Fan', 2 => 'Two Fan', 3 => 'Three Fan', 4 => 'Four Fan', 5 => '1 Nim 2', 6 => '1 Nim 3', 7 => '1 Nim 4', 8 => '2 Nim 1', 9 => '2 Nim 3', 10 => '2 Nim 4', 11 => '3 Nim 1', 12 => '3 Nim 2', 13 => '3 Nim 4', 14 => '4 Nim 1', 15 => '4 Nim 2', 16 => '4 Nim 3', 17 => 'Kwok (1,2)', 18 => 'Kwok (2,3)', 19 => 'Kwok (3,4)', 20 => 'Kwok (4,1)',
										21 => '2,3 One Nga', 22 => '2,4 One Nga', 23 => '3,4 One Nga', 24 => '1,3 Two Nga', 25 => '1,4 Two Nga', 26 => '3,4 Two Nga', 27 => '1,2 Three Nga', 28 => '1,4 Three Nga', 29 => '2,4 Three Nga', 30 => '1,2 Four Nga', 31 => '1,3 Four Nga', 32 => '2,3 Four Nga', 33 => 'Ssh (4,3,2)', 34 => 'Ssh (1,4,3)', 35 => 'Ssh (2,1,4)', 36 => 'Ssh (3,2,1)', 37 => 'Odd', 38 => 'Even' ),
		self::GAME_TYPE_FPC_DICE => array ( 1 => 'Big/Small(Small)', 2 => 'Big/Small(Big)', 4 => 'Points/4Point', 5 => 'Points/5Point', 6 => 'Points/6Point', 7 => 'Points/7Point', 8 => 'Points/8Point', 9 => 'Points/9Point', 10 => 'Points/10Point', 11 => 'Points/11Point', 12 => 'Points/12Point', 13 => 'Points/13Point',
										14 => 'Points/14Point', 15 => 'Points/15Point', 16 => 'Points/16Point', 17 => 'Points/17Point', 18 => 'Designate a color on 1 dice(Green)', 19 => 'Designate a color on 1 dice(Blue)', 20 => 'Designate a color on 1 dice(Red)', 21 => 'Designate a color on 2 dice(Green)',
										22 =>'Designate a color on 2 dice(Blue)', 23 => 'Designate a color on 2 dice(Red)', 24 => 'Designate a color on 3 dice(Green)', 25 => 'Designate a color on 3 dice(Blue)', 26 => 'Designate a color on 3 dice(Red)', 27 => 'A color on 3 dice', 28 => 'Specific Triples(1,1,1)', 29 => 'Specific Triples(2,2,2)', 30 => 'Specific Triples(3,3,3)', 31 => 'Specific Triples4,4,4)', 32 => 'Specific Triples(5,5,5)', 33 => 'Specific Triples(6,6,6)', 34 => 'Any Triple', 35 => 'One of A Kind(1)', 36 => 'One of A Kind(2)', 37 => 'One of A Kind(3)', 38 => 'One of A Kind(4)', 39 => 'One of A Kind(5)', 40 => 'One of A Kind(6)', 41 => 'Odd/Even(Odd)', 42 => 'Odd/Even(Even)' ),
		self::GAME_TYPE_INSURANCE_BACCARAT => array ( 1 => 'Banker', 2 => 'Player', 3 => 'Tie', 4 => 'Banker Pair', 5 => 'Player Pair', 6 => 'Big', 7 => 'Small', 16 => 'Banker Insurance', 17 => 'Player Insurance' ),
		self::GAME_TYPE_3_CARD_POKER => array ( 1 => 'Dragon', 2 => 'Phoenix', 4 => 'Pair 9 Plus', 8 => 'Straight', 16 => 'Flush', 32 => 'Straight Flush', 64 => 'Any Triple' ),
		self::GAME_TYPE_HILO => array ( 1 => 'High', 2 => 'Snap', 3 => 'Low', 4 => '2/3/4/5', 5 => '6/7/8/9', 6 => 'J/Q/K/A', 7 => 'Red', 8 => 'Black', 9 => 'Odd', 10 => 'Even' ),
		self::GAME_TYPE_BC_BACCARAT => array ( 1 => 'Banker', 2 => 'Player', 3 => 'Tie', 4 => 'Banker Pair', 5 => 'Player Pair', 6 => 'Big', 7 => 'Small', 12 => 'Either Pair', 13 => 'Perfect Pair', 14 => 'Banker(No-Commission)', 15 => 'Super Six(No-Commission)' ),
		self::GAME_TYPE_BC_DRAGON_TIGER => array ( 1 => 'Tiger', 2 => 'Dragon', 3 => 'Tie', 4 => 'Tiger Odd', 5 => 'Tiger Even', 6 => 'Dragon Odd', 7 => 'Dragon Even', 8 => 'Tiger Black', 9 => 'Tiger Red', 10 => 'Dragon Black', 11 => 'Dragon Red' ),
		self::GAME_TYPE_BC_INSURANCE_BACCARAT => array ( 1 => 'Banker', 2 => 'Player', 3 => 'Tie', 4 => 'Banker Pair', 5 => 'Player Pair', 6 => 'Big', 7 => 'Small', 16 => 'Banker Insurance', 17 => 'Player Insurance' ),
		self::GAME_TYPE_BC_ROULETTE => array ( 0 => 'Straight Up(0)', 1 => 'Straight Up(1)', 2 => 'Straight Up(2)', 3 => 'Straight Up(3)', 4 => 'Straight Up(4)', 5 => 'Straight Up(5)', 6 => 'Straight Up(6)', 7 => 'Straight Up(7)', 8 => 'Straight Up(8)', 9 => 'Straight Up(9)', 10 => 'Straight Up(10)', 11 => 'Straight Up(11)', 12 => 'Straight Up(12)', 13 => 'Straight Up(13)', 14 => 'Straight Up(14)', 15 => 'Straight Up(15)', 16 => 'Straight Up(16)',
										17 => 'Straight Up(17)', 18 => 'Straight Up(18)', 19 => 'Straight Up(19)', 20 => 'Straight Up(20)', 21 => 'Straight Up(21)', 22 => 'Straight Up(22)', 23 => 'Straight Up(23)', 24 => 'Straight Up(24)', 25 => 'Straight Up(25)', 26 => 'Straight Up(26)', 27 => 'Straight Up(27)', 28 => 'Straight Up(28)', 29 => 'Straight Up(29)', 30 => 'Straight Up(30)', 31 => 'Straight Up(31)', 32 => 'Straight Up(32)', 33 => 'Straight Up(33)', 34 => 'Straight Up(34)', 35 => 'Straight Up(35)',
										36 => 'Straight Up(36)', 37 => 'Split(0,1)', 38 => 'Split(0,2)', 39 => 'Split(0,3)', 40 => 'Split(1,2)', 41 => 'Split(1,4)', 42 => 'Split(2,3)', 43 => 'Split(2,5)', 44 => 'Split(3,6)', 45 => 'Split(4,5)', 46 => 'Split(4,7)', 47 => 'Split(5,6)', 48 => 'Split(5,8)', 49 => 'Split(6,9)', 50 => 'Split(7,8)', 51 => 'Split(7,10)', 52 => 'Split(8,9)', 53 => 'Split(8,11)', 54 => 'Split(9,12)', 55 => 'Split(10,11)', 56 => 'Split(10,13)', 57 => 'Split(11,12)', 58 => 'Split(11,14)',
										59 => 'Split(12,15)', 60 => 'Split(13,14)', 61 => 'Split(13,16)', 62 => 'Split(14,15)', 63 => 'Split(14,17)', 64 => 'Split(15,18)', 65 => 'Split(16,17)', 66 => 'Split(16,19)', 67 => 'Split(17,18)', 68 => 'Split(17,20)', 69 => 'Split(18,21)', 70 => 'Split(19,20)', 71 => 'Split(19,22)', 72 => 'Split(20,21)', 73 => 'Split(20,23)', 74 => 'Split(21,24)', 75 => 'Split(22,23)', 76 => 'Split(22,25)', 77 => 'Split(23,24)', 78 => 'Split(23,26)', 79 => 'Split(24,27)', 80 => 'Split(25,26)',
										81 => 'Split(25,28)', 82 => 'Split(26,27)', 83 => 'Split(26,29)', 84 => 'Split(27,30)', 85 => 'Split(28,29)', 86 => 'Split(28,31)', 87 => 'Split(29,30)', 88 => 'Split(29,32)', 89 => 'Split(30,33)', 90 => 'Split(31,32)', 91 => 'Split(31,34)', 92 => 'Split(32,33)', 93 => 'Split(32,35)', 94 => 'Split(33,36)', 95 => 'Split(34,35)', 96 => 'Split(35,36)', 97 => 'Street(1,2,3)', 98 => 'Street(4,5,6)', 99 => 'Street(7,8,9)', 100 => 'Street(10,11,12)', 101 => 'Street(13,14,15)',
										102 => 'Street(16,17,18)', 103 => 'Street(19,20,21)', 104 => 'Street(22,23,24)', 105 => 'Street(25,26,27)', 106 => 'Street(28,29,30)', 107 => 'Street(31,32,33)', 108 => 'Street(34,35,36)', 109 => 'Triple(0,1,2)', 110 => 'Triple(0,2,3)', 111 => 'Corner(1,2,4,5)', 112 => 'Corner(2,3,5,6)', 113 => 'Corner(4,5,7,8)', 114 => 'Corner(5,6,8,9)', 115 => 'Corner(7,8,10,11)', 116 => 'Corner(8,9,11,12)', 117 => 'Corner(10,11,13,14)', 118 => 'Corner(11,12,14,15)', 119 => 'Corner(13,14,16,17)',
										120 => 'Corner(14,15,17,18)', 121 => 'Corner(16,17,19,20)', 122 => 'Corner(17,18,20,21)', 123 => 'Corner(19,20,22,23)', 124 => 'Corner(20,21,23,24)', 125 => 'Corner(22,23,25,26)', 126 => 'Corner(23,24,26,27)', 127 => 'Corner(25,26,28,29)', 128 => 'Corner(26,27,29,30)', 129 => 'Corner(28,29,31,32)', 130 => 'Corner(29,30,32,33)', 131 => 'Corner(31,32,34,35)', 132 => 'Corner(32,33,35,36)', 133 => 'Four(0,1,2,3)', 134 => 'Line(1,2,3,4,5,6)', 135 => 'Line(4,5,6,7,8,9)', 136 => 'Line(7,8,9,10,11,12)',
										137 => 'Line(10,11,12,13,14,15)', 138 => 'Line(13,14,15,16,17,18)', 139 => 'Line(16,17,18,19,20,21)', 140 => 'Line(19,20,21,22,23,24)', 141 => 'Line(22,23,24,25,26,27)', 142 => 'Line(25,26,27,28,29,30)', 143 => 'Line(28,29,30,31,32,33)', 144 => 'Line(31,32,33,34,35,36)', 145 => 'Column1', 146 => 'Column2', 147 => 'Column3', 148 => 'Dozen1', 149 => 'Dozen2', 150 => 'Dozen3', 151 => 'Red/Black(Red)', 152 => 'Red/Black(Black)', 153 => 'Odd/Even(Odd)', 154 => 'Odd/Even(Even)', 155 => 'High/Low(1-18)', 156 => 'High/Low(19-36)' ),
		self::GAME_TYPE_BC_3_CARD_POKER => array ( 1 => 'Dragon', 2 => 'Phoenix', 4 => 'Pair 9 Plus', 8 => 'Straight', 16 => 'Flush', 32 => 'Straight Flush', 64 => 'Any Triple' ),
		self::GAME_TYPE_BC_3_FACE => array ( 1 => 'Player 1 Win', 2 => 'Player 1 Lose', 3 => 'Player 1 Tie', 4 => 'Player 1 (3 Face)', 5 => 'Player 1 Pair Plus', 6 => 'Player 2 Win', 7 => 'Player 2 Lose', 8 => 'Player 2 Tie', 9 => 'Player 2 (3 Face)', 10 => 'Player 2 Pair Plus', 11 => 'Player 3 Win', 12 => 'Player 3 Lose', 13 => 'Player 3 Tie', 14 => 'Player 3 (3 Face)', 15 => 'Player 3 Pair Plus', 16 => 'Banker Pair Plus' ),
		self::GAME_TYPE_BC_BULL_BULL => array ( 1 => 'Player 1 Equal', 2 => 'Player 1 Double', 3 => 'Player 1 Prepaid Amount', 4 => 'Player 2 Equal', 5 => 'Player 2 Double', 6 => 'Player 2 Prepaid Amount', 7 => 'Player 3 Equal', 8 => 'Player 3 Double', 9 => 'Player 3 Prepay Amount' ),
		self::GAME_TYPE_BC_SE_DIE => array ( 1 => '4 white', 2 => '4 red', 3 => '3 white 1 red', 4 => '3 red 1 white', 5 => 'Odd', 6 => 'Even' ),
		self::GAME_TYPE_BC_SIC_BO => array ( 1 => 'Big/Small(Small)', 2 => 'Big/Small(Big)', 4 => 'Points/4Point', 5 => 'Points/5Point', 6 => 'Points/6Point', 7 => 'Points/7Point', 8 => 'Points/8Point', 9 => 'Points/9Point', 10 => 'Points/10Point', 11 => 'Points/11Point', 12 => 'Points/12Point', 13 => 'Points/13Point', 14 => 'Points/14Point', 15 => 'Points/15Point', 16 => 'Points/16Point', 17 => 'Points/17Point', 18 => 'Two Dice Combination(1,2)', 19 => 'Two Dice Combination(1,3)',
										20 => 'Two Dice Combination(1,4)', 21 => 'Two Dice Combination(1,5)', 22 => 'Two Dice Combination(1,6)', 23 => 'Two Dice Combination(2,3)', 24 => 'Two Dice Combination(2,4)', 25 => 'Two Dice Combination(2,5)', 26 => 'Two Dice Combination(2,6)', 27 => 'Two Dice Combination(3,4)', 28 => 'Two Dice Combination(3,5)', 29 => 'Two Dice Combination(3,6)', 30 => 'Two Dice Combination(4,5)', 31 => 'Two Dice Combination(4,6)', 32 => 'Two Dice Combination(5,6)', 33 => 'Specific Double(1,1)', 34 => 'Specific Double(2,2)', 35 => 'Specific Double(3,3)',
										36 => 'Specific Double(4,4)', 37 => 'Specific Double(5,5)', 38 => 'Specific Double(6,6)', 39 => 'Specific Triples(1,1,1)', 40 => 'Specific Triples(2,2,2)', 41 => 'Specific Triples(3,3,3)', 42 => 'Specific Triples4,4,4)', 43 => 'Specific Triples(5,5,5)', 44 => 'Specific Triples(6,6,6)', 45 => 'Any Triple', 46 => 'One of A Kind(1)', 47 => 'One of A Kind(2)', 48 => 'One of A Kind(3)', 49 => 'One of A Kind(4)', 50 => 'One of A Kind(5)', 51 => 'One of A Kind(6)', 52 => 'Odd/Even(Odd)', 53 => 'Odd/Even(Even)' ),
		self::GAME_TYPE_BC_FPC_DICE => array ( 1 => 'Big/Small(Small)', 2 => 'Big/Small(Big)', 4 => 'Points/4Point', 5 => 'Points/5Point', 6 => 'Points/6Point', 7 => 'Points/7Point', 8 => 'Points/8Point', 9 => 'Points/9Point', 10 => 'Points/10Point', 11 => 'Points/11Point', 12 => 'Points/12Point', 13 => 'Points/13Point', 14 => 'Points/14Point', 15 => 'Points/15Point', 16 => 'Points/16Point', 17 => 'Points/17Point', 18 => 'Designate a color on 1 dice(Green)', 19 => 'Designate a color on 1 dice(Blue)', 20 => 'Designate a color on 1 dice(Red)', 21 => 'Designate a color on 2 dice(Green)',
										22 => 'Designate a color on 2 dice(Blue)', 23 => 'Designate a color on 2 dice(Red)', 24 => 'Designate a color on 3 dice(Green)', 25 => 'Designate a color on 3 dice(Blue)', 26 => 'Designate a color on 3 dice(Red)', 27 => 'A color on 3 dice', 28 => 'Specific Triples(1,1,1)', 29 => 'Specific Triples(2,2,2)', 30 => 'Specific Triples(3,3,3)', 31 => 'Specific Triples4,4,4)', 32 => 'Specific Triples(5,5,5)', 33 => 'Specific Triples(6,6,6)', 34 => 'Any Triple', 35 => 'One of A Kind(1)', 36 => 'One of A Kind(2)', 37 => 'One of A Kind(3)', 38 => 'One of A Kind(4)', 39 => 'One of A Kind(5)', 40 => 'One of A Kind(6)', 41 => 'Odd/Even(Odd)', 42 => 'Odd/Even(Even)' ),
		self::GAME_TYPE_BC_HILO => array ( 1 => 'High', 2 => 'Snap', 3 => 'Low', 4 => '2/3/4/5', 5 => '6/7/8/9', 6 => 'J/Q/K/A', 7 => 'Red', 8 => 'Black', 9 => 'Odd', 10 => 'Even' ),
		self::GAME_TYPE_BC_FAN_TAN => array ( 1 => 'One Fan', 2 => 'Two Fan', 3 => 'Three Fan', 4 => 'Four Fan', 5 => '1 Nim 2', 6 => '1 Nim 3', 7 => '1 Nim 4', 8 => '2 Nim 1', 9 => '2 Nim 3', 10 => '2 Nim 4', 11 => '3 Nim 1', 12 => '3 Nim 2', 13 => '3 Nim 4', 14 => '4 Nim 1', 15 => '4 Nim 2', 16 => '4 Nim 3', 17 => 'Kwok (1,2)', 18 => 'Kwok (2,3)', 19 => 'Kwok (3,4)', 20 => 'Kwok (4,1)', 21 => '2,3 One Nga', 22 => '2,4 One Nga', 23 => '3,4 One Nga', 24 => '1,3 Two Nga', 25 => '1,4 Two Nga',
										26 => '3,4 Two Nga', 27 => '1,2 Three Nga', 28 => '1,4 Three Nga', 29 => '2,4 Three Nga', 30 => '1,2 Four Nga', 31 => '1,3 Four Nga', 32 => '2,3 Four Nga', 33 => 'Ssh (4,3,2)', 34 => 'Ssh (1,4,3)', 35 => 'Ssh (2,1,4)', 36 => 'Ssh (3,2,1)', 37 => 'Odd', 38 => 'Even' ),
		self::GAME_TYPE_BC_WENZHOU => array ( 1 => 'Player 1 Win', 2 => 'Player 1 Lose', 3 => 'Player 2 Win', 4 => 'Player 2 Lose', 5 => 'Player 3 Win', 6 => 'Player 3 Lose' ),
		self::GAME_TYPE_BC_FDC_DICE_VN => array ( 1 => 'Big/Small(Small)', 2 => 'Big/Small(Big)', 4 => 'Points/4Point', 5 => 'Points/5Point', 6 => 'Points/6Point', 7 => 'Points/7Point', 8 => 'Points/8Point', 9 => 'Points/9Point', 10 => 'Points/10Point', 11 => 'Points/11Point', 12 => 'Points/12Point', 13 => 'Points/13Point', 14 => 'Points/14Point', 15 => 'Points/15Point', 16 => 'Points/16Point', 17 => 'Points/17Point', 18 => 'Designate a color on 1 dice(Green)', 19 => 'Designate a color on 1 dice(Blue)', 20 => 'Designate a color on 1 dice(Red)', 21 => 'Designate a color on 2 dice(Green)', 22 => 'Designate a color on 2 dice(Blue)', 23 => 'Designate a color on 2 dice(Red)',
									24 => 'Designate a color on 3 dice(Green)', 25 => 'Designate a color on 3 dice(Blue)', 26 => 'Designate a color on 3 dice(Red)', 27 => 'A color on 3 dice', 28 => 'Specific Triples(1,1,1)', 29 => 'Specific Triples(2,2,2)', 30 => 'Specific Triples(3,3,3)', 31 => 'Specific Triples4,4,4)', 32 => 'Specific Triples(5,5,5)', 33 => 'Specific Triples(6,6,6)', 34 => 'Any Triple', 35 => 'One of A Kind(1)', 36 => 'One of A Kind(2)', 37 => 'One of A Kind(3)', 38 => 'One of A Kind(4)', 39 => 'One of A Kind(5)', 40 => 'One of A Kind(6)', 41 => 'Odd/Even(Odd)', 42 => 'Odd/Even(Even)' ),
		self::GAME_TYPE_BC_FDC_DICE_TH => array ( 1 => 'Big/Small(Small)', 2 => 'Big/Small(Big)', 4 => 'Points/4Point', 5 => 'Points/5Point', 6 => 'Points/6Point', 7 => 'Points/7Point', 8 => 'Points/8Point', 9 => 'Points/9Point', 10 => 'Points/10Point', 11 => 'Points/11Point', 12 => 'Points/12Point', 13 => 'Points/13Point', 14 => 'Points/14Point', 15 => 'Points/15Point', 16 => 'Points/16Point', 17 => 'Points/17Point', 18 => 'Designate a color on 1 dice(Green)', 19 => 'Designate a color on 1 dice(Blue)', 20 => 'Designate a color on 1 dice(Red)', 21 => 'Designate a color on 2 dice(Green)', 22 => 'Designate a color on 2 dice(Blue)', 23 => 'Designate a color on 2 dice(Red)', 24 => 'Designate a color on 3 dice(Green)',
												25 => 'Designate a color on 3 dice(Blue)', 26 => 'Designate a color on 3 dice(Red)', 27 => 'A color on 3 dice', 28 => 'Specific Triples(1,1,1)', 29 => 'Specific Triples(2,2,2)', 30 => 'Specific Triples(3,3,3)', 31 => 'Specific Triples4,4,4)', 32 => 'Specific Triples(5,5,5)', 33 => 'Specific Triples(6,6,6)', 34 => 'Any Triple', 35 => 'One of A Kind(1)', 36 => 'One of A Kind(2)', 37 => 'One of A Kind(3)', 38 => 'One of A Kind(4)', 39 => 'One of A Kind(5)', 40 => 'One of A Kind(6)', 41 => 'Odd/Even(Odd)', 42 => 'Odd/Even(Even)' )
	);

	const BBIN_NEW_GAMETYPE = [
		"sports" => 109,
		"lottery" => 12,
		"live_dealer" => 3,
		"slots" => 5,
		"fishing_game" => 30
	];

	const BBIN_OLD_GAMETYPE = [
		1 => 109, // sports,
		5 => 5, // casino or slots
		30 => 30, // fishing_game
		33 => 109, // sports
		34 => 12, // lottery
		36 => 3, // live dealer
		37 => 5 // casino or slots
	];

	const URI_MAP = array(
		self::API_createPlayer => 'CreateMember',
		self::API_login => 'Login',
		self::API_logout => 'Logout',
		self::API_queryPlayerBalance => 'CheckUsrBalance',
		self::API_isPlayerExist => 'CheckUsrBalance',
		self::API_depositToGame => 'Transfer',
		self::API_withdrawFromGame => 'Transfer',
		self::API_syncGameRecords => 'BetRecord',
		self::API_batchQueryPlayerBalance => 'CheckUsrBalance',
		self::API_syncLostAndFound => 'BetRecordByModifiedDate3',
		self::API_syncJackpotRecords  => 'GetJPHistory',
		self::API_queryTransaction => 'CheckTransfer',
		self::API_createPlayerGameSession => 'CreateSession',

		#This is for mobile but can be used also in desktop
		self::API_changePassword => 'ChangeUserPwd',
		'getSportsRecord' => 'WagersRecordBy1',
		'getFishingRecord' => 'WagersRecordBy30',
		'getFishingRecord2' => 'WagersRecordBy38',
		'getWagersRecordBy66' => 'WagersRecordBy66',
		'getWagersRecordBy109' => 'WagersRecordBy109',
		'login2' => 'Login2',
		'fishHunter' => 'ForwardGameH5By30',
		'fishingMaster' => 'ForwardGameH5By38',
		'fishEventUrl' => 'FishEventUrl',
		'CasinoEventUrl' => 'CasinoEventUrl',
		'LiveEventUrl' => 'LiveEventUrl',
		'GetCasinoEventHistory' => 'GetCasinoEventHistory',
		'GetLiveEventHistory' => 'GetLiveEventHistory',
		'slots' => 'ForwardGameH5By5',
		'CreateSession' => 'CreateSession',
		'GameUrlBy109' => 'GameUrlBy109',
		'GameUrlBy30' => 'GameUrlBy30',
		'GetJPHistoryBy76' => 'GetJPHistoryBy76',
		'WagersRecordBy5' => 'WagersRecordBy5',
		'WagersRecordBy3' => 'WagersRecordBy3',
		'GameUrlBy107' => 'GameUrlBy107',
		'WagersRecordBy107' => 'WagersRecordBy107',
        'getWagersRecordBy75' => 'WagersRecordBy75',
        'getWagersRecordBy76' => 'WagersRecordBy76',
		'GameUrlBy3' => 'GameUrlBy3',
		'GameUrlBy5' => 'GameUrlBy5',
		'GameUrlBy12' => 'GameUrlBy12',
		// 'GameUrlBy30' => 'GameUrlBy30',
		'GameUrlBy31' => 'GameUrlBy31',
		'GameUrlBy38' => 'GameUrlBy38',
		'GameUrlBy66' => 'GameUrlBy66',
		'GameUrlBy73' => 'GameUrlBy73',
		'GameUrlBy75' => 'GameUrlBy75',
		'GameUrlBy76' => 'GameUrlBy76',
		'GameUrlBy93' => 'GameUrlBy93',
		// 'GameUrlBy107' => 'GameUrlBy107',
		'GameUrlBy109' => 'GameUrlBy109',
		'LobbyUrl' => 'LobbyUrl'
	);

	const GAME_URL_LIVE_CODE = 3;
	const GAME_URL_CASINO_CODE = 5;
	const GAME_URL_LOTTERY_CODE = 12;
	const GAME_URL_BB_FISHING_CONNOISSEUR_CODE = 30;
	const GAME_URL_NEW_BB_SPORTS_CODE = 31;
	const GAME_URL_BB_FISHING_MASTER_CODE = 38;
	const GAME_URL_BB_BATTLE_CODE = 66;
	const GAME_URL_XBB_LOTTERY_CODE = 73;
	const GAME_URL_XBB_LIVE_CODE = 75;
	const GAME_URL_XBB_CASINO_CODE = 76;
	const GAME_URL_NBB_BLOCK_CHAIN_CODE = 93;
	const GAME_URL_BBP_CASINO_CODE = 107;
	const GAME_URL_BB_SPORTS_CODE = 109;
	const GAME_URL_LOBBY_CODE = 0;

	const BET_DETAIL_GAME_CODE = array(
		"bac"=>["3001","3017"],
		"mahjong_tiles"=>"3002",
		"dragon_tiger"=>"3003",
		"three_face"=>"3005",
		"wenzhou_pai_gow"=>"3006",
		"roulette"=>"3007",
		"sicbo"=>"3008",
		"texas_holdem"=>"3010",
		"se_die"=>"3011",
		"bull_bull"=>"3012",
		"unlimited_blackjack"=>"3014",
		"fan_tan"=>"3015",
	);

	const GAME_SETTLED = 1;
	const GAME_UNSETTLED = 2;

	const LOTTERY_SETTLED = 'Y';
	const LOTTERY_UNSETTLED = 'N';
	const is_live = 'live';//live game

	const BBIN_FISHING_GAME = 1;
	const BBIN_CASINO_GAME = 2;
	const BBIN_SPORT_GAME = 3;
	const BBIN_LOTTERY_GAME = 4;
	const BBIN_CANCELED_BET = '-1';
	const BBIN_GetJPHistoryBy76 = 'GetJPHistoryBy76';
	const BBIN_GetJPHistory =  'GetJPHistory';
	const BBIN_WagersRecordBy5 =  'WagersRecordBy5';
	const BBIN_WagersRecordBy3 =  'WagersRecordBy3';
	const BBIN_WagersRecordBy107 = 'WagersRecordBy107'; #Bet Record of BBP CASINO
    const BBIN_XBB_LIVE_GAME = 'WagersRecordBy75';
    const BBIN_WagersRecordBy76 = 'WagersRecordBy76'; # Get bet record of XBB Casino.

	const ACTION_MODIFIED_TIME = 'ModifiedTime';

	public function __construct() {
		parent::__construct();
		$this->bbin_api_url = $this->getSystemInfo('url');
		$this->bbin_mywebsite = $this->getSystemInfo('bbin_mywebsite');

		#Mobile
		$this->enable_mobile_api = !empty($this->getSystemInfo('enable_mobile_api')) && $this->getSystemInfo('enable_mobile_api') ? true : false;
		$this->bbin_mobile_api_url = $this->getSystemInfo('bbin_mobile_api_url');
		$this->bbin_mobile_site_id = $this->getSystemInfo('bbin_mobile_site_id');
		$this->bbin_mobile_keyb = $this->getSystemInfo('bbin_mobile_keyb');

		$this->bbin_mobile_api_create_user_endpoint = '/CreateUser.ashx';
		$this->bbin_mobile_api_change_password_endpoint = '/ChangeUserPwd.ashx';


		$this->bbin_create_member = $this->getSystemInfo('bbin_create_member');
		$this->bbin_login_member = $this->getSystemInfo('bbin_login_member');
		$this->bbin_logout_member = $this->getSystemInfo('bbin_logout_member');
		$this->bbin_check_member_balance = $this->getSystemInfo('bbin_check_member_balance');
		$this->bbin_transfer = $this->getSystemInfo('bbin_transfer');
		$this->bbin_getbet = $this->getSystemInfo('bbin_getbet');
		$this->bbin_play_game = $this->getSystemInfo('bbin_play_game',false);
		$this->bbin_casino_event_game = $this->getSystemInfo('bbin_casino_event_game',false);
		$this->bbin_live_event_game = $this->getSystemInfo('bbin_live_event_game',false);
		$this->bbin_check_transfer = $this->getSystemInfo('bbin_check_transfer');

		$this->bbin_uppername = $this->getSystemInfo('bbin_uppername');
		$this->url_default = $this->getSystemInfo('url') . '/app/WebService/JSON/display.php';
		$this->url_login = $this->getSystemInfo('bbin_login_api_url') . '/app/WebService/JSON/display.php';
		$this->conversion_rate = floatval($this->getSystemInfo('bbin_conversion_rate'));

		$this->enable_pulling_fishing_record = $this->getSystemInfo('bbin_uppername', false);
		$this->enable_pulling_casino_event_record = $this->getSystemInfo('enable_pulling_casino_event_record', false);
		$this->enable_pulling_live_event_record = $this->getSystemInfo('enable_pulling_live_event_record', false);
		$this->enable_pulling_jackpot_record = $this->getSystemInfo('enable_pulling_jackpot_record', true);
        $this->enable_pulling_xbb_live_games_record = $this->getSystemInfo('enable_pulling_xbb_live_games_record', true);
        $this->enable_pulling_wagers_record_by_76 = $this->getSystemInfo('enable_pulling_wagers_record_by_76', true);

		$this->add_password_when_create_player = $this->getSystemInfo('add_password_when_create_player', true);
		$this->enabled_change_password = $this->getSystemInfo('enabled_change_password', false);
		$this->ignore_password_when_login=$this->getSystemInfo('ignore_password_when_login', true);
		$this->bbin_demo_link = $this->getSystemInfo('bbin_demo_link', 'http://777.x0day.net/');
		$this->bbin_live_present = $this->getSystemInfo('bbin_live_present', false);
		$this->modified_time_param = $this->getSystemInfo('modified_time_param','00:05:00');
		$this->language = $this->getSystemInfo('language','zh-cn');

		$this->use_new_version = $this->getSystemInfo('use_new_version', true);

		$this->default_fisharea_game_code = $this->getSystemInfo('default_fisharea_game_code', null);

		$this->keys = array(
			'bbin_create_member' => $this->bbin_create_member,
			'bbin_login_member' => $this->bbin_login_member,
			'bbin_logout_member' => $this->bbin_logout_member,
			'bbin_check_member_balance' => $this->bbin_check_member_balance,
			'bbin_transfer' => $this->bbin_transfer,
			'bbin_getbet' => $this->bbin_getbet,
			'bbin_play_game' => $this->bbin_play_game,
			'bbin_play_game' => $this->bbin_play_game,
			'bbin_casino_event_game' => $this->bbin_casino_event_game,
			'bbin_live_event_game' => $this->bbin_live_event_game,
			'bbin_check_transfer' => $this->bbin_check_transfer,
		);

		$this->lottery_kinds=$this->getSystemInfo('lottery_kinds', self::DEFAULT_LOTTERY_KINDS);
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
		$this->allow_sync_modified_data = $this->getSystemInfo('allow_sync_modified_data', false);
		$this->return_launcher_url_directly = $this->getSystemInfo('return_launcher_url_directly', false);
		$this->enable_pulling_bbin_battle = $this->getSystemInfo('enable_pulling_bbin_battle', false);
		$this->csv_path = $this->getSystemInfo('csv_path', '/var/game_platform/BBIN');
		$this->lobby_url = $this->getSystemInfo('lobby_url');
		$this->enable_pulling_bbp_casino = $this->getSystemInfo('enable_pulling_bbp_casino', false);
		$this->enabled_new_queryforward = $this->getSystemInfo('enabled_new_queryforward', true); // OGP-24803 as per game provider the updates is applicable to all clients
	}

	public function getPlatformCode() {
		return BBIN_API;
	}

	public function generateUrl($apiName, $params) {

		if(isset($params['isMobile']) && $params['isMobile']){
			$mobile_api_url = $params['mobile_api_url'];
			unset($params['isMobile']);
			unset($params['mobile_api_url']);
			$params_string = http_build_query($params);
			return $url = $mobile_api_url.'?'.$params_string;
		}

		$apiUri = self::URI_MAP[$apiName];

		$params_string = http_build_query($params);

		if($apiName == 'login' || $apiName == 'login2' || $apiName == 'fishHunter' || $apiName == 'fishingMaster') {
			$url =  $this->url_login . "/" . $apiUri . "?" . $params_string;
		} else {
			$url = $this->url_default . "/" . $apiUri . "?" . $params_string;
		}

		return $url;

		# $url = $apiName == 'login' ? $this->url_login . "/" . $apiUri . "?" . $params_string : $this->url_default . "/" . $apiUri . "?" . $params_string;
		# echo $url;exit;

		# return $url = $apiName == 'login' ? $this->url_login . "/" . $apiUri . "?" . $params_string : $this->url_default . "/" . $apiUri . "?" . $params_string;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

		return array(false, null);

	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
		$success = !empty($resultJson) && $resultJson['result'];

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('BBIN got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;
	}

	// const DATE_FORMAT_STYLE1 = 1; //'YYYYMMDD'
	// const DATE_FORMAT_STYLE2 = 2; //'YYYY-MM-DD'
	// const DATE_FORMAT_STYLE3 = 3; //'H:i:s'
	// private function getEasternStandardTime($dateTime, $formatType = self::DATE_FORMAT_STYLE1) {
	// 	//server time to game time
	// 	if ($formatType == self::DATE_FORMAT_STYLE1) {
	// 		return $dateTime->modify($this->getServerTimeToGameTime())->format('Ymd');
	// 	} elseif ($formatType == self::DATE_FORMAT_STYLE2) {
	// 		return $dateTime->modify($this->getServerTimeToGameTime())->format('Y-m-d');
	// 	} elseif ($formatType == self::DATE_FORMAT_STYLE3) {
	// 		return $dateTime->modify($this->getServerTimeToGameTime())->format('H:i:s');
	// 	}
	// }

	private function formatYMD($dateTimeStr) {
		$d = new Datetime($dateTimeStr);
		return $d->format('Ymd');
	}

	private function getYmdForKey() {
		return $this->formatYMD($this->serverTimeToGameTime(new DateTime()));
	}

	private function getStartKey($key_var) {
		return strtolower(random_string('alpha', $this->keys[$key_var]['start_key_len']));
	}

	private function getEndKey($key_var) {
		return strtolower(random_string('alpha', $this->keys[$key_var]['end_key_len']));
	}

	//===start createPlayer=====================================================================================

	public function createMobilePlayer($playerName, $playerId, $password, $email = null, $extra = null){

		$playerWithoutPrefix=$playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreateMobilePlayer',
			'playerWithoutPrefix' => $playerWithoutPrefix,
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$key =  strtolower(md5(utf8_encode($playerName . $this->bbin_mobile_keyb. $this->getYmdForKey()))) ;

		$params =  array(
			"username" => $playerName,
			"password" => $password,
			"siteid" => $this->bbin_mobile_site_id,
			"key" => $key,
			"isMobile" => true,
			'mobile_api_url'=> $this->bbin_mobile_api_url.$this->bbin_mobile_api_create_user_endpoint
		);

		return $this->callApi(self::API_createPlayer,$params, $context);

	}

	public function processResultForCreateMobilePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		if (!$success) {
			if (isset($resultJson['data']['Code']) && @$resultJson['data']['Code'] == '21001') {
				//repeated account
				$success = false;
				$this->CI->utils->debug_log('repeated account', $playerName);
			}
		}
		$this->CI->utils->debug_log('playerName', $playerName, 'response', @$resultJson['data']['Message'] ,'code', @$resultJson['data']['Code'] );
		return array($success);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$playerWithoutPrefix=$playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		//load it from game provider auth

		//use right password
		$password=$this->getPasswordByGameUsername($playerName);

		// $playerName = 'ogtest006';
		// $password = 'pass123';
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerWithoutPrefix' => $playerWithoutPrefix,
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$key = $this->getStartKey('bbin_create_member') .
			md5($this->bbin_mywebsite . $playerName . $this->bbin_create_member['keyb'] . $this->getYmdForKey()) .
			$this->getEndKey('bbin_create_member');

		$params =  array(
			"website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"uppername" => $this->bbin_uppername,
			"key" => $key
		);

		if($this->add_password_when_create_player){
			$params['password']=$password;
		}

		$rlt=$this->callApi(self::API_createPlayer, $params, $context);

		if($this->enable_mobile_api){
			if($rlt['success'] && !empty($this->bbin_mobile_api_url) && !empty($this->bbin_mobile_keyb)){

				//create mobile player too
				$mobileResult=$this->createMobilePlayer($playerWithoutPrefix, $playerId, $password, $email, $extra);

				if(!$mobileResult['success']){
					$rlt['mobile_result']=$mobileResult;
				}

				$this->utils->debug_log('createPlayer bbin mobileResult', $mobileResult);

			}
		}

		return $rlt;

	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result=[];

		if (!$success) {
			if (isset($resultJson['data']['Code']) && @$resultJson['data']['Code'] == '21001') {
				//repeated account
				$success = false;
				$result['user_exists']=true;
				$this->CI->utils->debug_log('repeated account', $playerName);
			}
		}

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		return array($success, $result);
	}

	public function changePassword($playerName, $oldPassword=null, $newPassword) {


		if($this->enable_mobile_api && $this->enabled_change_password){

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForChangePassword',
				'playerName' => $playerName,
				'newPassword' => $newPassword,
			);

			$key =  strtolower(md5(utf8_encode($playerName . $this->bbin_mobile_keyb. $this->getYmdForKey()))) ;

			$params =  array(
				"username" => $playerName,
				"password" => $newPassword,
				"siteid" => $this->bbin_mobile_site_id,
				"key" => $key,
				"isMobile" => 'true',
				'mobile_api_url'=> $this->bbin_mobile_api_url.$this->bbin_mobile_api_change_password_endpoint
			);

			return $this->callApi(self::API_changePassword, $params, $context);
		}

		return $this->returnUnimplemented();
	}

	function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		if (!$success) {
			if (isset($resultJson['result']) && @$resultJson['result'] == 'false') {
				// $success = false;
				$this->CI->utils->debug_log('BBIN changePasword ERROR', @$resultJson['result']['Message'], $playerName);
			}
		}

		return array($success, ['message'=>@$resultJson['result']['Message']]);
	}


	//===end createPlayer=====================================================================================

	//===start queryPlayerInfo=====================================================================================
	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}
	//===end queryPlayerInfo=====================================================================================


	//===start blockPlayer=====================================================================================
	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}
	//===end blockPlayer=====================================================================================

	//===start unblockPlayer=====================================================================================
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}
	//===end unblockPlayer=====================================================================================

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case '11000':
                $reasonCode = self::REASON_DUPLICATE_TRANSFER;
                break;
            case '10002':
                $reasonCode = self::REASON_INSUFFICIENT_AMOUNT;
            case '22002':
                $reasonCode = self::REASON_NOT_FOUND_PLAYER;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }

	//===start depositToGame=====================================================================================
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$remitno = intval(date('ymd').random_string('numeric')); // strict to int, max length:19 expected result length: 14

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			// 'playerName' => $playerName,
			'gameUsername' => $playerName,
			'amount' => $amount,
			'remitno' => $remitno,
			'usernameWithoutPrefix' => $playerUsername,
			'external_transaction_id' =>$remitno,
			//for this api
			// 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
		);

		$key = $this->getStartKey('bbin_transfer')
				. md5($this->bbin_mywebsite . $playerName . $remitno . $this->bbin_transfer['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_transfer');

		return $this->callApi(self::API_depositToGame,
				array("website" => $this->bbin_mywebsite,
					"username" => $playerName,
					"uppername" => $this->bbin_uppername,
					"remitno" => $remitno,
					"action" => 'IN',
					"remit" => $this->dBtoGameAmount($amount),
					"key" => $key,
				),
				$context);
	}

	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$remitno = $this->getVariableFromContext($params, 'remitno');
		$usernameWithoutPrefix = $this->getVariableFromContext($params, 'usernameWithoutPrefix');
		// $statusCode = $this->getStatusCodeFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $remitno,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
			# if error 500, treat as success
			$status = isset($resultJson['data']['code']) ? $resultJson['data']['code'] : null;
            if((in_array($status, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
                $result['reason_id'] = $this->getTransferErrorReasonCode($status);
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
            
        }

		return array($success, $result);
	}

	public function preDepositToGame($player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {

        if($amount < 1){
            return array(
                    'success' => false,
                    'message' => lang('not_allow_decimal')
                );
            }

        return $this->returnUnimplemented();
    }

    public function preWithdrawFromGame($player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {

    	// if($amount < 1){
     //        return array(
     //                'success' => false,
     //                'message' => lang('not_allow_decimal')
     //            );
     //        }

        return $this->returnUnimplemented();
    }

	//===end depositToGame=====================================================================================

	//===start withdrawFromGame=====================================================================================
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$playerUsername = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$remitno = intval(date('ymd').random_string('numeric')); // strict to int, max length:19 expected result length: 14

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			// 'playerName' => $playerName,
			'gameUsername' => $playerName,
			'amount' => $amount,
			'remitno' => $remitno,
			'usernameWithoutPrefix' => $playerUsername,
			'external_transaction_id' =>$remitno,
		);

		$key = $this->getStartKey('bbin_transfer')
				. md5($this->bbin_mywebsite . $playerName . $remitno . $this->bbin_transfer['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_transfer');

		return $this->callApi(self::API_withdrawFromGame,
				array("website" => $this->bbin_mywebsite,
					"username" => $playerName,
					"uppername" => $this->bbin_uppername,
					"remitno" => $remitno,
					"action" => 'OUT',
					"remit" => $this->dBtoGameAmount($amount),
					"key" => $key,
				),
				$context);
	}

	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$remitno = $this->getVariableFromContext($params, 'remitno');
		$usernameWithoutPrefix = $this->getVariableFromContext($params, 'usernameWithoutPrefix');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $remitno,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = isset($resultJson['data']['code']) ? $resultJson['data']['code'] : null;
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

		return array($success, $result);
	}

	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	public function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
		);

		$key = $this->getStartKey('bbin_login_member')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_login_member['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_login_member');

		$params=array(
			"website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"uppername" => $this->bbin_uppername,
			// "password" => $password,
			"key" => $key
		);

		if($this->add_password_when_create_player){
			$params['password']=$password;
		}

		if($this->ignore_password_when_login){
			unset($params['password']);
		}

		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		return array($success, $resultJson);
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	public function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			// 'playerId' => $playerId,
		);

		$key = $this->getStartKey('bbin_logout_member')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_logout_member['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_logout_member');

		return $this->callApi(self::API_logout,
				array("website" => $this->bbin_mywebsite,
					"username" => $playerName,
					"key" => $key),
				$context);
	}

	public function processResultForLogout($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		return array($success, null);
	}
	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	public function updatePlayerInfo($playerName, $infos) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	//===end updatePlayerInfo=====================================================================================

	//===start queryPlayerBalance=====================================================================================
	public function queryPlayerBalance($playerName) {
		$playerInfo = $this->getPlayerInfoByUsername($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		if (empty($playerName)) {
			$this->CI->load->library(array('salt'));
			$password = $this->CI->salt->decrypt($playerInfo->password, $this->CI->config->item('DESKEY_OG'));
			//try create player
			$rlt = $this->createPlayer($playerInfo->username, $playerInfo->playerId, $password);
			if (!$rlt['success']) {
				//failed
				return $rlt;
			} else {
				$this->updateRegisterFlag($playerInfo->playerId, Abstract_game_api::FLAG_TRUE);
				$playerName = $this->getGameUsernameByPlayerUsername($playerInfo->username);

			}
		}

		// $this->CI->utils->debug_log('playerInfo', $playerInfo, 'playerName', $playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $playerName,
			// 'playerName' => $playerName,
		);

		$key = $this->getStartKey('bbin_check_member_balance')
			. md5($this->bbin_mywebsite . $playerName . $this->bbin_check_member_balance['keyb'] . $this->getYmdForKey())
			. $this->getEndKey('bbin_check_member_balance');

		return $this->callApi(self::API_queryPlayerBalance,
				array("website" => $this->bbin_mywebsite,
					"username" => $playerName,
					"uppername" => $this->bbin_uppername,
					"key" => $key),
				$context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUsername);
		$result = array();
		if ($success && isset($resultJson['data']) && !empty($resultJson['data'])) {
			$balance = null;
			//search player name
			foreach ($resultJson['data'] as $row) {
				if (strtolower($gameUsername) == strtolower($row['LoginName'])) {
				// if ($playerName == $row['LoginName']) {
					$balance = $row['Balance'];
					break;
				}
			}
			if ($balance !== null) {
				$result['balance'] = floatval($this->gameAmountToDB($balance));#for conversion rate
				//reset to 0 if <1
				if($result['balance']<1){
					$result['balance']=0;
				}

				// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
				// $this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName,
				// 		'balance', $balance);
				// if ($playerId) {
				// 	//should update database
				// 	// $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
				// } else {
				// 	$msg = $this->CI->utils->debug_log('cannot get player id from ', $playerName, ' getPlayerIdInGameProviderAuth');
				// 	// log_message('error', $msg);
				// }
			} else {
				$this->CI->utils->debug_log('cannot find balance on player', $gameUsername);
			}
		} else {
			$success = false;
		}

		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================

	//===start queryPlayerDailyBalance=====================================================================================
	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		$daily_balance = parent::getPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null);

		$result = array();

		if ($daily_balance != null) {
			foreach ($daily_balance as $key => $value) {
				$balance = $this->gameAmountToDB($value['balance']);#for conversion rate
				$result[$value['updated_at']] = $balance;
			}
		}

		return array_merge(array('success' => true, "balanceList" => $result));
	}
	//===end queryPlayerDailyBalance=====================================================================================

	//===start queryGameRecords=====================================================================================
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
		return array('success' => true, 'gameRecords' => $gameRecords);
	}
	//===end queryGameRecords=====================================================================================

	//===start checkLoginStatus=====================================================================================
	public function checkLoginStatus($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true, "loginStatus" => true);
	}
	//===end checkLoginStatus=====================================================================================

	//===start totalBettingAmount=====================================================================================
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {

	}
	//===end totalBettingAmount=====================================================================================

	//===start queryTransaction=====================================================================================
	public function queryTransaction($transactionId, $extra) {

		$key = $this->getStartKey('bbin_check_transfer')
				. md5($this->bbin_mywebsite . $this->bbin_check_transfer['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_check_transfer');

		$context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id'    => $transactionId
        );

        $params = array(
            "website"    =>  $this->bbin_mywebsite,
            "transid"         => $transactionId,
            "key"      => $key
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);

	}
	public function processResultForQueryTransaction($params) {
        $this->CI->utils->debug_log('##########  QUERY TRANSACTION  #####################');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForQueryTransaction ==========================>', $resultJsonArr);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $result = array('response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
        if ($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }
	//===end queryTransaction=====================================================================================

	public function login2($playerName, $extra) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'proccessLogin2',
			'playerName' => $playerName,
			'fishFlag' => isset($extra['fishFlag']) ? $extra['fishFlag'] : null,
			'game_code'=> isset($extra['game_code']) ? $extra['game_code'] : null
		);

		$key = $this->getStartKey('bbin_login_member')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_login_member['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_login_member');

        $language = isset($extra['language']) ? $extra['language'] : $this->language;
		$params=array(
			"website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"uppername" => $this->bbin_uppername,
			"lang" => $this->getLauncherLanguage($language),
			"key" => $key,
		);

		return $this->callApi('login2', $params, $context);
	}

	public function proccessLogin2($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$fishFlag = $this->getVariableFromContext($params, 'fishFlag');
		$game_code = $this->getVariableFromContext($params, 'game_code');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array();
		if($success) {
			if($fishFlag == 1) {
				$result['url'] = $this->forwardGameH5('fishHunter', '30599', $playerName);
			} elseif ($fishFlag == 2) {
				$result['url'] = $this->forwardGameH5('fishingMaster', '38001', $playerName);
			} else {
				$result['url'] = null;
				if(!empty($game_code)){
					$result['url'] = $this->forwardGameH5('slots', $game_code, $playerName);
				}
			}
		}
		return array($success, $result);
	}

	public function queryForwardGameFishArea($playerName, $extra) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processQueryForwardGameFishArea',
			'playerName' => $playerName,
			'game_code'=> isset($extra['game_code']) ? $extra['game_code'] : null,
			'is_mobile' => isset($extra['is_mobile']) ? $extra['is_mobile'] : false,
			'language' => isset($extra['language']) ? $extra['language'] : $this->language
		);

		$key = $this->getStartKey('bbin_login_member')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_login_member['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_login_member');

        $language = isset($extra['language']) ? $extra['language'] : $this->language;
		$params=array(
			"website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"uppername" => $this->bbin_uppername,
			"lang" => $this->getLauncherLanguage($language),
			"key" => $key,
		);

		return $this->callApi('CreateSession', $params, $context);
	}

	public function processQueryForwardGameFishArea($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$is_mobile = $this->getVariableFromContext($params, 'is_mobile');

		$language = $this->getVariableFromContext($params, 'language');
		$game_code = $this->getVariableFromContext($params, 'game_code');


		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array();
		if($success) {

				$session_id = $resultJson["data"]["sessionid"];

				$fishArea = $this->forwardGameFishArea($session_id, $playerName, $is_mobile, $language, $game_code);

				$success = $fishArea["success"] ? $fishArea["success"] : false;

				if($success) {

					$result["url"] = $fishArea["url"];

				}
		}
		return array($success, $result);
	}

	public function forwardGameFishArea($session_id, $playerName, $is_mobile = false, $language, $game_code = null) {


		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');


		$apiUri = "GameUrlBy30";

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processForwardGameFishArea',
			'playerName' => $playerName,
			'game_code'=> isset($extra['game_code']) ? $extra['game_code'] : null,
			'is_mobile' => $is_mobile
		);

		$params=array(
			"website" => $this->bbin_mywebsite,
			"sessionid" => $session_id,
			"key" => $key,
			"lang" => $this->getLauncherLanguage($language)
		);

		if(!empty($game_code)) {
			$params["gametype"] = $game_code;
		}

		return $this->callApi($apiUri, $params, $context);



	}

	public function processForwardGameFishArea($params) {


		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$game_code = $this->getVariableFromContext($params, 'game_code');
		$is_mobile = $this->getVariableFromContext($params, 'is_mobile');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array();
		if($success) {

			$datas = $resultJson["data"];

			if(!empty($this->default_fisharea_game_code)) {

				$key = array_search($this->default_fisharea_game_code, array_column($datas, 'gametype'));

				if(!empty($key)) {
					$data = $datas[$key];
				} else {
					$data = $datas[0];
				}

			} else {
				$data = $datas[0];
			}

			$result['url'] = $data["html5"];

		}

		return array($success, $result);
	}


	public function queryForwardGameSports($playerName, $extra) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processQueryForwardGameSports',
			'playerName' => $playerName,
			'game_code'=> isset($extra['game_code']) ? $extra['game_code'] : null,
			'is_mobile' => isset($extra['is_mobile']) ? $extra['is_mobile'] : false,
			'language' => isset($extra['language']) ? $extra['language'] : $this->language
		);

		$key = $this->getStartKey('bbin_login_member')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_login_member['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_login_member');

        $language = isset($extra['language']) ? $extra['language'] : $this->language;
		$params=array(
			"website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"uppername" => $this->bbin_uppername,
			"lang" => $this->getLauncherLanguage($language),
			"key" => $key,
		);

		return $this->callApi('CreateSession', $params, $context);
	}

	public function processQueryForwardGameSports($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$is_mobile = $this->getVariableFromContext($params, 'is_mobile');

		$language = $this->getVariableFromContext($params, 'language');
		$game_code = $this->getVariableFromContext($params, 'game_code');


		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array();
		if($success) {

				$session_id = $resultJson["data"]["sessionid"];

				$gameSports = $this->forwardGameSports($session_id, $playerName, $is_mobile, $language);

				$success = $gameSports["success"] ? $gameSports["success"] : false;

				if($success) {

					$result["url"] = $gameSports["url"];

				}
		}
		return array($success, $result);
	}

	public function forwardGameSports($session_id, $playerName, $is_mobile = false, $language) {


		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');


		$apiUri = "GameUrlBy109";

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processForwardGameSports',
			'playerName' => $playerName,
			'game_code'=> isset($extra['game_code']) ? $extra['game_code'] : null,
			'is_mobile' => $is_mobile
		);

		$params=array(
			"website" => $this->bbin_mywebsite,
			"uppername" => $this->bbin_uppername,
			"sessionid" => $session_id,
			"key" => $key,
			"lang" => $this->getLauncherLanguage($language)
		);
		return $this->callApi($apiUri, $params, $context);



	}

	public function processForwardGameSports($params) {


		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$game_code = $this->getVariableFromContext($params, 'game_code');
		$is_mobile = $this->getVariableFromContext($params, 'is_mobile');

		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array();
		if($success) {


				$data = $resultJson["data"][0];


				if($is_mobile) {

					$result['url'] = $data["mobile"];

				} else {
					$result['url'] = $data["pc"];
				}
		}

		return array($success, $result);
	}

	public function forwardGameH5($apiName, $gameType, $playerName) {

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');

		$params=array(
			"website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"uppername" => $this->bbin_uppername,
			"gametype" => $gameType,
			"key" => $key,
		);

		$apiUri = self::URI_MAP[$apiName];

		$params_string = http_build_query($params);

		$url =  $this->url_login . "/" . $apiUri . "?" . $params_string;

		return $url;
	}

	public function directToFishingEvent($playerName, $extra = null) {

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');

        $language = isset($extra['language']) ? $extra['language'] : $this->language;
		$params=array(
			"website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"lang" => $this->getLauncherLanguage($language),
			"key" => $key,
		);

		$apiUri = self::URI_MAP['fishEventUrl'];

		$params_string = http_build_query($params);

		$url =  $this->url_login . "/" . $apiUri . "?" . $params_string;

		redirect($url);
	}

	public function directToEvent($playerName, $extra = null) {
		# SAME KEY CasinoEvent and LiveEvent
		$key =$this->getStartKey('bbin_casino_event_game')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_casino_event_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_casino_event_game');

        $language = isset($extra['language']) ? $extra['language'] : $this->language;
		$params=array(
			"website" => $this->bbin_mywebsite,
			"username" => $playerName,
			"lang" => $this->getLauncherLanguage($language),
			"key" => $key,
		);

		switch (@$extra['event']) {
			case 'casinoevent':
				$apiUri = self::URI_MAP['CasinoEventUrl'];
				break;
			case 'liveevent':
				$apiUri = self::URI_MAP['LiveEventUrl'];
				break;

			default:
				$apiUri = self::URI_MAP['CasinoEventUrl'];
				break;
		}

		$params_string = http_build_query($params);

		$url =  $this->url_login . "/" . $apiUri . "?" . $params_string;
		redirect($url);
	}

	public function getLauncherLanguage($language){
		$lang='';
		switch ($language) {
			case 1:
			case 'en-us':
				$lang = 'en-us'; // english
				break;
			case 2:
			case 'zh-cn':
				$lang = 'zh-cn'; // chinese
				break;
			case 4:
			case 'vi-vn':
				$lang = 'vi'; // vietnamese
				break;
			case 5:
			case 'ko-kr':
				$lang = 'ko'; // korean
				break;
			case 6:
			case 'th':
				$lang = 'th'; // thai
				break;
			case 7:
			case 'id':
				$lang = 'id'; // indonesian
				break;
			default:
				$lang = 'en-us'; // default as english
				break;
		}
		return $lang;
	}

	//===start queryForwardGame=====================================================================================
	public function queryForwardGame($playerName = null, $extra) {
		// $bbin_api_url = $this->CI->config->item('bbin_login_api_url');
		// $bbin_login_member = $this->CI->config->item('bbin_login_member');
		// $bbin_mywebsite = $this->CI->config->item('bbin_mywebsite');
		// $bbin_uppername = $this->CI->config->item('bbin_uppername');
		if($this->enabled_new_queryforward){

			if(isset($extra['new_bbin_url']) && $extra['new_bbin_url'] == true) {

				$game_type = isset($extra['game_url_no']) ? $extra['game_url_no'] : $extra['game_type']; // to check if its using goto_bbin_game or goto_bbingame (different parameters)
				$game_types_arr = ['sports', 'lottery', 'live_dealer', 'slots', 'fishing_game'];

				if(in_array($game_type, $game_types_arr)) {
					$extra['game_url_no'] = self::BBIN_NEW_GAMETYPE[$game_type];
				}

                $extra['game_type'] = null;
			} else {

				// $extra['game_url_no'] = $extra['game_type'] == 1 ? 109 : $extra['game_type'];

				$game_type = isset($extra['game_url_no']) ? $extra['game_url_no'] : $extra['game_type'];

				$old_game_types_arr = self::BBIN_OLD_GAMETYPE;

				if(isset($old_game_types_arr[$game_type])) {

					$extra['game_url_no'] = self::BBIN_OLD_GAMETYPE[$game_type];

				} else {
					$extra['game_url_no'] = $game_type;
				}

				$extra['game_type'] = null; // to fix invalid game type. game type is not important cause bbin has its own lobby.

			}

			return $this->newQueryForwardGame($playerName, $extra);
		}

		# code...
		if ($extra['game_mode'] == "real") {
			$password = $this->getPasswordString($playerName);
			$playerUsername = $playerName;
			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			// $key = strtolower(random_string('alpha', $bbin_login_member['start_key_len']))
			// . md5($bbin_mywebsite . $loginInfo->login_name . $bbin_login_member['keyb'] . $est)
			// . strtolower(random_string('alpha', $bbin_login_member['end_key_len']));

			$bbin_gametype = self::BBIN_GAMETYPE;
			$gameType = $extra['game_type'];
			if( $gameType == self::BBIN_GAME_TYPE_BBP_CASINO) {
				return $this->gameUrlBy107($playerName, $extra);
			}

			if( @$bbin_gametype[$gameType] == self::GAME_FISHAREA) {
				$fishArea = $this->queryForwardGameFishArea($playerUsername, $extra);
				redirect($fishArea['url']);

			}

			if(empty($gameType)){
				$gameType=@$extra['game_code'];
			}

			if( @$bbin_gametype[$gameType] == self::GAME_SLOTS) {
				$slotsGame = $this->login2($playerUsername, $extra);
				redirect($slotsGame['url']);
			}

			if( @$bbin_gametype[$gameType] == self::GAME_FISHHUNTER || @$bbin_gametype[$gameType] == self::GAME_FISHMASTER ) {
				$extra['fishFlag'] =  @$bbin_gametype[$gameType];
				$fishGame = $this->login2($playerUsername, $extra);
				redirect($fishGame['url']);
			}

			if( @$bbin_gametype[$gameType] == 'fishevent' ){
				$fishGame = $this->directToFishingEvent($playerName, $extra);
			}

			if( @$bbin_gametype[$gameType] == 'casinoevent' || @$bbin_gametype[$gameType] == 'liveevent'){
				$extra['event'] = $bbin_gametype[$gameType];
				$this->directToEvent($playerName, $extra);
			}

			if($this->use_new_version) {

				if( @$bbin_gametype[$gameType] == self::GAME_SPORTS) {
					$sportsGame = $this->queryForwardGameSports($playerUsername, $extra);
					redirect($sportsGame['url']);
				}
			}

            $language = isset($extra['language']) ? $extra['language'] : $this->language;

			// $url = $this->url_login . '/Login?website=' . $this->bbin_mywebsite .
			// '&username=' . $playerName . '&uppername=' . $this->bbin_uppername . '&password=' . $password .
			//  '&page_site=' . $bbin_gametype[$gameType] . '&key=' . $key . '&lang=' . $extra['language'];

			$key = $this->getStartKey('bbin_login_member')
				. md5($this->bbin_mywebsite . $playerName . $this->bbin_login_member['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_login_member');
			$params = array(
				'website' => $this->bbin_mywebsite,
				'username' => $playerName,
				'uppername' => $this->bbin_uppername,
				'password' => $password,
				'page_site' => @$bbin_gametype[$gameType],
				'key' => $key,
				'lang' => $this->getLauncherLanguage($language),
			);
			if($this->bbin_live_present && ($params['page_site'] == self::is_live)){
				$params['page_present'] = self::is_live;
			}

			if($this->ignore_password_when_login){
				unset($params['password']);
			}

			//merge url
			$params_string = http_build_query($params);
			$url = $this->url_login . '/Login?' . $params_string;
			if($this->return_launcher_url_directly){
				$result['message']=$url;
				$result['result']=1;
				$result['success'] = true;
				return $result;
			}

			//call get html form or json error
			$apiName = self::API_queryForwardGame;
			list($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj) = $this->httpCallApi($url, $params);
			$success = !$this->isErrorCode($apiName, $params, $statusCode, $errCode, $error);
			$dont_save_response_in_api = $this->CI->utils->getConfig('dont_save_response_in_api');
			if (!$success) {
				$this->CI->utils->debug_log('success', $success, 'result', $resultText, 'url', $url, 'params', $params);
			}
			$fields = ['full_url'=>$url];
			$responseResultId = $this->saveResponseResult($success, $apiName, $params,
					$resultText, $statusCode, $statusText, $header, $fields, $dont_save_response_in_api);

			$json = json_decode($resultText, true);


			if (!empty($json)) {
				//error
				$result['success'] = false;
				$this->CI->utils->debug_log('url', $url, 'params', $params, $result, $resultText);
				if (isset($json['data']['Message'])) {
					$result['message'] = $json['data']['Message'];
					$result['result'] = $json['result'];
				} else {
					$result['message_lang'] = 'goto_game.error';
				}
			} else {
				$result['html'] = $resultText;
				$result['success'] = true;
				$this->CI->utils->debug_log($result, $resultText, $json);
			}

		}

		if ($extra['game_mode'] != "real") {
			$result['url'] = $this->bbin_demo_link;
		}



		return $result;
	}
	//===end queryForwardGame=====================================================================================

	public function createSession($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreateSession'
		);

		$key = $this->getStartKey('bbin_login_member')
				. md5($this->bbin_mywebsite . $gameUsername . $this->bbin_login_member['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_login_member');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'username' => $gameUsername,
			'uppername' => $this->bbin_uppername,
			'lang' => $this->getLauncherLanguage($language),
			'key' => $key,
		);

		$this->CI->utils->debug_log('-----------------------bbin createSession params ----------------------------',$params);
		return $this->callApi(self::API_createPlayerGameSession, $params, $context);
	}

	public function processResultForCreateSession($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult);
		$result = ['sessionid' => null];

		$this->CI->utils->debug_log('-----------------------bbin processResultForCreateSession arrayResult ----------------------------',$arrayResult);

		if($success){
			if(isset($arrayResult['data']['sessionid'])){
				$result['sessionid'] = $arrayResult['data']['sessionid'];
			}
		}
		return array($success, $result);
	}

	public function gameUrlBy107($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);


		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'gametype' => $extra['game_code'],
			'sessionid' => $session['sessionid'],
			'exit_option' => self::EXIT_OPT_REDIRECT,
			'exit_url' => $this->lobby_url,
			'key' => $key,
		);

		if(!isset($extra['game_code'])){
			#redirect to casino lobby if no game type or game code
			$extra['active_site'] = 'casino';
			return $this->lobbyUrl($gameUsername, $extra);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrlBy107',
			'is_mobile' => $extra['is_mobile'],
			'game_code' => $extra['game_code'],
		);

		$this->CI->utils->debug_log('-----------------------bbin gameUrlBy107 params ----------------------------',$params);
		return $this->callApi('GameUrlBy107', $params, $context);
	}

	public function processResultForGameUrlBy107($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$isMobile = $this->getVariableFromContext($params, 'is_mobile');
		$gameCode = $this->getVariableFromContext($params, 'game_code');
		$success = $this->processResultBoolean($responseResultId, $arrayResult);
		$result = ['url' => null];
		if($success){
			if(!empty($gameCode)){
				$data = isset($arrayResult['data']) ? $arrayResult['data'] : [];
				$key = array_search($gameCode, array_column($data, 'gametype'));

				if($isMobile){
					if(isset($arrayResult['data'][$key]['mobile'])){
						$result['url'] = $arrayResult['data'][$key]['mobile'];
					}
				} else {
					if(isset($arrayResult['data'][$key]['pc'])){
						$result['url'] = $arrayResult['data'][$key]['pc'];
					}
				};
			}
		}
		return array($success, $result);
	}

	private function getSportsRecord($token, $gameKind = null,$action = 'BetTime') {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//convert to game time first
		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSportsRecord',
			'token' => $token,
			'gameKind' => $gameKind,
		);

		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		foreach($dates as $date) {
			$dateYmd = new DateTime(date('Y-m-d',strtotime($date)));
			$dateNow = new DateTime(date('Y-m-d'));
			$date_diff = date_diff($dateNow,$dateYmd);

			# you cannot search the modified data 7 days ago.
			if(($date_diff->days>6)&&$action == self::ACTION_MODIFIED_TIME){
				continue;
			}

			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;
			while(!$done && $failure_count < $this->common_retry_times) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"action" => $action,
					"uppername" => $this->bbin_uppername,
					"date" => $date,
					"starttime" => '00:00:00',
					"endtime" =>  '23:59:59',
					"page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);

				/**
				 * When
				 * action is ModifiedTime, get bet record which are modified in the range of
				 * time(the search period is 5 minutes and you cannot search the modified data 7
				 * days ago.).
				 */
				if($action == self::ACTION_MODIFIED_TIME){
					$params['endtime'] = $this->modified_time_param;
				}
				$this->takeSleep();
				$rlt = $this->callApi("getSportsRecord",$params, $context);
				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					$try_again=@$rlt['error_code']=='44003' || @$rlt['error_code']=='44005';
					if($try_again){
						$this->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
						//try again
						$this->takeSleep();
					}
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
				}
			}
		}
	}

	private function getWagersRecordBy109($token, $gameType = null, $action = "BetTime") {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//convert to game time first
		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));



		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWagersRecordBy109',
			'token' => $token,
			'gameKind' => self::BBIN_GAME_PROPERTY['bb_sports']['game_kind'],
		);

		if($action != "ModifiedTime") {

			$this->callWagers109Api($start_date, $end_date, $gameType, $action, $context);

		} else {

			$start_date = $start_date->format('Y-m-d H:i:s');
			$end_date   = $end_date->format('Y-m-d H:i:s');

			$self = $this;

			$this->CI->utils->loopDateTimeStartEnd($start_date, $end_date, '+5 minutes', function($start_date, $end_date) use ($gameType, $context, $action, $self)
			{
				$self->callWagers109Api($start_date, $end_date, $gameType, $action, $context);
				return true;

			});

		}
	}

	private function callWagers109Api($start_date, $end_date, $gameType, $action, $context) {
		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		foreach($dates as $date) {


			$dateYmd = new DateTime(date('Y-m-d',strtotime($date)));
			$dateNow = new DateTime(date('Y-m-d'));
			$date_diff = date_diff($dateNow,$dateYmd);

			# you cannot search the modified data 7 days ago.
			if(($date_diff->days>6)){
				continue;
			}

			$default_end_time = '23:59:59';

			if($action == "ModifiedTime") {

				$default_end_time = $end_date->format('H:i:s');

			}

			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;
			while(!$done && $failure_count < $this->common_retry_times) {

				$params = array(
					"website" => $this->bbin_mywebsite,
					"action" => $action,
					"uppername" => $this->bbin_uppername,
					"date" => $date,
					"starttime" => count($dates) == 1 ? $start_date->format('H:i:s') : '00:00:00',
					"endtime" =>  count($dates) == 1 ? $end_date->format('H:i:s') : $default_end_time,
					"page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);
				if ($gameType) {
					$params["gametype"] = $gameType;
				}

				$rlt = $this->callApi("getWagersRecordBy109",$params, $context);
				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					//try again if api busy
					$try_again=@$rlt['error_code']=='44003' || @$rlt['error_code']=='44005';
					if($try_again){
						$this->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
						//try again
						sleep($this->common_wait_seconds);
					}
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
				}
			}
		}
	}

	public function preProcessGameRecords(&$gameRecords,$extra, $game_kind=null){

		foreach($gameRecords as $index => $record) {
			if ($game_kind == self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']) {
				if (empty($record['PayoutTime'])) {
					$gameRecords[$index]['PayoutTime'] = $record['WagersDate'];
				}
			} elseif($game_kind == "casinoevent" || $game_kind == "liveevent"){
				$gameRecords[$index]['WagersID'] = $record['ID'];
				$gameRecords[$index]['WagersDate'] = $record['CreateTime'];
				$gameRecords[$index]['GameType'] = $game_kind;
				$gameRecords[$index]['Result'] = $record['Amount'];
				$gameRecords[$index]['Payoff'] = $record['Amount'];
				$gameRecords[$index]['BetAmount'] = 0;
				$gameRecords[$index]['Currency'] = "";
				$gameRecords[$index]['ExchangeRate'] = "";
				$gameRecords[$index]['gameKind'] = $game_kind;
				$gameRecords[$index]['PayoutTime'] = $record['CreateTime'];
			} elseif($game_kind == self::BBIN_GAME_PROPERTY['fish_hunter2']['game_kind']){
				$gameRecords[$index]['gameKind'] = $game_kind;
				$gameRecords[$index]['PayoutTime'] = $record['WagersDate'];
			}
			else{
				$gameRecords[$index]['PayoutTime'] = $record['WagersDate'];
			}

			if(!array_key_exists('IsPaid', $record)){
				$gameRecords[$index]['IsPaid']=null;
			}
			$gameRecords[$index]['WagerDetail'] = isset($record['WagerDetail']) ? $record['WagerDetail'] : null;
			$gameRecords[$index]['ResultType'] = isset($record['ResultType']) ? $record['ResultType'] : null;
			$gameRecords[$index]['ModifiedDate'] = isset($record['ModifiedDate']) ? $record['ModifiedDate'] : null;
		}
	}

	public function processResultForSportsRecord($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_kind = $this->getVariableFromContext($params, 'gameKind');
		$this->CI->load->model(array('bbin_game_logs', 'external_system','original_game_logs_model'));
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if ($success) {
			$gameRecords = $resultJson['data'];
			if ($gameRecords) {

				$extra = ['responseResultId'=>$responseResultId];
				$this->preProcessGameRecords($gameRecords,$extra, $game_kind);

				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'bbin_game_logs',					# original table logs
					$gameRecords,						# api record (format array)
					'WagersID',							# unique field in api
					'external_uniqueid',				# unique field in bbin_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS
				);

				$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

				unset($gameRecords);

				if (!empty($insertRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert', $game_kind);
				}
				unset($insertRows);


				if (!empty($updateRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update', $game_kind);
				}

				unset($updateRows);
			}

			//if ($gameRecords) {
			//	foreach ($gameRecords as $row) {
			//		$this->copyRowToDB($row, $responseResultId, $game_kind);
			//	}
			//}
			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		} else {
			$success = false;
			$errorCode = $result['error_code']=@$resultJson['data']['Code'];
			if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
				$result['currentPage'] = 0;
				$result['totalPages'] = 1;
				$result = $resultJson;
			}
		}
		return array($success, $result);
	}

	public function processResultForWagersRecordBy109($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_kind = $this->getVariableFromContext($params, 'gameKind');
		$this->CI->load->model(array('bbin_game_logs', 'external_system','original_game_logs_model'));
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if ($success) {
			$gameRecords = $resultJson['data'];
			if ($gameRecords) {

				$extra = ['responseResultId'=>$responseResultId];
				$this->preProcessGameRecords($gameRecords,$extra, $game_kind);

				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'bbin_game_logs',					# original table logs
					$gameRecords,						# api record (format array)
					'WagersID',							# unique field in api
					'external_uniqueid',				# unique field in bbin_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS
				);

				$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

				unset($gameRecords);

				if (!empty($insertRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert', $game_kind);
				}
				unset($insertRows);


				if (!empty($updateRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update', $game_kind);
				}

				unset($updateRows);
			}

			//if ($gameRecords) {
			//	foreach ($gameRecords as $row) {
			//		$this->copyRowToDB($row, $responseResultId, $game_kind);
			//	}
			//}
			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		} else {
			$success = false;
			$errorCode = $result['error_code']=@$resultJson['data']['Code'];
			if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
				$result['currentPage'] = 0;
				$result['totalPages'] = 1;
				$result = $resultJson;
			}
		}
		return array($success, $result);
	}

	//===start syncGameRecords=====================================================================================
	/**
	 *
	 */
	public function syncOriginalGameLogs($token) {

		$gameKind = $this->getValueFromSyncInfo($token, 'gameKind');
		$gameType = $this->getValueFromSyncInfo($token, 'gameType');
		$apiName = $this->getValueFromSyncInfo($token, 'apiName');
		$subGameKind = $this->getValueFromSyncInfo($token, 'subGameKind');
		$game = $this->getValueFromSyncInfo($token, 'game');
        
		if(!empty($game)){
			if($game == self::BBIN_WagersRecordBy107){
				return $this->getWagersRecordBy107($token);
			}

			#For manual sync for BBIN live casino wager
			if($game == self::BBIN_WagersRecordBy3) {
				return $this->getWagersRecordBy3($token);
			}

			#For manual sync for BBIN casino wager
			if($game == self::BBIN_WagersRecordBy5) {
				return $this->getWagersRecordBy5($token);
			}

            if($game == self::BBIN_WagersRecordBy76) {
				return $this->getWagersRecordBy76($token);
			}

			if($game == self::BBIN_FISHING_GAME){
				$this->takeSleep();
				return $this->getFishingRecord($token, $gameKind, $gameType, $apiName);
			}


			if($game == self::BBIN_SPORT_GAME){

				if($this->use_new_version) {

					$this->takeSleep();
					$this->getWagersRecordBy109($token, $gameType);
					$this->takeSleep();
					return $this->getWagersRecordBy109($token, $gameType, 'ModifiedTime');

				} else {

					$this->takeSleep();
					$this->getSportsRecord($token, $gameKind);
					$this->takeSleep();
					return $this->getSportsRecord($token, $gameKind, 'ModifiedTime');

				}

			}

			if($game == self::BBIN_CASINO_GAME){
				$this->takeSleep();
				$this->getBBINRecords($token, $gameKind, $gameType, $subGameKind, $apiName);
				$this->takeSleep();
				return $this->getJackpotRecord($token, $gameKind, $gameType);
			}
			if($game == self::BBIN_LOTTERY_GAME){
				$this->takeSleep();
				return $this->getBBINRecords($token, $gameKind, $gameType);
			}

			if($game == self::BBIN_BATTLE){
				return $this->getWagersRecordBy66($token, $gameType);
			}


			if($game == self::BBIN_GetJPHistoryBy76){
				return $this->getJPHistoryBy76($token);

			}

			if($game == self::BBIN_GetJPHistory){
				return $this->getJackpotRecord($token);
			}

		}

		if ($this->enable_pulling_casino_event_record) {
			$this->takeSleep();
			$this->getEventRecord($token,'GetCasinoEventHistory');
		}

        if ($this->enable_pulling_xbb_live_games_record) {
			$this->takeSleep();
			$this->getWagersRecordBy75($token, null, "BetTime");;
		}

		if ($this->enable_pulling_live_event_record) {
			$this->takeSleep();
			$this->getEventRecord($token,'GetLiveEventHistory');
		}

        if ($this->enable_pulling_wagers_record_by_76) {
			$this->takeSleep();
			$this->getWagersRecordBy76($token, null, 'BetTime');
		}

		if ($this->enable_pulling_fishing_record) {
			//$this->getFishingRecord($token, 30, '30599' ,'getFishingRecord');
			$this->takeSleep();
			$this->getFishingRecord($token, self::BBIN_GAME_PROPERTY['fish_hunter']['game_kind'], self::BBIN_GAME_PROPERTY['fish_hunter']['game_type_id'] ,'getFishingRecord');

			//$this->getFishingRecord($token, 38, '38001' ,'getFishingRecord2');
			$this->takeSleep();
			$this->getFishingRecord($token, self::BBIN_GAME_PROPERTY['fishing_master']['game_kind'], self::BBIN_GAME_PROPERTY['fishing_master']['game_type_id'] ,'getFishingRecord2');

			//$this->getFishingRecord($token, 38, '38002' ,'getFishingRecord2');
			$this->takeSleep();
			$this->getFishingRecord($token, self::BBIN_GAME_PROPERTY['fishing_master2']['game_kind'], self::BBIN_GAME_PROPERTY['fishing_master2']['game_type_id'] ,'getFishingRecord2');

			//$this->getFishingRecord($token, 30, '30598' ,'getFishingRecord');
			$this->takeSleep();
			$this->getFishingRecord($token, self::BBIN_GAME_PROPERTY['fish_hunter2']['game_kind'], self::BBIN_GAME_PROPERTY['fish_hunter2']['game_type_id'] ,'getFishingRecord');
			$this->takeSleep();
			$this->getFishingRecord($token, self::BBIN_GAME_PROPERTY['mammon_fishing']['game_kind'], self::BBIN_GAME_PROPERTY['mammon_fishing']['game_type_id'] ,'getFishingRecord');

			$this->takeSleep();
			$this->getFishingRecord($token, self::BBIN_GAME_PROPERTY['golden_boy_fishing']['game_kind'], self::BBIN_GAME_PROPERTY['golden_boy_fishing']['game_type_id'] ,'getFishingRecord');

			$this->takeSleep();
			$this->getFishingRecord($token, self::BBIN_GAME_PROPERTY['demon_buster_fishing']['game_kind'], self::BBIN_GAME_PROPERTY['demon_buster_fishing']['game_type_id'] ,'getFishingRecord');
		}

		if($this->use_new_version) {


			// bb sports v2

			$this->takeSleep();
			$this->getWagersRecordBy109($token, $gameType);
			$this->takeSleep();
			$this->getWagersRecordBy109($token, $gameType, 'ModifiedTime');

		} else {

			# Query by BetTime
			$this->takeSleep();
			$this->getSportsRecord($token, self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']);

			# Query by BetTime
			$this->takeSleep();
			$this->getSportsRecord($token, self::BBIN_GAME_PROPERTY['bb_sports']['game_kind'],'ModifiedTime');

		}



		$this->takeSleep();
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['live']['game_kind']);

		// $this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['3d_hall']['game_kind']);
		$this->takeSleep();
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 1);

		$this->takeSleep();
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 2);

		$this->takeSleep();
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 3);

		// $this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 4);
		$this->takeSleep();
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 5);


		// $cnt = 0;
		$this->CI->load->model('game_description_model');
		// $bbinLotteryGames = $this->CI->game_description_model->getGame(array('game_type_id' => self::BBIN_GAME_PROPERTY['lottery']['game_type_id']));
		// while ($cnt < count($bbinLotteryGames)) {
		// if (!empty($bbinLotteryGames)) {
			// foreach ($bbinLotteryGames as $lotteryGame) {
			// foreach ($this->lottery_kinds as $kind) {
				//get record for (lottery), when gamekind=12 gametype param is required
				$this->takeSleep();
				$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['lottery']['game_kind'], 'OTHER');
				$this->takeSleep();
				$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['lottery']['game_kind'], 'LT');
				// $cnt++;
			// }
		// }
		// }

		// if ($this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['bb_sports']['game_kind'])) {
		// 	if ($this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['live']['game_kind'])) {
		// 		if ($this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['3d_hall']['game_kind'])) {
		// 			if ($this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 1)) {
		// 				//get record for (casino), subGameKind param 1
		// 				if ($this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 2)) {
		// 					//get record for (casino), subGameKind param 2
		// 					$cnt = 0;
		// 					$this->CI->load->model('game_description_model');
		// 					$bbinLotteryGames = $this->CI->game_description_model->getGame(array('game_type_id' => self::BBIN_GAME_PROPERTY['lottery']['game_type_id']));
		// 					while ($cnt < count($bbinLotteryGames)) {
		// 						//get record for (lottery), when gamekind=12 gametype param is required
		// 						$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['lottery']['game_kind'], $bbinLotteryGames[$cnt]->gameCode) ? $cnt++ : '';
		// 					}
		// 				}
		// 			}
		// 		}
		// 	}
		// }

		if ($this->enable_pulling_bbin_battle) {
			$this->takeSleep();
			$this->getWagersRecordBy66($token);
		}

		if ($this->enable_pulling_jackpot_record) {
			$this->takeSleep();
			$this->getJackpotRecord($token);
			$this->getJPHistoryBy76($token);
		}

		if($this->enable_pulling_bbp_casino){
			$this->takeSleep();
			$this->getWagersRecordBy107($token);
		}

		return ['success'=>true];

	}
	private function getEventRecord($token,$apiEvent) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//convert to game time first
		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetEventRecord',
			'token' => $token,
			'apiEvent' => $apiEvent
		);

		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		foreach($dates as $date) {
			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;
			while(!$done && $failure_count < $this->common_retry_times) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"rounddate" => $date,
					"starttime" => '00:00:00',
					"endtime" =>  '23:59:59',
					"page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);
				$this->takeSleep();
				$rlt = $this->callApi($apiEvent,$params, $context);
				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
					$this->takeSleep();
				}
			}
		}
	}

	public function processResultForGetEventRecord($params) {
		$this->CI->load->model(array('bbin_game_logs', 'external_system','original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$apiEvent = $this->getVariableFromContext($params, 'apiEvent');
		# kind of events
		switch ($apiEvent) {
			case 'GetCasinoEventHistory':
				$game_kind = "casinoevent";
				break;
			case 'GetLiveEventHistory':
				$game_kind = "liveevent";
				break;
		}

		if ($success) {
			$gameRecords = $resultJson['data'];
			if ($gameRecords) {
				$extra = ['responseResultId'=>$responseResultId];
				$this->preProcessGameRecords($gameRecords,$extra,$game_kind);
				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'bbin_game_logs',					# original table logs
					$gameRecords,						# api record (format array)
					'ID',							# unique field in api
					'external_uniqueid',				# unique field in bbin_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL_CASINO_EVENT, # same field every event
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS_CASINO_EVENT # same field every event
				);

				$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

				unset($gameRecords);

				if (!empty($insertRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert', $game_kind);
				}
				unset($insertRows);


				if (!empty($updateRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update', $game_kind);
				}

				unset($updateRows);
			}

			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		} else {
			$success = false;
			$errorCode = $result['error_code']=@$resultJson['data']['Code'];
			if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
				$result['currentPage'] = 0;
				$result['totalPages'] = 1;
				$result = $resultJson;
			}
		}
		return array($success, $result);
	}

	private function getFishingRecord($token, $gameKind = null, $gameType =null, $apiName = null) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//convert to game time first
		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncFishingRecords',
			'token' => $token,
			'gameKind' => $gameKind,
		);

		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		foreach($dates as $date) {
			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;
			while(!$done && $failure_count < $this->common_retry_times) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"username" => '',
					"action" => 'BetTime',
					"uppername" => $this->bbin_uppername,
					"date" => $date,
					"starttime" => '00:00:00', //$start_date->format('H:i:s'),
					"endtime" =>  '23:59:59',  //$end_date->format('H:i:s'),
					"gametype" => $gameType,
					"page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);

				$this->takeSleep();
				$rlt = $this->callApi($apiName,$params, $context);
				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					//try again if api busy
					$try_again=@$rlt['error_code']=='44003' || @$rlt['error_code']=='44005';
					if($try_again){
						$this->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
						//try again
						$this->takeSleep();
					}
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
					$this->takeSleep();
				}
			}
		}
	}

	public function processResultForSyncFishingRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		$game_kind = $this->getVariableFromContext($params, 'gameKind');

		$this->CI->load->model(array('bbin_game_logs', 'external_system','original_game_logs_model'));
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		if ($success) {
			$gameRecords = $resultJson['data'];

			if ($gameRecords) {
				$extra = ['responseResultId'=>$responseResultId];
				$this->preProcessGameRecords($gameRecords,$extra, $game_kind);

				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'bbin_game_logs',					# original table logs
					$gameRecords,						# api record (format array)
					'WagersID',							# unique field in api
					'external_uniqueid',				# unique field in bbin_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS
				);

				$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

				unset($gameRecords);

				if (!empty($insertRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert', $game_kind);
				}
				unset($insertRows);


				if (!empty($updateRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update', $game_kind);
				}

				unset($updateRows);
			}

			//if ($gameRecords) {
			//	foreach ($gameRecords as $row) {
			//		$this->copyRowToDB($row, $responseResultId, $game_kind);
			//	}
			//}
			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		} else {
			$success = false;
			$errorCode = $result['error_code']=@$resultJson['data']['Code'];
			if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
				$result['currentPage'] = 0;
				$result['totalPages'] = 1;
				$result = $resultJson;
			}
		}
		return array($success, $result);
	}

	private function getBetRecordByModifiedDate3($token, $gameKind, $gameType = null, $subGameKind = null){
		$startDateTime = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDateTime = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDateTime = new DateTime($this->serverTimeToGameTime($startDateTime->format('Y-m-d H:i:s')));
		$endDateTime = new DateTime($this->serverTimeToGameTime($endDateTime->format('Y-m-d H:i:s')));
		$startDateTime->modify($this->getDatetimeAdjust());
		$startDateTime = $startDateTime->format('Y-m-d H:i:s');
		$endDateTime   = $endDateTime->format('Y-m-d H:i:s');
		$count = 0;

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		$self = $this;
		$this->CI->utils->loopDateTimeStartEnd($startDateTime, $endDateTime, '+5 minutes', function($startDateTime, $endDateTime) use ($gameKind, $gameType, $subGameKind, $key,$self)
		{
			$startDate = $startDateTime->format('Y-m-d');
			$endDate = $endDateTime->format('Y-m-d');
			$startTime = $startDateTime->format('H:i:s');
			$endTime = $endDateTime->format('H:i:s');
			$data = array(
				"website" 		=> $this->bbin_mywebsite,
				"start_date" 	=> $startDate,
				"end_date" 		=> $endDate,
				"starttime" 	=> $startTime,
				"endtime" 		=> $endTime,
				"gamekind"		=> $gameKind,
				"key"			=> $key
			);

			if($subGameKind){
				$data['subGameKind'] = $subGameKind;
			}

			if($gameKind == self::BBIN_GAME_PROPERTY['lottery']['game_kind']){
				$data['gametype'] = $gameType;
			}

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
			);
			$self->takeSleep();
			$result =  $self->callApi(self::API_syncLostAndFound, $data, $context);

			if($result['success']){
				$currentPage = $result['currentPage'];
				$totalPage = $result['totalPages'];
				while($currentPage != $totalPage) {

					$data['page'] = $currentPage + 1;
					$self->takeSleep();
				    $callApiByPage = $this->callApi(self::API_syncLostAndFound, $data, $context);
				    if($callApiByPage['success']){
				    	$currentPage = $callApiByPage['currentPage'];
				    } else{
						$currentPage = $totalPage;
						$self->takeSleep();
				    }
				}
			} else {
				//check if api busy
				$isBusy =@$result['error_code']=='44003' || @$result['error_code']=='44005';
				if($isBusy){//if busy try to sleep for the next leep of loopDateTimeStartEnd
					$self->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
					//try again
					$self->takeSleep();
				}
			}

			return true;

		});
		return array("success" => true);
	}

	private function getBBINRecords($token, $gameKind, $gameType = null, $subGameKind = null, $apiName = null) {

		$this->CI->utils->debug_log('getBBINRecordsParams' , 'gameKind', $gameKind, 'gameType', 'subGameKind', $subGameKind );
		//should try 3 times
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//convert to game time first
		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$dates = array();
		// if ($repeatFailedDate) {
		// 	$dates[] = $repeatFailedDate;
		// } else {
		//split date range (start date to end date)
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));
		$this->CI->utils->debug_log('dates', $dates, 'start_date', $start_date, 'end_date', $end_date);
		// }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'token' => $token,
			'gameKind' => $gameKind,
			'subGameKind' => $subGameKind,
		);

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		$cnt = 0;
		while ($cnt < count($dates)) {
			$page = self::START_PAGE;
			$done = false;
			$success = true;

			while (!$done) {
				$starttime = $cnt == 0 ? $start_date->format('H:i:s') : '00:00:00';
				$endtime = $cnt == count($dates) - 1 ? $end_date->format('H:i:s') : '23:59:59';
				$dateYmd = new DateTime(date('Y-m-d',strtotime($dates[$cnt])));
				$dateNow = new DateTime(date('Y-m-d'));
				$date_diff = date_diff($dateNow,$dateYmd);
				# (gamekind=5, only can get the information within 7 days
				if(($date_diff->days>6)&&($gameKind == self::BBIN_GAME_PROPERTY['casino']['game_kind'])){
					$this->CI->utils->debug_log('SKIP syncing gamekind=5, only can get the information within 7 days');
					$done = true;
					continue;
				}

				$data = array(
						"website" => $this->bbin_mywebsite,
						"uppername" => $this->bbin_uppername,
						"rounddate" => $dates[$cnt],
						"starttime" => $starttime,
						"endtime" => $endtime,
						"gamekind" => $gameKind,
						"page" => $page,
						"pagelimit" => self::ITEM_PER_PAGE,
						"key" => $key);

				//for lottery game type
				if ($gameType) {
					$data["gametype"] = $gameType;
				}

				//for casino game type
				if ($subGameKind) {
					$data["subgamekind"] = $subGameKind;
				}

				$retry_count=0;
				$try_again=true;
				while($retry_count<$this->common_retry_times && $try_again){

					$retry_count++;
					$this->takeSleep();
					$rlt = $this->callApi(self::API_syncGameRecords, $data, $context);

					$done = true;
					if ($rlt) {
						$success = $rlt['success'];
					}
					if ($rlt && $rlt['success']) {
						$try_again=false;
						$page = $rlt['currentPage'];
						$total_pages = $rlt['totalPages'];
						//next page
						$page += 1;

						$done = $page >= $total_pages;
						$this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
					}else{
						if(@$rlt['error_code']=='40014'){
							$this->CI->utils->debug_log('get 40014 print param', $data);
						}

						//try again if api busy
						$try_again=@$rlt['error_code']=='44003' || @$rlt['error_code']=='44005';
						if($try_again){
							$this->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
							//try again
							$this->takeSleep();
						}
					}
				}

				$this->CI->utils->debug_log('out of loop',$retry_count, $this->common_retry_times, $try_again);

			}
			if ($done) {
				$cnt++;
			}
		}
		return true;
	}

	public function processResultForSyncGameRecords($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		// load models
		$this->CI->load->model(array('bbin_game_logs', 'external_system','original_game_logs_model'));
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		if ($success) {
			$gameRecords = $resultJson['data'];

			if ($this->isPrintVerbose()) {
				if ($resultJson['pagination']['TotalPage'] > 0) {
					$this->CI->utils->debug_log('resultJson', $resultJson);
				}
			}

			$game_kind = isset($params['params']['gamekind']) ? $params['params']['gamekind'] : null;
			if(empty($game_kind)){ #try get on context
				$game_kind = $this->getVariableFromContext($params, 'gameKind');
			}

			if ($gameRecords) {

				$extra = ['responseResultId'=>$responseResultId];
				$this->preProcessGameRecords($gameRecords,$extra, $game_kind);

				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'bbin_game_logs',					# original table logs
					$gameRecords,						# api record (format array)
					'WagersID',							# unique field in api
					'external_uniqueid',				# unique field in bbin_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS
				);

				$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

				unset($gameRecords);

				if (!empty($insertRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert', $game_kind);
				}
				unset($insertRows);


				if (!empty($updateRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update', $game_kind);
				}

				unset($updateRows);

				// foreach ($gameRecords as $row) {
				// 	 $this->copyRowToDB($row, $responseResultId, $params['params']['gamekind']);
				// }

			}

			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;

			// $this->CI->utils->debug_log('==========get game records', count($gameRecords));
		} else {
			$success = false;
			$token = $this->getVariableFromContext($params, 'token');
			$gameKind = $params['params']['gamekind'];
			$gameType = isset($params['params']['gametype']) ? $params['params']['gametype'] : null;
			$subGameKind = isset($params['params']['subgamekind']) ? $params['params']['subgamekind'] : null;
			$errorCode = $result['error_code']=@$resultJson['data']['Code'];
			if($errorCode != self::API_BUSY){ # skip error log if API busy
				if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
					$success = true;
					$result['currentPage'] = $page;
					$result['totalPages'] = $totalPages;
					$result = $resultJson;
				}else{
					$this->CI->utils->error_log('BBIN Sync Game Log Failed!  game kind: ', $gameKind . ' gameType: ' . $gameType . ' subGameKind: ' . $subGameKind . ' actual params:' . json_encode($params['params']));
					$result['error_code']=@$resultJson['data']['Code'];
					# add logs for Error print response
					$this->CI->utils->error_log('ERROR BBIN RESULT ', $resultJson);
					$this->takeSleep();
				}
			}
		}

		return array($success, $result);
	}

	private function getFlagByRow($row, $gameKind) {
		$this->CI->load->model(array('bbin_game_logs'));

		$flagFinished = false;
		if ($gameKind == self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']) {
			//set flag
			//sports need get result !=
			$flagFinished = $row['Result'] == 'L' || $row['Result'] == 'W' ||
			$row['Result'] == 'LW' || $row['Result'] == 'LL' || $row['Result'] == 'O'; // according to GP, it should be O (letter) tie = O
		}

		if ($gameKind == self::BBIN_GAME_PROPERTY['live']['game_kind']) {
			$flagFinished = ($row['ResultType'] != '-1' && $row['ResultType'] != '0') || ($row['Result'] == 'W' || $row['Result'] == 'L' || $row['Result'] == 'N');
		}

        if ($gameKind == self::BBIN_GAME_PROPERTY['xbb_live_games']['game_kind']) {
			$flagFinished = ($row['Result'] == 'W' || $row['Result'] == 'L' || $row['Result'] == 'D' || $row['Result'] == 'C' || $row['Result'] == 'X'); // Bet result (C: Cancellation, X: Unsettled, W: Success/Win, L: Lose, D: Tie)
		}

        if ($gameKind == self::BBIN_GAME_PROPERTY['xbb_casino']['game_kind']) {
			$flagFinished = ($row['Result'] == 'W' || $row['Result'] == 'L' || $row['Result'] == 'D' || $row['Result'] == 'C' || $row['Result'] == 'X'); // Bet result (C: Cancellation, X: Unsettled, W: Success/Win, L: Lose, D: Tie)
		}

		if ($gameKind == self::BBIN_GAME_PROPERTY['casino']['game_kind']) {
			$flagFinished = $row['Result'] == '1' || $row['Result'] == '200';
		}

		if ($gameKind == self::BBIN_GAME_PROPERTY['lottery']['game_kind']) {
			$flagFinished = $row['Result'] == 'W' || $row['Result'] == 'L' || $row['Result'] == 'N';
			$flagFinished = $flagFinished && $row['IsPaid'] == 'Y';
		}

		// if ($gameKind == self::BBIN_GAME_PROPERTY['3d_hall']['game_kind']) {
		// 	$flagFinished = $row['Result'] == '1' || $row['Result'] == '3';
		// }

		if ($gameKind == '30' || $gameKind == '38' || $gameKind == self::BBIN_BATTLE) {
			$flagFinished = Bbin_game_logs::FLAG_FINISHED;
		}

		//extra checking for game type fishing
		if(isset($row['GameType']) &&  in_array($row['GameType'], self::GAMETYPE_FISHING)){
			$flagFinished = Bbin_game_logs::FLAG_FINISHED;
		}

		return $flagFinished ? Bbin_game_logs::FLAG_FINISHED : Bbin_game_logs::FLAG_UNFINISHED;

	}

	// private function copyRowToDB($row, $responseResultId, $gameKind) {
	// 	$external_uniqueid = $row['WagersID'];
	// 	$result = array(
	// 		'username' => $row['UserName'],
	// 		'wagers_id' => $row['WagersID'],
	// 		'wagers_date' => $this->gameTimeToServerTime($row['WagersDate']),
	// 		'game_type' => $row['GameType'],
	// 		'result' => $row['Result'],
	// 		'bet_amount' => $this->gameAmountToDB($row['BetAmount']),
	// 		'currency' => $row['Currency'],
	// 		'exchange_rate' => $row['ExchangeRate'],
	// 		// 'game_platform' => $this->getPlatformCode(),
	// 		'external_uniqueid' => $external_uniqueid,
	// 		'response_result_id' => $responseResultId,
	// 		'game_kind' => $gameKind,
	// 		'updated_at' => $this->CI->utils->getNowForMysql(),
	// 		'payout_time' => isset($row['PayoutTime'])?$this->gameTimeToServerTime($row['PayoutTime']):null,
	// 	);

	// 	$result['serial_id'] = isset($row['SerialID']) ? $row['SerialID'] : null;
	// 	$result['round_no'] = isset($row['RoundNo']) ? $row['RoundNo'] : null;
	// 	$result['game_code'] = isset($row['GameCode']) ? $row['GameCode'] : null;
	// 	$result['result_type'] = isset($row['ResultType']) ? $row['ResultType'] : null;
	// 	$result['card'] = isset($row['Card']) ? $row['Card'] : null;
	// 	//cashback ?
	// 	$result['commision'] = isset($row['Commission']) ? $row['Commission'] : null;
	// 	$result['is_paid'] = isset($row['IsPaid']) ? $row['IsPaid'] : null;
	// 	$result['origin'] = isset($row['Origin']) ? $row['Origin'] : null;

	// 	$result['commisionable'] = isset($row['Commissionable']) ? $this->gameAmountToDB($row['Commissionable']) : 0;
	// 	$result['payoff'] = $this->gameAmountToDB($row['Payoff']);

	// 	if ($gameKind == self::BBIN_GAME_PROPERTY['lottery']['game_kind']) {
	// 		$result['commisionable'] = $this->gameAmountToDB($result['bet_amount']);
	// 	}

	// 	$result['flag'] = $this->getFlagByRow($row, $gameKind);

		// if ($gameKind == self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::BBIN_GAME_PROPERTY['bb_sports']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// } elseif ($gameKind == self::BBIN_GAME_PROPERTY['live']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::BBIN_GAME_PROPERTY['live']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// 	$result['serial_id'] = isset($row['SerialID']) ? $row['SerialID'] : null;
		// 	$result['round_no'] = isset($row['RoundNo']) ? $row['RoundNo'] : null;
		// 	$result['game_code'] = isset($row['GameCode']) ? $row['GameCode'] : null;
		// 	$result['result_type'] = isset($row['ResultType']) ? $row['ResultType'] : null;
		// 	$result['card'] = isset($row['Card']) ? $row['Card'] : null;
		// } elseif ($gameKind == self::BBIN_GAME_PROPERTY['casino']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::BBIN_GAME_PROPERTY['casino']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// } elseif ($gameKind == self::BBIN_GAME_PROPERTY['lottery']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::BBIN_GAME_PROPERTY['lottery']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// 	$result['commision'] = $row['Commission'];
		// 	$result['is_paid'] = $row['IsPaid'];
		// } elseif ($gameKind == self::BBIN_GAME_PROPERTY['3d_hall']['game_kind']) {
		// 	$result['commisionable'] = $row['Result'] == self::BBIN_GAME_PROPERTY['3d_hall']['lose_type'] ? '-' . $row['Commissionable'] : $row['Commissionable'];
		// }

		// $this->CI->utils->debug_log('sync to game_log => game kind: ', $gameKind);
	// 	$this->CI->bbin_game_logs->sync($result);
	// }

	public function syncLostAndFound($token) {
		if($this->allow_sync_modified_data){
			//lottery
			$this->takeSleep();
			$this->getBetRecordByModifiedDate3($token, self::BBIN_GAME_PROPERTY['lottery']['game_kind'], 'LT');
			$this->takeSleep();
			$this->getBetRecordByModifiedDate3($token, self::BBIN_GAME_PROPERTY['lottery']['game_kind'], 'OTHER');
			$this->takeSleep();

			//sports
			if($this->use_new_version) {

				$this->getWagersRecordBy109($token, null, 'ModifiedTime');
			} else {

				$this->getBetRecordByModifiedDate3($token, self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']);

			}

		}
		return array('success'=>true);
	}

	public function syncConvertResultToDB($token) {
		return array('success'=>true);
	}

	/**
	 * extract file name
	 *
	 * param xmlFileRecord string
	 *
	 * @return  void
	 */
	private function extractXMLRecord($folderName, $file, $playerName = null, $responseResultId = null) {

	}

	private function getStringValueFromXml($xml, $key) {
		$value = (string) $xml[$key];
		if (empty($value) || $value == 'null') {
			$value = '';
		}

		return $value;
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		 $sqlTime='bbin.payout_time >= ? and bbin.payout_time <= ?';
		 if($use_bet_time){
		 	$sqlTime='bbin.wagers_date >= ? and bbin.wagers_date <= ?';
		 }

		# slots, lottery, live use wagers date
		# sports use payout_time
		#$sqlTime = 'IFNULL(`bbin`.`payout_time`,`bbin`.`wagers_date`) >= "'.$dateFrom.'" AND IFNULL(`bbin`.`payout_time`,`bbin`.`wagers_date`) <= "' . $dateTo . '"';

		$sql = <<<EOD
SELECT bbin.id as sync_index,
bbin.id,
bbin.wagers_id,
bbin.username,
bbin.external_uniqueid,
bbin.wagers_date,
bbin.wagers_date as bet_time,
bbin.payout_time,
bbin.game_type,
bbin.result,
bbin.commisionable as bet_amount,
bbin.bet_amount as real_bet_amount,
bbin.payoff,
bbin.jp_amount,
bbin.currency,
bbin.commisionable,
bbin.game_kind,
bbin.flag,
bbin.response_result_id,
bbin.result,
bbin.result_type,
bbin.last_sync_time,
bbin.md5_sum,
bbin.game_type as game_code,
bbin.game_type as game,
bbin.is_paid,
bbin.wager_detail,
bbin.modified_date,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM bbin_game_logs as bbin

left JOIN game_description as gd ON bbin.game_type = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON bbin.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE

{$sqlTime}

EOD;

		$params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom,$dateTo];

		$result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
		// print_r($result);exit;
		return $result;
	}

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

		$extra_info=[
			#'trans_amount'=>$row['real_bet_amount'],
			'note'=>isset($row['wager_detail']) ? $row['wager_detail'] : null,
			'odds' => isset($row['odds']) ? $row['odds'] : null
		];
		// $row['end_at'] = $row['bet_time'];
		// if(!empty($row['payout_time']) && $row['game_kind'] == self::BBIN_GAME_PROPERTY['lottery']['game_kind']) {
		$row['end_at'] = $row['payout_time'];
		// }

        if($row['game_kind'] == self::BBIN_GAME_PROPERTY['xbb_live_games']['game_kind']) {

			$this->CI->load->model(array('bbin_game_logs'));

			if($row["status"] == Bbin_game_logs::FLAG_UNFINISHED) {

				$row['end_at'] = $row['bet_time'];

			}

		}

        if($row['game_kind'] == self::BBIN_GAME_PROPERTY['xbb_casino']['game_kind']) {
			$this->CI->load->model(array('bbin_game_logs'));
			if($row["status"] == Bbin_game_logs::FLAG_UNFINISHED) {
				$row['end_at'] = $row['bet_time'];
			}
		}

		if($row['game_kind'] == self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']) {

			$this->CI->load->model(array('bbin_game_logs'));

			if($row["status"] == Bbin_game_logs::FLAG_UNFINISHED) {

				$row['end_at'] = $row['bet_time'];

			}

		}

		if(empty($row['md5_sum'])){
			$row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
					self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

		$result_amount = $row['payoff'];
		if($row['jp_amount']>0){
			$result_amount = $row['jp_amount'];
		}

		$logs_info = [
			'game_info'=>[
				'game_type_id'=>$row['game_type_id'],
				'game_description_id'=>$row['game_description_id'],
				'game_code'=>$row['game_code'],
				'game_type'=>null,
				'game'=>$row['game']
			],
			'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['username']],
			'amount_info'=>['bet_amount'=>$row['bet_amount'],
			'result_amount'=>$result_amount,
			'bet_for_cashback'=>$row['bet_amount'],
			'real_betting_amount'=>$row['real_bet_amount'],
			'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>null],
			'date_info'=>['start_at'=>$row['bet_time'],
			'end_at'=> $row['end_at'],
			'bet_at'=>$row['bet_time'],
			'updated_at'=>$row['last_sync_time']],
			'flag'=>Game_logs::FLAG_GAME,
			'status'=>$row['status'],
			'additional_info'=>[
				'has_both_side'=>0, 'external_uniqueid'=>$row['external_uniqueid'],
				'round_number'=>$row['external_uniqueid'],
				'md5_sum'=>$row['md5_sum'],
				'response_result_id'=>$row['response_result_id'],
				'sync_index'=>$row['sync_index'],
				'bet_type'=>null
			],
			'bet_details'=>isset($row['bet_details'])?$row['bet_details']:null,
			'extra'=>$extra_info,
			//from exists game logs
			'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
			'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
		];

		return $logs_info;
	}

	public function processWagerDetail($wager_details, $game_type) {

		$processed = array();

		$wager_details_arr = explode("*", $wager_details);

		$total_odds = 0;

		foreach($wager_details_arr as $detail) {

			$detail_arr = explode(",", $detail);

			$game_rule_id = $detail_arr[0];

			$game_rule_property = self::GAME_RULES_PROPERTY;

			$game_rule = isset($game_rule_property[$game_type][$game_rule_id]) ? $game_rule_property[$game_type][$game_rule_id] : "Unknown";
			$odds = $detail_arr[1];
			$bet = $this->gameAmountToDB($detail_arr[2]);
			$win_gold = $this->gameAmountToDB($detail_arr[3]);

			$processed[] = array(
				"game_rule" => $game_rule_id . " - "  . $game_rule,
				"odds" => $odds,
				"bet" => $bet,
				"win_gold" => $win_gold
			);

			// calculate odds (from ration to number)
			$odd_arr = explode(":", $odds);

			$odds_in_num = $odd_arr[0] / $odd_arr[1];

			$total_odds += $odds_in_num;
		}

		return array(
			"details" => $this->CI->utils->encodeJson($processed),
			"odds" => $total_odds
		);

	}

	public function preprocessOriginalRowForGameLogs(array &$row){
		$game_description_id = $row['game_description_id'];
		$game_type_id = $row['game_type_id'];

		if (empty($game_description_id)) {
			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->unknownGame);
		}

		$row['game_description_id']=$game_description_id;
		$row['game_type_id']=$game_type_id;

		if($row['game_kind'] == self::EVENT_CASINO || $row['game_kind'] == self::EVENT_LIVE || $row['game_kind'] == self::EVENT_FISHING){
			$gameDate = new \DateTime($row['wagers_date']);
			$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
			$row['bet_time'] = $gameDateStr;
			$row['start_at'] = $gameDateStr;
			$row['end_at'] = $gameDateStr;
		}

		$row['status'] = $row['flag'];

		if($row['game_kind'] == self::BBIN_GAME_PROPERTY['live']['game_kind']) {

			if(!empty($row['wager_detail'])) {

				$wager_details = $this->processWagerDetail($row['wager_detail'], $row["game"]);

				$row["wager_detail"] = null; // need to move the wager detail in bet_details
				$row["odds"] = $wager_details["odds"];
				$row['bet_details'] = $wager_details["details"]; // need to move the wager detail in bet_details

			}

		} else {

			$row['bet_details']=$this->processGameBetDetail($row,$row['payoff'], $row['bet_amount'], isset($row["odds"]) ? $row["odds"] : null);

		}



		#$row['bet_type']=Game_logs::BET_TYPE_SINGLE_BET;
	}

	public function updateOrInsertOriginalGameLogs($rows, $responseResultId, $type, $gameKind){
		$dataCount=0;
		if(!empty($rows)) {
			foreach ($rows as $row) {
				$external_uniqueid = $row['WagersID'];
				$data = [
					'username' => $row['UserName'],
					'wagers_id' => $row['WagersID'],
					'wagers_date' => $this->gameTimeToServerTime($row['WagersDate']),
					'game_type' => $row['GameType'],
					'result' => $row['Result'],
					'bet_amount' => $this->gameAmountToDB($row['BetAmount']),
					'currency' => $row['Currency'],
					'exchange_rate' => $row['ExchangeRate'],
					'external_uniqueid' => $external_uniqueid,
					'response_result_id' => $responseResultId,
					'game_kind' => $gameKind,
					'updated_at' => $this->CI->utils->getNowForMysql(),
					'payout_time' => isset($row['PayoutTime'])?$this->gameTimeToServerTime($row['PayoutTime']):null,

					// added field
					'md5_sum'=>$row['md5_sum'],
					'last_sync_time'=>$this->CI->utils->getNowForMysql(),
				];

				$data['serial_id'] = isset($row['SerialID']) ? $row['SerialID'] : null;
				$data['round_no'] = isset($row['RoundNo']) ? $row['RoundNo'] : null;
				$data['game_code'] = isset($row['GameCode']) ? $row['GameCode'] : null;
				$data['result_type'] = isset($row['ResultType']) ? $row['ResultType'] : null;
				$data['card'] = isset($row['Card']) ? $row['Card'] : null;
				$data['wager_detail'] = isset($row['WagerDetail']) ? $row['WagerDetail'] : null;

				$data['commision'] = isset($row['Commission']) ? $row['Commission'] : null;
				$data['is_paid'] = isset($row['IsPaid']) ? $row['IsPaid'] : null;
				$data['origin'] = isset($row['Origin']) ? $row['Origin'] : null;

				$data['commisionable'] = isset($row['Commissionable']) ? $this->gameAmountToDB($row['Commissionable']) : $this->gameAmountToDB($row['BetAmount']);
				$data['payoff'] = $this->gameAmountToDB($row['Payoff']);
				$data['modified_date'] = isset($row['ModifiedDate']) ?  $this->gameTimeToServerTime($row['ModifiedDate']) : null;
				#if modified_date is null then use payout_time, payout_time is null then use wager_date

				$data['payout_time'] = isset($row['ModifiedDate']) ?  $this->gameTimeToServerTime($row['ModifiedDate']) : $data['payout_time'];
				// $data['commisionable'] = $this->gameAmountToDB($data['bet_amount']);

				$data['flag'] = $this->getFlagByRow($row, $gameKind);

				//insert or update data to t1lottery API gamelogs table database
                if ($type=='update') {
					$data['id']=$row['id'];
					$this->CI->original_game_logs_model->updateRowsToOriginal('bbin_game_logs', $data);
				} else {
					$this->CI->original_game_logs_model->insertRowsToOriginal('bbin_game_logs', $data);
				}
                $dataCount++;
                unset($data);
			}
		}
		return $dataCount;

	}

	public function syncMergeToGameLogs($token) {
        $this->unknownGame = $this->getUnknownGame($this->getPlatformCode());

		$enabled_game_logs_unsettle=true;
		return $this->commonSyncMergeToGameLogs($token,
			$this,
			[$this, 'queryOriginalGameLogs'],
			[$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
			[$this, 'preprocessOriginalRowForGameLogs'],
			$enabled_game_logs_unsettle);


		/*
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');

		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		// if (!$dateTimeTo) {
		// 	$dateTimeTo = new DateTime();
		// }
		$this->CI->utils->debug_log('Sync Merge BBIN now..');

		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$result = $this->getBBINGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));
		$sportsResult = $this->getBBINGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'),true);
		$result = array_merge($result,$sportsResult);

		if ($result) {
			$this->CI->load->model(array('game_logs', 'player_model', 'game_description_model'));

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $bbindata) {
				$username = $bbindata->username;
				$player_id = $bbindata->player_id;

                # don't record other unsettle game logs other than sports
				if (!$player_id || ($bbindata->flag != self::GAME_SETTLED && $bbindata->game_kind != self::BBIN_GAME_PROPERTY['bb_sports']['game_kind'] )) {
					continue;
				}

				// $player = $this->CI->player_model->getPlayerById($player_id);
				$player_username = $username;

				$gameDate = new \DateTime($bbindata->wagers_date);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
				//valid bet amount

                #don't convert, value already convert on sync original
				$bet_amount = $bbindata->commisionable;
				$real_bet_amount= $bbindata->bet_amount;
                $result_amount = $bbindata->payoff;

				$game_code = $bbindata->game_code;
                $game_description_id = $bbindata->game_description_id;
                $game_type_id = $bbindata->game_type_id;

				if (empty($game_description_id)) {
                    list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($bbindata, $unknownGame);
				}

				$extra=[
                    'table' => $bbindata->external_uniqueid,
                    'trans_amount'=>$real_bet_amount,
                    'bet_details' => $this->processGameBetDetail($bbindata,$result_amount,$bet_amount),
                    'sync_index' => $bbindata->id,
                ];

                if($bbindata->game_kind == self::BBIN_GAME_PROPERTY['bb_sports']['game_kind'] && $bbindata->flag == self::GAME_UNSETTLED){
                    $extra['status'] = Game_logs::STATUS_PENDING;
                    $this->CI->utils->debug_log("BBIN Sync Merge ===========>[unsettled]",$extra);
                }elseif($bbindata->game_kind == self::BBIN_GAME_PROPERTY['bb_sports']['game_kind'] && $bbindata->flag == self::GAME_SETTLED){
                    $extra['status'] = Game_logs::STATUS_SETTLED;
                    $this->CI->utils->debug_log("BBIN Sync Merge ===========>[settled]",$extra);
                }

				$this->syncGameLogs($game_type_id, $game_description_id, $game_code,
					$game_type_id, $game_code, $player_id, $player_username,
					$bet_amount, $result_amount, null, null, null, null,
					$bbindata->external_uniqueid, $gameDateStr, $gameDateStr,
					$bbindata->response_result_id, Game_logs::FLAG_GAME, $extra);

				// $gameLogdata = array(
				// 	'bet_amount' => $this->gameAmountToDB($bet_amount),
				// 	'result_amount' => $result_amount,
				// 	'win_amount' => $result_amount > 0 ? $result_amount : 0,
				// 	'loss_amount' => $result_amount < 0 ? abs($result_amount) : 0,
				// 	'start_at' => $gameDateStr,
				// 	'end_at' => $gameDateStr,
				// 	'game_platform_id' => $this->getPlatformCode(),
				// 	'game_description_id' => $game_description_id,
				// 	'game_code' => $game_code,
				// 	'game_type_id' => $game_type_id,
				// 	'player_id' => $player_id,
				// 	'player_username' => $player_username,
				// 	'external_uniqueid' => $bbindata->external_uniqueid,
				// 	'flag' => Game_logs::FLAG_GAME,
				// );

				// $this->CI->game_logs->syncToGameLogs($gameLogdata);
			}
		}
		*/
	}

	public function processGameBetDetail($row,$result_amount,$bet_amount, $odds = null){
		if( ! empty($row["game_code"])){
			$gamesBetDetail = $this->gamesBetDetail($row);
			$bet_details = array(
				"win_amount" => ($bet_amount > 0) ? $bet_amount:0,
				"bet_amount" =>  $bet_amount,
				"bet_placed" => isset($gamesBetDetail['bet_placed']) ? $gamesBetDetail['bet_placed']:null,
				"won_side" => isset($gamesBetDetail['won_side']) ? $gamesBetDetail['won_side']:null,
				"winloss_amount" => $result_amount,
			);

			if(!empty($odds)) {

				$bet_details["odds"] = $odds;

			}


			return $this->CI->utils->encodeJson($bet_details);
		}
		return false;
	}

	/*
        note: (baccarat only) if player bet on "Tie" and lost we can't specify where the player bet
     */
	public function gamesBetDetail($game_details){
		if(in_array($game_details["game_code"], self::BET_DETAIL_GAME_CODE['bac']) || $game_details["game_code"] == self::BET_DETAIL_GAME_CODE['unlimited_blackjack']){
			$result = explode(',', $game_details->result);

			$blackjack_max = 21;
			$banker = $result[0];
			$player = $result[1];
			$banker = ($banker > $blackjack_max) ? 0: $banker;
			$player = ($player > $blackjack_max) ? 0: $player;
			$banker = ($banker === "BJ") ? $blackjack_max: $banker;
			$player = ($player === "BJ") ? $blackjack_max: $player;

			$bet_placed = '';
			$won_side = '';

			$opposite_bet_amount = $game_details->bet_amount / 2;
			$opposite_bet_amount = $opposite_bet_amount;
			$bet_amount = $game_details->bet_amount;
			$payoff = $game_details->payoff;

			# bet wins
			if($payoff > 0) {
				if($banker > $player) {
					$bet_placed = $won_side = 'banker';
				} elseif ($banker < $player) {
					$bet_placed = $won_side = 'player';
				} else {
					$bet_placed = $won_side = 'tie';
				}
			}

			# bet loses
			if($payoff < 0) {
				if($banker > $player) {
					$bet_placed = 'player';
					$won_side = 'banker';
				} elseif ($banker < $player) {
					$bet_placed = 'banker';
					$won_side = 'player';
				} else { # Should not happen
					$bet_placed = '';
					$won_side = 'tie';
				}
			}

			# bet withdrawn
			if ($payoff == 0) {
				if($banker == $player) { # Cannot determine which side is the bet
					$bet_placed = '';
					$won_side = 'tie';
				} else { # Should not happen
					$bet_placed = '';
					$won_side = '';
				}
			}

			#for bet on opposite bet

			if (in_array($game_details->game_code, self::BET_DETAIL_GAME_CODE['bac'])) {
				#case 1 opposite bet: win amount is greater than bet amount
				#case 2 opposite bet: opposite bet amount is greater than payoff(result amount)
				if (abs($payoff) > $bet_amount || $opposite_bet_amount > abs($payoff)) {
					$bet_placed = 'Player, Banker';
				}
			}
            #end

            return ["bet_placed" => $bet_placed, "won_side" => $won_side];
        }

        return null;
    }

	private function getGameDescriptionInfo($row, $unknownGame) {
		$this->CI->load->model('game_type_model');

		$external_game_id = $row['game_type'];
		$extra = array('game_code' => $row['game_type']);
		$game_description_id = null;

		switch ($row['game_kind']) {
			case self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%sports%')";
                break;

			case self::BBIN_GAME_PROPERTY['lottery']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%lottery%')";
                break;

			// case self::BBIN_GAME_PROPERTY['3d_hall']['game_kind']:
			case self::BBIN_GAME_PROPERTY['live']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%live%')";
                break;
			case self::BBIN_GAME_PROPERTY['casino']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%slots%')";
                break;

            case self::BBIN_GAME_PROPERTY['fish_hunter2']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%fishing%')";
                break;
			default:
				$query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%unknown%')";
				break;
		}

		$game_type_details = $this->CI->game_type_model->getGameTypeList($query);

		if(!empty($game_type_details[0])){
			$game_type_id = $game_type_details[0]['id'];
			$row['gametype'] = $game_type_details[0]['game_type'];
		}else{
			$game_type_id = $unknownGame->game_type_id;
			$row['gametype'] = $unknownGame->game_name;
		}

		return $this->processUnknownGame(
				$game_description_id, $game_type_id,
				$row['game_type'], $row['gametype'], $external_game_id, $extra,
				$unknownGame);

	}

	// public function gameAmountToDB($amount) {
	//        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
	//        $value = floatval($amount / $conversion_rate);
	//        return round($value,2);
	//    }

	private function getBBINGameLogStatistics($dateTimeFrom, $dateTimeTo,$isSports=false) {
		$this->CI->load->model('bbin_game_logs');
		return $this->CI->bbin_game_logs->getBBINGameLogStatistics($dateTimeFrom, $dateTimeTo,$isSports);
	}

	//===end syncGameRecords=====================================================================================

	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	//===start isPlayerExist=====================================================================================
	public function isPlayerExist($playerName) {
		$playerInfo = $this->getPlayerInfoByUsername($playerName);
		$playerId=$this->getPlayerIdFromUsername($playerName);

		$userName = $this->getGameUsernameByPlayerUsername($playerName);
		$userName = !empty($userName)?$userName:$playerName;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsplayerExist',
			'playerName' => $playerName,
			'playerId'=>$playerId,
		);

		$key = $this->getStartKey('bbin_check_member_balance')
				. md5($this->bbin_mywebsite . $userName . $this->bbin_check_member_balance['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_check_member_balance');

		$params = array(
			"website" => $this->bbin_mywebsite,
			"username" => $userName,
			"uppername" => $this->bbin_uppername,
			"key" => $key
		);
		return $this->callApi(self::API_isPlayerExist,$params,$context);
	}

	public function processResultForIsplayerExist($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$result = array();

		$success = true;
		if(empty($resultJson)){
			$success = false;
			$result = array('exists' => null);
		}else{
			if (isset($resultJson['data']['Code'])&&$resultJson['data']['Code']=="22002") {
				$result = array('exists' => false);
			}else{
				$result = array('exists' => true);
				$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			}
		}

		return array($success, $result);
	}

	//===end isPlayerExist=====================================================================================

	/**
	 * game time + 12 = server time
	 *
	 */
	public function getGameTimeToServerTime() {
		return '+12 hours';
	}

	public function getServerTimeToGameTime() {
		return '-12 hours';
	}

	//===start batchQueryPlayerBalance=====================================================================================
	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		$this->CI->benchmark->mark('bbin_sync_balance_start');
		//search all player balance for pt
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$this->CI->load->model(array('game_provider_auth', 'player_model'));
		if (empty($playerNames)) {
			// $playerNames = array();
			//load all players
			$playerNames = $this->getAllGameUsernames();
		} else {
			//convert to game username
			// foreach ($playerNames as &$username) {
			// 	$username = $this->getGameUsernameByPlayerUsername($username);
			// }
			//call parent
			// return parent::batchQueryPlayerBalanceForeach($playerNames, $syncId);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBatchQueryPlayerBalance',
			'playerNames' => $playerNames,
			'dont_save_response_in_api' => $this->getConfig('dont_save_response_in_api'),
			'syncId' => $syncId,
		);

		$page = self::START_PAGE;
		$done = false;
		$success = true;
		$result = array();

		try {
			$key = $this->getStartKey('bbin_check_member_balance')
					. md5($this->bbin_mywebsite . $this->bbin_check_member_balance['keyb'] . $this->getYmdForKey())
					. $this->getEndKey('bbin_check_member_balance');

			while (!$done) {

				$data = array(
					"website" => $this->bbin_mywebsite,
					"uppername" => $this->bbin_uppername,
					"page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					'key' => $key,
				);

				$rlt = $this->callApi(self::API_batchQueryPlayerBalance, $data, $context);

				$done = true;
				// if ($rlt) {
				// $success = $rlt['success'];
				// }

				if ($rlt && $rlt['success']) {
					$page = $rlt['currentPage'];
					$total_pages = $rlt['totalPages'];
					//next page
					$page += 1;

					$done = $page > $total_pages;
					// $this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
					if (empty($result)) {
						$result = $rlt['balances'];
					} else if(is_array($rlt['balances'])){
						$result = array_merge($rlt['balances'], $result);
					} else{
						//ignore
					}

				} else {
					//failed
					$this->CI->utils->debug_log('failed', $rlt);
				}
			}

		} catch (Exception $e) {
			$this->processError($e);
			$success = false;
		}
		$this->CI->benchmark->mark('bbin_sync_balance_stop');
		$this->CI->utils->debug_log('bbin_sync_balance_bench', $this->CI->benchmark->elapsed_time('bbin_sync_balance_start', 'bbin_sync_balance_stop'));

		return $this->returnResult($success, "balances", $result);
	}

	function processResultForBatchQueryPlayerBalance($params) {

		$responseResultId = $params['responseResultId'];
		$resultJson = $this->convertResultJsonFromParams($params);
		// $playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		// $result = array();
		// if ($success) {
		// 	$result["balance"] = floatval($resultJson['result']['BALANCE']);
		// 	$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		// 	$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName);
		// 	if ($playerId) {
		// 		//should update database
		// 		$this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
		// 	} else {
		// 		log_message('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
		// 	}
		// }

		$result = array('balances' => null);
		$cnt = 0;
		// if ($success) {
		if ($success && isset($resultJson['data']) && !empty($resultJson['data'])) {

			foreach ($resultJson['data'] as $balResult) {
				// $success = $balResult->IsSucceed;
				// if ($success) {
				//search account number
				// if ($balResult->AccountNumber == $accountNumber) {
				$gameUsername = $balResult['LoginName'];
				$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
				if ($playerId) {

					$bal = floatval($balResult['Balance']);

					// $this->CI->utils->debug_log('playerId', $playerId, 'bal', $bal);

					$result["balances"][$playerId] = $bal;

//					$this->updatePlayerSubwalletBalance($playerId, $bal);
					$cnt++;
				}
				// }
				//break;
				// }
			}
		}

		$this->CI->utils->debug_log('sync balance', $cnt, 'success', $success);
		if ($success) {
			// $success = true;
			// if (isset($resultJson['pagination'])) {
			$result['totalPages'] = @$resultJson['pagination']['TotalPage'];
			$result['currentPage'] = @$resultJson['pagination']['Page'];
			$result['itemsPerPage'] = self::ITEM_PER_PAGE;
			$result['totalCount'] = @$resultJson['pagination']['TotalNumber'];
		}
		$result['response'] = $resultJson;

		return array($success, $result);

	}
	//===end batchQueryPlayerBalance=====================================================================================

	public function onlyTransferPositiveInteger(){
		return true;
	}

	/**
	 * Sleep base in Common wait second
	 * @return void
	 */
	public function takeSleep()
	{
		return sleep($this->common_wait_seconds);
	}

	private function getWagersRecordBy66($token, $gameType = null, $action = "BetTime") {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//convert to game time first
		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetWagersRecordBy66',
			'token' => $token,
			'gameKind' => self::BBIN_BATTLE,
		);

		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		foreach($dates as $date) {
			$dateYmd = new DateTime(date('Y-m-d',strtotime($date)));
			$dateNow = new DateTime(date('Y-m-d'));
			$date_diff = date_diff($dateNow,$dateYmd);

			# you cannot search the modified data 7 days ago.
			if(($date_diff->days>6)){
				continue;
			}

			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;
			while(!$done && $failure_count < $this->common_retry_times) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"action" => $action,
					"uppername" => $this->bbin_uppername,
					"date" => $date,
					"starttime" => count($dates) == 1 ? $start_date->format('H:i:s') : '00:00:00',
					"endtime" =>  count($dates) == 1 ? $end_date->format('H:i:s') : '23:59:59',
					"page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);
				if ($gameType) {
					$params["gametype"] = $gameType;
				}
				$rlt = $this->callApi("getWagersRecordBy66",$params, $context);
				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					//try again if api busy
					$try_again=@$rlt['error_code']=='44003' || @$rlt['error_code']=='44005';
					if($try_again){
						$this->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
						//try again
						sleep($this->common_wait_seconds);
					}
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
				}
			}
		}


	}

	public function processResultForGetWagersRecordBy66($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_kind = $this->getVariableFromContext($params, 'gameKind');
		$this->CI->load->model(array('bbin_game_logs', 'external_system','original_game_logs_model'));
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if ($success) {
			$gameRecords = $resultJson['data'];
			if ($gameRecords) {

				$extra = ['responseResultId'=>$responseResultId];
				$this->preProcessGameRecords($gameRecords,$extra, $game_kind);

				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'bbin_game_logs',					# original table logs
					$gameRecords,						# api record (format array)
					'WagersID',							# unique field in api
					'external_uniqueid',				# unique field in bbin_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS
				);

				$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

				unset($gameRecords);

				if (!empty($insertRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert', $game_kind);
				}
				unset($insertRows);


				if (!empty($updateRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update', $game_kind);
				}

				unset($updateRows);
			}

			//if ($gameRecords) {
			//	foreach ($gameRecords as $row) {
			//		$this->copyRowToDB($row, $responseResultId, $game_kind);
			//	}
			//}
			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		} else {
			$success = false;
			$errorCode = $result['error_code']=@$resultJson['data']['Code'];
			if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
				$result['currentPage'] = 0;
				$result['totalPages'] = 1;
				$result = $resultJson;
			}
		}
		return array($success, $result);
	}

	private function getJackpotRecord($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//convert to game time first
		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetJackpotRecord',
			'token' => $token,
			'apiName' => self::API_syncJackpotRecords
		);

		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		foreach($dates as $date) {
			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;
			while(!$done && $failure_count < $this->common_retry_times) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"start_date" => $date,
					"end_date" => $date,
					"starttime" => '00:00:00',
					"endtime" =>  '23:59:59',
					//"gamekind" => $gameKind,
					//"gametype" => $gameType,
					"page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);
				$this->takeSleep();
				$rlt = $this->callApi(self::API_syncJackpotRecords,$params, $context);
				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
					$this->takeSleep();
				}
			}
		}
	}


	private function getJPHistoryBy76($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//convert to game time first
		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetJackpotRecord',
			'token' => $token,
			'apiName' => 'GetJPHistoryBy76'
		);

		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');

		foreach($dates as $date) {
			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;
			while(!$done && $failure_count < $this->common_retry_times) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"date" => $date,
					"starttime" => '00:00:00',
					"endtime" =>  '23:59:59',
					"page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);
				$this->takeSleep();
				$rlt = $this->callApi('GetJPHistoryBy76',$params, $context);
				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
					$this->takeSleep();
				}
			}
		}
	}

	public function processResultForGetJackpotRecord($params) {
		$this->CI->load->model(array('bbin_game_logs', 'external_system','original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$apiEvent = $this->getVariableFromContext($params, 'apiName');

		if ($success) {
			$gameRecords = $resultJson['data'];
			if ($gameRecords) {
				foreach($gameRecords as $gameRecord){
					$wagersId = isset($gameRecord['WagersID'])?$gameRecord['WagersID']:null;
					$userName = isset($gameRecord['UserName'])?$gameRecord['UserName']:null;
					if(empty($gameRecord) || empty($wagersId) || empty($userName)){
						continue;
					}

					//update OGL add jackpot details
					$data = [];
					$data['jackpot_details'] = json_encode($gameRecord);
					$data['jp_type_id'] = isset($gameRecord['JPTypeID'])?$gameRecord['JPTypeID']:null;
					$data['jp_amount'] = $this->gameAmountToDB($gameRecord['JPAmount']);

					$where = [];
					$where['wagers_id'] = $wagersId;
					$where['username'] = $userName;
					$this->CI->original_game_logs_model->updateRowsToOriginalFromMultipleConditions('bbin_game_logs', $data, $where);
					$this->CI->utils->info_log('BBIN processResultForGetJackpotRecord', 'gameRecord', $gameRecord);
				}
			}

			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		} else {
			$success = false;
			$errorCode = $result['error_code']=@$resultJson['data']['Code'];
			if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
				$result['currentPage'] = 0;
				$result['totalPages'] = 1;
				$result = $resultJson;
			}
		}
		return array($success, $result);
	}

	public function getWagersRecordBy5($token, $action = "BetTime"){
		$startDate = $this->getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = $this->getValueFromSyncInfo($token, 'dateTimeTo');
		$gameType = $this->getValueFromSyncInfo($token, 'gameType');
		$subGameKind = $this->getValueFromSyncInfo($token, 'subGameKind');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');
		$result = array();
		$this->CI->utils->loopDateTimeStartEndDaily($startDate, $endDate, function($startDate, $endDate) use(&$result, $token, $action, $gameType, $subGameKind) {
			$date = $startDate->format('Y-m-d');
			$startTime = $startDate->format('H:i:s');
			$endTime = $endDate->format('H:i:s');

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
				'token' => $token,
				'subGameKind' => $subGameKind,
				'gameKind' => self::BBIN_GAME_PROPERTY['casino']['game_kind']
			);
			$dateParams = array(
				"date" => $date,
				"starttime" => $startTime,
				"endtime" => $endTime,
			);
			$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');



			$current_page = 1;
			$page_number = 1;
		    while($current_page <= $page_number) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"action" => $action,
					"uppername" => $this->bbin_uppername,
					"date" => $date,
					"starttime" => $startTime,
					"endtime" => $endTime,
					"page" => $current_page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);

				if ($gameType) {
					$params["gametype"] = $gameType;
				}

				if ($subGameKind) {
					$params["subgamekind"] = $subGameKind;
				}
				$this->takeSleep();
				$response =  $this->callApi('WagersRecordBy5', $params, $context);
				$page_number = isset($response['totalPages']) ? $response['totalPages'] : $current_page;
				$current_page = $current_page + 1;
				$result[] = $response;
			}
			return true;
	    });
	    return array(true, $result);
	}

	function getFileExtension($filename)
    {
        $path_info = pathinfo($filename);
        return $path_info['extension'];
    }

	public function syncOriginalGameLogsFromCSV($isUpdate = false){
		set_time_limit(0);
    	$this->CI->load->model(array('original_game_logs_model'));
    	$extensions = array("csv");
    	$path = $this->csv_path;
    	$files = array_diff(scandir($path,1), array('..', '.'));

    	$gameRecords = array();
    	$result = array('data_count_insert'=>0, 'data_count_update'=>0);
    	$count = 0;
    	if(!empty($files)){
    		foreach ($files as $key => $csv) {
    			$ext = $this->getFileExtension($csv);
                if (!in_array($ext,$extensions)) {//skip other extension
                    continue;
                }
                $flag = true;
				$file = fopen($path."/".$csv,"r");
				while(! feof($file))
				{
					$entry = fgetcsv($file);
					if($flag || empty($entry[0])) { $flag = false; continue; }

					$data = array(
						"IsPaid" => null,
						"ResultType" => null,
						"WagersDate" => $entry[0],
						"PayoutTime" => $entry[0],
						"ModifiedDate" => $entry[0],
						"WagersID" => $entry[1],
						"GameType" => $entry[2],
						"Result" => $entry[3],
						"BetAmount" => $entry[4],
						"Payoff" => $entry[5],
						"UserName" => $entry[6],
					);
					$gameRecords[] = $data;
				}
				fclose($file);
			}
    	}



    	if(!empty($gameRecords)){
    		list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
				'bbin_game_logs',					# original table logs
				$gameRecords,						# api record (format array)
				'WagersID',							# unique field in api
				'external_uniqueid',				# unique field in bbin_game_logs table
				self::MD5_FIELDS_FOR_ORIGINAL,
				'md5_sum',
				'id',
				self::MD5_FLOAT_AMOUNT_FIELDS
			);

			$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

			unset($gameRecords);

			if (!empty($insertRows)) {
				$result['data_count_insert'] += $this->updateOrInsertOriginalGameLogsFromCsv($insertRows, 'insert');
			}
			unset($insertRows);


			if (!empty($updateRows)) {
				$result['data_count_update'] += $this->updateOrInsertOriginalGameLogsFromCsv($updateRows, 'update');
			}

			unset($updateRows);
    	}
    	$result = array('data_count'=>$count);
    	return array("success" => true,$result);
	}

	public function updateOrInsertOriginalGameLogsFromCsv($rows, $type){
		$this->CI->load->model(array('bbin_game_logs'));
		$dataCount=0;
		if(!empty($rows)) {
			foreach ($rows as $row) {
				// $external_uniqueid = $row['WagersID'];
				$payoff = $row['Payoff'] * -1; #convert to player view
				$data = [
					'username' => $row['UserName'],
					'wagers_id' => $row['WagersID'],
					'wagers_date' => $this->gameTimeToServerTime($row['WagersDate']),
					'payout_time' => $this->gameTimeToServerTime($row['WagersDate']),
					'game_type' => $row['GameType'],
					'result' => $row['Result'],
					'bet_amount' => $this->gameAmountToDB($row['BetAmount']),
					'commisionable' => $this->gameAmountToDB($row['BetAmount']),
					'payoff' => $this->gameAmountToDB($payoff),
					'external_uniqueid' => $row['WagersID'],
					'updated_at' => $this->CI->utils->getNowForMysql(),
					'flag' => $data['flag'] = Bbin_game_logs::FLAG_FINISHED, #default since copied from back office
					// added field
					'md5_sum'=>$row['md5_sum'],
					'last_sync_time'=>$this->CI->utils->getNowForMysql(),
				];

				#insert or update data to t1lottery API gamelogs table database
                if ($type=='update') {
					$data['id']=$row['id'];
					$this->CI->original_game_logs_model->updateRowsToOriginal('bbin_game_logs', $data);
				} else {
					$this->CI->original_game_logs_model->insertRowsToOriginal('bbin_game_logs', $data);
				}
                $dataCount++;
                unset($data);
			}
		}
		return $dataCount;
	}

	public function getWagersRecordBy3($token, $action = "BetTime"){
		$startDate = $this->getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = $this->getValueFromSyncInfo($token, 'dateTimeTo');
		$gameType = $this->getValueFromSyncInfo($token, 'gameType');
		$subGameKind = $this->getValueFromSyncInfo($token, 'subGameKind');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');
		$result = array();
		$this->CI->utils->loopDateTimeStartEndDaily($startDate, $endDate, function($startDate, $endDate) use(&$result, $token, $action, $gameType, $subGameKind) {
			$date = $startDate->format('Y-m-d');
			$startTime = $startDate->format('H:i:s');
			$endTime = $endDate->format('H:i:s');

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
				'token' => $token,
				'subGameKind' => $subGameKind,
				'gameKind' => self::BBIN_GAME_PROPERTY['live']['game_kind']
			);
			$dateParams = array(
				"date" => $date,
				"starttime" => $startTime,
				"endtime" => $endTime,
			);
			$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');



			$current_page = 1;
			$page_number = 1;
		    while($current_page <= $page_number) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"action" => $action,
					"uppername" => $this->bbin_uppername,
					"date" => $date,
					"starttime" => $startTime,
					"endtime" => $endTime,
					"page" => $current_page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);

				if ($gameType) {
					$params["gametype"] = $gameType;
				}

				if ($subGameKind) {
					$params["subgamekind"] = $subGameKind;
				}
				// $this->takeSleep();
				sleep(10);
				// echo "<pre>";
				// print_r($params);
				$response =  $this->callApi('WagersRecordBy3', $params, $context);
				// echo "<pre>";
				// print_r($response);
				$page_number = isset($response['totalPages']) ? $response['totalPages'] : $current_page;
				$current_page = $current_page + 1;
				$result[] = $response;
			}
			return true;
	    });
	    return array(true, $result);
	}

	public function getWagersRecordBy107($token, $action = "BetTime"){
		$startDate = $this->getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = $this->getValueFromSyncInfo($token, 'dateTimeTo');
		$gameType = $this->getValueFromSyncInfo($token, 'gameType');
		$subGameKind = $this->getValueFromSyncInfo($token, 'subGameKind');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');
		$result = array();
		$this->CI->utils->loopDateTimeStartEndDaily($startDate, $endDate, function($startDate, $endDate) use(&$result, $token, $action, $gameType, $subGameKind) {
			$date = $startDate->format('Y-m-d');
			$startTime = $startDate->format('H:i:s');
			$endTime = $endDate->format('H:i:s');

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
				'token' => $token,
				'subGameKind' => $subGameKind,
				'gameKind' => self::BBIN_GAME_PROPERTY['casino']['game_kind']
			);
			$dateParams = array(
				"date" => $date,
				"starttime" => $startTime,
				"endtime" => $endTime,
			);
			$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');



			$current_page = 1;
			$page_number = 1;
		    while($current_page <= $page_number) {
				$params = array(
					"website" => $this->bbin_mywebsite,
					"action" => $action,
					"uppername" => $this->bbin_uppername,
					"date" => $date,
					"starttime" => $startTime,
					"endtime" => $endTime,
					"page" => $current_page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"key" => $key
				);

				if ($gameType) {
					$params["gametype"] = $gameType;
				}

				if ($subGameKind) {
					$params["subgamekind"] = $subGameKind;
				}
				// $this->takeSleep();
				sleep(10);
				// echo "<pre>";
				// print_r($params);
				$response =  $this->callApi('WagersRecordBy107', $params, $context);
				// echo "<pre>";
				// print_r($response);die();
				$page_number = isset($response['totalPages']) ? $response['totalPages'] : $current_page;
				$current_page = $current_page + 1;
				$result[] = $response;
			}
			return true;
	    });
	    return array(true, $result);
	}

    private function getWagersRecordBy75($token, $gameType = null, $action = "BetTime"){

		$startDate = $this->getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = $this->getValueFromSyncInfo($token, 'dateTimeTo');

		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$start_date->modify($this->getDatetimeAdjust());
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWagersRecordBy75',
			'token' => $token,
			'gameKind' => self::BBIN_GAME_PROPERTY['xbb_live_games']['game_kind'],
		);

		if($action != "ModifiedTime") {

			$this->callWagers75Api($start_date, $end_date, $gameType, $action, $context);

		} else {

			$start_date = $start_date->format('Y-m-d H:i:s');
			$end_date   = $end_date->format('Y-m-d H:i:s');

			$self = $this;

			$this->CI->utils->loopDateTimeStartEnd($start_date, $end_date, '+5 minutes', function($start_date, $end_date) use ($gameType, $context, $action, $self)
			{
				$self->callWagers75Api($start_date, $end_date, $gameType, $action, $context);
				return true;

			});
		}
	}

    private function callWagers75Api($start_date, $end_date, $gameType, $action, $context) {
		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

		$key = $this->getStartKey('bbin_getbet')
				. md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_getbet');


		foreach($dates as $date) {

			$dateYmd = new DateTime(date('Y-m-d',strtotime($date)));
			$dateNow = new DateTime(date('Y-m-d'));
			$date_diff = date_diff($dateNow,$dateYmd);

			# you cannot search the modified data 7 days ago.
			if(($date_diff->days>6)){
			continue;
			}

			$default_end_time = '23:59:59';

			if($action == "ModifiedTime") {

				$default_end_time = $end_date->format('H:i:s');

			}

			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;
			while(!$done && $failure_count < $this->common_retry_times) {
				$params = array(
					"website" => $this->bbin_mywebsite,
                    "page" => $page,
					"pagelimit" => self::ITEM_PER_PAGE,
					"starttime" => count($dates) == 1 ? $start_date->format('H:i:s') : '00:00:00',
					"endtime" =>  count($dates) == 1 ? $end_date->format('H:i:s') : $default_end_time,
                    "action" => $action,
                    "uppername" => $this->bbin_uppername,
					"date" => $date,
					"key" => $key
				);
				if ($gameType) {
					$params["gametype"] = $gameType;
				}

				$rlt = $this->callApi("getWagersRecordBy75",$params, $context);
				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					//try again if api busy
					$try_again=@$rlt['error_code']=='44003' || @$rlt['error_code']=='44005';
					if($try_again){
						$this->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
						//try again
						sleep($this->common_wait_seconds);
					}
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
				}
			}
		}
	}

    public function processResultForWagersRecordBy75($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_kind = $this->getVariableFromContext($params, 'gameKind');
		$this->CI->load->model(array('bbin_game_logs', 'external_system','original_game_logs_model'));
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if ($success) {
			$gameRecords = $resultJson['data'];
			if ($gameRecords) {

				$extra = ['responseResultId'=>$responseResultId];
				$this->preProcessGameRecords($gameRecords,$extra, $game_kind);

				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'bbin_game_logs',					# original table logs
					$gameRecords,						# api record (format array)
					'WagersID',							# unique field in api
					'external_uniqueid',				# unique field in bbin_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS
				);

				$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

				unset($gameRecords);

				if (!empty($insertRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert', $game_kind);
				}
				unset($insertRows);


				if (!empty($updateRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update', $game_kind);
				}

				unset($updateRows);
			}


			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		} else {
			$success = false;
			$errorCode = $result['error_code']=@$resultJson['data']['Code'];
			if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
				$result['currentPage'] = 0;
				$result['totalPages'] = 1;
				$result = $resultJson;
			}
		}
		return array($success, $result);
	}

    public function getWagersRecordBy76($token, $gameType = null, $action = 'BetTime') {
		$startDate = $this->getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = $this->getValueFromSyncInfo($token, 'dateTimeTo');

		$start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $start_date->modify($this->getDatetimeAdjust());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWagersRecordBy76',
			'token' => $token,
			'gameKind' => self::BBIN_GAME_PROPERTY['xbb_casino']['game_kind'],
		);

		if($action != 'ModifiedTime') {
			$this->callWagers76Api($start_date, $end_date, $gameType, $action, $context);
		}else{
			$start_date = $start_date->format('Y-m-d H:i:s');
			$end_date = $end_date->format('Y-m-d H:i:s');
			$self = $this;

			$this->CI->utils->loopDateTimeStartEnd($start_date, $end_date, '+5 minutes', function($start_date, $end_date) use ($gameType, $context, $action, $self) {
				$self->callWagers76Api($start_date, $end_date, $gameType, $action, $context);
				return true;
			});
		}
	}

    public function callWagers76Api($start_date, $end_date, $gameType, $action, $context) {
		$dates = array();
		$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));
		$key = $this->getStartKey('bbin_getbet') . md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey()) . $this->getEndKey('bbin_getbet');

		foreach($dates as $date) {
			$dateYmd = new DateTime(date('Y-m-d',strtotime($date)));
			$dateNow = new DateTime(date('Y-m-d'));
			$date_diff = date_diff($dateNow,$dateYmd);

			# you cannot search the modified data 7 days ago.
			if(($date_diff->days>6)){
			continue;
			}

			$default_end_time = '23:59:59';

			if($action == 'ModifiedTime') {
				$default_end_time = $end_date->format('H:i:s');
			}

			$done = false;
			$failure_count = 0;
			$page = self::START_PAGE;

			while(!$done && $failure_count < $this->common_retry_times) {
				$params = array(
					'website' => $this->bbin_mywebsite,
                    'page' => $page,
					'pagelimit' => self::ITEM_PER_PAGE,
					'starttime' => count($dates) == 1 ? $start_date->format('H:i:s') : '00:00:00',
					'endtime' => count($dates) == 1 ? $end_date->format('H:i:s') : $default_end_time,
                    'action' => $action,
                    'uppername' => $this->bbin_uppername,
					'date' => $date,
					'key' => $key
				);

				if($gameType) {
					$params["gametype"] = $gameType;
				}

				$rlt = $this->callApi("getWagersRecordBy76", $params, $context);

				if($rlt['success']) {
					if($rlt['currentPage'] < $rlt['totalPages']) {
						$page = $rlt['currentPage'] + 1;
					} else {
						$done = true;
					}
					$failure_count = 0;
				} else {
					//try again if api busy
					$try_again=@$rlt['error_code']=='44003' || @$rlt['error_code']=='44005';
					if($try_again){
						$this->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
						//try again
						sleep($this->common_wait_seconds);
					}
					# API call may fail (e.g. during maintenance)
					# we shall terminate the loop after certain consecutive failures
					$failure_count++;
				}
			}
		}
	}

    public function processResultForWagersRecordBy76($params) {
        $this->CI->load->model(array('bbin_game_logs', 'external_system', 'original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_kind = $this->getVariableFromContext($params, 'gameKind');
		$result = array('data_count'=>0);
		$success = $this->processResultBoolean($responseResultId, $resultJson);

		if($success) {
			$gameRecords = $resultJson['data'];
			if($gameRecords) {
				$extra = ['responseResultId' => $responseResultId];
				$this->preProcessGameRecords($gameRecords,$extra, $game_kind);

				list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					'bbin_game_logs',					# original table logs
					$gameRecords,						# api record (format array)
					'WagersID',							# unique field in api
					'external_uniqueid',				# unique field in bbin_game_logs table
					self::MD5_FIELDS_FOR_ORIGINAL,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS
				);

				$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

				unset($gameRecords);

				if (!empty($insertRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,$responseResultId, 'insert', $game_kind);
				}

				unset($insertRows);

				if (!empty($updateRows)) {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,$responseResultId, 'update', $game_kind);
				}

				unset($updateRows);
			}


			$page = $resultJson['pagination']['Page'];
			$totalPages = $resultJson['pagination']['TotalPage'];
			$result['currentPage'] = $page;
			$result['totalPages'] = $totalPages;
		} else {
			$success = false;
			$errorCode = $result['error_code'] = @$resultJson['data']['Code'];
			if($errorCode == self::SYSTEM_MAINTENANCE) { # system maintenance skip error log
				$result['currentPage'] = 0;
				$result['totalPages'] = 1;
				$result = $resultJson;
			}
		}

		return array($success, $result);
	}

	public function newQueryForwardGame($playerName = null, $extra){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$game_url_no = isset($extra['game_url_no']) ? $extra['game_url_no'] : self::GAME_URL_LOBBY_CODE;#default lobby
		if(isset($extra['game_url_no'])){
			unset($extra['game_url_no']);
		}
		if ($extra['game_mode'] != "real") {
			$result['url'] = $this->bbin_demo_link;
		} else {
			switch ($game_url_no) {
				case self::GAME_URL_LIVE_CODE:
					$result = $this->gameUrlBy3($gameUsername, $extra);
					break;
				case self::GAME_URL_CASINO_CODE:
					$result = $this->gameUrlBy5($gameUsername, $extra);
					break;
				case self::GAME_URL_LOTTERY_CODE:
					$result = $this->gameUrlBy12($gameUsername, $extra);
					break;
				case self::GAME_URL_BB_FISHING_CONNOISSEUR_CODE:
					$result = $this->gameUrlBy30($gameUsername, $extra);
					break;
				case self::GAME_URL_NEW_BB_SPORTS_CODE:
					$result = $this->gameUrlBy31($gameUsername, $extra);
					break;
				case self::GAME_URL_BB_FISHING_MASTER_CODE:
					$result = $this->gameUrlBy38($gameUsername, $extra);
					break;
				case self::GAME_URL_BB_BATTLE_CODE:
					$result = $this->gameUrlBy66($gameUsername, $extra);
					break;
				case self::GAME_URL_XBB_LOTTERY_CODE:
					$result = $this->gameUrlBy73($gameUsername, $extra);
					break;
				case self::GAME_URL_XBB_LIVE_CODE:
					$result = $this->gameUrlBy75($gameUsername, $extra);
					break;
				case self::GAME_URL_XBB_CASINO_CODE:
					$result = $this->gameUrlBy76($gameUsername, $extra);
					break;
				case self::GAME_URL_NBB_BLOCK_CHAIN_CODE:
					$result = $this->gameUrlBy93($gameUsername, $extra);
					break;
				case self::GAME_URL_BBP_CASINO_CODE:
					$extra['game_code'] = $extra['game_type'];
					$result = $this->gameUrlBy107($gameUsername, $extra);
					break;
				case self::GAME_URL_BB_SPORTS_CODE:
					$result = $this->gameUrlBy109($gameUsername, $extra);
					break;

				default:
					$result = $this->lobbyUrl($gameUsername, $extra);
					break;
			}
		}
		return $result;
	}

	public function lobbyUrl($gameUsername, $extra){

		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'active_site' => isset($extra['active_site']) ? $extra['active_site'] : "live",
			'key' => $key,
		);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_LOBBY_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin LobbyUrl params ----------------------------',$params);
		return $this->callApi('LobbyUrl', $params, $context);
	}

	public function gameUrlBy3($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'key' => $key,
		);
		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
			$params['gamecode'] = $extra['game_code'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_LIVE_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy3 params ----------------------------',$params);
		return $this->callApi('GameUrlBy3', $params, $context);
	}

	public function gameUrlBy5($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'key' => $key,
		);
		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
		} else {
			#redirect to casino lobby if no game type
			$extra['active_site'] = 'casino';
			return $this->lobbyUrl($gameUsername, $extra);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_CASINO_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy5 params ----------------------------',$params);
		return $this->callApi('GameUrlBy5', $params, $context);
	}

	public function gameUrlBy12($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);


		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'exit_option' => self::EXIT_OPT_REDIRECT,
			'exit_url' => $this->lobby_url,
			'key' => $key,
		);
		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_LOTTERY_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy12 params ----------------------------',$params);
		return $this->callApi('GameUrlBy12', $params, $context);
	}

	public function gameUrlBy30($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'key' => $key,
		);
		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
		} else {
			#redirect to fishing lobby if no game type
			$extra['active_site'] = 'fisharea';
			return $this->lobbyUrl($gameUsername, $extra);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_BB_FISHING_CONNOISSEUR_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy30 params ----------------------------',$params);
		return $this->callApi('GameUrlBy30', $params, $context);
	}

	public function gameUrlBy31($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'key' => $key,
		);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_NEW_BB_SPORTS_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy31 params ----------------------------',$params);
		return $this->callApi('GameUrlBy31', $params, $context);
	}

	public function gameUrlBy38($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'exit_option' => self::EXIT_OPT_REDIRECT,
			'exit_url' => $this->lobby_url,
			'key' => $key,
		);
		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
		} else {
			#redirect to fishing lobby if no game type
			$extra['active_site'] = 'fisharea';
			return $this->lobbyUrl($gameUsername, $extra);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_BB_FISHING_MASTER_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy38 params ----------------------------',$params);
		return $this->callApi('GameUrlBy38', $params, $context);
	}

	public function gameUrlBy66($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'group' => 0, # 0:battle with bot
			'exit_option' => self::EXIT_OPT_REDIRECT,
			'exit_url' => $this->lobby_url,
			'key' => $key,
		);
		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_BB_BATTLE_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy66 params ----------------------------',$params);
		return $this->callApi('GameUrlBy66', $params, $context);
	}

	public function gameUrlBy73($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'exit_option' => self::EXIT_OPT_REDIRECT,
			'exit_url' => $this->lobby_url,
			'key' => $key,
		);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_XBB_LOTTERY_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy73 params ----------------------------',$params);
		return $this->callApi('GameUrlBy73', $params, $context);
	}

	public function gameUrlBy75($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'exit_option' => self::EXIT_OPT_REDIRECT,
			'exit_url' => $this->lobby_url,
			'key' => $key,
		);

		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
            if(isset($extra['game_code'])){
                $params['gamecode'] = $extra['game_code'];
            }
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_XBB_LIVE_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy75 params ----------------------------',$params);
		return $this->callApi('GameUrlBy75', $params, $context);
	}

	public function gameUrlBy76($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'exit_option' => self::EXIT_OPT_REDIRECT,
			'exit_url' => $this->lobby_url,
			'key' => $key,
		);

		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
		} else {
			#redirect to XBB Casino lobby if no game type
			$extra['active_site'] = 'xbbcasino';
			return $this->lobbyUrl($gameUsername, $extra);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_XBB_CASINO_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy76 params ----------------------------',$params);
		return $this->callApi('GameUrlBy76', $params, $context);
	}

	public function gameUrlBy93($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'exit_option' => self::EXIT_OPT_REDIRECT,
			'exit_url' => $this->lobby_url,
			'key' => $key,
		);

		if(isset($extra['game_type'])){
			$params['gametype'] = $extra['game_type'];
			$params['gamecode'] = $extra['game_code'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_NBB_BLOCK_CHAIN_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy93 params ----------------------------',$params);
		return $this->callApi('GameUrlBy93', $params, $context);
	}

	public function gameUrlBy109($gameUsername, $extra){
		$language = isset($extra['language']) ? $extra['language'] : $this->language;
		$session = $this->createSession($gameUsername, $extra);

		$this->CI->utils->debug_log('-----------------------bbin gameUrlBy109 session ----------------------------',$session);

		$key = $this->getStartKey('bbin_play_game')
				. md5($this->bbin_mywebsite . $this->bbin_play_game['keyb'] . $this->getYmdForKey())
				. $this->getEndKey('bbin_play_game');
		$params = array(
			'website' => $this->bbin_mywebsite,
			'lang' => $this->getLauncherLanguage($language),
			'sessionid' => $session['sessionid'],
			'key' => $key,
		);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGameUrl',
			'is_mobile' => $extra['is_mobile'],
			'game_type' => $extra['game_type'],
			'game_url_no' => self::GAME_URL_BB_SPORTS_CODE
		);

		$this->CI->utils->debug_log('-----------------------bbin GameUrlBy109 params ----------------------------',$params);
		return $this->callApi('GameUrlBy109', $params, $context);
	}

	public function processResultForGameUrl($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		// echo "<pre>";
		// print_r($arrayResult);
		$isMobile = $this->getVariableFromContext($params, 'is_mobile');
		$gameType = $this->getVariableFromContext($params, 'game_type');
		$gameUrlNo = $this->getVariableFromContext($params, 'game_url_no');

		$success = $this->processResultBoolean($responseResultId, $arrayResult);
		$result = ['url' => null];
		if($success){
			$data = isset($arrayResult['data']) ? $arrayResult['data'] : [];
			$key = 0;
			switch ($gameUrlNo) {
				case self::GAME_URL_CASINO_CODE:#slots
				case self::GAME_URL_BB_FISHING_CONNOISSEUR_CODE:#fishing
				case self::GAME_URL_BB_FISHING_MASTER_CODE:#fishing master
					if(isset($arrayResult['data'][$key]['html5'])){
						$result['url'] = $arrayResult['data'][$key]['html5'];
					}
					break;
				default:
					if($isMobile){
						if(isset($arrayResult['data'][$key]['mobile'])){
							$result['url'] = $arrayResult['data'][$key]['mobile'];
						}
					} else {
						if(isset($arrayResult['data'][$key]['pc'])){
							$result['url'] = $arrayResult['data'][$key]['pc'];
						}
					};
					break;
			}
		}
		// echo "<pre>";
		// print_r($result);exit();
		return array($success, $result);
	}

}

/*end of file*/
