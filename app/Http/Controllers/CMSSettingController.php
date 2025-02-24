<?php

namespace App\Http\Controllers;

use App\Models\CMSSetting;
use App\Models\Setting;
use Illuminate\Http\Request;

class CMSSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            '*.type' => 'required|string',
            '*.value' => 'nullable|string',
        ]);

        foreach ($validatedData as $data) {
            Setting::updateOrCreate(
                [
                    'company_id' => 1,
                    'type' => $data['type']
                ],
                ['value' => $data['value']]
            );
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CMSSetting  $cMSSetting
     * @return \Illuminate\Http\Response
     */
    public function show(CMSSetting $cMSSetting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CMSSetting  $cMSSetting
     * @return \Illuminate\Http\Response
     */
    public function edit(CMSSetting $cMSSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CMSSetting  $cMSSetting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CMSSetting $cMSSetting)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CMSSetting  $cMSSetting
     * @return \Illuminate\Http\Response
     */
    public function destroy(CMSSetting $cMSSetting)
    {
        //
    }
}
