<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => null, 'description' => null, 'padding' => true]));

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

foreach (array_filter((['title' => null, 'description' => null, 'padding' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 dark:bg-gray-800 dark:ring-gray-700'])); ?>>
    <?php if($title || $description): ?>
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <?php if($title): ?><h2 class="text-base font-bold text-gray-900 dark:text-gray-100"><?php echo e($title); ?></h2><?php endif; ?>
            <?php if($description): ?><p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"><?php echo e($description); ?></p><?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="<?php echo e($padding ? 'p-5' : ''); ?>">
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\components\admin\card.blade.php ENDPATH**/ ?>