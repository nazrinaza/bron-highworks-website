<?php

namespace Tests\Feature;

use App\Models\DroneOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpsPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_operations_users_can_open_ops_and_pilots_only_see_assigned_work(): void
    {
        $pilot = User::factory()->pilot()->create();
        $otherPilot = User::factory()->pilot()->create();
        $assigned = DroneOperation::factory()->create(['assigned_pilot_id' => $pilot->id]);
        $other = DroneOperation::factory()->create(['assigned_pilot_id' => $otherPilot->id]);

        $this->get('/ops')->assertRedirect('/admin/login');
        $this->actingAs($pilot)->get('/ops')->assertOk()->assertSee($assigned->reference)->assertDontSee($other->reference);
        $this->get(route('ops.operations.show', $assigned))->assertOk();
        $this->get(route('ops.operations.show', $other))->assertForbidden();

        $pilot->update(['status' => 'suspended']);
        $this->actingAs($pilot->fresh())->get('/ops')->assertForbidden();
    }

    public function test_admin_can_create_suspend_activate_and_soft_delete_a_pilot(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('ops.pilots.store'), [
            'name' => 'Remote Pilot One',
            'email' => 'pilot@example.test',
            'phone' => '0191234567',
            'pilot_licence_no' => 'RCOC-B-1001',
            'pilot_licence_expires_at' => today()->addYear()->toDateString(),
            'password' => 'PilotPassword123',
            'password_confirmation' => 'PilotPassword123',
        ])->assertSessionHasNoErrors();

        $pilot = User::query()->where('email', 'pilot@example.test')->firstOrFail();
        $this->assertTrue($pilot->isPilot());
        $this->patch(route('ops.pilots.status', $pilot), ['status' => 'suspended'])->assertSessionHasNoErrors();
        $this->assertSame('suspended', $pilot->fresh()->status);
        $this->patch(route('ops.pilots.status', $pilot), ['status' => 'active'])->assertSessionHasNoErrors();
        $this->delete(route('ops.pilots.destroy', $pilot))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($pilot);
    }

    public function test_release_gate_requires_approved_plans_go_preflight_and_signed_authorisation(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $pilot = User::factory()->pilot()->create();
        $operation = DroneOperation::factory()->create([
            'created_by_id' => $admin->id,
            'assigned_pilot_id' => $pilot->id,
        ]);
        $this->actingAs($admin);

        $risk = [
            'title' => 'Facade cleaning risk assessment',
            'assessment_date' => today()->toDateString(),
            'description' => 'Site-specific assessment.',
            'participants' => [['name' => 'Pilot One', 'job_title' => 'Remote pilot']],
            'hazards' => [[
                'phase' => 'Cleaning pass',
                'hazard' => 'Hose snag',
                'consequence' => 'Loss of control',
                'causal_factor' => 'Unexpected obstacle',
                'controls' => 'Observer and controlled hose routing',
                'likelihood' => 2,
                'severity' => 'C',
            ]],
            'reviewed_by' => 'Operations manager',
            'accepted_by' => 'Accountable manager',
            'action' => 'approve',
        ];
        $this->put(route('ops.operations.risk', $operation), $risk)->assertSessionHasNoErrors();

        $plan = [
            'activity' => 'Facade cleaning',
            'location' => 'Kuala Lumpur',
            'operating_date' => today()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'crew' => 'PIC, visual observer and pump operator',
            'uas_model' => 'Joyance JTC30',
            'uas_serial' => 'JTC30-001',
            'weather' => 'Dry with wind within operating limits',
            'threats' => 'Building edge, hose and pedestrian access',
            'highest_obstacle_m' => '60',
            'airspace_limit_m' => '90',
            'notam' => 'Checked before flight',
            'flight_planning' => 'Defined boundary, cleaning passes and hose route',
            'emergency_plan' => 'Abort, isolate pump and land in the nominated area',
            'filled_by' => 'Remote Pilot One',
            'checked_by' => 'Operations Manager',
            'action' => 'approve',
        ];
        $this->put(route('ops.operations.flight-plan', $operation), $plan)->assertSessionHasNoErrors();

        $incompletePreflight = $this->preflightData();
        unset($incompletePreflight['items']['weather']);
        $this->put(route('ops.operations.preflight', $operation), $incompletePreflight)->assertSessionHasErrors('items');
        $this->put(route('ops.operations.preflight', $operation), $this->preflightData())->assertSessionHasNoErrors();
        $this->assertFalse($operation->fresh()->isReadyForRelease());

        $this->put(route('ops.operations.authorisation', $operation), [
            'representative_name' => 'Customer Representative',
            'designation' => 'Building Manager',
            'company' => 'Customer Sdn Bhd',
            'identity_reference' => 'REF-100',
            'work_area' => 'North facade and controlled ground area',
            'permitted_hours' => '09:00 to 17:00',
            'known_hazards' => 'Public access at ground level',
            'signed_at' => today()->toDateString(),
            'signed_agreement' => UploadedFile::fake()->create('signed-authorisation.pdf', 20, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $operation->refresh();
        $this->assertTrue($operation->isReadyForRelease());
        $this->assertSame('ready', $operation->status);
        Storage::disk('local')->assertExists($operation->agreement_path);
        $this->get(route('ops.operations.agreement', $operation))->assertOk();
    }

    public function test_digital_logbook_calculates_a_cross_midnight_flight_and_preserves_pilot_identity(): void
    {
        $admin = User::factory()->admin()->create();
        $pilot = User::factory()->pilot()->create();
        $operation = DroneOperation::factory()->create([
            'created_by_id' => $admin->id,
            'assigned_pilot_id' => $pilot->id,
        ]);

        $this->actingAs($pilot)->post(route('ops.operations.flight-logs.store', $operation), [
            'flight_date' => '2026-10-27',
            'task' => 'Night façade cleaning test',
            'approval_category' => 'Authorised operation',
            'client' => 'Customer Sdn Bhd',
            'uas_model' => 'Joyance JTC30',
            'uas_serial' => 'JTC30-002',
            'location' => 'Kuala Lumpur',
            'observer' => 'Observer One',
            'takeoff_time' => '23:50',
            'landing_time' => '00:20',
            'remarks' => 'Normal operation',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('flight_log_entries', [
            'drone_operation_id' => $operation->id,
            'pilot_id' => $pilot->id,
            'total_minutes' => 30,
        ]);
        $log = $operation->flightLogs()->firstOrFail();
        $this->assertSame('2026-10-28', $log->landing_at->toDateString());
    }

    public function test_operations_manual_and_authorisation_template_require_an_operations_user(): void
    {
        $this->get(route('ops.manual.download'))->assertRedirect('/admin/login');
        $this->get(route('ops.authorisation-template'))->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->pilot()->create());
        $this->get(route('ops.manual.download'))->assertDownload('SERI_NS_Complete_Drone_Operations_Manual.docx');
        $this->get(route('ops.authorisation-template'))->assertDownload('BRON_Property_Access_and_Work_Authorisation.pdf');
    }

    /** @return array<string, mixed> */
    private function preflightData(): array
    {
        return [
            'weather_notes' => 'Weather within limits.',
            'temperature' => '30 C',
            'wind' => '5 kt',
            'visual_observer' => 'Observer One',
            'aircraft_id' => 'JTC30-001',
            'mission' => 'Facade cleaning',
            'items' => array_fill_keys(array_keys(DroneOperation::PREFLIGHT_ITEMS), '1'),
            'decision' => 'go',
            'notes' => 'Crew briefed.',
        ];
    }
}
