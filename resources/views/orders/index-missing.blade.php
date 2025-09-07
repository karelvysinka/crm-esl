@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="ti ti-database-off me-2 fs-4"></i>
        <div>
            Tabulky objednávek zatím nejsou vytvořeny. Spusťte migrace:
            <pre class="mb-2 mt-2"><code>php artisan migrate --force
php artisan orders:permissions-sync
php artisan orders:import-full --dry-run # volitelný test
php artisan orders:import-full</code></pre>
            Poté obnovte tuto stránku.
        </div>
    </div>
</div>
@endsection
