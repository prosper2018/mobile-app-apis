<?php

namespace App\Http\Livewire\Users;

use Livewire\Component;
use App\Models\Department;
use App\Models\Position;
use App\Models\Religion;
use App\Models\Bank;
use App\Models\BusinessDetails;
use App\Models\CountryCode;
use App\Models\Parameter;
use App\Models\User;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DateTime;
use App\Http\Livewire\Users\UserProfile;

class ManageUsers extends Component
{
    use WithFileUploads;
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    protected $staffs = [];
    protected $user_details = [];

    public $username, $firstname, $lastname, $email, $mobile_phone, $department_id, $position_id, $password, $contact_address, $gender, $dob, $nationality, $religion, $marital_status, $employment_date, $termination_date, $employment_type, $business_id, $entry_salary, $current_salary, $last_increment, $last_increment_date, $last_promotion, $passport_photo, $staff_id_card, $bank_account_no, $bank_code, $bank_account_name, $day_1 = true, $day_2 = true, $day_3 = true, $day_4 = true, $day_5 = true, $day_6 = true, $day_7 = true, $is_mfa = false, $user_disabled = false, $user_locked = false, $passchg_logon = true, $departments, $positions, $religions, $country_codes, $banks, $businesses, $photo_path, $id_path, $staffId, $posted_ip, $posted_user, $bank_verify_url, $bank_verify_secrete_key, $search, $pagelength = 5, $last_access, $sno = 1, $userId;

