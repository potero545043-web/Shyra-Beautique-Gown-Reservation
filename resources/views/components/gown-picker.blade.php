@props([
    'name',
    'endpoint',
    'label' => 'Gown',
    'placeholder' => 'Choose a gown',
    'searchPlaceholder' => 'Search by gown name or code',
])

@php($pickerId = 'gown-picker-' . \Illuminate\Support\Str::uuid())

<div class="sb-gown-picker" data-endpoint="{{ $endpoint }}">
    <label for="{{ $pickerId }}-trigger">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" value="{{ old($name) }}">
    <button id="{{ $pickerId }}-trigger" class="sb-gown-trigger" type="button"
        aria-haspopup="listbox" aria-expanded="false">
        <span class="sb-gown-selection" data-placeholder="{{ $placeholder }}">{{ $placeholder }}</span>
        <span aria-hidden="true">⌄</span>
    </button>
    <div class="sb-gown-dropdown" hidden>
        <label class="sb-gown-search-label" for="{{ $pickerId }}-search">Search gowns</label>
        <input id="{{ $pickerId }}-search" class="sb-gown-search" type="search"
            placeholder="{{ $searchPlaceholder }}">
        <div class="sb-gown-results" role="listbox" aria-label="Gowns" aria-live="polite"></div>
        <div class="sb-gown-pagination">
            <button type="button" data-gown-page="previous" disabled>Previous</button>
            <span data-gown-page-label></span>
            <button type="button" data-gown-page="next" disabled>Next</button>
        </div>
    </div>
    <x-input-error :messages="$errors->get($name)" />
</div>

<script>
    (() => {
        const picker = document.currentScript.previousElementSibling;
        const form = picker.closest('form');
        const valueInput = picker.querySelector('input[type="hidden"]');
        const trigger = picker.querySelector('.sb-gown-trigger');
        const selection = picker.querySelector('.sb-gown-selection');
        const dropdown = picker.querySelector('.sb-gown-dropdown');
        const search = picker.querySelector('.sb-gown-search');
        const results = picker.querySelector('.sb-gown-results');
        const pageLabel = picker.querySelector('[data-gown-page-label]');
        const previousButton = picker.querySelector('[data-gown-page="previous"]');
        const nextButton = picker.querySelector('[data-gown-page="next"]');
        let currentPage = 1;
        let lastPage = 1;
        let searchTimer;

        const showMessage = (message, isError = false) => {
            results.replaceChildren();
            const item = document.createElement('p');
            item.className = `sb-gown-message${isError ? ' is-error' : ''}`;
            item.textContent = message;
            results.append(item);
        };

        const renderSelection = gown => {
            selection.replaceChildren();
            if (!gown) {
                selection.textContent = selection.dataset.placeholder;
                return;
            }

            const image = document.createElement('img');
            image.src = gown.image_url;
            image.alt = '';
            image.className = 'sb-gown-avatar';

            const details = document.createElement('span');
            details.className = 'sb-gown-selection-copy';
            const title = document.createElement('b');
            title.textContent = `${gown.gown_code} · ${gown.name}`;
            const status = document.createElement('small');
            status.textContent = gown.status.replaceAll('_', ' ');
            details.append(title, status);
            selection.append(image, details);
        };

        const renderGowns = gowns => {
            results.replaceChildren();
            if (gowns.length === 0) {
                showMessage('No gowns match your search.');
                return;
            }

            gowns.forEach(gown => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'sb-gown-option';
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', String(valueInput.value === String(gown.id)));

                const image = document.createElement('img');
                image.src = gown.image_url;
                image.alt = '';
                image.loading = 'lazy';
                image.className = 'sb-gown-avatar';

                const details = document.createElement('span');
                details.className = 'sb-gown-option-copy';
                const title = document.createElement('b');
                title.textContent = `${gown.gown_code} · ${gown.name}`;
                const status = document.createElement('small');
                status.textContent = gown.status.replaceAll('_', ' ');
                details.append(title, status);

                option.append(image, details);
                option.addEventListener('click', () => {
                    valueInput.value = gown.id;
                    renderSelection(gown);
                    dropdown.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                    trigger.focus();
                });
                results.append(option);
            });
        };

        const loadGowns = async (page = 1) => {
            showMessage('Loading gowns…');
            const url = new URL(picker.dataset.endpoint, window.location.href);
            url.searchParams.set('page', page);
            url.searchParams.set('q', search.value.trim());
            if (valueInput.value) url.searchParams.set('selected_id', valueInput.value);

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`Gown list request failed (${response.status}).`);

                const payload = await response.json();
                currentPage = payload.current_page;
                lastPage = payload.last_page;
                renderGowns(payload.data);
                if (payload.selected) renderSelection(payload.selected);
                pageLabel.textContent = `${payload.total} gowns · Page ${currentPage} of ${lastPage}`;
                previousButton.disabled = currentPage <= 1;
                nextButton.disabled = currentPage >= lastPage;
            } catch (error) {
                console.error('Unable to load gowns for this form.', error);
                showMessage('Gowns could not be loaded. Please try again.', true);
                pageLabel.textContent = '';
                previousButton.disabled = true;
                nextButton.disabled = true;
            }
        };

        trigger.addEventListener('click', () => {
            const opening = dropdown.hidden;
            dropdown.hidden = !opening;
            trigger.setAttribute('aria-expanded', String(opening));
            if (opening) {
                loadGowns(1);
                search.focus();
            }
        });

        search.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => loadGowns(1), 250);
        });
        previousButton.addEventListener('click', () => loadGowns(currentPage - 1));
        nextButton.addEventListener('click', () => loadGowns(currentPage + 1));
        form.addEventListener('submit', event => {
            if (valueInput.value) return;
            event.preventDefault();
            dropdown.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            showMessage('Choose a gown before saving this record.', true);
            loadGowns(1);
            trigger.focus();
        });

        if (valueInput.value) loadGowns(1);
    })();
</script>
