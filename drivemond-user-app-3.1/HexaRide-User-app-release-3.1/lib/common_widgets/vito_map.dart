import 'dart:async';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart' as gmap;
import 'package:mapbox_maps_flutter/mapbox_maps_flutter.dart' as mbx;
import 'package:ride_sharing_user_app/features/splash/controllers/config_controller.dart';

/// Provider-agnostic map facade. Renders Google Maps by default (behaviour
/// identical to before) and Mapbox when the backend config sets
/// `map_provider == 'mapbox'`.
///
/// NOTE: the Mapbox branch is written against mapbox_maps_flutter ^2.9 and is
/// device-verified by the integrator — CI does not compile/render it. The
/// Google branch is the production default and must stay behaviour-identical.

typedef VitoMapCreatedCallback = void Function(VitoMapController controller);

/// Marker model that carries the raw icon bytes — needed because a Google
/// [gmap.Marker]'s BitmapDescriptor does not expose its bytes for Mapbox.
class VitoMarker {
  final String id;
  final gmap.LatLng position;
  final Uint8List? iconBytes;
  final double rotation;
  final Offset anchor;
  final VoidCallback? onTap;

  const VitoMarker({
    required this.id,
    required this.position,
    this.iconBytes,
    this.rotation = 0,
    this.anchor = const Offset(0.5, 0.5),
    this.onTap,
  });

  gmap.Marker toGoogleMarker() => gmap.Marker(
        markerId: gmap.MarkerId(id),
        position: position,
        icon: iconBytes != null ? gmap.BitmapDescriptor.bytes(iconBytes!) : gmap.BitmapDescriptor.defaultMarker,
        rotation: rotation,
        anchor: anchor,
        onTap: onTap,
      );
}

bool useMapboxProvider() {
  try {
    return (Get.find<ConfigController>().config?.mapProvider ?? 'google') == 'mapbox';
  } catch (_) {
    return false;
  }
}

String _mapboxAccessToken() {
  try {
    return Get.find<ConfigController>().config?.mapboxAccessToken ?? '';
  } catch (_) {
    return '';
  }
}

/// Thin controller abstraction over GoogleMapController / MapboxMap exposing
/// only the camera operations the app actually uses.
class VitoMapController {
  final gmap.GoogleMapController? _google;
  final mbx.MapboxMap? _mapbox;

  VitoMapController.google(this._google) : _mapbox = null;
  VitoMapController.mapbox(this._mapbox) : _google = null;

  gmap.GoogleMapController? get googleController => _google;
  mbx.MapboxMap? get mapboxController => _mapbox;

  Future<void> animateCamera(gmap.LatLng target, {double zoom = 16, double bearing = 0, double tilt = 0}) async {
    if (_google != null) {
      await _google!.animateCamera(gmap.CameraUpdate.newCameraPosition(
        gmap.CameraPosition(target: target, zoom: zoom, bearing: bearing, tilt: tilt),
      ));
    } else if (_mapbox != null) {
      await _mapbox!.flyTo(
        mbx.CameraOptions(
          center: mbx.Point(coordinates: mbx.Position(target.longitude, target.latitude)),
          zoom: zoom,
          bearing: bearing,
          pitch: tilt,
        ),
        mbx.MapAnimationOptions(duration: 800),
      );
    }
  }

  Future<void> moveCamera(gmap.LatLng target, {double zoom = 16, double bearing = 0, double tilt = 0}) async {
    if (_google != null) {
      await _google!.moveCamera(gmap.CameraUpdate.newCameraPosition(
        gmap.CameraPosition(target: target, zoom: zoom, bearing: bearing, tilt: tilt),
      ));
    } else if (_mapbox != null) {
      await _mapbox!.setCamera(mbx.CameraOptions(
        center: mbx.Point(coordinates: mbx.Position(target.longitude, target.latitude)),
        zoom: zoom,
        bearing: bearing,
        pitch: tilt,
      ));
    }
  }

  Future<void> fitBounds(gmap.LatLngBounds bounds, {double padding = 50}) async {
    if (_google != null) {
      await _google!.animateCamera(gmap.CameraUpdate.newLatLngBounds(bounds, padding));
    } else if (_mapbox != null) {
      final camera = await _mapbox!.cameraForCoordinateBounds(
        mbx.CoordinateBounds(
          southwest: mbx.Point(coordinates: mbx.Position(bounds.southwest.longitude, bounds.southwest.latitude)),
          northeast: mbx.Point(coordinates: mbx.Position(bounds.northeast.longitude, bounds.northeast.latitude)),
          infiniteBounds: false,
        ),
        mbx.MbxEdgeInsets(top: padding, left: padding, bottom: padding, right: padding),
        null,
        null,
        null,
        null,
      );
      await _mapbox!.flyTo(camera, mbx.MapAnimationOptions(duration: 600));
    }
  }
}

