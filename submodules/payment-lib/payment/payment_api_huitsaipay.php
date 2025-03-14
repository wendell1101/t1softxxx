<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huitsaipay.php';
/**
 *   HUITSAIPAY
 *
 * * HUITSAIPAY_PAYMENT_API, ID:
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://adc.sky7878.com/api-doc.html
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huitsaipay extends Abstract_payment_api_huitsaipay {

    public function getPlatformCode() {
        return HUITSAIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'huitsaipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
       $params['paymentType'] = self::PAYMENT_TYPE_BANKCARD;
    }

    // public function getPlayerInputInfo() {
    //     return array(
    //         array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
    //     );
    // }

    # Config in extra_info will overwrite this
    public function getBankListInfoFallback() {
        return array(
                array( "label"=> "枣庄银行", "value"=> "ZZB"),
                array( "label"=> "晋城银行", "value"=> "JINCHENG"),
                array( "label"=> "广发银行", "value"=> "GDB"),
                array( "label"=> "安溪美法信用社", "value"=> "AXMF"),
                array( "label"=> "深圳发展银行", "value"=> "SDB"),
                array( "label"=> "阜新银行", "value"=> "FUXINBANK"),
                array( "label"=> "四川天府银行", "value"=> "TFB"),
                array( "label"=> "福建农村信用社联合社", "value"=> "FJRCC"),
                array( "label"=> "重庆三峡银行", "value"=> "CTGB"),
                array( "label"=> "常熟农商银行", "value"=> "CSRCC"),
                array( "label"=> "泰安银行", "value"=> "TAIAN"),
                array( "label"=> "陕西农信", "value"=> "SXRCC"),
                array( "label"=> "湖南省农村信用社联合社", "value"=> "HNNXS"),
                array( "label"=> "江苏农村商业银行", "value"=> "JSRC"),
                array( "label"=> "长城华西银行", "value"=> "GWBK"),
                array( "label"=> "招商银行", "value"=> "CMB"),
                array( "label"=> "齐鲁银行", "value"=> "QLBCHINA"),
                array( "label"=> "湖北农商银行", "value"=> "HBNS"),
                array( "label"=> "蕲春中银富登村镇银行", "value"=> "JCZYFDCZ"),
                array( "label"=> "光大银行", "value"=> "CEB"),
                array( "label"=> "莱商银行", "value"=> "LSBANK"),
                array( "label"=> "辽宁农信银行", "value"=> "LNRCC"),
                array( "label"=> "浙江网商银行", "value"=> "ZJWS"),
                array( "label"=> "浙商银行", "value"=> "ZSB"),
                array( "label"=> "绵阳市商业银行", "value"=> "MYCC"),
                array( "label"=> "天津银行", "value"=> "TCCB"),
                array( "label"=> "宁乡农商银行", "value"=> "NXNS"),
                array( "label"=> "石嘴山银行", "value"=> "SZSCCB"),
                array( "label"=> "张家口银行", "value"=> "ZJKCCB"),
                array( "label"=> "莱芜珠江村镇银行", "value"=> "LPRVB"),
                array( "label"=> "济宁银行", "value"=> "JNBANK"),
                array( "label"=> "齐商银行", "value"=> "QISHANG"),
                array( "label"=> "交通银行", "value"=> "BOCOM"),
                array( "label"=> "民生银行", "value"=> "CMBC"),
                array( "label"=> "苏州银行", "value"=> "SUZHOUBANK"),
                array( "label"=> "青岛农商银行", "value"=> "QDNS"),
                array( "label"=> "南京银行", "value"=> "NJCB"),
                array( "label"=> "郑州银行", "value"=> "ZZ"),
                array( "label"=> "农业银行", "value"=> "ABC"),
                array( "label"=> "农村信用社", "value"=> "RCU"),
                array( "label"=> "广东普宁汇成村镇银行", "value"=> "GDPNHCCZ"),
                array( "label"=> "济南槐荫沪农商村镇银行", "value"=> "JNHSRCB"),
                array( "label"=> "贵阳银行", "value"=> "GUIYANG"),
                array( "label"=> "广东南粤银行", "value"=> "GDNY"),
                array( "label"=> "朝阳银行", "value"=> "CYCB"),
                array( "label"=> "徽商银行", "value"=> "HSBANK"),
                array( "label"=> "浙江农信", "value"=> "ZJRC"),
                array( "label"=> "东莞农村商业银行", "value"=> "DRCB"),
                array( "label"=> "广东农村信用社", "value"=> "GDRCU"),
                array( "label"=> "潍坊银行", "value"=> "BANKWF"),
                array( "label"=> "江南农村商业银行", "value"=> "JNRC"),
                array( "label"=> "吉林农信", "value"=> "JNRCC"),
                array( "label"=> "东亚银行", "value"=> "BEA"),
                array( "label"=> "河北银行", "value"=> "HEBEI"),
                array( "label"=> "中原银行", "value"=> "ZYBANK"),
                array( "label"=> "广州农商银行", "value"=> "GRCBANK"),
                array( "label"=> "河北农信", "value"=> "HBNX"),
                array( "label"=> "西安银行", "value"=> "XACBANK"),
                array( "label"=> "河北省农村信用社", "value"=> "HBNCRCC"),
                array( "label"=> "德州银行", "value"=> "DEZHOU"),
                array( "label"=> "平安银行", "value"=> "PAB"),
                array( "label"=> "厦门国际银行", "value"=> "XIB"),
                array( "label"=> "成都银行", "value"=> "BOCD"),
                array( "label"=> "华夏银行", "value"=> "HXBC"),
                array( "label"=> "张家港农商银行", "value"=> "ZJGRCC"),
                array( "label"=> "柳州银行", "value"=> "LZB"),
                array( "label"=> "北京农商银行", "value"=> "BJAB"),
                array( "label"=> "湖南农商银行", "value"=> "HNRCC"),
                array( "label"=> "深圳农村商业银行", "value"=> "SZNCSY"),
                array( "label"=> "青岛银行", "value"=> "QDCCB"),
                array( "label"=> "台州银行", "value"=> "TZB"),
                array( "label"=> "武汉农商银行", "value"=> "WHNS"),
                array( "label"=> "黑龙江农村信用社", "value"=> "HLJRCC"),
                array( "label"=> "龙江银行", "value"=> "LJB"),
                array( "label"=> "渤海银行", "value"=> "CBHB"),
                array( "label"=> "烟台银行", "value"=> "YTB"),
                array( "label"=> "临商银行", "value"=> "LSBCHINA"),
                array( "label"=> "桂林银行", "value"=> "GUILIN"),
                array( "label"=> "日照银行", "value"=> "RIZHAO"),
                array( "label"=> "江苏银行", "value"=> "JSBCHINA"),
                array( "label"=> "北京银行", "value"=> "BOB"),
                array( "label"=> "东莞银行", "value"=> "BOD"),
                array( "label"=> "广西北部湾银行", "value"=> "GXBBW"),
                array( "label"=> "支付宝", "value"=> "ZFB"),
                array( "label"=> "中信银行", "value"=> "CITIC"),
                array( "label"=> "威海市商业银行", "value"=> "WHCCB"),
                array( "label"=> "兴业银行", "value"=> "CIB"),
                array( "label"=> "邮政储蓄", "value"=> "PSBC"),
                array( "label"=> "上海银行", "value"=> "BOS"),
                array( "label"=> "恒丰银行", "value"=> "HENGFENG"),
                array( "label"=> "中国银行", "value"=> "BOCSH"),
                array( "label"=> "汇丰银行", "value"=> "HSBC"),
                array( "label"=> "广西农村信用社", "value"=> "GXRCC"),
                array( "label"=> "曲靖商业银行", "value"=> "QJCCB"),
                array( "label"=> "深圳福田银座村镇银行", "value"=> "FTYZB"),
                array( "label"=> "广州银行", "value"=> "GZCB"),
                array( "label"=> "自贡银行", "value"=> "ZGB"),
                array( "label"=> "江西银行", "value"=> "JXBANK"),
                array( "label"=> "济宁儒商村镇银行", "value"=> "JNCFV"),
                array( "label"=> "中银富登村镇银行", "value"=> "ZYFDB"),
                array( "label"=> "浦发银行", "value"=> "SPDB"),
                array( "label"=> "抚顺银行", "value"=> "BANKOFFS"),
                array( "label"=> "东营银行", "value"=> "DYCCB"),
                array( "label"=> "哈尔滨银行", "value"=> "HRBCB"),
                array( "label"=> "长沙银行", "value"=> "CSCB"),
                array( "label"=> "江苏江南农村商业银行（江南村镇银行）", "value"=> "JNCZB"),
                array( "label"=> "宁波银行", "value"=> "NBBC"),
                array( "label"=> "锦州银行", "value"=> "JINZHOUBANK"),
                array( "label"=> "重庆农村商业银行", "value"=> "CQRCB"),
                array( "label"=> "华融湘江银行", "value"=> "HRXJBANK"),
                array( "label"=> "甘肃银行", "value"=> "GSB"),
                array( "label"=> "上海农商银行", "value"=> "SRCB"),
                array( "label"=> "太仓农村商业银行", "value"=> "TCRCC"),
                array( "label"=> "成都农商银行", "value"=> "CDRCB"),
                array( "label"=> "山东省农村信用社联合社", "value"=> "SDRCU"),
                array( "label"=> "湖北银行", "value"=> "HBBK"),
                array( "label"=> "建设银行", "value"=> "CCB"),
                array( "label"=> "吉林农村信用社", "value"=> "JLNLS"),
                array( "label"=> "吉林银行", "value"=> "JILIN"),
                array( "label"=> "邯郸银行", "value"=> "GCB"),
                array( "label"=> "厦门银行", "value"=> "XMB"),
                array( "label"=> "盛京银行", "value"=> "SHENGJINGBANK"),
                array( "label"=> "湖北省农村信用社", "value"=> "HBRCC"),
                array( "label"=> "杭州银行", "value"=> "HZBC"),
                array( "label"=> "福建海峡银行", "value"=> "FJHX"),
                array( "label"=> "云南农村信用社", "value"=> "YNRCC"),
                array( "label"=> "莱芜珠江银行", "value"=> "LAIWUZHUJIANG"),
                array( "label"=> "工商银行", "value"=> "ICBC"),
                array( "label"=> "湖南三湘银行", "value"=> "CSXBANK"),
                array( "label"=> "陕西信合", "value"=> "SXNXS")
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}
