<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_huibo extends BaseTesting {

	private $platformCode = HUIBO_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		// list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		// $this->test($loaded, true, 'Is API class loaded. Expected: true');
		// if (!$loaded) {
		// 	$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
		// 	return;
		// }

		// $this->api = $this->$apiClassName;
		// $this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
	}

	## all tests route through this function
	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	# Actual Tests
	## Invokes all tests defined below. A test function's name should begin with 'test'
	public function testAll() {
		$classMethods = get_class_methods($this);
		$excludeMethods = array('test', 'testTarget', 'testAll');
		foreach ($classMethods as $method) {
			if (strpos($method, 'test') !== 0 || in_array($method, $excludeMethods)) {
				continue;
			}

			$this->$method();
		}
	}

	private function testDecrypt() {
		$privKey='-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAv/3Gb6YK74qZUSeD2EIRKQhWknyzYbQutceMN+Se7H/KN24D
YVTiaFDzUjbmhhHZPDew+O2kmrMW8V4VlV31AAS+oPDMUEeIcHqwWq0jjLCP8T33
KTS5XobTBggWGVBeloPr6JQs8tk+x6tgCBLy1w5fr/RCP0SPkGFL/UFE7OFtGmlm
wFH2xRsBTk3LDRTfUik7UEpCvVy61PQS9WUKlA/5MDaq2AYVN4qyl93LinmUaUzD
5T8x4FDFpK79Yz1+EzFs76NdeInmvC6Hm0Qu59AlZNT21OE1WtU4zM370fsfwIX7
yFoj6LedDciHbOX59qDg7cNj4WCSncecAbkA+QIDAQABAoIBADewoxjLryxgpaxW
q/XU5Clk08fWCCp1G397DH9B/59WSg/eB3j6KFpd8NaOOjv1fW/sL5dlR3PgMu18
fwI+qMyCk9EXSAyZU0hdLj5/Lmqm5HnzoXgAZSy4Kwn62n4pQ+ahCZMDOC6ROZat
bUOqS2p6LuNZFMZJWuNkdahWgGm9UUxHsaQe3EIeci7vgGnISKC4ObZEtmyj+mpn
gIIJAG7+SmfHJr3wxGiV+bbr4ncoQgR4DCS5Ji0epGdVWXPK0xD4ZpI8KwGzRG7s
oLSOoqmI+97fOjWxUz3AnHRtKSRcsAe+LAI8tMQeR9qMxPVN7Vcs2+hgscClFVFn
bReqpgECgYEA3xEy9rItPOk8ZOpFoTCc5EoEHs+STgt6PBW+hrtoV1glbB7VhfDE
0muAJf6eVZNaTXW+ahWk2QsRKXjMZOO9RBL1+bFM3t2x4EMJnvh4kJNfdsFLtVcT
5XDTZULYI4vE7cxmbhoGNLHaieu253/RCRTtghpBqLLAhR2CbMcIwSECgYEA3FYR
DjWe6rPWXYKUEDQZkwCXmTlJOWIaa87HFRtzk7ICLfdCnzE4V2H5HpCAAEDUHiNH
4qXrfA7Y+VxQePiI8PzfbPWinjMDQFK5IIiEeLOuQmxV02cvM6WLgYpQzdSi//ya
v8xSYr8hpaNykFuTpyaQ72nyjXVeESFmylmHzNkCgYBZYlXunqUb9EXcFjgCiC1G
GRaflgOFPHolm3z2FiCQZ2TPd2eOPVVRD/yQvP+LQPl9coHzlmqxAgtFd/9HKi7M
GWDxRgeMgn9lYtd3GQ+Ot08YkgoZRyRU2yoKOIfNbpNhynb9BXaZJO9yNr7a8s+7
eORXLthCliYo8RMzLWonoQKBgQCx8BeqAAesxzsxHUjxpQGaQ/op1aea3e00bzM7
ioXWGwXDlVGKOjej6g1Db7LPgYtMI3XkRdZcw6jaCIE8kIoXBEBzQBPp6oyPn769
1UIWMBVksxTEuynVbbWyEb+b2kMgtCFND3bhJDXKDKtyhWJCtNSLZGZrcwNaljzq
4U7YUQKBgEYk+3faZZ1M+zTBAcYY+H3qh2cFUx7fW7c1XAN5Uy3r7h0ZQeBN8HEA
5G5iyHxJpaKZp3eVOFPCoWWOD9lWYKK2GI0gsIVugNyL+Zup9oZomcgb2FAKGCix
gwVt0VM7yTWJPNUfxTHiRkC23a9TWQHcChoRImgp0uPBp4HEuE+1
-----END RSA PRIVATE KEY-----';
		$priKey=openssl_pkey_get_private($privKey);

		$pubKey='-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAg7pwBwcQWYEF72LAXZap
EgIfIQB5NY3RVcKLF7/mbClEt5x3QODh2ttCtL/SI2rdrvGcyqsMlTCX44TkqZaq
fP3KLxRjJ4qvURpWKxC7z/uIFC+lRumzxnhJqLIOC13kf42MUWgg5sKHnA3XQqlX
RPdX1ZJ/lK+a2d5F0H8tW9uJiqqpfC1qY/fkiPuBh0XgiCHZmqj7VcrLg4P+p0lD
moyXHFFDmQG22rj1TAzcn855Ebdt4vnXENH3fLP3rSE4bCKxkrmZ3AUr9cNhpx4t
FbiRl7Tzv3lLPquzHKu9gFdkImkcra0EYREZKw6kUUmXcpxvxSBt0hzpoqr1L5X6
JwIDAQAB
-----END PUBLIC KEY-----';
		$pubKey=openssl_get_publickey($pubKey);
		//test
		$response='{"data":"m2DnajtPnKKCuMg7OSsjicgdbwAoIeVAGShYV9GmXmmew0EnTbP/GfMBlqr7b047AbM0Ub/nQB6Va4N2OkRYb5r/WmXqNfMx+UPg5ozMl0cLDf2PLcNaQaosy5ClbX9uFv1rcJzEmBX0NKwuZgfb4txaFlyH/zgv0B6m4f1v1XJmiLSMtfqRyroDd3XldmOKCu8E8nSfTdVGwiR7QsG40tO/3qnrRM4nyQitL18Z2rG+sPt1EHcJQjtJsqawwqPS+KPGVOiwcqe2JHMNk+P4FnHLGA2tDUZ24HxImMQ0mK+ANkG9vSZ1JPKwnIHZ6kzczCjr+UskpRE90sUJ/6tmzQ==","signature":"Upw/32w1ktrwU0VS1MuyP3uYWtEY8Dg6vD45Y9RVZ5ZOVnOdaecEvKFMLjoMEwvHxKrCcKsJJlLPHRNZwRIAsfxpYnYB1JBt1dGUxHJsH+J4rWnyp0GMQsDDpo9+iOs4TpvpqXiu9CGgFHXxnVtRV2AR62dkUw/BtUtBgev9/Bj2KxJz13DMv/Dr0F2caQYTtLNS4AVH04v7+ozdvLIsKbOknODN+acGIZS+LwEUQPJnhrctAov4XWQl5CGHQzRHthbWL+STqQQPatAJU7mizTU+eve5ejrWy24G17CeOJBiSSqbK1FrQooF29kxeYtc4W54ancGQkgGgmogzq8Bpg=="}';
		$response = json_decode($response);
		$odata = $response->data;
		$signature = $response->signature;
		$decrypted = '';
		$data = str_split(base64_decode($odata), 256);
		foreach($data as $chunk) {
			$partial = '';
			$decryptionOK = openssl_private_decrypt($chunk, $partial, $priKey);

			if($decryptionOK === false){
				throw new Exception('decrypt wrong');
				// $this->CI->utils->error_log("api response decrypt failure");
				// return array(
				// 	'success' => false,
				// 	'type' => self::REDIRECT_TYPE_ERROR,
				// 	'message' => lang('Invalid API response'),
				// );
			}
			$decrypted .= $partial;
		}

		$decrypted = base64_decode($decrypted);
		$response = json_decode($decrypted);
		$signature = base64_decode($signature);
		if(openssl_verify($response->msg,$signature,$pubKey,OPENSSL_ALGO_MD5)) {
			$response = json_decode($response->msg);

			$this->utils->debug_log('response', $response);
		}

	}

}
