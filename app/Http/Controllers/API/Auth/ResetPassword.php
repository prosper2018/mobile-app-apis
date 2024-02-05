<?php

namespace App\Http\Livewire\Auth;

use App\Models\PasswordReset;
use Livewire\Component;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ResetPassword extends Component
{
    public $data, $uipa, $new_password, $confirm_password, $email, $password, $userId, $reset_pwd_link, $username, $userIP, $link, $showPassword = false, $showNewPassword = false, $showConfirmPassword = false;

    public function mount()
    {
        $queryString = urldecode(request()->getQueryString());
        $urlParams = collect(explode('&', $queryString))
            ->mapWithKeys(function ($param) {
                $parts = explode('=', $param);
                return [$parts[0] => $parts[1]];
            });

        $this->data = $urlParams->get('data');
        $this->uipa = base64_decode($urlParams->get('uipa'));
        $this->username = $this->uipa;

        $this->userIP = request()->ip();
    }

    protected function rules()
    {
        return [
            'new_password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'confirm_password' => 'required|same:new_password',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->layout('layouts.base');
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

    public function resetPassword()
    {
        $this->link = url('/') . "/forgot-password";
        $this->validate();
        try {

            if ($this->data == null || $this->uipa == null) {
                return $this->dispatchBrowserEvent('swal:modal', [
                    'type' => 'warning',
                    'message' => 'Warning!',
                    'text' => 'The password reset link is broken! Please try requesting a new link.'
                ]);
            }

            $user_details = User::select('id', 'email', 'reset_pwd_link', 'password')->where(['username' => $this->username])->first();
            if (!$user_details->exists()) {
                return $this->dispatchBrowserEvent('swal:modal', [
                    'type' => 'warning',
                    'message' => 'Warning!',
                    'text' => 'Unable to retrieve user credentials. Please try again.'
                ]);
            }


            $this->email = $user_details->email;
            $this->userId = $user_details->id;
            $this->reset_pwd_link = $user_details->reset_pwd_link;
            $this->password = $user_details->password;

            if ($this->reset_pwd_link === null || $this->reset_pwd_link == '') {
                return $this->dispatchBrowserEvent('swal:redirect', [
                    'type' => 'warning',
                    'message' => 'Warning!',
                    'text' => 'This password reset link has already been used or has expired. Please <b><a href ="' . $this->link . '">click here</a></b> to request for a new link and try again.'
                ]);
            }

            if ($this->reset_pwd_link != $this->data) {
                return $this->dispatchBrowserEvent('swal:modal', [
                    'type' => 'warning',
                    'message' => 'Warning!',
                    'text' => 'Unable to retrieve user credentials. Please try again.'
                ]);
            }

            PasswordReset::create([
                'username' => $this->username,
                'old_password' => $this->password,
                'new_password' => Hash::make($this->new_password),
                'posted_ip' => $this->userIP,
                'posted_userid' => $this->userId
            ]);

            User::whereId($this->userId)->update([
                'reset_pwd_link' => '',
                'password' => Hash::make($this->new_password)
            ]);

            return $this->dispatchBrowserEvent('swal:redirect', [
                'type' => 'info',
                'message' => 'Notice!',
                'text' => 'Your password reset is successful.... Please <b><a href ="'. url('/').'/' .'">click here</a></b> to login to your account.'
            ]);

        } catch (\Exception $ex) {
            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!' . $ex->getMessage()
            ]);
        }
    }
}
