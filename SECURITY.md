# Security Policy

## Supported versions

Security fixes are applied on the latest tagged release of `voodflow/voodbuilder` on the `main` branch. Older `0.0.x` tags are not supported.

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security problems.

Email or contact form: **https://voodflow.com** (prefer “security” in the subject).

Include:

- affected package version / commit
- description and impact
- steps to reproduce or a proof of concept (if safe to share privately)
- whether the issue is already public elsewhere

We aim to acknowledge reports within a few business days. Please give us a reasonable window to patch before disclosure.

## Scope

In scope: authentication/authorisation flaws in the public site or Filament admin surfaces shipped by this package, XSS/CSRF in editor/public chrome, unsafe defaults that expose customer sites.

Out of scope: issues that require a compromised host app, misconfigured deployment, or third-party companions not maintained in this repository.
