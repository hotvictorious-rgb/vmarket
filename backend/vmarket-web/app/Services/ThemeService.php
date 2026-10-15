<?php

namespace App\Services;

use App\Traits\FileManagerTrait;
use App\Traits\SettingsTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;
use ZipArchive;

class ThemeService
{

    use SettingsTrait;
    use FileManagerTrait;

    public function getUploadData(object $request): array
    {
        // [AI] Single authoritative theme: uploads permanently disabled
        return [
            'status' => 'error',
            'message' => translate('theme_upload_is_permanently_disabled_victorious_market_uses_a_single_authoritative_theme')
        ];
    }

    public function getPublishData(object $request): array
    {
        // [AI] Single authoritative theme: switching permanently disabled
        return [
            'reload_action' => 0,
            'informationModal' => '',
            'status' => 'error',
            'message' => translate('theme_switching_is_permanently_disabled_victorious_market_uses_a_single_authoritative_theme')
        ];
    }

    public function getActivationData(object $request): bool
    {
        // [AI] Single authoritative theme: external phone-home activation permanently disabled
        return false;
    }

    public function getNotifySellersData(object $request): array
    {
        return [
            'sent_by' => 'admin',
            'sent_to' => 'seller',
            'title' => 'Theme Changed to ' . ucwords(str_replace('_', ' ', theme_root_path())),
            'description' => 'Theme Changed Description, time - ' . Carbon::now(),
            'image' => $request->has('image') ? $this->upload(dir: 'notification/', format: 'webp', image: $request->file('image')) : null,
            'status' => 1,
            'notification_count' => 1
        ];
    }

    public function deleteTheme(object $request): array
    {
        // [AI] Single authoritative theme: theme deletion permanently disabled
        return [
            'status' => 'error',
            'message' => translate('theme_deletion_is_permanently_disabled_victorious_market_uses_a_single_authoritative_theme')
        ];
    }

    function getDirectories(): array
    {
        // [AI] Single authoritative storefront theme: theme_vmarket
        return [
            'theme_vmarket' => [
                'name' => 'Victorious MARKET Master Theme',
                'software_id' => 'MzE0NDg1OTc=',
                'version' => config('app.asset_version', '1.0.1'),
                'is_active' => 1,
                'image' => 'theme_vmarket.png',
                'comfortable_panel_version' => defined('SOFTWARE_VERSION') ? SOFTWARE_VERSION : '16.1',
            ]
        ];
    }

}
