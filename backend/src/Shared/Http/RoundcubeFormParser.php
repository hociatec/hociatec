<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class RoundcubeFormParser
{
    public function inputValue(string $html, string $name): ?string
    {
        $node = $this->firstNode($this->xpath($html), sprintf('//input[@name="%s"]', $name));

        return $node instanceof \DOMElement && '' !== $node->getAttribute('value')
            ? $node->getAttribute('value')
            : null;
    }

    public function selectedOptionValue(string $html, string $selectName): ?string
    {
        $xpath = $this->xpath($html);
        $node = $this->firstNode($xpath, sprintf('//select[@name="%s"]/option[@selected]', $selectName));
        if (!$node instanceof \DOMElement) {
            $node = $this->firstNode($xpath, sprintf('//select[@name="%s"]/option', $selectName));
        }

        return $node instanceof \DOMElement && '' !== $node->getAttribute('value')
            ? $node->getAttribute('value')
            : null;
    }

    private function xpath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        return new \DOMXPath($dom);
    }

    private function firstNode(\DOMXPath $xpath, string $query): ?\DOMNode
    {
        $nodes = $xpath->query($query);

        $node = false !== $nodes ? $nodes->item(0) : null;

        return $node instanceof \DOMNode ? $node : null;
    }
}
