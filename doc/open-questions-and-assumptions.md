# Open Questions & Assumptions

## Deployment
- **Assumption**: Host application is running Laravel Scheduler.
  - *Context*: Required for `CheckPortabilityTimers` and reconciliation.
- **Assumption**: Required PHP extensions (`soap`, `ssh2`) are available in the production environment.

## Configuration
- **Question**: Is `ALIZE_IDA_CODE` dynamic or static per environment? 
  - *Current*: Config defaults to env variable.
- **Question**: Are `tls.cert_path` and `key_path` relative or absolute?
  - *Current*: Documentation assumes absolute paths.

## Attachments (Personas Morales)
- **Question**: Where are generated PDF attachments stored temporarily?
  - *Assumption*: Uses system temporary directory before SOAP transmission.

## Legacy Code
- **Note**: Some cleanup has been performed to remove dependency on `App\` namespace, but integration testing with the host application is recommended to ensure no implicit bindings remain.
