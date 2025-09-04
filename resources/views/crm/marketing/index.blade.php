@extends('layouts.vertical')

@section('title', 'Marketing - Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">CRM</a></li>
                        <li class="breadcrumb-item active">Marketing</li>
                    </ol>
                </div>
                <h4 class="page-title">Marketing – Hlavní přehled</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-sm bg-primary rounded me-3"><i class="ri-megaphone-line avatar-title text-white"></i></div>
                    <div>
                        <h4 class="mb-0">Kampaně</h4>
                        <small class="text-muted">Přehled aktivních kampaní</small>
                    </div>
                    <div class="ms-auto"><a href="{{ route('marketing.exec.campaigns') }}" class="btn btn-sm btn-primary">Otevřít</a></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-sm bg-success rounded me-3"><i class="ri-git-branch-line avatar-title text-white"></i></div>
                    <div>
                        <h4 class="mb-0">Automatizace</h4>
                        <small class="text-muted">Workflow builder</small>
                    </div>
                    <div class="ms-auto"><a href="{{ route('marketing.exec.automation') }}" class="btn btn-sm btn-success">Otevřít</a></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-sm bg-warning rounded me-3"><i class="ri-folder-image-line avatar-title text-white"></i></div>
                    <div>
                        <h4 class="mb-0">Knihovna obsahu</h4>
                        <small class="text-muted">Assety & AI generátor</small>
                    </div>
                    <div class="ms-auto"><a href="{{ route('marketing.exec.content') }}" class="btn btn-sm btn-warning">Otevřít</a></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-sm bg-info rounded me-3"><i class="ri-mail-send-line avatar-title text-white"></i></div>
                    <div>
                        <h4 class="mb-0">E-mail</h4>
                        <small class="text-muted">Šablony, rozesílky</small>
                    </div>
                    <div class="ms-auto"><a href="{{ route('marketing.exec.email') }}" class="btn btn-sm btn-info">Otevřít</a></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Vývoj leadů a MQL (mock)</h4>
                    <div class="ratio ratio-16x9 bg-light rounded d-flex align-items-center justify-content-center text-muted">
                        Placeholder pro Chart.js line chart
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Rozdělení budgetu (mock)</h4>
                    <div class="ratio ratio-1x1 bg-light rounded d-flex align-items-center justify-content-center text-muted">
                        Placeholder pro pie chart
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
