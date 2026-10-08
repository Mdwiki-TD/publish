<?php
namespace Publish\EditProcess;

use function Publish\MdwikiSql\fetch_query;

/**
 * @return array<string, mixed>
 */
function getLanguagesSettings(string $langCode): array
{
    $query  = "SELECT move_dots, expend, add_en_lang FROM language_settings where lang_code = ?";
    $result = fetch_query($query, [$langCode]);

    // Ensure we always return an array, fallback to empty array if index 0 does not exist
    return (isset($result[0]) && is_array($result[0])) ? $result[0] : [];
}

function text_changes(
    string $sourcetitle,
    string $title,
    string $text,
    string $lang,
    int | string $mdwikiRevid
): string {
    if (function_exists('\WpRefs\FixPage\fix_page_with_setting')) {
        $settings = getLanguagesSettings($lang);

        // Cast database values safely to ?bool to strictly match fix_page_with_setting signature
        $moveDots  = isset($settings['move_dots']) ? (bool) $settings['move_dots'] : null;
        $expand    = isset($settings['expend']) ? (bool) $settings['expend'] : null;
        $addEnLang = isset($settings['add_en_lang']) ? (bool) $settings['add_en_lang'] : null;
        /*
        function fix_page_with_setting(
            string $sourcetitle,
            string $title,
            string $text,
            string $lang,
            int|string $mdwikiRevid,
            ?bool $moveDots = null,
            ?bool $expand = null,
            ?bool $addEnLang = null
        ): string {
        */
        /** @disregard P1010, PHP0417 */
        /** @suppress P1010, PHP0417 */
        $newtext = \WpRefs\FixPage\fix_page_with_setting(
            $sourcetitle,
            $title,
            $text,
            $lang,
            $mdwikiRevid,
            $moveDots,
            $expand,
            $addEnLang
        );

        return $newtext;
    }

    return $text;
}
