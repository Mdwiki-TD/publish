<?php

namespace Publish\Process;

use Publish\MdwikiSql\Database;

class TextChanger
{
    private const FIXER_FUNCTION = 'WpRefs\FixPage\fix_page_with_setting';

    private Database $db;

    /** @var (callable(string, string, string, string, int|string, ?bool, ?bool, ?bool): string)|null */
    private $fixer;

    /**
     * @param (callable(string, string, string, string, int|string, ?bool, ?bool, ?bool): string)|null $fixer
     *        Optional override for \WpRefs\FixPage\fix_page_with_setting (mainly for tests).
     */
    public function __construct(?Database $db = null, ?callable $fixer = null)
    {
        $this->db = $db ?? new Database();
        $this->fixer = $fixer;
    }

    /**
     * @return array<string, mixed>
     */
    private function getLanguagesSettings(string $langCode): array
    {
        $query = "SELECT move_dots, expend, add_en_lang
            FROM language_settings
            WHERE lang_code = ?
        ";

        $result = $this->db->fetchQuery($query, [$langCode]);

        // Ensure we always return an array, fallback to empty array if index 0 does not exist
        return (isset($result[0]) && is_array($result[0])) ? $result[0] : [];
    }

    /**
     * The callable that fixes page text, or null when none is available.
     */
    protected function resolveFixer()
    {
        if ($this->fixer !== null) {
            return $this->fixer;
        }

        return function_exists(self::FIXER_FUNCTION) ? self::FIXER_FUNCTION : null;
    }

    public function text_changes(
        string $sourcetitle,
        string $title,
        string $text,
        string $lang,
        int|string $mdwikiRevid
    ): string {
        $fixer = $this->resolveFixer();

        if ($fixer === null) {
            return $text;
        }

        $settings = $this->getLanguagesSettings($lang);

        // Cast database values safely to ?bool to strictly match fix_page_with_setting signature
        $moveDots = isset($settings['move_dots']) ? (bool) $settings['move_dots'] : null;
        $expand = isset($settings['expend']) ? (bool) $settings['expend'] : null;
        $addEnLang = isset($settings['add_en_lang']) ? (bool) $settings['add_en_lang'] : null;

        return $fixer(
            $sourcetitle,
            $title,
            $text,
            $lang,
            $mdwikiRevid,
            $moveDots,
            $expand,
            $addEnLang
        );
    }
}
