<?php

namespace Ometra\HelaAlize\Tests\Unit\Orchestration;

use Illuminate\Support\Facades\Event;
use Mockery;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Events\PortabilityStateChanged;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Orchestration\StateOrchestrator;
use Ometra\HelaAlize\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StateOrchestratorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function itDispatchesEventOnTransition()
    {
        Event::fake();

        $orchestrator = new StateOrchestrator();

        /** @var Portability $portability */
        $portability = Mockery::mock(Portability::class)->makePartial();
        $portability->port_id = 'PORT123';
        $portability->state = PortabilityState::INITIAL->value;
        $portability->shouldReceive('save')->once();

        // We need to bypass the StateTransition validation since flow is strict
        // But internal StateTransition is instantiated in constructor.
        // For unit test, we test valid transition: INITIAL -> PORT_REQUESTED

        $orchestrator->transition(
            $portability,
            PortabilityState::PORT_REQUESTED,
            'Testing'
        );

        Event::assertDispatched(PortabilityStateChanged::class, function ($event) {
            return $event->previousState === PortabilityState::INITIAL &&
                $event->newState === PortabilityState::PORT_REQUESTED;
        });
    }
}
