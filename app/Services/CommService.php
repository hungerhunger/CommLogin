<?php
namespace App\Services;
use Monolog\Logger;


class CommService{
	public function index(){
        echo $this->cookieFile;
	}

    protected $cookieFile = '';
    protected $billFile   = '';
    protected $extFile    = '';
    
    const COOKIE_PATH      = '/home/public/cookie/';
    const BILL_PATH        = '/home/public/bill/';
    const EXT_PATH         = '/home/public/ext/';

    //运营商相关
    const URL_GETPROVIDER  = 'http://chongzhi.jd.com/json/order/search_searchPhone.action';
    const PROVIDER_UNICOM  = 0;
    const PROVIDER_MOBILE  = 1;
    const PROVIDER_TELECOM = 2;

    //阶段
    const STATE_LOGIN   = 1; //登录阶段
    const STATE_AUTH    = 2; //认证阶段
    
    //错误码 
    const ERR_SUCC = 9999;       //   返回成功

    const ERR_FAIL       = 1000;       //   返回失败
    const ERR_SYS_ERROR  = 1001;        //   系统异常，请稍后再试
    const ERR_NOT_LOGIN  = 1002;        //   未登录

    const ERR_PARAM    = 2001;      // 参数不正确


    //短信验证码
    const ERR_RANDOM_MOBILE_WRONG     = 3001;      //手机号码有误，请重新输入
    const ERR_RANDOM_SYS_ERORR        = 3002;      //短信随机码暂时不能发送，请稍后再试
    const ERR_RANDOM_COUNT_LIMIT      = 3003;      //短信随机码获取达到上限
    const ERR_RANDOM_FREQUENCY        = 3004;      //短信发送次数过于频繁
    const ERR_RANDOM_OVERTIME         = 3005;      //验证码已过期，请从新获取新的验证码

    const ERR_RANDOM_WRONG   = 4001;      // 短信随机码不正确或已过期，请重新获取
    const ERR_PASSWORD_WORNG = 4002;      // 您的账户名与密码不匹配，请重新输入




    function __construct ($mobile){
        $this->cookieFile = self::COOKIE_PATH.$mobile.'.cookie';
        $this->billFile   = self::BILL_PATH.$mobile.'.txt';
    }   

    public static function create($mobile){
        $provider = self::getProvider($mobile);
        $service = null;
        switch ($provider){
            case self::PROVIDER_UNICOM :
              $service = new UnicomService($mobile); 
              break;
            case self::PROVIDER_MOBILE :
              $service = new MobileService($mobile); 
              break;
            case self::PROVIDER_TELECOM :
              $service = new TelecomService($mobile); 
              break;
        }
        return $service;
    }

    public static function phpCurl($url, $cookieFile, $referer, $params, $isPost=true, $headers=null, $getHeader=false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($cookieFile){ 
            curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        }
        if ($isPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        else {
            if ($params) {
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            }
        }
        if($referer){
            curl_setopt ($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_HEADER, true);
        if($headers){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $sContent = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $header = substr($sContent, 0, $headerSize);
        $body = substr($sContent, $headerSize);
        return $getHeader ? $header : $body;
    }
    
    public static function getMicroSecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }   

    public static function getProvider($mobile){
        $params = array('mobile' => $mobile); 
        $res = self::phpCurl(self::URL_GETPROVIDER, null, null, $params, false);
        if(!self::isUtf8($res)){
            $res = self::convertEncoding($res, "utf-8" , "gbk");
        }
        $res =  json_decode($res, true);
        return $res['provider'];
    }

    public static function isUtf8($str)
    {
        $a = mb_convert_encoding($str, 'gbk', 'utf-8');
        $b = mb_convert_encoding($a, 'utf-8', 'gbk');
        return $str === $b;
    }

    public static function convertEncoding($arr, $toEncoding, $fromEncoding='', $convertKey=false)
    {
        if (empty($arr))
        {
            return $arr;
        }
        if ($toEncoding == $fromEncoding)
        {
            return $arr;
        }
        if (is_array($arr))
        {
            $keys = array_keys($arr);
            for ($i=0,$max=count($keys);$i<$max;$i++)
            {
                $key = $keys[$i];
                $res = $arr[$key];
                if ($convertKey)
                {
                    unset($arr[$key]);
                    $key = mb_convert_encoding($key, $toEncoding, $fromEncoding);
                }

                if (is_array($res))
                {
                    $res = self::convertEncoding($res, $toEncoding, $fromEncoding, $convertKey);
                }
                else
                {
                    $res = mb_convert_encoding($res, $toEncoding, $fromEncoding);
                }

                $arr[$key] = $res;
            }
        }
        else
        {
            $arr = mb_convert_encoding($arr, $toEncoding, $fromEncoding);
        }
        return $arr;
    }

    public static function getMsg($code)
    {
        $ret = '系统繁忙，请稍后再试！';
        $arr = array(
                        self::ERR_SUCC    => '返回成功',
                        self::ERR_FAIL    => '返回失败',
                        self::ERR_PARAM   => '参数异常',
                        self::ERR_SYS_ERROR  => '系统异常，请稍后再试',
                        self::ERR_NOT_LOGIN  => '未登录',

                        self::ERR_RANDOM_MOBILE_WRONG => '手机号码有误，请重新输入',
                        self::ERR_RANDOM_SYS_ERORR    => '短信随机码暂时不能发送，请稍后再试',
                        self::ERR_RANDOM_COUNT_LIMIT  => '短信随机码获取达到上限',
                        self::ERR_RANDOM_FREQUENCY    => '短信发送次数过于频繁',
                        self::ERR_RANDOM_OVERTIME     => '验证码已过期，请从新获取新的验证码',

                        self::ERR_RANDOM_WRONG        => '短信随机码不正确或已过期，请重新获取',
                        self::ERR_PASSWORD_WORNG      => '您的账户名与密码不匹配，请重新输入',
                    );
        if ($code !== null && isset($arr[$code])) {
            $ret = $arr[$code];
        }
        return $ret;
    }

    public static function getErrInfo($code, $msg=null, $data=null)
    {
        $ret = array('code'=>$code, 'msg'=>self::getMsg($code));
        if($msg){
            $ret['msg'] = $msg;
        }
        if (!empty($data)){
            $ret['result'] = $data;
        }
        return $ret;
    }

    //提取小括号中的内容
    public static function getContent($str){
        $pos1 = strpos($str, '(');
        $pos2 = strpos($str, ')');
        return substr($str, $pos1+1, $pos2-$pos1-1);
    }

    //返回0~1之间的随机小数
    public static  function randFloat(){
        return mt_rand()/mt_getrandmax();
    }

    public static function getLocation($headerStr){
        preg_match('/Location: (.*?)\s/', $headerStr, $matches);
        return $matches[1];
    }

    public function delCookieFile(){
        if(file_exists($this->cookieFile)){
            unlink($this->cookieFile);
        }
    }
}
