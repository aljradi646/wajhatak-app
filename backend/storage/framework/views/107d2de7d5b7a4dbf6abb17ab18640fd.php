<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['type' => 'submit', 'variant' => 'primary', 'size' => 'md']));

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

foreach (array_filter((['type' => 'submit', 'variant' => 'primary', 'size' => 'md']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $variants = [
        'primary' => 'text-white hover:shadow-lg focus:ring-wajhatak-400',
        'secondary' => 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-600 dark:hover:bg-gray-700',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-300',
        'success' => 'bg-green-600 text-white hover:bg-green-700',
    ];
    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];
    $isPrimary = $variant === 'primary';
    $classes = $variants[$variant] . ' ' . $sizes[$size] . ' inline-flex items-center justify-center gap-2 rounded-xl font-bold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    $style = $isPrimary ? 'background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E); box-shadow: 0 4px 12px rgba(14, 138, 109, 0.25);' : '';
?>

<button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => $classes, 'style' => $style])); ?>>
    <?php echo e($slot); ?>

</button>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\components\admin\button.blade.php ENDPATH**/ ?>