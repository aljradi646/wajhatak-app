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
