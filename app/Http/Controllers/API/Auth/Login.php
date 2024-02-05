<?php

namespace App\Http\Controllers\API\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Parameter;
use App\Models\Position;

class Login extends BaseController
{
    public $username;
    public $password;
    public $remember;
    public $message;
    public $link;
    public $showPassword = false, $showNewPassword = false, $showConfirmPassword = false;



    protected function rules()
    {
        return [
            'username' => 'required',
            'password' => 'required|min:6',
        ];
    }

    function validateUser($request, $remember_me = false)
    {
        $label = "";
        $user_info = array();

        $no_of_pin_misses_arr =  Parameter::select('parameter_value')->where(['parameter_name' => 'no_of_pin_misses'])->first();
        $no_of_pin_misses = $no_of_pin_misses_arr->parameter_value;

        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {

            $user_info = Auth::user();

            @$ddate = date('w');
            if (!Auth::check()) {
                //username not found
                $label = 13;
                return $label;
            }

            $pin_missed = $user_info->pin_missed;
            $override_wh =  $user_info->override_wh;
            $extend_wh =  $user_info->extend_wh;


            @$dhrmin = date('Hi');
            $worktime_arr =  Parameter::select('parameter_value')->where(['parameter_name' => 'working_hours'])->first();
            $worktime = $worktime_arr->parameter_value;

            if ($override_wh == '1') {
                $worktime = $extend_wh;
            }
            $worktimesplit = explode("-", $worktime);
            $lowertime = str_replace(":", "", $worktimesplit[0]);
            $uppertime = str_replace(":", "", $worktimesplit[1]);

            $lowerstatus = ($lowertime < $dhrmin) == '' ? "0" : "1";
            $upperstatus = ($dhrmin < $uppertime) == '' ? "0" : "1";

            $pass_dateexpire = $user_info->pass_dateexpire;
            @$expiration_date = strtotime($pass_dateexpire);
            @$today = date('Y-m-d');
            @$today_date = strtotime($today);
            $roleEnabled_arr = Position::select('position_enabled', 'requires_login', 'is_deleted')->where(['position_id' => $user_info->position_id])->first();
            $roleEnabled = $roleEnabled_arr->position_enabled;
            $roleDeleted = $roleEnabled_arr->is_deleted;
            $requiresLogin = $roleEnabled_arr->requires_login;


            if ($user_info->user_disabled == '1') {
                // User Accout is Disabled
                $label = "2";
            } else if ($user_info->user_locked == '1') {
                //User Account is Locked
                $label = "3";
            } else if ($user_info->day_1 == '0' && $ddate == '0') {
                //You are not allowed to login on Sunday
                $label = "4";
            } else if ($user_info->day_2 == '0' && $ddate == '1') {
                //You are not allowed to login on Monday
                $label = "5";
            } else if ($user_info->day_3 == '0' && $ddate == '2') {
                //You are not allowed to login on Tuesday
                $label = "6";
            } else if ($user_info->day_4 == '0' && $ddate == '3') {
                //You are not allowed to login on Wednesday
                $label = "7";
            } else if ($user_info->day_5 == '0' && $ddate == '4') {
                //You are not allowed to login on Thursday
                $label = "8";
            } else if ($user_info->day_6 == '0' && $ddate == '5') {
                //You are not allowed to login on Friday
                $label = "9";
            } else if ($user_info->day_7 == '0' && $ddate == '6') {
                //You are not allowed to login on Saturday
                $label = "10";
            } else if (!(($lowerstatus == '1') && ($upperstatus == '1'))) {
                //You are not allowed to login due to working hours violation
                $label = "11";
            } else if ($requiresLogin != '1') {
                //Profile Not Allowed to Login
                $label = "14";
            } elseif ($roleEnabled != '1') {
                //Profile Disabled
                $label = "15";
            } elseif ($roleDeleted == '1') {
                //Profile Disabled
                $label = "16";
            } elseif ($user_info->passchg_logon == '1') {
                //Change Password on Logon
                $label = "17";
            } else {
                //Login Successful
                $label = 1;

                $this->updateLastAccess($request->username);

                $this->resetpinmissed($request->username);
            }
        } else {

            $pin_missed_arr = User::select('pin_missed')->where(['username' => $request->username])->first();

            if ($no_of_pin_misses > 0 && $no_of_pin_misses <= $pin_missed_arr->pin_missed) {
                $label = "12";
                $this->updateuserlock($request->username, '1');
            } else {
                $label = "0";
                $this->updatepinmissed($request->username);
            }
        }
        return array('label' => $label, 'data' => $user_info);
    }


    public function signIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //LOGIN THE ACTIVITY

        $user = $this->validateUser($request);
        $label = $user['label'];
        $data['user_data'] = $user['data'];
        
        switch ($label) {
            case '0':
                // Login Failed
                $code = '404';
                $message = trans('auth.failed');
                break;
            case '1':
                $retval = $this->doPostLoginActions();
                $code = $retval['response_code'];
                $message = $retval['response_message'];
                $data['token'] = Auth::user()->createToken('apiToken')->plainTextToken;
                break;
            case '2':
                $code = '403';
                $message = 'Account Profile is currently disabled.';
                break;
            case '3':
                $code = '403';
                $message = 'Account Profile is currently locked.';
                break;
            case '4':
                $code = '403';
                $message = 'You are not allowed to login on Sunday.';
                break;
            case '5':
                $code = '403';
                $message = 'You are not allowed to login on Monday.';
                break;
            case '6':
                $code = '403';
                $message = 'You are not allowed to login on Tuesday.';
                break;
            case '7':
                $code = '403';
                $message = 'You are not allowed to login on Wednesday.';
                break;
            case '8':
                $code = '403';
                $message = 'You are not allowed to login on Thursday.';
                break;
            case '9':
                $code = '403';
                $message = 'You are not allowed to login on Friday.';
                break;
            case '10':
                $code = '403';
                $message = 'You are not allowed to login on Saturday.';
                break;
            case '11':
                $code = '403';
                $message = 'You are not allowed to login due to working hours violation.';
                break;
            case '12':
                $code = '429';
                $message = 'Too many wrong attempt. Your account has been locked.';
                break;
            case '13':
                $code = '403';
                $message = 'Invalid Username/Password.';
                break;
            case '14':
                $code = '403';
                $message = 'Your Account Profile does not have Access to this Portal.';
                break;
            case '15':
                $code = '403';
                $message = 'Your Account Profile has been Disabled. Please contact the System Administrator.';
                break;
            case '16':
                $code = '403';
                $message = 'Your Account Profile has been Disabled. Please contact the System Administrator.';
                break;
            case '17':
                $code = '401';
                $message = 'Change Password on Logon.';
                break;
            default:
                $code = '403';
                $message = 'Unable to proceed at the moment. Kindly try again later.';
                break;
        }

        if ($code == '200') {
            return $this->sendResponse($data, $message);
        } else {
            return $this->sendError($message, [], $code);
        }
    }

    private function updateLastAccess($username)
    {
        User::where(['username' => $username])->update([
            'last_used' => NOW()
        ]);
    }

    private function resetpinmissed($username)
    {
        User::where(['username' => $username])->update([
            'pin_missed' => 0
        ]);
    }

    private function updateuserlock($username, $flag)
    {
        User::where(['username' => $username])->update([
            'user_disabled' => $flag
        ]);
    }

    private function updatepinmissed($username)
    {
        User::where('username', $username)->increment('pin_missed');
    }

    private function doPostLoginActions()
    {

        //send login email

        //generate jwt token

        //generate jwt token

        return array('response_code' => '200', 'response_message' => 'Login successful');
    }
}
