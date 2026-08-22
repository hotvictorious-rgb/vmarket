<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppBroadcast;
use App\Services\WhatsAppBroadcastService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhatsAppBroadcastController extends Controller
{
    /**
     * [AI] Broadcast Campaigns Dashboard View
     */
    public function index(): View
    {
        $broadcasts = WhatsAppBroadcast::with('creator')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin-views.whatsapp-crm.broadcasts', compact('broadcasts'));
    }

    /**
     * [AI] Create & Launch Broadcast Campaign
     */
    public function store(Request $request, WhatsAppBroadcastService $broadcastService): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'template_name' => 'required|string|max:100',
            'city_filter' => 'nullable|string',
            'min_ltv' => 'nullable|numeric',
            'scheduled_for' => 'nullable|date',
        ]);

        $adminId = auth('admin')->id();

        $filters = [];
        if (!empty($request->city_filter)) {
            $filters['city'] = $request->city_filter;
        }
        if (!empty($request->min_ltv)) {
            $filters['min_ltv'] = (float)$request->min_ltv;
        }

        $data = [
            'title' => $request->title,
            'template_name' => $request->template_name,
            'filters' => $filters,
            'scheduled_for' => $request->scheduled_for,
        ];

        $broadcastService->createAndQueueBroadcast($data, $adminId);

        Toastr::success('WhatsApp broadcast campaign created and queued successfully!');
        return back();
    }
}
