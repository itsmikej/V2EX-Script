<?php
/**
 * v2ex获取金币脚本
 */

set_time_limit(180);

class v2ex_get_coin
{
    static protected $login_url = 'http://v2ex.com/signin';
    static protected $coin_url = 'http://v2ex.com/mission/daily';
    static protected $get_coin_url = 'http://v2ex.com/mission/daily/redeem?once=';

    const USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0';

    static protected $v2exObj = null;
    static public function init()
    {
        if (!isset(self::$v2exObj) || !self::$v2exObj instanceof v2ex_get_coin) {
            self::$v2exObj = new v2ex_get_coin();
        }
        return self::$v2exObj;
    }

    public function go($u, $p)
    {
        $loginCode = $this->getLoginCode($this->send(self::$login_url));
        $this->login($u, $p, $loginCode);
    }

    protected function login($u, $p, $loginCode)
    {
        fwrite(STDOUT, "logining...\n");
        $postData = "u=".urlencode($u)."&p=".urlencode($p)."&once=".$loginCode."&next=".urlencode("/");
        $this->send(self::$login_url, $postData, self::$login_url);
        fwrite(STDOUT, "login success!\n");
        $this->getCoin();
    }

    protected function getCoin()
    {
        $coinCode = $this->getCoinCode($this->send(self::$coin_url));
        if ($coinCode) {
            fwrite(STDOUT, "get coin...\n");
        } else {
            fwrite(STDOUT, "get coin failed...\n");
            exit;
        }
        $infoHtml = $this->send(self::$get_coin_url . $coinCode);
        if(preg_match("/每日登录奖励已领取/", $infoHtml)){
            fwrite(STDOUT, "ok!\n");
            $this->logger("success!");
        }else{
            fwrite(STDOUT, "false!\n");
            $this->logger("false!");
        }
    }

    protected function getLoginCode($data)
    {
        if (preg_match("/value=\"(\d{5})\"\sname=\"once\"/", $data, $matches)) {
            return $matches[1];
        } else {
            $this->logger("login code error!");
        }
        return true;
    }

    protected function getCoinCode($data){
        if (preg_match("/\'\/mission\/daily\/redeem\?once=(\d{5})\'\;/", $data, $matches)) {
            return $matches[1];
        } else {
            $this->logger("can not find coin code!");
        }
        return true;
    }


    static protected $error = 0;
    protected function send($url, $postData='', $referer='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeader());
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        if (!empty($postData) && !empty($referer)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookieFile());
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookieFile());
        $data = curl_exec($ch);
        curl_close($ch);
        ++ self::$error;
        if ($data === false) {
            $this->logger('error'. self::$error . ':' . curl_error($ch));
            return false;
        } else {
            return $data;
        }
    }

    protected function getHttpHeader()
    {
        return array(
            'CLIENT-IP: ' . $this->randIp(),
            'X-FORWARDED-FOR: ' . $this->randIp()
        );
    }

    protected function randIp()
    {
        return mt_rand(60, 255).'.'.mt_rand(60, 255).'.'.mt_rand(60, 255).'.'.mt_rand(60, 255);
    }

    protected function logger($message)
    {
        $data = date("Y-m-d H:i:s")."\t".$message."\r\n";
        file_put_contents(__DIR__ . '/v2ex.log', $data, FILE_APPEND);
        exit;
    }

    protected function getCookieFile()
    {
        return '/tmp/v2ex.cookie';
    }
}

v2ex_get_coin::init()->go("username", "password");