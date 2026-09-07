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
  });

  final int id;
  final String name;
  final String email;
  final List<String> roles;
  final String? phone;
  final String? avatarUrl;

  bool get isAgent => roles.contains('agent') || roles.contains('admin');

  factory LuxUser.fromJson(Map<String, dynamic> json) => LuxUser(
    id: json['id'] as int,
    name: json['name'] as String? ?? '',
    email: json['email'] as String? ?? '',
    phone: json['phone'] as String?,
    avatarUrl: json['avatar_url'] as String?,
    roles: (json['roles'] as List<dynamic>? ?? const []).cast<String>(),
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'phone': phone,
    'avatar_url': avatarUrl,
    'roles': roles,
  };

  LuxUser copyWith({
    int? id,
    String? name,
    String? email,
    List<String>? roles,
    String? phone,
    String? avatarUrl,
  }) => LuxUser(
    id: id ?? this.id,
    name: name ?? this.name,
    email: email ?? this.email,
    roles: roles ?? this.roles,
    phone: phone ?? this.phone,
    avatarUrl: avatarUrl ?? this.avatarUrl,
  );
}
