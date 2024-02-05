<?php

namespace App\Http\Controllers\API\Users;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Validation\Rule;
use App\Models\Department;
use App\Models\Position;
use App\Models\Religion;
use App\Models\Bank;
use App\Models\BusinessDetails;
use App\Models\CountryCode;
use App\Models\Parameter;
use App\Models\User;
use Validator;
use Illuminate\Support\Facades\Hash;
use App\Http\Livewire\Users\ManageUsers;
use Illuminate\Support\Facades\Auth;


class StaffSetup extends BaseController
{
    private $staffs;

    public $id_path = '';
    public $photo_path = '';


    public $addStaff = true, $updateStaff = false, $showPassword = false, $showNewPassword = false, $showConfirmPassword = false;


    /**
     * delete action listener
     */
    protected $listeners = [
        'deleteBusinessListner' => 'deleteBusiness'
    ];


    public function toggleShowPassword($field)
    {
        if ($field == 'password') {
            $this->showPassword = !$this->showPassword;
        } elseif ($field == 'new_password') {
            $this->showNewPassword = !$this->showNewPassword;
        } elseif ($field == 'confirm_password') {
            $this->showConfirmPassword = !$this->showConfirmPassword;
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);

        $this->resetPage();
    }


    /**
     * store the user inputted post data in the posts table
     * @return void
     */
    public function storeStaff(Request $request)
    {
        
        if ($request->staffId) {
            $validator = Validator::make($request->all(), [
                'username' => [
                    'required',
                    'min:4',
                    Rule::unique('users')->ignore($request->staffId),
                ],
                'address' => 'required',
                'passport_photo' => 'nullable|image|mimes:jpeg,png,svg,jpg,gif|max:1024',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'username' => 'required|min:4|unique:users,username',
                'firstname' => 'required',
                'lastname' => 'required',
                'email' => 'required|email|unique:users,email',
                'mobile_phone' => 'required|min:11|max:16',
                'department_id' => 'required',
                'position_id' => 'required',
                'password' => 'required|min:6',
                'gender' => 'required',
                'employment_type' => 'required',
                'business_id' => 'required',
                'current_salary' => 'required',
                'entry_salary' => 'required',
                // 'passport_photo' => 'required|image|mimes:jpeg,png,svg,jpg,gif|max:1024',
            ]);
        }

        if ($validator->fails()) {
            return $this->sendError('Error validation', $validator->errors());
        }
        $photo_ext = ($request->passport_photo != '') ? pathinfo($request->passport_photo->getRealPath(), PATHINFO_EXTENSION) : '';
        // $this->photo_path = $request->passport_photo->storeAs('passport', str_replace(' ', '_', $request->username . '.' . $photo_ext));
        $id_ext = ($request->id_card != '') ? pathinfo($request->id_card->getRealPath(), PATHINFO_EXTENSION) : '';
        // $this->id_path = $request->id_card->storeAs('id_card', str_replace(' ', '_', $request->username . '.' . $id_ext));

        try {
            $user = User::create([
                'username' => $request->username,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'mobile_phone' => $request->mobile_phone,
                'photo' => $this->photo_path,
                'staff_id_card' => $this->id_path,
                'department_id' => $request->department_id,
                'position_id' => $request->position_id,
                'password' => Hash::make($request->password),
                'contact_address' => $request->contact_address,
                'gender' => $request->gender,
                'dob' => $request->dob,
                'nationality' => $request->nationality,
                'religion' => $request->religion,
                'marital_status' => $request->marital_status,
                'employment_date' => $request->employment_date,
                'termination_date' => $request->termination_date,
                'employment_type' => $request->employment_type,
                'business_id' => $request->business_id,
                'entry_salary' => $request->entry_salary,
                'current_salary' => $request->current_salary,
                'last_increment' => $request->last_increment,
                'last_increment_date' => $request->last_increment_date,
                'last_promotion' => $request->last_promotion,
                'bank_account_no' => $request->bank_account_no,
                'bank_code' => $request->bank_code,
                'bank_account_name' => $request->bank_account_name,
                'day_1' => 1,
                'day_2' => 1,
                'day_3' => 1,
                'day_4' => 1,
                'day_5' => 1,
                'day_6' => 1,
                'day_7' => 1,
                'is_mfa' => 0,
                'user_disabled' => 0,
                'user_locked' => 0,
                'passchg_logon' => 0,
                'posted_ip' => request()->ip(),
                'posted_user' => Auth::user()->username,
            ]);
            if ($user) {
                $success['token'] =  $user->createToken('apiToken')->plainTextToken;
                $success['staff'] =  $user;
                return $this->sendResponse($success, 'User created successfully.');
            } else {
                return $this->sendError('Error staff setup', ['Unable to setup staff profile.'], 404);;
            }
        } catch (\Exception $ex) {
            return $this->sendError('Error validation', $ex->getMessage(), 404);
        }
    }

    public function profile()
    {
        $data['profile_data'] = Auth::user();
        $data['token'] = Auth::user()->createToken('apiToken')->plainTextToken;
        return $this->sendResponse($data, 'User profile retrieved successfully');
    }

    public function verifyAccountNumber($bank_account_no, $bank_code)
    {
        $manageusers = new ManageUsers();
        if ($bank_account_no != '' && $bank_code != '') {
            $verify = $manageusers->verifyBankAccountNumber($bank_account_no, $bank_code);
            if ($verify['response_code'] == '0') {
                return $verify['response_message'];
            } else {
                return $this->addError('bank_account_name', $verify['response_message']);
            }
        }
    }
}
