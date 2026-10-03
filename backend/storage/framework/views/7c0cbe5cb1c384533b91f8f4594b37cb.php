
<?php
    $currentSection = request()->route('section');

    $aiNav = [
        ['label' => 'نظرة عامة', 'icon' => 'ai-assistant', 'route' => 'admin.ai.index', 'params' => [], 'section' => null],
    ];

    foreach ($sections as $key => $definition) {
        $aiNav[] = [
            'label' => $definition['label'],
            'icon' => 'settings',
            'route' => 'admin.ai.settings',
            'params' => ['section' => $key],
            'section' => $key,
        ];
    }

    $aiNav[] = ['label' => 'المراقبة والتشخيص', 'icon' => 'activity', 'route' => 'admin.ai.monitoring', 'params' => [], 'section' => null];
    $aiNav[] = ['label' => 'الإحصاءات', 'icon' => 'reports', 'route' => 'admin.ai.stats', 'params' => [], 'section' => null];
    $aiNav[] = ['label' => 'السجلات', 'icon' => 'clock', 'route' => 'admin.ai.logs', 'params' => [], 'section' => null];
    $aiNav[] = ['label' => 'تجربة المحادثة', 'icon' => 'chat', 'route' => 'admin.ai.playground', 'params' => [], 'section' => null];
?>

<nav class="flex flex-wrap items-center gap-2" aria-label="أقسام المساعد الذكي">
    <?php $__currentLoopData = $aiNav; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $isActive = request()->routeIs($item['route']) && $item['section'] === $currentSection;
        ?>
        <a href="<?php echo e(route($item['route'], $item['params'])); ?>"
           class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-bold transition
                <?php echo e($isActive
                    ? 'text-white shadow-sm'
                    : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700'); ?>"
           <?php if($isActive): ?> style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);" <?php endif; ?>>
            <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => $item['icon'],'class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['icon']),'class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
            <?php echo e($item['label']); ?>

        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<?php /**PATH /app/backend/resources/views/admin/ai/_nav.blade.php ENDPATH**/ ?>