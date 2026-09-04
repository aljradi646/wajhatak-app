<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <x-admin.input label="الاسم بالعربية" name="name_ar" value="{{ $propertyFeature->name_ar ?? '' }}" required />
    <x-admin.input label="الاسم بالإنجليزية" name="name_en" value="{{ $propertyFeature->name_en ?? '' }}" required />
    <x-admin.input label="الرابط المختصر (slug)" name="slug" value="{{ $propertyFeature->slug ?? '' }}" required />
    <x-admin.input label="الأيقونة" name="icon" value="{{ $propertyFeature->icon ?? '' }}" />
</div>
<div class="mt-5">
    <x-admin.checkbox label="خاصية نشطة" name="is_active" :checked="old('is_active', $propertyFeature->is_active ?? true)" />
</div>
