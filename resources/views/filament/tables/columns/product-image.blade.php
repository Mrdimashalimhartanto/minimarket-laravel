@php
    use Illuminate\Support\Facades\Storage;

    $path = $getState(); // <-- ini value dari kolom image_path (string)
    $src = $path ? Storage::disk('minio')->url($path) : null;
@endphp

@if ($src)
    <img
        src="{{ $src }}"
        class="h-11 w-11 rounded-full object-cover"
        alt="product"
        loading="lazy"
    />
@else
    <div class="h-11 w-11 rounded-full bg-gray-800"></div>
@endif
