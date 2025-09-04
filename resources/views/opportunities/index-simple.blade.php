@extends('layouts.vertical', ['page_title' => 'Příležitosti'])

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item active">Příležitosti</li>
                    </ol>
                </div>
                <h4 class="page-title">Příležitosti</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Seznam příležitostí</h4>
                    <a href="{{ route('opportunities.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Nová příležitost
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Název</th>
                                    <th>Společnost</th>
                                    <th>Hodnota</th>
                                    <th>Stádium</th>
                                    <th>Pravděpodobnost</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($opportunities as $opportunity)
                                <tr>
                                    <td>{{ $opportunity->name }}</td>
                                    <td>
                                        @if($opportunity->company)
                                            {{ $opportunity->company->name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($opportunity->value, 0, ',', ' ') }} Kč</td>
                                    <td>
                                        <span class="badge bg-{{ $opportunity->stage_color }}">{{ $opportunity->stage_label }}</span>
                                    </td>
                                    <td>{{ $opportunity->probability }}%</td>
                                    <td>
                                        <a href="{{ route('opportunities.show', $opportunity) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('opportunities.edit', $opportunity) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    {{ $opportunities->links() }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
