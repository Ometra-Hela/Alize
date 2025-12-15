# API Documentation

> **Note**: This package primarily operates via **SOAP (XML)** interactions with NUMLEX and internal PHP Service calls (`HelaAlize` Facade). It exposes a single HTTP endpoint for receiving async SOAP responses/notifications from NUMLEX.

## Endpoints

### 1. NUMLEX SOAP Webhook

**POST** `/{prefix}/`

Handles incoming SOAP messages from the NUMLEX Central Database (ABD). This endpoint expects raw XML body content adhering to the NUMLEX XSD schema.

- **URL Prefix**: Configurable via `ALIZE_ROUTE_PREFIX` (default: `alize`)
- **Controller**: `Ometra\HelaAlize\Http\Controllers\SoapController@handle`
- **Middleware**: `api`

#### Request
- **Headers**:
  - `Content-Type`: `text/xml` or `application/xml`
- **Body**: Raw XML content (`<NPCData>...`)

#### Supported Message Types
The controller automatically routes messages based on the XML content to the appropriate flow handler:
- `1005` (Ready to Schedule) -> `PortationFlowHandler`
- `1007` (Scheduled) -> `PortationFlowHandler`
- `3004` (Cancelled) -> `CancellationFlowHandler`
- `2002` (NIP Response) -> `NipFlowHandler`
- `4002` (Reversion Response) -> `ReversionFlowHandler`

#### Response
- **200 OK**: Message processed successfully.
- **400 Bad Request**: Invalid XML or unsupported message type.
- **500 Internal Server Error**: Processing failure.

---

## Internal PHP API (Facade)

The package provides a fluent PHP API via the `HelaAlize` facade for the host application to initiate actions.

### `HelaAlize::initiate(array $data)`
Starts a new portability process (1001).

```php
use Ometra\HelaAlize\Facades\HelaAlize;

$portability = HelaAlize::initiate([
    'dida' => '001',
    'dcr' => '09',
    'numbers' => [['start' => '5512345678', 'end' => '5512345678']],
    // ...
]);
```

### `HelaAlize::requestNip(string $dn, string $dida)`
Requests a NIP for a number (2001).

### `HelaAlize::cancel(Portability $portability, string $reason)`
Cancels an ongoing portability process (3001).
