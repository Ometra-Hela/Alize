<?php

namespace Ometra\HelaAlize\Console\Commands;

use Illuminate\Console\Command;
use Ometra\HelaAlize\Facades\HelaAlize;
use Ometra\HelaAlize\Models\Portability;

/**
 * Test Full Flow Portability Command.
 *
 * Interactive tool to manually trigger NIP, Reversion, Cancellation flows.
 *
 * @package Ometra\HelaAlize\Console\Commands
 */
class TestFullFlowPortability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'numlex:test-full-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively test full portability flows (NIP, Reversion, Cancellation)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->alert("NUMLEX Full Flow Test Tool");

        $choice = $this->choice('Select Flow to Test', [
            '1. Request NIP (2001)',
            '2. Initiate Portability (1001)',
            '3. Cancel Portability (3001)',
            '4. Request Reversion (4001)',
            '5. Simulate Inbound Message',
        ]);

        try {
            match ($choice) {
                '1. Request NIP (2001)' => $this->testNip(),
                '2. Initiate Portability (1001)' => $this->call('numlex:test-initiate'),
                '3. Cancel Portability (3001)' => $this->testCancellation(),
                '4. Request Reversion (4001)' => $this->testReversion(),
                '5. Simulate Inbound Message' => $this->testInbound(),
                default => $this->error('Invalid choice'),
            };
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->line($e->getTraceAsString());
        }

        return 0;
    }

    private function testNip(): void
    {
        $dn = $this->ask('Number to request NIP for (10 digits)');
        $dida = $this->ask('DIDA (Donor Carrier ID)', '001');

        $this->info("Requesting NIP for $dn...");
        $result = HelaAlize::requestNip($dn, $dida);

        $this->info("Result: " . json_encode($result));
    }

    private function testCancellation(): void
    {
        $portId = $this->ask('Port ID to cancel');
        $reason = $this->ask('Cancellation Reason', 'User Request');

        $portability = Portability::where('port_id', $portId)->first();
        if (!$portability) {
            $this->error("Portability $portId not found.");

            return;
        }

        $this->info("Requesting Cancellation for $portId...");
        HelaAlize::cancel($portability, $reason); // Note: Service method returns void currently, might change
        $this->info("Cancellation Requested.");
    }

    private function testReversion(): void
    {
        $portId = $this->ask('Port ID to reverse');
        $reason = $this->ask('Reversion Reason', 'Error in process');

        $portability = Portability::where('port_id', $portId)->first();
        if (!$portability) {
            $this->error("Portability $portId not found.");

            return;
        }

        $this->info("Requesting Reversion for $portId...");
        $result = HelaAlize::requestReversion($portability, $reason);
        $this->info("Result: " . json_encode($result));
    }

    private function testInbound(): void
    {
        $this->info("Simulating inbound SOAP message processing...");
        $xml = $this->ask("Paste XML Content (or leave empty for a mock 2002)");

        if (empty($xml)) {
            $msisdn = $this->ask('MSISDN for Mock 2002', '5512345678');
            $xml = <<<XML
<NPCData xmlns="urn:npc:mx:np">
  <MessageHeader>
    <TransTimestamp>20250101120000</TransTimestamp>
    <Sender>001</Sender>
  </MessageHeader>
  <NPCMessage>
    <PinDeliveryConfirmMsg>
       <PortID>MOCK2002</PortID>
       <PhoneNumber>$msisdn</PhoneNumber>
       <ResultCode>0</ResultCode>
       <Pin>1234</Pin>
    </PinDeliveryConfirmMsg>
  </NPCMessage>
</NPCData>
XML;
        }

        // We need to invoke ProcessNpcMsgAction.
        // Mocking Request object.
        $request = \Illuminate\Http\Request::create('/soap', 'POST', [], [], [], [], $xml);

        $action = new \Ometra\HelaAlize\Classes\Soap\ProcessNpcMsgAction();
        $response = $action->execute($request);

        $this->info("Response Status: " . $response->getStatusCode());
        $this->info("Response Content: " . $response->getContent());
    }
}
