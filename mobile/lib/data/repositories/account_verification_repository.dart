import 'dart:io';

import 'package:dio/dio.dart';

import '../api_client.dart';
import '../../core/utils/image_compressor.dart';

/// نتيجة عمليات التحقق من البريد.
class VerificationResult {
  const VerificationResult({required this.ok, required this.message, this.resendIn = 0});

  final bool ok;
  final String message;
  final int resendIn;
}

/// حالة توثيق حساب الوكيل كما يراها التطبيق.
class AgentProfileData {
  const AgentProfileData({
    required this.verificationStatus,
    this.rejectionReason,
    this.agencyName,
    this.jobTitle,
    this.phone,
    this.whatsapp,
    this.city,
    this.nationalId,
    this.experienceYears,
    this.address,
    this.website,
    this.facebook,
    this.instagram,
    this.twitter,
    this.bio,
    this.licenseNumber,
    this.photoUrl,
    this.idDocumentUrl,
    this.licenseDocumentUrl,
  });

  final String verificationStatus;
  final String? rejectionReason;
  final String? agencyName;
  final String? jobTitle;
  final String? phone;
  final String? whatsapp;
  final String? city;
  final String? nationalId;
  final int? experienceYears;
  final String? address;
  final String? website;
  final String? facebook;
  final String? instagram;
  final String? twitter;
  final String? bio;
  final String? licenseNumber;
  final String? photoUrl;
  final String? idDocumentUrl;
  final String? licenseDocumentUrl;

  bool get isApproved => verificationStatus == 'approved';
  bool get isPending => verificationStatus == 'pending';
  bool get isRejected => verificationStatus == 'rejected';

  static AgentProfileData fromJson(Map<String, dynamic> json) => AgentProfileData(
        verificationStatus: json['verification_status'] as String? ?? 'pending',
        rejectionReason: json['rejection_reason'] as String?,
        agencyName: json['agency_name'] as String?,
        jobTitle: json['job_title'] as String?,
        phone: json['phone'] as String?,
        whatsapp: json['whatsapp'] as String?,
        city: json['city'] as String?,
        nationalId: json['national_id'] as String?,
        experienceYears: (json['experience_years'] as num?)?.toInt(),
        address: json['address'] as String?,
        website: json['website'] as String?,
        facebook: json['facebook'] as String?,
        instagram: json['instagram'] as String?,
        twitter: json['twitter'] as String?,
        bio: json['bio'] as String?,
        licenseNumber: json['license_number'] as String?,
        photoUrl: json['photo_url'] as String?,
        idDocumentUrl: json['id_document_url'] as String?,
        licenseDocumentUrl: json['license_document_url'] as String?,
      );
}

/// التحقق من البريد + ملف توثيق الوكيل.
class AccountRepository {
  AccountRepository(this._api);

  final LuxApiClient _api;
  final ImageCompressor _compressor = ImageCompressor();

  /// حالة التحقق: هل البريد موثق؟ وكم تبقى لإعادة الإرسال؟
  Future<({bool verified, int resendIn})> emailStatus() async {
    final json = await _api.get('/me/email/status');
    final data = json['data'] as Map<String, dynamic>? ?? const {};
    return (
      verified: data['verified'] as bool? ?? false,
      resendIn: (data['resend_in'] as num?)?.toInt() ?? 0,
    );
  }

  /// طلب رمز جديد إلى البريد الحقيقي.
  Future<VerificationResult> sendVerificationCode() async {
    final json = await _api.post('/me/email/verification-code');
    final data = json['data'] as Map<String, dynamic>? ?? const {};
    return VerificationResult(
      ok: data['ok'] as bool? ?? false,
      message: data['message'] as String? ?? '',
      resendIn: (data['resend_in'] as num?)?.toInt() ?? 0,
    );
  }

  /// تأكيد الرمز المُدخل.
  Future<VerificationResult> confirmCode(String code) async {
    try {
      final json = await _api.post('/me/email/verify', data: {'code': code});
      final data = json['data'] as Map<String, dynamic>? ?? const {};
      return VerificationResult(
        ok: data['verified'] as bool? ?? false,
        message: data['message'] as String? ?? '',
      );
    } on ApiFailure catch (failure) {
      return VerificationResult(ok: false, message: failure.message);
    }
  }

  /// ملف توثيق الوكيل.
  Future<AgentProfileData> agentProfile() async {
    final json = await _api.get('/me/agent-profile');
    return AgentProfileData.fromJson(json['data'] as Map<String, dynamic>? ?? {});
  }

  /// حفظ ملف توثيق الوكيل (بيانات + مستندات اختيارية).
  Future<AgentProfileData> saveAgentProfile({
    required Map<String, dynamic> data,
    File? idDocument,
    File? licenseDocument,
    File? photo,
  }) async {
    Object payload = data;

    if (idDocument != null || licenseDocument != null || photo != null) {
      final map = <String, dynamic>{...data};
      if (photo != null) {
        final compressed = await _compressor.compress(photo);
        map['photo'] = await MultipartFile.fromFile(compressed);
      }
      if (idDocument != null) {
        final compressed = await _compressor.compress(idDocument);
        map['id_document'] = await MultipartFile.fromFile(compressed);
      }
      if (licenseDocument != null) {
        final compressed = await _compressor.compress(licenseDocument);
        map['license_document'] = await MultipartFile.fromFile(compressed);
      }
      payload = FormData.fromMap(map);
    }

    final json = await _api.post('/me/agent-profile', data: payload);
    return AgentProfileData.fromJson(json['data'] as Map<String, dynamic>? ?? {});
  }
}
