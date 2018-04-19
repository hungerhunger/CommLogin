<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class PhoneController extends Controller
{
    private $cookieStr = '';
    public static function getResContent($url,$cookieStr, $referer, $params, $isPost = true) {
    $cookieFile = '/home/public/13552290490.cookie';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        if ($isPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        else {
            if ($params) {
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        if($cookieStr){
    //        curl_setopt($ch,CURLOPT_COOKIE, $cookieStr);
        }
        if($referer){
            curl_setopt ($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_HEADER, true);
        $sContent = curl_exec($ch);

        /* 获得响应结果里的：头大小 */
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        /* 根据头大小去获取头信息内容 */
        $header = substr($sContent, 0, $headerSize);
        $html = substr($sContent, $headerSize);
        return array('header'=>$header, 'html'=>$html);
    }

    public function loginInfo()
    {
        $url = 'http://shop.10086.cn/i/v1/auth/loginfo';
        $ret = self::getResContent($url, $this->cookieStr, null, null, false);
        return response()->json(['ret' => $ret]);
    }

    //将用户名和密码录入临时文件，
    public function sendMsg(Request $request)
    {
        $this->validate($request, [
            'mobile' => 'required',
        ]);
        $mobile =  $request->get('mobile');
        $url = 'https://login.10086.cn/sendRandomCodeAction.action';
        $postData = array('userName'  =>  $mobile,
                          'channelID' =>  '12003',
                          'type'      =>  '01');
        $ret = self::getResContent($url, $this->cookieStr, null, $postData, true);
        return response()->json(['ret' => $ret]);
    }

    //将用户名和密码录入临时文件，
    public function login(Request $request)
    {
        $this->validate($request, [
            'mobile'  => 'required',
            'service' => 'required',
            'random'  => 'required',
        ]);

        $url = 'https://login.10086.cn/login.htm';
        $query = array(
            'accountType'=> '01',
            'account'    => $request->get('mobile'),
            'password'   => $request->get('service'),
            'pwdType'    => '01',
            'smsPwd'     => $request->get('random'),
            'inputCode'  => '',
            'backUrl'    => 'http://shop.10086.cn/i/sso.html',
            'rememberMe' => '0',
            'channelID'  => '12003',
            'protocol'   => 'https:',
        );
        $referer = "https://login.10086.cn/html/window/loginMini.html?channelID=12003&backUrl=http://shop.10086.cn/i/sso.html";
        $ret = self::getResContent($url, $this->cookieStr, $referer, $query, false);
        $data = json_decode($ret['html'], true);
        $url = $data['assertAcceptURL'];
        $query = array(
            'backUrl'  => 'http://shop.10086.cn/i/sso.html',
            'artifact' => $data['artifact'],
        );
        $ret = self::getResContent($url, $this->cookieStr, null, $query, false);
        return response()->json(['ret' => $ret]);
    }


    //将用户名和密码录入临时文件，
    public function getImg(Request $request)
    {
        $url = 'http://shop.10086.cn/i/authImg';
//$ret = getResContent($url, $cookieStr, $postData, true);
        $ret = self::getResContent($url, $this->cookieStr, null, null, false);
        echo "<img class=\"confirm_img\" src=\"data:image/png;base64,".base64_encode($ret['html'])."\">";
     //   return response($res, 200);
         //   ->header('Transfer-Encoding', 'chunked')
         //   ->header('wtlwpp-rest', '176_8711');
    }

    //将用户名和密码录入临时文件，
    public function auth(Request $request)
    {
        $this->validate($request, [
            'mobile'  => 'required',
            'service' => 'required',
            'random'  => 'required',
            'verify'  => 'required',
        ]);

        $url = 'https://shop.10086.cn/i/v1/fee/detailbilltempidentjsonp/13552290490';
        $query = array(
            'pwdTempSerCode'  => base64_encode($request->get('service')),
            'pwdTempRandCode' => base64_encode($request->get('random')),
            'captchaVal'      => $request->get('verify'),
        );
        $referer = "http://shop.10086.cn/i/?f=billdetailqry";
        $ret = self::getResContent($url, $this->cookieStr, $referer, $query, false);

        return response()->json(['ret' => $ret]);
    }

    public function bill()
    {
        $url = "https://shop.10086.cn/i/v1/fee/detailbillinfojsonp/13552290490?callback=jQuery18302888697337777453_1509698260039&curCuror=1&step=100&qryMonth=201709&billType=02&_=1509698535610";
//$cookieStr = 'cmccssotoken=8712b64a297b4997b5aa17f470fe1911@.10086.cn;is_login=true;userinfokey=%7b%22loginType%22%3a%2201%22%2c%22provinceName%22%3a%22100%22%2c%22pwdType%22%3a%2201%22%7d;defaultloginuser_p=null;c=8712b64a297b4997b5aa17f470fe1911;verifyCode=de48d088ddf37d58cb5ee0fe8b9159cab72b29be;CITY_INFO=100|10;';$cookieStr = "jsessionid-echd-cpt-cmcc-jt=12B1442138F02CF78AA461840E9D764A";
        $ret = self::getResContent($url, $this->cookieStr, null, null, false);

        echo $ret['html'];
    }
}
