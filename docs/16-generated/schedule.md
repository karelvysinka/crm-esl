# Schedule (generováno)

| Typ | Cíl | Frekvence | Queue | Poznámka |
|-----|-----|-----------|-------|----------|
| command | backup:run |  |  |  |
| command | backup:clean | weeklyOn(1, '4:00') |  |  |
| job | \App\Jobs\ActiveCampaignSyncJob | everyMinute |  |  |
| job | \App\Jobs\Ops\EvaluateBackupHealthJob | everyTenMinutes | ops |  |
| call | closure |  |  |  |
