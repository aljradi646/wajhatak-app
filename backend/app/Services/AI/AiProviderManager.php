<?php

namespace App\Services\AI;

use App\Models\Setting;
use App\Services\AI\Contracts\AiProviderInterface;
use App\Services\AI\Providers\LocalGlmProvider;
use InvalidArgumentException;

/**
 * سجل المزودين — نقطة التبديل الوحيدة بين التطبيق ومحرك الاستدلال.
 * لإضافة محرك جديد: أنشئ كلاسًا يطبق AiProviderInterface وسجّله هنا.
 * لا يعتمد أي ملف آخر في التطبيق على كلاس مزود محدد.
 */
class AiProviderManager
{
    /** @var array<string, callable(): AiProviderInterface> */
    private array $factories = [];

    public function __construct()
    {
        $this->register('local_glm', fn () => LocalGlmProvider::fromConfig($this->inferenceConfig()));
    }

    public function register(string $name, callable $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function available(): array
    {
        return array_keys($this->factories);
    }

    /** المزود المفعّل: من إعدادات لوحة التحكم مع الرجوع إلى config عند الغياب. */
    public function provider(): AiProviderInterface
    {
        $name = (string) Setting::get('ai_provider', config('ai.default_provider', 'local_glm'));

        return $this->resolve($name);
    }

    public function resolve(string $name): AiProviderInterface
    {
        $factory = $this->factories[$name] ?? null;

        if ($factory === null) {
            throw new InvalidArgumentException("مزود AI غير معروف: {$name}");
        }

        return $factory();
    }

    /** إعدادات الاستدلال الفعلية: env أولاً ثم تجاوزات لوحة التحكم. */
    private function inferenceConfig(): array
    {
        $config = (array) config('ai.inference');
        $overrides = [
            'base_url' => Setting::get('ai_inference_endpoint'),
            'model' => Setting::get('ai_model'),
            'api_key' => Setting::get('ai_inference_key'),
            'timeout' => Setting::get('ai_timeout'),
        ];

        foreach ($overrides as $key => $value) {
            if ($value !== null && $value !== '') {
                $config[$key] = $key === 'timeout' ? (int) $value : $value;
            }
        }

        return $config;
    }
}
