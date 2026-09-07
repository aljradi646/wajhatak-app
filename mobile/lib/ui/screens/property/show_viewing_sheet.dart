import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../state/providers.dart';
import '../auth/auth_screen.dart';

/// نافذة طلب معاينة عقار — تصميم عصري بأيقونات ملونة ومنع إرسال مزدوج.
Future<void> showViewingSheet(
  BuildContext context,
  WidgetRef ref,
  int propertyId,
) async {
  if (ref.read(sessionProvider).asData?.value == null) {
    await Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const AuthScreen()));
    return;
  }
  var selectedDate = DateTime.now().add(const Duration(days: 1));
  var selectedTime = const TimeOfDay(hour: 17, minute: 0);
  var submitting = false;
  final notes = TextEditingController();
  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    builder: (sheetContext) => StatefulBuilder(
      builder: (_, setModalState) => Padding(
        padding: EdgeInsets.fromLTRB(
          20,
          12,
          20,
          MediaQuery.viewInsetsOf(sheetContext).bottom + 22,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // رأس النافذة
            Row(
              children: [
                const GradientIconBadge(
                  icon: Icons.event_available_rounded,
                  size: 46,
                  color: WajhatakColors.orange,
                ),
                const SizedBox(width: 13),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'طلب معاينة عقار',
                        style: Theme.of(sheetContext).textTheme.titleLarge
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'اختر الموعد المناسب وسيصل طلبك للوكيل فورًا',
                        style: Theme.of(sheetContext).textTheme.bodySmall
                            ?.copyWith(
                              color: Theme.of(
                                sheetContext,
                              ).colorScheme.onSurfaceVariant,
                            ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            // اختيار التاريخ
            _PickerTile(
              icon: Icons.calendar_month_rounded,
              tone: AccentTone.emerald,
              label: 'التاريخ',
              value: DateFormat.yMMMMd('ar').format(selectedDate),
              onTap: () async {
                final date = await showDatePicker(
                  context: sheetContext,
                  firstDate: DateTime.now(),
                  lastDate: DateTime.now().add(const Duration(days: 120)),
                  initialDate: selectedDate,
                  locale: const Locale('ar'),
                );
                if (date != null) setModalState(() => selectedDate = date);
              },
            ),
            const SizedBox(height: 10),
            // اختيار الوقت
            _PickerTile(
              icon: Icons.schedule_rounded,
              tone: AccentTone.sky,
              label: 'الوقت',
              value: selectedTime.format(sheetContext),
              onTap: () async {
                final time = await showTimePicker(
                  context: sheetContext,
                  initialTime: selectedTime,
                );
                if (time != null) setModalState(() => selectedTime = time);
              },
            ),
            const SizedBox(height: 14),
            TextField(
              controller: notes,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'ملاحظات للوكيل (اختياري)',
                alignLabelWithHint: true,
                prefixIcon: Icon(Icons.sticky_note_2_outlined),
              ),
            ),
            const SizedBox(height: 18),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: submitting
                    ? null
                    : () async {
                        setModalState(() => submitting = true);
                        try {
                          await ref
                              .read(viewingRequestRepositoryProvider)
                              .requestViewing(
                                propertyId: propertyId,
                                date: selectedDate,
                                time:
                                    '${selectedTime.hour.toString().padLeft(2, '0')}:${selectedTime.minute.toString().padLeft(2, '0')}',
                                notes: notes.text,
                              );
                          ref.invalidate(viewingRequestsProvider);
                          if (sheetContext.mounted) {
                            Navigator.pop(sheetContext);
                          }
                          if (context.mounted) {
                            util.notice(
                              context,
                              'تم إرسال طلب المعاينة للوكيل.',
                            );
                          }
                        } on ApiFailure catch (error) {
                          setModalState(() => submitting = false);
                          if (sheetContext.mounted) {
                            util.notice(sheetContext, error.message);
                          }
                        }
                      },
                icon: submitting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.send_rounded, size: 19),
                label: Text(
                  submitting ? 'جارٍ الإرسال…' : 'إرسال الطلب',
                ),
              ),
            ),
          ],
        ),
      ),
    ),
  );
  notes.dispose();
}

class _PickerTile extends StatelessWidget {
  const _PickerTile({
    required this.icon,
    required this.tone,
    required this.label,
    required this.value,
    required this.onTap,
  });

  final IconData icon;
  final AccentTone tone;
  final String label;
  final String value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Material(
      color: theme.colorScheme.surfaceContainerHigh,
      borderRadius: BorderRadius.circular(17),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(17),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 11),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(17),
            border: Border.all(color: theme.colorScheme.outlineVariant),
          ),
          child: Row(
            children: [
              TintedIcon(icon: icon, tone: tone, size: 40, iconSize: 19),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      label,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      value,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.expand_more_rounded,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
