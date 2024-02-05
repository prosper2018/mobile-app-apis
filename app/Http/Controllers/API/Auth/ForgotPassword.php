<?php

namespace App\Http\Livewire\Auth;

use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyMail;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class ForgotPassword extends Component
{
    public $email, $username, $userId, $token, $link, $url, $name, $showPassword = false, $showNewPassword = false, $showConfirmPassword = false;

    public function rules()
    {
        return ['username' => 'required'];
    }

    public function sendEmail()
    {
        $user_details = User::select('id', 'email',  DB::raw("concat(firstname, ' ', lastname) as name"))->where(['username' => $this->username])->first();
        if ($user_details == null) {
            return array('error' => 'Your input is not consistent with our records.');
        }
        $this->email = $user_details->email;
        $this->userId = $user_details->id;
        $this->name = $user_details->name;

        try {
            Mail::to($this->email)->send(new NotifyMail('Password Reset', 'emails.password-reset-email',  ['name' => $this->name, 'data' => ['link' => $this->link]]));
            return array('success' => 'A passord reset link has been sent to your email. Please check your email to proceed!');
        } catch (\Exception $ex) {
            return array('error' => $ex->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->layout('layouts.base');
    }

    public function requestPasswordResetLink()
    { 
        $this->validate();
        try {

            $this->url = url('/');
            $this->token = Str::random(64);
            $this->link = $this->url . "/password-reset?data=" . $this->token . '&uipa=' . base64_encode($this->username);

            if ($this->username != '') {
                $send_email = $this->sendEmail();
                if (isset($send_email['error'])) {
                    return $this->dispatchBrowserEvent('swal:modal', [
                        'type' => 'warning',
                        'message' => 'Warning!',
                        'text' =>  $send_email['error']
                    ]);
                }
            }

            User::whereId($this->userId)->update([
                'reset_pwd_link' => $this->token
            ]);

            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'message' => 'Successful!',
                'text' => 'A reset password link has been sent to your email, kindly login to your mail and follow the direction.'
            ]);

        } catch (\Exception $ex) {
            return $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'message' => 'Warning!',
                'text' => 'Something goes wrong!!' . $ex->getMessage()
            ]);
        }
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
