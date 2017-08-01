<?php

namespace Hal\Renderer;

use DOMDocument;
use DOMNode;
use Hal\HalResource;
use Hal\Exception;

class XmlRenderer implements Renderer
{
    public function render(HalResource $resource) : string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->appendChild($this->createResourceNode($dom, $resource->toArray()));
        return trim($dom->saveXML());
    }

    private function createResourceNode(DOMDocument $doc, array $resource, string $resourceRel = 'self') : DOMNode
    {
        // Normalize resource
        $resource['_links']    = $resource['_links'] ?? [];
        $resource['_embedded'] = $resource['_embedded'] ?? [];

        $node = $doc->createElement('resource');

        // Self-relational link attributes, if present and singular
        $resource = $this->injectSelfRelationalLink($resourceRel, $resource, $node);

        // All other links, including multiple "self" links
        foreach ($resource['_links'] as $rel => $linkData) {
            if ($this->isAssocArray($linkData)) {
                $node->appendChild($this->createLinkNode($doc, $rel, $linkData));
                continue;
            }

            foreach ($linkData as $linkDatum) {
                $node->appendChild($this->createLinkNode($doc, $rel, $linkDatum));
            }
        }
        unset($resource['_links']);

        foreach ($resource['_embedded'] as $rel => $childData) {
            if ($this->isAssocArray($childData)) {
                $node->appendChild($this->createResourceNode($doc, $childData, $rel));
                continue;
            }

            foreach ($childData as $childDatum) {
                $node->appendChild($this->createResourceNode($doc, $childDatum, $rel));
            }
        }
        unset($resource['_embedded']);

        return $this->createNodeTree($doc, $node, $resource);
    }

    private function createLinkNode(DOMDocument $doc, string $rel, array $data)
    {
        $link = $doc->createElement('link');
        $link->setAttribute('rel', $rel);
        foreach ($data as $key => $value) {
            $value = $this->normalizeConstantValue($value);
            $link->setAttribute($key, $value);
        }
        return $link;
    }

    /**
     * Convert true, false, and null to appropriate strings.
     *
     * In all other cases, return the value as-is.
     *
     * @param mixed $value
     * @return string|mixed
     */
    private function normalizeConstantValue($value)
    {
        $value = $value === true ? 'true' : $value;
        $value = $value === false ? 'false' : $value;
        $value = $value === null ? '' : $value;
        return $value;
    }

    private function isAssocArray(array $value) : bool
    {
        return array_values($value) !== $value;
    }

    /**
     * @return DOMNode|DOMNode[]
     */
    private function createResourceElement(DOMDocument $doc, string $name, $data)
    {
        if (is_scalar($data)) {
            $data = $this->normalizeConstantValue($data);
            return $doc->createElement($name, $data);
        }

        if (! is_array($data)) {
            throw Exception\InvalidResourceValueException::fromValue($data);
        }

        if ($this->isAssocArray($data)) {
            return $this->createNodeTree($doc, $doc->createElement($name), $data);
        }

        $elements = [];
        foreach ($value as $child) {
            $elements[] = $this->createResourceElement($doc, $name, $child);
        }
        return $elements;
    }

    private function createNodeTree(DOMDocument $doc, DOMNode $node, array $data) : DOMNode
    {
        foreach ($data as $key => $value) {
            $element = $this->createResourceElement($doc, $key, $value);
            if (! is_array($element)) {
                $node->appendChild($element);
                continue;
            }
            foreach ($element as $child) {
                $node->appendChild($child);
            }
        }

        return $node;
    }

    /**
     * Attempts to inject the "self" relational link into the resource node.
     *
     * Uses the provided `$rel` in place of "self" during injection.
     *
     * Returns an updated $resource, minus the self relational link if it was
     * found and injected.
     */
    private function injectSelfRelationalLink(string $rel, array $resource, DOMNode $node) : array
    {
        // No self link, or multiple self links
        if (! isset($resource['_links']['self'])
            || 1 < count($resource['_links']['self'])
        ) {
            return $resource;
        }

        $link = array_shift($resource['_links']['self']);

        // self link has no href
        if (! isset($link['href'])) {
            $resource['_links']['self'][] = $link;
            return $resource;
        }

        unset($resource['_links']['self']);

        $node->setAttribute('rel', $rel);
        $node->setAttribute('href', $link['href']);
        foreach ($link as $attribute => $value) {
            if ($attribute === 'href') {
                continue;
            }
            $node->setAttribute($attribute, $value);
        }

        return $resource;
    }
}
