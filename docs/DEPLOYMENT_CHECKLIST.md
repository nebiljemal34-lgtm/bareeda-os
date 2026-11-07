# Bareeda Injera - Deployment Checklist

**Application:** Bareeda Injera E-Commerce Website
**Target Environment:** cPanel Shared Hosting (PHP 7.4+, MySQL 5.7+)
**Domain:** www.bareeda-injera.com

---

## Pre-Deployment Preparation

### 1. Code Preparation
- [ ] All security audit fixes applied (see `docs/SECURITY_AUDIT.md`)
- [ ] No `dd()` or `var_dump()` calls left in production code
- [ ] No hardcoded development URLs or paths
- [ ] All assets (CSS, JS, images) are present and optimized
- [ ] Test the application locally with `APP_ENV=production` settings

### 2. Configuration Updates

#### `config/config.php` - Update ALL of these:
- [ ] **DB_PASS** — Change from `CHANGE_ME_IN_PRODUCTION` to a strong, unique MySQL password
  ```php
  define('DB_PASS', 'your-strong-unique-password-here');
  ```
- [ ] **APP_URL** — Verify it matches your production domain exactly
  ```php
  define('APP_URL', 'https://www.bareeda-injera.com');
  ```
- [ ] **APP_EMAIL** — Set to your production email address
  ```php
  define('APP_EMAIL', 'info@bareeda-injera.com');
  ```
- [ ] **CRON_SECRET_KEY** — Change to a random string (minimum 32 characters)
  ```php
  define('CRON_SECRET_KEY', 'generate-a-random-32-char-string-here');
  ```
  Generate one with: `php -r "echo bin2hex(random_bytes(32));"`

#### `public/index.php` - Set production environment:
- [ ] Change APP_ENV from `development` to `production`:
  ```php
  define('APP_ENV', 'production');
  ```
  This must be placed BEFORE the existing `if (!defined('APP_ENV'))` block.

---

## cPanel Setup

### 3. Create MySQL Database
- [ ] Log in to cPanel
- [ ] Go to **MySQL Databases**
- [ ] Create database (e.g., `bareeda_injera`)
- [ ] Create database user with a **strong password**
- [ ] Grant **ALL PRIVILEGES** to the user on the database
- [ ] Note the exact database name, username, and password (cPanel may add prefixes)
- [ ] Update `config/config.php` with the actual DB credentials:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'yourcpanelprefix_bareeda_injera');
  define('DB_USER', 'yourcpanelprefix_bareeda_user');
  define('DB_PASS', 'your-actual-password');
  ```

### 4. Import Database Schema
- [ ] Open **phpMyAdmin** in cPanel
- [ ] Select your database
- [ ] Go to **Import** tab
- [ ] Upload and import `database/schema.sql`
- [ ] Verify all tables were created (users, products, categories, orders, etc.)
- [ ] **IMMEDIATELY change the default admin password:**
  - Option A: Log in to admin panel with `admin@bareeda-injera.com` / `admin123` and change it
  - Option B: Generate a new bcrypt hash and update via phpMyAdmin:
    ```sql
    -- Generate hash with: php -r "echo password_hash('YourNewPassword123!', PASSWORD_BCRYPT, ['cost'=>12]);"
    UPDATE users SET password_hash = '$2y$12$YOUR_NEW_HASH_HERE' WHERE email = 'admin@bareeda-injera.com';
    ```

### 5. Upload Application Files
- [ ] Upload all project files to your cPanel web root (typically `public_html/` or a subdomain directory)
- [ ] **Directory structure on server should be:**
  ```
  /home/yourcpanel/
  ├── bareeda-website/          (or public_html/)
  │   ├── public/               ← Web server document root points HERE
  │   │   ├── index.php
  │   │   ├── .htaccess
  │   │   └── assets/
  │   ├── config/
  │   ├── controllers/
  │   ├── classes/
  │   ├── models/
  │   ├── helpers/
  │   ├── views/
  │   ├── database/
  │   ├── uploads/
  │   └── logs/
  ```

### 6. Configure Web Server Document Root
- [ ] In cPanel, go to **Domains** or **Subdomains**
- [ ] Set the document root to the `public/` directory:
  ```
  /home/yourcpanel/bareeda-website/public/
  ```
- [ ] This ensures `config/`, `classes/`, `models/`, etc. are NOT directly accessible via URL

### 7. Verify .htaccess is Active
- [ ] Test that sensitive directories are blocked:
  - Visit `https://www.bareeda-injera.com/config/config.php` — should get 403 Forbidden
  - Visit `https://www.bareeda-injera.com/.env` — should get 403 Forbidden
  - Visit `https://www.bareeda-injera.com/database/schema.sql` — should get 403 Forbidden
