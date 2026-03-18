<?php

namespace App\Http\Procedures;

class FusionLiveXmlElement
{
    private \SimpleXMLElement $xml;
    private array $fields = [];

    public static function attr(\SimpleXMLElement $node, string $name): ?string
    {
        $attrs = $node->attributes();
        if (!$attrs || !isset($attrs[$name])) return null;
        return (string)$attrs[$name];
    }

    public function __construct(\SimpleXMLElement $xml, array $fields)
    {
        $this->xml = $xml;
        $this->fields = $fields;
    }

    /**
     * @return mixed Retourne soit un scalaire, soit un tableau de \SimpleXMLElement (si many=true).
     */
    public function __get(string $name): mixed
    {
        $def = $this->fields[$name] ?? null;

        if ($def === null) {
            return null;
        }

        // string => scalaire (1er match)
        if (is_string($def)) {
            $nodes = $this->xml->xpath($def);
            return $nodes[0] ?? null;
        }

        // array => config
        if (is_array($def)) {

            $xpath = $def['xpath'] ?? null;

            if (!is_string($xpath) || $xpath === '') {
                return null;
            }

            $nodes = $this->xml->xpath($xpath);
            $nodes = is_array($nodes) ? $nodes : [];

            if (!empty($def['many'])) {
                return $nodes; // liste
            }
            return $nodes[0] ?? null; // scalaire
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->xml->asXML();
    }
}