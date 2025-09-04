@extends('layouts.vertical')

@section('content')
    @include('layouts.partials.breadcrumb', ['title' => 'Importy'])
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title">Importy dat</h4>
                        <p class="text-muted">Zde můžete spravovat a spouštět importy dat do CRM. Proveďte import firem, kontaktů, prodejů nebo produktových skupin podle aktuálního plánu migrace.</p>
                        <hr>
                        <a href="#" class="btn btn-primary mb-2 disabled">Nový import (připravujeme)</a>
                        <div class="alert alert-info mt-3">
                            <b>Tip:</b> Importní skripty a plán najdete v <code>import_plan_cz.md</code> a <code>src/tools/import/</code>.
                        </div>
                        <h5 class="mt-4">Historie importů</h5>
                        @if(!empty($logs))
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Soubor</th>
                                        <th>Čas</th>
                                        <th>Velikost</th>
                                        <th>Souhrn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                    <tr>
                                        <td><code>{{ $log['file'] }}</code></td>
                                        <td>{{ date('d.m.Y H:i:s', $log['mtime']) }}</td>
                                        <td>{{ number_format($log['size']) }} B</td>
                                        <td>
                                            @if(is_array($log['summary']))
                                                @php $s = $log['summary']; @endphp
                                                <small class="text-muted">
                                                    @foreach($s as $k=>$v)
                                                        <span class="me-2"><b>{{ $k }}:</b> {{ is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}</span>
                                                    @endforeach
                                                </small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                            <p class="text-muted">Zatím nebyly nalezeny žádné importní logy.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
