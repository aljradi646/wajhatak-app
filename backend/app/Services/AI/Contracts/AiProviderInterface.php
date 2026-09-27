<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\AiHealthStatus;
use App\Services\AI\AiResponse;

/**
 * عقد موحد لأي محرك استدلال — أي تطبيق (GLM محلي، Ollama، vLLM، llama.cpp،
 * أو محرك مستقبلي) يُستبدل دون تغيير أي منطق في التطبيق.
 *
 * ملاحظة أمنية: التطبيق لا يعتمد على أي مزود محدد؛ التوجيه يتم عبر
 * config('ai.default_provider') وإعدادات لوحة التحكم.
 */
interface AiProviderInterface
{
    /**
     * حوار حر (يُستخدم لتوليد الرد النهائي للمستخدم).
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array{temperature?: float, max_tokens?: int, timeout?: int}  $options
     */
    public function chat(array $messages, array $options = []): AiResponse;

    /**
     * استدعاء مُهيكل مع مخطط JSON — يُستخدم لتحليل نية المستخدم.
     * يُشترط أن يعيد التطبيق مصفوفة مرتبطة صالحة وفق المخطط.
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $schema
     * @param  array{temperature?: float, max_tokens?: int, timeout?: int}  $options
     * @return array<string, mixed>
     */
    public function structured(array $messages, array $schema, array $options = []): array;

    /** فحص صحة خادم الاستدلال (يُستخدم من لوحة التحكم ومراقبة الإنتاج). */
    public function health(): AiHealthStatus;
}
