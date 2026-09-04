@props(['status' => null])

@php
    $map = [
        'published' => ['bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400', 'منشور'],
        'pending' => ['bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400', 'قيد المراجعة'],
        'draft' => ['bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300', 'مسودة'],
        'rejected' => ['bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400', 'مرفوض'],
        'archived' => ['bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300', 'مؤرشف'],
        'confirmed' => ['bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400', 'مؤكد'],
        'cancelled' => ['bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400', 'ملغي'],
        'completed' => ['bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400', 'مكتمل'],
        'sale' => ['bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400', 'بيع'],
        'rent' => ['bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400', 'إيجار'],
    ];
    [$color, $text] = isset($map[$status]) ? $map[$status] : ['bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300', \Illuminate\Support\Str::ucfirst($status)];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . $color]) }}>
    {{ $text ?? $status }}
</span>
