<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label' => null, 'name' => null, 'checked' => false, 'description' => null]));

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

foreach (array_filter((['label' => null, 'name' => null, 'checked' => false, 'description' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="flex items-start gap-3">
    <input
        id="<?php echo e($name); ?>"
        type="checkbox"
        name="<?php echo e($name); ?>"
        value="1"
        <?php if(old($name, $checked ? 1 : 0)): ?> checked <?php endif; ?>
        <?php echo e($attributes->merge(['class' => 'mt-0.5 h-4 w-4 rounded border-gray-300 text-wajhatak-600 focus:ring-wajhatak-300'])); ?>

    >
    <?php if($label): ?>
        <div>
            <label for="<?php echo e($name); ?>" class="block text-sm font-bold text-gray-700 dark:text-gray-300"><?php echo e($label); ?></label>
            <?php if($description): ?><p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($description); ?></p><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\components\admin\checkbox.blade.php ENDPATH**/ ?>