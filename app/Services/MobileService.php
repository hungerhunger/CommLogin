<?php
namespace App\Services;
use Log;

class MobileService extends CommService{
    const URL_MOBILE_LOGINFO          = 'http://shop.10086.cn/i/v1/auth/loginfo';
    const URL_MOBILE_SENDRANDOM_LOGIN = 'https://login.10086.cn/sendRandomCodeAction.action';
    const URL_MOBILE_SENDRANDOM_AUTH  = 'https://shop.10086.cn/i/v1/fee/detbillrandomcodejsonp';
    const URL_MOBILE_LOGIN            = 'https://login.10086.cn/login.htm';
    const URL_MOBILE_GETCAPTCHA       = 'http://shop.10086.cn/i/authImg';
    const URL_MOBILE_AUTH             = 'https://shop.10086.cn/i/v1/fee/detailbilltempidentjsonp';
    const URL_MOBILE_BILL             = 'https://shop.10086.cn/i/v1/fee/detailbillinfojsonp';

    const URL_MOBILE_SENDAUTHRANDOM   = 'http://laravel.cn/Comm/sendRandomAuth';

    private $call_count = 0;

    public function checkLogin(){
        $url = self::URL_MOBILE_LOGINFO;
        $res = self::phpCurl($url, $this->cookieFile, null, null, false);
        return $res;
    }

    public function sendRandom($mobile, $state){
        if($state == self::STATE_LOGIN){
            $res = $this->sendRandomLogin($mobile);
        }
        elseif ($state == self::STATE_AUTH){
        //    $res = $this->sendRandomLogin($mobile);
            $res = $this->sendRandomAuth($mobile);
        //    $res = $this->sendAuthRandom($mobile);
        }
        return $res;
    }


    public function sendRandomLogin($mobile){
        @unlink($this->cookieFile);
        $this->checkLogin();
        $url = self::URL_MOBILE_SENDRANDOM_LOGIN;
        $params = array(
            'userName'  =>  $mobile,
            'channelID' =>  '12003',
            'type'      =>  '01'
        );
        $code = self::phpCurl($url, $this->cookieFile, null, $params, true);
        Log::info('sendRandomLogin|'.$code);
        switch ($code){
            case 0:
                $res = self::getErrInfo(self::ERR_SUCC, '发送成功');
                break;
            case 4005:
                $res = self::getErrInfo(self::ERR_RANDOM_MOBILE_WRONG);
                break;
            case 1:
                $res = self::getErrInfo(self::ERR_RANDOM_SYS_ERORR);
                break;
            case 2:
                $res = self::getErrInfo(self::ERR_RANDOM_COUNT_LIMIT);
                break;
            case 3:
                $res = self::getErrInfo(self::ERR_RANDOM_FREQUENCY);
                break;
        }
        if($code != 0){
            if($this->call_count < 1){
                return $this->sendRandomLogin($mobile);
            }
        }
        return $res;
    }

    public function sendRandomAuth($mobile){
        Log::info('sendRandomAuth|request|'.serialize($_REQUEST));
        $url = self::URL_MOBILE_SENDRANDOM_AUTH.'/'.$mobile;
        $params = array(
            'callback'  =>  'jQuery18305039525094747159_'.self::getMicroSecond(),
            '_'         =>  self::getMicroSecond(),
        );
        $referer = 'http://shop.10086.cn/i/?f=home';
        $ret = self::phpCurl($url, $this->cookieFile, $referer, $params, false);
        Log::info('sendRandomAuth|'.$ret);
        $ret = self::getContent($ret);
        $ret = json_decode($ret, true);
        if($ret['retCode'] == '000000'){
            $res = self::getErrInfo(self::ERR_SUCC);
        }
        elseif($ret['retCode'] == '500003'){
            $res = self::getErrInfo(self::ERR_NOT_LOGIN);
        }
        elseif($ret['retCode'] == '555001' || $ret['retCode'] == '555002' || $ret['retCode'] == '550011'){
            $res = self::getErrInfo(self::ERR_RANDOM_FREQUENCY);
        }
        else{
            $res = self::getErrInfo(self::ERR_FAIL);
        }
        return $res;
    }

    public function sendAuthRandom($mobile){
        /*
        $url = self::URL_MOBILE_SENDAUTHRANDOM;
        $params = array(
            'mobile'  =>  $mobile,
        );
        $ret = self::phpCurl($url, null, null, $params, false);
        $res = json_decode($ret, true);
        return $res;
        */
        $url = self::URL_MOBILE_SENDAUTHRANDOM.'?mobile='.$mobile;
        $cmd = "curl ".$url;
        exec($cmd, $output);
        $res = json_decode($output[0], true);
        return $res;
    }

