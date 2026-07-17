@props([
    'phone',
    'message',
    'label' => 'WhatsApp',
    'class' => 'btn btn-sm btn-success',
])

@php
    $normalizedPhone = preg_replace('/[^0-9]/', '', (string) $phone);

    if (str_starts_with($normalizedPhone, '0')) {
        $normalizedPhone = '62'.substr($normalizedPhone, 1);
    }
@endphp

@if($normalizedPhone !== '')
    <a {{ $attributes->merge(['class' => $class]) }} target="_blank" rel="noopener" href="https://wa.me/{{ $normalizedPhone }}?text={{ urlencode($message) }}">
        <i class="bi bi-whatsapp"></i>{{ $label }}
    </a>
@else
    <span class="text-muted small">No WA belum ada</span>
@endif
