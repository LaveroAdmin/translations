# Translation Manager for LaveroAdmin

Web-based localization and language files management extension for LaveroAdmin.

## Installation

```bash
composer require laveroadmin/translations
```

Import menu item and permissions:

```bash
php artisan admin:import translations
```

## Features

- **Side-by-Side Comparison**: View source language text alongside target translations in a clear two-column layout.
- **Support for Both PHP and JSON**: Seamlessly parses and writes back both nested PHP array files (`lang/{locale}/*.php`) and root JSON files (`lang/{locale}.json`).
- **Translation Completeness Bar**: Real-time progress metric highlighting untranslated and missing keys.
- **Key Creator**: Add new keys on the fly directly from the administrative UI.
- **Live Filtering**: Instant search across keys, source strings, and existing translations.

## License

The MIT License (MIT).
