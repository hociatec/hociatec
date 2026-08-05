<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Security;

final class EmailTemplatePreviewSanitizer
{
    /** @var array<string, true> */
    private const ALLOWED_TAGS = [
        'a' => true,
        'b' => true,
        'blockquote' => true,
        'br' => true,
        'code' => true,
        'div' => true,
        'em' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
        'hr' => true,
        'i' => true,
        'img' => true,
        'li' => true,
        'ol' => true,
        'p' => true,
        'pre' => true,
        'small' => true,
        'span' => true,
        'strong' => true,
        'table' => true,
        'tbody' => true,
        'td' => true,
        'tfoot' => true,
        'th' => true,
        'thead' => true,
        'tr' => true,
        'u' => true,
        'ul' => true,
    ];

    /** @var array<string, true> */
    private const REMOVED_TAGS = [
        'button' => true,
        'embed' => true,
        'form' => true,
        'iframe' => true,
        'input' => true,
        'link' => true,
        'meta' => true,
        'object' => true,
        'script' => true,
        'select' => true,
        'style' => true,
        'textarea' => true,
    ];

    /** @var array<string, true> */
    private const ALLOWED_ATTRIBUTES = [
        'abbr' => true,
        'align' => true,
        'alt' => true,
        'aria-label' => true,
        'bgcolor' => true,
        'border' => true,
        'cellpadding' => true,
        'cellspacing' => true,
        'colspan' => true,
        'height' => true,
        'role' => true,
        'rowspan' => true,
        'scope' => true,
        'title' => true,
        'valign' => true,
        'width' => true,
    ];

    public function sanitize(string $html): string
    {
        if ('' === trim($html)) {
            return '';
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!doctype html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>',
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body instanceof \DOMElement) {
            return '';
        }

        $this->sanitizeChildren($body);

        $output = '';
        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child) ?: '';
        }

        return $output;
    }

    private function sanitizeChildren(\DOMNode $node): void
    {
        for ($child = $node->firstChild; null !== $child;) {
            $next = $child->nextSibling;

            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                $child = $next;
                continue;
            }

            if ($child instanceof \DOMElement) {
                $tagName = strtolower($child->tagName);

                if (isset(self::REMOVED_TAGS[$tagName])) {
                    $node->removeChild($child);
                    $child = $next;
                    continue;
                }

                if (!isset(self::ALLOWED_TAGS[$tagName])) {
                    $this->unwrap($child);
                    $child = $next;
                    continue;
                }

                $this->sanitizeAttributes($child);
                $this->sanitizeChildren($child);
            }

            $child = $next;
        }
    }

    private function sanitizeAttributes(\DOMElement $element): void
    {
        for ($index = $element->attributes->length - 1; $index >= 0; --$index) {
            $attribute = $element->attributes->item($index);
            if (!$attribute instanceof \DOMAttr) {
                continue;
            }

            $name = strtolower($attribute->name);
            if (str_starts_with($name, 'on') || str_starts_with($name, 'data-') || !isset(self::ALLOWED_ATTRIBUTES[$name])) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    private function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (!$parent instanceof \DOMNode) {
            return;
        }

        while ($element->firstChild instanceof \DOMNode) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
