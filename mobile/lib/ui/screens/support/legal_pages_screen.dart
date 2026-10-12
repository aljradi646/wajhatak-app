import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../state/providers.dart';
import '../../widgets.dart';
import 'support_tickets_screen.dart';

class LegalPagesScreen extends ConsumerStatefulWidget {
  const LegalPagesScreen({super.key});
  @override
  ConsumerState<LegalPagesScreen> createState() => _LegalPagesScreenState();
}

class _LegalPagesScreenState extends ConsumerState<LegalPagesScreen> {
  late Future<List<Map<String, dynamic>>> _future;
  final _search = TextEditingController();
  String _query = '';
  String _category = 'all';

  @override
  void initState() {
    super.initState();
    _future = _load();
    _search.addListener(() {
      if (mounted) setState(() => _query = _search.text.trim().toLowerCase());
    });
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<List<Map<String, dynamic>>> _load() async {
    final response = await ref.read(apiClientProvider).get('/pages');
    return (response['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .where((page) => (page['title'] as String? ?? '').trim().isNotEmpty)
        .toList();
  }

  String _kind(Map<String, dynamic> page) {
    final value = (page['category'] as String? ?? '').toLowerCase();
    if (value == 'legal') return 'legal';
    if (value == 'help') return 'help';
    return 'info';
  }

  IconData _icon(String slug) {
    if (slug.contains('privacy')) return Icons.shield_outlined;
    if (slug.contains('terms') || slug.contains('usage')) return Icons.gavel_rounded;
    if (slug.contains('refund')) return Icons.receipt_long_outlined;
    if (slug.contains('cookie')) return Icons.cookie_outlined;
    if (slug.contains('faq')) return Icons.quiz_outlined;
    if (slug.contains('contact')) return Icons.support_agent_rounded;
    if (slug.contains('how-to')) return Icons.menu_book_outlined;
    if (slug.contains('about')) return Icons.info_outline_rounded;
    return Icons.article_outlined;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colors = theme.colorScheme;
    return Scaffold(
      appBar: const WajhatakScreenHeader(title: 'السياسات والمعلومات'),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) return const ListSkeleton(trailing: true);
          if (snapshot.hasError) return _RetryView(onRetry: () => setState(() => _future = _load()));
          final pages = snapshot.data ?? const <Map<String, dynamic>>[];
          final filtered = pages.where((page) {
            final kind = _kind(page);
            final categoryMatches = _category == 'all' || kind == _category;
            final haystack = [page['title'], page['summary'], page['content']]
                .whereType<String>().join(' ').toLowerCase();
            return categoryMatches && (_query.isEmpty || haystack.contains(_query));
          }).toList();

          return RefreshIndicator(
            onRefresh: () async {
              final next = _load();
              setState(() => _future = next);
              await next;
            },
            child: ListView(
              keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
              padding: const EdgeInsets.fromLTRB(18, 12, 18, 32),
              children: [
                Container(
                  padding: const EdgeInsets.all(21),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topRight,
                      end: Alignment.bottomLeft,
                      colors: [colors.primary, Color.lerp(colors.primary, colors.tertiary, .42)!],
                    ),
                    borderRadius: BorderRadius.circular(25),
                  ),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Container(
                      width: 48, height: 48,
                      decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(15)),
                      child: const Icon(Icons.menu_book_rounded, color: Colors.white, size: 26),
                    ),
                    const SizedBox(height: 17),
                    Text('كل ما تحتاج معرفته', style: theme.textTheme.headlineSmall?.copyWith(
                      color: Colors.white, fontWeight: FontWeight.w900, height: 1.35)),
                    const SizedBox(height: 8),
                    Text('السياسات والخصوصية وإرشادات الاستخدام والإجابات التي تساعدك على استخدام وجهتك بثقة.',
                      style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white.withValues(alpha: .92), height: 1.75)),
                    const SizedBox(height: 15),
                    const Row(children: [
                      Icon(Icons.verified_user_outlined, color: Colors.white, size: 18),
                      SizedBox(width: 8),
                      Text('معلومات منشورة من منصة وجهتك', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
                    ]),
                  ]),
                ),
                const SizedBox(height: 19),
                TextField(
                  controller: _search,
                  textInputAction: TextInputAction.search,
                  decoration: InputDecoration(
                    hintText: 'ابحث في السياسات والأسئلة...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: _query.isEmpty ? null : IconButton(onPressed: _search.clear, tooltip: 'مسح البحث', icon: const Icon(Icons.close_rounded)),
                    filled: true,
                    fillColor: colors.surfaceContainerHighest.withValues(alpha: .55),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
                  ),
                ),
                const SizedBox(height: 13),
                SingleChildScrollView(scrollDirection: Axis.horizontal, child: Row(children: [
                  _FilterChip(label: 'الكل', icon: Icons.grid_view_rounded, selected: _category == 'all', onTap: () => setState(() => _category = 'all')),
                  const SizedBox(width: 8),
                  _FilterChip(label: 'السياسات', icon: Icons.shield_outlined, selected: _category == 'legal', onTap: () => setState(() => _category = 'legal')),
                  const SizedBox(width: 8),
                  _FilterChip(label: 'المساعدة', icon: Icons.help_outline_rounded, selected: _category == 'help', onTap: () => setState(() => _category = 'help')),
                  const SizedBox(width: 8),
                  _FilterChip(label: 'معلومات عامة', icon: Icons.info_outline_rounded, selected: _category == 'info', onTap: () => setState(() => _category = 'info')),
                ])),
                const SizedBox(height: 20),
                Row(children: [
                  Expanded(child: Text(_query.isEmpty && _category == 'all' ? 'استكشف مركز المعلومات' : 'نتائج البحث',
                    style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900))),
                  Text(filtered.length.toString() + ' صفحة', style: theme.textTheme.labelMedium?.copyWith(color: colors.onSurfaceVariant)),
                ]),
                const SizedBox(height: 11),
                if (filtered.isEmpty)
                  _NoResults(onClear: () { _search.clear(); setState(() => _category = 'all'); })
                else
                  ...filtered.map((page) {
                    final kind = _kind(page);
                    final tint = kind == 'legal' ? colors.primary : kind == 'help' ? colors.tertiary : colors.secondary;
                    final summary = (page['summary'] as String? ?? '').trim();
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Material(
                        color: colors.surface,
                        borderRadius: BorderRadius.circular(19),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(19),
                          onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(
                            builder: (_) => LegalPageDetailScreen(slug: page['slug'] as String? ?? '', title: page['title'] as String? ?? 'المعلومات'))),
                          child: Container(
                            padding: const EdgeInsets.all(15),
                            decoration: BoxDecoration(borderRadius: BorderRadius.circular(19), border: Border.all(color: colors.outlineVariant.withValues(alpha: .8))),
                            child: Row(children: [
                              Container(width: 48, height: 48,
                                decoration: BoxDecoration(color: tint.withValues(alpha: .10), borderRadius: BorderRadius.circular(15)),
                                child: Icon(_icon((page['slug'] as String? ?? '').toLowerCase()), color: tint, size: 24)),
                              const SizedBox(width: 13),
                              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Text(page['title'] as String? ?? '', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                                if (summary.isNotEmpty) ...[
                                  const SizedBox(height: 4),
                                  Text(summary, maxLines: 2, overflow: TextOverflow.ellipsis,
                                    style: theme.textTheme.bodySmall?.copyWith(color: colors.onSurfaceVariant, height: 1.55)),
                                ],
                                const SizedBox(height: 8),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                                  decoration: BoxDecoration(color: tint.withValues(alpha: .10), borderRadius: BorderRadius.circular(7)),
                                  child: Text(kind == 'legal' ? 'سياسة' : kind == 'help' ? 'مساعدة' : 'معلومات',
                                    style: theme.textTheme.labelSmall?.copyWith(color: tint, fontWeight: FontWeight.w800)),
                                ),
                              ])),
                              const SizedBox(width: 8),
                              Icon(Icons.arrow_back_ios_new_rounded, size: 16, color: colors.onSurfaceVariant),
                            ]),
                          ),
                        ),
                      ),
                    );
                  }),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(17),
                  decoration: BoxDecoration(color: colors.surfaceContainerHighest.withValues(alpha: .55), borderRadius: BorderRadius.circular(20)),
                  child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Icon(Icons.support_agent_rounded, color: colors.primary, size: 27),
                    const SizedBox(width: 12),
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text('لم تجد الإجابة؟', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
                      const SizedBox(height: 4),
                      Text('تواصل مع فريق الدعم أو أنشئ بلاغًا لمتابعة الرد من داخل التطبيق.',
                        style: theme.textTheme.bodySmall?.copyWith(color: colors.onSurfaceVariant, height: 1.6)),
                      const SizedBox(height: 12),
                      Align(alignment: AlignmentDirectional.centerStart,
                        child: FilledButton.tonalIcon(
                          onPressed: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => const SupportTicketsScreen())),
                          icon: const Icon(Icons.forum_outlined, size: 18), label: const Text('الدعم والبلاغات'))),
                    ])),
                  ]),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class LegalPageDetailScreen extends ConsumerStatefulWidget {
  const LegalPageDetailScreen({super.key, required this.slug, required this.title});
  final String slug;
  final String title;
  @override
  ConsumerState<LegalPageDetailScreen> createState() => _LegalPageDetailScreenState();
}

