# Schedule (generováno)

| Typ | Cíl | Frekvence | Queue | Poznámka |
|-----|-----|-----------|-------|----------|
| command | backup:run |  |  |  |
| command | backup:clean | weeklyOn(1, '4:00') |  |  |
| job | \App\Jobs\ActiveCampaignSyncJob | everyMinute |  |  |
| job | \App\Jobs\Ops\EvaluateBackupHealthJob | everyTenMinutes | ops |  |
| call | closure |  |  |  |
| command | products:import-full |  |  |  |
| command | products:sync-availability |  |  |  |
| command | orders:sync-incremental |  |  |  |
| command | orders:import-full | weeklyOn(7, '03:40') |  |  |
| command | orders:reconcile-recent --pages=5 |  |  |  |
| command | orders:backfill-items --limit=150 |  |  |  |
| command | orders:integrity-check --limit=500 |  |  |  |
