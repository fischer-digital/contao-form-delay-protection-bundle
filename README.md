# tbo-contao-form-delay-protection-bundle

Spam protection for [Contao](https://contao.org) forms. Combines a time-based check (minimum delay between page load and form submission using HMAC-signed timestamps) with pattern-based spam filters and an optional silent drop.

## How it works

### Time-based protection

1. When the form is rendered, the bundle injects a hidden field containing a **UNIX timestamp** and an **HMAC-SHA256 signature** (signed with the application's kernel secret).
2. On submission, the server verifies the signature and checks whether the configured minimum delay has elapsed.
3. If the form was submitted too quickly, an error message is shown and the form is **not processed** (no email sent, no data stored).

This approach is **completely session-independent** — it works reliably with HTTP caching, AJAX forms and without session cookies.

### Pattern-based protection

Optionally, the submitted values are checked against typical spam patterns (see [Spam patterns](#spam-patterns)). If a pattern matches, the form is rejected — or silently dropped, see [Silent drop](#silent-drop).

### Silent drop

With the **Silently drop spam** option, detected spam submissions (pattern match or submitted in less than 3 seconds) show the regular success message but are **not** sent or stored — no email, no database record, no session data. Bots get no feedback that they have been detected. Every silent drop is logged as a warning.

## Requirements

- Contao 5.7+
- PHP 8.1+

## Installation

### Via Composer (recommended)

```bash
composer require fischerdigital/contao-form-delay-protection-bundle
```

### Manual installation

Clone or download this repository into your `vendor/fischerdigital/contao-form-delay-protection-bundle` directory and add a path repository to your `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "vendor/tbo/contao-form-delay-protection-bundle",
        "options": { "relative": true, "symlink": true }
    }
]
```

Then run:

```bash
composer install
```

## Configuration

1. Open the Contao backend and navigate to **Forms**.
2. Edit the form you want to protect.
3. In the **Form configuration** legend, enable **Enable time-based spam protection**.
4. Select the **Minimum time** (3, 5, 10 or 15 seconds, default: 5).
5. Optionally enable **Enable regex spam protection** to activate the pattern checks. In its subpalette you can list **Exceptions (field names)** — a comma-separated list of form field names that are excluded from the regex checks (e.g. `message,bemerkung`).
6. Optionally enable **Silently drop spam** (see [Silent drop](#silent-drop)).

That's it — no JavaScript, no additional configuration.

## Spam patterns

The regex spam protection checks the submitted values against three patterns:

| # | Pattern | Example | Applied to |
|---|---------|---------|------------|
| 1 | Consonant gibberish: 5 or more consecutive consonants (`/[b-df-hj-np-tv-z]{5,}/i`) | `xjkrtw` | Single-line text fields only (name/street-like inputs)¹ |
| 2 | Repeated letters: 3 or more identical consecutive letters (`/(.)\1{2,}/i`) | `rrrttzr` | Single-line text fields only¹ |
| 3 | Case jumble: unusual upper/lowercase mixing within a word (`/[a-z]{2,}[A-Z]{2,}/`) | `aggZJZAK` | All textual values (including textareas)² |
| 4 | Dot trick: 3 or more dots in the local part of an email address (any domain) | `j.o.h.n.doe@gmail.com` | Email-like values |

¹ Patterns 1 and 2 are deliberately **not** applied to free text: German compound words can contain long consonant clusters ("selbstständig", "Herbstschmuck") and casual writing often repeats letters ("Jaaa", "sooo").

² Pattern 3 can also match legitimate mixed-case terms such as "OpenAI" or "PowerBI" (lowercase run followed by an uppercase run).

Individual fields can be excluded from all regex checks via the **Exceptions (field names)** option (comma-separated list of field names, e.g. `message,bemerkung`).

## Silent drop

If **Silently drop spam** is enabled, the following submissions show the regular success message (or redirect) but are dropped without any processing:

- submissions matching one of the [spam patterns](#spam-patterns)
- submissions arriving **less than 3 seconds** after the form was rendered (a fixed hard floor, independent of the configured minimum time)

"Without processing" means: **no email is sent, no data is stored in the target table and nothing is written to the session**. Each drop is written to the Contao system log (back end → System-Protokoll, action `FORMS`) and to the monolog log, e.g. `Form "Contact" (ID 4): submission silently dropped (reason: regex_spam), no data was processed.`

Note: third-party `processFormData` hooks still run (Contao offers no way to skip them), but the core sending/storing mechanisms are safely disabled.

## How the token works

The hidden field value format is `timestamp.hmac`:

- **timestamp**: UNIX timestamp (seconds) at render time
- **hmac**: HMAC-SHA256 over `timestamp|formId`, signed with `%kernel.secret%`

The HMAC prevents bots from submitting arbitrary timestamps. The signature can only be generated by the server, making it tamper-proof.

## Security

- The HMAC is signed with the application's `kernel.secret` — the same secret used for CSRF tokens and session signing.
- The token is regenerated on every page load (including AJAX re-renders after failed submissions).
- The signature binds the timestamp to the specific form ID, preventing token reuse across forms.

## Compatibility

This bundle works alongside other spam protection methods:

- Contao's built-in **security question** and **honeypot**
- [ALTCHA](https://altcha.org) (antispam widget)
- Any other form validation

## Upgrading from 0.9.x (BC break)

Since version 0.10 the bundle stores its settings as **virtual fields** in the shared `jsonData` column of `tl_form` (a Contao 5.7 feature) instead of dedicated columns. When upgrading, run:

```bash
php vendor/bin/contao-console contao:migrate
```

The bundled migration copies the values of the old columns (`enableTimeBasedSpamProtection`, `minLoadTime`) into `jsonData` and then drops the old columns. Contao 5.7+ is required from now on.

## License

MIT
