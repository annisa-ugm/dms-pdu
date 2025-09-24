<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    public function store(Request $request, File $file){
        try {
            if (Auth::id() !== $file->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to share this file.',
            ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'permission_id' => 'required|exists:permissions,id',
                'file_id' => 'required|exists:files,id',
            ]);

            $existingShare = $file->shares()
                ->where('user_id', $validated['user_id'])
                ->where('permission_id', $validated['permission_id'])
                ->first();

            if ($existingShare) {
                $existingShare->pivot->update([
                    'permission_id' => $validated['permission_id'],
                    'created_by' => Auth::id(),
                ]);
            } else {
                $file->shares()->attach(
                    $validated['user_id'],
                    ['permission_id' => $validated['permission_id'],
                    'created_by' => Auth::id()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'File shared successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to share file: ' . $e->getMessage(),
            ], 500);
        }
    }
}
