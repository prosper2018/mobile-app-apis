<?php

namespace App\Http\Controllers\API;

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
use App\Models\LeaveApplications;
use Validator;
use Illuminate\Support\Facades\Hash;
use App\Http\Livewire\Users\ManageUsers;
use Illuminate\Support\Facades\Auth;
use App\Rules\DateRange;


class LeaveController extends BaseController
{
    /**
     * store the user inputted post data in the posts table
     * @return void
     */
    public function leaveSetup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_type' => 'required',
            'staff_id' => 'required',
            'reason' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            // 'end_date' => ['required', 'date', new DateRange],
            // 'app_document' => 'required|image|mimes:jpeg,png,svg,jpg,gif,pdf,doc|max:1024',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error validation', $validator->errors());
        }
        $doc_ext = ($request->app_document != '') ? pathinfo($request->app_document->getRealPath(), PATHINFO_EXTENSION) : '';
        // $this->id_path = $request->app_document->storeAs('id_card', str_replace(' ', '_', $request->username . '.' . $id_ext));

        try {
            $app = LeaveApplications::create([
                'leave_type' => $request->leave_type,
                'staff_id' => $request->staff_id,
                'reason' => $request->reason,
                'app_document' => $request->app_document,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'submitted_by' => Auth::user()->username
            ]);
            if ($app) {
                $success['staff'] =  $app;
                return $this->sendResponse($success, 'Application submitted successfully.');
            } else {
                return $this->sendError('Error staff setup', ['Unable to setup leave application.'], 404);;
            }
        } catch (\Exception $ex) {
            return $this->sendError('Error', $ex->getMessage(), 404);
        }
    }
}
