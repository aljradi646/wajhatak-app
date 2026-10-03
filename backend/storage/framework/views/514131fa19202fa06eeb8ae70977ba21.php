<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => 'مراجعة الوكيل — '.$agent->user->name,'title' => 'مراجعة الوكيل','breadcrumbs' => [['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'الوكلاء', 'url' => route('admin.agents.index')]]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('مراجعة الوكيل — '.$agent->user->name),'title' => 'مراجعة الوكيل','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'الوكلاء', 'url' => route('admin.agents.index')]])]); ?>

    <div class="space-y-5" x-data="{ tab: 'profile' }">


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
            <div class="flex flex-wrap items-center gap-4">
                <?php if($agent->photo_path): ?>
                    <img src="<?php echo e(asset('storage/'.$agent->photo_path)); ?>" class="h-16 w-16 rounded-2xl object-cover" alt="صورة الوكيل">
                <?php else: ?>
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl text-2xl font-black text-white brand-mark"><?php echo e(mb_substr($agent->user->name, 0, 1)); ?></span>
                <?php endif; ?>

                <div class="flex-1 min-w-[200px]">
                    <div class="font-black text-lg text-gray-900 dark:text-gray-100"><?php echo e($agent->user->name); ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($agent->agency_name ?: 'وكيل عقاري'); ?> · <?php echo e($agent->job_title ?: 'غير محدد'); ?></div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                        <?php
                            $verificationBadges = [
                                'approved' => ['label' => 'موثّق', 'class' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'],
                                'pending' => ['label' => 'بانتظار التوثيق', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'],
                                'rejected' => ['label' => 'مرفوض التوثيق', 'class' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'],
                            ];
                            $badge = $verificationBadges[$agent->verification_status] ?? ['label' => $agent->verification_status, 'class' => 'bg-gray-100 text-gray-600'];
                        ?>
                        <span class="rounded-full px-2.5 py-0.5 font-bold <?php echo e($badge['class']); ?>"><?php echo e($badge['label']); ?></span>
                        <span class="rounded-full px-2.5 py-0.5 font-bold <?php echo e($agent->is_active ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'); ?>">
                            <?php echo e($agent->is_active ? 'نشط' : 'موقوف'); ?>

                        </span>
                        <span class="text-gray-500 dark:text-gray-400">★ <?php echo e(number_format((float) $agent->rating, 2)); ?> (<?php echo e(number_format($agent->reviews_count)); ?> مراجعة)</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <?php if($agent->verification_status === 'pending'): ?>
                        <form method="POST" action="<?php echo e(route('admin.agents.approve', $agent)); ?>">
                            <?php echo csrf_field(); ?>
                            <?php if (isset($component)) { $__componentOriginal60a020e5340f3f52bbc4501dc9f93102 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>✓ توثيق الحساب <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $attributes = $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $component = $__componentOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
                        </form>
                    <?php endif; ?>
                    <a href="<?php echo e(route('admin.agents.edit', $agent)); ?>" class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">تعديل</a>
                    <a href="<?php echo e(route('admin.agents.index')); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-wajhatak-600 dark:text-gray-400">
                        <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'back','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'back','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> رجوع
                    </a>
                </div>
            </div>

            <?php if($agent->verification_status === 'rejected' && $agent->rejection_reason): ?>
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 dark:bg-red-500/10 dark:border-red-500/30 px-4 py-3 text-sm text-red-700 dark:text-red-400">
                    <span class="font-bold">سبب الرفض المسجّل:</span> <?php echo e($agent->rejection_reason); ?>

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


        <div class="flex flex-wrap items-center gap-2">
            <?php $__currentLoopData = [
                'profile' => 'بيانات الوكيل',
                'properties' => 'عقارات الوكيل ('.$agent->properties->count().')',
                'verification' => 'التوثيق ('.$verificationEvents->count().')',
                'reports' => 'سجلات التقارير ('.$reportLogs->count().')',
                'conversations' => 'المحادثات ('.$conversations->count().')',
                'viewings' => 'طلبات المعاينة ('.$viewingRequests->count().')',
                'activity' => 'السجل ('.$activities->count().')',
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tabKey => $tabLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button type="button" @click="tab = '<?php echo e($tabKey); ?>'"
                        :class="tab === '<?php echo e($tabKey); ?>' ? 'text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700'"
                        class="rounded-xl px-3.5 py-2 text-sm font-bold transition"
                        <?php if($loop->first): ?> style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);" <?php endif; ?>>
                    <?php echo e($tabLabel); ?>

                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>


        <div x-show="tab === 'profile'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['title' => 'البيانات الأساسية']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'البيانات الأساسية']); ?>
                <dl class="space-y-3 text-sm">
                    <?php $__currentLoopData = [
                        'الاسم' => $agent->user->name,
                        'البريد الإلكتروني' => $agent->user->email,
                        'الجوال' => $agent->phone ?? $agent->user->phone,
                        'واتساب' => $agent->whatsapp,
                        'المكتب' => $agent->agency_name,
                        'المسمى الوظيفي' => $agent->job_title,
                        'المدينة' => $agent->city,
                        'العنوان' => $agent->address,
                        'الرقم الوطني' => $agent->national_id,
                        'رقم الترخيص' => $agent->license_number,
                        'سنوات الخبرة' => $agent->experience_years !== null ? $agent->experience_years.' سنة' : null,
                        'تاريخ التسجيل' => $agent->created_at?->format('Y-m-d H:i'),
                        'تاريخ التوثيق' => $agent->verified_at?->format('Y-m-d H:i'),
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400"><?php echo e($label); ?></dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100" dir="auto"><?php echo e($value ?: '—'); ?></dd>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </dl>
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

            <div class="space-y-4">
                <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['title' => 'النبذة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'النبذة']); ?>
                    <p class="text-sm text-gray-600 whitespace-pre-line dark:text-gray-300"><?php echo e($agent->bio ?: '—'); ?></p>
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

                <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['title' => 'روابط ومستندات']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'روابط ومستندات']); ?>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <?php if($agent->website): ?><a href="<?php echo e($agent->website); ?>" target="_blank" rel="noopener" class="font-bold text-wajhatak-600">🌐 الموقع</a><?php endif; ?>
                        <?php if($agent->facebook): ?><a href="<?php echo e($agent->facebook); ?>" target="_blank" rel="noopener" class="font-bold text-blue-600">فيسبوك</a><?php endif; ?>
                        <?php if($agent->instagram): ?><a href="<?php echo e($agent->instagram); ?>" target="_blank" rel="noopener" class="font-bold text-pink-600">إنستغرام</a><?php endif; ?>
                        <?php if($agent->twitter): ?><a href="<?php echo e($agent->twitter); ?>" target="_blank" rel="noopener" class="font-bold text-sky-600">X</a><?php endif; ?>
                        <?php if (! ($agent->website || $agent->facebook || $agent->instagram || $agent->twitter)): ?>
                            <span class="text-gray-400">لا توجد روابط.</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                        <?php if($agent->id_document_path): ?>
                            <a href="<?php echo e(asset('storage/'.$agent->id_document_path)); ?>" target="_blank" rel="noopener" class="rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 font-bold text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-700">🪪 صورة الهوية — عرض</a>
                        <?php else: ?>
                            <div class="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-gray-400">لم يرفع صورة الهوية</div>
                        <?php endif; ?>
                        <?php if($agent->license_document_path): ?>
                            <a href="<?php echo e(asset('storage/'.$agent->license_document_path)); ?>" target="_blank" rel="noopener" class="rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 font-bold text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-700">📜 صورة الترخيص — عرض</a>
                        <?php else: ?>
                            <div class="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-gray-400">لم يرفع صورة الترخيص</div>
                        <?php endif; ?>
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
            </div>
        </div>


        <div x-show="tab === 'properties'" x-cloak>
            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['padding' => false,'title' => 'عقارات الوكيل','description' => 'كل عقارات هذا الوكيل من قاعدة البيانات.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'title' => 'عقارات الوكيل','description' => 'كل عقارات هذا الوكيل من قاعدة البيانات.']); ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الكود</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العقار</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النوع</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الموقع</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">السعر</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php $__empty_1 = true; $__currentLoopData = $agent->properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 text-gray-500" dir="ltr"><?php echo e($property->reference_code ?? '—'); ?></td>
                                    <td class="px-4 py-3">
                                        <a href="<?php echo e(route('admin.properties.show', $property)); ?>" class="font-semibold text-wajhatak-600 hover:text-wajhatak-700"><?php echo e($property->title); ?></a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($property->type?->name_ar ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($property->location?->city ?? '—'); ?><?php echo e($property->location?->district ? ' — '.$property->location->district : ''); ?></td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap"><?php echo e(number_format((float) $property->price)); ?> <?php echo e($property->currency); ?></td>
                                    <td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['status' => $property->status->value]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($property->status->value)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $attributes = $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $component = $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">لا توجد عقارات لهذا الوكيل.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        </div>


        <div x-show="tab === 'verification'" x-cloak class="space-y-4">
            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['title' => 'إجراء التوثيق الحالي']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'إجراء التوثيق الحالي']); ?>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs text-gray-500">الحالة الحالية</div>
                        <div class="font-bold mt-1"><?php echo e($badge['label']); ?></div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs text-gray-500">وُثّق في</div>
                        <div class="font-bold mt-1"><?php echo e($agent->verified_at?->format('Y-m-d H:i') ?? '—'); ?></div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs text-gray-500">وُثّق بواسطة</div>
                        <div class="font-bold mt-1"><?php echo e($agent->verified_by ? (\App\Models\User::find($agent->verified_by)?->name ?? '#'.$agent->verified_by) : '—'); ?></div>
                    </div>
                </div>

                <?php if($agent->verification_status !== 'approved' || ! $agent->is_active): ?>
                    <div class="mt-4 flex flex-wrap items-start gap-3">
                        <?php if($agent->verification_status === 'pending'): ?>
                            <form method="POST" action="<?php echo e(route('admin.agents.approve', $agent)); ?>">
                                <?php echo csrf_field(); ?>
                                <?php if (isset($component)) { $__componentOriginal60a020e5340f3f52bbc4501dc9f93102 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>✓ توثيق الحساب وفتح النشر <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $attributes = $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $component = $__componentOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?php echo e(route('admin.agents.reject-verification', $agent)); ?>" class="flex-1 min-w-[260px] space-y-2">
                            <?php echo csrf_field(); ?>
                            <textarea name="reason" rows="2" required placeholder="سبب الرفض (يُرسل للوكيل ويُحفظ)..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600"></textarea>
                            <?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <?php if (isset($component)) { $__componentOriginal60a020e5340f3f52bbc4501dc9f93102 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.button','data' => ['type' => 'submit','variant' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'danger']); ?>✗ رفض التوثيق <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $attributes = $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $component = $__componentOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
                        </form>
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

            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['padding' => false,'title' => 'سجل عمليات التوثيق']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'title' => 'سجل عمليات التوثيق']); ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العملية</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">بواسطة</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php $__empty_1 = true; $__currentLoopData = $verificationEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200"><?php echo e($event->description); ?></td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($event->user?->name ?? 'النظام'); ?></td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?php echo e($event->created_at?->format('Y-m-d H:i')); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-400">لا توجد عمليات توثيق مسجلة.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        </div>


        <div x-show="tab === 'reports'" x-cloak>
            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['padding' => false,'title' => 'التقارير التي وُلّدت من حساب الوكيل','description' => 'سجلات حقيقية من جدول سجل التقارير.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'title' => 'التقارير التي وُلّدت من حساب الوكيل','description' => 'سجلات حقيقية من جدول سجل التقارير.']); ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التقرير</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الصيغة</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">عدد السجلات</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التاريخ</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php $__empty_1 = true; $__currentLoopData = $reportLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100"><?php echo e($log->typeLabel()); ?></td>
                                    <td class="px-4 py-3 text-gray-500"><?php echo e($log->formatLabel()); ?></td>
                                    <td class="px-4 py-3 tabular-nums text-gray-600 dark:text-gray-300"><?php echo e(number_format($log->row_count)); ?></td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?php echo e($log->created_at?->format('Y-m-d H:i')); ?></td>
                                    <td class="px-4 py-3"><a href="<?php echo e(route('admin.reports.logs.download', $log)); ?>" class="font-bold text-wajhatak-600 hover:text-wajhatak-700">إعادة التوليد</a></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">لم يولّد هذا الوكيل أي تقرير بعد.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        </div>


        <div x-show="tab === 'conversations'" x-cloak>
            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['padding' => false,'title' => 'محادثات الوكيل مع العملاء','description' => 'مراسلات حقيقية من قاعدة البيانات — لا يُعرض أي محتوى إلا للمشرف.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'title' => 'محادثات الوكيل مع العملاء','description' => 'مراسلات حقيقية من قاعدة البيانات — لا يُعرض أي محتوى إلا للمشرف.']); ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العميل</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العقار</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">آخر رسالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php $__empty_1 = true; $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100"><?php echo e($conversation->client?->name ?? '—'); ?></div>
                                        <div class="text-xs text-gray-400" dir="ltr"><?php echo e($conversation->client?->email ?? ''); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($conversation->property?->title ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?php echo e($conversation->last_message_at?->format('Y-m-d H:i') ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-400">لا توجد محادثات لهذا الوكيل.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        </div>


        <div x-show="tab === 'viewings'" x-cloak>
            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['padding' => false,'title' => 'طلبات معاينة الوكيل']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'title' => 'طلبات معاينة الوكيل']); ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العقار</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العميل</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الموعد</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php $__empty_1 = true; $__currentLoopData = $viewingRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $viewingRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200"><?php echo e($viewingRequest->property?->title ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><?php echo e($viewingRequest->client?->name ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?php echo e($viewingRequest->scheduled_date?->format('Y-m-d') ?? '—'); ?> <?php echo e($viewingRequest->scheduled_time ?? ''); ?></td>
                                    <td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
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
<?php endif; ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">لا توجد طلبات معاينة.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        </div>


        <div x-show="tab === 'activity'" x-cloak>
            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['padding' => false,'title' => 'سجل أنشطة حساب الوكيل']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'title' => 'سجل أنشطة حساب الوكيل']); ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النشاط</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النوع</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">عنوان IP</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200"><?php echo e($activity->description); ?></td>
                                    <td class="px-4 py-3 text-gray-500"><?php echo e($activity->log_name); ?></td>
                                    <td class="px-4 py-3 text-gray-500" dir="ltr"><?php echo e($activity->ip_address ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?php echo e($activity->created_at?->format('Y-m-d H:i')); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">لا توجد أنشطة مسجلة.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
<?php /**PATH /app/backend/resources/views/admin/agents/show.blade.php ENDPATH**/ ?>