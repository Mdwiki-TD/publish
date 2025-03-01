<?php

namespace WpRefs\ES;
/*

usage:

use function WpRefs\ES\fix_es;

*/

use function WpRefs\Bots\es_months\fix_es_months;
use function WpRefs\Bots\es_refs\mv_es_refs;
use function WikiParse\Template\getTemplates;
use function WpRefs\TestBot\echo_test;
// ---
// Mapping templates
$refs_temps = [
    "cite web" => "cita web",
    "cite arxiv" => "cita arxiv",
    "cite certification" => "cita certificación",
    "cite conference" => "cita conferencia",
    "cite encyclopedia" => "cita enciclopedia",
    "cite interview" => "cita entrevista",
    "cite episode" => "cita episodio",
    "cite newsgroup" => "cita grupo de noticias",
    "cite comic" => "cita historieta",
    "cite court" => "cita juicio",
    "cite book" => "cita libro",
    "cite mailing list" => "cita lista de correo",
    "cite map" => "cita mapa",
    "cite av media notes" => "cita notas audiovisual",
    "cite news" => "cita noticia",
    "cite podcast" => "cita podcast",
    "cite journal" => "cita publicación",
    "citation needed" => "cita requerida",
    "cite thesis" => "cita tesis",
    "cite tweet" => "cita tuit",
    "cite av media" => "cita video",
    "cite video game" => "cita videojuego",
];

// ---
// Mapping arguments
$args_to = [
    "title" => "título",
    "website" => "sitioweb",
    "access-date" => "fechaacceso",
    "accessdate" => "fechaacceso",
    "language" => "idioma",
    "archive-url" => "urlarchivo",
    "archiveurl" => "urlarchivo",
    "date" => "fecha",
    "archive-date" => "fechaarchivo",
    "archivedate" => "fechaarchivo",
    "first" => "nombre",
    "last" => "apellidos",
    "first1" => "nombre1",
    "last1" => "apellidos1",
    "last2" => "apellidos2",
    "first2" => "nombre2",
];

// ---
// More mapping with grouped parameters
$params = [
    "nombre1" => ["first1", "given1"],
    "enlaceautor1" => [
        "authorlink1",
        "author1-link",
        "author-link1",
    ],
    "enlaceautor" => [
        "author-link",
        "authorlink",
    ],
    "título" => ["title"],
    "fechaacceso" => ["accessdate"],
    "año" => ["year"],
    "fecha" => ["date"],
    "editorial" => ["publisher"],
    "apellido-editor" => ["editor-last", "editor-surname", "editor1-last"],
    "nombre-editor" => ["editor-first", "editor-given", "editor1-first", "editor1-given"],
    "enlace-editor" => ["editor-link", "editor1-link"],
    "ubicación" => ["place", "location"],
    "lugar-publicación" => ["publication-place"],
    "fecha-publicación" => ["publication-date"],
    "edición" => ["edition"],
    "sined" => ["noed"],
    "volumen" => ["volume"],
    "página" => ["page"],
    "páginas" => ["pages"],
    "en" => ["at"],
    "enlace-pasaje" => ["url-pasaje"],
    "idioma" => ["language"],
    "título-trad" => ["trans_title"],
    "capítulo" => ["chapter"],
    "url-capítulo" => ["url-chapter"],
    "capítulo-trad" => ["trans_chapter"],
    "formato" => ["format"],
    "cita" => ["quote"],
    "separador" => ["separator"],
    "resumen" => ["laysummary", "layurl"],
    "fecha-resumen" => ["laydate"],
    "apellidos1" => [
        "last1",
    ],
    "apellidos2" => [
        "last2",
    ],
    "nombre2" => ["first2", "given2"],
    "enlaceautor2" => ["authorlink2", "author2-link", "authorlink2"],
    "apellidos3" => ["last3", "surname3", "author3"],
    "nombre3" => ["first3", "given3"],
    "enlaceautor3" => ["authorlink3", "author3-link", "authorlink3"],
    "apellidos4" => ["last4", "surname4", "author4"],
    "nombre4" => ["first4", "given4"],
    "enlaceautor4" => ["authorlink4", "author4-link", "authorlink4"],
    "apellidos5" => ["last5", "surname5", "author5"],
    "nombre5" => ["first5", "given5"],
    "enlaceautor5" => ["authorlink5", "author5-link", "authorlink5"],
    "apellidos6" => ["last6", "surname6", "author6"],
    "nombre6" => ["first6", "given6"],
    "enlaceautor6" => ["authorlink6", "author6-link", "authorlink6"],
    "apellidos7" => ["last7", "surname7", "author7"],
    "nombre7" => ["first7", "given7"],
    "enlaceautor7" => ["authorlink7", "author7-link", "authorlink7"],
    "apellidos8" => ["last8", "surname8", "author8"],
    "nombre8" => ["first8", "given8"],
    "enlaceautor8" => ["authorlink8", "author8-link", "authorlink8"],
    "apellidos9" => ["last9", "surname9", "author9"],
    "nombre9" => ["first9", "given9"],
    "enlaceautor9" => ["authorlink9", "author9-link", "authorlink9"],
    "separador-nombres" => ["author-name-separator"],
    "separador-autores" => ["author-separator"],
    "número-autores" => ["display-authors"],
    "otros" => ["others"],
];

