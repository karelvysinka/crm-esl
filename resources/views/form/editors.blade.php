@extends('layouts.vertical', ['title' => 'Editors', 'topbarTitle' => 'Editors'])

@section('css')
@vite(['node_modules/quill/dist/quill.core.css', 'node_modules/quill/dist/quill.snow.css', 'node_modules/quill/dist/quill.bubble.css'])
@endsection

@section('content')

<div class="card">
    <div class="card-header border-bottom border-dashed d-flex align-items-center">
        <h4 class="header-title">Quill Editor</h4>
    </div>

    <div class="card-body">
        <p class="text-muted">Snow is a clean, flat toolbar theme.</p>

        <div id="snow-editor" style="height: 300px;">
            <h3><span class="ql-size-large">Hello World!</span></h3>
            <p><br></p>
            <h3>This is an simple editable area.</h3>
            <p><br></p>
            <ul>
                <li>
                    Select a text to reveal the toolbar.
                </li>
                <li>
                    Edit rich document on-the-fly, so elastic!
                </li>
            </ul>
            <p><br></p>
            <p>
                End of simple area
            </p>
        </div><!-- end Snow-editor-->
    </div>
</div> <!-- end card-->

<div class="card">
    <div class="card-header border-bottom border-dashed d-flex align-items-center">
        <h4 class="header-title">Bubble Editor</h4>
    </div>

    <div class="card-body">
        <p class="text-muted">Bubble is a simple tooltip based theme.</p>

        <div id="bubble-editor" style="height: 300px;">
            <h3><span class="ql-size-large">Hello World!</span></h3>
            <p><br></p>
            <h3>This is an simple editable area.</h3>
            <p><br></p>
            <ul>
                <li>
                    Select a text to reveal the toolbar.
                </li>
                <li>
                    Edit rich document on-the-fly, so elastic!
                </li>
            </ul>
            <p><br></p>
            <p>
                End of simple area
            </p>
        </div> <!-- end Snow-editor-->
    </div>
</div> <!-- end card-->

@endsection

@section('scripts')
@vite(['resources/js/components/form-quilljs.js'])
@endsection