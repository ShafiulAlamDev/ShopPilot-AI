<?php

namespace App\Services\AI;

use App\Models\AiUsageLog;

class AiUsageLogger
{
    // Approximate costs per 1K tokens (USD) — update as pricing changes
    private const COSTS_PER_1K = [
        'gpt-4o'                 => ['input' => 0.0025,  'output' => 0.010],
        'gpt-4o-mini'            => ['input' => 0.00015, 'output' => 0.0006],
        'text-embedding-3-small' => ['input' => 0.00002, 'output' => 0.0],
        'text-embedding-3-large' => ['input' => 0.00013, 'output' => 0.0],
    ];

    /**
     * Log a successful AI usage event.
     */
    public function logSuccess(
        int    $organizationId,
        string $feature,
        string $model,
        int    $promptTokens,
        int    $completionTokens,
        int    $durationMs,
        ?int   $storeId = null,
        ?int   $userId  = null,
    ): AiUsageLog {
        $totalTokens = $promptTokens + $completionTokens;
        $costUsd     = $this->estimateCost($model, $promptTokens, $completionTokens);

        return AiUsageLog::create([
            'organization_id'   => $organizationId,
            'store_id'          => $storeId,
            'user_id'           => $userId,
            'feature'           => $feature,
            'model'             => $model,
            'prompt_tokens'     => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens'      => $totalTokens,
            'cost_usd'          => $costUsd,
            'duration_ms'       => $durationMs,
            'status'            => 'success',
        ]);
    }

    /**
     * Log a failed AI usage event.
     */
    public function logError(
        int    $organizationId,
        string $feature,
        string $model,
        string $errorMessage,
        ?int   $storeId = null,
        ?int   $userId  = null,
    ): AiUsageLog {
        return AiUsageLog::create([
            'organization_id'   => $organizationId,
            'store_id'          => $storeId,
            'user_id'           => $userId,
            'feature'           => $feature,
            'model'             => $model,
            'prompt_tokens'     => 0,
            'completion_tokens' => 0,
            'total_tokens'      => 0,
            'status'            => 'error',
            'error_message'     => $errorMessage,
        ]);
    }

    /**
     * Estimate the USD cost for a given model and token counts.
     */
    private function estimateCost(string $model, int $promptTokens, int $completionTokens): ?float
    {
        $pricing = self::COSTS_PER_1K[$model] ?? null;

        if (!$pricing) {
            return null;
        }

        return round(
            ($promptTokens / 1000 * $pricing['input']) +
            ($completionTokens / 1000 * $pricing['output']),
            6
        );
    }
}
