import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../auth/auth_screen.dart';

class ViewingRequestsScreen extends ConsumerWidget {
  const ViewingRequestsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(sessionProvider);
    final appBar = (ModalRoute.of(context)?.canPop ?? false)
        ? WajhatakScreenHeader(
            title: 'طلبات المعاينة',
            subtitle: 'مواعيدك وقراراتك',
          )
        : null;

    if (session.isLoading) {
      return Scaffold(appBar: appBar, body: const ListSkeleton());
    }
    if (session.asData?.value == null) {
      return Scaffold(
        appBar: appBar,
        body: const AuthRequiredScreen(
          title: 'طلبات المعاينة',
          body: 'سجّل دخولك لمتابعة طلبات المعاينة.',
          actionLabel: 'تسجيل الدخول',
        ),
      );
    }
    final requests = ref.watch(viewingRequestsProvider);
    final canRespond =
        ref.watch(sessionProvider).asData?.value?.user.isAgent ?? false;
    final content = LuxAsyncView<List<ViewingRequestItem>>(
      value: requests,
      loading: const ViewingRequestsSkeleton(),
      errorRetry: () => ref.invalidate(viewingRequestsProvider),
      data: (data) {
        if (data.isEmpty) {
          return const EmptyState(
            title: 'لا توجد طلبات',
            body: 'ستظهر طلبات المعاينة الخاصة بك هنا.',
          );
        }
        return ListView.separated(
          padding: const EdgeInsets.all(20),
          itemCount: data.length,
          separatorBuilder: (context, index) => const SizedBox(height: 10),
          itemBuilder: (context, index) {
            final item = data[index];
            return _ViewingRequestCard(item: item, canRespond: canRespond);
          },
        );
      },
    );

    return Scaffold(
      appBar: appBar,
      body: appBar == null
          ? Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 6),
                  child: Text(
                    'طلباتك',
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
                Expanded(child: content),
              ],
            )
          : content,
    );
  }
}

class _ViewingRequestCard extends ConsumerStatefulWidget {
  const _ViewingRequestCard({required this.item, required this.canRespond});

  final ViewingRequestItem item;
  final bool canRespond;

  @override
  ConsumerState<_ViewingRequestCard> createState() =>
      _ViewingRequestCardState();
}

class _ViewingRequestCardState extends ConsumerState<_ViewingRequestCard> {
  String? _updatingStatus;

  bool get _isClient => !widget.canRespond;

