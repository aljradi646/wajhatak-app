import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppSettings {
  const AppSettings({
    this.messageNotifications = true,
    this.viewingNotifications = true,
    this.propertyUpdates = true,
    this.searchHistory = const [],
    this.lastTransactionType,
  });

  final bool messageNotifications;
  final bool viewingNotifications;
  final bool propertyUpdates;
  final List<String> searchHistory;
  final String? lastTransactionType;

  AppSettings copyWith({
    bool? messageNotifications,
    bool? viewingNotifications,
    bool? propertyUpdates,
    List<String>? searchHistory,
    String? Function()? lastTransactionType,
  }) => AppSettings(
    messageNotifications: messageNotifications ?? this.messageNotifications,
    viewingNotifications: viewingNotifications ?? this.viewingNotifications,
    propertyUpdates: propertyUpdates ?? this.propertyUpdates,
    searchHistory: searchHistory ?? this.searchHistory,
    lastTransactionType: lastTransactionType != null
        ? lastTransactionType()
        : this.lastTransactionType,
  );
}

final appSettingsProvider =
    NotifierProvider<AppSettingsController, AppSettings>(
      AppSettingsController.new,
    );

class AppSettingsController extends Notifier<AppSettings> {
  static const _messagesKey = 'lux_notify_messages';
  static const _viewingsKey = 'lux_notify_viewings';
  static const _updatesKey = 'lux_notify_property_updates';
  static const _searchHistoryKey = 'lux_search_history';
  static const _lastTransactionKey = 'lux_last_transaction_type';
  static const _maxSearchHistory = 10;
  final SharedPreferencesAsync _preferences = SharedPreferencesAsync();

  @override
  AppSettings build() {
    _restore();
    return const AppSettings();
  }

  Future<void> _restore() async {
    final messages = await _preferences.getBool(_messagesKey);
    final viewings = await _preferences.getBool(_viewingsKey);
    final updates = await _preferences.getBool(_updatesKey);
    final historyJson = await _preferences.getString(_searchHistoryKey);
    final lastTx = await _preferences.getString(_lastTransactionKey);
    List<String> history = const [];
    if (historyJson != null && historyJson.isNotEmpty) {
      try {
        history = List<String>.from(jsonDecode(historyJson) as List);
      } catch (_) {}
    }
    state = AppSettings(
      messageNotifications: messages ?? true,
      viewingNotifications: viewings ?? true,
      propertyUpdates: updates ?? true,
      searchHistory: history,
      lastTransactionType: lastTx,
    );
  }

  Future<void> setMessageNotifications(bool enabled) => _update(
    state.copyWith(messageNotifications: enabled),
    _messagesKey,
    enabled,
  );

  Future<void> setViewingNotifications(bool enabled) => _update(
    state.copyWith(viewingNotifications: enabled),
    _viewingsKey,
    enabled,
  );

  Future<void> setPropertyUpdates(bool enabled) =>
      _update(state.copyWith(propertyUpdates: enabled), _updatesKey, enabled);

  Future<void> addSearchTerm(String term) async {
    if (term.trim().isEmpty) return;
    final updated = [
      term.trim(),
      ...state.searchHistory.where((t) => t != term.trim()),
    ].take(_maxSearchHistory).toList();
    state = state.copyWith(searchHistory: updated);
    await _preferences.setString(_searchHistoryKey, jsonEncode(updated));
  }

  Future<void> removeSearchTerm(String term) async {
    final updated = state.searchHistory.where((t) => t != term).toList();
    state = state.copyWith(searchHistory: updated);
    await _preferences.setString(_searchHistoryKey, jsonEncode(updated));
  }

  Future<void> clearSearchHistory() async {
    state = state.copyWith(searchHistory: const []);
    await _preferences.remove(_searchHistoryKey);
  }

  Future<void> setLastTransactionType(String? type) async {
    state = state.copyWith(lastTransactionType: () => type);
    if (type == null) {
      await _preferences.remove(_lastTransactionKey);
    } else {
      await _preferences.setString(_lastTransactionKey, type);
    }
  }

  Future<void> _update(AppSettings next, String key, bool value) async {
    state = next;
    await _preferences.setBool(key, value);
  }
}