- [ ] Test that HTTPS redirect works:
  - Visit `http://www.bareeda-injera.com` — should redirect to `https://`

### 8. SSL Certificate
- [ ] Install SSL certificate via cPanel **SSL/TLS** or **Let's Encrypt** (free via cPanel's AutoSSL)
- [ ] Verify HTTPS works: `https://www.bareeda-injera.com`
- [ ] Verify HTTP redirects to HTTPS
- [ ] Check that the certificate covers both `www` and non-`www` variants

---

## File Permissions

### 9. Set Correct Permissions
- [ ] **Directories:** `755` (rwxr-xr-x)
  ```bash
  find /path/to/bareeda-website -type d -exec chmod 755 {} \;
  ```
- [ ] **PHP/HTML files:** `644` (rw-r--r--)
  ```bash
  find /path/to/bareeda-website -type f -exec chmod 644 {} \;
  ```
- [ ] **uploads/ directory:** `755` (writable by web server)
  ```bash
  chmod 755 /path/to/bareeda-website/uploads/
  ```
- [ ] **logs/ directory:** `755` (writable by web server)
  ```bash
  chmod 755 /path/to/bareeda-website/logs/
  ```
- [ ] **config/config.php:** `644` (readable, NOT writable by web)
  ```bash
  chmod 644 /path/to/bareeda-website/config/config.php
  ```
- [ ] **CRITICAL:** Ensure `config/`, `classes/`, `models/`, `database/` are NOT web-accessible (blocked by .htaccess)

---

## Post-Deployment Verification

### 10. Functional Testing

#### Customer Flow:
- [ ] Homepage loads correctly
- [ ] Product listing page shows products
- [ ] Product detail page shows product info and images
- [ ] Search functionality works
- [ ] Add to cart works (as guest)
- [ ] Cart page displays items correctly
- [ ] Registration form works
- [ ] Login works with correct credentials
- [ ] Login fails with wrong password (check lockout after 5 attempts)
- [ ] Checkout flow completes successfully
- [ ] Order confirmation page displays
- [ ] Order history shows past orders
- [ ] Account update works
- [ ] Password change works
- [ ] Logout works
- [ ] Subscription creation works
- [ ] Coupon application works (try code: WELCOME10)

#### Admin Flow:
- [ ] Admin login works
- [ ] Admin dashboard loads
- [ ] Product CRUD (create, read, update, delete)
- [ ] Product image upload works
- [ ] Order list and detail views work
- [ ] Order status update works
- [ ] Payment status update works
- [ ] User list and detail views work
- [ ] User role change works
- [ ] Category CRUD works
- [ ] Coupon management works
- [ ] Delivery zone management works
- [ ] Sales report loads with date filtering
- [ ] Product report loads
- [ ] Customer report loads
- [ ] Subscription list and management works

### 11. Security Verification
- [ ] CSRF tokens present in all forms (inspect HTML for hidden `csrf_token` input)
- [ ] Session cookies have correct flags (check browser DevTools > Application > Cookies):
  - `HttpOnly` flag: YES
  - `Secure` flag: YES
  - `SameSite`: Strict
- [ ] Admin pages return 403 when accessed by non-admin users
- [ ] Admin pages redirect to login when accessed by unauthenticated users
- [ ] Direct access to `config/`, `classes/`, `models/` returns 403
- [ ] Error messages don't reveal system paths or database details
- [ ] File upload rejects non-image files
- [ ] File upload rejects images larger than 5MB

### 12. Cron Job Setup
- [ ] Set up a cron job in cPanel to process subscriptions:
  - Go to **cPanel > Cron Jobs**
  - Add a new cron job:
    ```
    Command: curl -s "https://www.bareeda-injera.com/cron/process-subscriptions?key=YOUR_CRON_SECRET_KEY"
    Schedule: Every hour (or as needed)
    ```
    Or if your host supports PHP CLI:
    ```
    Command: php /home/yourcpanel/bareeda-website/controllers/CronController.php
    Schedule: Every hour
    ```
  - Common schedule: `0 * * * *` (every hour at minute 0)
