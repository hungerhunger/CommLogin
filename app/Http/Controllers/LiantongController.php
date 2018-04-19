<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class PhoneController extends Controller
{
    private $cookieStr = null;

    public static function getResContent($url,$cookieStr, $referer, $params, $isPost = true) {
    $cookieFile = '/home/public/13256988074.cookie';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
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
        if($cookieStr){
            curl_setopt($ch,CURLOPT_COOKIE, $cookieStr);
        }
        if($referer){
            curl_setopt ($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_HEADER, true);
        $sContent = curl_exec($ch);

        /* 获得响应结果里的：头大小 */
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        /* 根据头大小去获取头信息内容 */
        $header = substr($sContent, 0, $headerSize);
        $html = substr($sContent, $headerSize);
        return array('header'=>$header, 'html'=>$html);
    }

    public function loginInfo()
    {
        $url = 'https://uac.10010.com/portal/Service/CheckNeedVerify';
    //    $url = 'http://iservice.10010.com/e3/static/check/checklogin';
        $query = array(
            'callback' => 'jQuery17209846638105373287_'.self::getMillisecond(),
            'userName' => '13256988074',
            'pwdType'  => '01',
        );
        $ret = self::getResContent($url, $this->cookieStr, null, $query, false);
        return response()->json(['ret' => $ret]);
    }


    public function checkLogin()
    {
        $url = 'http://iservice.10010.com/e3/static/check/checklogin';
     //   $url = 'http://iservice.10010.com/e3/static/bind';
        $ret = self::getResContent($url, $this->cookieStr, null, array(), true);
        return response()->json(['ret' => $ret]);
    }


    public static function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    //将用户名和密码录入临时文件，
    public function sendMsg(Request $request)
    {
        $this->validate($request, [
            'mobile' => 'required',
        ]);
        $mobile =  $request->get('mobile');
        $url = 'https://uac.10010.com/portal/Service/SendCkMSG';
        $postData = array(
                            'callback'  =>  'jQuery17206254386466759905_'.self::getMillisecond(),
                            'req_time'  =>  self::getMillisecond(),
                            'mobile'    =>  $request->get('mobile'),
                         );
        $ret = self::getResContent($url, $this->cookieStr, null, $postData, false);
        return response()->json(['ret' => $ret]);
    }

    //将用户名和密码录入临时文件，
    public function sendAuthMsg(Request $request)
    {
       // $url = 'http://iservice.10010.com/e3/static/query/sendRandomCode?_=1510407396307&accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001';
        $url = 'http://iservice.10010.com/e3/static/query/sendRandomCode?accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001&_='.self::getMillisecond();
        $postData = array(
            'menuId'     => '000100030001',
        );
        $referer = 'http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001';
        $ret = self::getResContent($url, $this->cookieStr, $referer, $postData, true);
        return response()->json(['ret' => $ret]);
        /*
        $url = "http://iservice.10010.com/e3/static/query/accountBalance/search";
        $postData = array(
                'type' => 'onlyAccount',
        );
        $ret = self::getResContent($url, $this->cookieStr, null, $postData, true);
        return response()->json(['ret' => $ret]);
        */
    }


    //将用户名和密码录入临时文件，
    public function login(Request $request)
    {
        $this->validate($request, [
            'mobile'  => 'required',
            'service' => 'required',
            'random'  => 'required',
        ]);

        $url = 'https://uac.10010.com/portal/Service/MallLogin';
        $query = array(
            'callback'    => 'jQuery17203329943988508435_'.self::getMillisecond(),
            'req_time'    => self::getMillisecond(),
            'redirectURL' => 'http://www.10010.com',
            'userName'    => $request->get('mobile'),
            'password'    => $request->get('service'),
            'pwdType'     => '01',
            'productType' => '01',
            'redirectType'=> '03',
            'rememberMe'  => '1',
            'verifyCKCode'=> $request->get('random'),
        );
        $referer = "http://uac.10010.com/portal/hallLogin";
        $ret = self::getResContent($url, $this->cookieStr, $referer, $query, false);

        $url = "http://iservice.10010.com/e3/static/common/l?_=".self::getMillisecond();
        $referer = "http://iservice.10010.com/e4/";
        $ret = self::getResContent($url, $this->cookieStr, $referer, array(), true);
        
        $url = "http://iservice.10010.com/e3/static/common/mall_info?callback=jsonp1510454682550";
        $ret = self::getResContent($url, $this->cookieStr, null, null, false);
        
        $url = "http://www.10010.com";
        $ret = self::getResContent($url, $this->cookieStr, null, null, false);
        return response()->json(['ret' => $ret]);
    }


    //将用户名和密码录入临时文件，
    public function getImg(Request $request)
    {
        $url = 'http://uac.10010.com/portal/Service/CreateImage';
        $query = array(
            't' => self::getMillisecond(),
        );
//$ret = getResContent($url, $cookieStr, $postData, true);
        $ret = self::getResContent($url, $this->cookieStr, null, $query, false);
        echo "<img class=\"confirm_img\" src=\"data:image/png;base64,".base64_encode($ret['html'])."\">";
     //   return response($res, 200);
         //   ->header('Transfer-Encoding', 'chunked')
         //   ->header('wtlwpp-rest', '176_8711');
    }

    //将用户名和密码录入临时文件，
    public function auth(Request $request)
    {
        $this->validate($request, [
            'random'  => 'required',
        ]);

        $url = 'http://iservice.10010.com/e3/static/query/verificationSubmit?_=1510213729834&accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001';
        $query = array(
            'inputcode'  => $request->get('random'),
            'menuId'     => '000100030001',
        );
        $referer = "http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001";
        $ret = self::getResContent($url, $this->cookieStr, $referer, $query, true);

        return response()->json(['ret' => $ret]);
    }

    public function bill()
    {
        $url = "http://iservice.10010.com/e3/static/query/callDetail?_=1510214405506&accessURL=http://iservice.10010.com/e4/query/bill/call_dan-iframe.html?menuCode=000100030001&menuid=000100030001";
        $query = array( 'pageNo'    => 1,
                        'pageSize'  => 10,
                        'beginDate' => '20170901',
                        'endDate'   => '20170930');
        $ret = self::getResContent($url, $this->cookieStr, null, $query, true);
        echo $ret['html'];
    }


}
