# Live monitoring

Open /Cybersecurity/admin.php for the administrator login and /Cybersecurity/student-portal/login.php for the student login. Administrator and student sessions are separate.

MySQL database: plmun_cybersecurity. Default local XAMPP connection is root with an empty password. Set CYBER_DB_USER and CYBER_DB_PASSWORD in the PHP environment for a different database account.

Student login success/failure, logout, page views, rejected protected-page requests, and administrator incident updates are collected server side. Passwords, session cookies, and CSRF tokens are never event fields. IP addresses use REMOTE_ADDR, not untrusted forwarding headers.

Rules: five failed logins per IP within ten minutes; unauthorized protected access; thirty page views per IP within one minute. Matching incidents generate local alert records. The same rule and IP are deduplicated for ten minutes. Detection locks serialize simultaneous requests per IP. High activity signals need human review, particularly on shared campus IPs.

The dashboard reads real MySQL records, refreshes every fifteen seconds, exports the latest 10,000 events as CSV, and supports saved incident status changes. Tests used the account label integration-check; these are real test events in the log.

Gmail sending is not configured. Alerts are local records and are never displayed as delivered email. No external network, Gmail login, device malware, or school-wide activity is monitored. Rules run synchronously; no background worker is installed. Event collection failures are written to PHP's error log and do not prevent student access. The health tab shows the latest recorded event to help identify stale collection.

Only one administrator and one student account are configured. User provisioning, email delivery, and production deployment hardening remain separate work. The student's requested demo password is weak and should be changed before deployment.
