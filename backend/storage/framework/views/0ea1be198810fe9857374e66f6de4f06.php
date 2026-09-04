<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['heading' => 'لوحة التحكم', 'title' => 'لوحة تحكم وجهتك']));

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

foreach (array_filter((['heading' => 'لوحة التحكم', 'title' => 'لوحة تحكم وجهتك']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e($title); ?> — <?php echo e(config('app.name', 'وجهتك')); ?></title>

        <link rel="icon" type="image/png" href="<?php echo e(asset('storage/branding/logo-small.png')); ?>">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

        <style>
            .fi-body { font-family: 'Cairo', ui-sans-serif, system-ui, sans-serif; }
            [x-cloak] { display: none !important; }

            /* براند مارك بتدرج زمردي */
            .brand-mark {
                background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);
                box-shadow: 0 4px 14px rgba(14, 138, 109, 0.3);
            }

            /* أزرار أساسية بتدرج زمردي */
            .btn-brand {
                background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);
                box-shadow: 0 4px 14px rgba(14, 138, 109, 0.25);
                color: #fff;
            }
            .btn-brand:hover {
                box-shadow: 0 6px 20px rgba(14, 138, 109, 0.4);
                transform: translateY(-1px);
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100" style="font-family:'Cairo',ui-sans-serif,system-ui,sans-serif;">
        <div
            x-data="{ sidebarOpen: false, collapsed: (window.matchMedia('(min-width: 1024px)').matches && localStorage.getItem('lux_sidebar') === '1') }"
            x-init="$watch('collapsed', value => localStorage.setItem('lux_sidebar', value ? '1' : '0'))"
            class="min-h-screen flex"
        >
            
            <div x-show="sidebarOpen" x-transition:opacity @click="sidebarOpen = false" x-cloak class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"></div>

            
            <aside
                :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'"
                class="fixed lg:static inset-y-0 right-0 z-50 lg:z-auto transition-all duration-300 ease-in-out flex flex-col lg:translate-x-0
                       bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 lg:border-l-0
                       shadow-2xl lg:shadow-none"
            >
                <div class="flex flex-col h-full w-72"
                     :class="collapsed ? 'lg:w-20' : 'lg:w-72'">
                    
                    <div class="flex items-center gap-3 h-16 px-5 border-b border-gray-200 dark:border-gray-700 shrink-0 min-w-0">
                        <img src="<?php echo e(asset('storage/branding/logo.png')); ?>" alt="شعار وجهتك" class="h-9 w-9 shrink-0 rounded-xl object-contain bg-white shadow-sm" />
                        <div class="min-w-0 flex-1" x-show="!collapsed" x-cloak>
                            <div class="font-extrabold tracking-[0.2em] text-[10px] leading-none" style="color: #B97D1B;">WAJHATAK</div>
                            <div class="font-extrabold text-gray-900 dark:text-gray-100 leading-tight text-lg">وجهتك</div>
                        </div>
                        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'close','class' => 'h-6 w-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'close','class' => 'h-6 w-6']); ?>
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
                        </button>
                    </div>

                    
                    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.dashboard')).'','active' => request()->routeIs('admin.dashboard')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.dashboard')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.dashboard'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'dashboard']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'dashboard']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            لوحة التحكم
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <div x-show="!collapsed" class="pt-4 text-[11px] font-bold uppercase tracking-wider text-gray-400 px-3 dark:text-gray-500" x-cloak>الإدارة</div>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.users.index')).'','active' => request()->routeIs('admin.users.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.users.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.users.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'users']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'users']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            المستخدمون
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.activity-logs.index')).'','active' => request()->routeIs('admin.activity-logs.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.activity-logs.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.activity-logs.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'activity']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'activity']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            سجل النشاطات
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.locations.index')).'','active' => request()->routeIs('admin.locations.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.locations.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.locations.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'location']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'location']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            المواقع
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.agents.index')).'','active' => request()->routeIs('admin.agents.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.agents.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.agents.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'agents']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'agents']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            الوكلاء
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.properties.index')).'','active' => request()->routeIs('admin.properties.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.properties.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.properties.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'properties']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'properties']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            العقارات
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.viewing-requests.index')).'','active' => request()->routeIs('admin.viewing-requests.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.viewing-requests.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.viewing-requests.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'viewing-requests']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'viewing-requests']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            طلبات المعاينة
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <div x-show="!collapsed" class="pt-4 text-[11px] font-bold uppercase tracking-wider text-gray-400 px-3 dark:text-gray-500" x-cloak>المحتوى</div>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.property-types.index')).'','active' => request()->routeIs('admin.property-types.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.property-types.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.property-types.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'property-types']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'property-types']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            أنواع العقارات
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.property-features.index')).'','active' => request()->routeIs('admin.property-features.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.property-features.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.property-features.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'property-features']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'property-features']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            خصائص العقارات
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.nav-link','data' => ['href' => ''.e(route('admin.settings.index')).'','active' => request()->routeIs('admin.settings.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('admin.settings.index')).'','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.settings.*'))]); ?>
                             <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'settings']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'settings']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $attributes = $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd)): ?>
