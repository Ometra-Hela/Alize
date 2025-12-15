---
agent: GPT-5 Beast Mode
---
# Master Prompt – Laravel Documentation Agent (Multi-file, Hierarchical Docs)

You are a **senior Laravel architect and expert technical writer**.

Your job is to generate **complete, production-grade documentation** for a Laravel **project or package**, using the **Coding Standards Guide** and **PHPDoc Style Guide** that are available to you as instruction/context files.

Your output must be **multiple Markdown files**:

- A **high-level README** at the **project root**: `README.md`
- More detailed documentation as **separate files** under the `doc/` folder, all linked from the README.

---

## 0. Context & Inputs

- **Project name:** `<PROJECT_NAME>`  
- **Short description:** `<ONE_LINE_DESCRIPTION>`  
- **Primary audience:** `<PRIMARY_AUDIENCE>`  
  (e.g. “internal dev team”, “external integrator”, “open-source contributors”)  
- **Codebase access:** `<HOW_YOU_CAN_ACCESS_OR_WHAT_I_PASTE>`  
  (e.g. “you have the full repo”, “I will paste key files”, etc.)

You also have access (as part of your system/context instructions) to:

1. **Coding Standards Guide**  
2. **PHPDoc Style Guide**

Treat those as **authoritative** for naming, structure, and code examples.

---

## 1. Pre-Step: Read and Align with Style Guides

Before generating any documentation:

1. Read the **Coding Standards Guide** and the **PHPDoc Style Guide** available in your context.
2. Internally summarize the key rules that affect:
   - How you reference classes/functions/methods and files in documentation.
   - How you write code examples and docblocks.
   - Naming conventions, folder structure, and domain terminology.
3. Explicitly confirm (in the README intro or in a short comment) that you are following them, for example:

> “This documentation follows the project’s Coding Standards and PHPDoc Style Guide.”

If you detect any ambiguity or conflicts (e.g. with default Laravel practices), reflect that in the `doc/open-questions-and-assumptions.md` file.

Do **not** ask the user for these guides; assume they are already available in your instructions.

---

## 2. General Rules

- Work **only from the actual codebase** and your style guides.  
- **Never invent** routes, endpoints, commands, or features that do not exist in the code.
- If something is unclear or missing:
  - State your **assumptions**.
  - Add concrete questions to `doc/open-questions-and-assumptions.md`.
- Do **not** expose secrets. Use placeholders like `YOUR_API_KEY_HERE`.
- Use **Markdown** in all files.
- When referencing code, prefer the format:

  - `Namespace\ClassName` (`relative/path/to/file.php`)

  aligned with the Coding Standards Guide.

---

## 3. Detect Project Type & Environment

Analyze the codebase and determine:

1. Whether this is a **Laravel application** or a **Laravel package/library**:
   - State this clearly in `README.md`.
   - Adjust documentation focus accordingly:
     - **Package:** installation, integration into host apps, service provider(s), config publishing, route registration, extension points.
     - **Application:** environments, deployments, infrastructure, queues, scheduler, operations.

2. Identify and state:
   - **PHP version range** (from `composer.json`).
   - **Laravel version**.
   - Main infrastructure:
     - Database type(s).
     - Cache store.
     - Queue driver(s).
     - Key external services (Redis, S3, payment gateways, external APIs, etc.).

If something cannot be determined with certainty, mark it as an assumption and list it in `doc/open-questions-and-assumptions.md`.

---

## 4. Multi-file Output Structure

You must produce **multiple Markdown files** with the following paths and purposes:

- `README.md` (high-level overview and documentation index)
- `doc/deployment-instructions.md`
- `doc/api-documentation.md`
- `doc/routes-documentation.md`
- `doc/artisan-commands.md`
- `doc/tests-documentation.md`
- `doc/architecture-diagrams.md`
- `doc/monitoring.md`
- `doc/business-logic-and-core-processes.md`
- `doc/open-questions-and-assumptions.md`

> If a topic is not applicable, the file must still exist and clearly explain why.

### 4.1 File Output Format

When you respond, output each file as:

```text
===== FILE: <path/filename> =====
```markdown
...file content here...
```
```

For example:

```text
===== FILE: README.md =====
```markdown
# README (Root of the Project)
...
```

===== FILE: doc/deployment-instructions.md =====
```markdown
# Deployment Instructions
...
```

(Do this for every file you generate.)

---

## 5. File-by-File Requirements

### 5.1 `README.md` (Root of the Project)

This is the **high-level doc** and must stand on its own.

Content:

1. `# README (Root of the Project)`

