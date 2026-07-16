<?php

namespace Modules\CRM\Services;

use Modules\CRM\Models\Campaign;
use Modules\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(protected MetaAdsService $metaAdsService) {}

    public function getAll(): Collection
    {
        try {
            return Campaign::with('costs')->orderByDesc('updated_at')->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(int $id): Campaign
    {
        try {
            return Campaign::with('costs')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['id' => $id]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    public function create(array $data): Campaign
    {
        try {
            $data['meta_source'] = $data['meta_source'] ?? 'manual';
            return Campaign::create($data);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        try {
            $campaign->update($data);
            return $campaign->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $campaign->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Campaign $campaign): void
    {
        try {
            $campaign->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $campaign->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getAvailableMetaCampaigns(): array
    {
        $adsToken = (string) config('services.meta_facebook.ads_access_token', '');
        $adAccountId = (string) config('services.meta_facebook.ad_account_id', '');

        if (blank($adsToken) || blank($adAccountId)) {
            return [];
        }

        $existingIds = Campaign::query()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $accountLookup = collect();

        try {
            $accountLookup = collect($this->metaAdsService->listAdAccounts($adsToken))
                ->mapWithKeys(function (array $account) {
                    $id = preg_replace('/^act_/i', '', (string) ($account['account_id'] ?? $account['id'] ?? ''));

                    return $id !== '' ? [$id => ($account['name'] ?? ('Ad Account ' . $id))] : [];
                });
        } catch (\Throwable $e) {
            Log::warning(__METHOD__ . ' unable to decorate Meta campaigns with ad account labels', [
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage(),
            ]);
        }

        return collect($this->metaAdsService->listCampaigns($adAccountId))
            ->map(function (array $campaign) use ($adAccountId, $existingIds, $accountLookup) {
                $budget = $campaign['daily_budget'] ?? $campaign['lifetime_budget'] ?? null;
                $insights = $campaign['insights'] ?? [];
                $resultMetrics = $this->resolveResultMetrics(
                    $insights['actions'] ?? [],
                    $insights['cost_per_action_type'] ?? [],
                );
                $adSets = collect($campaign['adsets'] ?? [])->map(function (array $adSet) {
                    $budget = $adSet['daily_budget'] ?? $adSet['lifetime_budget'] ?? null;

                    return [
                        'id' => (string) ($adSet['id'] ?? ''),
                        'name' => $adSet['name'] ?? 'Unnamed ad set',
                        'status' => isset($adSet['status']) ? strtolower((string) $adSet['status']) : null,
                        'optimization_goal' => $adSet['optimization_goal'] ?? null,
                        'budget' => $budget !== null ? ((float) $budget) / 100 : null,
                    ];
                })->filter(fn (array $adSet) => filled($adSet['id']))->values()->all();
                $normalizedAccountId = preg_replace('/^act_/i', '', $adAccountId);

                return [
                    'id' => (string) ($campaign['id'] ?? ''),
                    'name' => $campaign['name'] ?? 'Unnamed campaign',
                    'ad_account_id' => $normalizedAccountId,
                    'ad_account_name' => $accountLookup->get($normalizedAccountId),
                    'platform' => 'meta_ads',
                    'status' => isset($campaign['status']) ? strtolower((string) $campaign['status']) : null,
                    'objective' => $campaign['objective'] ?? null,
                    'start_date' => $campaign['start_time'] ?? null,
                    'end_date' => $campaign['stop_time'] ?? null,
                    'budget' => $budget !== null ? ((float) $budget) / 100 : null,
                    'currency' => 'EGP',
                    'spend' => isset($insights['spend']) ? (float) $insights['spend'] : null,
                    'impressions' => isset($insights['impressions']) ? (int) $insights['impressions'] : null,
                    'clicks' => isset($insights['clicks']) ? (int) $insights['clicks'] : null,
                    'ctr' => isset($insights['ctr']) ? (float) $insights['ctr'] : null,
                    'cpc' => isset($insights['cpc']) ? (float) $insights['cpc'] : null,
                    'results' => $resultMetrics['value'],
                    'result_label' => $resultMetrics['label'],
                    'ad_sets' => $adSets,
                    'imported' => in_array((string) ($campaign['id'] ?? ''), $existingIds, true),
                ];
            })
            ->filter(fn (array $campaign) => filled($campaign['id']))
            ->values()
            ->all();
    }

    public function importMetaCampaigns(array $campaignIds): Collection
    {
        $available = collect($this->getAvailableMetaCampaigns())->keyBy('id');
        $selected = collect($campaignIds)
            ->map(fn ($id) => (string) $id)
            ->filter(fn ($id) => $available->has($id))
            ->values();

        if ($selected->isEmpty()) {
            throw new \RuntimeException('No valid Meta campaigns were selected for import.');
        }

        return DB::transaction(function () use ($selected, $available) {
            foreach ($selected as $campaignId) {
                $campaign = $available->get($campaignId);

                Campaign::query()->updateOrCreate(
                    ['id' => (int) $campaignId],
                    [
                        'name' => $campaign['name'],
                        'ad_account_id' => $campaign['ad_account_id'],
                        'ad_account_name' => $campaign['ad_account_name'],
                        'platform' => $campaign['platform'],
                        'description' => null,
                        'start_date' => $campaign['start_date'],
                        'end_date' => $campaign['end_date'],
                        'budget' => $campaign['budget'],
                        'currency' => $campaign['currency'] ?: 'EGP',
                        'status' => in_array($campaign['status'], ['draft', 'active', 'paused'], true) ? $campaign['status'] : 'active',
                        'objective' => $campaign['objective'],
                        'meta_source' => 'meta_ads',
                        'spend' => $campaign['spend'],
                        'impressions' => $campaign['impressions'],
                        'clicks' => $campaign['clicks'],
                        'ctr' => $campaign['ctr'],
                        'cpc' => $campaign['cpc'],
                        'results' => $campaign['results'],
                        'result_label' => $campaign['result_label'],
                        'ad_sets' => $campaign['ad_sets'],
                        'metrics_synced_at' => now(),
                    ]
                );
            }

            $this->attachImportedCampaignsToExistingLeads($selected->all());

            return Campaign::with('costs')
                ->whereIn('id', $selected->map(fn ($id) => (int) $id)->all())
                ->get();
        });
    }

    protected function attachImportedCampaignsToExistingLeads(array $campaignIds): void
    {
        $normalized = collect($campaignIds)->map(fn ($id) => (string) $id)->all();

        Lead::query()
            ->whereNull('campaign_id')
            ->get()
            ->each(function (Lead $lead) use ($normalized) {
                $metaCampaignId = (string) data_get($lead->metadata, 'meta_campaign_id', '');

                if ($metaCampaignId !== '' && in_array($metaCampaignId, $normalized, true)) {
                    $lead->update(['campaign_id' => (int) $metaCampaignId]);
                }
            });
    }

    protected function resolveResultMetrics(array $actions, array $costPerActionTypes): array
    {
        $priority = [
            'lead' => 'Leads',
            'onsite_conversion.lead_grouped' => 'Leads',
            'offsite_conversion.fb_pixel_lead' => 'Leads',
            'onsite_web_lead' => 'Leads',
            'messaging_conversation_started_7d' => 'Messages Started',
            'omni_initiated_messaging_conversation' => 'Messages Started',
            'link_click' => 'Link Clicks',
            'landing_page_view' => 'Landing Page Views',
            'purchase' => 'Purchases',
            'offsite_conversion.fb_pixel_purchase' => 'Purchases',
            'complete_registration' => 'Registrations',
            'subscribe' => 'Subscriptions',
            'post_engagement' => 'Post Engagement',
        ];

        foreach ($priority as $actionType => $label) {
            $action = collect($actions)->firstWhere('action_type', $actionType);
            if ($action && isset($action['value'])) {
                return [
                    'label' => $label,
                    'value' => (float) $action['value'],
                ];
            }
        }

        $first = collect($actions)->first();

        return [
            'label' => $first['action_type'] ?? null,
            'value' => isset($first['value']) ? (float) $first['value'] : null,
        ];
    }
}