  /// تعديل الموعد — متاح للعميل لطلبه المفتوح فقط (والخادم يتحقق أيضًا).
  Future<void> _reschedule() async {
    final item = widget.item;
    final picked = await showDatePicker(
      context: context,
      initialDate: item.date.isBefore(DateTime.now())
          ? DateTime.now()
          : item.date,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      locale: const Locale('ar'),
    );
    if (picked == null || !mounted) return;

    final time = await showTimePicker(
      context: context,
      initialTime: _parseTime(item.time),
    );
    if (time == null || !mounted) return;

    setState(() => _updatingStatus = 'reschedule');
    try {
      await ref
          .read(viewingRequestRepositoryProvider)
          .rescheduleViewingRequest(
            requestId: item.id,
            date: picked,
            time:
                '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}',
            notes: item.notes,
          );
      ref.invalidate(viewingRequestsProvider);
      if (mounted) util.notice(context, 'تم تحديث موعد المعاينة.');
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _updatingStatus = null);
    }
  }

  Future<void> _delete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('حذف طلب المعاينة؟'),
        content: const Text('سيتم حذف الطلب نهائيًا ولا يمكن التراجع.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('إلغاء'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('حذف'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _updatingStatus = 'delete');
    try {
      await ref
          .read(viewingRequestRepositoryProvider)
          .deleteViewingRequest(widget.item.id);
      ref.invalidate(viewingRequestsProvider);
      if (mounted) util.notice(context, 'تم حذف طلب المعاينة.');
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _updatingStatus = null);
    }
  }

  Future<void> _showHistory() async {
    try {
      final entries = await ref
          .read(viewingRequestRepositoryProvider)
          .history(widget.item.id);
      if (!mounted) return;
      await showModalBottomSheet<void>(
        context: context,
        showDragHandle: true,
        builder: (sheetContext) => _HistorySheet(entries: entries),
      );
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    }
  }

  static TimeOfDay _parseTime(String? value) {
    final parts = (value ?? '09:00').split(':');
    final hour = int.tryParse(parts.first) ?? 9;
    final minute = parts.length > 1 ? int.tryParse(parts[1]) ?? 0 : 0;
    return TimeOfDay(hour: hour, minute: minute);
  }

  Future<void> _respond(String status) async {
    setState(() => _updatingStatus = status);
    try {
      await ref
          .read(viewingRequestRepositoryProvider)
          .updateViewingRequest(requestId: widget.item.id, status: status);
      ref.invalidate(viewingRequestsProvider);
      if (mounted) {
        util.notice(
          context,
          status == 'confirmed'
              ? 'تم تأكيد الموعد وإشعار العميل.'
              : 'تم رفض الطلب وإشعار العميل.',
        );
      }
    } on ApiFailure catch (error) {
      if (mounted) {
        util.notice(context, error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _updatingStatus = null);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final item = widget.item;
    final pending = item.status == 'pending';
    final onSurfaceVariant = Theme.of(context).colorScheme.onSurfaceVariant;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    item.title,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
                _StatusChip(status: item.status),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              '${DateFormat.yMMMMd('ar').format(item.date)}${item.time == null ? '' : ' · ${item.time}'}',
              style: TextStyle(color: onSurfaceVariant),
            ),
            if (widget.canRespond && item.clientName?.isNotEmpty == true) ...[
              const SizedBox(height: 5),
              Row(
                children: [
                  UserAvatar(
                    avatarUrl: item.clientAvatarUrl,
                    name: item.clientName,
                    radius: 11,
                  ),
                  const SizedBox(width: 7),
                  Expanded(
                    child: Text(
                      'العميل: ${item.clientName}',
                      style: TextStyle(color: onSurfaceVariant, fontSize: 12),
                    ),
                  ),
                ],
              ),
            ],
            if (item.notes?.isNotEmpty == true) ...[
              const SizedBox(height: 5),
              Text(
                item.notes!,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(color: onSurfaceVariant, fontSize: 12),
              ),
            ],
            if (widget.canRespond && pending) ...[
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: _updatingStatus == null
                          ? () => _respond('rejected')
                          : null,
                      child: Text(
                        _updatingStatus == 'rejected' ? 'جارٍ الرفض…' : 'رفض',
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: FilledButton(
                      onPressed: _updatingStatus == null
                          ? () => _respond('confirmed')
                          : null,
                      child: Text(
                        _updatingStatus == 'confirmed'
                            ? 'جارٍ التأكيد…'
                            : 'تأكيد الموعد',
                      ),
                    ),
                  ),
                ],
              ),
            ],

            // إدارة العميل لطلبه: تعديل الموعد/الإلغاء/الحذف/سجل التغييرات.
            if (_isClient) ...[
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  if (item.status == 'pending' ||
                      item.status == 'confirmed') ...[
                    OutlinedButton.icon(
                      onPressed: _updatingStatus == null ? _reschedule : null,
                      icon: const Icon(Icons.edit_calendar_rounded, size: 18),
                      label: const Text('تعديل الموعد'),
                    ),
                    OutlinedButton.icon(
                      onPressed: _updatingStatus == null
                          ? () => _respond('cancelled')
                          : null,
                      icon: const Icon(Icons.cancel_outlined, size: 18),
                      label: const Text('إلغاء الطلب'),
                    ),
                  ],
                  if (item.status == 'pending' ||
                      item.status == 'rejected' ||
                      item.status == 'cancelled')
                    OutlinedButton.icon(
                      onPressed: _updatingStatus == null ? _delete : null,
                      icon: const Icon(Icons.delete_outline_rounded, size: 18),
                      label: const Text('حذف الطلب'),
                    ),
                  TextButton.icon(
                    onPressed: _showHistory,
                    icon: const Icon(Icons.history_rounded, size: 18),
                    label: const Text('سجل التغييرات'),
                  ),
                ],
              ),
            ] else ...[
              const SizedBox(height: 6),
              Align(
                alignment: AlignmentDirectional.centerStart,
                child: TextButton.icon(
                  onPressed: _showHistory,
                  icon: const Icon(Icons.history_rounded, size: 18),
                  label: const Text('سجل التغييرات'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// سجل تغييرات الطلب — من بيانات الخادم الحقيقية.
class _HistorySheet extends StatelessWidget {
  const _HistorySheet({required this.entries});

  final List<ViewingRequestHistoryEntry> entries;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 6, 20, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'سجل تغييرات الطلب',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 10),
            if (entries.isEmpty)
              Text('لا توجد تغييرات مسجلة.', style: theme.textTheme.bodySmall)
            else
              Flexible(
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: entries.length,
                  itemBuilder: (context, index) {
                    final entry = entries[index];
                    final details = [
                      if (entry.from != null || entry.to != null)
                        '${entry.from ?? '—'} ← ${entry.to ?? '—'}',
                      if (entry.by != null) 'بواسطة ${entry.by}',
                      if (entry.at != null)
                        DateFormat('yyyy-MM-dd HH:mm').format(entry.at!),
                    ].join(' • ');
                    return ListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.circle, size: 10),
                      title: Text(entry.label),
                      subtitle: details.isEmpty ? null : Text(details),
                    );
                  },
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});
  final String status;
  @override
  Widget build(BuildContext context) {
    final labels = {
      'pending': 'قيد المراجعة',
      'confirmed': 'مؤكد',
      'rejected': 'مرفوض',
      'cancelled': 'ملغى',
      'completed': 'مكتمل',
    };
    final color = status == 'confirmed' || status == 'completed'
        ? WajhatakColors.emerald
        : status == 'rejected' || status == 'cancelled'
        ? WajhatakColors.terracotta
        : WajhatakColors.amber;
    return Chip(
      label: Text(labels[status] ?? status),
      labelStyle: TextStyle(color: color, fontWeight: FontWeight.w800),
      side: BorderSide(color: color.withValues(alpha: .35)),
    );
  }
}
