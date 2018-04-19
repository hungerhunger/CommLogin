<?php
namespace App\Services;
use App\Tools\AesCrypter as AesCrypter;
use App\Tools\CryptoJSAES as CryptoJSAES;

class TelecomService extends CommService{

    const URL_TELECOM_CHECKLOGIN  = 'http://www.189.cn/login/index.do';
    const URL_TELECOM_CHECKPHONE  = 'http://login.189.cn/web/login/ajax';
    const URL_TELECOM_CHECK       = 'https://uac.10010.com/portal/Service/CheckNeedVerify';
    const URL_TELECOM_SENDMSG     = 'https://uac.10010.com/portal/Service/SendCkMSG';
    const URL_TELECOM_SENDAUTHMSG = 'http://bj.189.cn/iframe/feequery/smsRandCodeSend.action';
    const URL_TELECOM_GETCAPTCHA  = 'http://login.189.cn/web/captcha';
    const URL_TELECOM_LOGIN       = 'http://login.189.cn/web/login';
    const URL_TELECOM_AUTH        = 'http://iservice.10010.com/e3/static/query/verificationSubmit?accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001';
    const URL_TELECOM_BILL        = 'http://iservice.10010.com/e3/static/query/callDetail?accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001';

    const AES_KEY    = 'login.189.cn';
    const AES_IV     = '1234567812345678';

    public function checkLogin($mobile)
    {
        $url = self::URL_TELECOM_CHECKLOGIN;
        $referer = 'http://www.189.cn/html/login/right.html';
        $ret = self::phpCurl($url, $this->cookieFile, $referer, null, false);
        $ret = json_decode($ret, true);
        return $ret;
    }

    public function checkPhone($mobile)
    {
        $url = self::URL_TELECOM_CHECKPHONE;
        $params = array(
            'm'     => 'checkphone',
            'phone' => $mobile,
        );
        $ret = self::phpCurl($url, $this->cookieFile, null, $params, true);
        $ret = json_decode($ret, true);
        return $ret;
    }

    public function checkVefify($mobile)
    {
        $url = self::URL_TELECOM_CHECK;
        $params = array(
            'callback' => 'jQuery17209846638105373287_'.self::getMicroSecond(),
            'userName' => $mobile,
            'pwdType'  => '01',
        );
        $res = self::phpCurl($url, $this->cookieFile, null, $params, false);
        return $res;
    }

    public function sendRandom($mobile, $state){
        $url = self::URL_TELECOM_SENDAUTHMSG;
        $params = array(
            'menuId'     => '000100030001',
        );
        $referer = 'http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001';
        $ret = self::phpCurl($url, $this->cookieFile, $referer, $params, true);
        $ret = json_decode($ret, true);
        if($ret['issuccess']){
            $res = self::getErrInfo(self::ERR_SUCC);
        }
        else{
            $res = self::getErrInfo(self::ERR_FAIL);
        }
        return $res;
    }

    public function getCaptcha(){
        $this->delCookieFile();
        $this->preCookie();
        $url = self::URL_TELECOM_GETCAPTCHA.'?'.'undefined&source=login&width=100&height=37&'.self::randFloat();
        $ret = self::phpCurl($url, $this->cookieFile, null, null, false);
        return "<img src=\"data:image/png;base64,".base64_encode($ret)."\">";
    }

    public function login($mobile, $service, $random, $captcha)
    {
        $phoneInfo = $this->checkPhone($mobile);
        $reqInfo = $this->genReqInfo($mobile, '201', $phoneInfo['provinceId'], 0);
        $reqInfo = CryptoJSAES::encrypt($reqInfo, self::AES_KEY);
        $reqInfo = urlencode($reqInfo);
        $this->cookieWrite('ECS_ReqInfo_login1', $reqInfo);
        $url = self::URL_TELECOM_LOGIN;
        $password = $this->aesEncrypt($service);
        $params = array(
            'Account'    => $mobile,
            'UType'      => '201',
            'ProvinceID' => $phoneInfo['provinceId'],
            'AreaCode'   => '',//$phoneInfo['areaCode'],
            'CityNo'     => '',//$phoneInfo['cityNo'],
            'RandomFlag' => 0,
            'Password'   => $password,
            'Captcha'    => $captcha,
        );
        $referer = 'http://login.189.cn/web/login';
        $headers = array(
            'Pragma: no-cache',
            'Origin: http://login.189.cn',
            'Accept-Encoding: gzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.8',
            'Upgrade-Insecure-Requests: 1',
            'Referer: http://login.189.cn/web/login',
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.89 Safari/537.36',
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Cache-Control: no-cache',
            'Connection: keep-alive',
        );
        $headerStr1 = self::phpCurl($url, $this->cookieFile, null, $params, true, $headers, true);
        $location1 = self::getLocation($headerStr1);
        if(!$location1){
            return self::getErrInfo(self::ERR_FAIL);
        }

        $headerStr2 = self::phpCurl($location1, $this->cookieFile, $referer, null, false, null, true);
        $location2 = self::getLocation($headerStr2);

        $headerStr3 = self::phpCurl($location2, $this->cookieFile, $referer, null, false, null, true);
        $location3 = self::getLocation($headerStr3);

        file_put_contents($this->extFile, $location3);
        $headerStr4 = self::phpCurl($location3, $this->cookieFile, $referer, null, false, null, true);

        return self::getErrInfo(self::ERR_SUCC);
    }

