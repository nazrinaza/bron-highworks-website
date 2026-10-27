@extends('layout')
@section('title','Drone operations')
@section('intro')<p>Plan, approve and record each cleaning operation from risk assessment through pilot logbook.</p>@endsection
@section('content')
<div class="stats ops-stats">
    <div class="panel"><span>Planning</span><strong>{{ $counts['planning'] ?? 0 }}</strong></div>
    <div class="panel"><span>Ready / active</span><strong>{{ ($counts['ready'] ?? 0) + ($counts['in_progress'] ?? 0) }}</strong></div>
    <div class="panel"><span>Completed</span><strong>{{ $counts['completed'] ?? 0 }}</strong></div>
    @if($activePilots !== null)<div class="panel"><span>Active pilots</span><strong>{{ $activePilots }}</strong></div>@endif
</div>
<div class="actions">@can('manage-operations-users')<a class="button" href="{{ route('ops.operations.create') }}">Create operation</a>@endcan<a href="{{ route('ops.manual') }}">Open operations manual</a></div>
<div class="panel table-wrap">
<table><thead><tr><th>Operation</th><th>Site / customer</th><th>Pilot</th><th>Schedule</th><th>Workflow</th><th>Status</th></tr></thead><tbody>
@forelse($operations as $operation)<tr>
<td><a href="{{ route('ops.operations.show',$operation) }}">{{ $operation->reference }}</a><small>{{ $operation->title }}</small></td>
<td>{{ $operation->customer_name }}<small>{{ $operation->site_address }}</small></td>
<td>{{ $operation->assignedPilot?->name ?? 'Unassigned' }}</td>
<td>{{ $operation->scheduled_start?->format('d M Y H:i') ?? 'Not scheduled' }}</td>
<td><div class="progress"><span style="width:{{ $operation->completedWorkflowSteps()*20 }}%"></span></div><small>{{ $operation->completedWorkflowSteps() }}/5 records complete</small></td>
<td><span class="badge status-{{ $operation->status }}">{{ str_replace('_',' ',ucfirst($operation->status)) }}</span></td>
</tr>@empty<tr><td colspan="6">No drone operations yet.</td></tr>@endforelse
</tbody></table></div>{{ $operations->links('pagination') }}
@endsection
