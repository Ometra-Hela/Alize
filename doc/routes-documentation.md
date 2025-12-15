# Routes Documentation

This package registers its routes automatically via `Ometra\HelaAlize\HelaAlizeServiceProvider`.

## Configuration
Routes are defined in `routes/alize.php` and configured in `config/alize.php`.

- **Prefix**: Configurable via `route_prefix` (default: `alize`).
- **Middleware**: Applies the standard `api` middleware group.

## Route List

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| POST | `/{prefix}/` | N/A | `SoapController@handle` | Main entry point for incoming NUMLEX SOAP messages. |

## Customization
To customize the route prefix, update your `.env` file:
```dotenv
ALIZE_ROUTE_PREFIX=my-custom-prefix
```

The resulting URL for the NUMLEX webhook configuration would be:
`https://your-domain.com/my-custom-prefix`
