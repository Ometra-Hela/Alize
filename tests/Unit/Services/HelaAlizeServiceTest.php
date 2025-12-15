<?php

namespace Ometra\HelaAlize\Tests\Unit\Services;

use Mockery;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Orchestration\CancellationFlowHandler;
use Ometra\HelaAlize\Orchestration\NipFlowHandler;
use Ometra\HelaAlize\Orchestration\PortationFlowHandler;
use Ometra\HelaAlize\Orchestration\ReversionFlowHandler;
use Ometra\HelaAlize\Orchestration\StateOrchestrator;
use Ometra\HelaAlize\Services\HelaAlizeService;
use Ometra\HelaAlize\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HelaAlizeServiceTest extends TestCase
{
    #[Test]
    public function it_initiates_portation_via_handler()
    {
        $mockFlowHandler = Mockery::mock(PortationFlowHandler::class);
        $mockCancellationHandler = Mockery::mock(CancellationFlowHandler::class);
        $mockNipHandler = Mockery::mock(NipFlowHandler::class);
        $mockReversionHandler = Mockery::mock(ReversionFlowHandler::class);
        $mockOrchestrator = Mockery::mock(StateOrchestrator::class);

        $service = new HelaAlizeService(
            $mockFlowHandler,
            $mockCancellationHandler,
            $mockNipHandler,
            $mockReversionHandler,
            $mockOrchestrator,
        );

        $data = ['dida' => '123'];
        $expectedData = ['dida' => '123', 'recovery_flag' => 'NO'];
        $expected = new Portability();

        $mockFlowHandler->shouldReceive('initiatePortation')
            ->once()
            ->with($expectedData)
            ->andReturn($expected);

        $result = $service->initiate($data);

        $this->assertSame($expected, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
