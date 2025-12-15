# Repository Coding Rules

---
applyTo: '**'
---

## Global
- **EditorConfig**: enforce LF endings, final newline, 4-space indentation.
- **Max line length**: 250 characters.
- **Filenames**: ASCII only, no spaces; `kebab-case` for non-PHP assets, `StudlyCase.php` for PHP classes.
- **Encoding**: UTF-8 without BOM.

### Top-of-file Header (canonical)
- **PHP files**: `<?php` **MUST be the very first bytes of the file** — no BOM, no whitespace, no comments before it.
- **Exactly one blank line after `<?php`** (even if there is **no header block**).
- If a header block is present, it sits after that single blank line and **may include internal blank lines**.
- **Exactly one blank line** after the header block before `namespace` (or first declaration).

**Good**
```php
<?php

/**
 * BaseAdapter for API integrations.
 *
 * Provides common HTTP client logic for API adapters, including header management,
 * body type selection, and Guzzle client instantiation. Used as a base for all API adapters.
 *
 * PHP version 8.2+
 *
 * @package   App\Classes\ApiAdapters
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace App\Classes\ApiAdapters;
```

**No header example**
```php
<?php

namespace App\Classes\Offers;
```

---

## PHP

### Standards & Tooling
- **Base standard**: PSR-12.
- **StyleCI**: `laravel` preset with `no_unused_imports` **disabled**.
- **Static analysis**: PHPStan level 8 (or project max), Larastan if Laravel present.
- **Formatter**: PHP-CS-Fixer aligned with rules below (run locally or in CI if desired).

### Language & Syntax
- **No `declare(strict_types=1);`** (disallowed).
- **Indentation**: 4 spaces. **Line endings**: LF. **Final newline**: required.
- **Named parameters**: **Prefer named arguments** where they improve readability or skip optionals.
  - **Must** use for calls with **≥ 3 params**, **boolean flags**, skipped optionals, or **non-obvious order**.
  - You may mix positional + named only when the positionals are the **leading required** params; once a named arg appears, **all following must be named**.
  - Avoid named args for **vendor APIs** whose parameter names may change; wrap them in an internal method if needed.
- **Closures**: **Prefer arrow functions** (`fn`) for single-expression callbacks; use `function (...) use (...) { ... }` if multiple statements, try/catch, by-ref capture, or readability requires it.
- **Parameters & Calls Formatting**
  - **If there are multiple parameters/arguments, put each on its own line.**  
    Applies to **function/method declarations**, **constructor property promotion**, **function/method calls**, and **`throw new`** with named args.
  - **If there is only one parameter/argument, a single line is OK.**
  - Indent one level (4 spaces) inside the parentheses; close parenthesis on its own line aligned with the opener.
  - **Trailing comma is required** after the last item in multi-line lists (PHP 8.2+).
- **Namespaces & imports**:
  - One `use` per line.
  - **Import source categories (precise definitions):**
    - **Framework**: `Illuminate\*`, `Laravel\*`, and built-in Laravel facades under `Illuminate\Support\Facades\*`.
    - **Third-party**: any Composer package **not** under your org namespace(s).
    - **First-party**: `App\*` and org-owned namespaces (e.g., `Gabo\*`).
  - **Order imports by source**, with **one blank line between groups** and **alphabetical inside each group**:
    1) **Framework**  
    2) **Third-party**  
    3) **First-party**
  - **Single-word tail rule**:
    - “Single-word” = root symbols with **no namespace**: `Exception`, `Throwable`, `Closure`, and **global class aliases** (e.g., `Storage` **only if** you intentionally use the global alias).  
    - Prefer **namespaced imports** for facades like `Illuminate\Support\Facades\Storage` unless you intentionally want the alias.  
    - Place all eligible single-word imports **after** the three groups (tail). Inside the tail, preserve framework → third-party → first-party order if it applies.
  - **Do not use fully-qualified class names (FQCNs) at call sites. Always import and use the short name.** If two classes share the same short name, alias one with `as`.
