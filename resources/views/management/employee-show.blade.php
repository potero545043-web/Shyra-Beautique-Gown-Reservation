<x-app-layout>
    <div class="sb-page"><div class="sb-wrap">
        <div class="sb-heading"><div><span class="sb-kicker">EMPLOYEE WORK LOG</span><h1>{{ $employee->full_name }}<em>.</em></h1><p>{{ $employee->position }} · {{ $employee->employee_code }} · {{ ucfirst($employee->status) }}</p></div><a class="sb-outline-btn sb-back-btn" href="{{ route('owner.employees') }}">Back to employees</a></div>
        <div class="sb-columns">
            <section class="sb-panel">
                <div class="sb-panel-head"><div><h2>Employee details</h2><p>Account and employment information</p></div></div>
                <div class="sb-gown-row"><div><b>Email</b><small>{{ $employee->user->email }}</small></div></div>
                <div class="sb-gown-row"><div><b>Contact</b><small>{{ $employee->contact_number ?: 'Not provided' }}</small></div></div>
                <div class="sb-gown-row"><div><b>Status</b><small>{{ ucfirst($employee->status) }}</small></div></div>
                <div class="sb-gown-row"><form method="POST" action="{{ route('owner.employees.toggle', $employee) }}">@csrf @method('PATCH')<button class="sb-small-btn {{ $employee->status === 'active' ? 'sb-deactivate' : '' }}">{{ $employee->status === 'active' ? 'Deactivate employee' : 'Reactivate employee' }}</button></form></div>
            </section>
            <section class="sb-panel">
                <div class="sb-panel-head"><div><h2>Work activity</h2><p>Recorded handoffs, returns, cleaning, maintenance, and payment work</p></div><span class="sb-pill">{{ $activities->total() }} activities</span></div>
                <div class="sb-table-wrap"><table class="sb-table"><thead><tr><th>DATE</th><th>ACTIVITY</th><th>DETAILS</th></tr></thead><tbody>
                    @forelse($activities as $activity)
                        <tr><td>{{ $activity['date']?->format('M d, Y h:i A') ?? '—' }}</td><td><strong>{{ $activity['action'] }}</strong></td><td>{{ $activity['description'] }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="sb-empty">No recorded work activity for this employee yet.</td></tr>
                    @endforelse
                </tbody></table></div>
                @include('components.table-pagination', ['paginator' => $activities, 'itemLabel' => 'activities'])
            </section>
        </div>
    </div></div>
</x-app-layout>
