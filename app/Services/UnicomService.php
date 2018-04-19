<?php
namespace App\Services;

class UnicomService extends CommService{

    const URL_UNICOM_CHECKLOGIN  = 'http://iservice.10010.com/e3/static/check/checklogin';
    const URL_UNICOM_CHECK       = 'https://uac.10010.com/portal/Service/CheckNeedVerify';
    const URL_UNICOM_SENDMSG     = 'https://uac.10010.com/portal/Service/SendCkMSG';
    const URL_UNICOM_SENDAUTHMSG = 'http://iservice.10010.com/e3/static/query/sendRandomCode?accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001';
    const URL_UNICOM_GETCAPTCHA  = 'http://uac.10010.com/portal/Service/CreateImage';
    const URL_UNICOM_LOGIN       = 'https://uac.10010.com/portal/Service/MallLogin';
    const URL_UNICOM_AUTH        = 'http://iservice.10010.com/e3/static/query/verificationSubmit?accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001';
    const URL_UNICOM_BILL        = 'http://iservice.10010.com/e3/static/query/callDetail?accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001';

    private $call_count = 0;

    public function checkLogin($mobile)
    {
        $url = self::URL_UNICOM_CHECKLOGIN;
        $ret = self::phpCurl($url, $this->cookieFile, null, array(), true);
        return $ret;
    }

    public function checkVefify($mobile)
    {
        $url = self::URL_UNICOM_CHECK;
        $params = array(
            'callback' => 'jQuery17209846638105373287_'.self::getMicroSecond(),
            'userName' => $mobile,
            'pwdType'  => '01',
        );
        $res = self::phpCurl($url, $this->cookieFile, null, $params, false);
        return $res;
    }

    public function sendRandom($mobile, $state){
        if($state == self::STATE_LOGIN){
            $res = $this->sendRandomLogin($mobile);
        }
        elseif ($state == self::STATE_AUTH){
            $res = $this->sendRandomAuth($mobile);
        }
        return $res;
    }

    public function sendRandomLogin($mobile){
        $this->checkVefify($mobile);
        $url = self::URL_UNICOM_SENDMSG;
        $params = array(
            'callback'  =>  'jQuery17206254386466759905_'.self::getMicroSecond(),
            'req_time'  =>  self::getMicroSecond(),
            'mobile'    =>  $mobile,
        );
        $ret = self::phpCurl($url, $this->cookieFile, null, $params, false);
        $ret = self::getContent($ret);
        $code = substr($ret, strpos($ret, 'resultCode')+12, 4);
        if($code == '0000'){
            $res = self::getErrInfo(self::ERR_SUCC);
        }
        elseif($code == '7096'){
            $res = self::getErrInfo(self::ERR_RANDOM_FREQUENCY);
        }
        elseif($code == '7098'){
            $res = self::getErrInfo(self::ERR_RANDOM_COUNT_LIMIT);
        }
        else{
            $res = self::getErrInfo(self::ERR_FAIL);
            unlink($this->cookieFile);
            if($this->call_count < 1){
                return $this->sendRandomLogin($mobile);
            }
        }
        return $res;
    }

    public function sendRandomAuth($mobile){
        $this->checkLogin($mobile);
        $url = self::URL_UNICOM_SENDAUTHMSG;
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
        $url = self::URL_UNICOM_GETCAPTCHA;
        $query = array(
            't' => self::getMicroSecond(),
        );
        $res = self::phpCurl($url, $this->cookieFile, null, $query, false);
        return "<img src=\"data:image/png;base64,".base64_encode($res)."\">";
    }

    public function login($mobile, $service, $random, $captcha)
    {
        $url = self::URL_UNICOM_LOGIN;
        $query = array(
            'callback'    => 'jQuery17203329943988508435_'.self::getMicroSecond(),
            'req_time'    => self::getMicroSecond(),
            'redirectURL' => 'http://www.10010.com',
            'userName'    => $mobile,
            'password'    => $service,
            'pwdType'     => '01',
            'productType' => '01',
            'redirectType'=> '03',
            'rememberMe'  => '1',
            'verifyCKCode'=> $random,
        );
        $referer = "http://uac.10010.com/portal/hallLogin";
        $ret = self::phpCurl($url, $this->cookieFile, $referer, $query, false);
        $ret = self::getContent($ret);
        $code = substr($ret, strpos($ret, 'resultCode')+12, 4);
        if($code == '0000' || $code == '0301'){
            $res = self::getErrInfo(self::ERR_SUCC);
        }
        elseif($code == '7007' || $code == '7006'){
            $res = self::getErrInfo(self::ERR_PASSWORD_WORNG);
        }
        else{
            $res = self::getErrInfo(self::ERR_FAIL);
        }
        return $res;
    }

    public function auth($mobile, $service, $random, $captcha){
        $url = self::URL_UNICOM_AUTH;
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
        $url = self::URL_UNICOM_BILL;
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
}