// Populate $args_to with $params
foreach ($params as $new => $list) {
    foreach ($list as $old) {
        $args_to[$old] = $new;
    }
}


function work_one_temp($temp, $name)
{
    // ---
    global $refs_temps, $args_to;
    // ---
    // echo_test("\n$name\n");
    // ---
    $temp_name2 = isset($refs_temps[$name]) ? $refs_temps[$name] : $name;
    // ---
    if (strtolower($temp_name2) !== strtolower($name)) {
        $temp->setTempName($temp_name2);
    }
    // ---
    // $params = $temp->getParameters();
    // ---
    $temp->changeParametersNames($args_to);
    // ---
    $temp->deleteParameter("url-status");
    // ---
    $new_text_str = $temp->toString();
    // ---
    return $new_text_str;
}

function fix_temps($text)
{
    // ---
    global $refs_temps;
    // ---
    $temps_in = getTemplates($text);
    // ---
    // echo_test("lenth temps_in:" . count($temps_in) . "\n");
    // ---
    $new_text = $text;
    // ---
    foreach ($temps_in as $temp) {
        // ---
        $name = $temp->getStripName();
        // ---
        // echo_test("* name: $name\n");
        // ---
        $old_text_template = $temp->getTemplateText();
        // ---
        if (!array_key_exists($name, $refs_temps) && !in_array($name, $refs_temps)) {
            // echo_test("not found: $name\n");
            continue;
        }
        // ---
        $new_text_str = work_one_temp($temp, $name);
        // ---
        $new_text = str_replace($old_text_template, $new_text_str, $new_text);
        // ---
    };
    // ---
    return $new_text;
}


function es_section($sourcetitle, $text, $revid)
{
    // ---
    // if text has /\{\{\s*Traducido ref\s*\|/ then return text
    preg_match('/\{\{\s*Traducido\s*ref\s*\|/', $text, $ma);
    if (!empty($ma)) {
        // pub_test_print("return text;");
        return $text;
    }
    // ---
    $date = "{{subst:CURRENTDAY}} de {{subst:CURRENTMONTHNAME}} de {{subst:CURRENTYEAR}}";
    // ---
    $temp = "{{Traducido ref|mdwiki|$sourcetitle|oldid=$revid|trad=|fecha=$date}}";
    // ---
    // find /==\s*Enlaces\s*externos\s*==/ in text if exists add temp after it
    // if not exists add temp at the end of text
    // ---
    preg_match('/==\s*Enlaces\s*externos\s*==/', $text, $matches);
    // ---
    if (!empty($matches)) {
        $text = preg_replace('/==\s*Enlaces\s*externos\s*==/', "== Enlaces externos ==\n$temp\n", $text, 1);
    } else {
        $text .= "\n== Enlaces externos ==\n$temp\n";
    }
    // ---
    return $text;
}

function fix_es($text, $title)
{
    // Check for "#REDIRECCIÓN"
    if (strpos($text, "#REDIRECCIÓN") !== false && $title != "test!") {
        return $text;
    }

    // Check if the text has fewer than 10 lines
    if (substr_count($text, "\n") < 10 && $title != "test!") {
        echo_test("less than 10 lines\n");
        // return $text;
    }

    // Replace <references /> with {{listaref}}
    if (strpos($text, "<references />") !== false) {
        $text = str_replace("<references />", "{{listaref}}", $text);
    }

    // Apply transformations
    $newtext = $text;
    $newtext = fix_es_months($newtext);
    $newtext = fix_temps($newtext);
    $newtext = mv_es_refs($newtext);

    return $newtext;
}