- **Classes**: `StudlyCase`. **Methods/vars**: `camelCase`. **Constants**: `SNAKE_CASE`.
- **Empty classes**: **must include a single commented placeholder line** inside the body (just `//` on its own line).
- **Visibility**: always declare (`public|protected|private`); no `var`.
- **Type declarations**: for params, returns, and properties wherever possible; prefer union/intersection/nullable where appropriate; avoid `mixed` unless truly required and documented.

### Constructors
- **Always** use **constructor property promotion** where possible.
- Empty constructor allowed with a single commented placeholder:
```php
public function __construct()
{
    //
}
```

### Spacing & Blank lines
- **Exactly one** blank line between **class properties** and **methods**.
- No extra leading/trailing blank lines beyond the header conventions; final newline required.

### Control Structures & Strings
- PSR-12 brace style; prefer early returns over deep nesting.
- **Strings**: single quotes by default; double quotes for interpolation/escape convenience.

### Documentation
- PHPDoc for all **public** methods and **non-trivial** protected/private methods.
- Omit redundant PHPDoc when types fully express intent.
- **Author tag** (where used): `Gabriel Ruelas <gruelas@gruelas.com>` on significant classes/entry points.

### Exceptions
- **Naming**: `StudlyCase` with the `Exception` suffix, e.g., `InvalidPaymentMethodException`.
- **Location**: place custom exceptions **inside the owning domain** (e.g., `app/Classes/Finance/Exceptions/InvalidPaymentMethodException.php`).
- **Use case**: throw for **illegal/unexpected** states (business rule violations). For expected outcomes, return a Result/DTO.
- **Body**: an empty body is acceptable; include a placeholder comment.
- **Constructor**: when needed, use **constructor property promotion** and **named parameters** at throw sites.
- **Throw formatting**: follow multi-argument formatting rules (one per line with trailing comma).

**Minimal example**
```php
<?php

namespace App\Classes\Payments\Exceptions;

use Exception;

class InvalidPaymentMethodException extends Exception
{
    //
}
```

**Usage**
```php
throw new InvalidPaymentMethodException(
    message: 'Unsupported payment method',
);
```

### Routing & builder-style APIs
- When passing a **closure argument** that contains a block (e.g., Laravel `Route::prefix(...)->group(...)`), use **multi-line call formatting** for readability **even if it’s a single argument**.
- **Exception to trailing comma rule**: for these single-closure calls, you **may omit** the trailing comma after the closure (preferred in routing files).

**Bad**
```php
Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1/index.php';
});
```

**Good**
```php
Route::prefix('v1')->group(
    function () {
        require __DIR__ . '/api/v1/index.php';
    }
);
```

### Assignment alignment (readability)
- Align **consecutive, related** assignments **within a block** only (don’t align across unrelated blocks).
```php
private int    $minLength   = 8;
private int    $maxLength   = 128;
private string $policyName  = 'default';
private bool   $mustContain = true;
```

### Examples
**Constructor (multiple: one per line)**
```php
public function __construct(
    private string $tokenFile,
    private string $apiKey,
    private string $apiUrl,
) {
    //
}
```

**HTTP call (multiple: one per line)**
```php
$response = $client->request(
    'POST',
    $url,
    [
        'headers' => [
            'Authorization' => 'Basic ' . $this->apiKey,
        ],
    ],
);
```

**Exception (named args, multiple: one per line)**
```php
throw new BadRequestException(
    message: 'Error Generating Token from Altan API',
    previous: $e,
);
```

**Single parameter (single line is OK)**
```php
$logger->info('Sync started');
```

**Named params & closures**
```php
$response = $client->sendRequest(
    method: 'POST',
    uri: $url,
    headers: $headers,
    body: $payload,
    timeout: 5.0,
);

$data = array_map(
    fn ($item) => str_starts_with($string, $item),
    explode(',', $string)
);

$data = array_map(
    function ($item) use ($string) {
        return str_starts_with($string, $item);
    },
    explode(',', $string)
);
```

---

## JavaScript
- **ES Modules** only (`import`/`export`).
- **Semicolons**: required.
- **Arrow functions** by default; `function` where `this` matters/constructors.
- **Variables**: `const` then `let`; no `var`.
- **Naming**: `camelCase` for vars/functions; `PascalCase` for classes/components.
- **Import order (mirrors PHP)**:
  1) **Framework** libs (e.g., React/Vue/Svelte, framework runtime)  
  2) **Third-party** packages  
  3) **First-party** app code (e.g., `@/…` or relative)  
  Add **one blank line** between groups.
