<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'size' => 'md',
    'showTagline' => true,
    'class' => '',
]));

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

foreach (array_filter(([
    'size' => 'md',
    'showTagline' => true,
    'class' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $mark = match ($size) {
        'sm' => 'h-8 w-8 text-lg rounded-lg',
        'lg' => 'h-14 w-14 text-3xl rounded-2xl',
        default => 'h-10 w-10 text-xl rounded-xl',
    };
    $en = match ($size) {
        'sm' => 'text-[10px] tracking-[0.18em]',
        'lg' => 'text-sm tracking-[0.25em]',
        default => 'text-[11px] tracking-[0.2em]',
    };
    $ar = match ($size) {
        'sm' => 'text-lg',
        'lg' => 'text-2xl',
        default => 'text-xl',
    };
?>

<div class="flex items-center gap-3 <?php echo e($class); ?>">
    <span class="flex <?php echo e($mark); ?> shrink-0 items-center justify-center font-black text-white" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);">
        و
    </span>
    <div class="leading-tight">
        <div class="font-extrabold tracking-[0.2em] <?php echo e($en); ?>" style="color: #B97D1B;">WAJHATAK</div>
        <div class="font-extrabold text-gray-900 dark:text-gray-100 leading-tight <?php echo e($ar); ?>">وجهتك</div>
        <?php if($showTagline): ?>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-snug">وجهتك إلى العقار المناسب.</p>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\components\brand.blade.php ENDPATH**/ ?>