<?php

namespace Modules\CRM\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaAdsService
{
    public function listAdAccounts(?string $accessToken = null, ?string $apiVersion = null): array
    {
        $token = $this->resolveAccessToken($accessToken);
        $version = $apiVersion ?: $this->apiVersion();

        return $this->collectPaginated(
            "https://graph.facebook.com/{$version}/me/adaccounts",
            $token,
            [
                'fields' => 'id,account_id,name,account_status,currency',
                'limit' => 200,
            ],
        );
    }

    public function listCampaigns(string $adAccountId, ?string $accessToken = null, ?string $apiVersion = null): array
    {
        $token = $this->resolveAccessToken($accessToken);
        $version = $apiVersion ?: $this->apiVersion();
        $normalizedId = $this->normalizeAdAccountId($adAccountId);

        $campaigns = $this->collectPaginated(
            "https://graph.facebook.com/{$version}/act_{$normalizedId}/campaigns",
            $token,
            [
                'fields' => 'id,name,status,objective,start_time,stop_time,daily_budget,lifetime_budget',
                'limit' => 200,
            ],
        );

        return array_map(function (array $campaign) use ($token, $version) {
            $campaignId = (string) ($campaign['id'] ?? '');

            return array_merge($campaign, [
                'insights' => $campaignId !== '' ? $this->campaignInsights($campaignId, $token, $version) : [],
                'adsets' => $campaignId !== '' ? $this->campaignAdSets($campaignId, $token, $version) : [],
            ]);
        }, $campaigns);
    }

    protected function collectPaginated(string $url, string $token, array $query): array
    {
        $results = [];
        $nextUrl = $url;
        $nextQuery = $query;

        while ($nextUrl) {
            $response = Http::withToken($token)->acceptJson()->get($nextUrl, $nextQuery);

            if ($response->failed()) {
                throw new RuntimeException($response->json('error.message') ?: 'Meta Ads API request failed.');
            }

            $payload = $response->json();
            $results = array_merge($results, $payload['data'] ?? []);
            $nextUrl = $payload['paging']['next'] ?? null;
            $nextQuery = [];
        }

        return $results;
    }

    protected function campaignInsights(string $campaignId, string $token, string $version): array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/{$campaignId}/insights", [
                'fields' => 'spend,impressions,clicks,ctr,cpc,actions,cost_per_action_type',
                'date_preset' => 'maximum',
                'limit' => 1,
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json('data.0', []) ?: [];
    }

    protected function campaignAdSets(string $campaignId, string $token, string $version): array
    {
        return $this->collectPaginated(
            "https://graph.facebook.com/{$version}/{$campaignId}/adsets",
            $token,
            [
                'fields' => 'id,name,status,optimization_goal,daily_budget,lifetime_budget',
                'limit' => 200,
            ],
        );
    }

    protected function resolveAccessToken(?string $token = null): string
    {
        $resolved = (string) ($token ?: config('services.meta_facebook.ads_access_token'));

        if (blank($resolved)) {
            throw new RuntimeException('Meta Ads access token is not configured.');
        }

        return $resolved;
    }

    protected function apiVersion(): string
    {
        return (string) config('services.meta_facebook.api_version', 'v20.0');
    }

    protected function normalizeAdAccountId(string $adAccountId): string
    {
        return preg_replace('/^act_/i', '', trim($adAccountId)) ?: trim($adAccountId);
    }
}