class _LegalPageDetailScreenState extends ConsumerState<LegalPageDetailScreen> {
  late Future<Map<String, dynamic>> _future;
  @override
  void initState() { super.initState(); _future = _load(); }

  Future<Map<String, dynamic>> _load() async {
    final response = await ref.read(apiClientProvider).get('/pages/' + widget.slug);
    return response['data'] as Map<String, dynamic>;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colors = theme.colorScheme;
    return Scaffold(
      appBar: WajhatakScreenHeader(title: widget.title),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) return const ListSkeleton(trailing: true);
          if (snapshot.hasError || !snapshot.hasData) return _RetryView(onRetry: () => setState(() => _future = _load()));
          final page = snapshot.data!;
          final title = (page['title'] as String? ?? widget.title).trim();
          final summary = (page['summary'] as String? ?? '').trim();
          final content = (page['content'] as String? ?? '').trim();
          final updated = (page['updated_at'] as String? ?? '').trim();
          return ListView(padding: const EdgeInsets.fromLTRB(18, 12, 18, 32), children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: colors.primaryContainer.withValues(alpha: .65), borderRadius: BorderRadius.circular(23)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Container(width: 44, height: 44, decoration: BoxDecoration(color: colors.surface, borderRadius: BorderRadius.circular(14)),
                  child: Icon(_icon(widget.slug), color: colors.primary, size: 23)),
                const SizedBox(height: 15),
                Text(title, style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900, height: 1.35)),
                if (summary.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(summary, style: theme.textTheme.bodyMedium?.copyWith(color: colors.onSurfaceVariant, height: 1.75)),
                ],
                if (updated.isNotEmpty) ...[
                  const SizedBox(height: 14),
                  Row(children: [
                    Icon(Icons.update_rounded, size: 16, color: colors.onSurfaceVariant),
                    const SizedBox(width: 6),
                    Text('آخر تحديث: ' + (updated.length >= 10 ? updated.substring(0, 10) : updated),
                      style: theme.textTheme.labelSmall?.copyWith(color: colors.onSurfaceVariant)),
                  ]),
                ],
              ]),
            ),
            const SizedBox(height: 17),
            if (content.isEmpty)
              const EmptyState(title: 'المحتوى غير متاح حاليًا', body: 'لم يتم نشر نص لهذه الصفحة بعد. يمكنك التواصل مع الدعم للمساعدة.', icon: Icons.article_outlined)
            else
              Container(
                padding: const EdgeInsets.fromLTRB(18, 18, 18, 18),
                decoration: BoxDecoration(color: colors.surface, borderRadius: BorderRadius.circular(21), border: Border.all(color: colors.outlineVariant)),
                child: _PageContent(content: content)),
            if (widget.slug == 'contact') ...[
              const SizedBox(height: 14),
              if ((page['contact_email'] as String? ?? '').trim().isNotEmpty)
                _ContactInfo(icon: Icons.email_outlined, title: 'البريد الإلكتروني', value: page['contact_email'] as String),
              if ((page['contact_phone'] as String? ?? '').trim().isNotEmpty)
                _ContactInfo(icon: Icons.phone_outlined, title: 'رقم التواصل', value: page['contact_phone'] as String),
            ],
            const SizedBox(height: 15),
            OutlinedButton.icon(
              onPressed: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => const SupportTicketsScreen())),
              icon: const Icon(Icons.chat_bubble_outline_rounded),
              label: const Text('تحتاج إلى مساعدة؟ تواصل مع الدعم')),
          ]);
        },
      ),
    );
  }

  IconData _icon(String slug) {
    if (slug.contains('privacy')) return Icons.shield_outlined;
    if (slug.contains('terms') || slug.contains('usage')) return Icons.gavel_rounded;
    if (slug.contains('refund')) return Icons.receipt_long_outlined;
    if (slug.contains('cookie')) return Icons.cookie_outlined;
    if (slug.contains('faq')) return Icons.quiz_outlined;
    if (slug.contains('contact')) return Icons.support_agent_rounded;
    if (slug.contains('how-to')) return Icons.menu_book_outlined;
    if (slug.contains('about')) return Icons.info_outline_rounded;
    return Icons.article_outlined;
  }
}