<?php $component = $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd; ?>
<?php unset($__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                            الإعدادات
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $attributes = $__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__attributesOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9)): ?>
<?php $component = $__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9; ?>
<?php unset($__componentOriginal2a216ff9e9eb80e1c598e0a42b5b7ec9); ?>
<?php endif; ?>
                    </nav>

                    
                    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700 shrink-0">
                        <p class="text-xs text-gray-400 leading-snug dark:text-gray-500" x-show="!collapsed" x-cloak>وجهتك إلى العقار المناسب.</p>
                        <div class="text-[10px] text-gray-300 dark:text-gray-600">لوحة تحكم المشرف</div>
                    </div>
                </div>
            </aside>

            
            <div class="flex-1 flex flex-col min-w-0">
                
                <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                    <div class="flex items-center h-16 px-4 sm:px-6 gap-4">
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'menu','class' => 'h-6 w-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'menu','class' => 'h-6 w-6']); ?>
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
                        </button>

                        
                        <button
                            @click="collapsed = !collapsed"
                            class="hidden lg:inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 me-0 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800"
                            title="طي / توسيع القائمة الجانبية"
                            aria-label="إظهار أو إخفاء القائمة الجانبية"
                        >
                            <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['xShow' => '!collapsed','name' => 'collapse-right','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-show' => '!collapsed','name' => 'collapse-right','class' => 'h-5 w-5']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['xShow' => 'collapsed','name' => 'collapse-left','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-show' => 'collapsed','name' => 'collapse-left','class' => 'h-5 w-5']); ?>
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
                        </button>

                        <h1 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-100"><?php echo e($heading); ?></h1>

                        <?php if(isset($breadcrumbs)): ?>
                            <nav class="hidden md:flex items-center text-sm text-gray-400 ms-auto dark:text-gray-500">
                                <?php $__currentLoopData = $breadcrumbs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $crumb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if(! $loop->last): ?>
                                        <a href="<?php echo e($crumb['url']); ?>" class="hover:text-wajhatak-600"><?php echo e($crumb['label']); ?></a>
                                        <span class="mx-2">‹</span>
                                    <?php else: ?>
                                        <span class="text-gray-600 dark:text-gray-300"><?php echo e($crumb['label']); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </nav>
                        <?php endif; ?>

                        <div class="ms-auto flex items-center gap-2">
                            <?php if (isset($component)) { $__componentOriginal85f185e3f15d6d27cb00c3d80daf57d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal85f185e3f15d6d27cb00c3d80daf57d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.theme-switcher','data' => ['theme' => 'system']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.theme-switcher'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['theme' => 'system']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal85f185e3f15d6d27cb00c3d80daf57d6)): ?>
<?php $attributes = $__attributesOriginal85f185e3f15d6d27cb00c3d80daf57d6; ?>
<?php unset($__attributesOriginal85f185e3f15d6d27cb00c3d80daf57d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal85f185e3f15d6d27cb00c3d80daf57d6)): ?>
<?php $component = $__componentOriginal85f185e3f15d6d27cb00c3d80daf57d6; ?>
<?php unset($__componentOriginal85f185e3f15d6d27cb00c3d80daf57d6); ?>
<?php endif; ?>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-200 dark:hover:text-white">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-white font-bold brand-mark">
                                        <?php echo e(mb_substr(Auth::user()->name, 0, 1)); ?>

                                    </span>
                                    <span class="hidden sm:block"><?php echo e(Auth::user()->name); ?></span>
                                    <?php if (isset($component)) { $__componentOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906aaa6a63a2f5f8b29c23c3195c96dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.icon','data' => ['name' => 'chevron-down','class' => 'h-4 w-4 text-gray-400 dark:text-gray-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'h-4 w-4 text-gray-400 dark:text-gray-500']); ?>
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
                                </button>

                                <div x-show="open" x-transition @click.outside="open = false" x-cloak class="absolute left-0 mt-2 w-56 rounded-2xl shadow-xl bg-white ring-1 ring-black/5 z-50 dark:bg-gray-800 dark:ring-gray-700">
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100"><?php echo e(Auth::user()->name); ?></div>
                                        <div class="text-xs text-gray-500 truncate dark:text-gray-400"><?php echo e(Auth::user()->email); ?></div>
                                    </div>
                                    <div class="py-1.5">
                                        <a href="<?php echo e(route('profile.edit')); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">الملف الشخصي</a>
                                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                                        <form method="POST" action="<?php echo e(route('logout')); ?>" class="block">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">تسجيل الخروج</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                
                <main class="flex-1 p-4 sm:p-6">
                    <?php if(session('status')): ?>
                        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-900/30 dark:text-green-300"><?php echo e(session('status')); ?></div>
                    <?php endif; ?>
                    <?php if(session('error')): ?>
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-900/30 dark:text-red-300"><?php echo e(session('error')); ?></div>
                    <?php endif; ?>

                    <?php echo e($slot); ?>

                </main>
            </div>
        </div>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    </body>
</html>

<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\components\admin\layouts\admin.blade.php ENDPATH**/ ?>