# Database configuration

## Local XAMPP default

For a basic local XAMPP setup, the app uses these defaults when none of the
`FACULTY_DB_*` environment variables are set:

- Host: `localhost`
- User: `root`
- Password: empty
- Database: `faculty_evaluation_db`

This restores the simple local workflow: import the database into phpMyAdmin,
then run the app. It assumes the local MySQL `root` account has no password.
If Apache has any `FACULTY_DB_*` variables configured, the app requires all
four values and does not use these defaults.

## Recommended account for shared or production deployments

Do not use the local XAMPP defaults on a server accessible to other users or
from the internet. A `root` account with no password gives the application
broad access to the MySQL server. Use a dedicated account instead.

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

Set all four variables in Apache configuration outside the web root (for
example, the XAMPP Apache configuration), then restart Apache:

```apache
SetEnv FACULTY_DB_HOST "localhost"
SetEnv FACULTY_DB_USER "faculty_eval_app"
SetEnv FACULTY_DB_PASSWORD "REPLACE_WITH_THE_SAME_RANDOM_PASSWORD"
SetEnv FACULTY_DB_NAME "faculty_evaluation_db"
```

Keep the password out of source control and out of files served by Apache. To
return to the local defaults, remove all four `FACULTY_DB_*` settings from the
Apache configuration and restart Apache.

Password-reset codes are stored as password hashes, expire after 10 minutes,
and only the newest code per email remains valid. Verification is rate-limited,
and final code consumption and the password update are performed together.
Existing plaintext reset codes are invalidated when a new reset is requested;
users with a pending reset should request a fresh code.

## Evaluation privacy

Students can view only their own evaluation history. Faculty can view reports
only for their own account; administrators can access system-wide reports.
Individual answer rows are not returned by report endpoints, and report data
does not include student names or IDs. The database still links each evaluation
to its student to prevent duplicate submissions, so this is confidentiality in
the reporting interface, not unlinkable storage.

Faculty and report views suppress scores, response counts, category details,
and comments when a selected cohort has fewer than five distinct respondents.
The minimum is defined in `shared/evaluation_privacy.php`; update it only if
institutional evaluation policy specifies a different disclosure threshold.
Free-text comments can themselves identify a respondent, so they follow the
same minimum-size rule.

Account-creation temporary passwords are generated with a cryptographically
secure random source, sent by email only, and are never returned in the admin
or login API response. If delivery fails, the account holder should use Forgot
Password to set a password.

## HTTPS and sessions

In production, set `FACULTY_FORCE_HTTPS` to `1`; the app then redirects HTTP
requests to HTTPS and marks session cookies `Secure` when PHP detects HTTPS.
For TLS-terminating reverse proxies, set `FACULTY_TRUST_PROXY` to `1` only when
the proxy overwrites `X-Forwarded-Proto` and clients cannot spoof that header.
Leave HTTPS enforcement disabled for local HTTP-only XAMPP development.

Sessions expire after 30 minutes without activity and after 8 hours at most.
The dashboard keep-alive request runs only while recent user interaction is
detected.
