# PLMUN Cyber Watch — starter

A PHP interface starter based on the Cybersecurity Figma dashboard. It includes overview, event log, access roles, source collection, detection rules, incidents, Gmail alerts, reports, and system health screens, using sample data and a starter MySQL schema.

## Run with XAMPP

1. Place this folder at `C:\xampp\htdocs\Cybersecurity`.
2. Start Apache in the XAMPP Control Panel.
3. Open `http://localhost/Cybersecurity/`.
4. To prepare the database, import `database/schema.sql` using phpMyAdmin or the MySQL CLI.

The screens use sample data. Database persistence, real authentication and permissions, portal event collection, rule execution, Gmail OAuth and delivery, scheduled reports, and worker monitoring still need implementation. Alert and status controls explain when an action is only a preview.
