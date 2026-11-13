<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function generateProductDescription(Request $request)
    {
        // 1. Validate Admin Input
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'features' => 'nullable|string',
            'tone' => 'nullable|string|in:professional,playful,luxury,urgent',
        ]);

        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            Log::error('Gemini API key is missing. Check config/services.php and .env.');

            return response()->json([
                'message' => 'Failed to connect to AI service.',
            ], 500);
        }

        // 2. Construct the AI Prompt
        $prompt = "You are an expert e-commerce SEO copywriter. Write a highly converting, SEO-optimized product description based on the following details. Do not include conversational filler like 'Here is your description', just output the description itself.\n\n";
        $prompt .= 'Product Name: '.$validated['product_name']."\n";

        if (! empty($validated['features'])) {
            $prompt .= 'Key Features/Keywords: '.$validated['features']."\n";
        }

        $tone = $validated['tone'] ?? 'professional';
        $prompt .= 'Tone of Voice: '.$tone."\n";
        $prompt .= 'Format: Return the output in clean, plain text with short paragraphs. Keep it under 150 words.';

        // 3. Try the configured model first, then any configured fallbacks.
        // The Interactions API is used because it is Google's recommended
        // interface and answers reliably when the legacy generateContent
        // endpoint hangs for the mainline models.
        $models = $this->descriptionModels();
        $deadline = now()->addSeconds(25);
        $lastStatus = null;
        $lastError = null;

        foreach ($models as $model) {
            if (now()->greaterThan($deadline)) {
                Log::warning('Gemini description generation ran out of time.', ['model' => $model]);

                break;
            }

            try {
                $response = Http::connectTimeout(5)
                    ->timeout(12)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post(self::API_URL.'/interactions', [
                        'model' => $model,
                        'store' => false,
                        'input' => $prompt,
                    ]);
            } catch (ConnectionException $e) {
                Log::warning('Gemini description generation timed out.', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
                $lastStatus = null;
                $lastError = $e->getMessage();

                continue;
            } catch (\Exception $e) {
                Log::error('Gemini description connection exception: '.$e->getMessage(), ['model' => $model]);
                $lastStatus = null;
                $lastError = $e->getMessage();

                continue;
            }

            if ($response->failed()) {
                $lastStatus = $response->status();
                $lastError = $response->json() ?? $response->body();

                Log::warning('Gemini description generation failed.', [
                    'model' => $model,
                    'status' => $lastStatus,
                    'response' => $lastError,
                ]);

                continue;
            }

            $generatedText = $this->interactionText($response->json());

            if ($generatedText === '') {
                Log::warning('Gemini returned an empty description response.', [
                    'model' => $model,
                    'response' => $response->json(),
                ]);
                $lastStatus = 502;
                $lastError = $response->json();

                continue;
            }

            return response()->json([
                'description' => trim($generatedText),
            ]);
        }

        // 4. Every model failed — report the most useful error we have.
        $busy = $lastStatus === null || in_array($lastStatus, [429, 500, 502, 503, 504], true);
        $message = match (true) {
            $lastStatus === 429 => 'The AI service is temporarily over its rate limit. Please try again in a moment.',
            $busy => 'The AI service is temporarily busy. Please try again in a moment.',
            default => 'Failed to generate a product description.',
        };
        $statusCode = $lastStatus === 429 ? 429 : ($busy ? 503 : ($lastStatus >= 400 ? $lastStatus : 502));

        return response()->json([
            'message' => $message,
            'debug_error' => config('app.debug') ? $lastError : null,
        ], $statusCode);
    }

    /**
     * The configured description model followed by its fallbacks, de-duplicated.
     *
     * @return array<int, string>
     */
    private function descriptionModels(): array
    {
        $fallbacks = config('services.gemini.fallback_models', []);
        $fallbacks = is_array($fallbacks) ? $fallbacks : explode(',', (string) $fallbacks);

        return array_values(array_unique(array_filter(array_map(
            fn ($model) => trim((string) $model),
            array_merge([config('services.gemini.model')], $fallbacks),
        ))));
    }

    /**
     * Pull the model's text out of an Interactions API response.
     */
    private function interactionText(mixed $data): string
    {
        if (! is_array($data)) {
            return '';
        }

        $text = collect($data['steps'] ?? [])
            ->where('type', 'model_output')
            ->flatMap(fn (array $step) => $step['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->filter(fn ($part) => is_string($part) && $part !== '')
            ->join('');

        if ($text !== '') {
            return $text;
        }

        $outputText = $data['output_text'] ?? '';

        return is_string($outputText) ? $outputText : '';
    }

    public function generateProductImage(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            Log::error('Gemini API key is missing. Check config/services.php and .env.');

            return response()->json([
                'message' => 'Failed to connect to AI image service.',
            ], 500);
        }

        $model = config('services.gemini.image_model');
        $prompt = "Generate one photorealistic, polished e-commerce product photograph of: {$validated['product_name']}. "
            .'Show the product clearly as the main subject, centered on a clean, simple studio background with soft natural lighting. '
            .'Do not add text, labels, logos, watermarks, borders, or extra products.';

        if (! empty($validated['description'])) {
            $prompt .= "\nProduct details: {$validated['description']}";
        }

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_URL.'/interactions', [
                    'model' => $model,
                    'store' => false,
                    'input' => [
                        ['type' => 'text', 'text' => $prompt],
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error('Gemini image generation connection failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to connect to Gemini image service.',
                'debug_error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        if ($response->failed()) {
            Log::error('Gemini image generation failed: '.$response->body());

            return response()->json([
                'message' => 'Gemini could not generate a product image.',
                'debug_error' => config('app.debug') ? ($response->json() ?? $response->body()) : null,
            ], $response->status());
        }

        $imageParts = collect($response->json('steps', []))
            ->where('type', 'model_output')
            ->flatMap(fn (array $step) => $step['content'] ?? []);
        $generatedImage = $imageParts->first(fn (array $part) => ($part['type'] ?? null) === 'image')
            ?? $response->json('output_image', []);
        $mimeType = $generatedImage['mime_type'] ?? null;
        $imageData = $generatedImage['data'] ?? null;

        if (
            ! is_string($imageData)
            || ! is_string($mimeType)
            || ! in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp'], true)
        ) {
            Log::warning('Gemini returned no supported generated product image.', [
                'response' => $response->json(),
            ]);

            return response()->json([
                'message' => 'Gemini did not return a supported product image.',
            ], 502);
        }

        return response()->json([
            'mime_type' => $mimeType,
            'image' => $imageData,
        ]);
    }
}
