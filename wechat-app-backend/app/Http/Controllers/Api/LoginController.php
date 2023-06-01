<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar'=>'required',
            'name'=>'required',
            'type'=>'required',
            'open_id'=>'required',
            'email'=>'nullable|max:50',
            'phone'=>'max:30',
        ]);

        if ($validator->fails()) {
            return ['code'=>-1, 'data'=>'no valid data', 'msg'=>$validator->errors()->first()];
        }
        try{
            $validated = $validator->validated();
        $map = [];
        $map['type'] = $validated['type'];
        $map['open_id'] = $validated['open_id'];
        $result = DB::table('users')
            ->select('avatar', 'name', 'description', 'type', 'token', 'access_token', 'online')
            ->where($map)
            ->first();

        if(empty($result)){
            $validated['token'] = md5(uniqid().rand(10000, 99999));
            $validated['created_at'] = Carbon::now();
            $validated['access_token'] = md5(uniqid().rand(1000000,9999999));
            $validated['expire_date'] = Carbon::now()->addDays(30);
            $user_id = DB::table('users')->insertGetId($validated);
            $user_result = DB::table('users')->select('avatar', 'name', 'description', 'type', 'token', 'access_token', 'online')
            ->where('id', '=', $user_id)->first();
            
            return ['code'=>0, 'data'=>$user_result, 'msg'=>'User has been created'];
        } else {
            $access_token = md5(uniqid().rand(10000, 99999));
            $expire_date = Carbon::now()->addDays(30);
            DB::table('users')->where($map)->update(
                [
                'access_token' => $access_token,
                'expire_date' => $expire_date
                ]
            );
            $result->access_token = $access_token;
            return ['code'=>0, 'data'=>$result, 'msg'=>'User informantion updated'];
        }
        }
        catch(Exception $e){
            return ['code'=>-1, 'data'=>'no data available', 'msg'=>(String)$e];
        }
}

    public function contact(Request $request) {
        $token = $request->user_token;
        $res = DB::table('users')->select(
            "avatar",
            "description",
            "online",
            "token",
            "name"
        )->where("token", "!=", $token)->get();

        return ['code'=>0, 'data'=>$res, 'msg'=>'got all the user info'];
    }

    public function send_notice(Request $request) {
        //caller information
        $user_token = $request->user_token;
        $user_avatar = $request->user_avatar;
        $user_name = $request->user_name;

        //callee information
        $to_token = $request->input('to_token');
        return ['code'=>0, 'data'=>$to_token, 'msg'=>'success'];
    }

}
