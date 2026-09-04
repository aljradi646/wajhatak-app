<x-admin.layouts.admin heading="المواقع" title="المواقع" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')]]">
    @php
        $tab = request('tab', 'countries');
        $edit = request('edit', null);
        $selectedCountry = $tab === 'regions' ? (int) request('country_id', $regions->first()->country_id ?? '') : '';
        $selectedRegion = $tab === 'cities' ? (int) request('region_id', $cities->first()->region_id ?? '') : '';
        $selectedCity = $tab === 'areas' ? (int) request('city_id', $areas->first()->city_id ?? '') : '';
        $tabs = [
            'countries' => ['label' => 'الدول', 'count' => $countries->count()],
            'regions' => ['label' => 'المناطق', 'count' => $regions->count()],
            'cities' => ['label' => 'المدن', 'count' => $cities->count()],
            'areas' => ['label' => 'الأحياء', 'count' => $areas->count()],
        ];
    @endphp

    {{-- Tabs --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach($tabs as $key => $meta)
            <a href="{{ route('admin.locations.index', ['tab' => $key]) }}"
               @if($tab === $key) style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);" @endif
               class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition
                      {{ $tab === $key ? 'text-white shadow-lg' : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                {{ $meta['label'] }}
                <span class="text-xs {{ $tab === $key ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-700' }} rounded-full px-2 py-0.5">{{ $meta['count'] }}</span>
            </a>
        @endforeach
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ===================== COUNTRIES ===================== --}}
    @if($tab === 'countries')
        <x-admin.card>
            <h3 class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">
                {{ $edit ? "تعديل الدولة #{$edit}" : 'إضافة دولة' }}
            </h3>
            <form method="POST" action="{{ $edit ? route('admin.locations.country.update', Country::query()->find($edit)) : route('admin.locations.country.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @csrf
                @if($edit) @method('PUT') @endif
                @php $c = $edit ? Country::query()->find($edit) : null; @endphp
                <x-admin.input name="name_ar" label="الاسم بالعربية" value="{{ $c->name_ar ?? old('name_ar') }}" required />
                <x-admin.input name="name_en" label="الاسم بالإنجليزية" value="{{ $c->name_en ?? old('name_en') }}" placeholder="Country (EN)" />
                <x-admin.input name="code" label="الرمز" value="{{ $c->code ?? old('code') }}" placeholder="YE" maxlength="3" />
                <x-admin.input name="currency_code" label="رمز العملة" value="{{ $c->currency_code ?? old('currency_code') }}" placeholder="YER" maxlength="3" />
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 sm:col-span-2">
                    <input type="checkbox" name="is_active" value="1" @checked($c ? $c->is_active : true) class="rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600">
                    نشط
                </label>
                <div class="flex items-center gap-2 sm:col-span-2">
                    <x-admin.button type="submit">{{ $edit ? 'تحديث' : 'إضافة' }}</x-admin.button>
                    @if($edit)
                        <a href="{{ route('admin.locations.index', ['tab'=>'countries']) }}" class="text-sm text-gray-500 hover:text-gray-700">إلغاء</a>
                    @endif
                </div>
            </form>
        </x-admin.card>

        <x-admin.card :padding="false" class="mt-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الاسم (عربي)</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الاسم (إنجليزي)</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الرمز</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">العملة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">المناطق</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($countries as $country)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $country->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $country->name_en ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $country->code ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $country->currency_code ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $country->regions_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.locations.index', ['tab'=>'countries','edit'=>$country->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                    <form method="POST" action="{{ route('admin.locations.country.destroy', $country) }}" onsubmit="return confirm('حذف الدولة «{{ $country->name_ar }}»؟ ستحذف مناطقها ومدنها وأحياءها المرتبطة.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">لا توجد دول بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    @endif

    {{-- ===================== REGIONS ===================== --}}
    @if($tab === 'regions')
        <x-admin.card>
            <h3 class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">{{ $edit ? "تعديل المنطقة #{$edit}" : 'إضافة منطقة' }}</h3>
            <form method="POST" action="{{ $edit ? route('admin.locations.region.update', Region::query()->find($edit)) : route('admin.locations.region.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @csrf
                @if($edit) @method('PUT') @endif
                @php $r = $edit ? Region::query()->with('country')->find($edit) : null; @endphp
                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">الدولة</label>
                    <select name="country_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600" required>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" @selected(($r->country_id ?? old('country_id', $selectedCountry)) == $country->id)>{{ $country->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <x-admin.input name="name_ar" label="الاسم بالعربية" value="{{ $r->name_ar ?? old('name_ar') }}" required />
                <x-admin.input name="name_en" label="الاسم بالإنجليزية" value="{{ $r->name_en ?? old('name_en') }}" />
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1" @checked($r ? $r->is_active : true) class="rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600"> نشط
                </label>
                <div class="flex items-center gap-2">
                    <x-admin.button type="submit">{{ $edit ? 'تحديث' : 'إضافة' }}</x-admin.button>
                    @if($edit)<a href="{{ route('admin.locations.index', ['tab'=>'regions']) }}" class="text-sm text-gray-500 hover:text-gray-700">إلغاء</a>@endif
                </div>
            </form>
        </x-admin.card>

        <x-admin.card :padding="false" class="mt-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الاسم (عربي)</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الدولة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">المدن</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($regions as $region)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $region->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $region->country->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $region->cities_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.locations.index', ['tab'=>'regions','edit'=>$region->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                    <form method="POST" action="{{ route('admin.locations.region.destroy', $region) }}" onsubmit="return confirm('حذف المنطقة «{{ $region->name_ar }}»؟ ستحذف مدنها وأحياءها.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">لا توجد مناطق بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    @endif

    {{-- ===================== CITIES ===================== --}}
    @if($tab === 'cities')
        <x-admin.card>
            <h3 class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">{{ $edit ? "تعديل المدينة #{$edit}" : 'إضافة مدينة' }}</h3>
            <form method="POST" action="{{ $edit ? route('admin.locations.city.update', City::query()->find($edit)) : route('admin.locations.city.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @csrf
                @if($edit) @method('PUT') @endif
                @php $ci = $edit ? City::query()->with('region')->find($edit) : null; @endphp
                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">المنطقة</label>
                    <select name="region_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600" required>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" @selected(($ci->region_id ?? old('region_id', $selectedRegion)) == $region->id)>{{ $region->name_ar }} ({{ $region->country->name_ar }})</option>
                        @endforeach
                    </select>
                </div>
                <x-admin.input name="name_ar" label="الاسم بالعربية" value="{{ $ci->name_ar ?? old('name_ar') }}" required />
                <x-admin.input name="name_en" label="الاسم بالإنجليزية" value="{{ $ci->name_en ?? old('name_en') }}" />
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1" @checked($ci ? $ci->is_active : true) class="rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600"> نشط
                </label>
                <div class="flex items-center gap-2">
                    <x-admin.button type="submit">{{ $edit ? 'تحديث' : 'إضافة' }}</x-admin.button>
                    @if($edit)<a href="{{ route('admin.locations.index', ['tab'=>'cities']) }}" class="text-sm text-gray-500 hover:text-gray-700">إلغاء</a>@endif
                </div>
            </form>
        </x-admin.card>

        <x-admin.card :padding="false" class="mt-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الاسم (عربي)</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">المنطقة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الأحياء</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($cities as $city)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $city->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $city->region->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $city->areas_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.locations.index', ['tab'=>'cities','edit'=>$city->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                    <form method="POST" action="{{ route('admin.locations.city.destroy', $city) }}" onsubmit="return confirm('حذف المدينة «{{ $city->name_ar }}»؟ ستحذف أحياءها.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">لا توجد مدن بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    @endif

    {{-- ===================== AREAS ===================== --}}
    @if($tab === 'areas')
        <x-admin.card>
            <h3 class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">{{ $edit ? "تعديل الحي #{$edit}" : 'إضافة حي' }}</h3>
            <form method="POST" action="{{ $edit ? route('admin.locations.area.update', Area::query()->find($edit)) : route('admin.locations.area.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @csrf
                @if($edit) @method('PUT') @endif
                @php $a = $edit ? Area::query()->with('city')->find($edit) : null; @endphp
                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">المدينة</label>
                    <select name="city_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600" required>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" @selected(($a->city_id ?? old('city_id', $selectedCity)) == $city->id)>{{ $city->name_ar }} ({{ $city->region->name_ar }})</option>
                        @endforeach
                    </select>
                </div>
                <x-admin.input name="name_ar" label="الاسم بالعربية" value="{{ $a->name_ar ?? old('name_ar') }}" required />
                <x-admin.input name="name_en" label="الاسم بالإنجليزية" value="{{ $a->name_en ?? old('name_en') }}" />
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1" @checked($a ? $a->is_active : true) class="rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600"> نشط
                </label>
                <div class="flex items-center gap-2">
                    <x-admin.button type="submit">{{ $edit ? 'تحديث' : 'إضافة' }}</x-admin.button>
                    @if($edit)<a href="{{ route('admin.locations.index', ['tab'=>'areas']) }}" class="text-sm text-gray-500 hover:text-gray-700">إلغاء</a>@endif
                </div>
            </form>
        </x-admin.card>

        <x-admin.card :padding="false" class="mt-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الاسم (عربي)</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">المدينة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($areas as $area)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $area->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $area->city->name_ar }} ({{ $area->city->region->name_ar }})</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.locations.index', ['tab'=>'areas','edit'=>$area->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                    <form method="POST" action="{{ route('admin.locations.area.destroy', $area) }}" onsubmit="return confirm('حذف الحي «{{ $area->name_ar }}»؟');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">لا توجد أحياء بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    @endif
</x-admin.layouts.admin>
