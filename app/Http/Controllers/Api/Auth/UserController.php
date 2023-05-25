<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\RegisterMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Mail;


class UserController extends Controller
{
    //

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required',
            'email' => 'required|unique:users',
            'mobile' => 'required',
            'short_bio' => 'required',
            'age' => 'required',
            'password' => 'required'
        ], [
            'full_name.required' => 'The full name field is required.',
            'short_bio.required' => 'The short bio field is required.',
        ]);

        if ($validator->fails()) {
            $err = [];
            foreach ($validator->errors()->getMessages() as $key => $error) {
                $err[] = ['code' => $key, 'message' => $error[0]];
            }
            return response()->json(['errors' => $err], 403);
        }
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'age' => $request->age,
            'short_bio' => $request->short_bio,
            'is_active' => 1,
            'password' => bcrypt($request->password),
        ]);

        if ($user){
            $data = [
                "name" => $user->full_name,
            ];
        
            // Mail::send('email.register', $data, function($message,$user) {
            //     $message->to($user->email, $user->full_name)->subject
            //        ('Registration Successful');
            //     $message->from('admin@jojoelectricals.com','Jojo Electricals');
            //  });
            Mail::to($request->email)->send(new RegisterMail($data));
        }

        // $token = $user->createToken('auth_token')->accessToken;
        return response()->json(['status'=>200,'message' => 'registered successfully'], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            $err = [];
            foreach ($validator->errors()->getMessages() as $key => $error) {
                $err[] = ['code' => $key, 'message' => $error[0]];
            }
            return response()->json(['errors' => $err], 403);
        }

        $email = $request['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $errors = [];
                array_push($errors, ['code' => 'email', 'message' => 'Invalid email address']);
                return response()->json([
                    'errors' => $errors
                ], 403);
        }

        $data = [
            'email' => $email,
            'password' => $request->password
        ];

        $user = User::where('email',$email)->first();

        if (isset($user) && auth()->attempt($data)) {
            // $token = $user->createToken('signinToken')->accessToken;
            $response = Http::asForm()->post(env('APP_URL','https://api.jojoelectricals.com').'/oauth/token', [
                'grant_type' => 'password',
                'client_id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
                'client_secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
                'username' => $request->email,
                'password' => $request->password,
                'scope' => '',
            ]);

            return response()->json(['status'=>200,$response->json()], 200);

        } else {
            $errors = [];
            array_push($errors, ['code' => 'login', 'message' => 'incorrect email or password!']);
            return response()->json([
                'errors' => $errors
            ], 401);
        }
    }
    public function logout(Request $request){

        $request->user()->token()->revoke();
        return response([
            'message' => 'logged out successfully'
        ], 200);
    }

    public function getUserInfo(Request $request){
        // $this->refreshToken($request);
        if (auth()->user()){
            $user = auth()->user();
            return response([
                'data' => $user,
            ], 200);
        }
        return response([
            'message' => 'unauthorized'
        ], 401);
    }

    public function getAllUsers(Request $request){
        // $this->refreshToken($request);
        if (auth()->user()){
            $users = User::all();
            if($users){
                return response([
                    'data' => $users,
                ], 200);
            }
            
            return response([
                'message' => 'something went wrong'
            ], 500);
        }
        return response([
            'message' => 'unauthorized'
        ], 401);
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        $url = 
        $response = Http::asForm()->post(env('APP_URL','https://api.jojoelectricals.com').'/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
            'client_secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
            'scope' => '',
        ]);
         
        return $response->json();
    } 
}