class _PageContent extends StatelessWidget {
  const _PageContent({required this.content});
  final String content;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colors = theme.colorScheme;
    final children = <Widget>[];
    for (final raw in content.split('\n')) {
      final line = raw.trim();
      if (line.isEmpty) {
        if (children.isNotEmpty && children.last is! SizedBox) children.add(const SizedBox(height: 8));
      } else if (line.startsWith('# ')) {
        children.add(Padding(padding: const EdgeInsets.only(top: 14, bottom: 6),
          child: Text(line.substring(2), style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900))));
      } else if (line.startsWith('## ')) {
        children.add(Padding(padding: const EdgeInsets.only(top: 12, bottom: 5),
          child: Text(line.substring(3), style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900))));
      } else if (line.startsWith('### ')) {
        children.add(Padding(padding: const EdgeInsets.only(top: 10, bottom: 5),
          child: Text(line.substring(4), style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900))));
      } else if (line.startsWith('- ') || line.startsWith('• ')) {
        children.add(Padding(padding: const EdgeInsets.only(bottom: 7), child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Padding(padding: const EdgeInsets.only(top: 9), child: Icon(Icons.circle, size: 6, color: colors.primary)),
          const SizedBox(width: 10),
          Expanded(child: Text(line.substring(2), style: theme.textTheme.bodyMedium?.copyWith(height: 1.85))),
        ])));
      } else {
        children.add(Padding(padding: const EdgeInsets.only(bottom: 9),
          child: Text(line, style: theme.textTheme.bodyMedium?.copyWith(height: 1.9))));
      }
    }
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: children);
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({required this.label, required this.icon, required this.selected, required this.onTap});
  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) => FilterChip(
    selected: selected, onSelected: (_) => onTap(), showCheckmark: false,
    avatar: Icon(icon, size: 17), label: Text(label),
    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 7));
}

