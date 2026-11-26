<?php

namespace LaveroAdmin\Admin\Translations;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TranslationService
{
    /**
     * Get path to language files.
     *
     * @return string
     */
    public function getLangPath(): string
    {
        if (function_exists('lang_path') && File::isDirectory(lang_path())) {
            return lang_path();
        }

        if (File::isDirectory(base_path('lang'))) {
            return base_path('lang');
        }

        return resource_path('lang');
    }

    /**
     * Get list of available locales.
     *
     * @return array
     */
    public function getLocales(): array
    {
        $path = $this->getLangPath();
        $locales = [];

        if (File::isDirectory($path)) {
            $dirs = File::directories($path);
            foreach ($dirs as $dir) {
                $locales[] = basename($dir);
            }

            $jsonFiles = File::glob($path . '/*.json');
            foreach ($jsonFiles as $file) {
                $locales[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        $locales = array_unique(array_filter($locales));
        if (empty($locales)) {
            $locales = ['en', 'ru'];
        }

        sort($locales);

        return $locales;
    }

    /**
     * Get translation groups/files.
     *
     * @return array
     */
    public function getGroups(): array
    {
        $path = $this->getLangPath();
        $groups = [];

        if (File::isDirectory($path)) {
            $phpFiles = File::allFiles($path);
            foreach ($phpFiles as $file) {
                if ($file->getExtension() === 'php') {
                    $groups[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                }
            }

            if (count(File::glob($path . '/*.json')) > 0) {
                $groups[] = '_json';
            }
        }

        $groups = array_unique(array_filter($groups));
        if (empty($groups)) {
            $groups = ['admin', 'auth', 'pagination', 'validation'];
        }

        sort($groups);

        return $groups;
    }

    /**
     * Load translations comparison between source and target locale.
     *
     * @param string $sourceLocale
     * @param string $targetLocale
     * @param string $group
     * @return array
     */
    public function loadTranslations(string $sourceLocale, string $targetLocale, string $group): array
    {
        $sourceArray = $this->loadFile($sourceLocale, $group);
        $targetArray = $this->loadFile($targetLocale, $group);

        $flatSource = Arr::dot($sourceArray);
        $flatTarget = Arr::dot($targetArray);

        $allKeys = array_unique(array_merge(array_keys($flatSource), array_keys($flatTarget)));
        sort($allKeys);

        $result = [];
        $translatedCount = 0;

        foreach ($allKeys as $key) {
            $sourceVal = $flatSource[$key] ?? '';
            $targetVal = $flatTarget[$key] ?? '';
            $isMissing = ($targetVal === '' || $targetVal === null);

            if (!$isMissing) {
                $translatedCount++;
            }

            $result[] = [
                'key'        => $key,
                'source'     => is_array($sourceVal) ? json_encode($sourceVal, JSON_UNESCAPED_UNICODE) : (string)$sourceVal,
                'target'     => is_array($targetVal) ? json_encode($targetVal, JSON_UNESCAPED_UNICODE) : (string)$targetVal,
                'is_missing' => $isMissing,
            ];
        }

        $totalCount = count($allKeys);
        $percent = $totalCount > 0 ? round(($translatedCount / $totalCount) * 100) : 100;

        return [
            'items'            => $result,
            'total'            => $totalCount,
            'translated_count' => $translatedCount,
            'missing_count'    => $totalCount - $translatedCount,
            'percent'          => $percent,
        ];
    }

    /**
     * Load raw translation file.
     *
     * @param string $locale
     * @param string $group
     * @return array
     */
    protected function loadFile(string $locale, string $group): array
    {
        $path = $this->getLangPath();

        if ($group === '_json') {
            $file = $path . '/' . $locale . '.json';
            if (File::exists($file)) {
                return json_decode(File::get($file), true) ?: [];
            }
            return [];
        }

        $file = $path . '/' . $locale . '/' . $group . '.php';
        if (File::exists($file)) {
            $data = include $file;
            return is_array($data) ? $data : [];
        }

        return [];
    }

    /**
     * Save translation keys into file.
     *
     * @param string $locale
     * @param string $group
     * @param array $translations [key => value]
     * @return bool
     */
    public function saveTranslations(string $locale, string $group, array $translations): bool
    {
        $path = $this->getLangPath();

        if ($group === '_json') {
            $file = $path . '/' . $locale . '.json';
            $existing = File::exists($file) ? (json_decode(File::get($file), true) ?: []) : [];
            $merged = array_merge($existing, $translations);
            ksort($merged);

            return (bool)File::put($file, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $dir = $path . '/' . $locale;
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $file = $dir . '/' . $group . '.php';
        $existing = File::exists($file) ? (include $file) : [];
        if (!is_array($existing)) {
            $existing = [];
        }

        $flatExisting = Arr::dot($existing);
        foreach ($translations as $key => $val) {
            $flatExisting[$key] = $val;
        }

        $nested = [];
        foreach ($flatExisting as $key => $val) {
            Arr::set($nested, $key, $val);
        }

        $export = $this->prettyPhpExport($nested);
        $content = "<?php\n\nreturn " . $export . ";\n";

        $tmpFile = tempnam($dir, 'lang_');
        if ($tmpFile !== false && File::put($tmpFile, $content) !== false) {
            @chmod($tmpFile, 0644);
            return @rename($tmpFile, $file);
        }

        return (bool) File::put($file, $content);
    }

    /**
     * Pretty print array as clean PHP syntax.
     *
     * @param array $expression
     * @param int $level
     * @return string
     */
    protected function prettyPhpExport(array $expression, int $level = 1): string
    {
        $indent = str_repeat('    ', $level);
        $prevIndent = str_repeat('    ', $level - 1);

        $entries = [];
        foreach ($expression as $key => $val) {
            $k = is_int($key) ? $key : "'" . addcslashes($key, "'\\") . "'";
            if (is_array($val)) {
                $entries[] = "{$indent}{$k} => " . $this->prettyPhpExport($val, $level + 1);
            } else {
                $v = "'" . addcslashes((string)$val, "'\\") . "'";
                $entries[] = "{$indent}{$k} => {$v},";
            }
        }

        return "[\n" . implode("\n", $entries) . "\n{$prevIndent}]" . ($level > 1 ? ',' : '');
    }
}
