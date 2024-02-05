<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;

class PwdChngOnLogon extends Component
{
    public $password, $new_password, $confirm_password, $username, $link, $showPassword = false, $showNewPassword = false, $showConfirmPassword = false;

    public function mount()
    {
        if (!Auth::check()) {
            return Redirect::to('/');
        }

        if (Auth::user()->passchg_logon != '1') {
            return Redirect::to('/');
        }
    }

    protected function rules()
    {
        $user = Auth::user();

        return [
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
            'confirm_password' => 'required|same:new_password',
        ];
    }


    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function changePassword()
    {
        $this->link = url('/') . "/pwd-chng-on-logon";
        $this->validate();

        try {

            User::whereId(Auth::user()->id)->update([
                'passchg_logon' => 0,
                'password' => Hash::make($this->new_password)
            ]);

            Auth::logout();

            return $this->dispatchBrowserEvent('swal:redirect', [
                'type' => 'info',
                'message' => 'Notice!',
                'text' => 'Password Changed Successfully!!. Please <b><a href ="'.$this->link.'">click here</a></b> to login to your account.'
            ]);

        } catch (\Exception $ex) {
            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!' . $ex->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.auth.pwd-chng-on-logon')->layout('layouts.base');
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
}
