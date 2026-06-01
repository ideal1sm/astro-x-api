@php
    $images = array_values(array_filter($images ?? []));
@endphp

<div class="space-y-4">
    @if (count($images))
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <img
                src="{{ $images[0] }}"
                alt="{{ $productName ?: 'Изображение товара' }}"
                class="h-auto max-h-[28rem] w-full object-contain bg-gray-50"
            >
        </div>

        @if (count($images) > 1)
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($images as $index => $image)
                    <a
                        href="{{ $image }}"
                        target="_blank"
                        rel="noreferrer"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white transition hover:border-primary-500"
                    >
                        <img
                            src="{{ $image }}"
                            alt="{{ ($productName ?: 'Изображение товара') . ' #' . ($index + 1) }}"
                            class="h-28 w-full object-cover"
                        >
                    </a>
                @endforeach
            </div>
            <p class="text-sm text-gray-500">
                Дополнительные изображения доступны ниже. По клику откроются в новой вкладке.
            </p>
        @endif
    @else
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
            Для этого товара изображение не загружено.
        </div>
    @endif
</div>
