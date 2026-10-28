# symfony-user-ds

Version: 7.0.0

Design-system screens for symfony-user: login, second factor, password, terms, authenticator app

## Table of Contents

- [Installation](#installation)

## Installation

```php
// config/bundles.php
Wexample\SymfonyUser\WexampleSymfonyUserBundle::class => ['all' => true],
Wexample\SymfonyUserDs\WexampleSymfonyUserDsBundle::class => ['all' => true],
```

The screens of symfony-user, drawn with the design system. symfony-user holds the logic, the forms and their texts, the mails; this package holds the pages and what the forms look like.

| Route | Path | What |
|---|---|---|
| `user_security_login` | `/login` | password form, magic link form, link to the reset |
| `user_security_two_factor` | `/login/2fa` | code form, resend, cancel |
| `user_password_forgot` | `/password/forgot` | reset request |
| `user_password_new` | `/password/new` | new password, once the reset or the activation is proven |
| `user_password_activation_invalid` | `/password/activation-invalid` | where a dead activation link leads |
| `user_terms_index` | `/account/terms` | the terms gate |
| `user_totp_index` | `/account/authenticator` | the authenticator app: backup codes, turn it off |
| `user_totp_backup_codes` | `/account/authenticator/backup-codes` | the new backup codes, shown once |
| `user_totp_setup_index` | `/account/authenticator/setup/` | the setup tunnel |
| `user_account_picker` | `/login/accounts` | sign in by choosing an account — where the firewall lists `AccountPickerAuthenticator`, 404 otherwise |
| `user_memberships_index` | `/memberships/{kind}:{id}` | the members of a group, for its members to read; its administrators change them in a modal and add new ones, searched for |
| `user_memberships_show` | `/memberships/{kind}:{id}:{membership}/member` | one member: its grants, or its leaving |
| `user_impersonate_index` | `/account/impersonate` | choose an account to impersonate — all listed when few, searched otherwise —; while impersonating, the next one or the way back |

The names are symfony-user's `Routing\UserRoute`. Templates live under `assets/` and are overridden like any `symfony-loader` template; the ones an application includes:

- `@WexampleSymfonyUserDsBundle/forms/<form>.html.twig`, through `form_load()` — the login form on any page;
- `@WexampleSymfonyUserDsBundle/tunnels/partials/login.html.twig` and `user_mail.html.twig`, in the tunnel steps of symfony-user;
- `@WexampleSymfonyUserDsBundle/partials/user_menu.html.twig`, the account menu.

## Groups and their members

Where the application has groups (symfony-user, "Memberships"), it declares one `MembershipManagerInterface` per kind of group, then links to the members page from its own pages:

```twig
{%- if memberships_url(team) -%}
    {{ button_link('ph:bold/users-three', 'Members', memberships_url(team)) }}
{%- endif -%}
```

`memberships_url()` is null for an account neither member of the group nor administering it — the page answers it 404. The member to add is picked with symfony-search's entity field: the member class carries `#[Searchable]`, and whoever administers a group can find every account it makes searchable. The profile page lists the groups of the account (`user_memberships()`), each with its grants and its members page.

A change made — from the page, or from the member's modal — reloads the members page: the loader has no way yet to refresh a page under a modal once its form succeeded.

## The accounts table

The list of `/accounts` is a vue table (`components/account-table`) reading `GET /_api/user/list`: every account as `UserDto` sends it — address, name, roles, state, last sign-in —, typed `user` whatever the application's user class. The application's api client lists symfony-user's repository and schema:

```ts
import getUserEntitySchemas from '@wexample/symfony-user/Common/generatedEntitySchemas';
import userRepositories from '@wexample/symfony-user/Common/generatedRepositories';

protected getRepositoryClasses() { return [...userRepositories]; }
getEntitySchemas() { return { ...getUserEntitySchemas() }; }
```

An application importing the bundles' routes by hand imports symfony-user's `@WexampleSymfonyUserBundle/Resources/config/routes.yaml`, which holds the api controllers as well. The list is read on the page's session, under `/_api/` as symfony-api's `usage/route-prefixes` sets it: the application's session firewall covers it, and its api client reads `baseUrl: '/_api/'`. Creating an account is `/accounts/create` (`user_accounts_create`), opened in a modal from the page's toolbar.
