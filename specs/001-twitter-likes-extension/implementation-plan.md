# Implementation Plan: Twitter Likes Capture Extension

**Feature Branch**: `001-twitter-likes-extension`  
**Created**: 2025-07-12  
**Specification**: Link to feature-spec.md in same directory  

---

## ⚡ Quick Guidelines

**Note**: This document serves two purposes:
1. **As a template** - For AIs/humans creating implementation plans
2. **As a guide** - For AIs/humans executing the implementation

Instructions marked *(for execution)* apply when implementing the feature.
Instructions marked *(when creating this plan)* apply when filling out this template.

- ✅ Mark all technical decisions that need clarification
- ✅ Use [NEEDS CLARIFICATION: question] for any assumptions
- ❌ Don't guess at technical choices without context
- ❌ Don't include actual code - use pseudocode or references
- 📋 The review checklist acts as "unit tests" for this plan
- 📁 Extract details to `implementation-details/` files

### For AI Generation *(when creating this plan document)*
When generating this plan from a feature spec:
1. **NO CODE IN THIS DOCUMENT**: 
   - Use pseudocode or high-level descriptions only
   - Extract any detailed code fragments to `implementation-details/` files
   - Reference the detail files instead of embedding code
2. **Mark missing context**: Use [NEEDS CLARIFICATION] for:
   - Unspecified technical stack choices
   - Missing performance requirements
   - Unclear integration points
   - Ambiguous testing requirements
3. **Common areas needing clarification**:
   - Language/framework versions
   - Database technology choices
   - Authentication/authorization approach
   - Deployment environment
   - Third-party service integrations
4. **Don't assume**: If the spec doesn't specify it, mark it
5. **Use implementation-details/ folder**:
   - Research results → `implementation-details/00-research.md`
   - API design details → `implementation-details/03-api-contracts.md`
   - Complex algorithms → `implementation-details/04-algorithms.md`
   - Integration specifics → `implementation-details/05-integrations.md`

### Implementation Details File Examples

**00-research.md**: Technical investigations, spike results, performance benchmarks
**02-data-model.md**: Entity definitions, relationships, validation rules, state machines
**03-api-contracts.md**: OpenAPI specs, request/response schemas, error codes
**06-contract-tests.md**: Test scenarios for each endpoint, edge cases, error conditions
**08-inter-library-tests.md**: Library interaction contracts, boundary tests

---

## Executive Summary *(mandatory)*

A Safari browser extension written in plain JavaScript that automatically captures Twitter/X posts when users like them, sending content and screenshots to a Laravel backend via HTTP API for storage and AI-powered analysis and associative memory retrieval.

## Requirements *(mandatory)*

**Minimum Versions**: Safari 14+, macOS 11+, PHP 8.1+, Laravel 10+  
**Dependencies**: SQLite 3.8+, Laravel Framework only  
**Technology Stack**: Plain JavaScript (Safari extension), Laravel 10 + SQLite, local file storage  
**Feature Spec Alignment**: [x] All requirements addressed

---

## Constitutional Compliance *(mandatory)*

*Note: The Constitution articles referenced below can be found in `/memory/constitution.md`. AI agents should read this file to understand the specific requirements of each article.*

### Simplicity Declaration (Articles VII & VIII)
- **Project Count**: 2 (Safari Extension, Laravel Backend) - maximum 3 compliant
- **Model Strategy**: [x] Single model (using Laravel Eloquent models directly)
- **Framework Usage**: [x] Direct (Safari Extension APIs directly, Laravel features directly)
- **Patterns Used**: [x] None (no Repository, Unit of Work, or other complex patterns)

### Testing Strategy (Articles III & IX)
- **Test Order**: Contract → Integration → E2E → Unit
- **Contract Location**: `/contracts/` (database schema as contract)
- **Real Environments**: [x] Yes (real SQLite database, real Safari extension environment)
- **Coverage Target**: 80% minimum, 100% critical paths (like detection, data capture, database operations)

