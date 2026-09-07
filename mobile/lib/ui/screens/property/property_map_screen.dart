import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../../../core/theme/app_theme.dart';
import '../../../data/models/models.dart';
import '../../widgets.dart';
import 'property_detail_screen.dart';

class PropertyMapScreen extends StatelessWidget {
  const PropertyMapScreen({super.key, required this.properties});
  final List<LuxProperty> properties;

  @override
  Widget build(BuildContext context) {
    final mapped = properties
        .where(
          (property) =>
              property.location?.latitude != null &&
              property.location?.longitude != null,
        )
        .toList();
    final center = mapped.isEmpty
        ? const LatLng(24.7136, 46.6753)
        : LatLng(
            mapped.first.location!.latitude!,
            mapped.first.location!.longitude!,
          );
    return Scaffold(
      appBar: WajhatakScreenHeader(title: 'خريطة العقارات'),
      body: Stack(
        children: [
          FlutterMap(
            options: MapOptions(
              initialCenter: center,
              initialZoom: mapped.isEmpty ? 10 : 12,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'com.wajhatak.app',
              ),
              MarkerLayer(
                markers: [
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
                  TextSourceAttribution('© OpenStreetMap contributors'),
                ],
              ),
            ],
          ),
          if (mapped.isEmpty)
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
