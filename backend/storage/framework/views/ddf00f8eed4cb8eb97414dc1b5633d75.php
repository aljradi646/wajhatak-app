<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => 'المساعد الذكي','title' => 'المساعد الذكي','breadcrumbs' => [['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')]]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'المساعد الذكي','title' => 'المساعد الذكي','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')]])]); ?>

    <div class="space-y-5">
        <?php echo $__env->make('admin.ai._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if(session('status')): ?>
            <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-500/10 dark:border-green-500/30 px-4 py-3 text-sm font-bold text-green-700 dark:text-green-400">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        
        <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['title' => 'حالة المساعد','description' => 'المحرك حتمي داخل الخادم: الردود تُبنى من عقارات وجهتك الحقيقية فقط.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'حالة المساعد','description' => 'المحرك حتمي داخل الخادم: الردود تُبنى من عقارات وجهتك الحقيقية فقط.']); ?>
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <?php if($enabled && $health->healthy): ?>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400">● يعمل</span>
                <?php elseif(! $enabled): ?>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">● معطّل من الإعدادات</span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400">● يحتاج إصلاحًا</span>
                <?php endif; ?>

                <span>الاسم: <b><?php echo e($assistantName); ?></b></span>
                <?php if($health->message): ?>
                    <span class="text-gray-500 dark:text-gray-400"><?php echo e($health->message); ?></span>
                <?php endif; ?>

                <div class="ms-auto flex flex-wrap items-center gap-2">
                    <a href="<?php echo e(route('admin.ai.monitoring')); ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'activity','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'activity','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> المراقبة والتشخيص
                    </a>
                    <a href="<?php echo e(route('admin.ai.playground')); ?>" class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-bold text-white" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);">
                        <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'chat','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chat','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> تجربة المحادثة
                    </a>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalad5130b5347ab6ecc017d2f5a278b926)): ?>
<?php $attributes = $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926; ?>
<?php unset($__attributesOriginalad5130b5347ab6ecc017d2f5a278b926); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalad5130b5347ab6ecc017d2f5a278b926)): ?>
<?php $component = $__componentOriginalad5130b5347ab6ecc017d2f5a278b926; ?>
<?php unset($__componentOriginalad5130b5347ab6ecc017d2f5a278b926); ?>
<?php endif; ?>

        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php $__currentLoopData = [
                'إجمالي المحادثات' => $stats['total_conversations'],
                'إجمالي الرسائل' => $stats['total_messages'],
                'طلبات ناجحة' => $stats['successful_requests'],
                'طلبات اليوم' => $stats['requests_today'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400"><?php echo e($label); ?></div>
                    <div class="text-2xl font-black text-gray-900 dark:text-gray-100 mt-1"><?php echo e($value); ?></div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div>
            <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-3">أقسام الإعدادات</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $definition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('admin.ai.settings', ['section' => $key])); ?>"
                       class="group block rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 transition hover:border-wajhatak-300 hover:shadow-md">
                        <div class="flex items-center gap-2">
                            <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'settings','class' => 'h-4 w-4 text-wajhatak-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'settings','class' => 'h-4 w-4 text-wajhatak-600']); ?>
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
                            <span class="font-bold text-gray-900 dark:text-gray-100"><?php echo e($definition['label']); ?></span>
                        </div>
                        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400"><?php echo e($definition['description']); ?></p>
                        <span class="mt-3 inline-block text-xs font-bold text-wajhatak-600 group-hover:underline">فتح القسم ←</span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal069c916459f102ed6d71cf67d43601ae)): ?>
<?php $attributes = $__attributesOriginal069c916459f102ed6d71cf67d43601ae; ?>
<?php unset($__attributesOriginal069c916459f102ed6d71cf67d43601ae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal069c916459f102ed6d71cf67d43601ae)): ?>
<?php $component = $__componentOriginal069c916459f102ed6d71cf67d43601ae; ?>
<?php unset($__componentOriginal069c916459f102ed6d71cf67d43601ae); ?>
<?php endif; ?>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views/admin/ai/index.blade.php ENDPATH**/ ?>