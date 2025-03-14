<?php

namespace payment\crypto_payment\cce {
    exit('No need loadded');

    use stdClass;

    class APIBody extends stdClass {
        /** @var string */
        public $code = '0000';

        /** @var string */
        public $message = '';
    }
}

namespace payment\crypto_payment\cce\entities {
    use \payment\crypto_payment\cce\APIBody;
    
    class CreateAccount extends APIBody
    {
        /** @var string 业务请求流水号 */
        public $serie_no = '';

        /** @var string 货币符号 */
        public $mny_smb = 'ETH.USDT';

        /** @var string 客户编号id */
        public $cust_no = '';

        /** @var string 用户地址 */
        public $cust_address = '';
    }

    class GetBalance extends APIBody
    {
        /** @var string 业务请求流水号 */
        public $serie_no = '';

        /** @var string 货币符号 */
        public $mny_smb = 'ETH.USDT';

        /** @var string 用户地址 */
        public $cust_address = '';

        /** @var string 地址余额 */
        public $cust_balance = '';
    }

    class TransferOutCoinlogs extends APIBody
    {
        /** @var string 业务请求流水号 */
        public $serie_no = '';

        /** @var string 货币符号 */
        public $mny_smb = 'ETH.USDT';

        /** @var string 用户地址 */
        public $cust_address = '';
    }

    class HistoryEntity extends APIBody
    {
        /** @var string 交易流水号 */
        public $tx_no = '';

        /** @var string 交易状态。
         * 0000新建
         * 0001已更新到余额表
         * 0002取消
         * 0003已清空到历史
         * 0009余额不足取消
         */
        public $trade_status = '';

        /** @var string 变动前账户余额 */
        public $bal_before = '';

        /** @var string 变动后账户余额 */
        public $bal_after = '';

        /** @var string 发生金额 */
        public $amt = '';

        /** @var string 区块id */
        public $bc_blockid  = '';

        /** @var string 交易hash */
        public $bc_txhash = '';

        /**
         * @var string 区块的交易状态
         * 0:pending
         * 1:成功
         * 2:失败
         */
        public $bc_blockstate = '';

        /** @var string 业务状态 充值；提币；余额变更 */
        public $biztype = '';
    }

    class SearchHistory extends APIBody
    {
        /** @var string 业务请求流水号 */
        public $serie_no = '';

        /** @var string 用户地址 */
        public $cust_address = '';

        /** @var HistoryEntity[] 交易记录列表 */
        public $records = [];
    }

    class CoinInfo extends APIBody
    {
        /** @var string */
        public $serie_no = '';

        /** @var string */
        public $min_amt = '';

        /** @var string */
        public $max_amx = '';

        /** @var string */
        public $min_fee_amt = '';

        /** @var string */
        public $max_fee_amt = '';

        /** @var string */
        public $fee_per = '';

        /** @var string */
        public $max_day_amt = '';

        /** @var string */
        public $max_times = '';

        /** @var string 正常,暂停 */
        public $status = '';
    }

    class CallbackRequest {
        /** @var string 商户编号 */
        public $client_id = '';

        /** @var string */
        public $request_type = 'BJC';

        /** @var string 编码 */
        public $charset = 'UTF-8';

        /** @var string 时间 */
        public $request_time = '20230621000000000';

        /** @var string */
        public $message_no = '';

        /** @var string 解密方式 */
        public $sign_method = 'DES';

        /** @var string */
        public $signature = '';

        /** @var string|CallbackBody[] */
        public $body = '';
    }

    class CallbackBody {
        /** @var string */
        public $serie_no = '';

        /** @var string 交易hash（充值的hash没有0x） */
        public $tx_hash = '';

        /** @var string 类型：充值、提币 */
        public $opt_type = '充值';

        /** @var string 货币符号 */
        public $mny_smb = 'ETH.USDT';

        /** @var string 金额 */
        public $mny_count = '0';

        /** @var string 发出地址 */
        public $from = '';

        /** @var string 接收地址 */
        public $to = '';

        /** @var string 状态：1表示成功，其他状态表示失败 */
        public $state = '1';

        /** @var string 客户编号 */
        public $cust_no = '';

        /** @var string 块号 */
        public $block_number = '';

        /** @var string 提币时间 */
        public $confirm_time = '';
    }
}