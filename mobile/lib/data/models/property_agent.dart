import 'package:flutter/foundation.dart';

/// الوكيل المسؤول عن العقار.
@immutable
class PropertyAgent {
  const PropertyAgent({
    required this.id,
    required this.name,
    this.phone,
    this.rating,
    this.reviewsCount,
    this.propertiesCount,
    this.bio,
    this.avatarUrl,
    this.isActive = true,
    this.isVerified = false,
    this.verificationStatus,
  });

  final int id;
  final String name;
  final String? phone;
  final double? rating;
  final int? reviewsCount;
  final int? propertiesCount;
  final String? bio;
  final String? avatarUrl;
  final bool isActive;

  /// هل اعتمدت الإدارة توثيق هذا الوكيل فعليًا؟ (تُقرأ من الخادم، لا تُفترض)
  final bool isVerified;

  /// pending | approved | rejected
  final String? verificationStatus;

  factory PropertyAgent.fromJson(Map<String, dynamic> json) => PropertyAgent(
    id: json['id'] as int,
    name: json['name'] as String? ?? '',
    phone: json['phone'] as String?,
    rating: (json['rating'] as num?)?.toDouble(),
    reviewsCount: (json['reviews_count'] as num?)?.toInt(),
    propertiesCount: (json['properties_count'] as num?)?.toInt(),
    bio: json['bio'] as String?,
    avatarUrl: json['avatar_url'] as String?,
    isActive: json['is_active'] as bool? ?? true,
    isVerified: json['is_verified'] as bool? ?? false,
    verificationStatus: json['verification_status'] as String?,
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'phone': phone,
    'rating': rating,
    'reviews_count': reviewsCount,
    'properties_count': propertiesCount,
    'bio': bio,
    'avatar_url': avatarUrl,
    'is_active': isActive,
    'is_verified': isVerified,
    'verification_status': verificationStatus,
  };
}
