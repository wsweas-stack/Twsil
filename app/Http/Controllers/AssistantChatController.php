<?php

namespace App\Http\Controllers;

use App\Exceptions\AssistantNotConfiguredException;
use App\Exceptions\AssistantUnavailableException;
use App\Http\Requests\AssistantChatRequest;
use App\Services\OpenAiAssistantService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssistantChatController extends Controller
{
    public function __invoke(AssistantChatRequest $request, OpenAiAssistantService $assistant): JsonResponse
    {
        $historyKey = 'assistant.conversation.' . ($request->user()?->getAuthIdentifier() ?? 'guest');
        $history = $request->session()->get($historyKey, []);

        try {
            $reply = $assistant->reply(
                $request->user(),
                $request->validated('message'),
                $history,
            );
        } catch (AssistantNotConfiguredException $exception) {
            Log::warning('AI assistant provider is not configured.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'code' => 'assistant_not_configured',
                'message' => __('app.assistant.not_configured'),
            ], 503);
        } catch (AssistantUnavailableException $exception) {
            $previous = $exception->getPrevious();
            $context = [
                'exception' => $exception::class,
                'provider_exception' => $previous === null ? null : $previous::class,
            ];

            if ($previous instanceof RequestException) {
                $context['provider_status'] = $previous->response->status();
                $context['provider_url'] = (string) $previous->response->effectiveUri();
                $context['provider_body'] = Str::limit($previous->response->body(), 500);
            }

            if ($previous instanceof ConnectionException
                && preg_match('/^cURL error \d+:\s*(.+?)(?:\s+\(see|\s+for https?:\/\/|$)/i', $previous->getMessage(), $matches) === 1) {
                $context['transport_reason'] = Str::limit($matches[1], 150);
            }

            Log::warning('AI assistant provider request failed.', $context);

            return response()->json([
                'message' => __('app.assistant.unavailable'),
            ], 503);
        }

        $request->session()->put($historyKey, array_slice([
            ...$history,
            ['role' => 'user', 'content' => $request->validated('message')],
            ['role' => 'assistant', 'content' => $reply],
        ], -8));

        return response()->json(['reply' => $reply]);
    }
}
