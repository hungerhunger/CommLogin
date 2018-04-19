<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Services\CommService as CommService;


class CommController extends Controller
{
    protected $commService;
    function __construct (Request $request){
        $this->validate($request, [
                'mobile'  => 'required',
                ]);
        $mobile = $request->get('mobile');
        $this->commService = CommService::create($mobile);
    }

    public function checkLogin(Request $request){
        $mobile = $request->get('mobile');
        $res = $this->commService->checkLogin($mobile);
        return response()->json($res);
    }

    public function sendRandom(Request $request){
        $mobile = $request->get('mobile');
        $state  = $request->get('state');
        $res = $this->commService->sendRandom($mobile, $state);
        return response()->json($res);
    }

    public function sendRandomAuth(Request $request){
        $mobile = $request->get('mobile');
        $res = $this->commService->sendRandomAuth($mobile);
        return response()->json($res);
    }

    public function getCaptcha(Request $request){  
        $res = $this->commService->getCaptcha();
        return response()->json($res);
    }

    public function getCaptcha1(Request $request){
        $res = $this->commService->getCaptcha1();
        return $res;
    }

    public function login(Request $request){
        $this->validate($request, [
                'mobile'  => 'required',
                'service' => 'required',
                ]);
        $mobile  = $request->get('mobile');
        $service = $request->get('service');
        $random  = $request->get('random');
        $captcha = $request->get('captcha');
        $res = $this->commService->login($mobile, $service, $random, $captcha);
        return response()->json($res);
    }

    public function auth(Request $request){
        $this->validate($request, [
                'mobile'  => 'required',
                ]);

        $mobile  = $request->get('mobile');
        $service = $request->get('service');
        $random  = $request->get('random');
        $captcha = $request->get('captcha');
        $res = $this->commService->auth($mobile, $service, $random, $captcha);
        return response()->json($res);
    }

    public function bill(Request $request){
        $mobile  = $request->get('mobile');
        $res = $this->commService->bill($mobile);
        return response()->json($res);
    }
}

