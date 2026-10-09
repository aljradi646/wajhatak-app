<?php
namespace App\Services\AI;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AiLlmClient
{
    public function configured(): bool
    {
        return (bool) config('ai.llm.enabled') && trim((string) config('ai.llm.base_url')) !== '' && trim((string) config('ai.llm.model')) !== '';
    }

    public function chat(array $messages, array $tools = []): array
    {
        if (! $this->configured()) throw new RuntimeException('LLM provider is not configured.');
        $payload = ['model'=>(string)config('ai.llm.model'),'messages'=>$messages,'temperature'=>(float)config('ai.llm.temperature',0.2),'max_tokens'=>(int)config('ai.llm.max_output_tokens',1200),'stream'=>false];
        if ($tools !== []) {$payload['tools']=$tools;$payload['tool_choice']='auto';}
        $response=$this->client()->post('/chat/completions',$payload);
        if ($response->failed()) {
            $message=$response->json('error.message')??$response->json('message')??'inference request failed';
            throw new RuntimeException('LLM HTTP '.$response->status().': '.mb_substr((string)$message,0,300));
        }
        $data=$response->json(); $message=data_get($data,'choices.0.message');
        if (!is_array($message)) throw new RuntimeException('Invalid LLM response.');
        return ['message'=>$message,'usage'=>(array)($data['usage']??[]),'model'=>(string)($data['model']??config('ai.llm.model'))];
    }

    /**
     * توليد رد نهائي بتدفق SSE من مزود متوافق مع OpenAI.
     * لا تستخدم هذه الطريقة لاتخاذ قرارات الأدوات؛ يُحسم البحث أولًا ثم يُبث الملخص
     * النصي وحده حتى لا تصل مخرجات تخطيط الأدوات أو بيانات داخلية إلى المستخدم.
     *
     * @param callable(string): void|null $onDelta
     * @return array{message: array{role: string, content: string}, usage: array, model: string}
     */
    public function chatStream(array $messages, ?callable $onDelta = null): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('LLM provider is not configured.');
        }

        $payload = [
            'model' => (string) config('ai.llm.model'),
            'messages' => $messages,
            'temperature' => (float) config('ai.llm.temperature', 0.2),
            'max_tokens' => (int) config('ai.llm.max_output_tokens', 1200),
            'stream' => true,
        ];

        $response = $this->client()->withOptions(['stream' => true])->post('/chat/completions', $payload);
        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->json('message') ?? 'inference request failed';
            throw new RuntimeException('LLM HTTP '.$response->status().': '.mb_substr((string) $message, 0, 300));
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $content = '';
        $usage = [];
        $model = (string) config('ai.llm.model');
        $pendingText = '';
        $insideThink = false;

        $emitVisible = static function (string $text) use (&$content, $onDelta): void {
            if ($text === '') {
                return;
            }

            $content .= $text;
            if ($onDelta !== null) {
                $onDelta($text);
            }
        };

        // لا نكشف <think> حتى عندما تصل علاماته على أكثر من chunk.
        $filterText = static function (string $text, bool $flush = false) use (&$pendingText, &$insideThink, $emitVisible): void {
            $pendingText .= $text;

            while ($pendingText !== '') {
                if ($insideThink) {
                    $end = stripos($pendingText, '</think>');
                    if ($end === false) {
                        $pendingText = $flush ? '' : substr($pendingText, -7);
                        break;
                    }

                    $pendingText = substr($pendingText, $end + 8);
                    $insideThink = false;
                    continue;
                }

                $start = stripos($pendingText, '<think>');
                if ($start !== false) {
                    $emitVisible(substr($pendingText, 0, $start));
                    $pendingText = substr($pendingText, $start + 7);
                    $insideThink = true;
                    continue;
                }

                if ($flush) {
                    $emitVisible($pendingText);
                    $pendingText = '';
                    break;
                }

                // احتفظ بآخر ستة أحرف لاحتمال أن تكون بداية وسم <think>.
                $safeLength = strlen($pendingText) - 6;
                if ($safeLength > 0) {
                    $emitVisible(substr($pendingText, 0, $safeLength));
                    $pendingText = substr($pendingText, $safeLength);
                }
                break;
            }
        };

        $consumeLine = static function (string $line) use (&$content, &$usage, &$model, $filterText): bool {
            $line = rtrim($line, "\r");
            if (! str_starts_with($line, 'data:')) {
                return true;
            }

            $data = trim(substr($line, 5));
            if ($data === '[DONE]') {
                return false;
            }

            $event = json_decode($data, true);
            if (! is_array($event)) {
                return true;
            }

            if (is_string($event['model'] ?? null) && $event['model'] !== '') {
                $model = $event['model'];
            }
            if (is_array($event['usage'] ?? null)) {
                $usage = $event['usage'];
            }

            $delta = data_get($event, 'choices.0.delta.content');
            if (is_string($delta) && $delta !== '') {
                $filterText($delta);
            }

            return true;
        };

        $done = false;
        while (! $body->eof() && ! $done) {
            $buffer .= $body->read(1024);

            while (($newline = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newline);
                $buffer = substr($buffer, $newline + 1);
                if (! $consumeLine($line)) {
                    $done = true;
                    break;
                }
            }
        }

        if (! $done && trim($buffer) !== '') {
            $consumeLine($buffer);
        }
        $filterText('', true);

        return [
            'message' => ['role' => 'assistant', 'content' => trim($content)],
            'usage' => $usage,
            'model' => $model,
        ];
    }

    public function health(): array
    {
        $started=microtime(true);
        if (!$this->configured()) return ['configured'=>false,'reachable'=>false,'model'=>(string)config('ai.llm.model',''),'latency_ms'=>0,'error'=>'llm_not_configured'];
        try {
            $response=$this->client()->get('/models');
            return ['configured'=>true,'reachable'=>$response->successful(),'model'=>(string)config('ai.llm.model'),'latency_ms'=>(int)round((microtime(true)-$started)*1000),'error'=>$response->successful()?null:'http_'.$response->status()];
        } catch(Throwable $e) {
            return ['configured'=>true,'reachable'=>false,'model'=>(string)config('ai.llm.model'),'latency_ms'=>(int)round((microtime(true)-$started)*1000),'error'=>class_basename($e)];
        }
    }

    private function client(): PendingRequest
    {
        $request=Http::acceptJson()->contentType('application/json')
            ->connectTimeout((int)config('ai.llm.connect_timeout',5))
            ->timeout((int)config('ai.llm.timeout',45))
            ->retry((int)config('ai.llm.retries',1),(int)config('ai.llm.retry_delay_ms',250),throw:false);
        $key=trim((string)config('ai.llm.api_key'));
        if ($key!=='') $request=$request->withToken($key);
        return $request->baseUrl(rtrim((string)config('ai.llm.base_url'),'/'));
    }
}