- **Linting**: ESLint (`eslint:recommended` + `import`, `promise`, selected `unicorn`).
- **Formatting**: Prettier with `printWidth: 250`.
- **Types**: Prefer JSDoc or TypeScript in new code (if TS, `strict: true`).

---

## SCSS
- Import **Bootstrap first**, then custom partials.
- **Indentation**: 4 spaces; **max nesting depth**: 3.
- Use variables/mixins for colors/spacing/breakpoints; avoid magic numbers.
- Order: variables → mixins → base → components → utilities.
- Lint with Stylelint + SCSS plugin.

---

## Git & Repository Hygiene
- **.gitattributes**: `* text=auto eol=lf`; diff/linguist settings for lockfiles, vendor, images.
- **.gitignore**: build artifacts, IDE files, `vendor/`, `node_modules/`, caches.
- **Commits**: Conventional Commits; bodies explain **why**, not just **what**.
- **Branches**: `main` protected; features `feat/<topic>`, fixes `fix/<topic>`.
- **PRs**: At least one review; lint/tests/format should pass (if CI used).

---

## Laravel Project Structure (final)

```
app/
  Classes/         # REQUIRED: split into domain subfolders (e.g., Finance/, Ecommerce/, Offers/, GeoTools/, Numlex/, …)
  Console/
    Commands/
    Kernel.php
  Exports/         # Exporters (e.g., Laravel Excel), report builders
  Helpers/         # Small utilities (classes or namespaced functions)
  Imports/         # Data ingesters/parsers (CSV/JSON/Excel), ETL mappers
  Jobs/            # Queue jobs (thin; delegate to Classes/)
  Models/
  Policies/
  Http/
    Controllers/   # Thin; delegate to Classes/
    Requests/      # Validation
    Resources/     # API transformers
  Support/         # Cross-cutting infra (wrappers/adapters)
config/
database/
resources/
routes/
tests/
```

### **Rules for `app/Classes/`**
- **Purpose**: Central home of application/business logic. Controllers/jobs/commands **must** remain thin and delegate to **Classes**.
- **Organization (required)**: Create **one folder per domain** directly under `app/Classes` (e.g., `Finance/`, `Ecommerce/`, `Offers/`, `GeoTools/`, `Numlex/`, `Hooks/`, `Tools/`, `ApiAdapters/`, etc.).
- **Inside each domain**: No mandated internal sub-structure—organize as the domain needs (services, DTOs, repositories, clients, etc.), keeping single responsibility and discoverability.
- **DTOs & Result types**: place them **inside the relevant domain folder** (`app/Classes/<Domain>/…`).  
- **Enums & Value Objects**: define them **inside the domain that owns them**; only put truly shared VOs in a dedicated `Shared/` domain folder.
- **Namespaces**: `App\Classes\<Domain>\...`.
- **Dependencies**:
  - Explicit constructor dependencies (property promotion).
  - Small public methods; **prefer named parameters** at call sites.
  - For cross-domain collaboration, depend on **interfaces/contracts** or use **domain events**; avoid hard coupling to other domains’ concrete classes.

### Helpers autoload (if using function files)
- Add namespaced function files to `composer.json` → `autoload.files`, then run `composer dump-autoload`.

---

## (Optional) Config Snippets

**.editorconfig**
```
root = true

[*]
end_of_line = lf
insert_final_newline = true
charset = utf-8

[*.{php,js,ts,scss,css,json,yml,yaml,md}]
indent_style = space
indent_size = 4
```

**.prettierrc.json**
```json
{ "printWidth": 250, "tabWidth": 4, "semi": true, "singleQuote": true }
```

**.eslintrc.json (excerpt)**
```json
{
  "env": { "es2022": true, "browser": true, "node": true },
  "extends": ["eslint:recommended", "plugin:import/recommended"],
  "rules": {
    "no-var": "error",
    "prefer-const": "error",
    "eqeqeq": ["error", "always"]
  }
}
```