### Library Organization (Articles I & II)
- **Libraries**: 
  1. Safari Extension Library (DOM interaction, content capture, user interface)
  2. Laravel Backend Library (data processing, CLI interface, analysis preparation, HTTP API)
- **CLI Interfaces**: 
  - Laravel: `php artisan posts:process`, `posts:status`, `posts:export`, `posts:cleanup`
- **CLI Standards**: All CLIs implement --help, --version, --format
- **Inter-Library Contracts**: HTTP API (Extension → Laravel via localhost:8000)

### Observability (Article V)
- [x] Structured logging planned (Laravel logging for backend, Safari console for extension)
- [x] Error reporting defined (capture failures, processing errors, user notifications)
- [x] Metrics collection (capture success rates, processing performance, storage usage)

---

## Project Structure *(mandatory)*

```
001-twitter-likes-extension/
├── implementation-plan.md          # This document (HIGH-LEVEL ONLY)
├── manual-testing.md              # Step-by-step validation instructions
├── implementation-details/         # Detailed specifications
│   ├── 00-research.md             # Safari extension and Laravel research
│   ├── 02-data-model.md           # Database schema and relationships
│   ├── 03-api-contracts.md        # Extension ↔ Laravel HTTP API communication
│   ├── 04-like-button-detection-experiments.md  # X.com like button research
│   ├── 05-dom-extraction-selectors.md  # Complete DOM extraction methods and selectors
│   ├── 06-contract-tests.md       # Database and API contract tests
│   └── 08-inter-library-tests.md  # Extension ↔ Laravel integration tests
├── contracts/                      # API contracts (FIRST)
│   ├── database-schema.sql        # SQLite schema definition
│   └── api-spec.yaml             # HTTP API OpenAPI specification
├── safari-extension/               # Safari extension source
│   ├── manifest.json              # Extension manifest with localhost permissions
│   ├── background.js              # Background script for API communication
│   ├── content.js                 # Content script for x.com interaction
│   └── popup/                     # Extension popup UI
└── laravel-backend/               # Laravel application
    ├── app/Models/                # Eloquent models
    ├── app/Console/Commands/      # Artisan CLI commands
    ├── app/Http/Controllers/      # API controllers for extension
    ├── routes/api.php             # API routes for extension
    ├── database/migrations/       # Database schema migrations
    └── tests/                     # Laravel test suite
        ├── Unit/                  # Unit tests for complex logic
        ├── Feature/               # Feature tests for CLI commands and API
        └── Integration/           # Extension ↔ Laravel integration tests
```

### File Creation Order
1. Create directory structure
2. Create `contracts/database-schema.sql` with complete schema
3. Create `implementation-details/` files (already created)
4. Create Laravel application with migrations matching contract
5. Create test files in order: contract → integration → feature → unit
6. Create Safari extension files to pass integration tests
7. Create `manual-testing.md` for E2E validation

**IMPORTANT**: This implementation plan should remain high-level and readable. Any code samples, detailed algorithms, or extensive technical specifications must be placed in the appropriate `implementation-details/` file and referenced here.

---

## Implementation Phases *(mandatory)*

### Phase -1: Pre-Implementation Gates

#### Technical Unknowns
- [x] Complex areas identified: Safari extension limitations, native messaging requirements, x.com like button detection
- [x] Research completed in implementation-details/00-research.md
- [x] Like button detection experiments completed and validated (implementation-details/04-like-button-detection-experiments.md)
- [x] DOM extraction selectors documented and validated (implementation-details/05-dom-extraction-selectors.md)
*Research findings: implementation-details/00-research.md*
*Experiment results: implementation-details/04-like-button-detection-experiments.md*
*DOM extraction methods: implementation-details/05-dom-extraction-selectors.md*

#### Simplicity Gate (Article VII)
- [x] Using ≤3 projects? (Yes: 2 projects - Safari Extension + Laravel Backend)
- [x] No future-proofing? (Yes: minimal viable implementation, no speculative features)
- [x] No unnecessary patterns? (Yes: direct framework usage, no Repository/UoW patterns)

