<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Utils\Helpers;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * [AI] Class AdminAuditLogController
 * Read-only administrative audit log viewer.
 * Spec Reference: Sections 42, 43
 * Rule: Audit logs are immutable and append-only. No deletion or editing permitted.
 */
class AdminAuditLogController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (!Helpers::module_permission_check('audit_log.view') && !Helpers::module_permission_check('system_settings')) {
            ToastMagic::error(translate('Access Denied: You do not have permission to view administrative audit logs.'));
            return redirect()->route('admin.dashboard.index');
        }

        $query = AdminAuditLog::query()->with('admin');

        if ($request->has('searchValue') && !empty($request->searchValue)) {
            $search = $request->searchValue;
            $query->where(function ($q) use ($search) {
                $q->where('admin_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('resource_type', 'like', "%{$search}%")
                    ->orWhere('resource_id', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->has('action_filter') && !empty($request->action_filter)) {
            $query->where('action', $request->action_filter);
        }

        $auditLogs = $query->latest()->paginate(25);

        return view('admin-views.system.admin-audit-log', compact('auditLogs'));
    }
}
