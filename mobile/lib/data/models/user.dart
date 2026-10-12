import 'package:flutter/foundation.dart';

/// المستخدم — يمثل العميل أو الوكيل أو المدير.
@immutable
class LuxUser {
  const LuxUser({
    required this.id,
    required this.name,
    required this.email,
    required this.roles,
    this.phone,
    this.avatarUrl,
    this.emailVerified,
    this.emailVerificationEnabled = true,
    this.agentVerificationStatus,
  });

  final int id;
  final String name;
  final String email;
  final List<String> roles;
  final String? phone;
  final String? avatarUrl;

  /// هل البريد موثق برمز فعلي؟ (null = بيانات قديمة قبل الميزة)
  final bool? emailVerified;

  /// هل طلب توثيق البريد مفعّل حاليًا في إعدادات المنصة؟
  final bool emailVerificationEnabled;

  /// حالة توثيق حساب الوكيل: pending | approved | rejected.
  final String? agentVerificationStatus;

  bool get isAgent => roles.contains('agent') || roles.contains('admin');
  bool get isAgentApproved => agentVerificationStatus == 'approved';

  factory LuxUser.fromJson(Map<String, dynamic> json) => LuxUser(
    id: json['id'] as int,
    name: json['name'] as String? ?? '',
    email: json['email'] as String? ?? '',
    phone: json['phone'] as String?,
    avatarUrl: json['avatar_url'] as String?,
    emailVerified: json['email_verified'] as bool?,
    emailVerificationEnabled: json['email_verification_enabled'] as bool? ?? true,
    agentVerificationStatus: json['agent_verification_status'] as String?,
    roles: (json['roles'] as List<dynamic>? ?? const []).cast<String>(),
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'phone': phone,
    'avatar_url': avatarUrl,
    'email_verified': emailVerified,
    'email_verification_enabled': emailVerificationEnabled,
    'agent_verification_status': agentVerificationStatus,
    'roles': roles,
  };

  LuxUser copyWith({
    int? id,
    String? name,
    String? email,
    List<String>? roles,
    String? phone,
    String? avatarUrl,
    bool? emailVerified,
    bool? emailVerificationEnabled,
    String? agentVerificationStatus,
  }) => LuxUser(
    id: id ?? this.id,
    name: name ?? this.name,
    email: email ?? this.email,
    roles: roles ?? this.roles,
    phone: phone ?? this.phone,
    avatarUrl: avatarUrl ?? this.avatarUrl,
    emailVerified: emailVerified ?? this.emailVerified,
    emailVerificationEnabled: emailVerificationEnabled ?? this.emailVerificationEnabled,
    agentVerificationStatus: agentVerificationStatus ?? this.agentVerificationStatus,
  );
}
