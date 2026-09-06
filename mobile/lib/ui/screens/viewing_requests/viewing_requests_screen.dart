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