#### Anti-Abstraction Gate (Article VIII)
- [x] Using framework directly? (Yes: Safari Extension APIs directly, Laravel Eloquent directly)
- [x] Single model representation? (Yes: Laravel Eloquent models used throughout)
- [x] Concrete classes by default? (Yes: no interfaces except where required by frameworks)

#### Integration-First Gate (Article IX)
- [x] Contracts defined? (Yes: database schema in contracts/, API contracts in implementation-details/)
- [x] Contract tests written? (Yes: specified in implementation-details/06-contract-tests.md)
- [x] Integration plan ready? (Yes: detailed in implementation-details/08-inter-library-tests.md)

**📝 During plan creation**: No gate failures - architecture aligns with constitutional principles
**⚠️ During implementation**: Proceed with confidence, all gates passed

#### Gate Failure Handling
**When creating this plan:**
- All gates passed - no exceptions needed
- Research documented in implementation-details/00-research.md
- Integration patterns clearly defined

**When executing this plan:**
- No documented exceptions required
- Research findings validate technical approach
- Proceed with Phase 0

### Verification: Phase -1 Complete *(execution checkpoint)*
- [x] All gates passed with no exceptions required
- [x] Research findings documented and validate approach
- [x] Ready to create directory structure and contracts

### Phase 0: Contract & Test Setup

**Prerequisites** *(for execution)*: Phase -1 verification complete
**Deliverables** *(from execution)*: Failing contract tests, database schema, test strategy

1. **Define Database Schema Contract**
   ```pseudocode
   Create contracts/database-schema.sql
   Define tables: liked_posts, post_screenshots, thread_relationships, capture_sessions
   Include indexes, constraints, relationships
   ```
   *Details: implementation-details/02-data-model.md*

2. **Write Contract Tests**
   ```pseudocode
   Create Laravel migration tests that verify schema matches contract
   Create database operation tests (CRUD for each entity)
   Create CLI command interface tests
   These must fail (no implementation yet)
   ```
   *Detailed test scenarios: implementation-details/06-contract-tests.md*

3. **Design Integration Tests**
   ```pseudocode
   Plan Safari extension → database → Laravel CLI workflow tests
   Plan concurrent access tests (extension + Laravel simultaneously)
   Plan error propagation tests (failures in one component affect other)
   ```
   *Test strategy details: implementation-details/08-inter-library-tests.md*

4. **Create Manual Testing Guide**
   - Map each user story to validation steps
   - Document Safari extension installation and setup
   - Create step-by-step like capture validation
   - Document Laravel backend setup and CLI usage
   *Output: manual-testing.md*

### Verification: Phase 0 Complete *(execution checkpoint)*
- [ ] Database schema contract exists in `/contracts/database-schema.sql`
- [ ] Laravel migration tests written and failing
- [ ] CLI command contract tests written and failing
- [ ] Integration test plan documented with specific scenarios
- [ ] Manual testing guide created with user story mapping

### Phase 1: Core Implementation

**Prerequisites** *(for execution)*: Phase 0 verification complete
**Deliverables** *(from execution)*: Working implementation passing all contract tests

