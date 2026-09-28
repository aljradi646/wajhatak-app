<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ملف الوكيل الكامل — بيانات التوثيق التي تحتاجها الإدارة للقبول.
 * الوكيل يعبئها مرة واحدة بعد التسجيل ويستطيع تحديثها أثناء pending/rejected.
 */
class AgentProfileController extends Controller
{
    /** GET /me/agent-profile */
    public function show(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        abort_unless($agent, 404, 'لا يوجد ملف وكيل لهذا الحساب.');

        return response()->json(['data' => $this->present($agent)]);
    }

    /** POST /me/agent-profile — إنشاء/تحديث بيانات التوثيق. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'agency_name' => ['nullable', 'string', 'max:190'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:120'],
            'national_id' => ['nullable', 'string', 'max:60'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'id_document' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'license_document' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'website.url' => 'رابط الموقع الإلكتروني غير صحيح.',
            'facebook.url' => 'رابط فيسبوك غير صحيح.',
            'instagram.url' => 'رابط إنستغرام غير صحيح.',
            'twitter.url' => 'رابط X/تويتر غير صحيح.',
            'id_document.image' => 'صورة الهوية يجب أن تكون ملف صورة.',
            'license_document.image' => 'صورة الترخيص يجب أن تكون ملف صورة.',
            'photo.image' => 'الصورة الشخصية يجب أن تكون ملف صورة.',
        ]);

        $agent = $user->agentProfile;
        if ($agent === null) {
            // حساب وكيل سجّل قبل وجود ملف: أنشئ صفًا معلق التوثيق.
            $agent = Agent::query()->create([
                'user_id' => $user->id,
                'is_active' => false,
                'verification_status' => 'pending',
            ]);
        }

        abort_if($agent->verification_status === 'approved' && ! $user->hasRole('admin'), 403, 'حسابك موثق — تعديل بيانات التوثيق يتطلب مراجعة الإدارة.');

        // المستندات: تخزين حقيقي في public disk.
        foreach (['id_document' => 'id_document_path', 'license_document' => 'license_document_path', 'photo' => 'photo_path'] as $fileKey => $column) {
            if ($request->hasFile($fileKey)) {
                $old = $agent->{$column};
                $path = $request->file($fileKey)->store("agents/{$agent->id}", 'public');
                $data[$column] = $path;
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
            }
        }

        $agent->fill(collect($data)->except(['id_document', 'license_document', 'photo'])->all())->save();

        // أي تعديل بعد الرفض يعيد الطلب إلى قائمة المراجعة.
        if ($agent->verification_status === 'rejected') {
            $agent->forceFill([
                'verification_status' => 'pending',
                'rejection_reason' => null,
            ])->save();
        }

        return response()->json(['data' => $this->present($agent->fresh())]);
    }

    private function present(Agent $agent): array
    {
        $docUrl = fn (?string $path) => $path ? asset('storage/'.$path) : null;

        return [
            'verification_status' => $agent->verification_status,
            'rejection_reason' => $agent->rejection_reason,
            'verified_at' => $agent->verified_at?->toISOString(),
            'agency_name' => $agent->agency_name,
            'job_title' => $agent->job_title,
            'phone' => $agent->phone,
            'whatsapp' => $agent->whatsapp,
            'city' => $agent->city,
            'national_id' => $agent->national_id,
            'experience_years' => $agent->experience_years,
            'address' => $agent->address,
            'website' => $agent->website,
            'facebook' => $agent->facebook,
            'instagram' => $agent->instagram,
            'twitter' => $agent->twitter,
            'bio' => $agent->bio,
            'license_number' => $agent->license_number,
            'photo_url' => $docUrl($agent->photo_path),
            'id_document_url' => $docUrl($agent->id_document_path),
            'license_document_url' => $docUrl($agent->license_document_path),
        ];
    }
}