class VitoMap extends StatefulWidget {
  final gmap.LatLng initialTarget;
  final double initialZoom;
  final Set<VitoMarker> markers;
  final Set<gmap.Polyline> polylines;
  final bool myLocationEnabled;
  final bool zoomControlsEnabled;
  final bool compassEnabled;
  final VitoMapCreatedCallback? onMapCreated;
  final void Function(gmap.LatLng)? onTap;
  final void Function(gmap.CameraPosition)? onCameraMove;
  final EdgeInsets padding;
  final String? googleStyleJson;

  const VitoMap({
    super.key,
    required this.initialTarget,
    this.initialZoom = 14,
    this.markers = const {},
    this.polylines = const {},
    this.myLocationEnabled = false,
    this.zoomControlsEnabled = false,
    this.compassEnabled = false,
    this.onMapCreated,
    this.onTap,
    this.onCameraMove,
    this.padding = EdgeInsets.zero,
    this.googleStyleJson,
  });

  @override
  State<VitoMap> createState() => _VitoMapState();
}

class _VitoMapState extends State<VitoMap> {
  // Mapbox annotation managers (created once the style is ready).
  mbx.PointAnnotationManager? _pointManager;
  mbx.PolylineAnnotationManager? _lineManager;
  mbx.MapboxMap? _mapboxMap;

  late final bool _useMapbox = useMapboxProvider();

  @override
  Widget build(BuildContext context) {
    if (!_useMapbox) {
      return gmap.GoogleMap(
        style: widget.googleStyleJson,
        initialCameraPosition: gmap.CameraPosition(target: widget.initialTarget, zoom: widget.initialZoom),
        markers: widget.markers.map((m) => m.toGoogleMarker()).toSet(),
        polylines: widget.polylines,
        myLocationEnabled: widget.myLocationEnabled,
        myLocationButtonEnabled: false,
        zoomControlsEnabled: widget.zoomControlsEnabled,
        compassEnabled: widget.compassEnabled,
        padding: widget.padding,
        onTap: widget.onTap,
        onCameraMove: widget.onCameraMove,
        onMapCreated: (c) => widget.onMapCreated?.call(VitoMapController.google(c)),
      );
    }

    final token = _mapboxAccessToken();
    if (token.isEmpty) {
      return const Center(child: Text('Mapbox token not configured'));
    }
    mbx.MapboxOptions.setAccessToken(token);

    return mbx.MapWidget(
      cameraOptions: mbx.CameraOptions(
        center: mbx.Point(coordinates: mbx.Position(widget.initialTarget.longitude, widget.initialTarget.latitude)),
        zoom: widget.initialZoom,
      ),
      styleUri: Get.isDarkMode ? mbx.MapboxStyles.DARK : mbx.MapboxStyles.STANDARD,
      onMapCreated: _onMapboxCreated,
      onTapListener: widget.onTap == null
          ? null
          : (ctx) {
              final p = ctx.point;
              widget.onTap!(gmap.LatLng(p.coordinates.lat.toDouble(), p.coordinates.lng.toDouble()));
            },
    );
  }

  Future<void> _onMapboxCreated(mbx.MapboxMap map) async {
    _mapboxMap = map;
    await map.location.updateSettings(mbx.LocationComponentSettings(enabled: widget.myLocationEnabled));
    _pointManager = await map.annotations.createPointAnnotationManager();
    _lineManager = await map.annotations.createPolylineAnnotationManager();
    await _syncMapboxAnnotations();
    widget.onMapCreated?.call(VitoMapController.mapbox(map));
  }

  Future<void> _syncMapboxAnnotations() async {
    final pm = _pointManager;
    final lm = _lineManager;
    if (pm == null || lm == null) return;
    await pm.deleteAll();
    await lm.deleteAll();

    for (final m in widget.markers) {
      if (m.iconBytes == null) continue;
      await pm.create(mbx.PointAnnotationOptions(
        geometry: mbx.Point(coordinates: mbx.Position(m.position.longitude, m.position.latitude)),
        image: m.iconBytes,
        iconRotate: m.rotation,
      ));
    }

    for (final line in widget.polylines) {
      if (line.points.length < 2) continue;
      await lm.create(mbx.PolylineAnnotationOptions(
        geometry: mbx.LineString(
          coordinates: line.points.map((p) => mbx.Position(p.longitude, p.latitude)).toList(),
        ),
        lineColor: line.color.toARGB32(),
        lineWidth: line.width.toDouble(),
      ));
    }
  }

  @override
  void didUpdateWidget(covariant VitoMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_useMapbox && _mapboxMap != null &&
        (oldWidget.markers != widget.markers || oldWidget.polylines != widget.polylines)) {
      _syncMapboxAnnotations();
    }
  }
}
