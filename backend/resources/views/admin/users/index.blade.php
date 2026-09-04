<x-admin.layouts.admin heading="المستخدمون" title="المستخدمون">
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <form method="GET" class="flex gap-2 sm:w-80">
            <x-admin.input name="search" value="{{ $search }}" placeholder="بحث بالاسم أو البريد أو الجوال..." />
            <x-admin.button variant="secondary" type="submit">بحث</x-admin.button>
        </form>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-white rounded-xl hover:shadow-lg" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E); box-shadow: 0 4px 12px rgba(14, 138, 109, 0.25);">
            + مستخدم جديد
        </a>
    </div>

    <x-admin.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الاسم</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">البريد الإلكتروني</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الجوال</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الدور</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">نشط</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">تاريخ الإنشاء</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->phone ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @foreach($user->roles as $role)
                                    <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-semibold">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs font-semibold">نشط</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-semibold">غير نشط</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-wajhatak-600 hover:text-wajhatak-700 text-sm font-medium">عرض</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">لا توجد مستخدمون.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $users])
    </x-admin.card>
</x-admin.layouts.admin>
