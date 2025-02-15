<?php
// https://github.com/wiki-connect/ParseWiki
namespace Publish\TextFix\Citation;

/*

Usage:

use function Publish\TextFix\Citation\getCitations;

*/


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
        // preg_match_all("/<ref\s*([^>\/]*)>(.*?)<\/ref>|<ref\s*([^>\/]*)\/>/is", $string, $matches);  // coderabbitai
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
