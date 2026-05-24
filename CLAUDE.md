# GraphQL App — AI Instructions

This is the **GraphQL API layer** for the Track Any Device platform. It is a standalone
Laravel 13 app backed by **Nuwave Lighthouse** and consumed by the central Next.js marketing
site, M2M integrations, and the GraphQL Explorer UI.

Read this file before making any change.

---

## Purpose

- Expose a typed GraphQL API at `POST /graphql`
- Serve a browser-based **GraphQL Explorer** at `GET /` (central staff only)
- Act as the **public content API** for the central Next.js site (device types, solutions, etc.)
- Act as the **authenticated data API** for central staff (users, tenants, devices)

---

## Rule 1 — Plan before implementing

Before writing any code, ask clarifying questions. Present a plan and get explicit agreement.
Only begin implementation once the approach is confirmed. Never start on an ambiguous requirement.

---

## Rule 2 — Always use `@guard(with: ["sanctum"])`, never bare `@guard`

Lighthouse's `@guard` directive without a `with:` argument falls back to
`auth.defaults.guard` which is `web` — not `sanctum`. The `web` guard has no session
in API requests, so field-level auth silently passes without a real user.

**Wrong:**
```graphql
users: [User!]! @all @guard
```

**Right:**
```graphql
users: [User!]! @all @guard(with: ["sanctum"])
```

`@auth` is the exception — it reads the guard from `config/lighthouse.php` (`'guard' => 'sanctum'`)
and is safe to use without `with:`.

---

## Rule 3 — Never enable Lighthouse query cache

`config/lighthouse.php` has `query_cache.enable = false`. Do not enable it.

**Why:** Lighthouse serialises `DocumentNode` PHP objects to Redis. After a deploy (or
between requests if the class definition changes), they deserialise as `__PHP_Incomplete_Class`,
breaking introspection and all queries.

The schema cache (`cache.enable`) is safe — it caches the compiled SDL string, not a PHP object.
Only the **query** cache is forbidden.

---

## Rule 4 — `Fortify::ignoreRoutes()` must be in `register()`, not `boot()`

`AppServiceProvider::register()` calls `Fortify::ignoreRoutes()`. Do not move this to `boot()`.

**Why:** `FortifyServiceProvider` boots before `AppServiceProvider`. If `ignoreRoutes()` is
called in `boot()`, Fortify has already registered `/login`, `/register`, etc. — which conflict
with the SSO-driven routes in this app.

---

## Rule 5 — `rebing/graphql-laravel` must stay in `dont-discover`

`composer.json` has:
```json
"extra": {
    "laravel": {
        "dont-discover": ["rebing/graphql-laravel"]
    }
}
```

`rebing/graphql-laravel` is a dev-time dependency (used by the explorer tooling) but its
service provider must never auto-register — Lighthouse is the sole GraphQL handler. If
`rebing` boots, it registers a conflicting `/graphql` route and breaks everything.

---

## Rule 6 — Do not add `auth:sanctum` to the `/graphql` route middleware

`config/lighthouse.php` route middleware is `['graphql.key']` only.

**Why:** `auth:sanctum` would reject M2M API key callers with a 401 before
`GraphQlApiKey` middleware has a chance to set the user. Authentication at the
`/graphql` route level is handled by `GraphQlApiKey`; authentication at the
**field level** is handled by `@guard(with: ["sanctum"])` and `@auth` in the schema.

---

## Namespace