- [ ] Test the cron URL manually in browser to verify it works
- [ ] Verify the response JSON shows `"success": true`

---

## Performance & Optimization

### 13. PHP Configuration (via cPanel PHP Settings or .user.ini)
- [ ] `memory_limit` = 128M (or higher)
- [ ] `max_execution_time` = 30
- [ ] `upload_max_filesize` = 5M (matching MAX_UPLOAD_SIZE)
- [ ] `post_max_size` = 10M
- [ ] `display_errors` = Off
- [ ] `log_errors` = On
- [ ] `opcache.enable` = 1 (if available)

### 14. Database Optimization
- [ ] Verify indexes exist on frequently queried columns (check schema.sql)
- [ ] Consider enabling MySQL query cache (if available on your host)
- [ ] Set up automated database backups (daily minimum)

### 15. Asset Optimization
- [ ] Minify CSS files for production
- [ ] Minify JavaScript files for production
- [ ] Optimize/compress product images (use WebP where possible)
- [ ] Consider adding cache headers for static assets via .htaccess:
  ```apache
  <IfModule mod_expires.c>
      ExpiresActive On
      ExpiresByType image/jpeg "access plus 1 month"
      ExpiresByType image/png "access plus 1 month"
      ExpiresByType image/webp "access plus 1 month"
      ExpiresByType text/css "access plus 1 week"
      ExpiresByType application/javascript "access plus 1 week"
  </IfModule>
  ```

---

## Monitoring & Maintenance

### 16. Set Up Monitoring
- [ ] Check `logs/php_errors.log` regularly for errors
- [ ] Check `logs/app.log` for application events
- [ ] Monitor disk space usage (uploads, logs, database)
- [ ] Set up uptime monitoring (e.g., UptimeRobot - free tier)
- [ ] Monitor for repeated failed login attempts

### 17. Backup Strategy
- [ ] **Database:** Automated daily backups via cPanel Backup Wizard
- [ ] **Files:** Weekly full backup of all application files
- [ ] **Uploads:** Include in file backup (product images)
- [ ] **Offsite:** Store at least one backup copy offsite (different from hosting)
- [ ] **Test:** Verify you can restore from backup (test quarterly)

### 18. Ongoing Security
- [ ] Keep PHP version updated (minimum 7.4, recommend 8.1+)
- [ ] Update any third-party libraries periodically
- [ ] Review `logs/` files weekly for suspicious activity
- [ ] Change admin password periodically (every 90 days)
- [ ] Review user accounts quarterly (remove inactive/suspicious accounts)
- [ ] Monitor for new CVEs affecting PHP/MySQL

---

## Go/No-Go Checklist

Before announcing the site as live, ALL of these must be true:

- [ ] All configuration values updated (DB password, APP_URL, CRON_SECRET_KEY)
- [ ] APP_ENV set to `production`
- [ ] Admin password changed from default
- [ ] SSL certificate installed and working
- [ ] HTTPS redirect working
- [ ] Sensitive directories return 403
- [ ] Customer flow works end-to-end
- [ ] Admin flow works end-to-end
- [ ] Cron job configured and tested
- [ ] File permissions set correctly
- [ ] Backups configured
- [ ] Error logging working (check logs/ directory)
- [ ] No development credentials or test data visible
- [ ] Security audit fixes verified (see `docs/SECURITY_AUDIT.md`)

---

## Emergency Contacts & Procedures

### If the site goes down:
1. Check cPanel error logs
2. Check `logs/php_errors.log`
3. Verify database connection (check credentials in `config/config.php`)
4. Check file permissions
5. Contact hosting provider support

### If a security breach is suspected:
1. Put the site in maintenance mode (create a static `index.html` in `public/`)
2. Change all passwords (database, admin, FTP, cPanel)
3. Regenerate `CRON_SECRET_KEY`
4. Review `logs/` for suspicious activity
5. Check database for unauthorized changes
6. Restore from last known good backup if needed
7. Contact a security professional for incident response

---

*Last updated: 2026-11-08*
*For questions about this checklist, refer to `docs/SECURITY_AUDIT.md`*
