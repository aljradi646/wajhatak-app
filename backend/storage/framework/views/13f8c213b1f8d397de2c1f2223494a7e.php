<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => 'تفاصيل طلب المعاينة','title' => 'تفاصيل طلب المعاينة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'تفاصيل طلب المعاينة','title' => 'تفاصيل طلب المعاينة']); ?>
    <div class="mb-4 flex items-center gap-2">
        <a href="<?php echo e(route('admin.viewing-requests.edit', $viewingRequest)); ?>" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">تعديل</a>
        <a href="<?php echo e(route('admin.viewing-requests.index')); ?>" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">رجوع للقائمة</a>
    </div>

    <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
        <dl class="space-y-3 text-sm">
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">العقار</dt>
                <dd class="font-medium text-left"><?php echo e($viewingRequest->property?->title ?? '—'); ?></dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">العميل</dt>
                <dd class="font-medium text-left"><?php echo e($viewingRequest->client?->name ?? '—'); ?></dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الوكيل</dt>
                <dd class="font-medium text-left"><?php echo e($viewingRequest->agent?->user?->name ?? ('وكيل #'.$viewingRequest->agent_id)); ?></dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">التاريخ</dt>
                <dd class="font-medium"><?php echo e($viewingRequest->scheduled_date?->format('Y-m-d')); ?></dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الوقت</dt>
                <dd class="font-medium"><?php echo e($viewingRequest->scheduled_time); ?></dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الحالة</dt>
                <dd><?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['status' => $viewingRequest->status->value]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($viewingRequest->status->value)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $attributes = $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $component = $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?></dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">تاريخ الإنشاء</dt>
                <dd class="font-medium"><?php echo e($viewingRequest->created_at?->format('Y-m-d H:i')); ?></dd></div>
        </dl>
        <?php if($viewingRequest->notes): ?>
            <div class="mt-4 rounded-lg bg-gray-50 p-4">
                <p class="text-sm font-medium text-gray-700 mb-1">الملاحظات</p>
                <p class="text-sm text-gray-600 whitespace-pre-line"><?php echo e($viewingRequest->notes); ?></p>
            </div>
        <?php endif; ?>
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
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\admin\viewing_requests\show.blade.php ENDPATH**/ ?>