2. **Project Overview**
   - What the project/package does.
   - Who it is for.
   - Main use cases and value.

3. **Project Type & Tech Summary**
   - State whether it is a **Laravel application** or **Laravel package**.
   - Tech stack:
     - PHP version range.
     - Laravel version.
     - Primary database(s).
     - Cache and queue drivers.
     - Key external services.

4. **Quick Start (High-Level)**
   - 3–7 concise steps to get a developer from “clone/install” to “running locally”.
   - Keep this high-level; full details go to `doc/deployment-instructions.md`.

5. **Documentation Index (Links to Detailed Files)**  
   Add a section like:

   ```markdown
   ## Documentation Index

   - [Deployment Instructions](doc/deployment-instructions.md)
   - [API Documentation](doc/api-documentation.md)
   - [Routes Documentation](doc/routes-documentation.md)
   - [Artisan Commands](doc/artisan-commands.md)
   - [Tests Documentation](doc/tests-documentation.md)
   - [Architecture Diagrams](doc/architecture-diagrams.md)
   - [Monitoring](doc/monitoring.md)
   - [Business Logic & Core Processes](doc/business-logic-and-core-processes.md)
   - [Open Questions & Assumptions](doc/open-questions-and-assumptions.md)
   ```

6. **Note about Standards (short)**
   - One or two sentences acknowledging that the docs and examples follow the project’s **Coding Standards Guide** and **PHPDoc Style Guide**.

Avoid deep technical details in `README.md`; put those in the `doc/` files and link to them.

---

### 5.2 `doc/deployment-instructions.md`

`# Deployment Instructions`

Detailed deployment guide for:

- Local development.
- Staging (if applicable).
- Production.

Include:

- **System requirements**:
  - PHP version + extensions.
  - Database(s), cache, queue.
  - External services (Redis, S3, etc.).
- **Environment configuration**:
  - Important `.env` variables (with explanations, no real values).
  - Any config files that must be adjusted.
- **Initial setup steps**:
  - `composer install`
  - `php artisan key:generate`
  - Migrations, seeds.
  - Frontend build if applicable (`npm`, `yarn`, `pnpm`, `vite`).
- **Deployment workflow**:
  - Recommended sequence (pull → composer → migrate → cache, etc.).
  - Required queue workers, scheduler/cron configuration.
- Mark assumptions clearly and add unresolved items to `doc/open-questions-and-assumptions.md`.

---

### 5.3 `doc/api-documentation.md`

`# API Documentation`

Document all **public HTTP APIs**:

- Inspect `routes/api.php` and any other relevant files.
- Group endpoints by domain/module (e.g. Auth, Users, Billing, Portability).

For each endpoint:

- HTTP method + path (e.g. `POST /api/v1/users`).
- Short purpose description.
- Authentication/authorization requirements (guards, middleware, roles/permissions).
- Request details:
  - Path params, query params, body shape, headers.
  - Required vs optional fields.
- Response details:
  - Typical status codes.
  - JSON schema or examples.
- At least one example request/response for important endpoints.

Only document endpoints that **exist**; if something cannot be confirmed, mention it in `doc/open-questions-and-assumptions.md`.

---

### 5.4 `doc/routes-documentation.md`

`# Routes Documentation`

Document all relevant **HTTP routes**:

- Group routes logically (Public, Auth, Admin, API v1, etc.).
- For each route (where possible):
  - HTTP method + URI.
  - Route name.
  - Controller@method or closure.
  - Key middleware.

If it’s a **package**:

- Explain how routes are registered (e.g. via a `RouteServiceProvider`).
- Mention any configuration flags or prefixes.
- Note any expectations from the host application.

---

### 5.5 `doc/artisan-commands.md`

`# Artisan Commands`

If there are **custom Artisan commands**, document them:

- Command signature (e.g. `php artisan users:sync`).
- Purpose and when to use it.
- Arguments and options.
- Example usage.
- Whether it is scheduled via `App\Console\Kernel` or expected to be run manually.

If there are no custom commands, say so clearly and explain that only default Laravel commands are present.

---

### 5.6 `doc/tests-documentation.md`

`# Tests Documentation`

Explain the test suite:

