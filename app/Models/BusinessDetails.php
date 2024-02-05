<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDetails extends Model
{
    use HasFactory;
    protected $fillable = ['business_name', 'address', 'description', 'logo', 'posted_user', 'posted_userid', 'posted_ip'];
}
