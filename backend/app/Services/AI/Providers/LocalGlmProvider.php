<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiHealthStatus;
use App\Services\AI\AiResponse;
use App\Services\AI\Contracts\AiProviderInterface;
use App\Services\AI\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * مزود الاستدلال المحلي — يتصل بخادم GLM ذاتي الاستضافة عبر OpenAI-compatible
 * API (vLLM / SGLang / llama.cpp / Ollama، وكلها تدعم نفس البروتوكول).
 *
 * يعمل داخل الشبكة الداخلية فقط: لا مفاتيح سحابية ولا بيانات تخرج خارج
 * النظام. عنوان الخادم يُضبط من config('ai.inference') أو لوحة التحكم.
 */
class LocalGlmProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly ?string $apiKey = null,
        private readonly int $timeout = 30,
        private readonly int $connectTimeout = 5,
    ) {}

    public static function fromConfig(array $inference): self
    {
        return new self(
            baseUrl: rtrim((string) $inference['base_url'], '/'),
            model: (string) $inference['model'],
            apiKey: $inference['api_key'] ?? null,
            timeout: (int) ($inference['timeout'] ?? 30),
            connectTimeout: (int) ($inference['connect_timeout'] ?? 5),
        );
    }

    public function chat(array $messages, array $options = []): AiResponse
    {
        $body = $this->post('/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? 0.3),
            'max_tokens' => (int) ($options['max_tokens'] ?? 700),
        ], $options['timeout'] ?? null);

        $response = AiResponse::fromOpenAiCompatible($body);

        if (trim($response->content) === '') {
            throw new AiProviderException('استجابة فارغة من محرك الاستدلال.');
        }

        return $response;
    }

    public function structured(array $messages, array $schema, array $options = []): array
    {
        // instruction صريح بJSON فقط (يعمل مع كل المحركات حتى بدون دعم response_format).
        $messages = [...$messages, [
            'role' => 'system',
            'content' => "أجب بكائن JSON صالح فقط وفق هذا المخطط، دون أي نص آخر قبل أو بعد JSON:\n"
                .json_encode($schema, JSON_UNESCAPED_UNICODE),
        ]];

        $temperature = (float) ($options['temperature'] ?? 0.1);

        try {
            // المحركات التي تدعم response_format تعطي JSON أنظف.
            $body = $this->post('/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => (int) ($options['max_tokens'] ?? 300),
                'response_format' => ['type' => 'json_object'],
            ], $options['timeout'] ?? null);
        } catch (AiProviderException $e) {
            // بعض الخوادم ترفض response_format — نعيد المحاولة بدونه.
            if (str_contains($e->getMessage(), 'response_format')) {
                $body = $this->post('/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => (int) ($options['max_tokens'] ?? 300),
                ], $options['timeout'] ?? null);
            } else {
                throw $e;
            }
        }

        $content = (string) ($body['choices'][0]['message']['content'] ?? '');

        return $this->extractJson($content);
    }

    public function health(): AiHealthStatus
    {
        $started = (int) (microtime(true) * 1000);

        try {
            // /models متاح في جميع الخوادم المتوافقة مع OpenAI ويكشف النماذج المقدمة.
            $response = Http::withHeaders($this->headers())
                ->timeout(min(6, $this->timeout))
                ->connectTimeout($this->connectTimeout)
                ->get($this->baseUrl.'/models');

            $latency = (int) (microtime(true) * 1000) - $started;

            if (! $response->successful()) {
                // HTTP 503 من llama-server = النموذج ما زال يُحمَّل في الذاكرة.
                if ($response->status() === 503) {
                    return new AiHealthStatus(false, 'local_glm', $this->model, $latency,
                        'النموذج قيد التحميل إلى الذاكرة (أول تشغيل). أعد المحاولة بعد قليل.');
                }

                return new AiHealthStatus(false, 'local_glm', $this->model, $latency,
                    'استجابة غير ناجحة من خادم الاستدلال (HTTP '.$response->status().').');
            }

            $models = array_map(
                fn ($m) => (string) ($m['id'] ?? ''),
                $response->json('data') ?? []
            );

            return new AiHealthStatus(true, 'local_glm', $this->model, $latency,
                'خادم الاستدلال يعمل.', ['available_models' => $models]);
        } catch (Throwable $e) {
            $hint = $this->localServiceHint();

            return new AiHealthStatus(false, 'local_glm', $this->model, null,
                'لا يمكن الوصول إلى خادم الاستدلال: '.class_basename($e).$hint);
        }
    }

    /** تلميح تشخيصي عند استخدام الخدمة الداخلية داخل الحاوية. */
    private function localServiceHint(): string
    {
        if (! str_contains((string) $this->baseUrl, '127.0.0.1')) {
            return '';
        }

        $logPath = storage_path('logs/ai-inference.log');
        $modelLog = storage_path('app/ai/models/llama-server.log');

        return ' — افحص: '.basename($logPath).' و'.basename($modelLog)
            .' أو شغّل: php artisan ai:status';
    }

    // ------------------------------------------------------------------

    private function post(string $path, array $payload, ?int $timeoutOverride): array
    {
        $attempt = 0;
        $maxAttempts = 2;

        while (true) {
            $attempt++;
            try {
                $response = Http::withHeaders($this->headers())
                    ->timeout($timeoutOverride ?? $this->timeout)
                    ->connectTimeout($this->connectTimeout)
                    ->post($this->baseUrl.$path, $payload);
            } catch (Throwable $e) {
                if ($attempt < $maxAttempts && str_contains(class_basename($e), 'Connection')) {
                    Sleep::for(300)->milliseconds();
                    continue;
                }
                throw new AiProviderException('تعذر الوصول إلى محرك الاستدلال: '.class_basename($e), 0, $e);
            }

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            throw new AiProviderException(
                'خطأ من محرك الاستدلال (HTTP '.$response->status().'): '.str_limit($response->body(), 180),
                $response->status()
            );
        }
    }

    private function headers(): array
    {
        return array_filter([
            'Authorization' => $this->apiKey ? 'Bearer '.$this->apiKey : null,
            'X-Title' => 'Wajhatak-AI',
        ]);
    }

    /** استخراج أول كائن JSON من نص الرد (يتحمل نصوصًا محيطة). */
    private function extractJson(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $content) ?? $content;

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // التقاط أول {...} متوازن داخل النص.
        $start = strpos($content, '{');
        if ($start !== false) {
            $depth = 0;
            $inString = false;
            $escape = false;
            $length = mb_strlen($content);
            for ($i = $start; $i < $length; $i++) {
                $char = mb_substr($content, $i, 1);
                if ($inString) {
                    if ($escape) {
                        $escape = false;
                    } elseif ($char === '\\') {
                        $escape = true;
                    } elseif ($char === '"') {
                        $inString = false;
                    }
                    continue;
                }
                if ($char === '"') {
                    $inString = true;
                } elseif ($char === '{') {
                    $depth++;
                } elseif ($char === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $candidate = mb_substr($content, $start, $i - $start + 1);
                        $decoded = json_decode($candidate, true);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                        break;
                    }
                }
            }
        }

        throw new AiProviderException('محرك الاستدلال لم يعيد JSON صالحًا.');
    }
}
