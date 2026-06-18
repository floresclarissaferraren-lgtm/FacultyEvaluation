
```php
// Session lifetime: 24 hours (86400 seconds)
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cache_expire', 1440); // 1440 minutes = 24 hours
```

```apache
php_value session.gc_maxlifetime 86400
php_value session.cookie_lifetime 86400
php_value session.cache_expire 1440
```

```php
session_start();
$_SESSION['last_activity'] = time();
```

```javascript
const PING_INTERVAL = 5 * 60 * 1000; // 5 minutes
setInterval(pingServer, PING_INTERVAL);
```

```sql
CREATE TABLE evaluation_periods (...)
CREATE TABLE evaluation_settings (...)
```


Edit `session_keepalive.js`:
```javascript
const PING_INTERVAL = 5 * 60 * 1000;  // 5 minutes
const PING_INTERVAL = 10 * 60 * 1000; // 10 minutes
```
