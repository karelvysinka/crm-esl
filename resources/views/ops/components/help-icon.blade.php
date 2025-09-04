@php($content = config('ops_help.'.trim($key,'"\'')) )
<span class="ms-1" data-bs-toggle="tooltip" title="{{ $content ? Str::limit(strip_tags($content),120) : 'Nápověda nenalezena' }}">
  <i class="ri-question-line text-muted"></i>
</span>
