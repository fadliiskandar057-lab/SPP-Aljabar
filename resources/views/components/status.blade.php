@props(['status'])
<span class="badge status-{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