    public function auth($mobile, $service, $random, $captcha){
        $url = self::URL_TELECOM_AUTH;
        $params = array(
            'inputcode'  => $random,
            'menuId'     => '000100030001',
        );
        $referer = "http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001";
        $ret = self::phpCurl($url, $this->cookieFile, $referer, $params, true);
        $ret = json_decode($ret, true);
        $code = $ret['flag'];
        if($code == '00'){
            $res = self::getErrInfo(self::ERR_SUCC);
        }
        elseif($code == '01'){
            $res = self::getErrInfo(self::ERR_RANDOM_OVERTIME);
        }
        elseif($code == '02'){
            $res = self::getErrInfo(self::ERR_RANDOM_WRONG);
        }
        else{
            $res = self::getErrInfo(self::ERR_FAIL);
        }
        return $res;
    }

    public function bill($mobile){
        $url = self::URL_TELECOM_BILL;
        $res = array();
        $billContent = '';
        for($i=0; $i<6; $i++){
            $firstDay = date('Ym01', strtotime('-'.$i.' month'));
            $endDay  = date('Ymd', strtotime($firstDay.' +1 month -1 day'));
            $params = array( 'pageNo'    => 1,
                'pageSize'  => 100000,
                'beginDate' => $firstDay,
                'endDate'   => $endDay,
            );
            $ret = self::phpCurl($url, $this->cookieFile, null, $params, true);
            $result = json_decode($ret, true);
            if($result['isSuccess']){
                $billContent .= $ret."\n";
                $res[] = $result;
            }
            else{
                break;
            }
        }
        if(!empty($billContent)){
            file_put_contents($this->billFile, $billContent);
            $res = self::getErrInfo(self::ERR_SUCC, '', $res);
        }
        else{
            $res = self::getErrInfo(self::ERR_FAIL);
        }
        return $res;
    }

    public function aesEncrypt($pass){
        $aes = new AesCrypter(self::AES_KEY, self::AES_IV);
        $res = $aes->encrypt($pass);
        return $res;
    }

    public function genSfid(){
        $f = 8;
        $e = 4;
        $b = '';
        $d = '';
        for ($c = 0; 16 > $c; $c++) {
            $f = mt_rand(0, $f);
            $b .= substr("0123456789ABCDEF", $f, 1);
            $e = mt_rand(0, $e);
            $d .= substr("0123456789ABCDEF", $e, 1);
            $f = $e = 16;
        }
        return $b."-".$d;
    }

    public function genTrkId(){
        $chars = str_split('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
        $uuid = array();
        $rnd = 0;
        $r = 0;
        for ($i = 0; $i < 36; $i++) {
            if ($i == 8 || $i == 13 || $i == 18 || $i == 23) {
                $uuid[$i] = '-';
            } else if ($i == 14) {
                $uuid[$i] = '4';
            } else {
                if ($rnd <= 0x02)
                    $rnd = 0x2000000 + mt_rand(0, 0x1000000) | 0;
                $r = $rnd & 0xf;
                $rnd = $rnd >> 4;
                $uuid[$i] = $chars[($i == 19) ? ($r & 0x3) | 0x8 : $r];
            }
        }
        return implode('', $uuid);
    }

    public function genLvid(){
        $b = str_split('abcdef1234567890');
        $a = '';
        for ($n = 0; $n < 32; $n++) {
            $a .= $b[mt_rand(0, 15)];
        }
        return $a;
    }

    public function genReqInfo($mobile, $uType, $proviceId, $randomFlag){
        $res = $mobile.'$$'.$uType.'$地市（中文/拼音）$'.$proviceId.'$$$'.$randomFlag;
        return $res;
    }

    public function cookieWrite($key, $value) {
        $domain = '.189.cn';
        $path = '/';
        if(!file_exists($this->cookieFile)){
            $coo = fopen($this->cookieFile, "w+");
            $notes = "# Netscape HTTP Cookie File\n# http://curl.haxx.se/docs/http-cookies.html\n# This file was generated by libcurl! Edit at your own risk.\n\n";
            fwrite($coo, $notes);
            fclose($coo);
        }
        $coo = fopen($this->cookieFile, "a+");
        $content = $domain."\t"."TRUE"."\t".$path."\t"."FALSE"."\t"."0"."\t".$key."\t".$value."\n";
        fwrite($coo, $content);
        fclose($coo);
    }

    public function preCookie(){
        $sFid = $this->genSfid();
        $this->cookieWrite('s_fid', $sFid);

        $this->cookieWrite('loginStatus', 'non-logined');

        $lVid = $this->genLvid();
        $this->cookieWrite('lvid', $lVid);

        $this->cookieWrite('nvid', 1);

        $trkId = $this->genTrkId();
        $this->cookieWrite('trkId', $trkId);

        $this->cookieWrite('trkHmClickCoords', '808%2C470%2C933');

    }
}
