<?php

namespace App\Services\Mail;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

final class EmailTemplateRenderer
{
    public function __construct(
        private readonly EmailTemplateSanitizer $sanitizer,
        private readonly CssToInlineStyles $inliner,
    ) {
    }

    public function render(EmailTemplate $template, array $variables = []): array
    {
        return $this->renderPayload((string) $template->subject, $template->html_content, $template->text_content, $template->css_styles, $variables);
    }

    public function renderVersion(EmailTemplateVersion $version, array $variables = []): array
    {
        return $this->renderPayload((string) $version->subject, $version->html_content, $version->text_content, $version->css_styles, $variables);
    }

    /**
     * @param array|string|null $cssStyles
     * @return array{subject:string,html:?string,text:?string,unknown_variables:list<string>}
     */
    public function renderPayload(
        string $subject,
        ?string $html,
        ?string $text,
        array|string|null $cssStyles,
        array $variables = [],
    ): array {
        $definitions = EmailTemplateVariableRegistry::definitions();
        $unknown = [];

        $normalizeLegacy = static function (?string $content) use ($definitions): ?string {
            if ($content === null || $content === '') {
                return $content;
            }

            $legacy = array_keys(array_filter(
                $definitions,
                static fn (array $definition, string $key): bool => ! str_contains($key, '.'),
                ARRAY_FILTER_USE_BOTH,
            ));

            if ($legacy === []) {
                return $content;
            }

            $pattern = '/(?<!\{)\{('.implode('|', array_map(static fn (string $key): string => preg_quote($key, '/'), $legacy)).')\}(?!\})/u';

            return preg_replace_callback(
                $pattern,
                static fn (array $match): string => '{{'.$match[1].'}}',
                $content,
            ) ?? $content;
        };

        $resolve = static function (string $name) use ($variables, $definitions, &$unknown): mixed {
            if (array_key_exists($name, $variables)) {
                return $variables[$name];
            }

            $value = data_get($variables, $name, null);
            if ($value !== null) {
                return $value;
            }

            if (array_key_exists($name, $definitions) && array_key_exists('preview', $definitions[$name])) {
                // Preview defaults are only supplied explicitly by the editor.
                // Production rendering must never invent a missing value.
                $unknown[] = $name;
                return '';
            }

            $unknown[] = $name;
            return '';
        };

        $replace = static function (?string $content, bool $escapeHtml) use ($resolve, $normalizeLegacy): ?string {
            $content = $normalizeLegacy($content);
            if ($content === null || $content === '') {
                return $content;
            }

            return preg_replace_callback(
                '/{{\s*([A-Za-z0-9_.-]+)\s*}}/u',
                static function (array $match) use ($resolve, $escapeHtml): string {
                    $value = $resolve($match[1]);
                    $text = is_scalar($value) || $value === null
                        ? (string) $value
                        : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    return $escapeHtml ? e($text) : $text;
                },
                $content,
            ) ?? $content;
        };

        $renderedSubject = strip_tags($replace($subject, false) ?? '');
        $renderedText = $replace($text, false);

        if ($html === null || trim($html) === '') {
            return [
                'subject' => $renderedSubject,
                'html' => null,
                'text' => $renderedText,
                'unknown_variables' => array_values(array_unique($unknown)),
            ];
        }

        $safeHtml = $this->sanitizer->sanitize((string) $replace($html, true));
        $css = is_array($cssStyles)
            ? implode("\n", array_map(static fn (mixed $value): string => (string) $value, $cssStyles))
            : (string) ($cssStyles ?? '');
        $safeCss = $this->sanitizer->sanitizeCss($css);

        $document = '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>'.$safeCss.'</style></head><body style="margin:0;padding:0;background:#f5f7f6;"><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;width:100%;margin:0;padding:0;"><tr><td align="center" dir="rtl" style="padding:24px 12px;"><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:680px;margin:0 auto;border-collapse:collapse;"><tr><td style="font-family:Arial,Helvetica,"Segoe UI",sans-serif;font-size:16px;line-height:1.7;direction:rtl;text-align:right;">'.$safeHtml.'</td></tr></table></td></tr></table></body></html>';

        try {
            $inlined = $this->inliner->convert($document, $safeCss);
        } catch (\Throwable) {
            $inlined = $document;
        }

        $finalHtml = $this->sanitizer->sanitize($inlined);

        return [
            'subject' => $renderedSubject,
            'html' => $finalHtml,
            'text' => $renderedText ?? trim(strip_tags($finalHtml)),
            'unknown_variables' => array_values(array_unique($unknown)),
        ];
    }
}