class _ContactInfo extends StatelessWidget {
  const _ContactInfo({required this.icon, required this.title, required this.value});
  final IconData icon;
  final String title;
  final String value;
  @override
  Widget build(BuildContext context) => Card(elevation: 0, child: ListTile(
    leading: Icon(icon, color: Theme.of(context).colorScheme.primary),
    title: Text(title), subtitle: SelectableText(value)));
}

class _NoResults extends StatelessWidget {
  const _NoResults({required this.onClear});
  final VoidCallback onClear;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 28),
    child: Column(children: [
      Icon(Icons.search_off_rounded, size: 42, color: Theme.of(context).colorScheme.onSurfaceVariant),
      const SizedBox(height: 12), const Text('لا توجد نتائج مطابقة'),
      const SizedBox(height: 5), Text('جرّب كلمة أخرى أو غيّر تصنيف الصفحات.', style: Theme.of(context).textTheme.bodySmall),
      const SizedBox(height: 12), TextButton(onPressed: onClear, child: const Text('عرض جميع الصفحات')),
    ]));
}

class _RetryView extends StatelessWidget {
  const _RetryView({required this.onRetry});
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => Center(child: Padding(
    padding: const EdgeInsets.all(24),
    child: Column(mainAxisSize: MainAxisSize.min, children: [
      Icon(Icons.cloud_off_rounded, size: 44, color: Theme.of(context).colorScheme.onSurfaceVariant),
      const SizedBox(height: 12), const Text('تعذر تحميل المعلومات'),
      const SizedBox(height: 6), Text('تحقق من اتصال الإنترنت ثم حاول مرة أخرى.',
        textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodyMedium),
      const SizedBox(height: 14),
      FilledButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh_rounded), label: const Text('إعادة المحاولة')),
    ])));
}