    public function getCaptcha(){
        $url = self::URL_MOBILE_GETCAPTCHA;
        $res = self::phpCurl($url, $this->cookieFile, null, null, false);
    //    return "<img src=\"data:image/png;base64,".base64_encode($res)."\">";
        $res =  "data:image/png;base64,".base64_encode($res);
        return self::getErrInfo(self::ERR_SUCC, null, $res);
    }

    public function getCaptcha1(){
        $url = self::URL_MOBILE_GETCAPTCHA;
        $res = self::phpCurl($url, $this->cookieFile, null, null, false);
        return "<img src=\"data:image/png;base64,".base64_encode($res)."\">";
    //    $res =  "data:image/png;base64,".base64_encode($res);
    //    return self::getErrInfo(self::ERR_SUCC, null, $res);
    }

    public function login($mobile, $service, $random){
        $url = self::URL_MOBILE_LOGIN;
        $params = array(
                'accountType'=> '01',
                'account'    => $mobile,
                'password'   => $service,
                'pwdType'    => '01',
                'smsPwd'     => $random,
                'inputCode'  => '',
                'backUrl'    => 'http://shop.10086.cn/i/sso.html',
                'rememberMe' => '0',
                'channelID'  => '12003',
                'protocol'   => 'https:',
                );
        $referer = "https://login.10086.cn/html/window/loginMini.html?channelID=12003&backUrl=http://shop.10086.cn/i/sso.html";
        $ret = self::phpCurl($url, $this->cookieFile, $referer, $params, false);
        Log::info('login1|'.$ret);
        $data = json_decode($ret, true);
        if($data['code'] == '3007' || $data['code'] == '2036' || $data['code'] == '4005'){
            return self::getErrInfo(self::ERR_SYS_ERROR);
        }
        if($data['code'] == '6001' || $data['code'] == '6002'){
            return self::getErrInfo(self::ERR_RANDOM_WRONG);
        }
        if($data['code'] == '8002'){
            return self::getErrInfo(self::ERR_PASSWORD_WORNG);
        }
        if($data['code'] == '0000'){
            $res = self::getErrInfo(self::ERR_SUCC);
        }
        $url = $data['assertAcceptURL'];
        $params = array(
                'backUrl'  => 'http://shop.10086.cn/i/sso.html',
                'artifact' => $data['artifact'],
                );
        $ret = self::phpCurl($url, $this->cookieFile, null, $params, false);
        Log::info('login2|'.$ret);
        return $res;
    }

    public function auth($mobile, $service, $random, $captcha){
        $url = self::URL_MOBILE_AUTH.'/'.$mobile;
        $params = array(
                'pwdTempSerCode'  => base64_encode($service),
                'pwdTempRandCode' => base64_encode($random),
                'captchaVal'      => $captcha,
                );
        $referer = "http://shop.10086.cn/i/?f=billdetailqry";
        $ret = self::phpCurl($url, $this->cookieFile, $referer, $params, false);
        Log::info('auth|'.$ret);
        $ret = self::getContent($ret);
        $ret = json_decode($ret, true);
        if($ret['retCode'] == '000000'){
            $res = self::getErrInfo(self::ERR_SUCC);
        }
        elseif($ret['retCode'] == '570005'){
            $res = self::getErrInfo(self::ERR_RANDOM_WRONG);
        }
        elseif($ret['retCode'] == '570002'){
            $res = self::getErrInfo(self::ERR_PASSWORD_WORNG);
        }
        else{
            $res = self::getErrInfo(self::ERR_FAIL);
        }
        return $res;
    }

    public function bill($mobile){
        $url = self::URL_MOBILE_BILL.'/'.$mobile;
        $res = array();
        $billContent = '';
        for($i=0; $i<6; $i++){
            $ym = date('Ym',strtotime('-'.$i.' month'));
            $params = array(
                'callback' => $ym,
                'curCuror' => '1',
                'step'     => '1000000',
                'qryMonth' => $ym,
                'billType' => '02',
            );
            $ret = self::phpCurl($url, $this->cookieFile, null, $params, false);
            Log::info('bill|'.$ret);
            $content = self::getContent($ret);
            $result = json_decode($content, true);
            if($result['retCode'] == '000000'){
                $billContent .= $ret."\n";
                $res[] = $result;
            }
            else{
                break;
            }
        }
        if(!empty($billContent)){
            file_put_contents($this->billFile, $billContent);
            $res = self::getErrInfo(self::ERR_SUCC, '获取成功', $res);
        }
        else{
            $res = self::getErrInfo(self::ERR_FAIL, '获取失败');
        }
        return $res;
    }
}
