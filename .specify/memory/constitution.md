<!--
SYNC IMPACT REPORT
==================
Version change: 1.1.0 → 1.2.0 (MINOR — new principle added, new section added, two sections expanded)
Modified principles: N/A (no existing principles renamed or redefined)
Added principles:
  - VI. Containerization & Deployment Readiness (new)
Added sections:
  - Infrastructure & Deployment
Expanded sections:
  - Technology Stack → Tooling: Laravel Sail added as preferred local dev environment
  - Development Workflow → Definition of Done: container build check added
Removed sections: N/A
Templates requiring updates:
  - .specify/templates/plan-template.md  ✅ reviewed — Constitution Check is filled at runtime; no update required
  - .specify/templates/spec-template.md  ✅ reviewed — no update required
  - .specify/templates/tasks-template.md ✅ reviewed — Phase 1 Setup tasks already include infrastructure setup; no update required
  - .specify/templates/agent-file-template.md ✅ reviewed — no update required
Follow-up TODOs:
  - TODO(DATA_SOURCES): Enumerate authoritative data sources once known
    (government portals, PBS census API, etc.)
  - TODO(VULNERABILITY_TOOL): Confirm the vulnerability scanning tool
    (e.g., `composer audit`, `npm audit`, Snyk) — add to Tooling section when decided
  - TODO(FORGE_SITE_ID): Record the Laravel Forge site ID / server ID once provisioned,
    so deploy scripts can reference it directly
-->

# Pakistan Database Constitution

## Core Principles

### I. Data Integrity First

Every datum stored in Pakistan Database MUST have a traceable, authoritative source.
Specifically:

- All records MUST reference a `source_url` or `source_citation` at ingestion time.
- Data from unverified or undocumented sources MUST NOT enter the production dataset.
- Conflicting records from multiple sources MUST be flagged and resolved explicitly —
  silent overwriting is forbidden.
- Schema changes that alter the meaning of existing fields MUST include a migration
  plan and backward-compatibility window.

**Rationale**: A database of national significance is only as trustworthy as its
provenance chain. Data without a source is indistinguishable from fabrication.

### II. API-First Design

All data access MUST flow through well-defined, versioned interfaces.

- No application code may issue raw queries against the storage layer directly;
  all access MUST go through a service or repository layer.
- Public-facing endpoints MUST be versioned (e.g., `/v1/`, `/v2/`) and MUST NOT
  introduce breaking changes within a version.
- Both machine-readable (JSON/CSV) and human-readable output formats MUST be
  supported for any data export operation.

**Rationale**: Versioned interfaces allow consumers to rely on stable contracts,
and decouple the data model from delivery format.

### III. Pre-Implementation Gate & TDD (NON-NEGOTIABLE)

Before any feature implementation begins, three gates MUST be cleared in order:

1. **Vulnerability gate**: Run `composer audit` and `npm audit`. All known vulnerabilities
   MUST be resolved or explicitly risk-accepted before proceeding.
2. **Green gate**: The existing test suite MUST be fully passing. No new work may begin
   on a red suite.
3. **Test-first gate**: New tests for the feature MUST be written and MUST FAIL before
   any implementation code is written.

The Red → Green → Refactor cycle is then strictly enforced:

- Contract tests MUST cover every public API endpoint or data interface.
- Integration tests MUST cover end-to-end data ingestion and retrieval flows.
- No feature is considered complete until its acceptance scenarios pass.

**Rationale**: Starting work on a vulnerable or broken baseline silently accumulates
risk. A clean slate at every feature boundary keeps defects attributable and fixable.

### IV. Observability

All data pipelines and API services MUST emit structured, queryable telemetry:

- Ingestion jobs MUST log: source, record count, success/failure counts,
  and wall-clock duration in structured JSON format.
- API services MUST emit request logs including method, path, status code,
  latency, and a correlation ID for tracing.
- Errors MUST be logged with full context (source, affected record IDs,
  error type) — swallowed exceptions are forbidden.

**Rationale**: Without observability, debugging data quality issues and
pipeline failures in production becomes guesswork.

### V. Simplicity & Modularity

Complexity MUST be justified by a current, concrete requirement, and code MUST
be kept small and focused:

- No abstractions, patterns, or infrastructure may be introduced speculatively.
- A simpler solution that satisfies today's requirements MUST be preferred over
  a flexible solution that anticipates tomorrow's.
- **Class size**: No class may have more than 10 methods. When a class exceeds this
  limit it MUST be split into focused single-responsibility classes.
- **File grouping**: When a directory contains more than 10 files, related files
  MUST be extracted into a named subdirectory (e.g., `app/Services/Ingestion/`).
- **Helper extraction**: Shared helper logic MUST be extracted into well-named
  helper classes or traits; copy-paste of helper code is forbidden.
- Any deviation from simplicity MUST be documented in the Complexity Tracking
  table of the feature's plan.md.

**Rationale**: Small, focused units are easier to test, review, and debug. File
grouping prevents directories from becoming unmaintainable flat lists.

