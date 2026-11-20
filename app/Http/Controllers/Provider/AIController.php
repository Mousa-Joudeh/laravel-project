<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Throwable;

/**
 * "Write with AI" button on the space / service / package forms.
 * Returns {"text": "..."} for the description field.
 */
class AIController extends Controller
{
    public function describe(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'kind'         => 'required|in:space,service,package',
            'name'         => 'required|string|max:150',
            'city'         => 'nullable|string|max:100',
            'highlights'   => 'nullable|array|max:10',
            'highlights.*' => 'string|max:100',
        ]);

        $subject = ['space' => 'photography/creative studio space', 'service' => 'creative service (such as a photographer, engineer or editor)', 'package' => 'bundled photo-session package'][$data['kind']];

        $prompt = "Write a warm, honest marketing description for a {$subject} listed on a studio-booking website in Jordan.\n"
            . "Name: {$data['name']}\n"
            . (! empty($data['city']) ? "City: {$data['city']}\n" : '')
            . (! empty($data['highlights']) ? 'Highlights: ' . implode(', ', $data['highlights']) . "\n" : '')
            . "Rules: 60 to 90 words, one paragraph, plain text only (no markdown, no emojis, no quotes), "
            . 'do not invent prices, addresses or facts that are not listed above.';

        try {
            $text = trim($gemini->generate($prompt));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'The AI writer is not available right now.'], 503);
        }

        return response()->json(['text' => $text]);
    }
}
