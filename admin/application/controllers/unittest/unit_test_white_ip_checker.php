<?php
trait unit_test_white_ip_checker{

	/**
     *
     * - true: regular white ip
     * - false: regular non-white ip
     * - false: regular blocked ip
     * - use x-forwarded-for to fake ip
     *   - false: use proxy ip + non-white ip which is not white ip, "<proxy ip>, <non-white ip>"
     *   - true: use proxy ip + non-white ip which is white ip
     *   - false: use proxy ip only
     *   - true: use ss ip and ss is white
	 * 	 - false: use ss ip and ss is not white
	 * - true: regular white ip + proxy ip
	 * - false: regular non-white ip + proxy ip
	 * - false: regular blocked ip + proxy ip
	 * - false: regular white ip + regular non-white ip
     *
     */
    public function unit_test_white_ip_checker_with(){
		// jp proxy
		$proxyIp='35.198.200.57';
		$ssWhiteIp='119.9.106.90';
		$ssNonWhiteIp='13.112.224.75';
		$nonWhiteIp='8.8.8.8';
		$blockedIp='200.200.200.200';
		// twoffice
		$whiteIp='114.32.43.85';
		$remoteAddr='172.69.221.176';
        $this->load->model(['white_ip_checker']);
        // regular white ip
        $xForward=[$whiteIp];
        $this->utils->info_log("test case 1: regular white ip", $remoteAddr, $xForward);
        $exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
        $this->assertTrue($exists);

		// regular non-white ip
		$xForward=[$nonWhiteIp];
		$this->utils->info_log("test case 2: regular non-white ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertFalse($exists);

		// regular blocked ip
		$xForward=[$blockedIp];
		$this->utils->info_log("test case 3: regular blocked ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertFalse($exists);

		// use proxy ip + non-white ip which is not white ip
		$xForward=[$proxyIp, $nonWhiteIp];
		$this->utils->info_log("test case 4: use proxy ip + non-white ip which is not white ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertFalse($exists);

		// use proxy ip + non-white ip which is white ip
		$xForward=[$proxyIp, $whiteIp];
		$this->utils->info_log("test case 5: use proxy ip + non-white ip which is white ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertTrue($exists);

		// use proxy ip only
		$xForward=[$proxyIp];
		$this->utils->info_log("test case 6: use proxy ip only", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertFalse($exists);

		// use ss ip and ss is white ip
		$xForward=[$ssWhiteIp];
		$this->utils->info_log("test case 7: use ss ip and ss is white ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertTrue($exists);

		// use ss ip and ss is not white
		$xForward=[$ssNonWhiteIp];
		$this->utils->info_log("test case 8: use ss ip and ss is not white", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertFalse($exists);

		// regular white ip + proxy ip
		$xForward=[$whiteIp, $proxyIp];
		$this->utils->info_log("test case 9: regular white ip + proxy ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertTrue($exists);

		// regular non-white ip + proxy ip
		$xForward=[$nonWhiteIp, $proxyIp];
		$this->utils->info_log("test case 10: regular non-white ip + proxy ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertFalse($exists);

		// regular blocked ip + proxy ip
		$xForward=[$blockedIp, $proxyIp];
		$this->utils->info_log("test case 11: regular blocked ip + proxy ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertFalse($exists);

		// regular white ip + regular non-white ip
		$xForward=[$whiteIp, $nonWhiteIp];
		$this->utils->info_log("test case 12: regular white ip + regular non-white ip", $remoteAddr, $xForward);
		$exists=$this->white_ip_checker->checkWhiteIpForAdmin($remoteAddr, $xForward);
		$this->assertFalse($exists);

        $this->printAssertionSummary();
    }

}