<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\ThemeSetupRequest;
use App\Services\ThemeService;
use App\Traits\PushNotificationTrait;
use App\Traits\SettingsTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Throwable;

class ThemeController extends BaseController
{
    use SettingsTrait;
    use PushNotificationTrait;

    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepo,
        private readonly ThemeService $themeService,
    )
    {}

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */
    public function index(Request|null $request, ?string $type = null): View
    {
        return $this->getView();
    }

    public function getView(): View
    {
        $themes = $this->themeService->getDirectories();
        return view('admin-views.system-setup.themes.theme-setup', compact('themes'));
    }

    public function upload(ThemeSetupRequest $request, ThemeService $themeService): JsonResponse
    {
        // [AI] Single authoritative theme architecture: ZIP uploads permanently disabled
        return response()->json([
            'status' => 'error',
            'message'=> translate('theme_upload_is_permanently_disabled_victorious_market_uses_a_single_authoritative_theme')
        ], 403);
    }

    public function publish(Request $request, ThemeService $themeService): JsonResponse
    {
        // [AI] Single authoritative theme architecture: runtime theme switching permanently disabled
        return response()->json([
            'status' => 'error',
            'message'=> translate('theme_switching_is_permanently_disabled_victorious_market_uses_a_single_authoritative_theme')
        ], 403);
    }

    public function activation(Request $request, ThemeService $themeService): Redirector|RedirectResponse|Application
    {
        // [AI] Single authoritative theme architecture: third-party theme activation permanently disabled
        ToastMagic::error(translate('theme_activation_is_disabled_victorious_market_uses_a_single_authoritative_theme'));
        return back();
    }

    public function delete(Request $request, ThemeService $themeService): JsonResponse
    {
        // [AI] Single authoritative theme architecture: theme deletion permanently disabled
        return response()->json([
            'status' => 'error',
            'message'=> translate('theme_deletion_is_permanently_disabled_victorious_market_uses_a_single_authoritative_theme')
        ], 403);
    }


    public function notifyAllTheVendors(Request $request, ThemeService $themeService): JsonResponse
    {
        $status = 0;
        $message = translate('Notification_Sent_to_All_Vendors_Fail');

        try {
            $dataArray = $themeService->getNotifySellersData(request: $request);
            $status = 1;
            $message = translate('Notification_Sent_to_All_Vendors');
            $this->notificationRepo->add($dataArray);
            $this->sendPushNotificationToTopic(data: $dataArray, topic: 'six_valley_seller');
        } catch (Throwable $th) {
        }

        return response()->json([
            'status' => $status,
            'message'=> $message,
        ]);
    }
}
