<?php

namespace WpRefs\Bots\es_refs;
/*
usage:

use function WpRefs\Bots\es_refs\mv_es_refs;
*/

use function WikiParse\Template\getTemplates;
use function WikiParse\Reg_Citations\getShortCitations;
use function WikiParse\Citations\getCitations;

function get_refs(string $text): array
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
        $refs[$cite_attrs] = $cite_contents;
        // ---
        // echo_test("\n$cite_attrs\n");
        // ---
        $cite_newtext = "<ref $cite_attrs />";
        // ---
        $new_text = str_replace($cite_text, $cite_newtext, $new_text);
    }
    // ---
    return [
        "refs" => $refs,
        "new_text" => $new_text,
    ];
}

function check_short_refs($line)
{
    // ---
    $shorts = getShortCitations($line);
    // ---
    foreach ($shorts as $short) {
        $line = str_replace($short["tag"], "", $line);
    }
    // ---
    // remove \n+
    $line = preg_replace("/\n+/", "\n", $line);
    // ---
    return $line;
};

function make_line(array $refs): string
{
    $line = "\n";

    foreach ($refs as $name => $ref) {
        $la = '<ref ' . trim($name) . '>' . $ref . '</ref>' . "\n";
        $line .= $la;
    }

    $line = trim($line);

    return $line;
}

function add_line_to_temp($line, $text)
{
    // ---
    $temps_in = getTemplates($text);
    // ---
    // echo_test("lenth temps_in:" . count($temps_in) . "\n");
    // ---
    $new_text = $text;
    // ---
    $temp_already_in = false;
    // ---
    foreach ($temps_in as $temp) {
        // ---
        $name = $temp->getStripName();
        // ---
        // echo_test("\n$name\n");
        // ---
        $old_text_template = $temp->getTemplateText();
        // ---
        if (!in_array(strtolower($name), ["reflist", "listaref"])) {
            continue;
        };
        // ---
        // echo_test("\n$name\n");
        // ---
        $refn_param = $temp->getParameter("refs");
        // ---
        if ($refn_param && !empty($refn_param)) {
            $refn_param = check_short_refs($refn_param);
            // ---
            $line = trim($refn_param) . "\n" . trim($line);
        };
        // ---
        $temp->setParameter("refs", "\n" . trim($line) . "\n");
        // ---
        $temp_already_in = true;
        // ---
        $new_text_str = $temp->toString();
        // ---
        $new_text = str_replace($old_text_template, $new_text_str, $new_text);
        // ---
        break;
    };
    // ---
    if (!$temp_already_in) {
        $section_ref = "\n== Referencias ==\n{{listaref|refs=\n$line\n}}";
        $new_text .= $section_ref;
    }
    // ---
    return $new_text;
}

function mv_es_refs(string $text): string
{
    // ---
    if (empty($text)) {
        // echo_test("text is empty");
        return $text;
    }
    // ---
    $refs = get_refs($text);
    // ---
    $new_lines = make_line($refs['refs']);
    // ---
    $new_text = $refs['new_text'];
    // ---
    $new_text = add_line_to_temp($new_lines, $new_text);
    // ---
    return $new_text;
}
