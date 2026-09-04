<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['active' => false, 'href' => '#']));

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

foreach (array_filter((['active' => false, 'href' => '#']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $classes = $active
        ? 'bg-wajhatak-50 text-wajhatak-700 font-semibold dark:bg-wajhatak-500/10 dark:text-wajhatak-300'
        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white';
?>

<a href="<?php echo e($href); ?>" <?php echo e($attributes->merge(['class' => 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition ' . $classes])); ?> :class="collapsed ? 'justify-center px-2' : ''">
    <?php if(isset($icon)): ?>
        <span class="shrink-0"><?php echo e($icon); ?></span>
    <?php endif; ?>
    <span x-show="!collapsed"><?php echo e($slot); ?></span>
</a>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\components\admin\nav-link.blade.php ENDPATH**/ ?>