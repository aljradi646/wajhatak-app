<?php

namespace App\Services\Mail;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class EmailTemplateSanitizer
{
    /** @var list<string> */
    private const BLOCKED_TAGS = [
        'script', 'iframe', 'object', 'embed', 'applet', 'form', 'base',
        'frameset', 'frame', 'audio', 'video', 'source', 'track', 'meta',
    ];

    /** @var list<string> */
    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'cite', 'poster'];

    public function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $dom->loadHTML(
                '<?xml encoding="UTF-8"><!doctype html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );

            $xpath = new DOMXPath($dom);

            foreach (self::BLOCKED_TAGS as $tag) {
                foreach ($xpath->query('//'.$tag) ?: [] as $node) {
                    $node->parentNode?->removeChild($node);
                }
            }

            foreach ($xpath->query('//*') ?: [] as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $remove = [];
                foreach ($node->attributes ?: [] as $attribute) {
                    $name = strtolower($attribute->name);
                    $value = trim($attribute->value);

                    if (
                        str_starts_with($name, 'on')
                        || in_array($name, ['srcdoc', 'formaction', 'integrity'], true)
                    ) {
                        $remove[] = $attribute->name;
                        continue;
                    }

                    if (in_array($name, self::URL_ATTRIBUTES, true) && ! $this->isSafeUrl($value, $name)) {
                        $remove[] = $attribute->name;
                        continue;
                    }

                    if ($name === 'style') {
                        $node->setAttribute('style', $this->sanitizeCss($value));
                    }
                }

                foreach ($remove as $name) {
                    $node->removeAttribute($name);
                }

                if (strtolower($node->tagName) === 'img' && ! $node->hasAttribute('alt')) {
                    $node->setAttribute('alt', '');
                }
            }

            $body = $xpath->query('//body')->item(0);
            $output = '';
            foreach ($body?->childNodes ?? [] as $child) {
                $output .= $dom->saveHTML($child);
            }

            return $output;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function sanitizeCss(string $css): string
    {
        $css = preg_replace('/@import\s+[^;]+;?/iu', '', $css) ?? '';
        $css = preg_replace('/expression\s*\(/iu', '', $css) ?? '';
        $css = preg_replace('/(?:javascript|vbscript)\s*:/iu', '', $css) ?? '';
        $css = preg_replace('/-moz-binding\s*:[^;]+;?/iu', '', $css) ?? '';
        $css = preg_replace('/behavior\s*:[^;]+;?/iu', '', $css) ?? '';

        return trim($css);
    }

    private function isSafeUrl(string $url, string $attribute): bool
    {
        if ($url === '') {
            return $attribute !== 'src';
        }

        if (preg_match('/^https?:\/\//iu', $url) === 1) {
            return true;
        }

        if (in_array($attribute, ['href', 'cite'], true) && preg_match('/^(?:mailto|tel):/iu', $url) === 1) {
            return true;
        }

        if ($attribute === 'src' && preg_match('/^data:image\/(?:png|jpe?g|gif|webp);base64,[a-z0-9+\/]+=*$/iu', $url) === 1) {
            return true;
        }

        return false;
    }
}