### VI. Containerization & Deployment Readiness (NON-NEGOTIABLE)

Every service in this project MUST run inside a Docker container. The repository
MUST be deployable from a clean clone with no manual server provisioning:

- **Docker-first**: All application services (app, queue, scheduler, database,
  cache, search) MUST be defined as Docker containers. Running services directly
  on the host OS is not permitted in any environment.
- **Laravel Sail**: Sail is the preferred local development environment. All
  developers MUST use `./vendor/bin/sail` (or the `sail` alias) to run commands —
  direct `php artisan`, `composer`, or `npm` invocations outside the container
  are not permitted during development.
- **Environment parity**: The local Sail containers and the production containers
  MUST use the same base images and service versions. Divergence between local and
  production environments MUST be treated as a bug.
- **Laravel Forge**: Production and staging deployments MUST target Laravel Forge.
  All server configuration (daemons, scheduler, queues, environment variables) MUST
  be managed through Forge — no out-of-band SSH configuration is permitted.
- **Laravel tools over custom scripts**: Forge deploy scripts MUST use
  `php artisan` commands. Custom bash provisioning scripts that duplicate Laravel
  Artisan or Sail capabilities are forbidden.
- **Committed configuration**: `docker-compose.yml` (Sail), `Dockerfile` (if
  customised), and the Forge deploy script template MUST be committed to the
  repository. No deployment-critical configuration may live only in a dashboard.

**Rationale**: Container parity eliminates "works on my machine" failures and
ensures every merge can be shipped. Forge manages provisioning declaratively so
the repository remains the single source of truth for the full application lifecycle.

## Technology Stack

The following stack is mandatory for all features. Deviations require a
constitution amendment.

### Backend

- **Framework**: Laravel — always the latest stable major version. Upgrading to
  a new major version MUST be done proactively; running on an end-of-life version
  is not permitted.
- **Framework-first rule**: Laravel's built-in features (Collections, Eloquent,
  Jobs, Events, Notifications, Gates, Policies, etc.) MUST be used in preference
  to native PHP implementations. Native PHP MUST only be used when Laravel provides
  no equivalent.
- **Runtime**: Laravel Octane (Swoole or FrankenPHP driver) — the application server
  MUST run on Octane. Traditional PHP-FPM is not permitted in production.
- **Admin panel**: Laravel Nova — all administrative interfaces MUST be built in Nova.
  Custom admin UIs outside Nova require explicit justification.
- **PHP version**: Always the latest stable minor version supported by the current
  Laravel major version.

### Frontend

- **Framework**: Vue.js (latest stable version) for all interactive UI components.
- **CSS**: Tailwind CSS — always the latest stable version. Custom CSS MUST only
  be written when Tailwind utilities are demonstrably insufficient.
- **Package management**: npm or pnpm; Yarn is not used in this project.

### Infrastructure

- **Local environment**: Laravel Sail — `docker-compose.yml` at repository root.
  The Sail-provided services (MySQL, Redis, Meilisearch, Mailpit, etc.) MUST be
  used in preference to locally installed equivalents.
- **Deployment platform**: Laravel Forge — no other deployment mechanism is used.
- **Containerisation**: Docker. Custom `Dockerfile` modifications MUST extend the
  official Laravel/Sail base images; do not build from a generic `php` base image
  unless Sail images are demonstrably insufficient.

### Tooling

- **Linting (PHP)**: Laravel Pint — configuration committed at `pint.json`.
- **Linting (JS/Vue)**: ESLint — configuration committed at `.eslintrc.*`.
- **Formatting (JS/CSS)**: Prettier — configuration committed at `.prettierrc.*`.
- **Commit hooks**: Husky + lint-staged MUST enforce Pint, ESLint, and Prettier
  on every commit. No commit may land with lint errors.
- **IDE**: PhpStorm is the primary IDE. The `.idea/` project configuration MUST be
  committed to the repository so that all contributors share the same inspection
  profiles, code style settings, and run configurations.

## Code Standards & Conventions

### Method Naming

All methods MUST follow a verb-oriented naming convention:

- Use `get*`, `set*`, `create*`, `update*`, `delete*`, `find*`, `load*`,
  `build*`, `handle*`, `send*`, `process*` prefixes as appropriate.
- Boolean-returning methods MUST use `is*`, `has*`, `can*`, or `should*` prefixes.
- Vague names (`do`, `run`, `execute`, `manage`, `handle` alone) are forbidden —
  the verb MUST describe the specific action (e.g., `processIngestJob`, not `handle`).

### Laravel Conventions

- Follow the Laravel naming conventions for Models, Controllers, Migrations,
  Jobs, Events, Listeners, Policies, and Observers exactly as documented in the
  official Laravel documentation.
- Controllers MUST be resource-oriented and follow single-action controller
  patterns when a resource controller would have fewer than 3 active methods.
- Form Requests MUST be used for all input validation; validation logic inside
  controllers is forbidden.
- Route model binding MUST be used wherever a route resolves a model instance.

### Folder Structure

