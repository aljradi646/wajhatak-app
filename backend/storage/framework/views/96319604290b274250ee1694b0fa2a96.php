<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['status' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['status' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
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
?>

<span <?php echo e($attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . $color])); ?>>
    <?php echo e($text ?? $status); ?>

</span>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\components\admin\badge.blade.php ENDPATH**/ ?>