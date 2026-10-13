# Security configuration

The application requires a dedicated MySQL account. Do not configure it to use
`root` or an empty password.

## Current XAMPP setup

The local XAMPP installation has been configured with a randomly generated
password and the `faculty_eval_app` account. The credentials are stored outside
the web root in
`C:\xampp\apache\conf\extra\faculty-evaluation-env.conf`. That file is readable
only by the Apache account, SYSTEM, and local Administrators. Apache includes
it through `httpd-xampp.conf`.

Do not copy the generated password into this repository. If the Apache
configuration needs to be moved or restored, create a fresh password, update
the MySQL account and the external Apache environment file together, then
restart Apache.

## MySQL account

Run the following as a MySQL administrator, replacing the password with a
unique, randomly generated value:

```sql
CREATE USER 'faculty_eval_app'@'localhost' IDENTIFIED BY 'REPLACE_WITH_A_LONG_RANDOM_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX
    ON faculty_evaluation_db.* TO 'faculty_eval_app'@'localhost';
```

The application performs schema checks and some schema updates at runtime, so
the account needs the listed schema-scoped `CREATE`, `ALTER`, and `INDEX`
privileges. Do not grant privileges on `*.*`.

## Apache environment

Set these variables in Apache configuration outside the web root (for example,
the XAMPP Apache configuration), then restart Apache:

```apache
SetEnv FACULTY_DB_HOST "localhost"
SetEnv FACULTY_DB_USER "faculty_eval_app"
SetEnv FACULTY_DB_PASSWORD "REPLACE_WITH_THE_SAME_RANDOM_PASSWORD"
SetEnv FACULTY_DB_NAME "faculty_evaluation_db"
```

The app intentionally returns an error instead of falling back to MySQL root
when the configuration is missing. Keep the password out of source control and
out of files served by Apache.

## HTTPS and sessions

In production, set `FACULTY_FORCE_HTTPS` to `1`; the app then redirects HTTP
requests to HTTPS and marks session cookies `Secure` when PHP detects HTTPS.
For TLS-terminating reverse proxies, set `FACULTY_TRUST_PROXY` to `1` only when
the proxy overwrites `X-Forwarded-Proto` and clients cannot spoof that header.
Leave HTTPS enforcement disabled for local HTTP-only XAMPP development.

Sessions expire after 30 minutes without activity and after 8 hours at most.
The dashboard keep-alive request runs only while recent user interaction is
detected.
