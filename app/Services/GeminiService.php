<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class GeminiService {

public function generate(string $prompt){
    $apiKey = config('services.gemini.key');
    $model = config('services.gemini.model');

    if (empty($apiKey) || empty($model)) {
            throw new RuntimeException(
                'Gemini configuration is missing.'
            );
        }
    $response = Http::acceptJson()
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
            ->timeout(40)
            ->retry(
                [1000, 2000, 4000],
                fn ($exception) =>
                    $exception instanceof RequestException
                    && $exception->response->status() === 503,
                throw: false
            )
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ]
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gemini API Error: ' .
                $response->status() . ' - ' .
                $response->json('error.message', 'Unknown error')
            );
        }

        $parts = $response->json(
            'candidates.0.content.parts',
            []
        );

        $text = collect($parts)
            ->pluck('text')
            ->filter(fn ($text) => is_string($text) && $text !== '')
            ->implode("\n");

        if ($text === '') {
            throw new RuntimeException(
                'Gemini returned an empty response.'
            );
        }

        return $text;
    }
}