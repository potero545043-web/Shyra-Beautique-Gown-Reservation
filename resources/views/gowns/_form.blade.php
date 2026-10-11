<x-app-layout>
    <div class="sb-page sb-inventory-page"><div class="sb-wrap">
        <div class="sb-heading"><div><span class="sb-kicker">INVENTORY · GOWNS</span><h1>{{ $formTitle }}<em>.</em></h1><p>Add the rental details, category, and optional pieces supplied with this look.</p></div><a class="sb-inventory-secondary sb-back-btn" href="{{ route('owner.gowns.index', !empty($gown?->archived_at) ? ['archived' => 1] : []) }}">{{ !empty($gown?->archived_at) ? 'Back to archive' : 'Back to collection' }}</a></div>
        @if($errors->any())<div class="sb-form-errors">{{ $errors->first() }}</div>@endif
        <section class="sb-panel sb-inventory-form-card">
            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
                @csrf
                @if($isEditing) @method('PUT') @endif
                @include('gowns._fields')
            </form>
        </section>
    </div></div>
</x-app-layout>