- PHP: follow the standard Laravel application directory layout (`app/Models/`,
  `app/Http/Controllers/`, `app/Services/`, `app/Jobs/`, etc.).
- Vue: follow the standard Vite + Vue layout (`resources/js/components/`,
  `resources/js/pages/`, `resources/js/composables/`, etc.).
- Tailwind: configuration at `tailwind.config.js` at repository root.
- Do not introduce top-level directories that conflict with framework conventions.

## Infrastructure & Deployment

### Local Development with Sail

- Every developer MUST set up the project using `./vendor/bin/sail up` — no
  alternative local setup is supported.
- Database, cache (Redis), and any other backing services MUST run as Sail
  containers; locally installed services are not used.
- Artisan commands, Composer, and npm/pnpm MUST be run through Sail:
  `sail artisan migrate`, `sail composer require`, `sail npm run dev`.
- The Sail `docker-compose.yml` MUST be kept up to date with any new services
  introduced (e.g., a new queue driver, search engine, or object store).

### Production Deployment with Laravel Forge

- The Forge server MUST be provisioned with the same PHP version and extensions
  as the Sail container.
- Octane MUST be configured as the application daemon in Forge (not PHP-FPM).
- Queue workers and the scheduler MUST be registered as Forge daemons — not
  managed by cron entries added manually on the server.
- Environment variables MUST be managed through Forge's environment editor;
  `.env` files MUST NOT be committed to the repository.
- The Forge deploy script MUST include: `composer install --no-dev`,
  `php artisan migrate --force`, `php artisan octane:reload`, and
  `php artisan queue:restart` as a minimum.
- Zero-downtime deployments MUST be enabled in Forge where the server plan
  supports it.

### Environment Parity Rules

- The PHP version in `docker-compose.yml` MUST match the PHP version on the
  Forge server. Drift MUST be resolved within one sprint of discovery.
- All environment variables used in production MUST have a corresponding entry
  in `.env.example` (with a safe placeholder value) committed to the repository.

## Data Standards

All data assets in this project MUST comply with the following standards:

- **Normalisation**: Geographic names (cities, districts, provinces) MUST use a
  canonical reference list; aliases MUST map to canonical names, not coexist.
- **Encoding**: All text data MUST be stored as UTF-8; Urdu/Arabic script MUST be
  preserved losslessly.
- **Timestamps**: All timestamps MUST be stored in ISO 8601 UTC format
  (`YYYY-MM-DDTHH:MM:SSZ`); local timezone conversion is a presentation concern only.
- **Identifiers**: Every entity MUST have a stable, opaque `id` field. Human-readable
  slugs are acceptable as secondary keys but MUST NOT serve as primary references.
- **Versioning**: Datasets MUST be versioned by a monotonically increasing
  `dataset_version` field so consumers can detect and react to updates.

## Development Workflow

- **Branching**: All work happens on feature branches following the naming
  convention `###-short-description` (e.g., `001-province-schema`).
- **Code Review**: Every PR MUST be reviewed by at least one other contributor
  before merge. Self-merges are permitted only for trivial documentation fixes.
- **Constitution Check**: Every plan.md MUST include a Constitution Check gate that
  verifies compliance with all Core Principles — including the Pre-Implementation
  Gate (vulnerability scan + green suite), the Technology Stack constraints, and
  Containerization readiness — before implementation begins.
- **Definition of Done**: A task is done when: all three pre-implementation gates
  passed, tests pass, the feature is observable (logs emitted), source attribution
  is present for any new data, linting is clean, `sail up` brings the environment
  up cleanly with no manual steps, and documentation is updated.
- **Commits**: Commit after each logical task. Commit messages MUST follow
  `type: short description` (e.g., `feat: add district schema`,
  `fix: resolve UTF-8 encoding bug`). Commits are blocked by the Husky lint-staged hook.

## Governance

This constitution supersedes all other development practices and agreements.
Any practice that conflicts with these principles MUST be amended or removed.

**Amendment procedure**:
1. Open a PR with the proposed change to `.specify/memory/constitution.md`.
2. Describe the motivation, the impact on existing features, and a migration plan
   if any principle is weakened or removed.
3. At least one contributor MUST approve before merge.
4. After merge, run `/speckit.constitution` to propagate changes to dependent templates.

**Versioning policy**: Follow semantic versioning:
- MAJOR bump: principle removed, redefined, or governance weakened.
- MINOR bump: new principle or section added; existing guidance materially expanded.
- PATCH bump: wording clarification, typo fix, non-semantic refinement.

**Compliance review**: All PRs and code reviews MUST verify that the implementation
respects the Core Principles and the Technology Stack constraints. Complexity
violations not documented in plan.md MUST be flagged in review and resolved
before merge.

**Runtime guidance**: Use `.specify/memory/` for project context and feature-level
guidance files. Use CLAUDE.md (when created) for agent-specific runtime instructions.

**Version**: 1.2.0 | **Ratified**: 2026-06-13 | **Last Amended**: 2026-06-13
