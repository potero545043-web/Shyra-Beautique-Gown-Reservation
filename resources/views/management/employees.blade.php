<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">TEAM ACCESS</span>

                    <h1>Your <em>team.</em></h1>

                    <p>Create staff accounts and manage employee access to operations.</p>
                </div>
            </div>


            {{-- MESSAGES --}}
            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            @if($errors->any() && !old('_employee_drawer'))
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif


            {{-- STAFF PANEL --}}
            <section class="sb-panel">

                <div class="sb-inventory-content">

                    {{-- PANEL HEADER: title left, Add button right --}}
                    <div class="sb-inventory-toolbar">

                        <div>
                            <span class="sb-kicker">STAFF DIRECTORY</span>

                            <h2>All employees</h2>

                            <p>
                                {{ $employees->total() }}
                                employee {{ \Illuminate\Support\Str::plural('account', $employees->total()) }}
                            </p>
                        </div>

                        <button class="sb-inventory-primary sb-inventory-filter-add" type="button"
                            onclick="document.getElementById('employee-create-dialog').showModal()">
                            <span aria-hidden="true">＋</span>
                            Add employee
                        </button>

                    </div>


                    {{-- TABLE --}}
                    <div class="sb-inventory-table-wrap">

                        <table class="sb-inventory-table sb-employee-table">

                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Contact</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($employees as $employee)

                                    @php
                                        $initials = \Illuminate\Support\Str::upper(
                                            \Illuminate\Support\Str::of($employee->full_name)
                                                ->explode(' ')
                                                ->filter()
                                                ->map(fn($w) => mb_substr($w, 0, 1))
                                                ->take(2)
                                                ->implode('')
                                        );
                                    @endphp

                                    <tr class="sb-clickable-row" data-href="{{ route('owner.employees.show', $employee) }}"
                                        tabindex="0" role="link" aria-label="View employee {{ $employee->full_name }}">

                                        {{-- EMPLOYEE --}}
                                        <td data-label="Employee">
                                            <div class="sb-inventory-name">

                                                <div class="sb-employee-avatar" aria-hidden="true">
                                                    {{ $initials }}
                                                </div>

                                                <span>
                                                    <b>{{ $employee->full_name }}</b>
                                                    <small>{{ $employee->employee_code }}</small>
                                                </span>

                                            </div>
                                        </td>


                                        {{-- CONTACT --}}
                                        <td data-label="Contact" class="sb-employee-contact">
                                            <span>{{ $employee->user->email }}</span>
                                            <small>{{ $employee->contact_number ?: 'No phone number' }}</small>
                                        </td>


                                        {{-- POSITION --}}
                                        <td data-label="Position">
                                            {{ $employee->position }}
                                        </td>


                                        {{-- STATUS --}}
                                        <td data-label="Status">
                                            <span
                                                class="sb-inventory-status sb-gown-status {{ $employee->status === 'active' ? 'is-available' : 'is-disabled' }}">
                                                {{ ucfirst($employee->status) }}
                                            </span>
                                        </td>


                                        {{-- ACTIONS --}}
                                        <td data-label="Actions">

                                            <div class="sb-inventory-actions">

                                                <button type="button" class="sb-action-edit"
                                                    onclick="document.getElementById('employee-edit-{{ $employee->id }}').showModal()">
                                                    Edit
                                                </button>

                                                <form method="POST"
                                                    action="{{ route('owner.employees.toggle', $employee) }}">

                                                    @csrf
                                                    @method('PATCH')

                                                    @if($employee->status === 'active')
                                                        <button class="sb-action-danger" type="submit">Deactivate</button>
                                                    @else
                                                        <button class="sb-action-edit" type="submit">Activate</button>
                                                    @endif

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="5">
                                            <div class="sb-inventory-empty">
                                                <b>No employees yet</b>
                                                <p>Add your first staff account to give your team access to rental
                                                    operations.</p>
                                            </div>
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- PAGINATION --}}
                    @include('components.table-pagination', ['paginator' => $employees, 'itemLabel' => 'employees'])

                </div>

            </section>


            {{-- EDIT EMPLOYEE DRAWERS (one per employee, outside the table so nothing gets clipped) --}}
            @foreach($employees as $employee)

                <dialog class="sb-side-drawer" id="employee-edit-{{ $employee->id }}"
                    aria-labelledby="employee-edit-title-{{ $employee->id }}"
                    onclick="if (event.target === this) this.close()">

                    <div class="sb-side-drawer-head">

                        <div>
                            <span class="sb-kicker">TEAM ACCESS</span>

                            <h2 id="employee-edit-title-{{ $employee->id }}">Edit employee</h2>

                            <p>{{ $employee->employee_code }} · update contact details and position.</p>
                        </div>

                        <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                            onclick="document.getElementById('employee-edit-{{ $employee->id }}').close()">
                            &times;
                        </button>

                    </div>

                    <form method="POST" action="{{ route('owner.employees.update', $employee) }}"
                        class="sb-side-drawer-form">

                        @csrf
                        @method('PUT')

                        <label>Full name
                            <input name="name" value="{{ $employee->full_name }}" required>
                        </label>

                        <label>Email address
                            <input type="email" name="email" value="{{ $employee->user->email }}" required>
                        </label>

                        <label>Contact number
                            <input name="contact_number" value="{{ $employee->contact_number }}">
                        </label>

                        <label>Position
                            <input name="position" value="{{ $employee->position }}" required>
                        </label>

                        <div class="sb-side-drawer-actions">
                            <button class="sb-side-drawer-cancel" type="button"
                                onclick="document.getElementById('employee-edit-{{ $employee->id }}').close()">
                                Cancel
                            </button>

                            <button class="sb-inventory-primary" type="submit">Save details</button>
                        </div>

                    </form>

                </dialog>

            @endforeach


            {{-- ADD EMPLOYEE DRAWER --}}
            <dialog class="sb-side-drawer" id="employee-create-dialog" aria-labelledby="employee-create-title"
                onclick="if (event.target === this) this.close()">

                <div class="sb-side-drawer-head">

                    <div>
                        <span class="sb-kicker">TEAM ACCESS</span>

                        <h2 id="employee-create-title">New employee account</h2>

                        <p>A new employee account receives operational access only.</p>
                    </div>

                    <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                        onclick="document.getElementById('employee-create-dialog').close()">
                        &times;
                    </button>

                </div>


                <form method="POST" action="{{ route('owner.employees.store') }}" class="sb-side-drawer-form">

                    @csrf

                    <input type="hidden" name="_employee_drawer" value="1">

                    @if($errors->any() && old('_employee_drawer'))
                        <div class="sb-form-errors">{{ $errors->first() }}</div>
                    @endif

                    <label>Full name
                        <input name="name" required autofocus autocomplete="name" value="{{ old('name') }}">
                        <x-input-error :messages="$errors->get('name')" />
                    </label>

                    <label>Email address
                        <input type="email" name="email" required autocomplete="email" value="{{ old('email') }}">
                        <x-input-error :messages="$errors->get('email')" />
                    </label>

                    <label>Password
                        {{-- Passwords are never echoed back or stored in the page markup.
                             The field starts empty, autocomplete is disabled, and it is
                             cleared once submitted so nothing lingers in the browser. --}}
                        <input type="password" id="employee-password" name="password" minlength="8" required
                            autocomplete="new-password" autocapitalize="off" spellcheck="false"
                            value="" oncontextmenu="return false" data-lpignore="true">
                        <small class="sb-side-drawer-help">Minimum 8 characters. The password is stored only as a
                            secure hash and is never shown again.</small>
                        <x-input-error :messages="$errors->get('password')" />
                    </label>

                    <label>Contact number
                        <input type="tel" inputmode="tel" name="contact_number" autocomplete="tel"
                            placeholder="09XX XXX XXXX" value="{{ old('contact_number') }}">
                        <x-input-error :messages="$errors->get('contact_number')" />
                    </label>

                    <label>Position
                        <input name="position" placeholder="e.g. Rental Staff"
                            value="{{ old('position', 'Rental Staff') }}" required>
                        <small class="sb-side-drawer-help">Position title only; account permissions stay
                            employee-level.</small>
                        <x-input-error :messages="$errors->get('position')" />
                    </label>

                    <div class="sb-side-drawer-actions">
                        <button class="sb-side-drawer-cancel" type="button"
                            onclick="document.getElementById('employee-create-dialog').close()">
                            Cancel
                        </button>

                        <button class="sb-inventory-primary" type="submit">Create employee</button>
                    </div>

                </form>

            </dialog>


            {{-- REOPEN DRAWER AFTER VALIDATION ERROR --}}
            @if(old('_employee_drawer'))
                <script>
                    document.getElementById('employee-create-dialog').showModal();
                </script>
            @endif

        </div>
    </div>

</x-app-layout>