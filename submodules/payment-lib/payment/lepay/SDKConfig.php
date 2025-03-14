<?php
namespace lepay_sdk;

// ######(以下配置为PM环境：入网测试环境用，生产环境配置见文档说明)#######
// 签名证书路径
//生产环境测试账户使用 pdtclient.pfx
//测试环境测试账户使用 testclient.pfx
//生产环境商户生产账户，商户需要按照开发者文档生成商户自己的签名证书文件，证书存放的路径根据商户的开发环境配置
const SDK_SIGN_CERT_PATH = 'C:/xampp/htdocs/appapi.php/certs/pdtclient.pfx';

// 签名证书密码
const SDK_SIGN_CERT_PWD = '123456';

// 密码加密证书（这条一般用不到的请随便配）C:/certs/acp_test_enc.cer
//生产环境测试账户使用pdtserver.pem
//测试环境测试账户使用testserver.pem
//生产环境商户生产账户文件名不需要更改，商户需要根据商户的开发环境配置
const SDK_ENCRYPT_CERT_PATH = 'C:/xampp/htdocs/appapi.php/certs/pdtserver.pem';

// 验签证书路径（请配到文件夹，不要配到具体文件）
const SDK_VERIFY_CERT_DIR = 'C:/xampp/htdocs/appapi.php/certs';

// 前台请求地址
const SDK_FRONT_TRANS_URL = 'https://101.231.204.80:5000/gateway/api/frontTransReq.do';

// 后台请求地址
//const SDK_BACK_TRANS_URL = 'https://101.231.204.80:5000/gateway/api/backTransReq.do';

//生产环境测试账户使用https://openapi.unionpay95516.cc/pre.lepay.api/order/add
//测试环境测试账户使用http://lepay.asuscomm.com/pre.lepay.api/order/add
 const SDK_BACK_TRANS_URL = 'https://openapi.unionpay95516.cc/pre.lepay.api/order/add';

//const SDK_BACK_TRANS_URL = 'http://lepay.asuscomm.com/lepay.appapi/order/add.json';


// 批量交易
const SDK_BATCH_TRANS_URL = 'https://101.231.204.80:5000/gateway/api/batchTrans.do';

//单笔查询请求地址
const SDK_SINGLE_QUERY_URL = 'https://101.231.204.80:5000/gateway/api/queryTrans.do';

//文件传输请求地址
const SDK_FILE_QUERY_URL = 'https://101.231.204.80:9080/';

//有卡交易地址
const SDK_Card_Request_Url = 'https://101.231.204.80:5000/gateway/api/cardTransReq.do';

//App交易地址
const SDK_App_Request_Url = 'https://101.231.204.80:5000/gateway/api/appTransReq.do';

// 前台通知地址 (商户自行配置通知地址)
const SDK_FRONT_NOTIFY_URL = 'http://localhost:8085/upacp_demo_wtz/demo/api_03_wtz/FrontReceive.php';

// 后台通知地址 (商户自行配置通知地址，需配置外网能访问的地址)
const SDK_BACK_NOTIFY_URL = 'http://222.222.222.222/upacp_demo_wtz/demo/api_03_wtz/BackReceive.php';

//文件下载目录
const SDK_FILE_DOWN_PATH = 'C:/file/';

//日志 目录
const SDK_LOG_FILE_PATH = 'C:/logs/';

//日志级别，关掉的话改PhpLog::OFF
const SDK_LOG_LEVEL = PhpLog::DEBUG;


/** 以下缴费产品使用，其余产品用不到，无视即可 */
// 前台请求地址
const JF_SDK_FRONT_TRANS_URL = 'https://101.231.204.80:5000/jiaofei/api/frontTransReq.do';
// 后台请求地址
const JF_SDK_BACK_TRANS_URL = 'https://101.231.204.80:5000/jiaofei/api/backTransReq.do';
// 单笔查询请求地址
const JF_SDK_SINGLE_QUERY_URL = 'https://101.231.204.80:5000/jiaofei/api/queryTrans.do';
// 有卡交易地址
const JF_SDK_CARD_TRANS_URL = 'https://101.231.204.80:5000/jiaofei/api/cardTransReq.do';
// App交易地址
const JF_SDK_APP_TRANS_URL = 'https://101.231.204.80:5000/jiaofei/api/appTransReq.do';

?>