<?php

namespace App\Http\Controllers\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Models\BlacklistedCustomer;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlacklistController extends Controller
{
    /**
     * [AI] 1-Click Ban Customer & Blacklist identifiers.
     */
    public function banCustomer(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'phone' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        try {
            $user = null;
            if ($request->user_id) {
                $user = User::find($request->user_id);
            }

            if ($user) {
                $user->update(['is_active' => 0]);
            }

            BlacklistedCustomer::updateOrCreate(
                ['phone' => $request->phone],
                [
                    'user_id' => $user?->id,
                    'email' => $user?->email ?? $request->email,
                    'ip_address' => $request->ip(),
                    'reason' => $request->reason ?? 'Manual payment fraud / Suspicious activity',
                    'banned_by' => auth('admin')->id() ?? 1,
                ]
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Customer successfully banned and blacklisted across all platforms.',
                ]);
            }

            Toastr::success('Customer successfully banned and blacklisted.');
            return back();

        } catch (Exception $e) {
            Log::error('[BlacklistController Ban Error] ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
            Toastr::error('Failed to ban customer.');
            return back();
        }
    }

    /**
     * [AI] 1-Click Unban Customer.
     */
    public function unbanCustomer(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        try {
            BlacklistedCustomer::where('phone', $request->phone)->delete();
            User::where('phone', $request->phone)->update(['is_active' => 1]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => true, 'message' => 'Customer access restored.']);
            }

            Toastr::success('Customer access restored.');
            return back();

        } catch (Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
            Toastr::error('Failed to restore customer.');
            return back();
        }
    }
}
