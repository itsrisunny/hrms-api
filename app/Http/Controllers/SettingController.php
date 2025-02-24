<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index(Request $request){
        $settings = Setting::get();
        foreach ($settings as &$setting) {
            if($setting["type"] == "working days" && $this->isJson($setting["value"])){
                $setting["value"] = json_decode($setting["value"], true);
            }
            if($setting["type"] == "company_logo"){
                $setting["value"] = Storage::url($setting["value"]);
            }
        }
        return response()->json(["result" => $settings, 'status' => 200]);
    }

    public function addSetting(Request $request)
    {
        // Check if the request is JSON or form-data
        if ($request->isJson()) {
            $settings = $request->json()->all();
        } else {
            $settings = $request->all();
        }

        foreach ($settings as $key => $settingData) {
            if ($key === 'value' && $request->hasFile('value')) {
                $settingData = [
                    'type' => $request->input('type'),
                    'value' => $request->file('value')
                ];
            }
            if (is_array($settingData)) {
                $this->processSetting($settingData, $request);
            }
        }

        return response()->json(['message' => 'Settings processed successfully.']);
    }

    private function processSetting($settingData, $request)
    {
        $type = $settingData['type'];
        $value = $settingData['value'];

        // Handle file upload for company logo
        if ($type == 'company_logo' && $value instanceof \Illuminate\Http\UploadedFile) {
            $filename = time() . '_' . $value->getClientOriginalName();
            $filePath = $value->storeAs('uploads/company_logos', $filename, 'public');
            $value = $filePath;
        }

        $setting = Setting::where('type', $type)->first();

        if ($setting) {
            $setting->value = $value;
            $setting->save();
        } else {
            $setting = new Setting();
            $setting->type = $type;
            $setting->value = $value;
            $setting->save();
        }
    }

    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function companyInformation()
    {
        $settings = Setting::where('company_id', 1)
            ->whereIn('type', ['CompanyName', 'CompanyAddress', 'CompanyPhone', 'CompanyEmail', 'company_logo', 'primaryColor', 'headerColor', 'secondaryColor','fontFamily','searchFormPosition'])
            ->get();
        $companyInfo = [];

        foreach ($settings as $setting) {
            if ($setting->type == 'company_logo') {
                $companyInfo[$setting->type] = url(Storage::url($setting->value));
            } else {
                $companyInfo[$setting->type] = $setting->value;
            }
        }

        return response()->json(['company_information' => $companyInfo, 'status' => 200]);
    }

}
