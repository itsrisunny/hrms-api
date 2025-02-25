<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SME;
use Illuminate\Support\Facades\Hash;

class SMEController extends Controller
{
    public function addExternalSME(Request $request)
    {
        $validatedData = $request->validate([
            'sme_name' => 'required|string|max:255',
            'sme_id' => 'required|string|max:255|unique:s_m_e_s,sme_id',
            'sme_phone' => 'required|string|max:15',
            'sme_email' => 'required|string|email|max:255|unique:s_m_e_s,sme_email',
            'sme_expertise_area' => 'required|string|max:255',
            'sme_linkedin_profile' => 'nullable|string|max:255',
            'sme_temporary_email' => 'nullable|string|email|max:255',
            'sme_temporary_password' => 'nullable|string|max:255',
            'enable_temporary_values' => 'required|boolean',
        ]);

        if (!empty($validatedData['sme_temporary_password'])) {
            $validatedData['sme_temporary_password'] = Hash::make($validatedData['sme_temporary_password']);
        }

        $validatedData['status'] = 1; // Set status to Active by default

        $sme = SME::create($validatedData);

        return response()->json(['message' => 'SME added successfully', 'sme' => $sme], 201);
    }

    public function listActiveSMEs()
    {
        $activeSMEs = SME::where('status', 1)->get(['id', 'sme_name']);
        return response()->json($activeSMEs);
    }

    public function listAllSMEs()
    {
        $allSMEs = SME::where('status', '!=', 90)->get(['id','sme_name',
        'sme_id',
        'sme_phone',
        'sme_email',
        'sme_expertise_area',
        'sme_linkedin_profile',
        'sme_temporary_email',
        'enable_temporary_values',
        'status']);
        return response()->json($allSMEs);
    }

    public function deleteSME($id)
    {
        $sme = SME::find($id);
        if ($sme) {
            $sme->status = 90; // Set status to Deleted
            $sme->save();
            return response()->json(['message' => 'SME deleted successfully']);
        } else {
            return response()->json(['message' => 'SME not found'], 404);
        }
    }

    public function toggleSMEStatus($id)
    {
        $sme = SME::find($id);
        if ($sme && $sme->status != 90) { // Check if SME is not deleted
            $sme->status = $sme->status == 1 ? 0 : 1; // Toggle between Active and Inactive
            $sme->save();
            return response()->json(['message' => 'SME status updated successfully', 'sme' => $sme]);
        } else {
            return response()->json(['message' => 'SME not found or is deleted'], 404);
        }
    }
}
