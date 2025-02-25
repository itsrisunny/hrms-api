<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\JObPostController;
use App\Http\Controllers\OnBoardingController; 
use App\Http\Controllers\JobList; 
use App\Http\Controllers\JobFunctionController; 
use App\Http\Controllers\CarouselController;
use App\Http\Controllers\CMSSettingController;
use App\Http\Controllers\SMEController;

Route::post('login', [EmployeeController::class, 'login']);
Route::post('token', [EmployeeController::class, 'token']);
Route::post('send-reset-password-link', [EmployeeController::class, 'sendResetPasswordLink']); // Add this line
Route::post('validate-password-token', [EmployeeController::class, 'validatePasswordToken']); // Add this line
Route::post('change-password-with-token', [EmployeeController::class, 'changePasswordWithToken']); // Add this line
Route::get('company-information', [SettingController::class, 'companyInformation']);
Route::get('create-storage-link', [OnBoardingController::class, 'createStorageLink']);
Route::post('/job-detail', [JObPostController::class, 'listDetail']);
Route::post('job-list-count-by-group', [JobList::class, 'listJobsByJobFunctionGroup']);
Route::post('/job-list', [JobList::class, 'listActiveJobs']);
Route::post('/carousel-list', [CarouselController::class, 'listActiveCarousels']);
Route::post('/job-functions', [JObPostController::class, 'jobFunctions']);
Route::post('/apply-on-job', [OnBoardingController::class, 'applyOnJob']);
Route::get('/autogenerate-job-id', [JObPostController::class, 'autogenerateJobId']);
//Route::middleware('custom.auth')->get('user', [EmployeeController::class, 'getUser']);
Route::group(['middleware' => 'auth:api'], function () {
    Route::post('user-detail', [EmployeeController::class, 'getUser']);
    Route::post('employees', [EmployeeController::class, 'getEmployeesList']);
    Route::post('change-password', [EmployeeController::class, 'changePassword']);
    Route::post('add-employee', [EmployeeController::class, 'addEmployee']);
    Route::post('manager', [EmployeeController::class, 'getManager']);
    Route::post('employee-leave-holiday-list', [EmployeeController::class, 'getEmployeeLeaveHoliday']);
    Route::post('profile-picture', [EmployeeController::class, 'updateProfilePicture']);
    Route::post('department', [DepartmentController::class, 'getDepartment']);
    Route::post('attendance', [AttendanceController::class, 'markAttendance']);
    Route::post('employee-dashboard', [DashboardController::class, 'getEmployeeDashboard']);
    Route::post('admin-dashboard', [DashboardController::class, 'getAdminDashboard']);
    Route::post('employee', [EmployeeController::class, 'getEmployeeById']);
    Route::post('calender', [EventController::class, 'getCalenderData']);
    Route::post('add-event', [EventController::class, 'addEvent']);
    Route::post('leave-request', [LeaveController::class, 'leaveRequest']);
    Route::post('attendance-request', [LeaveController::class, 'attendanceRequest']);
    Route::post('attendance-request-list', [LeaveController::class, 'attendanceRequestList']);
    Route::post('attendance-status', [LeaveController::class, 'attendanceStatus']);
    Route::post('leave-request-list', [LeaveController::class, 'leaveRequestList']);
    Route::post('leave-status', [LeaveController::class, 'leaveManageStatus']);
    Route::post('remaining-leave', [LeaveController::class, 'remainingLeavs']);
    Route::post('settings', [SettingController::class, 'index']);
    Route::post('job-post/save', [JObPostController::class, 'saveJobPost']);
    Route::post('job-post/save/{id}', [JObPostController::class, 'saveJobPost']);
    
    Route::post('/job-posts', [JObPostController::class, 'listJobs']);
    Route::post('add-settings', [SettingController::class, 'addSetting']);
    Route::post('notification', [NotificationController::class, 'getNotification']);
    Route::post('read-notification', [NotificationController::class, 'readNotification']);
    Route::group(['prefix' => 'report'], function () {
        Route::post('attendance', [ReportController::class, 'attendance']);
        Route::post('leave', [ReportController::class, 'leave']);
        Route::post('employee', [ReportController::class, 'employee']);
    });
    Route::group(['prefix' => 'onboarding'], function () {
        Route::post('upload-and-save-resume', [OnBoardingController::class, 'uploadAndSaveResume']); // Add this line
        Route::post('list', [OnBoardingController::class, 'listOnBoardingRecords']);
        Route::post('schedule-interview', [OnBoardingController::class, 'scheduleInterview']);
        Route::post('interview-detail', [OnBoardingController::class, 'interviewDetail']);
        Route::post('detail', [OnBoardingController::class, 'individualInterviewDetail']);
    });
    Route::post('save-note', [OnBoardingController::class, 'saveNotepad']);
    Route::post('re-schedule', [OnBoardingController::class, 'reSceduleInterview']);
    Route::post('interview-list', [OnBoardingController::class, 'InterviewList']);
    
    Route::group(['prefix' => 'job-function'], function () {
        Route::post('list', [JobFunctionController::class, 'list']);
        Route::post('update-status/{id}', [JobFunctionController::class, 'updateStatus']);
        Route::post('edit/{id}', [JobFunctionController::class, 'edit']);
        Route::post('add', [JobFunctionController::class, 'add']);
    });
    Route::group(['prefix' => 'carousel'], function () {
        Route::post('list', [CarouselController::class, 'list']);
        Route::post('add', [CarouselController::class, 'add']);
        Route::post('edit/{id}', [CarouselController::class, 'edit']);
        Route::post('update-status/{id}', [CarouselController::class, 'updateStatus']);
        Route::post('delete/{id}', [CarouselController::class, 'delete']);
    });
    Route::post('cms-settings', [CMSSettingController::class, 'store']);
    Route::post('add-external-sme', [SMEController::class, 'addExternalSME']); // Add this line
    Route::get('active-sme-list', [SMEController::class, 'listActiveSMEs']); // Add this line
    Route::get('all-sme-list', [SMEController::class, 'listAllSMEs']); // Add this line
    Route::delete('delete-sme/{id}', [SMEController::class, 'deleteSME']); // Add this line
    Route::post('toggle-sme-status/{id}', [SMEController::class, 'toggleSMEStatus']); // Add this line
});