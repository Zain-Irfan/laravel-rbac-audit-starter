@props(['event'])

@php
$color = match ($event) {
    'created' => 'green',
    'updated' => 'amber',
    'deleted' => 'red',
    default => 'indigo',
};
@endphp

<x-badge :color="$color">{{ \Illuminate\Support\Str::headline($event) }}</x-badge>
