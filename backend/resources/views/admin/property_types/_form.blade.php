<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <x-admin.input label="الاسم بالعربية" name="name_ar" value="{{ $propertyType->name_ar ?? '' }}" required />
    <x-admin.input label="الاسم بالإنجليزية" name="name_en" value="{{ $propertyType->name_en ?? '' }}" required />
    <x-admin.input label="الرابط المختصر (slug)" name="slug" value="{{ $propertyType->slug ?? '' }}" required />
</div>
<div class="mt-5">
    <x-admin.checkbox label="نوع عقار نشط" name="is_active" :checked="old('is_active', $propertyType->is_active ?? true)" />
</div>
