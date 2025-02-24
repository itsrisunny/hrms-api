<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Smtp;
use App\Models\ResetPasswordToken;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Event;
use App\Models\EmployeeLeaveBalance;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Optional;
use Illuminate\Support\Str;
class EmployeeController extends Controller
{


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $employee = Employee::where('email', $request->email)->first();

        if (!$employee || !Hash::check($credentials['password'], $employee->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        if ($employee->Status !== 'Active') {
            return response()->json(['error' => 'Your account is inactive, please contact Admin', "status" => 403], 403);
        }
        try {
            $token = JWTAuth::fromUser($employee);
            return response()->json(['token' => $token, "status" => 200]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }
    public function token(Request $request)
    {
        $credentials = $request->only('username');

        $employee = Employee::where('Email', $credentials['username'])->first();

        if (!$employee) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        if ($employee->Status !== 'Active') {
            return response()->json(['error' => 'Your account is inactive, please contact Admin', "status" => 200], 403);
        }
        try {
            $token = JWTAuth::fromUser($employee);
            return response()->json(['token' => $token, "status" => 200]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }
    public function getUser(Request $request)
    {
        $employee = $request->user();
        $employee['ProfilePicture'] = $employee['ProfilePicture']?url(Storage::url('profile_pictures/' . $employee['ProfilePicture'])):url(Storage::url('profile_pictures/no-img.jpg'));
        return response()->json(['data'=>$employee, 'status' => 200]);
    }
    public function getEmployeeById(Request $request)
    {
        $data = $request->only('EmployeeID');
        $request->validate([
            'EmployeeID' => 'required'
        ]);
        $employee = Employee::with(['role', 'department', 'manager'])->where("EmployeeID", $data['EmployeeID'])->first();
        $employee['CompanyID'] = $employee['CompanyID']?$employee['CompanyID']:"-";
        $employee['ProfilePicture'] = $employee['ProfilePicture']?url(Storage::url('profile_pictures/' . $employee['ProfilePicture'])):url(Storage::url('profile_pictures/no-img.jpg'));
        return response()->json(['data'=>$employee, 'status' => 200]);
    }
    function removePasswordKey($employees) {
        return array_map(function ($employee) {
            unset($employee['password']);
            return $employee;
        }, $employees);
    }
    public function getEmployeesList(Request $request)
    {
        $employees = Employee::with(['role', 'department', 'manager'])->get()->toArray();
        foreach ($employees as &$employee) {
            $employee['CompanyID'] = $employee['CompanyID']?$employee['CompanyID']:"-";
            $employee['ProfilePicture'] = $employee['ProfilePicture']?url(Storage::url('profile_pictures/' . $employee['ProfilePicture'])):url(Storage::url('profile_pictures/no-img.jpg')); // update the ProfilePicture key
        }
        return response()->json(['data'=> $this->removePasswordKey($employees), 'status' => 200]);
    }
    public function getManager(Request $request)
    {
        $employees = Employee::select('FirstName', 'LastName', 'EmployeeID')->get()->toArray();
        return response()->json(['data'=> $employees, 'status' => 200]);
    }
    public function addEmployee(Request $request)
    {
        // Validate the request data
        $rules = [
            'status' => 'required',
            'department' => 'required',
            'manager' => 'required',
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'personalEmail' => 'required|email',
            'designation' => 'required',
            'mobileNumber' => 'required',
            'companyId' => 'required',
        ];
        
        if (!$request->has('EmployeeID')) {
            $rules['password'] = 'required';
            $rules['profilePicture'] = 'required|mimes:jpg,jpeg,png|max:2048';
            $rules['email'] .= '|unique:employees'; // Add the unique rule only when creating a new employee
        }
        
        $request->validate($rules);

        if ($request->has('EmployeeID')) {
            $employee = Employee::find($request->input('EmployeeID'));
            if (!$employee) {
                return response()->json(['message' => 'Employee not found'], 404);
            }
        } else {
            $employee = new Employee();
        }
        $employee->CompanyID = $request->input('companyId');
        $employee->FirstName = $request->input('firstName');
        $employee->LastName = $request->input('lastName');
        $employee->DateOfBirth = $request->input('dob')?$request->input('dob'):"";
        $employee->Gender = $request->input('gender');
        $employee->HireDate = $request->input('dateOfHire')?$request->input('dateOfHire'):"";
        if(!$request->has('EmployeeID')){
            $employee->RoleID = '2';
        }        
        $employee->DepartmentID = $request->input('department');
        $employee->Designation = $request->input('designation');
        $employee->ManagerID = $request->input('manager');
        $employee->CurrentAddress = $request->input('currentAddress');
        $employee->PermanentAddress = $request->input('permanentAddress');
        $employee->Phone = $request->input('mobileNumber');
        if (!$request->has('EmployeeID')) {
            $employee->password = Hash::make($request->input('password'));
        }
        $employee->email = $request->input('email');
        $employee->personalEmail = $request->input('personalEmail');
        $employee->EmergencyContactNumber = $request->input('emergencyNumber');
        $employee->BloodGroup = $request->input('bloodGroup');
        $employee->Status = $request->input('status');
        $employee->DateOfJoining = $request->input('dateOfJoining')?$request->input('dateOfJoining'):"";
        $employee->Nationality = $request->input('nationality');
        $employee->MaritalStatus = $request->input('maritalStatus');
        $employee->createdBy = $request->input('createdBy');
        
        if ($request->hasFile('profilePicture')) {
            $profilePicture = $request->file('profilePicture');
            $filename = time() . '.' . $profilePicture->getClientOriginalExtension();
            $profilePicture->storeAs('public/profile_pictures', $filename);
            $employee->ProfilePicture = $filename;
        }
        
        $employee->save();
        
        // Return a success response
        if ($request->has('EmployeeID')) {
            return response()->json(['message' => 'Employee updated successfully'], 200);
        } else {
            return response()->json(['message' => 'Employee added successfully'], 201);
        }
    }
    public function changePassword(Request $request){
        $request->validate([
            'EmployeeID' => 'required',
            'Password' => 'required',
            'ConfirmPassword' => 'required',
        ]);
        $employee = Employee::find($request->input('EmployeeID'));
        if ($employee) {
            $employee->password = Hash::make($request->input('Password'));
            $employee->save();
            return response()->json(['message' => 'Password has been changed successfully', 'status' => 200], 200);
        } else {
            return response()->json(['error' => 'The employee ID is not valid.'], 422);
        }
    }
    public function getEmployeeLeaveHoliday(Request $request){
        $today = Carbon::today(); // Get today's date
        $request->validate([
            'EmployeeID' => 'required'
        ]);
        //$leaves = Leave::with(['employee'])->where('EmployeeID', $request->EmployeeID) ->where('Status', '=', 'Approved')->orderBy('LeaveRequestID', 'desc')->get();
        $leaves = Leave::with(['employee'])->where('EmployeeID', $request->EmployeeID)
        ->whereIn('Status', ['Approved', 'deducted']) // Add 'deducted' status check
        ->whereYear('StartDate', '=', $today->format('Y')) // Use current year
        ->orderBy('LeaveRequestID', 'desc')->get();
        foreach ($leaves as $leave) {
            $leave->StartDate = Carbon::parse($leave->StartDate)->format('d M Y');
            $leave->EndDate = Carbon::parse($leave->EndDate)->format('d M Y');
            $leave->RequestDate = Carbon::parse($leave->RequestDate)->format('d M Y');
            $leave->LeaveType = $leave->LeaveType?$leave->LeaveType." Leave":"-";
            $leave->employeeName = $leave->employee->FirstName . ' ' . $leave->employee->LastName;
            $leave->EmployeeProfilePhoto = $leave->employee->ProfilePicture?url(Storage::url('profile_pictures/' . $leave->employee->ProfilePicture)):url(Storage::url('profile_pictures/no-img.jpg'));
            if ($leave->Document) {
                if (str_starts_with($leave->Document, '[') && str_ends_with($leave->Document, ']')) {
                    $files = json_decode($leave->Document, true);
                    $documents = [];
                    foreach ($files as $file) {
                        $documents[] = url(Storage::url('leave/' .$leave->employee->EmployeeID.'/'. $file['filename']));
                    }
                    $leave->Document = $documents;
                } else {
                     
                    $leave->Document = array(url(Storage::url('leave/' .$leave->employee->EmployeeID.'/'. $leave->Document)));
                }
            } else {
                $leave->Document = null;
            }
            unset($leave->employee);
        }
        $holiday = Event::whereIn("EventType", ["Holiday", "Holiday-F"])->whereYear('EventStartDate', '=', $today->format('Y'))->orderBy("EventStartDate", "DESC")->get();
        //$holiday = Event::whereIn("EventType", ["Holiday", "Holiday-F"])->orderBy("EventStartDate", "DESC")->get();
        //$getBalanceLeave = EmployeeLeaveBalance::where("EmployeeID",  $request->EmployeeID)->first();
        
        $getBalanceLeave = EmployeeLeaveBalance::where("EmployeeID",  $request->EmployeeID)->where('Year', $today->format('Y'))->first();
        if ($getBalanceLeave === null) {
            $getBalanceLeave = [
                'Sick' => 0,
                'Paid' => 0,
                'Floating' => 0,
                'Bereavement' => 0,
                'Paternity' => 0,
                'Maternity' => 0,
            ];
        }
        $leaveTypes = ['Sick', 'Paid', 'Floating', 'Bereavement', 'Paternity', 'Maternity'];
        foreach ($leaveTypes as $leaveType) {
            $totalLeaveDays = 0;
            foreach ($leaves as $leave) {
                if ($leave->LeaveType === $leaveType . ' Leave') {
                    $leaveDays = $this->calculateLeaveDays($leave->StartDate, $leave->EndDate);
                    $usedLeave = ($leave->HalfFull == "Full Day") ? $leaveDays : 0.5;
                    $totalLeaveDays += $usedLeave; // assume getLeaveDays() method returns the number of leave days
                }
            }
            $getBalanceLeave[$leaveType] -= $totalLeaveDays;
            $getBalanceLeave[$leaveType] = max(0, $getBalanceLeave[$leaveType]); // ensure remaining leave is not negative
        }
        unset($getBalanceLeave->EmpLeaveBalanceID);
        unset($getBalanceLeave->EmployeeID);
        unset($getBalanceLeave->Year);
        return response()->json(['data'=> ["leaves"=>$leaves,"holiday"=>$holiday, "balanceLeave" => $getBalanceLeave], 'status' => 200]);
    }

    private function calculateLeaveDays($fromDate, $toDate)
    {
        $start = \Carbon\Carbon::parse($fromDate);
        $end = \Carbon\Carbon::parse($toDate);

        return $start->diffInDays($end) + 1; // +1 to include the start date
    }
    public function updateProfilePicture(Request $request){
        $rules = [
            "EmployeeID" => "required",
            "profilePicture" => "required|mimes:jpg,jpeg,png|max:2048"
        ];
        $request->validate($rules);
        $employee = Employee::find($request->input('EmployeeID'));
        if ($employee) {
            if ($request->hasFile('profilePicture')) {
                $profilePicture = $request->file('profilePicture');
                $filename = time() . '.' . $profilePicture->getClientOriginalExtension();
                $profilePicture->storeAs('public/profile_pictures', $filename);
                $employee->ProfilePicture = $filename;
            }
            $employee->save();
            return response()->json(['message' => 'Your profile picture has been updated successfully!', 'status' => 200], 200);
        } else {
            return response()->json(['error' => 'The employee ID is not valid.'], 422);
        }
    }
    public function sendResetPasswordLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $employee = Employee::where('email', $request->input('email'))->first();

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $token = Str::random(60); // Generate a random token
        $resetLink = 'https://sphere.apisod.ai/reset-password?token=' . $token;

        // Store the token in the reset_password_tokens table
        ResetPasswordToken::create([
            'email' => $employee->email,
            'token' => $token,
        ]);

        $smtp = Smtp::first();
        config(['mail.mailers.smtp.host' => $smtp->host]);
        config(['mail.mailers.smtp.port' => $smtp->port]);
        config(['mail.mailers.smtp.username' => $smtp->username]);
        config(['mail.mailers.smtp.password' => $smtp->password]);
        config(['mail.mailers.smtp.encryption' => 'tls']);
        Mail::send('emails.reset_password', ['resetLink' => $resetLink, 'employee' => $employee], function ($message) use ($employee) {
            $message->from('spherehrms@apisod.ai', 'HRMS Portal');
            $message->to($employee->email);
            $message->subject('Reset Password');
        });

        return response()->json(['message' => 'Reset password link has been sent to your email', 'status' => 200], 200);
    }

    public function validatePasswordToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);
        $resetToken = ResetPasswordToken::where('token', $request->token)->first();
        if ($resetToken) {
            return response()->json(['message' => 'Token is valid', 'status' => 200], 200);
        } else {
            return response()->json(['error' => 'Token is invalid or expired'], 401);
        }
    }

    public function changePasswordWithToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'newPassword' => 'required|min:8',
            'confirmPassword' => 'required|same:newPassword',
        ]);
        $resetToken = ResetPasswordToken::where('token', $request->token)->first();

        if ($resetToken) {
            $employee = Employee::where('email', $resetToken->email)->first();
            $employee->password = Hash::make($request->input('newPassword'));
            $employee->save();
            $resetToken->delete();
            return response()->json(['message' => 'Password has been changed successfully.', 'status' => 200], 200);
        } else {
            return response()->json(['error' => 'Token is invalid or expired'], 401);
        }
    }
}
