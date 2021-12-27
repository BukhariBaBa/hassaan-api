<?php

namespace App\Http\Controllers\api;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Mail\VerificationPin;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $rules = [
            'name'=>'required|string',
            'email'=>'required|unique:users',
            'password'=>'required|string',
        ];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response([ 'status' =>'failed','message' => $validator->messages() ], 200);
        }else{
            $params = $request->all();
            $imageName = NULL;
            if (!empty($request->photo)) {
                $imageName = time().'.'.$request->photo->extension();
                $request->photo->move(public_path('uploads'), $imageName);
            }
            try {
                $userModel = new User();
                $userModel->name = $params['name'];
                $userModel->email = $params['email'];
                $userModel->password = Hash::make($params['password']);
                $userModel->auth_token = Str::random(32);
                $userModel->photo = $imageName;
                $userModel->save();
                return response([ 'status' =>'success','message' => 'You are successfully registered!' ], 200);
            } catch (\Throwable $th) {
                return response([ 'status' =>'failed','message' => 'ERROR:'.$th->getMessage() ], 200);
            }
        }
    }

    public function login(Request $request)
    {
        $params = $request->all();

        if(Auth::attempt(['email'=>$params['email'],'password'=>$params['password']]))
        {
            $user['user_id']    = Auth::user()->id;
            $user['name']       = Auth::user()->name;
            $user['email']      = Auth::user()->email;
            return response([ 'status' =>'success','data'=>$user ], 200);
        }
        return response([ 'status' =>'failed','message'=>'Invalid login details' ], Response::HTTP_UNAUTHORIZED);
    }

    public function update_password(Request $request)
    {
        try {
            $params = $request->all();
            $userModel = User::find($params['user_id']);
            if(empty($userModel)){
                return response([ 'status' =>'failed','message'=>'User not found!' ], Response::HTTP_UNAUTHORIZED);
            }else{
                if(Auth::attempt(['email'=>$userModel->email,'password'=>$params['old_password']])){
                    $userModel->password = Hash::make($params['new_password']);
                    $userModel->save();
                    return response([ 'status' =>'success','message' => 'Password updated successfully ' ], 200);
                }else{
                    return response([ 'status' =>'failed','message'=>'Password did not match' ], Response::HTTP_UNAUTHORIZED);
                }
            }
        } catch (\Throwable $th) {
            return response([ 'status' =>'failed','message' => 'ERROR:'.$th->getMessage() ], 200);
        }
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::get();
        return response([ 'status' =>'success','data'=>$subscriptions ], 200);
    }

    public function update_profile(Request $request)
    {
        $params = $request->all();
        $imageName = NULL;
        if (!empty($request->photo)) {
            $imageName = time().'.'.$request->photo->extension();
            $request->photo->move(public_path('uploads'), $imageName);
        }
        $userModel = User::find($params['user_id']);
        if (!empty($userModel)) {
            $userModel->name = $params['name'];
            $userModel->email = $params['email'];
            if(!empty($imageName))
                $userModel->photo = $imageName;

            $userModel->save();
            return response([ 'status' =>'success','message' => 'Profile Updated!' ], 200);
        }else{
            return response([ 'status' =>'failed','message' => 'User Profile Not Found' ], Response::HTTP_UNAUTHORIZED);
        }
    }
    public function update_fcm(Request $request)
    {
        $params = $request->all();
        $userModel = User::find($params['user_id']);
        if (!empty($userModel)) {
            $userModel->fcm_token = $params['fcm'];
            $userModel->save();
            return response([ 'status' =>'success','message' => 'User FCM Updated!' ], 200);
        }else{
            return response([ 'status' =>'failed','message' => 'User Not Found' ], Response::HTTP_UNAUTHORIZED);
        }
    }
    public function get_profile(Request $request)
    {
        $params = $request->all();
        $userModel = User::find($params['user_id']);
        if (!empty($userModel)) {
            $data['name'] = $userModel->name;
            $data['email'] = $userModel->email;
            $data['photo'] = URL::to('/uploads/'.$userModel->photo);
            return response([ 'status' =>'success','message' => 'User FCM Updated!','data'=>$userModel ], 200);
        }else{
            return response([ 'status' =>'failed','message' => 'User Not Found' ], Response::HTTP_UNAUTHORIZED);
        }
    }
    public function get_fcm(Request $request)
    {
        $params = $request->all();
        $userModel = User::find($params['user_id']);
        if (!empty($userModel)) {
            return response([ 'status' =>'success','message' => 'User FCM Updated!','fcm'=>$userModel->fcm_token ], 200);
        }else{
            return response([ 'status' =>'failed','message' => 'User Not Found' ], Response::HTTP_UNAUTHORIZED);
        }
    }
    public function send_verification_pin(Request $request)
    {
        $params = $request->all();
        $userModel = User::where(['email'=>$params['email']])->first();
        if(!empty($userModel)){
            $email = $userModel->email;
            // sent email to user
            $verificationPIN = random_int(100000, 999999);
            $messageBody = array('pin'=>$verificationPIN,'message'=>'Your verification PIN: ');
            Mail::to($email)->send(new VerificationPin($messageBody));
            if (Mail::failures()) {
                return response([ 'status' =>'failed','message'=>'Invalid Email please check your email Address' ], 200);
            }else{
                return response([ 'status' =>'success','message'=>'Verification PIN sent.','pin'=>$verificationPIN ], 200);
            }
        }else{
            return response([ 'status' =>'failed','message' => 'This Email is not Registered' ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function set_new_password(Request $request)
    {
        $params = $request->all();
        $userModel = User::where(['email'=>$params['email']])->first();
        if(!empty($userModel)){
            $userModel->password = Hash::make($params['new_password']);
            $userModel->save();
            return response([ 'status' =>'success','message'=>'Password reset successfully!' ], 200);
        }else{
            return response([ 'status' =>'failed','message' => 'This Email is not Registered' ], Response::HTTP_UNAUTHORIZED);
        }
    }




}