**.php-cs-fixer.php (excerpt)**
```php
<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__.'/app')->in(__DIR__.'/tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        // Note: off-the-shelf tools won't fully enforce vendor grouping or "single-word tail".
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order'  => ['class', 'function', 'const'],
        ],
        'no_unused_imports' => false,
        'single_blank_line_at_eof' => true,
        'class_attributes_separation' => [
            'elements' => ['const' => 'one', 'property' => 'one', 'method' => 'one'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['return', 'try', 'throw'],
        ],
    ])
    ->setFinder($finder);
```

**phpstan.neon (excerpt)**
```neon
parameters:
  level: max
  paths:
    - app
  ignoreErrors: []
```

**.stylelintrc.json**
```json
{
  "extends": ["stylelint-config-standard-scss"],
  "rules": {
    "max-nesting-depth": 3,
    "indentation": 4
  }
}
```

---

# Agent Operating Notes (for the AI applying this standard)

## Decision hierarchy
1) **Correctness > Readability > Consistency > Brevity**  
2) If rules clash, prefer the **more specific** one.  
3) In legacy files, prefer **minimal diffs** unless clarity would suffer.

## Named parameters — when to use
- **MUST** use when a call has **≥ 3 params**, **boolean flags**, you’re **skipping optionals**, or the **order is non-obvious**.  
- **MAY** mix positional + named only if positionals are the **leading required** params; once a named arg is used, **all following args are named**.  
- **AVOID** for vendor APIs whose parameter names are unstable; wrap them in your own method.

**Example**
```php
$service->sync(
    customerId: $id,
    full: false,
    retries: 2,
    backoffMs: 250,
);
```

## Closures — arrow functions vs traditional
- Use **arrow functions (`fn`)** for **single-expression** callbacks that don’t mutate outer scope.
- Use **closures (`function (...) use (...) { ... }`)** when you need **multiple statements**, **try/catch**, **early returns**, or **by-ref** captures.
- If an arrow body becomes long or nears 250 chars, switch to a closure.

## Imports — single-word tail clarified
- “Single-word” means **root symbols** (no namespace): `Exception`, `Throwable`, `Closure`, and global aliases (e.g., `Storage` if intentionally used).  
- Keep main groups **framework → third-party → first-party** (alphabetical in each), then place the **single-word tail last** (preserve framework→third-party→first-party order inside the tail if relevant).
- **No FQCNs at call sites**: Always import (`use`) the class or alias with `as`; only allow FQCNs in stringy contexts (configs/metadata), dynamic class names, or anonymous classes.

**Example**
```php
use Illuminate\Support\Facades\Log;  // Framework
use Carbon\CarbonImmutable;          // Third-party
use App\Classes\Finance\Invoicer;    // First-party
use Closure;                         // Tail
use Exception;                       // Tail
```

## Headers & spacing — edge cases
- Always **one** blank line after `<?php`; if **no header**, `namespace` follows next.  
- Header blocks may include internal blank lines; keep line length ≤ 250.
- **PHP files** must start with `<?php` as the first bytes (no BOM/whitespace/comments before it).

## Domain structure (app/Classes)
- **Required**: create **one folder per domain** directly under `app/Classes` (`Finance/`, `Ecommerce/`, `Offers/`, `GeoTools/`, `Numlex/`, …).
- Put **DTOs**, **Result** types, **Enums**, and **Value Objects** **inside the owning domain**; only truly shared VOs go in a `Shared/` domain folder.
- Cross-domain calls go through **interfaces/contracts** or **domain events**; avoid depending on other domains’ concretes (create an adapter locally if needed).

## Parameters & calls (formatting)
- Multi-parameter **one per line** with a **trailing comma**; single-parameter can be **single line**.
- Close `)` on its own line aligned to opener; 4-space indent inside parens.

## Controller/Job/Command recipe
1) Validate (Form Request / signature)  
2) Build **DTOs**  
3) Call **one** domain service (named args)  
4) Return **Resource/Result** (no business rules here)

