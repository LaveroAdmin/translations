<style>
    .trans-key-cell {
        font-family: Consolas, "Courier New", monospace;
        font-size: 13px;
        background-color: #f8f9fa;
        vertical-align: top;
    }
    .trans-source-cell {
        background-color: #fafbfc;
        vertical-align: top;
        font-size: 14px;
        color: #495057;
    }
    .trans-target-cell textarea {
        font-size: 14px;
        resize: vertical;
        min-height: 38px;
    }
    .missing-highlight {
        border-color: #dc3545 !important;
        background-color: #fff8f8;
    }
</style>

<!-- Controls & Filter Form -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="get" action="{{ route('translations-index') }}" class="row g-2 align-items-center">
            <div class="col-sm-3 col-md-2">
                <label class="form-label small text-muted mb-1">{{ trans('lavero-admin-translations::translations.source_language') }}</label>
                <select name="source" class="form-select form-select-sm" onchange="this.form.submit();">
                    @foreach($locales as $loc)
                        <option value="{{ $loc }}" @if($loc === $sourceLocale) selected @endif>{{ strtoupper($loc) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-sm-3 col-md-2">
                <label class="form-label small text-muted mb-1">{{ trans('lavero-admin-translations::translations.target_language') }}</label>
                <select name="target" class="form-select form-select-sm" onchange="this.form.submit();">
                    @foreach($locales as $loc)
                        <option value="{{ $loc }}" @if($loc === $targetLocale) selected @endif>{{ strtoupper($loc) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-sm-4 col-md-3">
                <label class="form-label small text-muted mb-1">{{ trans('lavero-admin-translations::translations.group') }}</label>
                <select name="group" class="form-select form-select-sm" onchange="this.form.submit();">
                    @foreach($groups as $grp)
                        <option value="{{ $grp }}" @if($grp === $group) selected @endif>{{ $grp }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">&nbsp;</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="filter-search" class="form-control" placeholder="{{ trans('lavero-admin-translations::translations.search_keys') }}">
                    <span class="input-group-text"><i class="icon-search"></i></span>
                </div>
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" id="filter-untranslated">
                    <label class="form-check-label small text-muted" for="filter-untranslated">Only untranslated</label>
                </div>
            </div>

            <div class="col-sm-6 col-md-2 text-end d-flex align-items-end justify-content-end gap-1 flex-wrap">
                <a href="{{ route('translations-export', ['source' => $sourceLocale, 'target' => $targetLocale, 'group' => $group]) }}" class="btn btn-sm btn-outline-secondary no-ajax" title="Export JSON">
                    <i class="icon-download"></i>
                </a>
                <label class="btn btn-sm btn-outline-secondary mb-0" title="Import JSON" style="cursor: pointer;">
                    <i class="icon-upload"></i>
                    <input type="file" id="import-json-input" accept=".json" class="d-none">
                </label>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-add-key">
                    <i class="icon-plus"></i> {{ trans('lavero-admin-translations::translations.add_key') }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Progress Bar -->
<div class="card mb-3">
    <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="small fw-semibold">
                {{ trans('lavero-admin-translations::translations.total_keys') }}: <span class="badge bg-secondary">{{ $data['total'] }}</span>
            </span>
            <span class="small text-success fw-semibold">
                {{ trans('lavero-admin-translations::translations.translated') }}: <span class="badge bg-success">{{ $data['translated_count'] }}</span>
            </span>
            @if($data['missing_count'] > 0)
                <span class="small text-danger fw-semibold">
                    {{ trans('lavero-admin-translations::translations.missing') }}: <span class="badge bg-danger">{{ $data['missing_count'] }}</span>
                </span>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3" style="min-width: 250px;">
            <div class="progress flex-grow-1" style="height: 12px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $data['percent'] }}%;" aria-valuenow="{{ $data['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <span class="small fw-bold">{{ $data['percent'] }}%</span>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="icon-file-alt me-1 text-primary"></i> {{ $group }} ({{ strtoupper($targetLocale) }})
        </h6>

        <button type="button" class="btn btn-sm btn-primary" id="btn-save-translations">
            <i class="icon-save me-1"></i> {{ trans('lavero-admin-translations::translations.save_all') }}
        </button>
    </div>

    @if(empty($data['items']))
        <div class="card-body text-center py-5 text-muted">
            <i class="icon-language fs-1 mb-2 opacity-50"></i>
            <h6 class="fw-normal">{{ trans('lavero-admin-translations::translations.empty') }}</h6>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" id="translations-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 28%;">{{ trans('lavero-admin-translations::translations.key') }}</th>
                        <th style="width: 36%;">{{ trans('lavero-admin-translations::translations.source_text') }} ({{ strtoupper($sourceLocale) }})</th>
                        <th style="width: 36%;">{{ trans('lavero-admin-translations::translations.target_text') }} ({{ strtoupper($targetLocale) }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['items'] as $item)
                    <tr class="translation-row" data-key="{{ strtolower($item['key']) }}" data-text="{{ strtolower($item['source'].' '.$item['target']) }}">
                        <td class="trans-key-cell">
                            <span class="text-primary fw-medium">{{ $item['key'] }}</span>
                        </td>
                        <td class="trans-source-cell">
                            {{ $item['source'] ?: '—' }}
                        </td>
                        <td class="trans-target-cell">
                            <textarea class="form-control translation-input @if($item['is_missing']) missing-highlight @endif" rows="1" data-key="{{ $item['key'] }}">{{ $item['target'] }}</textarea>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Modal: Add Key -->
<div class="modal fade" id="modal-add-key" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ trans('lavero-admin-translations::translations.new_key_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ admin_trans('admin.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ trans('lavero-admin-translations::translations.key_name') }}</label>
                    <input type="text" class="form-control" id="new-key-name" placeholder="my_new_key">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ trans('lavero-admin-translations::translations.default_value') }}</label>
                    <textarea class="form-control" id="new-key-value" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ admin_trans('admin.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-submit-new-key">{{ trans('lavero-admin-translations::translations.add_key') }}</button>
            </div>
        </div>
    </div>
</div>

<script data-exec-on-popstate>
(function () {
    const saveUrl = '{{ route("translations-save") }}';
    const addKeyUrl = '{{ route("translations-add-key") }}';
    const currentLocale = '{{ $targetLocale }}';
    const currentGroup = '{{ $group }}';
    const currentSource = '{{ $sourceLocale }}';

    // Live search & untranslated filter
    const searchInput = document.getElementById('filter-search');
    const untranslatedCheck = document.getElementById('filter-untranslated');
    function applyTranslationFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const onlyMissing = untranslatedCheck ? untranslatedCheck.checked : false;
        document.querySelectorAll('.translation-row').forEach(row => {
            const key = row.dataset.key || '';
            const text = row.dataset.text || '';
            const input = row.querySelector('.translation-input');
            const isMissing = !input || input.value.trim().length === 0;
            const matchesQuery = !query || key.includes(query) || text.includes(query);
            const matchesMissing = !onlyMissing || isMissing;
            row.style.display = (matchesQuery && matchesMissing) ? '' : 'none';
        });
    }
    if (searchInput) searchInput.addEventListener('input', applyTranslationFilters);
    if (untranslatedCheck) untranslatedCheck.addEventListener('change', applyTranslationFilters);

    // Import JSON
    const importInput = document.getElementById('import-json-input');
    if (importInput) {
        importInput.addEventListener('change', function () {
            if (!this.files || !this.files.length) return;
            const fd = new FormData();
            fd.append('file', this.files[0]);
            fd.append('locale', currentLocale);
            fd.append('group', currentGroup);
            fd.append('_token', '{{ csrf_token() }}');
            fetch('{{ route("translations-import") }}', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res && res.status) {
                        admin.toastr.success(res.message);
                        setTimeout(() => admin.ajax.reload(), 600);
                    } else {
                        admin.toastr.error((res && res.message) ? res.message : 'Import failed');
                    }
                })
                .catch(() => admin.toastr.error('Import failed'));
        });
    }

    // Auto remove red highlight on typing
    document.querySelectorAll('.translation-input').forEach(input => {
        input.addEventListener('input', function () {
            if (this.value.trim().length > 0) {
                this.classList.remove('missing-highlight');
            } else {
                this.classList.add('missing-highlight');
            }
        });
    });

    // Save All
    const btnSave = document.getElementById('btn-save-translations');
    if (btnSave) {
        btnSave.addEventListener('click', function () {
            const translations = {};
            document.querySelectorAll('.translation-input').forEach(input => {
                translations[input.dataset.key] = input.value;
            });

            const origHtml = btnSave.innerHTML;
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ...';

            admin.ajax.post(saveUrl, {
                locale: currentLocale,
                group: currentGroup,
                translations: translations
            }, function (res) {
                btnSave.disabled = false;
                btnSave.innerHTML = origHtml;

                if (res && res.data && res.data.status) {
                    admin.toastr.success(res.data.message);
                } else {
                    admin.toastr.error(res && res.data ? res.data.message : 'Error');
                }
            });
        });
    }

    // Add Key
    const btnSubmitKey = document.getElementById('btn-submit-new-key');
    if (btnSubmitKey) {
        btnSubmitKey.addEventListener('click', function () {
            const key = document.getElementById('new-key-name').value.trim();
            const value = document.getElementById('new-key-value').value;

            if (!key) {
                admin.toastr.error('Key name cannot be empty');
                return;
            }

            admin.ajax.post(addKeyUrl, {
                group: currentGroup,
                target: currentLocale,
                source: currentSource,
                key: key,
                value: value
            }, function (res) {
                if (res && res.data && res.data.status) {
                    admin.toastr.success(res.data.message);
                    const modalEl = document.getElementById('modal-add-key');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    setTimeout(() => admin.ajax.reload(), 600);
                } else {
                    admin.toastr.error(res && res.data ? res.data.message : 'Error');
                }
            });
        });
    }
})();
</script>