    public $addStaff = false, $updateStaff = false, $viewStaff = false, $showPassword = false, $showNewPassword = false, $showConfirmPassword = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->posted_ip = request()->ip();
        $this->posted_user = (Auth::check()) ? Auth::user()->username : '::1';
    }
    /**
     * delete action listener
     */
    protected $listeners = [
        'deleteBusinessListner' => 'deleteBusiness'
    ];

    /**
     * List of add/edit form rules
     */
    protected function rules()
    {
        if ($this->staffId) {
            return [
                'username' => [
                    'required',
                    'min:4',
                    Rule::unique('users')->ignore($this->staffId),
                ],
                'address' => 'required',
                'passport_photo' => 'nullable|image|mimes:jpeg,png,svg,jpg,gif|max:1024',
            ];
        } else {
            return [
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
                'passport_photo' => 'required|image|mimes:jpeg,png,svg,jpg,gif|max:1024',
            ];
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);

        $this->resetPage();
    }

    /**
     * Reseting all inputted fields
     * @return void
     */
    private function resetInputFields()
    {
        $this->username = '';
        $this->firstname = '';
        $this->lastname = '';
        $this->email = '';
        $this->mobile_phone = '';
        $this->passport_photo = '';
        $this->staff_id_card = '';
        $this->department_id = '';
        $this->position_id = '';
        $this->password = '';
        $this->contact_address = '';
        $this->gender = '';
        $this->dob = '';
        $this->nationality = '';
        $this->religion = '';
        $this->marital_status = '';
        $this->employment_date = '';
        $this->termination_date = '';
        $this->employment_type = '';
        $this->business_id = '';
        $this->entry_salary = '';
        $this->current_salary = '';
        $this->last_increment = '';
        $this->last_increment_date = '';
        $this->last_promotion = '';
        $this->bank_account_no = '';
        $this->bank_code = '';
        $this->bank_account_name = '';
        $this->day_1 = '';
        $this->day_2 = '';
        $this->day_3 = '';
        $this->day_4 = '';
        $this->day_5 = '';
        $this->day_6 = '';
        $this->day_7 = '';
        $this->is_mfa = '';
        $this->user_disabled = '';
        $this->user_locked = '';
        $this->passchg_logon = '';
        $this->posted_ip = '';
        $this->posted_user = '';
    }


    public function render()
    {

        $this->religions = Religion::select('id', 'display_name')->where('is_deleted', '0')->get();
        $this->banks = Bank::select('bank_code', 'bank_name')->get();
        $this->country_codes = CountryCode::select('id', 'country_name')->get();
        $this->businesses = BusinessDetails::select('id', 'business_name')->where('is_deleted', '0')->get();
        $this->departments = Department::select('id', 'display_name')->where('is_deleted', '0')->get();
        $this->positions = Position::select('position_id', 'position_name')->where(['is_deleted' => '0', 'department_id' => $this->department_id])->get();

        if ($this->search != "") {
            $this->staffs = User::where('username', 'LIKE', '%' . $this->search . '%')->orWhere('contact_address', 'LIKE', '%' . $this->search . '%')->paginate($this->pagelength);
        } else {
            // $this->staffs = User::paginate($this->pagelength);
            $this->staffs = DB::table('users')
                ->leftJoin('positions', 'users.position_id', '=', 'positions.position_id')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
                ->leftJoin('business_details', 'users.business_id', '=', 'business_details.id')
                ->leftJoin('country_codes', 'users.nationality', '=', 'country_codes.id')
                ->leftJoin('religions', 'users.religion', '=', 'religions.id')
                ->select(['positions.position_name as position', 'departments.display_name as department', 'business_details.business_name', 'religions.display_name', 'religions.display_name as religion', 'users.*'])->paginate($this->pagelength);
        }
        return view('livewire.users.manage-users', ['staffs' => $this->staffs, 'user_details' => $this->user_details]);
    }

    public function verifyAccountNumber()
    {

        if ($this->bank_account_no != '' && $this->bank_code != '') {
            $verify = $this->verifyBankAccountNumber($this->bank_account_no, $this->bank_code);
            if ($verify['response_code'] == '0') {
                $this->bank_account_name = $verify['response_message'];
            } else {
                $this->addError('bank_account_name', $verify['response_message']);
            }
        }
    }

    public function create()
    {

        $this->addStaff = true;
        $this->updateStaff = false;
        $this->viewStaff = false;
        $this->resetInputFields();
    }

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

    /**
     * store the user inputted post data in the posts table
     * @return void
     */
    public function storeStaff()
    {
        $this->validate();
        $photo_ext = pathinfo($this->passport_photo->getRealPath(), PATHINFO_EXTENSION);
        $this->photo_path = $this->passport_photo->storeAs('passport', str_replace(' ', '_', $this->username . '.' . $photo_ext));
        $id_ext = pathinfo($this->passport_photo->getRealPath(), PATHINFO_EXTENSION);
        $this->id_path = $this->passport_photo->storeAs('id_card', str_replace(' ', '_', $this->username . '.' . $id_ext));

        try {
            User::create([
                'username' => $this->username,
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'email' => $this->email,
                'mobile_phone' => $this->mobile_phone,
                'photo' => $this->photo_path,
                'staff_id_card' => $this->id_path,
                'department_id' => $this->department_id,
                'position_id' => $this->position_id,
                'password' => Hash::make($this->password),
                'contact_address' => $this->contact_address,
                'gender' => $this->gender,
                'dob' => $this->dob,
                'nationality' => $this->nationality,
                'religion' => $this->religion,
                'marital_status' => $this->marital_status,
                'employment_date' => $this->employment_date,
                'termination_date' => $this->termination_date,
                'employment_type' => $this->employment_type,
                'business_id' => $this->business_id,
                'entry_salary' => $this->entry_salary,
                'current_salary' => $this->current_salary,
                'last_increment' => $this->last_increment,
                'last_increment_date' => $this->last_increment_date,
                'last_promotion' => $this->last_promotion,
                'bank_account_no' => $this->bank_account_no,
                'bank_code' => $this->bank_code,
                'bank_account_name' => $this->bank_account_name,
                'day_1' => $this->day_1,
                'day_2' => $this->day_2,
                'day_3' => $this->day_3,
                'day_4' => $this->day_4,
                'day_5' => $this->day_5,
                'day_6' => $this->day_6,
                'day_7' => $this->day_7,
                'is_mfa' => $this->is_mfa,
                'user_disabled' => $this->user_disabled,
                'user_locked' => $this->user_locked,
                'passchg_logon' => $this->passchg_logon,
                'posted_ip' => request()->ip(),
                'posted_user' => 'admin@mail.com',
            ]);

            $this->resetInputFields();
            $this->addStaff = false;
            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'message' => 'Successful!',
                'text' => 'User Created Successfully!!'
            ]);
        } catch (\Exception $ex) {
            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!'.$ex->getMessage()
            ]);
        }
    }

    /**
     * show existing post data in edit post form
     * @param mixed $id
     * @return void
     */
    public function edit($id)
    {
        try {
            $user = User::findOrFail($id);
            
            if (!$user) {

                return $this->dispatchBrowserEvent('swal:modal', [
                    'type' => 'warning',
                    'message' => 'Warning!',
                    'text' => 'User not found. Please try again.'
                ]);
                
            } else {

                $this->userId = $user->id;
                $this->username = $user->username;
                $this->firstname = $user->firstname;
                $this->lastname = $user->lastname;
                $this->email = $user->email;
                $this->mobile_phone = $user->mobile_phone;
                $this->passport_photo = $user->passport_photo;
                $this->staff_id_card = $user->staff_id_card;
                $this->department_id = $user->department_id;
                $this->position_id = $user->position_id;
                // $this->password = $user->password;
                $this->contact_address = $user->contact_address;
                $this->gender = $user->gender;
                $this->dob = $user->dob;
                $this->nationality = $user->nationality;
                $this->religion = $user->religion;
                $this->marital_status = $user->marital_status;
                $this->employment_date = $user->employment_date;
                $this->termination_date = $user->termination_date;
                $this->employment_type = $user->employment_type;
                $this->business_id = $user->business_id;
                $this->entry_salary = $user->entry_salary;
                $this->current_salary = $user->current_salary;
                $this->last_increment = $user->last_increment;
                $this->last_increment_date = $user->last_increment_date;
                $this->last_promotion = $user->last_promotion;
                $this->bank_account_no = $user->bank_account_no;
                $this->bank_code = $user->bank_code;
                $this->bank_account_name = $user->bank_account_name;
                $this->day_1 = $user->day_1;
                $this->day_2 = $user->day_2;
                $this->day_3 = $user->day_3;
                $this->day_4 = $user->day_4;
                $this->day_5 = $user->day_5;
                $this->day_6 = $user->day_6;
                $this->day_7 = $user->day_7;
                $this->is_mfa = $user->is_mfa;
                $this->user_disabled = $user->user_disabled;
                $this->user_locked = $user->user_locked;
                $this->passchg_logon = $user->passchg_logon;
                $this->posted_ip = $user->posted_ip;
                $this->posted_user = $user->posted_user;

                $this->addStaff = false;
                $this->updateStaff = true;
                $this->viewStaff = false;
            }
        } catch (\Exception $ex) {

            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!'.$ex->getMessage()
            ]);
            
        }
    }

    /**
     * update the post data
     * @return void
     */
    public function update()
    {
        try {

            $this->validate();
            $user = User::findOrFail($this->userId);
            $photo_ext = (!is_string($this->passport_photo)) ? pathinfo($this->passport_photo->getRealPath(), PATHINFO_EXTENSION) : "";
            $this->photo_path = ($photo_ext != '') ? $this->passport_photo->storeAs('passport', str_replace(' ', '_', $this->username . '.' . $photo_ext)) : $user->passport_photo;
            $id_ext = (!is_string($this->staff_id_card)) ? pathinfo($this->staff_id_card->getRealPath(), PATHINFO_EXTENSION) : "";
            $this->id_path = ($id_ext != '') ? $this->staff_id_card->storeAs('id_card', str_replace(' ', '_', $this->username . '.' . $id_ext)) : $user->staff_id_card;
            User::whereId($this->businessId)->update([
                'username' => $this->username,
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'email' => $this->email,
                'mobile_phone' => $this->mobile_phone,
                'passport_photo' => $this->photo_path,
                'staff_id_card' => $this->id_path,
                'department_id' => $this->department_id,
                'position_id' => $this->position_id,
                'password' => $this->password,
                'contact_address' => $this->contact_address,
                'gender' => $this->gender,
                'dob' => $this->dob,
                'nationality' => $this->nationality,
                'religion' => $this->religion,
                'marital_status' => $this->marital_status,
                'employment_date' => $this->employment_date,
                'termination_date' => $this->termination_date,
                'employment_type' => $this->employment_type,
                'business_id' => $this->business_id,
                'entry_salary' => $this->entry_salary,
                'current_salary' => $this->current_salary,
                'last_increment' => $this->last_increment,
                'last_increment_date' => $this->last_increment_date,
                'last_promotion' => $this->last_promotion,
                'bank_account_no' => $this->bank_account_no,
                'bank_code' => $this->bank_code,
                'bank_account_name' => $this->bank_account_name,
                'day_1' => $this->day_1,
                'day_2' => $this->day_2,
                'day_3' => $this->day_3,
                'day_4' => $this->day_4,
                'day_5' => $this->day_5,
                'day_6' => $this->day_6,
                'day_7' => $this->day_7,
                'is_mfa' => $this->is_mfa,
                'user_disabled' => $this->user_disabled,
                'user_locked' => $this->user_locked,
                'passchg_logon' => $this->passchg_logon,
                'posted_ip' => request()->ip(),
                'posted_user' => 'admin@mail.com',
            ]);
           
            $this->resetFields();
            $this->updateStaff = false;

            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'message' => 'Successful!',
                'text' => 'User Updated Successfully!!'
            ]);

        } catch (\Exception $ex) {
            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!'.$ex->getMessage()
            ]);
        }
    }


    /**
     * delete specific post data from the posts table
     * @param mixed $id
     * @return void
     */
    public function deleteUser($id)
    {
        try {
            User::find($id)->delete();

            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'message' => 'Successful!',
                'text' => 'User Deleted Successfully!!'
            ]);
            
        } catch (\Exception $e) {

           return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!'.$e->getMessage()
            ]);
        }
    }

    public function verifyBankAccountNumber($account_number, $bank_code)
    {
        $url_query = Parameter::select('parameter_value')->where(['parameter_name' => 'pay_stack_account_verify'])->first();
        $this->bank_verify_url = $url_query->parameter_value;

        $secre_query = Parameter::select('parameter_value')->where(['parameter_name' => 'paystack_secret_code'])->first();
        $this->bank_verify_secrete_key = $secre_query->parameter_value;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->bank_verify_url . "?account_number=" . $account_number . "&bank_code=" . $bank_code,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $this->bank_verify_secrete_key,
                "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return array('response_code' => 20, 'response_message' => "cURL Error #:" . $err);
        } else {

            $data = json_decode($response, true);
            $data['message'] = (isset($data['message']) && !empty($data['message'])) ? $data['message'] : "Something went wrong!!! Please try again.";

            $message = (isset($data['data']['account_name']) && !empty($data['data']['account_name'])) ? $data['data']['account_name'] : $data['message'];
            $code = (isset($data['data']['account_name']) && !empty($data['data']['account_name'])) ? 0 : 20;
            return array('response_code' => $code, 'response_message' => $message);
        }
    }

    public function viewStaff($id)
    {
        $this->addStaff = false;
        $this->updateStaff = false;
        $this->viewStaff = true;

        $this->user_details = DB::table('users')
            ->leftJoin('positions', 'users.position_id', '=', 'positions.position_id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->leftJoin('business_details', 'users.business_id', '=', 'business_details.id')
            ->leftJoin('country_codes', 'users.nationality', '=', 'country_codes.id')
            ->leftJoin('religions', 'users.religion', '=', 'religions.id')
            ->select(['positions.position_name as position', 'departments.display_name as department', 'business_details.business_name', 'religions.display_name', 'religions.display_name as religion', 'users.*'])->where(['users.id' => $id])->first();

        $photo = $this->user_details->photo;
        $gender = $this->user_details->gender;
        $avartar = ($gender == 'Male' || $gender == 'male' || $gender == 'MALE') ? 'avartar-m' : 'avartar-f';
        $this->photo_path = ($photo != '') ? '/' . $this->user_details->photo : "/assets/img/" . $avartar . '.png';

        $profile = new UserProfile();
        $date = new DateTime($this->user_details->last_used);
        $this->last_access = $profile->formatDate($date);
    }
}
