@props(['href', 'type'])

@php
    switch (strtolower($type)) {
        case 'create':
        case 'add':
            $buttonClass = 'btn btn-lg btn-outline-secondary';
            $iconClass = 'bi bi-plus-circle';
            break;
        case 'edit':
            $buttonClass = 'btn btn-sm btn-outline-success';
            $iconClass = 'bi bi-pencil-square';
            break;
        case 'delete':
        case 'remove':
            $buttonClass = 'btn btn-sm btn-outline-danger';
            $iconClass = 'bi bi-trash';
            break;
        case 'show':
        case 'view':
            $buttonClass = 'btn btn-sm btn-outline-info';
            $iconClass = 'bi bi-eye';
            break;
        case 'back':
            $buttonClass = 'btn btn-sm btn-outline-secondary';
            $iconClass = 'bi bi-arrow-left-circle';
            break;
        case 'reset':
            $buttonClass = 'btn btn-sm btn-outline-secondary';
            $iconClass = 'bi bi-arrow-counterclockwise';
            break;
        case 'submit':
        case 'save':
            $buttonClass = 'btn btn-sm btn-outline-primary';
            $iconClass = 'bi bi-save2';
            break;
        case 'cancel':
        case 'close':
        case 'exit':
            $buttonClass = 'btn btn-sm btn-outline-secondary';
            $iconClass = 'bi bi-x-circle';
            break;
        case 'home':
            $buttonClass = 'btn btn-sm btn-outline-success';
            $iconClass = 'bi bi-house-door';
            break;
        default:
            $buttonClass = 'btn btn-sm btn-outline-warning';
            $iconClass = 'bi bi-save';
            break;
    }
@endphp

<a href="{{ $href }}" class="{{ $buttonClass }}">
    <span class="{{ $iconClass }} me-1"></span>
    {{ ucfirst($type) }}
</a>
