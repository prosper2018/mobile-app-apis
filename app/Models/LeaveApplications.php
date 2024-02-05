<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplications extends Model
{
    use HasFactory;

    protected $fillable = ['leave_type', 'staff_id', 'reason', 'app_document', 'start_date', 'end_date', 'app_status', 'created_at', 'updated_at', 'submitted_by', 'approved_date', 'approved_by', 'rejection_date', 'rejected_by', 'comments_reason', 'is_deleted'];
}
