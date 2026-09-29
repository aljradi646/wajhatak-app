<?php

/**
 * بدائل خفيفة (Stubs) لتشغيل محرك المساعد الحتمي بلا Laravel ولا vendor.
 *
 * تُستخدم فقط من scripts/ai_selftest/run.php لاختبار منطق المحرك نفسه
 * (الحواجز + تحليل النية + محرك الردود) على أي جهاز أو استضافة.
 * لا تُحمَّل أبدًا في مسار التطبيق الحقيقي.
 */

namespace Illuminate\Support {
    if (! class_exists(Str::class)) {
        class Str
        {
            /** بديل مصغّر عن Str::contains — يطابق التوقيع المستخدم في المحرك. */
            public static function contains(string|array $haystack, string|array $needles, bool $ignoreCase = false): bool
            {
                $needles = (array) $needles;

                foreach ($needles as $needle) {
                    if ($needle === '') {
                        continue;
                    }

                    $found = $ignoreCase
                        ? mb_stripos((string) $haystack, (string) $needle) !== false
                        : mb_strpos((string) $haystack, (string) $needle) !== false;

                    if ($found) {
                        return true;
                    }
                }

                return false;
            }
        }
    }
}

namespace {
    if (! function_exists('collect')) {
        /**
         * بديل مصغّر عن collect() لجزء المحرك الذي يحتاجه فعلًا
         * (filter / unique / implode / count) بلا Laravel.
         */
        class SelftestCollection implements Countable
        {
            public function __construct(private array $items = []) {}

            public function filter(?callable $callback = null): self
            {
                return new self(array_values($callback ? array_filter($this->items, $callback) : array_filter($this->items)));
            }

            public function unique(): self
            {
                return new self(array_values(array_unique($this->items)));
            }

            public function values(): self
            {
                return new self(array_values($this->items));
            }

            public function map(callable $callback): self
            {
                return new self(array_map($callback, $this->items));
            }

            public function implode(string $glue = ''): string
            {
                return implode($glue, array_map(static fn ($v) => (string) $v, $this->items));
            }

            public function slice(int $offset, ?int $length = null): self
            {
                return new self(array_slice($this->items, $offset, $length));
            }

            public function all(): array
            {
                return $this->items;
            }

            public function toArray(): array
            {
                return $this->items;
            }

            public function isEmpty(): bool
            {
                return $this->items === [];
            }

            public function count(): int
            {
                return count($this->items);
            }
        }

        function collect(mixed $items = []): SelftestCollection
        {
            if ($items instanceof SelftestCollection) {
                return $items;
            }

            return new SelftestCollection(is_array($items) ? array_values($items) : (array) $items);
        }
    }
}

namespace App\Services\AI {
    if (! class_exists(AiSettingsService::class, false)) {
        /** إعدادات ثابتة للاختبار (نفس مفاتيح AiSettingsService الحقيقية). */
        class AiSettingsService
        {
            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function enabled(): bool
            {
                return true;
            }

            public function assistantName(): string
            {
                return 'مساعد وجهتك';
            }

            public function guardActive(string $guard): bool
            {
                return true;
            }
        }
    }

    if (! class_exists(AiPropertySearchService::class, false)) {
        /** بحث وهمي مُتحكَّم به — يُرجع صفوفًا حقيقية الشكل بلا قاعدة بيانات. */
        class AiPropertySearchService
        {
            /** @var list<array<string, mixed>> */
            public array $items = [];

            public array $locations = [];

            public ?array $detailsRow = null;

            public function search(array $filters, ?int $limit = null): array
            {
                return ['items' => $this->items, 'total' => count($this->items)];
            }

            public function details(int $propertyId): ?array
            {
                if ($this->detailsRow !== null && (int) ($this->detailsRow['property_id'] ?? 0) === $propertyId) {
                    return $this->detailsRow;
                }

                foreach ($this->items as $item) {
                    if ((int) ($item['property_id'] ?? 0) === $propertyId) {
                        return $item;
                    }
                }

                return null;
            }

            public function findLocations(string $term, int $limit = 8): array
            {
                return array_slice($this->locations, 0, $limit);
            }

            public function similar(int $propertyId, int $limit = 4): array
            {
                return array_slice($this->items, 0, $limit);
            }
        }
    }
}
