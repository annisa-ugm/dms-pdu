<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
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
                'permission_id' => 'required|exists:permissions,id',
                'file_id' => 'required|exists:files,id',
            ]);

            $user = User::where('email', $request->email)->firstOrFail();
            $user_id = $user->id;

            $existingShare = $file->shares()
                ->wherePivot('user_id', $user_id)
                ->wherePivot('permission_id', $validated['permission_id'])
                ->first();

            if ($existingShare) {
                $file->shares()->updateExistingPivot(
                    $user_id,
                    [
                        'permission_id' => $validated['permission_id'],
                        'created_by' => Auth::id(),
                    ],
                );
            } else {
                $file->shares()->attach(
                    $user_id,
                    [
                        'permission_id' => $validated['permission_id'],
                        'created_by' => Auth::id(),
                    ]
                );
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
