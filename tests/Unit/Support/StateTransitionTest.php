<?php

namespace Ometra\HelaAlize\Tests\Unit\Support;

use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Exceptions\InvalidTransitionException;
use Ometra\HelaAlize\Support\StateTransition;
use Ometra\HelaAlize\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StateTransitionTest extends TestCase
{
    private StateTransition $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new StateTransition();
    }

    #[Test]
    public function it_allows_valid_transition_from_initial_to_port_requested()
    {
        $this->assertTrue(
            $this->validator->isAllowed(
                PortabilityState::INITIAL,
                PortabilityState::PORT_REQUESTED,
            ),
        );
    }

    #[Test]
    public function it_allows_valid_transition_from_port_requested_to_ready()
    {
        $this->assertTrue(
            $this->validator->isAllowed(
                PortabilityState::PORT_REQUESTED,
                PortabilityState::READY_TO_BE_SCHEDULED,
            ),
        );
    }

    #[Test]
    public function it_rejects_invalid_transition()
    {
        $this->assertFalse(
            $this->validator->isAllowed(
                PortabilityState::INITIAL,
                PortabilityState::PORTED,
            ),
        );
    }

    #[Test]
    public function it_rejects_transition_from_terminal_state()
    {
        $this->assertFalse(
            $this->validator->isAllowed(
                PortabilityState::PORTED,
                PortabilityState::PORT_SCHEDULED,
            ),
        );

        // Except reversal request
        $this->assertTrue(
            $this->validator->isAllowed(
                PortabilityState::PORTED,
                PortabilityState::REVERSAL_REQUESTED,
            ),
        );
    }

    #[Test]
    public function it_allows_cancellation_from_valid_states()
    {
        $validStates = [
            PortabilityState::PORT_REQUESTED,
            PortabilityState::READY_TO_BE_SCHEDULED,
            PortabilityState::PORT_SCHEDULED,
        ];

        foreach ($validStates as $state) {
            $this->assertTrue(
                $this->validator->isAllowed($state, PortabilityState::CANCELLED),
                "Should allow cancellation from {$state->value}",
            );
        }
    }

    #[Test]
    public function it_throws_exception_on_invalid_transition()
    {
        $this->expectException(InvalidTransitionException::class);
        $this->expectExceptionMessage('Invalid state transition');

        $this->validator->validateOrFail(
            PortabilityState::INITIAL,
            PortabilityState::PORTED,
        );
    }

    #[Test]
    public function it_returns_allowed_next_states()
    {
        $allowedStates = $this->validator->getAllowedNextStates(
            PortabilityState::PORT_REQUESTED,
        );

        $this->assertContains(PortabilityState::READY_TO_BE_SCHEDULED, $allowedStates);
        $this->assertContains(PortabilityState::CANCELLED, $allowedStates);
        $this->assertContains(PortabilityState::REJECTED, $allowedStates);
    }
}
