<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;
    protected $fillable = ['username', 'old_password', 'new_password', 'posted_ip', 'posted_userid'];
}
