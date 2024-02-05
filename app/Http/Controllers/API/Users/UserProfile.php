<?php

namespace App\Http\Livewire\Users;

use App\Models\BusinessDetails;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Livewire\Component;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\PasswordReset;
use Livewire\WithFileUploads;

class UserProfile extends Component
{
    use WithFileUploads;

    public $user_details, $position_name, $photo_path, $business_name, $department_name, $last_access, $firstname, $lastname, $email, $mobile_phone, $password, $staff_id_card, $passport_photo, $path, $userID, $showPassword = false, $showNewPassword = false, $showConfirmPassword = false, $new_password, $confirm_password;

    public $activeTab = 'profile';

    protected function rules()
    {
        return [
            'mobile_phone' => [
                'required',
                Rule::unique('users')->ignore(Auth::user()->id),
            ],
            'email' => [
                'required',
                Rule::unique('users')->ignore(Auth::user()->id),
            ],
            'firstname' => 'required',
            'lastname' => 'required',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
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

    public function mount()
    {

        $this->user_details = Auth::user();
        $this->userID = $this->user_details->id;

        $positions = Position::select('position_name')->where(['position_id' => $this->user_details->position_id])->first();
        $this->position_name = $positions->position_name;

        $business = BusinessDetails::select('business_name')->where(['id' => $this->user_details->business_id])->first();
        $this->business_name = $business->business_name;

        $department = Department::select('display_name')->where(['id' => $this->user_details->department_id])->first();
        $this->department_name = $department->display_name;

        $photo = $this->user_details->photo;
        $gender = $this->user_details->gender;
        $avartar = ($gender == 'Male' || $gender == 'male' || $gender == 'MALE') ? 'avartar-m' : 'avartar-f';
        $this->photo_path = ($photo != '') ? '/'.$this->user_details->photo : "/assets/img/" . $avartar . '.png';

        $date = new DateTime($this->user_details->last_used);
        $this->last_access = $this->formatDate($date);
    }

    public function render()
    {
        if (!Auth::check()) {
            redirect()->to('/');
        }

        return view('livewire.users.user-profile');
    }

    private function resetInputFields()
    {
        $this->firstname = '';
        $this->lastname = '';
        $this->email = '';
        $this->mobile_phone = '';
        $this->password = '';
        $this->passport_photo = '';
        $this->staff_id_card = '';
    }

    public function updatePassport()
    {
        $this->validate(['passport_photo' => 'required|image|mimes:jpeg,png,svg,jpg,gif|max:1024']);
        try {
            $photo_ext = pathinfo($this->passport_photo->getRealPath(), PATHINFO_EXTENSION);
            $this->photo_path = $this->passport_photo->storeAs('passport', str_replace(' ', '_', Auth::user()->username . '.' . $photo_ext));
            User::whereId(Auth::user()->id)->update([
                'photo' => $this->photo_path
            ]);

            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'message' => 'Successful!',
                'text' => 'User Passport Photo Changed Successfully!!.'
            ]);
            $this->passport_photo = '';
           
        } catch (\Exception $ex) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!' . $ex
            ]);
        }
    }

    public function showTab($tab)
    {
        $user = User::select('firstname', 'lastname', 'mobile_phone', 'email')->where(['id' => $this->user_details->id])->first();
        $this->firstname = $user->firstname;
        $this->lastname = $user->lastname;
        $this->email = $user->email;
        $this->mobile_phone = $user->mobile_phone;

        $this->activeTab = $tab;
    }

    public function changePassword()
    {
        $user = Auth::user();

        $this->validate([
            'new_password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'password' => [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    if ($user && !Hash::check($value, $user->password)) {
                        $fail(__('Your :attribute input is not consistent with our records.'));
                    }
                }
            ],
            'confirm_password' => 'required|same:new_password'
        ]);

        try {

            PasswordReset::create([
                'username' => $user->username,
                'old_password' => $user->password,
                'new_password' => Hash::make($this->new_password),
                'posted_ip' => request()->ip(),
                'posted_userid' => $user->id
            ]);

            User::whereId($user->id)->update([
                'password' => Hash::make($this->new_password)
            ]);

            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'message' => 'Successful!',
                'text' => 'User Password Changed Successfully!!.'
            ]);

            $this->dispatchBrowserEvent('page:redirect', [
                'url' => url('/') . "/logout",
                'secs' => 5000
            ]);
        } catch (\Exception $ex) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!' . $ex
            ]);
        }
    }


    public function updateProfile()
    {
        $this->validate();
        try {

            User::whereId($this->userID)->update([
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'email' => $this->email,
                'mobile_phone' => $this->mobile_phone
            ]);

            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'message' => 'Successful!',
                'text' => 'Profile Updated Successfully!!.'
            ]);
        } catch (\Exception $ex) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!' . $ex
            ]);
        }
    }


    public function formatDate($datetime)
    {
        $datetime2 = new DateTime('NOW');
        $interval = $datetime2->diff($datetime);
        $suffix = ($interval->invert ? ' ago' : '');
        if ($v = $interval->y >= 1) return $this->pluralize($interval->y, 'year') . $suffix;
        if ($v = $interval->m >= 1) return $this->pluralize($interval->m, 'month') . $suffix;
        if ($v = $interval->d >= 1) return $this->pluralize($interval->d, 'day') . $suffix;
        if ($v = $interval->h >= 1) return $this->pluralize($interval->h, 'hour') . $suffix;
        if ($v = $interval->i >= 1) return $this->pluralize($interval->i, 'minute') . $suffix;
        return $this->pluralize($interval->s, 'second') . $suffix;
    }

    public function pluralize($count, $text)
    {
        return $count . (($count == 1) ? (" $text") : (" ${text}s"));
    }
}
