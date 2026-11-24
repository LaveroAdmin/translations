<?php

namespace LaveroAdmin\Admin\Translations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaveroAdmin\Admin\Facades\Admin;
use LaveroAdmin\Admin\Layout\Content;

class TranslationsController
{
    protected TranslationService $service;

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): Content
    {
        return Admin::content(function (Content $content) use ($request) {
            $content->header(trans('lavero-admin-translations::translations.title'));
            $content->description(trans('lavero-admin-translations::translations.description'));

            $locales = $this->service->getLocales();
            $groups = $this->service->getGroups();

            $sourceLocale = $request->input('source', 'en');
            $targetLocale = $request->input('target', config('app.locale', 'ru'));
            $group = $request->input('group', $groups[0] ?? 'admin');

            $data = $this->service->loadTranslations($sourceLocale, $targetLocale, $group);

            $content->body(view('lavero-admin-translations::index', compact('locales', 'groups', 'sourceLocale', 'targetLocale', 'group', 'data')));
        });
    }

    public function save(Request $request): JsonResponse
    {
        $locale = $request->input('locale');
        $group = $request->input('group');
        $translations = $request->input('translations', []);

        if (empty($locale) || empty($group) || !is_array($translations)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid parameters',
            ]);
        }

        try {
            $this->service->saveTranslations($locale, $group, $translations);

            return response()->json([
                'status'  => true,
                'message' => trans('lavero-admin-translations::translations.save_success'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function addKey(Request $request): JsonResponse
    {
        $group = $request->input('group');
        $key = trim($request->input('key', ''));
        $targetLocale = $request->input('target');
        $sourceLocale = $request->input('source', 'en');
        $value = $request->input('value', '');

        if (empty($group) || empty($key)) {
            return response()->json([
                'status'  => false,
                'message' => 'Key cannot be empty',
            ]);
        }

        try {
            $this->service->saveTranslations($targetLocale, $group, [$key => $value]);
            if ($sourceLocale !== $targetLocale) {
                $this->service->saveTranslations($sourceLocale, $group, [$key => $key]);
            }

            return response()->json([
                'status'  => true,
                'message' => trans('lavero-admin-translations::translations.add_key_success'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