## Pre-save checklist (fast pass)
- `<?php` is first bytes; then one blank line; header (if present) → one blank line → `namespace`.
- Imports: framework → third-party → first-party; **single-word tail last**.
- **Never** use FQCNs at call sites; always `use` the class (or alias). 
- Types everywhere; no unjustified `mixed`; **no** `strict_types`.
- Multi-parameter one-per-line **with trailing comma**; single-parameter single line OK.
- Prefer **named parameters** and **arrow functions** (fallback to closures when needed).
- Exactly one blank line between properties and methods.
- Empty classes include a single `//` placeholder comment in body.
- File lives under the correct domain: `app/Classes/<Domain>/…`.
- Line length ≤ 250; final newline present.



# PHPDoc Style Guide — Agent-Optimized (No emojis, no explicit property docs)

This guide is tuned for automated agents that need **deterministic rules**, **repair strategies**, and **ready-to-paste templates**. It minimizes ambiguity and encodes decisions an agent can apply consistently across a PHP 8.1+ codebase (Laravel-friendly).

Core constraint: **Do not document explicitly declared class properties** (no `@var` on declared props). If you use **constructor-promoted properties**, document them only via the **constructor DocBlock**.

---

## 0) Global Rules (MUST / SHOULD)

1. **MUST** use `/** … */` DocBlocks; never `/* … */` or `//` for documentation.
2. **MUST** document: **files** (File-Level DocBlock at top), **classes/traits/enums**, **methods/functions**, **constructors**.  
   **MUST NOT** document explicitly declared properties.  
   **MAY** document **magic/virtual** members via `@property`, `@property-read`, `@method`, `@mixin` when applicable.
3. **MUST** write **third-person present** (“Returns…”, “Creates…”, “Specifies…”).
4. **MUST** begin with a **single-sentence summary**. Optional one short paragraph description.  
   If description is present, **MUST** leave **one blank line** before tags.
5. **MUST** keep line length ≤ **120 chars**. Use **4-space hanging indent** for wrapped tag descriptions.
6. **MUST** use the **Tag Order** below.  
   `@template` → `@param` → `@return` → `@throws` → `@see`/`@link` → `@deprecated` → `@since` → tool-specific (`@psalm-*`, `@phpstan-*`).
7. **MUST** align `@param`/`@return`/`@throws` columns (types, names) in a block.
8. **MUST** fully document every parameter and `@return` (including `void`).
9. **MUST** document every escaping exception with `@throws` and a reason.
10. **MUST** use PHP native types in signatures; use PHPDoc for **semantics, shapes, generics**.
11. **MUST** use English.
12. **MUST NOT** restate the method name or signature in the summary.
13. **SHOULD** use `@return static` for fluent/factories returning the same class.
14. **SHOULD** prefer precise PHPDoc types (see Type Rules) over `mixed`.

---

## 1) Do / Do-Not Table

| Case | Do | Do Not |
|---|---|---|
| Files, classes/traits/enums, methods/constructors | Add DocBlocks | Skip |
| **File-Level DocBlock** | **Require at top of every PHP file** | Place after `namespace`/`use` |
| Explicit class properties | — | Add `@var` DocBlocks |
| Trivial getters/setters w/o side effects/throws | Usually skip | Add verbose DocBlocks |
| Magic/dynamic members (`__get`, macros, Eloquent) | Use `@property(-read)`, `@method`, `@mixin` | Document as real properties |
| Private helpers in short scripts/tests | May skip if self-describing | Over-document internals |

---

## 2) Tag Order (Single Source of Truth)

Use this exact sequence when present:

```
@template
@param
@return
@throws
@see
@link
@deprecated
@since
@psalm-*/@phpstan-*
```

Agents: when reformatting, **sort tags into this order** and then align.

---

## 3) Type Rules (PHPDoc)

- Scalars: `int`, `string`, `bool`, `float` (short forms only).
- Nullable: `T|null` (union form; avoid `?T` in PHPDoc).
- Union: `A|B`; Intersection: `A&B` (advanced).
- Arrays: homogeneous `T[]`; keyed `array<K, V>` (e.g., `array<string, Contact>`); lists `list<T>` (0..n keys).
- Array shapes (stable APIs): `array{key: T, optional?: U}`.
- Generics/Collections: `Collection<T>` or `Collection<K, V>`, `IteratorAggregate<int, T>`.
- Class strings: `class-string<T>`.
- Callables: `callable(A, B): R`.
- Literals: `'asc'|'desc'`.
- Use `mixed` only when unavoidable.

