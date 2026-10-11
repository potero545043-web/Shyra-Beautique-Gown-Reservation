<x-app-layout>
    <div class="sb-page"><div class="sb-wrap">
        <div class="sb-heading"><div><span class="sb-kicker">STAFF DIRECTORY</span><h1>Add an <em>employee.</em></h1><p>Create an employee account for operational access.</p></div><a class="sb-outline-btn sb-back-btn" href="{{ route('owner.employees') }}">Back to employees</a></div>
        @if($errors->any())<div class="sb-form-errors">{{ $errors->first() }}</div>@endif
        <section class="sb-panel sb-form-panel"><form method="POST" action="{{ route('owner.employees.store') }}" class="sb-reservation-form">
            @csrf
            <h2>Employee details</h2>
            <label>Full name<input name="name" required value="{{ old('name') }}"></label>
            <label>Email address<input type="email" name="email" required value="{{ old('email') }}"></label>
            {{-- Password stays masked and is never echoed back; it is stored only as a hash. --}}
            <label>Temporary password<input type="password" name="password" minlength="8" required
                    autocomplete="new-password" autocapitalize="off" spellcheck="false" value=""
                    data-lpignore="true"></label>
            <label>Contact number<input name="contact_number" value="{{ old('contact_number') }}"></label>
            <label>Position<input name="position" value="{{ old('position', 'Rental Staff') }}" required></label>
            <button class="sb-btn" type="submit">Create employee account</button>
        </form></section>
    </div></div>
</x-app-layout>