All PHP classes use the `GraphQlApp\` namespace:

```
GraphQlApp\Http\Controllers\
GraphQlApp\Http\Middleware\
GraphQlApp\Models\
GraphQlApp\Providers\
GraphQlApp\GraphQL\Queries\
GraphQlApp\GraphQL\Mutations\
GraphQlApp\GraphQL\Directives\
GraphQlApp\GraphQL\Scalars\
```

---

## PHP Packages Required

| Package | Role |
|---|---|
| `track-any-device/core` | `User`, `Tenant`, `Device`, `DeviceType` models; `Role` enum |
| `track-any-device/sso-client` | `SsoClientServiceProvider` — configures Socialite `sso` driver from DB |
| `nuwave/lighthouse` | GraphQL server |
| `laravel/socialite` | SSO login for the Explorer UI |

Does **not** require: `sso-server`, `admin`, `jt808`, `drivers`, `mcp`, `sms-gateway`.

---

## Authentication Model

There are two caller types for `POST /graphql`:

### Machine-to-machine (M2M)

```
Authorization: Bearer {GRAPHQL_KEY}
X-Api-Secret: {GRAPHQL_SECRET}
```

`GraphQlApiKey` middleware validates both headers with `hash_equals`, then binds a
virtual system `User` and calls `Auth::shouldUse('sanctum')` so that `@guard(with: ["sanctum"])`
is satisfied. The virtual user has no `role` attribute — it bypasses the Role enum cast.

### Browser session (Explorer UI)

1. `GET /login` → `Socialite::driver('sso')->redirect()` → `login.*/oauth/authorize`
2. Passport issues auth code → `GET /sso/callback?code=...`
3. `SsoCallbackController` exchanges code → `Auth::login($user)` → redirect to `/`
4. Subsequent requests carry the Sanctum session cookie

### Public queries (no auth)

`deviceTypes`, `deviceType`, `featuredDeviceTypes`, `chips`, `computeBoards`,
`sensors`, `solutions`, `featuredSolutions` — no `@guard` or `@auth` on these.
They are consumed by the central Next.js site without credentials.

---

## Explorer UI Access

`GET /` is gated by:

```
middleware(['auth', 'central.staff'])
```

`central.staff` (`RequiresCentralStaff`) checks `$user->role` is one of
`Role::Admin`, `Role::Supervisor`, or `Role::Staff`. All other authenticated users
get a 403.

The Explorer itself renders `resources/views/explorer.blade.php`.

---

## OAuth Client

| Field | Value |
|---|---|
| Kind | `OAuthClientKind::GraphQl` — string `'graphql'` |
| Prefix | `tci_graphql_` |
| Seeded by | `OAuthClientSeeder` |
| `APP_SURFACE` | `graphql` |

`APP_SURFACE=graphql` in the environment tells `SsoClientServiceProvider` which
`oauth_clients` row to load for the Socialite `sso` driver.

---

## Schema Conventions (`graphql/schema.graphql`)

### Public vs authenticated split

Public fields (no auth directive):
```graphql
deviceTypes: [DeviceType!]! @all(scopes: ["active"])
```

Authenticated fields (must include `@guard`):
```graphql
users: [User!]! @all @guard(with: ["sanctum"])
tenants: [Tenant!]! @all @guard(with: ["sanctum"])
devices: [Device!]! @all @guard(with: ["sanctum"])
```

`me` uses `@auth` (reads the configured `sanctum` guard):
```graphql
me: User @auth(guard: "sanctum")
```

### Eloquent directives

Use Lighthouse's built-in Eloquent directives where possible:

| Directive | Use case |
|---|---|
| `@all` | Return all records of a model |
| `@find` | Return a single record by argument |
| `@paginate` | Paginated lists (default 15, max 100) |
| `@belongsTo` | Eager-load a belongs-to relation |
| `@belongsToMany` | Eager-load a many-to-many relation |
| `@eq` | Filter by exact value on an argument |
| `@orderBy` | Sort results |

Custom resolvers live in `app/GraphQL/Queries/` (`GraphQlApp\GraphQL\Queries\`).

### Model namespaces

Lighthouse resolves model classes from:
1. `TrackAnyDevice\Core\Models` (primary — User, Tenant, Device, DeviceType…)
2. `GraphQlApp\Models` (app-local fallback)

Name types to match model class names exactly. `Device` resolves to
`TrackAnyDevice\Core\Models\Device` automatically.

---

## Route Map

| Method | Path | Guard | Purpose |
|---|---|---|---|
| `GET` | `/login` | public | Redirect to SSO provider |
| `GET` | `/sso/callback` | public | Exchange SSO code for session |
| `GET` | `/` | `auth` + `central.staff` | GraphQL Explorer UI |
| `POST` | `/logout` | `auth` | Invalidate session |
| `POST` | `/graphql` | `graphql.key` | GraphQL endpoint |

`/graphql` is registered entirely by Lighthouse — no stub in `routes/api.php`.

---

## Lighthouse Config Highlights

| Setting | Value | Reason |
|---|---|---|
| `guard` | `sanctum` | Default guard for `@auth` |
| `query_cache.enable` | `false` | Prevents `__PHP_Incomplete_Class` on Redis |
| `cache.enable` | env-driven (true in prod) | SDL string cache — safe |
| `introspection` | env `LIGHTHOUSE_INTROSPECTION` (default true) | Disable in prod if needed |
| `security.max_query_complexity` | 200 | Prevents runaway nested queries |
| `security.max_query_depth` | 15 | |
| `pagination.default_count` | 15 | |
| `pagination.max_count` | 100 | |
| `namespaces.models` | `TrackAnyDevice\Core\Models`, `GraphQlApp\Models` | Core models first |

---

## Environment Variables

| Variable | Purpose |
|---|---|
| `APP_KEY` | Laravel encryption key |
| `APP_ENV` | `local` / `production` |
| `APP_SURFACE` | Must be `graphql` — tells `SsoClientServiceProvider` which OAuth client to load |
| `APP_DOMAIN` | Base domain |
| `GRAPHQL_KEY` | M2M bearer token |
| `GRAPHQL_SECRET` | M2M secret header (`X-Api-Secret`) |
| `DB_*` | MySQL connection (shared central database) |
| `SESSION_DOMAIN` | `.track-any-device.com` — shared session cookie across all subdomains |
| `LIGHTHOUSE_INTROSPECTION` | `true` / `false` |
| `LIGHTHOUSE_DISABLE_INTROSPECTION` | `0` / `1` (int, not bool) |
| `LIGHTHOUSE_CACHE_ENABLE` | SDL schema cache — safe to enable in prod |
| `LIGHTHOUSE_QUERY_CACHE_ENABLE` | Must remain `false` — see Rule 3 |

---

## Adding a New Query

1. Add the field to `graphql/schema.graphql`
2. If it uses an Eloquent model directly, use `@all` / `@find` / `@paginate` — no resolver needed
3. If custom logic is required, create a class in `app/GraphQL/Queries/FooQuery.php`
   with namespace `GraphQlApp\GraphQL\Queries` and a `__invoke(mixed $root, array $args)` method
4. Reference it in the schema with `@field(resolver: "GraphQlApp\\GraphQL\\Queries\\FooQuery")`
5. Apply `@guard(with: ["sanctum"])` if the field requires authentication

## Adding a New Mutation

1. Add to `schema.graphql` under `type Mutation {}`
2. Create `app/GraphQL/Mutations/FooMutation.php`
3. Always add `@guard(with: ["sanctum"])` — mutations are never public
4. Validate input with `@rules` or a dedicated input type with `@validator`

## Adding a New Type

1. Define it in `schema.graphql`
2. If backed by a model in `TrackAnyDevice\Core\Models`, no PHP class needed — Lighthouse
   resolves it automatically via the `namespaces.models` config
3. If it needs a custom resolver, create a class in `app/GraphQL/` under the appropriate
   sub-namespace

---

## What Does NOT Belong Here

- Inertia pages, React components, or any frontend beyond the Explorer blade view
- Fortify auth routes (`/login` here is SSO-only — no local credential login)
- Tenant-scoped data endpoints (tenant data is served from the `my.*` / `tenant.*` surfaces)
- Any Passport OAuth2 server logic — that lives in `sso-server` package
- `rebing/graphql-laravel` service provider auto-registration (kept in `dont-discover`)
