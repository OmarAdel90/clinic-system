<?php

namespace Modules\CRM\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class MetaSettingsService
{
    public function __construct(protected MetaAdsService $metaAdsService) {}

    public function getSettings(): array
    {
        $adsAccounts = $this->resolveAdAccounts();

        return [
            'webhook_url' => url('/api/webhook/meta'),
            'facebook_instagram' => [
                'facebook_page_id' => (string) config('services.meta_facebook.page_id', ''),
                'facebook_page_access_token' => (string) config('services.meta_facebook.page_access_token', ''),
                'instagram_access_token' => (string) config('services.meta_facebook.instagram_access_token', ''),
                'ads_access_token' => (string) config('services.meta_facebook.ads_access_token', ''),
                'selected_ad_account_id' => (string) config('services.meta_facebook.ad_account_id', ''),
                'available_ad_accounts' => $adsAccounts,
                'app_id' => (string) config('services.meta_facebook.app_id', ''),
                'app_secret' => (string) config('services.meta_app_secret', ''),
                'verify_token' => (string) config('services.meta_facebook.verify_token', ''),
                'api_version' => (string) config('services.meta_facebook.api_version', 'v20.0'),
                'facebook_token_configured' => filled(config('services.meta_facebook.page_access_token')),
                'instagram_token_configured' => filled(config('services.meta_facebook.instagram_access_token')),
                'ads_token_configured' => filled(config('services.meta_facebook.ads_access_token')),
            ],
            'whatsapp' => [
                'access_token' => (string) config('services.meta_whatsapp.access_token', ''),
                'phone_number_id' => (string) config('services.meta_whatsapp.phone_number_id', ''),
                'waba_id' => (string) config('services.meta_whatsapp.waba_id', ''),
                'verify_token' => (string) config('services.meta_whatsapp.verify_token', ''),
                'api_version' => (string) config('services.meta_whatsapp.api_version', 'v20.0'),
                'access_token_configured' => filled(config('services.meta_whatsapp.access_token')),
            ],
        ];
    }

    public function updateFacebookInstagram(array $data): array
    {
        $this->writeEnvValues([
            'META_FACEBOOK_PAGE_ID' => $data['facebook_page_id'] ?? '',
            'META_FACEBOOK_PAGE_ACCESS_TOKEN' => $data['facebook_page_access_token'] ?? '',
            'META_INSTAGRAM_ACCESS_TOKEN' => $data['instagram_access_token'] ?? '',
            'META_ADS_ACCESS_TOKEN' => $data['ads_access_token'] ?? '',
            'META_AD_ACCOUNT_ID' => $data['selected_ad_account_id'] ?? '',
            'META_APP_ID' => $data['app_id'] ?? '',
            'META_APP_SECRET' => $data['app_secret'] ?? '',
            'META_FACEBOOK_VERIFY_TOKEN' => $data['verify_token'],
            'META_API_VERSION' => $data['api_version'],
        ]);

        $this->syncRuntimeConfig([
            'services.meta_facebook.page_id' => $data['facebook_page_id'] ?? '',
            'services.meta_facebook.page_access_token' => $data['facebook_page_access_token'] ?? '',
            'services.meta_facebook.instagram_access_token' => $data['instagram_access_token'] ?? '',
            'services.meta_facebook.ads_access_token' => $data['ads_access_token'] ?? '',
            'services.meta_facebook.ad_account_id' => $data['selected_ad_account_id'] ?? '',
            'services.meta_facebook.app_id' => $data['app_id'] ?? '',
            'services.meta_facebook.verify_token' => $data['verify_token'],
            'services.meta_facebook.api_version' => $data['api_version'],
            'services.meta_app_secret' => $data['app_secret'] ?? '',
        ]);

        return $this->getSettings();
    }

    public function updateWhatsapp(array $data): array
    {
        $this->writeEnvValues([
            'META_WHATSAPP_ACCESS_TOKEN' => $data['access_token'] ?? '',
            'META_PHONE_NUMBER_ID' => $data['phone_number_id'] ?? '',
            'META_WABA_ID' => $data['waba_id'] ?? '',
            'META_WHATSAPP_VERIFY_TOKEN' => $data['verify_token'],
            'META_API_VERSION' => $data['api_version'],
        ]);

        $this->syncRuntimeConfig([
            'services.meta_whatsapp.access_token' => $data['access_token'] ?? '',
            'services.meta_whatsapp.phone_number_id' => $data['phone_number_id'] ?? '',
            'services.meta_whatsapp.waba_id' => $data['waba_id'] ?? '',
            'services.meta_whatsapp.verify_token' => $data['verify_token'],
            'services.meta_whatsapp.api_version' => $data['api_version'],
            'services.meta_facebook.api_version' => $data['api_version'],
        ]);

        return $this->getSettings();
    }

    protected function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');
        $contents = File::exists($envPath) ? File::get($envPath) : '';

        foreach ($values as $key => $value) {
            $formatted = $this->formatEnvLine($key, $value);
            $pattern = "/^{$key}=.*$/m";

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $formatted, $contents) ?? $contents;
                continue;
            }

            $contents .= rtrim($contents) === '' ? $formatted : PHP_EOL . $formatted;
        }

        File::put($envPath, rtrim($contents) . PHP_EOL);
    }

    protected function formatEnvLine(string $key, mixed $value): string
    {
        $stringValue = (string) ($value ?? '');
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $stringValue);

        return sprintf('%s="%s"', $key, $escaped);
    }

    protected function syncRuntimeConfig(array $values): void
    {
        foreach ($values as $key => $value) {
            Config::set($key, $value);
        }

        Artisan::call('config:clear');
    }

    protected function resolveAdAccounts(): array
    {
        $token = (string) config('services.meta_facebook.ads_access_token', '');

        if (blank($token)) {
            return [];
        }

        try {
            return collect($this->metaAdsService->listAdAccounts($token))
                ->map(function (array $account) {
                    $id = (string) ($account['account_id'] ?? $account['id'] ?? '');

                    return [
                        'id' => $id,
                        'name' => $account['name'] ?? ('Ad Account ' . $id),
                        'currency' => $account['currency'] ?? null,
                        'account_status' => $account['account_status'] ?? null,
                    ];
                })
                ->filter(fn (array $account) => filled($account['id']))
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
