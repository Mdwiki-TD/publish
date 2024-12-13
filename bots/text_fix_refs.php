<?php

namespace Publish\TextFix\DelDuplicateRefs;

/*

Usage:

use function Publish\TextFix\DelDuplicateRefs\remove_Duplicate_refs;

*/

// use function WikiParse\Citations\getCitations;

class Citation
{
    private string $template;
    private string $options;
    private string $cite_text;
    public function __construct(string $template, string $options = "", string $cite_text = "")
    {
        $this->template = $template;
        $this->options = $options;
        $this->cite_text = $cite_text;
    }
    public function getCiteText(): string
    {
        return $this->cite_text;
    }
    public function getTemplate(): string
    {
        return $this->template;
    }
    public function getOptions(): string
    {
        return $this->options;
    }
    public function toString(): string
    {
        return "<ref " . trim($this->options) . ">" . $this->template . "</ref>";
    }
}


class ParserCitations
{
    private string $text;
    private array $citations;
    public function __construct(string $text)
    {
        $this->text = $text;
        $this->parse();
    }
    private function find_sub_citations($string)
    {
        preg_match_all("/<ref([^\/>]*?)>(.+?)<\/ref>/is", $string, $matches);
        return $matches;
    }
    public function parse(): void
    {
        $text_citations = $this->find_sub_citations($this->text);
        $this->citations = [];
        foreach ($text_citations[1] as $key => $text_citation) {
            $_Citation = new Citation($text_citations[2][$key], $text_citation, $text_citations[0][$key]);
            $this->citations[] = $_Citation;
        }
    }

    public function getCitations(): array
    {
        return $this->citations;
    }
}


function getCitations($text)
{
    $do = new ParserCitations($text);
    $citations = $do->getCitations();

    return $citations;
}


function get_html_attribute_value(string $text, string $param): string
{
    if (empty($text)) {
        return '';
    }

    // Case-insensitive regular expression for attribute extraction
    $pattern = sprintf('/(?i)%s\s*=\s*[\'"]?(?P<%s>[^\'" >]+)[\'"]?/', $param, $param);
    $match = preg_match($pattern, $text, $matches);

    if ($match) {
        return $matches[$param];
    }

    return '';
}
function remove_Duplicate_refs(string $text): string
{
    // ---
    $new_text = $text;
    // ---
    $refs = [];
    // ---
    $citations = getCitations($text);
    // ---
    $new_text = $text;
    // ---
    $numb = 0;
    // ---
    foreach ($citations as $key => $citation) {
        // ---
        $cite_text = $citation->getCiteText();
        // ---
        $cite_contents = $citation->getTemplate();
        // ---
        $cite_attrs = $citation->getOptions();
        $cite_attrs = $cite_attrs ? trim($cite_attrs) : "";
        // ---
        if (empty($cite_attrs)) {
            $numb += 1;
            $name = "autogen_" . $numb;
            $cite_attrs = "name='$name'";
        }
        // ---
        $cite_newtext = "<ref $cite_attrs />";
        // ---
        // echo "\n$cite_newtext";
        // ---
        if (isset($refs[$cite_attrs])) {
            // ---
            $new_text = str_replace($cite_text, $cite_newtext, $new_text);
        } else {
            $refs[$cite_attrs] = $cite_newtext;
        };
    }
    // ---
    return $new_text;
}
