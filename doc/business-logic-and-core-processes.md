# Business Logic & Core Processes

## 1. Portability Initiation Flow (Outbound)

**Goal**: Move a number from another carrier (Donor) to us (Recipient).

1. **Initiate (1001)**:
   - User provides Number (DN) and Donor ID (DIDA).
   - Package sends `1001` message via SOAP.
   - Status: `REGISTERED` (0).
   
2. **Timer T1 (Customer Confirmation)**:
   - Started upon initiation.
   - Window for customer to confirm via NIP/SMS.
   - **Validation**: `CheckPortabilityTimers` job.
   - If expired: Portability is Cancelled.

3. **Scheduled (1007)**:
   - ABD confirms customer approval.
   - Status updates to `SCHEDULED` (3).
   - **Timer T4 (Cancellation Window)** starts (typically until 14:00 D-1).

4. **Execution**:
   - On the `port_exec_date`, the number becomes active on our network.
   - Status updates to `PORTED` (5).

## 2. Cancellation Flow

**Goal**: Stop an ongoing portability process before it executes.

1. **Validation**:
   - Can only cancel if **T4 Timer** has not expired.
   - `StateOrchestrator` checks `t4_expires_at`.

2. **Request (3001)**:
   - Sends `3001` message to NUMLEX.
   - Reason code required.

3. **Confirmation (3004)**:
   - ABD confirms cancellation.
   - Status updates to `CANCELLED`.

## 3. Daily Reconciliation

**Goal**: Ensure local database matches ABD truth.

1. **Download**: `ReconcileDailyFiles` downloads encrypted files via SFTP.
2. **Decrypt & Parse**: Processes the daily delta.
3. **Update**:
   - Updates status of local portabilities.
   - Marks completed portabilities as `PORTED`.
   - Flags discrepancies.

## Key Timers (ABD Specs)

| Timer | Description | Handling |
|-------|-------------|----------|
| **T1** | NIP Validity/Confirmation Window | Checked by `CheckPortabilityTimers` |
| **T3** | Scheduling Window (Admin action) | Host app notification via Events |
| **T4** | Cancellation Deadline | Checked by `StateOrchestrator` before Cancel |
