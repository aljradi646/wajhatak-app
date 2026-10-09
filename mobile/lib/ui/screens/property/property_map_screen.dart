import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../../../core/services/location_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/models.dart';
import '../../widgets.dart';
import 'property_detail_screen.dart';

/// خريطة أقمار صناعية حقيقية — تطلب صلاحية الموقع عند الدخول،
/// وتُظهر موقعك الحقيقي بجانب بطاقات العقارات، والنقر على أي بطاقة
/// يفتح تفاصيل العقار.
class PropertyMapScreen extends StatefulWidget {
  const PropertyMapScreen({super.key, required this.properties});

  final List<LuxProperty> properties;

  @override
  State<PropertyMapScreen> createState() => _PropertyMapScreenState();
}

class _PropertyMapScreenState extends State<PropertyMapScreen> {
  bool _locating = true;
  double? _myLatitude;
  double? _myLongitude;
  String? _locationMessage;
  final MapController _mapController = MapController();

  @override
  void initState() {
    super.initState();
    // طلب صلاحية الموقع وتحديد الموقع الحقيقي فور الدخول للخريطة.
    _locateUser();
  }

  Future<void> _locateUser() async {
    final result = await LocationService.requestAndLocate();
    if (!mounted) return;
    setState(() {
      _locating = false;
      _myLatitude = result.latitude;
      _myLongitude = result.longitude;
      _locationMessage = result.granted ? null : result.message;
    });

    // توسيط الخريطة على موقع المستخدم الحقيقي إن توفر.
    if (result.granted && result.latitude != null && mounted) {
      _mapController.move(LatLng(result.latitude!, result.longitude!), 14);
    }
  }

  @override
  Widget build(BuildContext context) {
    final mapped = widget.properties
        .where(
          (property) =>
              property.location?.latitude != null &&
              property.location?.longitude != null,
        )
        .toList();
    final center = _myLatitude != null
        ? LatLng(_myLatitude!, _myLongitude!)
        : (mapped.isEmpty
              ? const LatLng(24.7136, 46.6753)
              : LatLng(
                  mapped.first.location!.latitude!,
                  mapped.first.location!.longitude!,
                ));

    return Scaffold(
      appBar: WajhatakScreenHeader(title: 'خريطة العقارات'),
      body: Stack(
        children: [
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: center,
              initialZoom: _myLatitude != null
                  ? 14
                  : (mapped.isEmpty ? 10 : 12),
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
              MarkerLayer(
                markers: [
                  // موقع العميل الحقيقي (دائرة زرقاء).
                  if (_myLatitude != null && _myLongitude != null)
                    Marker(
                      point: LatLng(_myLatitude!, _myLongitude!),
                      width: 26,
                      height: 26,
                      child: Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E88E5).withValues(alpha: .35),
                          shape: BoxShape.circle,
                        ),
                        padding: const EdgeInsets.all(5),
                        child: Container(
                          decoration: const BoxDecoration(
                            color: Color(0xFF1E88E5),
                            shape: BoxShape.circle,
                          ),
                        ),
                      ),
                    ),
                  // بطاقات العقارات — النقر يفتح التفاصيل.
                  for (final property in mapped)
                    Marker(
                      point: LatLng(
                        property.location!.latitude!,
                        property.location!.longitude!,
                      ),
                      width: 48,
                      height: 48,
                      child: GestureDetector(
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) =>
                                PropertyDetailScreen(propertyId: property.id),
                          ),
                        ),
                        child: const Icon(
                          Icons.location_on,
                          size: 46,
                          color: WajhatakColors.terracotta,
                        ),
                      ),
                    ),
                ],
              ),
              const RichAttributionWidget(
                attributions: [
                  TextSourceAttribution('© Esri, Maxar, Earthstar Geographics'),
                ],
              ),
            ],
          ),

          // زر «موقعي» — يعيد طلب الموقع ويتوسّط عليه.
          Positioned(
            top: 12,
            left: 12,
            child: FloatingActionButton.small(
              heroTag: 'my_location_map',
              onPressed: _locating ? null : _locateUser,
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

          if (_locationMessage != null)
            Align(
              alignment: Alignment.bottomCenter,
              child: SafeArea(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Card(
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Text(
                        _locationMessage!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontSize: 13),
                      ),
                    ),
                  ),
                ),
              ),
            )
          else if (mapped.isEmpty)
            Align(
              alignment: Alignment.bottomCenter,
              child: SafeArea(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Card(
                    child: const Padding(
                      padding: EdgeInsets.all(16),
                      child: Text(
                        'لا تحتوي النتائج الحالية على إحداثيات منشورة لعرضها على الخريطة.',
                        textAlign: TextAlign.center,
                      ),
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
