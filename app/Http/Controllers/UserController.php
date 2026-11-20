<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Admin only: used to fill the "assign to" dropdown.
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Admins only');

        return ApiResponse::success(
            UserResource::collection(User::where('role', 'user')->orderBy('name')->get())
        );
    }
}
