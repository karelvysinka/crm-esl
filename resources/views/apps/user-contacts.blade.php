@extends('layouts.vertical', ['title' => 'Uživatelé', 'topbarTitle' => 'Uživatelé'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header justify-content-between d-flex gap-2 align-items-center">
                <h4 class="mb-0">Správa uživatelů</h4>

                <form method="GET" action="{{ route('apps.users.index') }}" class="d-flex align-items-start flex-wrap justify-content-sm-end gap-2">
                    <div class="d-flex align-items-start flex-wrap">
                        <label for="q" class="visually-hidden">Hledat</label>
                        <input type="search" name="q" value="{{ $q ?? '' }}" class="form-control" id="q" placeholder="Hledat jméno nebo email...">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-search fs-20"></i>
                    </button>
                </form>
            </div>
            @if(session('success'))
                <div class="alert alert-success mb-0 mx-3">{{ session('success') }}</div>
            @endif
        </div>
    </div>
</div>
<!-- end row -->

<div class="row">
    @forelse($users as $user)
        <div class="col-xl-3 col-sm-6">
            <div class="card text-center h-100">
                <div class="card-body d-flex flex-column">
                    <div class="dropdown float-end">
                        <a href="#" class="text-body" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-dots-vertical fs-22"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('apps.users.edit', $user) }}">Upravit</a>
                        </div>
                    </div>
                    <img src="/images/users/avatar-1.jpg" class="rounded-circle img-thumbnail avatar-xl mt-1 align-self-center" alt="avatar">
                    <h4 class="mt-3 mb-1">
                        <a href="{{ route('apps.users.edit', $user) }}" class="text-dark">{{ $user->name ?? '—' }}</a>
                    </h4>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    <div class="mt-auto">
                        <span class="badge {{ $user->is_admin ? 'bg-success' : 'bg-secondary' }}">
                            {{ $user->is_admin ? 'Admin' : 'User' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info">Žádní uživatelé k zobrazení.</div>
        </div>
    @endforelse
</div>

<div class="row">
    <div class="col-12 d-flex justify-content-center">
    {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- end row -->

@endsection

@section('scripts')

@endsection