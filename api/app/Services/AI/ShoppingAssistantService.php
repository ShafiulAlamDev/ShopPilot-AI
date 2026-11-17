<?php

namespace App\Services\AI;

use App\Models\Store;

class ShoppingAssistantService
{
    public function __construct(
        private readonly OpenAiClient  $client,
        private readonly AiUsageLogger $logger,
    ) {}

    /**
     * Process a shopping assistant chat turn.
     *
     * @param  array<array{role: string, content: string}>  $history  Previous messages
     * @param  string  $userMessage  The new user message
     * @return array{reply: string, tokens_used: int}
     */
    public function chat(
        Store  $store,
        array  $history,
        string $userMessage,
        int    $organizationId,
        ?int   $userId = null,
    ): array {
        $model  = config('ai.openai.model', 'gpt-4o');
        $budget = (int) config('ai.token_budgets.shopping_assistant', 4000);

        $systemPrompt = <<<PROMPT
You are a helpful shopping assistant for "{$store->name}", an online store.
Your job is to help customers find products, answer questions about items, and assist with purchase decisions.
- Be friendly, concise, and helpful
- If you don't know specific product details, say so honestly
- Don't make up prices or availability — only use information provided
- Currency: {$store->currency}, Locale: {$store->locale}
PROMPT;

        // Build messages array: system + history (last 10 turns) + new user message
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            array_slice($history, -10),
            [['role' => 'user', 'content' => $userMessage]],
        );

        try {
            $result = $this->client->chat([
                'model'       => $model,
                'max_tokens'  => $budget,
                'messages'    => $messages,
                'temperature' => 0.6,
            ]);

            $response = $result['response'];
            $reply    = trim($response['choices'][0]['message']['content'] ?? '');
            $usage    = $response['usage'] ?? [];

            $this->logger->logSuccess(
                organizationId:   $organizationId,
                feature:          'shopping_assistant',
                model:            $model,
                promptTokens:     $usage['prompt_tokens'] ?? 0,
                completionTokens: $usage['completion_tokens'] ?? 0,
                durationMs:       $result['duration_ms'],
                storeId:          $store->id,
                userId:           $userId,
            );

            return [
                'reply'       => $reply,
                'tokens_used' => $usage['total_tokens'] ?? 0,
            ];
        } catch (\RuntimeException $e) {
            $this->logger->logError(
                organizationId: $organizationId,
                feature:        'shopping_assistant',
                model:          $model,
                errorMessage:   $e->getMessage(),
                storeId:        $store->id,
                userId:         $userId,
            );

            throw $e;
        }
    }
}
