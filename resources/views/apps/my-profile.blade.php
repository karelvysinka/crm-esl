@extends('layouts.vertical', ['title' => 'Můj profil', 'topbarTitle' => 'Můj profil'])

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="bg-picture card-body">
                <div class="d-flex align-items-top">
                    <img src="/images/users/avatar-1.jpg"
                        class="flex-shrink-0 rounded-circle avatar-xl img-thumbnail float-start me-3"
                        alt="profile-image">

                    <div class="flex-grow-1 overflow-hidden">
                        <h4 class="m-0 lh-base">{{ $user->name ?? '—' }}</h4>
                        <p class="text-muted"><i>{{ $user->email }}</i></p>
                        <p class="mb-0">
                            Role: <span class="badge {{ $user->is_admin ? 'bg-success' : 'bg-secondary' }}">{{ $user->is_admin ? 'Admin' : 'User' }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('apps.me.update') }}" class="mb-2">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Jméno</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nové heslo (volitelné)</label>
                        <input type="password" name="password" class="form-control" placeholder="Nechte prázdné pro beze změny">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Potvrzení hesla</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Zopakujte nové heslo">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Uložit</button>
                        <a href="{{ route('crm.dashboard') }}" class="btn btn-light">Zpět na dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center border-bottom border-dashed">
                <h4 class="header-title mb-0">Rychlé info</h4>
            </div>

            <ul class="list-unstyled align-items-start mt-2 mb-0 p-3">
                <li class="d-flex mb-2">
                    <div class="flex-grow-1">
                        <h6 class="m-0">ID</h6>
                        <p class="mb-0 text-muted">{{ $user->id }}</p>
                    </div>
                </li>
                <li class="d-flex mb-2">
                    <div class="flex-grow-1">
                        <h6 class="m-0">Vytvořen</h6>
                        <p class="mb-0 text-muted">{{ optional($user->created_at)->format('Y-m-d H:i') }}</p>
                    </div>
                </li>
                <li class="d-flex">
                    <div class="flex-grow-1">
                        <h6 class="m-0">Upraven</h6>
                        <p class="mb-0 text-muted">{{ optional($user->updated_at)->format('Y-m-d H:i') }}</p>
                    </div>
                </li>
            </ul>
            <div class="px-3 pb-3">
                <p class="text-muted mb-0">Upravte své základní údaje. Změna hesla je volitelná.</p>
            </div>
        </div>
    </div>
</div>
@endsection