---

## 4) File-Level DocBlock (Required)

**Placement:** After `<?php`, before `declare(strict_types=1);` (if used), `namespace`, and any `use` statements.  
**Purpose:** Identify file intent, licensing, and ownership metadata.  
**Enforcement:** Agents must **add or repair** this block in every PHP file.

### Template

```php
<?php
/**
 * <Short file purpose>.
 *
 * PHP 8.1+
 *
 * @package   <VendorOrApp>\<LogicalGroup>
 * @author    <Name> <email@domain>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://example.com/docs/<page> Documentation
 */
declare(strict_types=1);

namespace App\Contacts;
```

### Example

```php
<?php
/**
 * Contact management entrypoint and helpers.
 *
 * PHP 8.1+
 *
 * @package   App\Contacts
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://example.com/docs/contacts API docs
 */
declare(strict_types=1);

namespace App\Contacts;

use App\Models\Contact;
```

**Agent heuristics:** infer `@package` from root namespace (e.g., `App\Contacts`); omit unknown tags; ensure a single blank line between the narrative and tags, then continue with `declare`/`namespace`/`use` as shown.

---

## 5) Class / Trait / Enum DocBlock (Template)

```php
/**
 * <One-sentence purpose>.
 *
 * <Optional short paragraph with role, invariants, patterns>.
 *
 * @template T of ModelBase
 * @implements \IteratorAggregate<int, DomainEntity>
 */
final class ContactService implements \IteratorAggregate
{
    // …
}
```

Enums: if case semantics are non-obvious, add a short paragraph; do not document cases individually unless needed.

Magic/Dynamic APIs (Eloquent builders, macros):

```php
/**
 * @property-read string $id
 * @method static self findByEmail(string $email)
 * @mixin \Illuminate\Database\Eloquent\Builder<Contact>
 */
```

These entries are allowed because the members are not explicitly declared.

---

## 6) Method / Function DocBlock

**Structure**

```php
/**
 * <One-sentence summary>.
 *
 * <Optional 1–3 lines for side effects, pre/post-conditions, performance>.
 *
 * @param  Type               $paramName  What it is, units, constraints
 * @param  OtherType|null     $other      Constraints or behavior
 * @return ReturnType|static              What the caller receives
 * @throws SpecificException              When/why it is thrown
 * @see    Fully\Qualified\Symbol         Optional related API
 */
```

**Example**

```php
/**
 * Returns a Contact instance loaded by its URI.
 *
 * @param  string $uriContact  Unique URI (typically SHA1(email)).
 * @return static              New Contact instance loaded from storage.
 * @throws NotFoundException   When no contact exists for the given URI.
 */
public static function fromUri(string $uriContact): static { /* … */ }
```

**Constructor with promoted properties (document via constructor only)**

```php
/**
 * Creates a service bound to an endpoint and key.
 *
 * @param non-empty-string $apiUrl  Base URL without trailing slash.
 * @param non-empty-string $apiKey  Authentication credential.
 */
public function __construct(
    private string $apiUrl,
    private string $apiKey
) {}
```

**Fluent API**

```php
/**
 * Sets the request timeout.
 *
 * @param  int $seconds  Timeout in seconds (1..120).
 * @return static
 */
public function timeout(int $seconds): static { /* … */ }
```

---

## 7) JSON Serialization

```php
/**
 * Specifies data to be serialized to JSON.
 *
 * @inheritDoc \JsonSerializable::jsonSerialize
 * @return array<string, scalar|array|object|null>  Serializable representation.
 */
public function jsonSerialize(): array { /* … */ }
```

If the shape is stable, prefer an explicit array shape:

```php
/**
 * @return array{
 *   id: non-empty-string,
 *   email: non-empty-string,
 *   meta?: array<string, scalar>
 * }
 */
```

---

## 8) Alignment Algorithm (for Agents)

**Goal:** Align columns for `@param`, `@return`, `@throws` within a single DocBlock.

1. Collect tag lines of the same kind set (`param`/`return`/`throws`).
2. Parse fields:  
   `@param ["@param", <type>, <name>, <description?>]`  
   `@return ["@return", <type>, <description?>]`  
   `@throws ["@throws", <type>, <description?>]`
