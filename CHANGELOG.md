# Changelog

All notable changes to `migears/utils` are documented here. This project follows [Semantic Versioning](https://semver.org/).

## [2.0.0] — Unreleased

Renamed from `migears/common`, and a breaking release in its own right. `URL` stopped guessing protocols, and `Str` / `Arr` left the package.

### Removed

- `MiGears\Utils\Str` and `MiGears\Utils\Arr` — both reimplemented helpers from `Illuminate\Support` (method names, parameter order, and edge-case behavior matched almost one to one). Duplicating an upstream library inside a utility package gave every consumer a second source of truth for the same behavior. Install `illuminate/support` or `symfony/string` if you need those helpers.
- The `ext-mbstring` requirement, which only `Str` needed.
- `MiGears\Utils\Date` — moved to `migears/i18n` as `LocalizedDate`. Dates are presented to a person in their own timezone and language, so the class now renders text through a translator there. See that package's CHANGELOG for its history.

### Changed

- `URL::parse()` no longer prepends `https://` to a scheme-less string. It now throws `InvalidArgumentException`, so a missing protocol is never silently invented.
- `URL` constructor: `$scheme` and `$host` are both required. Previously `$host` defaulted to `''` and then threw on that empty value — a default that could never be used.
- `URL` query parsing no longer goes through `parse_str()`. Keys keep their original characters, so `?a.b=1` yields the key `a.b` instead of `a_b`. Bracket notation is no longer expanded: every key is flat and every value is a scalar.
- `URL::parse()` scheme detection tightened from `[\w]+` to `[a-z][a-z0-9+.\-]*`, rejecting invalid schemes such as `1a://`.
- `Paginator` raises `$pageSize` and `$currentPage` to 1 when given lower values, so a page size of 0 can no longer divide by zero and a page number below 1 can no longer produce a negative `offset()`.
- `Paginator::firstPage()` returns 0 for an empty result set, matching `lastPage()`.
- `URL` validates the scheme in the constructor, so `new URL(scheme: '1a', ...)` and `withScheme('1a b')` are now rejected the same way `parse('1a://...')` already was.
- `URL` percent-encodes the path and fragment on input, leaving existing escape sequences intact, and treats an empty user or password as absent.

### Added

- `URL::http()` and `URL::https()` — parse a string while forcing the given scheme. The scheme is applied whether the string omits it or carries a different one, so `URL::https('http://example.com')` yields `https://example.com/`.
- `URL::user()`, `URL::pass()`, and `URL::withUser()` — userinfo access and modification. Credentials are percent-encoded when written and decoded when read, and a password without a user is rejected in the constructor.

### Fixed

- `URL::parse()` silently dropped userinfo. `https://user:secret@example.com/` came back as `https://example.com/`, losing the credentials with no error; it now parses and rebuilds unchanged.
- `Paginator` threw `DivisionByZeroError` when constructed with `pageSize: 0`.
- `Paginator::offset()` returned a negative offset for a page number below 1, which would reach a query as a negative `OFFSET`.
- `FlowPaginator::nextPage([], true)` produced a paginator claiming more pages while holding a null cursor, sending the next fetch back to the first page — an infinite loop in an infinite-scroll client. An empty page now ends the sequence.
- `URL` emitted raw spaces in the path and fragment, producing invalid URLs, and rendered an empty user name as `https://@example.com/`.

### Documentation

- README rewritten for a three-class package: `Str` / `Arr` sections dropped, `Date` removed, `URL`'s absolute-URL contract, userinfo handling, and query-key behavior documented.

### Migration

| Before | After |
|---|---|
| `URL::parse('example.com/path')` | `URL::https('example.com/path')` or `URL::http('example.com/path')` |
| `new URL()` | `new URL($scheme, $host)` — both required |
| `Date::toTimeString()` | `LocalizedDate::toDateTimeString()` in `migears/i18n` |
| `Date::relativeTime()` | `LocalizedDate::relative()` in `migears/i18n` |
| `Date::humanize()` | `LocalizedDate::humanize()` in `migears/i18n` |
| `Str::slug(...)` | `Illuminate\Support\Str::slug(...)` or `symfony/string` |
| `Arr::get(...)` | `Illuminate\Support\Arr::get(...)` or native array functions |
