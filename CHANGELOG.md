# Changelog

## [1.0.0] — Unreleased

### Added
- `MovementRecord` immutable DTO with fluent builder and `fromArray()` factory
- `MovementRecordBuilder` — maps snake_case, camelCase, and legacy column names
- `EdiSegment` — single fixed-width field primitive
- `EdiLine` — ordered segment collection; renders to fixed-width text or associative array
- `EdiOutput` — immutable result carrying content, `includedIds`, event type, and rows
- `CarrierProfileInterface` — contract for all carrier profiles
- `AbstractCarrierProfile` — base class with shared timestamp helpers and chronological validators
- `MscCarrierProfile` — full MSC EDI generator: gate_in, survey, repair_dispatch, repair_return, gate_out, cfs_arrival, stuffing, destuffing
- `MscEventCodeResolver` — MSC event code mapping with zone-specific overrides (Hazira, Mundra, Nhava Sheva)
- `MscLineSchema` — MSC field layout definition (228-char fixed-width)
- `EdiLink` manager class with carrier registry
- Laravel service provider, Facade, and config file
- `edilink:generate` and `edilink:validate` Artisan commands
- Unit tests for core classes
- Integration tests for MSC profile covering all event types and edge cases