3. Compute max widths: `maxType`, `maxName` (for `@param` only).
4. Rebuild lines with **single spaces** between columns; pad `<type>` and `<name>` to max widths.
5. Wrap descriptions at ≤ 120; continue with a **4-space hanging indent**.

**Target format**

```php
/**
 * @param  non-empty-string           $template   Template slug.
 * @param  list<non-empty-string>     $recipients Recipient emails.
 * @param  array<string, scalar>      $data       Template variables.
 * @return \Symfony\Component\Mime\Email          Built, unsent email.
 * @throws \RuntimeException                       When template is missing.
 */
```

---

## 9) Auto-Fix Heuristics (for Agents)

- Missing summary → synthesize from verb + object (“Returns a token.” / “Creates a contact.”).
- Redundant summary (“This method…”) → replace with verb phrase (“Returns…”).
- Wrong tag order → re-sort using the Tag Order.
- Method throws but no `@throws` → add entries for escaping exceptions.
- Missing `@return` → add `@return void` or specific type; use `@return static` for fluent/factory.
- Property DocBlocks present → remove `@var` on declared props; keep magic `@property` entries.
- Lines > 120 → wrap with 4-space hanging indent.
- Non-English words → replace with English equivalents.
- `mixed` used but can be narrowed → infer union/generic from usage, tests, or hints.
- **Missing File-Level DocBlock** → insert using the File-Level Template with inferred `@package` and optional `@link`.

---

## 10) Laravel / Ecosystem Conventions

- Controllers: public actions **MUST** have DocBlocks if they return complex responses or throw custom exceptions. Include response shape.
- Form Requests: document side effects and constraints in the description paragraph when non-trivial.
- Eloquent Models: do not document declared properties. Use `@property`/`@method` only for magic accessors/relations/builders.
- Events/Listeners/Jobs: document payload semantics and failure policy via description and `@throws`.
- Console Commands: `@return int Exit code (0 success).`
- Policies/Guards/Middleware: document preconditions and side effects (session, headers).

Generic collections you can stamp out:

- `\Illuminate\Database\Eloquent\Collection<Contact>`  
- `\Illuminate\Contracts\Pagination\LengthAwarePaginator<Contact>`  
- `\Illuminate\Database\Eloquent\Builder<Contact>` (via `@mixin` on the model)

---

## 11) Exceptions Policy

Document **only** exceptions that can escape the method boundary. If caught and transformed, document the transformed one. Prefer domain exceptions over generic runtime ones, and name the violated rule.

```
@throws DomainRuleViolation  When the email domain is not allowed.
@throws NotFoundException    When the contact does not exist.
@throws TimeoutException     When the API does not respond within the configured timeout.
```

---

## 12) Inheritance / {@inheritDoc}

- Allowed only when behavior is fully identical.
- If subclass changes behavior, constraints, or exceptions, write explicit docs.
- Canonical casing: `{@inheritDoc}`.

---

## 13) Deprecation Lifecycle

- Introduce: `@deprecated X.Y Use Foo::bar().` (include replacement)
- Soft removal: after **2 minor** versions.
- Hard removal: next **major**.
- Prefer adding `@since` when introducing new replacements.

---

## 14) Deterministic Formatting Settings

- Wrap at **120** characters.
- Hanging indent: **4** spaces for wrapped tag descriptions.
- Column spacing: **1 space** between columns after padding.
- One blank line between description block and tags.

---

## 15) Anti-Patterns (Agents must correct)

- “Gets the X.” → replace with “Returns …”
- “Function/Method to …” → remove “Function/Method to”
- Duplicating signature types in prose → keep types in tags
- `?T` in PHPDoc → use `T|null`
- `@package` not matching PSR-4 → normalize to top-level namespace group

---

## 16) Ready-to-Paste Templates

**Service class**

