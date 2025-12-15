<?php

namespace Ometra\HelaAlize\Tests\Unit\Services;

use Mockery;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Orchestration\PortationFlowHandler;
use Ometra\HelaAlize\Services\HelaAlizeService;
use PHPUnit\Framework\TestCase;

class HelaAlizeServiceTest extends TestCase
{
    /** @test */
    public function it_initiates_portation_via_handler()
    {
        $mockFlowHandler = Mockery::mock(PortationFlowHandler::class);
        $mockCancellationHandler = Mockery::mock(\Ometra\HelaAlize\Orchestration\CancellationFlowHandler::class);
        $mockOrchestrator = Mockery::mock(\Ometra\HelaAlize\Orchestration\StateOrchestrator::class);

        $service = new HelaAlizeService($mockFlowHandler, $mockCancellationHandler, $mockOrchestrator);

        $data = ['dida' => '123'];
        $expected = new Portability();

        $mockFlowHandler->shouldReceive('initiatePortation')
            ->once()
            ->with($data)
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
