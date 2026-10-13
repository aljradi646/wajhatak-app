import 'dart:async';
import 'dart:convert';

import 'package:web_socket_channel/web_socket_channel.dart';

import '../config/app_config.dart';
import '../../data/api_client.dart';

/// Authenticated, read-only realtime transport. Laravel remains the source of truth;
/// REST endpoints still load authoritative state after an event arrives.
class RealtimeService {
  RealtimeService(this._api);

  final LuxApiClient _api;
  WebSocketChannel? _channel;
  StreamSubscription<dynamic>? _subscription;
  Timer? _reconnectTimer;
  bool _stopped = true;
  int _reconnectAttempt = 0;

  void Function(String event, Map<String, dynamic> data)? onEvent;

  bool get isConfigured => AppConfig.realtimeWebSocketUrl.isNotEmpty;
  bool get isConnected => _channel != null && !_stopped;

  Future<void> start() async {
    if (_stopped == false || !isConfigured) return;
    _stopped = false;
    await _connect();
  }

  Future<void> stop() async {
    _stopped = true;
    _reconnectTimer?.cancel();
    _reconnectTimer = null;
    await _subscription?.cancel();
    _subscription = null;
    final channel = _channel;
    _channel = null;
    if (channel != null) {
      await channel.sink.close();
    }
  }

  Future<void> _connect() async {
    if (_stopped || !isConfigured) return;
    WebSocketChannel? candidate;
    try {
      final response = await _api.post('/realtime/ticket');
      final data = response['data'];
      final ticket = data is Map<String, dynamic>
          ? data['ticket']?.toString()
          : null;
      if (ticket == null || ticket.isEmpty) {
        _scheduleReconnect();
        return;
      }

      final baseUri = Uri.parse(AppConfig.realtimeWebSocketUrl);
      final query = Map<String, String>.from(baseUri.queryParameters)
        ..['ticket'] = ticket;
      candidate = WebSocketChannel.connect(
        baseUri.replace(queryParameters: query),
      );
      await candidate.ready.timeout(const Duration(seconds: 8));
      if (_stopped) {
        await candidate.sink.close();
        return;
      }

      _channel = candidate;
      _reconnectAttempt = 0;
      _subscription = candidate.stream.listen(
        _handleFrame,
        onError: (Object _) => _scheduleReconnect(),
        onDone: _scheduleReconnect,
        cancelOnError: true,
      );
    } on Object {
      if (candidate != null) {
        await candidate.sink.close();
      }
      _scheduleReconnect();
    }
  }

  void _handleFrame(dynamic frame) {
    if (frame is! String) return;
    try {
      final decoded = jsonDecode(frame);
      if (decoded is! Map<String, dynamic>) return;
      final event = decoded['event']?.toString();
      final data = decoded['data'];
      if (event == null || data is! Map<String, dynamic>) return;
      onEvent?.call(event, data);
    } on Object {
      // Ignore malformed packets; REST remains authoritative.
    }
  }

  void _scheduleReconnect() {
    _channel = null;
    if (_stopped || _reconnectTimer != null || !isConfigured) return;
    unawaited(_subscription?.cancel());
    _subscription = null;
    final seconds = _reconnectAttempt < 5 ? (1 << _reconnectAttempt) : 30;
    _reconnectAttempt++;
    _reconnectTimer = Timer(Duration(seconds: seconds), () {
      _reconnectTimer = null;
      unawaited(_connect());
    });
  }
}
