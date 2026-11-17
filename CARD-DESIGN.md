# Dashboard card styling

Use a thin, 3px bottom accent for cards that summarize a number, a service, a connection, or a detection rule. Keep detailed work surfaces flat with a solid background and neutral border.

| Area | Bottom accent | Flat |
| --- | --- | --- |
| Overview | Four summary metrics | Trend, risk, breakdown, recent-event panels |
| Event Log | Summary metrics when present | Filters and event table |
| Authentication & Roles | Summary metrics when present | Accounts and permissions tables |
| Sources & Collection | Portal and database connection cards | Collection categories, payload, request forms |
| Threat Detection | Three rule cards | Header, filters, configuration note, match table |
| Incidents | Four incident summary cards | Queue, investigation, evidence, timeline, status form |
| Gmail Alerts | Summary metrics when present | Connection form and delivery history |
| Reports & Analytics | Four summary cards | Charts and schedule/export table |
| System Health | Measurement and service cards | Collection header, charts, activity table, diagnostics |

Purple identifies rules and aggregate summaries. Blue identifies collection, sources, and services. Amber identifies pending work, rose identifies high priority or failed activity, and green identifies completed incidents. Service accent colors identify the card category; the text badge communicates actual service status.

The shared implementation is assets/card-system.css, loaded after individual tab styles. Do not add competing top or side accents to these cards.
