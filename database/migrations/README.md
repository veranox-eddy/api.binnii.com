# No migrations here — ever

The schema owner is **app.binnii.com**: every table (including the historic
ones this API grew up with) is defined by its `database/migrations/`. The
legacy copies that used to live here were duplicates and have been removed.

The test suite builds its throwaway sqlite schema from
`database/schema/sqlite-schema.sql`, a dump of the app repo's migrations —
regenerate it there when the schema changes (recipe in this repo's README).
Deployment MUST NOT run `php artisan migrate`.
