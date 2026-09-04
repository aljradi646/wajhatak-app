<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label' => null, 'name' => null, 'type' => 'text', 'value' => null, 'required' => false, 'help' => null, 'placeholder' => null]));

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

foreach (array_filter((['label' => null, 'name' => null, 'type' => 'text', 'value' => null, 'required' => false, 'help' => null, 'placeholder' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div>
    <?php if($label): ?>
        <label for="<?php echo e($name); ?>" class="block text-sm font-bold text-gray-700 mb-1 dark:text-gray-300">
            <?php echo e($label); ?> <?php if($required): ?><span class="text-red-500">*</span><?php endif; ?>
        </label>
    <?php endif; ?>
    <input
        id="<?php echo e($name); ?>"
        type="<?php echo e($type); ?>"
        name="<?php echo e($name); ?>"
        value="<?php echo e(old($name, $value)); ?>"
        <?php if($required): ?> required <?php endif; ?>
        placeholder="<?php echo e($placeholder); ?>"
        <?php echo e($attributes->merge(['class' => 'w-full rounded-xl border-gray-200 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm bg-gray-50 focus:bg-white transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100'])); ?>

        style="border-width: 1.5px;"
    >
    <?php if($help): ?><p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($help); ?></p><?php endif; ?>
    <?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\components\admin\input.blade.php ENDPATH**/ ?>