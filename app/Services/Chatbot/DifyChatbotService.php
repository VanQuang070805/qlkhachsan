<?php

namespace App\Services\Chatbot;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DifyChatbotService
{
    public function ask(Request $request, string $message): ?array
    {
        $baseUrl = rtrim((string) config('services.dify.base_url'), '/');
        $apiKey = (string) config('services.dify.api_key');

        if ($baseUrl === '' || $apiKey === '') {
            return null;
        }

        $customerId = (int) $request->session()->get('customer_user_id', 0);
        $staffContext = (int) $request->session()->get('staff_user_id', 0) > 0 && $customerId === 0;
        $user = 'royal_'.substr(hash_hmac(
            'sha256',
            $request->session()->getId().'|'.($staffContext ? 'staff-session' : 'customer-'.$customerId),
            (string) config('app.key'),
        ), 0, 40);
        $conversationKey = 'royal_dify_conversation_'.substr(hash('sha256', $user), 0, 32);

        try {
            $payload = [
                'inputs' => new \stdClass(),
                'query' => $message,
                'response_mode' => 'blocking',
                'user' => $user,
            ];
            if ($conversationId = $request->session()->get($conversationKey)) {
                $payload['conversation_id'] = $conversationId;
            }

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(max(5, (int) config('services.dify.timeout', 90)))
                ->post($baseUrl.'/chat-messages', $payload);

            if (! $response->successful()) {
                Log::warning('Royal Dify request failed', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();
            $answer = str_replace('**', '', trim((string) data_get($data, 'answer', '')));
            if ($answer === '') {
                return null;
            }

            $nextConversationId = data_get($data, 'conversation_id');
            if (is_string($nextConversationId) && $nextConversationId !== '') {
                $request->session()->put($conversationKey, $nextConversationId);
            }

            return ['reply' => $answer];
        } catch (\Throwable $exception) {
            Log::warning('Royal Dify unavailable', ['type' => $exception::class]);
            return null;
        }
    }
}