1. **Laravel Backend Implementation**
   ```pseudocode
   Create Laravel application with database migrations
   Implement Eloquent models: LikedPost, PostScreenshot, ThreadRelationship, CaptureSession
   Configure CORS for Safari extension communication (localhost origins)
   Implement HTTP API endpoints for extension communication
   Implement Artisan CLI commands: posts:process, posts:status, posts:export, posts:cleanup
   ```
   - Use Laravel migrations to match database contract exactly
   - Configure CORS middleware for Safari extension origins (localhost:*, file://)
   - Implement API routes with validation and rate limiting
   - Implement CLI commands to pass interface contract tests
   - Add structured logging and error handling
   *Detailed models and relationships: implementation-details/02-data-model.md*

2. **Safari Extension Implementation**
   ```pseudocode
   Create extension manifest with required permissions
   Implement content script for x.com DOM interaction
   Implement like button detection using data-testid="like" selector
   Implement complete post content extraction using documented selectors
   Implement screenshot capture using browser APIs
   Implement HTTP API communication to Laravel backend
   ```
   - Use Safari Extension APIs directly
   - Implement like button detection: `data-testid="like"` → `data-testid="unlike"`
   - Extract tweet content using `[data-testid="tweetText"]` and related selectors
   - Extract author info using `[data-testid="User-Name"]` and `[data-testid="Tweet-User-Avatar"]`
   - Extract engagement metrics using `[data-testid="reply|retweet|like|bookmark"]` selectors
   - Add user feedback for capture success/failure
   - Handle x.com DOM variations with fallback selectors
   *Complete DOM extraction methods: implementation-details/05-dom-extraction-selectors.md*

3. **Integration Implementation**
   ```pseudocode
   Verify Safari extension → Laravel HTTP API communication
   Implement error handling and retry logic for API failures
   Add request validation and rate limiting
   Verify integration tests pass end-to-end
   ```

### Phase 2: Refinement

**Prerequisites** *(for execution)*: Phase 1 complete, all contract/integration tests passing
**Deliverables** *(from execution)*: Production-ready code with full test coverage

1. **Unit Tests** (only for complex logic)
   - Thread relationship analysis algorithms
   - Post content parsing edge cases  
   - Screenshot capture error handling

2. **Performance Optimization** (only if metrics show need)
   - Database query optimization for large datasets
   - Screenshot compression if storage becomes issue
   - Background processing performance tuning

3. **Documentation Updates**
   - Update installation and setup instructions
   - Document CLI command usage with examples
   - Create troubleshooting guide for common issues

4. **Manual Testing Execution**
   - Follow manual-testing.md procedures exactly
   - Verify all user stories work E2E on real x.com
   - Test error scenarios and recovery
   - Document any issues found and resolution

### Verification: Phase 2 Complete *(execution checkpoint)*
- [ ] All tests passing (contract, integration, unit)
- [ ] Manual testing completed successfully for all user stories
- [ ] Performance metrics meet 3-second capture requirement
- [ ] Documentation updated and accurate

---

## Success Criteria *(mandatory)*

1. **Constitutional**: All gates passed, no unjustified complexity
2. **Functional**: All 16 functional requirements (FR-001 through FR-016) implemented and verified
3. **Testing**: Contract tests verify database schema, integration tests verify extension ↔ Laravel communication
4. **Performance**: Post capture completes within 3 seconds, 99% capture success rate achieved
5. **Simplicity**: Two libraries only, direct framework usage, no unnecessary abstractions

---

## Review & Acceptance Checklist

### Plan Completeness
- [x] No [NEEDS CLARIFICATION] markers remain
- [x] All mandatory sections completed
- [x] Technology stack fully specified (Safari Extension + Laravel + SQLite)
- [x] Dependencies justified (only framework dependencies, no additional packages)

### Constitutional Alignment
- [x] All Phase -1 gates passed with no exceptions needed
- [x] No deviations recorded (none required)

### Technical Readiness
- [x] Phase 0 verification defines clear deliverables
- [x] Phase 1 implementation path clear with specific technologies
- [x] Success criteria measurable and aligned with feature specification

### Risk Management
- [x] Complex areas identified (Safari screenshot APIs, concurrent SQLite access)
- [x] Integration points clearly defined (shared database, file communication)
- [x] Performance requirements specified (3-second capture, 99% success rate)
- [x] Security considerations addressed (local-only data, input sanitization)

### Implementation Clarity
- [x] All phases have clear prerequisites and deliverables
- [x] No speculative features (unlike removes data, no complex patterns)
- [x] Manual testing procedures defined for all user stories

---

*This plan follows Constitution v2.0.0 (see `/memory/constitution.md`) emphasizing simplicity, framework trust, and integration-first testing.*