<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Section;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function indexBySection($sectionId)
    {
        $section = Section::with('course')->find($sectionId);
        if (!$section) {
            return response()->json(['status' => false, 'message' => 'Section not found'], 404);
        }

        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher' || $section->course->teacher_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $materials = Material::where('section_id', $sectionId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['status' => true, 'data' => $materials]);
    }

    public function store(Request $request)
    {
        // 1. BASIC VALIDATION (Check Inputs)
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:pdf,video,text,ppt,document,link',
        ]);

        // Ownership check (teacher must own the course)
        $section = Section::with('course')->find($request->section_id);
        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher' || !$section || $section->course->teacher_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        // 2. VALIDATE BASED ON TYPE
        $type = $request->type;

        // File-based uploads
        if ($type === 'pdf') {
            $request->validate([
                'file' => 'required|file|mimes:pdf|max:20480', // 20MB
            ]);
        }

        if ($type === 'ppt') {
            $request->validate([
                'file' => 'required|file|mimes:ppt,pptx|max:20480',
            ]);
        }

        if ($type === 'document') {
            $request->validate([
                'file' => 'required|file|mimes:doc,docx,txt,rtf|max:20480',
            ]);
        }

        // Video: allow either file upload OR URL/content
        if ($type === 'video') {
            if (!$request->hasFile('file')) {
                $request->validate([
                    'content' => 'required|string',
                ]);
            } else {
                $request->validate([
                    'file' => 'file|mimes:mp4,mov,avi|max:20480',
                ]);
            }
        }

        // Link: store URL in content
        if ($type === 'link') {
            $request->validate([
                'content' => 'required|url',
            ]);
        }

        // Text: store content
        if ($type === 'text') {
            $request->validate([
                'content' => 'required|string',
            ]);
        }

        $filePath = null;

        try {
            // 4. HANDLE FILE UPLOAD
            if ($request->hasFile('file')) {
                // Generate a clean filename (e.g., "timestamp_filename.pdf")
                $filename = time() . '_' . $request->file('file')->getClientOriginalName();
                
                // Store in "storage/app/public/materials"
                $path = $request->file('file')->storeAs('materials', $filename, 'public');
                
                // Create the Public URL
                $filePath = '/storage/' . $path;
            }

            // 5. SAVE TO DATABASE
            $material = Material::create([
                'section_id' => $request->section_id,
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'file_path' => $filePath,
                'content' => $request->content,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Material added successfully!',
                'data' => $material
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $material = Material::find($id);
        if (!$material) {
            return response()->json(['status' => false, 'message' => 'Material not found'], 404);
        }

        $section = Section::with('course')->find($material->section_id);
        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher' || !$section || $section->course->teacher_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:pdf,video,text,ppt,document,link',
            'content' => 'nullable|string',
        ]);

        $type = $request->input('type', $material->type);

        // Handle new file if provided
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'file|max:20480',
            ]);

            // Delete old file
            if ($material->file_path && str_starts_with($material->file_path, '/storage/')) {
                $old = str_replace('/storage/', '', $material->file_path);
                Storage::disk('public')->delete($old);
            }

            $filename = time() . '_' . $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->storeAs('materials', $filename, 'public');
            $material->file_path = '/storage/' . $path;
        }

        if ($request->has('title')) $material->title = $request->title;
        if ($request->has('description')) $material->description = $request->description;
        if ($request->has('type')) $material->type = $request->type;
        if (in_array($type, ['text', 'video', 'link'], true) && $request->has('content')) {
            $material->content = $request->content;
        }

        $material->save();

        return response()->json(['status' => true, 'message' => 'Material updated', 'data' => $material]);
    }

    public function destroy($id)
    {
        $material = Material::find($id);
        if (!$material) {
            return response()->json(['status' => false, 'message' => 'Material not found'], 404);
        }

        $section = Section::with('course')->find($material->section_id);
        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher' || !$section || $section->course->teacher_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($material->file_path && str_starts_with($material->file_path, '/storage/')) {
            $old = str_replace('/storage/', '', $material->file_path);
            Storage::disk('public')->delete($old);
        }

        $material->delete();

        return response()->json(['status' => true, 'message' => 'Material deleted']);
    }
}