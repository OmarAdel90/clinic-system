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
        $adAccountId = (string) config('services.meta_facebook.ad_account_id', '');

        if (blank($adAccountId)) {
            throw new \RuntimeException('No Meta ad account is selected in settings.');
        }

        $existingIds = Campaign::query()->pluck('id')->map(fn ($id) => (string) $id)->all();

        return collect($this->metaAdsService->listCampaigns($adAccountId))
            ->map(function (array $campaign) use ($adAccountId, $existingIds) {
                $budget = $campaign['daily_budget'] ?? $campaign['lifetime_budget'] ?? null;

                return [
                    'id' => (string) ($campaign['id'] ?? ''),
                    'name' => $campaign['name'] ?? 'Unnamed campaign',
                    'ad_account_id' => preg_replace('/^act_/i', '', $adAccountId),
                    'platform' => 'meta_ads',
                    'status' => isset($campaign['status']) ? strtolower((string) $campaign['status']) : null,
                    'objective' => $campaign['objective'] ?? null,
                    'start_date' => $campaign['start_time'] ?? null,
                    'end_date' => $campaign['stop_time'] ?? null,
                    'budget' => $budget !== null ? ((float) $budget) / 100 : null,
                    'currency' => 'EGP',
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
                        'platform' => $campaign['platform'],
                        'description' => null,
                        'start_date' => $campaign['start_date'],
                        'end_date' => $campaign['end_date'],
                        'budget' => $campaign['budget'],
                        'currency' => $campaign['currency'] ?: 'EGP',
                        'status' => in_array($campaign['status'], ['draft', 'active', 'paused'], true) ? $campaign['status'] : 'active',
                        'objective' => $campaign['objective'],
                        'meta_source' => 'meta_ads',
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
}