```php
/**
 * Provides <domain> operations with validation and persistence orchestration.
 *
 * Coordinates repositories and external APIs to enforce business rules.
 *
 * @template TRepo of \App\Contracts\RepositoryInterface
 */
final class <Name>Service
{
    /**
     * Creates a service with required dependencies.
     *
     * @param TRepo                        $repo    Primary repository.
     * @param \Psr\Log\LoggerInterface     $logger  Logger instance.
     */
    public function __construct(
        private \App\Contracts\RepositoryInterface $repo,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    /**
     * Returns the <domain> by its identifier.
     *
     * @param  non-empty-string $id  Identifier.
     * @return \App\Domain\<Domain>
     * @throws \App\Exceptions\NotFoundException  When the entity is missing.
     */
    public function getById(string $id): \App\Domain\<Domain> { /* … */ }
}
```

**Controller action**

```php
/**
 * Returns a paginated collection of contacts as JSON.
 *
 * Applies filters for status and search term. Results are ordered by creation date.
 *
 * @param  \Illuminate\Http\Request $request  HTTP request with query params.
 * @return \Illuminate\Http\JsonResponse      JSON payload: array{data: list<array>, meta: array}
 * @throws \Illuminate\Validation\ValidationException  When validation fails.
 */
public function index(Request $request): JsonResponse { /* … */ }
```

**Event**

```php
/**
 * Dispatched when a contact is created.
 *
 * @param non-empty-string $contactId  Created contact identifier.
 */
final class ContactCreated
{
    public function __construct(public string $contactId) {}
}
```

**Console command**

```php
/**
 * Rebuilds the search index for contacts.
 *
 * @return int  Exit code (0 on success).
 */
public function handle(): int { /* … */ }
```

---

## 17) Precedence: Signature vs PHPDoc

If signature and PHPDoc disagree, the signature is the source of truth. The agent must fix PHPDoc to match, then add extra semantics (generics/shapes) in PHPDoc.

---

## 18) Extended Description Decision Rule

Provide an extended paragraph only if at least one applies: side effects, pre/post-conditions, performance caveat, security constraint, domain invariant. Otherwise omit.

---

## 19) Psalm/PHPStan Tags

- Place after standard tags, sorted lexicographically within their block.
- Use named types (`@psalm-type`, `@phpstan-type`) for reused shapes.
- Use override forms (`@psalm-return`, `@phpstan-return`) only when framework stubs are inaccurate.

---

## 20) Anti-Drift PR Checklist (Agent-Enforced)

- [ ] **File-Level DocBlock present and correctly placed** (top of file, before `declare`/`namespace`/`use`).
- [ ] Summary present; ends with a period.
- [ ] Optional description ≤ 3 lines and adds value.
- [ ] Tag order correct; tags aligned; wrap at 120 with 4-space hanging indent.
- [ ] All params documented; names/order match signature.
- [ ] `@return` present (use `void` when none); `@return static` for fluent/factory.
- [ ] All escaping exceptions documented with `@throws` and reasons.
- [ ] Types follow Type Rules; no `?T` in PHPDoc; `mixed` minimized.
- [ ] No DocBlocks on explicitly declared properties.
- [ ] Magic members documented if applicable (`@property(-read)`, `@method`, `@mixin`).
- [ ] English only; no redundant restatements.
- [ ] Deprecations include version and replacement; lifecycle observed.

---

## 21) Regex Hints (for Agents)

- Remove explicit property DocBlocks:  
  `(?s)/\*\*.*?\*/\s*(public|protected|private)\s+[^\$]*\$[A-Za-z_]\w*`
- Normalize nullable shorthand in PHPDoc:  
  `@param\s+\?([A-Za-z_\\][^ \t\r\n]*)` → `@param \1|null`
- Remove “Function/Method to …” boilerplate in summaries:  
  `(?im)^\s*\*\s*(This\s+(method|function)\s+)?to\s+` → replace with imperative verb.
- Ensure summary ends with a period (lint rule).
- **Insert File-Level DocBlock if missing** (anchor at start of file, before `declare|namespace|use`): use the File-Level template and infer `@package` from the root namespace.

---

## 22) Skip-Docs Escape Hatch (Internal Only)

For **private** methods in small local scripts/tests, you may omit DocBlocks if:
- Name + signature are self-explanatory,
- No exceptions escape,
- No external consumers.

This avoids over-documentation of dead-end internals while keeping strict rules for libraries.

---
