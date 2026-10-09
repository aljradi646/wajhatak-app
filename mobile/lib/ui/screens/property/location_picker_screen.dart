import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../../../core/services/location_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../widgets.dart';

/// نتيجة منتقي الموقع — إحداثيات حقيقية تُرسل مع بيانات العقار.
class PickedLocation {
  const PickedLocation({required this.latitude, required this.longitude});

  final double latitude;
  final double longitude;
}

/// شاشة تحديد موقع العقار على خريطة حقيقية:
/// • سحب الخريطة يبقي الدبوس في المنتصف (تحديد دقيق بلمسة واحدة).
/// • زر «موقعي الحالي» يستخدم GPS الجهاز بصلاحية فعلية.
/// • زر التأكيد يرجع الإحداثيات لنموذج إضافة العقار.
class LocationPickerScreen extends StatefulWidget {
  const LocationPickerScreen({
    super.key,
    this.initialLatitude,
    this.initialLongitude,
  });

  final double? initialLatitude;
  final double? initialLongitude;

  @override
  State<LocationPickerScreen> createState() => _LocationPickerScreenState();
}

class _LocationPickerScreenState extends State<LocationPickerScreen> {
  late LatLng _center;
  bool _locating = false;

  @override
  void initState() {
    super.initState();
    _center = LatLng(
      widget.initialLatitude ?? 15.3694,
      widget.initialLongitude ?? 44.191,
    );
  }

  Future<void> _useMyLocation() async {
    setState(() => _locating = true);
    final result = await LocationService.requestAndLocate();
    if (!mounted) return;
    setState(() => _locating = false);
    if (result.granted && result.latitude != null) {
      setState(() {
        _center = LatLng(result.latitude!, result.longitude!);
      });
      _mapController.move(_center, 16);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result.message),
          duration: const Duration(seconds: 3),
        ),
      );
    }
  }

  final MapController _mapController = MapController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: WajhatakScreenHeader(title: 'حدد موقع العقار على الخريطة'),
      body: Stack(
        children: [
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: _center,
              initialZoom: widget.initialLatitude != null ? 16 : 12,
              interactionOptions: const InteractionOptions(
                flags: InteractiveFlag.all,
              ),
              onPositionChanged: (position, hasGesture) {
                if (hasGesture) {
                  _center = position.center;
                }
              },
            ),
            children: [
              TileLayer(
                urlTemplate:
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                userAgentPackageName: 'com.wajhatak.app',
              ),
              TileLayer(
                urlTemplate:
                    'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
                userAgentPackageName: 'com.wajhatak.app',
              ),
              const RichAttributionWidget(
                attributions: [
                  TextSourceAttribution('© Esri, Maxar, Earthstar Geographics'),
                ],
              ),
            ],
          ),

          // دبوس ثابت في منتصف الشاشة — حرّك الخريطة تحته لتحديد الموقع.
          Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.location_on,
                  size: 52,
                  color: WajhatakColors.terracotta,
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: .6),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Text(
                    'حرّك الخريطة حتى يستقر الدبوس على موقع العقار',
                    style: TextStyle(color: Colors.white, fontSize: 11),
                    textAlign: TextAlign.center,
                  ),
                ),
              ],
            ),
          ),

          // زر موقعي الحالي (GPS حقيقي).
          Positioned(
            top: 12,
            left: 12,
            child: FloatingActionButton.small(
              heroTag: 'pick_my_location',
              onPressed: _locating ? null : _useMyLocation,
              backgroundColor: Colors.white,
              child: _locating
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.my_location, size: 20),
            ),
          ),

          // شريط التأكيد السفلي — يرجع الإحداثيات الحقيقية.
          Positioned(
            left: 20,
            right: 20,
            bottom: 20,
            child: SafeArea(
              child: FilledButton.icon(
                onPressed: () => Navigator.of(context).pop(
                  PickedLocation(
                    latitude: _center.latitude,
                    longitude: _center.longitude,
                  ),
                ),
                icon: const Icon(Icons.check_rounded),
                label: const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: Text(
                    'تأكيد موقع العقار',
                    style: TextStyle(fontSize: 15),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
