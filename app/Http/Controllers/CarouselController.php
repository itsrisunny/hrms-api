<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarouselController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Carousel  $carousel
     * @return \Illuminate\Http\Response
     */
    public function show(Carousel $carousel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Carousel  $carousel
     * @return \Illuminate\Http\Response
     */
    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Carousel  $carousel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Carousel $carousel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Carousel  $carousel
     * @return \Illuminate\Http\Response
     */
    public function destroy(Carousel $carousel)
    {
        //
    }
    public function listActiveCarousels(Request $request)
    {
        $carousels = Carousel::where('status', 1)
            ->orderBy('id', 'desc')
            ->get(['title', 'description', 'image']);
        
        $carousels->transform(function ($carousel) {
            $carousel->image = $carousel->image ? url(Storage::url($carousel->image)) : "";
            return $carousel;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Active carousels retrieved successfully',
            'data' => $carousels
        ]);
    }

    public function list(Request $request)
    {
        $carousels = Carousel::orderBy('id', 'desc')->get(['id', 'title', 'description', 'status', 'image']);
        $carousels->transform(function ($carousel) {
            $carousel->image = $carousel->image ? url(Storage::url($carousel->image)) : "";
            return $carousel;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Carousels retrieved successfully',
            'data' => $carousels
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        $carousel = new Carousel();
        $carousel->title = $request->input('title');
        $carousel->description = $request->input('description');
        $carousel->status = 1;
        $imagePath = $request->file('image')->store('carousels', 'public');
        $carousel->image = $imagePath;
        $carousel->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Carousel added successfully',
            'data' => $carousel
        ]);
    }

    public function edit(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|in:0,1'
        ]);

        $carousel = Carousel::find($id);
        if (!$carousel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Carousel not found'
            ], 404);
        }

        $carousel->title = $request->input('title');
        $carousel->description = $request->input('description');
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('carousels', 'public');
            $carousel->image = $imagePath;
        }
        $carousel->status = $request->input('status');
        $carousel->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Carousel updated successfully',
            'data' => $carousel
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:0,1'
        ]);

        $carousel = Carousel::find($id);
        if (!$carousel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Carousel not found'
            ], 404);
        }

        $carousel->status = $request->input('status');
        $carousel->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Carousel status updated successfully'
        ]);
    }

    public function delete($id)
    {
        $carousel = Carousel::find($id);
        if (!$carousel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Carousel not found'
            ], 404);
        }

        $carousel->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Carousel deleted successfully'
        ]);
    }
}
