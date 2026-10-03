<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => 'طلبات توثيق الوكلاء','title' => 'طلبات التوثيق','breadcrumbs' => [['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'الوكلاء', 'url' => route('admin.agents.index')]]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'طلبات توثيق الوكلاء','title' => 'طلبات التوثيق','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'الوكلاء', 'url' => route('admin.agents.index')]])]); ?>
    <div class="space-y-5">

        <?php $__empty_1 = true; $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <?php if($agent->photo_path): ?>
                                <img src="<?php echo e(asset('storage/'.$agent->photo_path)); ?>" class="h-14 w-14 rounded-2xl object-cover" alt="صورة الوكيل">
                            <?php else: ?>
                                <span class="flex h-14 w-14 items-center justify-center rounded-2xl text-xl font-black text-white brand-mark"><?php echo e(mb_substr($agent->user->name, 0, 1)); ?></span>
                            <?php endif; ?>
                            <div>
                                <div class="font-black text-gray-900 dark:text-gray-100"><?php echo e($agent->user->name); ?></div>
                                <div class="text-xs text-gray-500"><?php echo e($agent->user->email); ?></div>
                                <div class="text-xs text-gray-400">سُجّل <?php echo e($agent->created_at->format('Y/m/d')); ?></div>
                                <a href="<?php echo e(route('admin.agents.show', $agent)); ?>" class="mt-1 inline-block text-xs font-bold text-wajhatak-600 hover:text-wajhatak-700">فتح صفحة المراجعة الكاملة ←</a>
                            </div>
                        </div>
                        <dl class="text-sm space-y-1.5 text-gray-600 dark:text-gray-300">
                            <div><span class="font-bold">المكتب:</span> <?php echo e($agent->agency_name ?? '—'); ?></div>
                            <div><span class="font-bold">المسمى:</span> <?php echo e($agent->job_title ?? '—'); ?></div>
                            <div><span class="font-bold">الجوال:</span> <?php echo e($agent->phone ?? $agent->user->phone ?? '—'); ?></div>
                            <div><span class="font-bold">واتساب:</span> <?php echo e($agent->whatsapp ?? '—'); ?></div>
                            <div><span class="font-bold">المدينة:</span> <?php echo e($agent->city ?? '—'); ?></div>
                            <div><span class="font-bold">الرقم الوطني:</span> <?php echo e($agent->national_id ?? '—'); ?></div>
                            <div><span class="font-bold">رقم الترخيص:</span> <?php echo e($agent->license_number ?? '—'); ?></div>
                            <div><span class="font-bold">الخبرة:</span> <?php echo e($agent->experience_years ? $agent->experience_years.' سنة' : '—'); ?></div>
                        </dl>
                    </div>


                    <div>
                        <h4 class="text-sm font-black mb-3 text-gray-700 dark:text-gray-200">المستندات والروابط</h4>
                        <div class="space-y-2 text-sm">
                            <?php if($agent->id_document_path): ?>
                                <a href="<?php echo e(asset('storage/'.$agent->id_document_path)); ?>" target="_blank" class="block rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 font-bold">🪪 صورة الهوية — عرض</a>
                            <?php else: ?>
                                <div class="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-gray-400">لم يرفع صورة الهوية</div>
                            <?php endif; ?>
                            <?php if($agent->license_document_path): ?>
                                <a href="<?php echo e(asset('storage/'.$agent->license_document_path)); ?>" target="_blank" class="block rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 font-bold">📜 صورة الترخيص — عرض</a>
                            <?php else: ?>
                                <div class="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-gray-400">لم يرفع صورة الترخيص</div>
                            <?php endif; ?>
                            <?php if($agent->bio): ?>
                                <p class="text-gray-600 dark:text-gray-300 leading-relaxed pt-1"><?php echo e(\Illuminate\Support\Str::limit($agent->bio, 220)); ?></p>
                            <?php endif; ?>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <?php if($agent->website): ?><a href="<?php echo e($agent->website); ?>" target="_blank" class="text-xs font-bold text-wajhatak-600">🌐 موقع</a><?php endif; ?>
                                <?php if($agent->facebook): ?><a href="<?php echo e($agent->facebook); ?>" target="_blank" class="text-xs font-bold text-blue-600">فيسبوك</a><?php endif; ?>
                                <?php if($agent->instagram): ?><a href="<?php echo e($agent->instagram); ?>" target="_blank" class="text-xs font-bold text-pink-600">إنستغرام</a><?php endif; ?>
                                <?php if($agent->twitter): ?><a href="<?php echo e($agent->twitter); ?>" target="_blank" class="text-xs font-bold text-sky-600">X</a><?php endif; ?>
                            </div>
                        </div>
                    </div>


                    <div class="flex flex-col gap-3 justify-center">
                        <form method="POST" action="<?php echo e(route('admin.agents.approve', $agent)); ?>" onsubmit="return confirm('توثيق الوكيل سيفتح له بوابة نشر العقارات. متابعة؟');">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-white rounded-xl" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);">
                                ✓ توثيق الحساب وفتح النشر
                            </button>
                        </form>
                        <form method="POST" action="<?php echo e(route('admin.agents.reject-verification', $agent)); ?>" class="space-y-2">
                            <?php echo csrf_field(); ?>
                            <textarea name="reason" rows="2" required placeholder="سبب الرفض (يُرسل للوكيل)..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600"></textarea>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-red-600 rounded-xl border border-red-200 hover:bg-red-50 dark:hover:bg-red-900/20">
                                ✗ رفض الطلب
                            </button>
                        </form>
                        <a href="<?php echo e(route('admin.agents.edit', $agent)); ?>" class="text-center text-sm text-gray-500 hover:text-gray-700">تعديل بياناته يدويًا</a>
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
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
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
                <div class="py-10 text-center text-gray-500">
                    لا توجد طلبات توثيق معلقة حاليًا — كل الوكلاء تمت مراجعتهم ✓
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
        <?php endif; ?>

        <?php echo $__env->make('admin.partials.pagination', ['paginator' => $agents], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH /app/backend/resources/views/admin/agents/verifications.blade.php ENDPATH**/ ?>