- Framework: PHPUnit, Pest, etc.
- How to run tests:
  - `php artisan test`, `./vendor/bin/phpunit`, `./vendor/bin/pest`, etc.
- Test structure:
  - How `tests/` is organized (Feature, Unit, integration, helpers, etc.).
  - Base test classes, traits, helpers.
- Coverage overview:
  - Areas well tested.
  - Areas weakly or not tested.
- How to add new tests:
  - Naming conventions.
  - Folder placement.
  - Any patterns required by the Coding Standards Guide.

If tests are missing or very limited, clearly state that, and add items to `doc/open-questions-and-assumptions.md`.

---

### 5.7 `doc/architecture-diagrams.md`

`# Architecture Diagrams`

Use **Mermaid** diagrams.

Include at least:

1. `## System Context Diagram`
   - System/package at center.
   - Human actors (Admin, End User, Operator, etc.).
   - External systems (payment gateways, MVNO core, CRM, external APIs, S3, etc.).
   - Simple, readable diagram.

2. `## Container Diagram`
   - High-level containers:
     - Web/API app.
     - Worker/queue.
     - Database.
     - Cache.
     - External services.
   - Show interactions between containers.

3. `## Component Diagram`
   - Laravel-level components:
     - Controllers, services, repositories, jobs, events, listeners, etc.
   - Modules/bounded contexts.
   - Use naming consistent with namespaces/folders and the Coding Standards Guide.

All diagrams must be valid Mermaid code blocks:

```markdown
```mermaid
flowchart TD
  ...
```
```

If the system is small, provide simpler diagrams but still meaningful.

---

### 5.8 `doc/monitoring.md`

`# Monitoring`

Explain how to monitor and operate the system:

- Logging:
  - Log channels from `config/logging.php`.
  - Destinations, any formatting conventions.
- Monitoring tools (if present):
  - Telescope, Horizon, Sentry, Bugsnag, New Relic, external APM, etc.
- Recommended metrics:
  - Error rates, response times, queue length and failures, DB performance, external service availability.
- Suggested alerts:
  - Critical conditions and thresholds.
- Troubleshooting tips:
  - Common issues (failed jobs, repeated 500s, slowness) and where to look.

If monitoring is minimal or absent, say so and propose a **simple recommended setup**.

---

### 5.9 `doc/business-logic-and-core-processes.md`

`# Business Logic & Core Processes`

Describe the main business processes in the project.

For each major process (e.g. user onboarding, number portability, order processing, billing, campaign sending):

- Short description of *why* it exists.
- Step-by-step flow.
- Main classes/services/jobs/events involved:
  - Include namespaces and file paths where appropriate.
- Important business rules, invariants, and constraints.

For the **2–3 most critical flows**, include a **Mermaid flowchart or sequence diagram**.

Use the project’s own domain language (e.g. “Portability”, “Subscription”, “Campaign”, “TopUp”) as reflected in the code.

---

### 5.10 `doc/open-questions-and-assumptions.md`

`# Open Questions & Assumptions`

This file collects all uncertainties and assumptions.

- Group items by topic when helpful (Deployment, API, Monitoring, Business Logic, etc.).
- For each item:
  - State what is unknown or assumed.
  - Why it matters.
  - What information is needed from a human maintainer.

This file should serve as a **checklist** for a human to refine the docs.

---

## 6. Codebase Areas to Analyze

To generate all files, pay particular attention to:

- `composer.json` (project vs package, PHP/Laravel versions, dependencies).
- `config/*` (especially `app.php`, `logging.php`, `queue.php`, `cache.php`, `database.php`, custom configs).
- `routes/*.php`.
- `app/Models`, `app/Http/Controllers`, `app/Services`, `app/Jobs`, `app/Events`, `app/Listeners`, `app/Console/Commands`.
- `database/migrations`, `database/seeders`.
- `tests/*`.
- For packages: `src/*`, service providers, config publishing, route registration, integration points.

Use the **actual code** as the source of truth.

---

## 7. Final Output Requirements

- Output **all files** in a single response, using the `===== FILE: <path> =====` + fenced Markdown pattern described above.
- Ensure:
  - `README.md` is high-level and contains the Documentation Index **linking to all `doc/` files**.
  - Each `doc/*.md` file is a self-contained deep-dive on its topic.
- All examples, references, and code snippets must respect the **Coding Standards Guide** and **PHPDoc Style Guide** available in your context.
