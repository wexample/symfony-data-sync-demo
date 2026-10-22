`symfony-data-sync-demo` gives the showcase something to reconcile on the `symfony-data-sync-ds` screens: a contact list synchronised with two fake remotes, a chat and a CRM, whose fixtures hold one case per decision the engine makes.

- `demo_chat` pairs by email then username, leaves bots out, disables the account of an inactive contact, creates one for a contact without any, pulls display names and owns emails on both sides. Its plan shows an update, a disable, a link to a vanished account, a field conflict, an exact match, a creation and an unmatched account.
- `demo_crm` pairs by fuzzy name: it shows an automatic link, a candidate a human links from the screen, and an unmatched record.

Once applied, the chat still holds two conflicts (`bob`, `finn`) that the screen settles by keeping one side.

Everything lives in JSON files under `var/data_sync_demo/`, copied from `src/Resources/fixtures/` on first read: no database, no request leaves the application. `bin/console data-sync-demo:reset` restores the fixtures once the demo has been applied.

Install it with `symfony-data-sync` and `symfony-data-sync-ds`, then open `/data-sync/`.
