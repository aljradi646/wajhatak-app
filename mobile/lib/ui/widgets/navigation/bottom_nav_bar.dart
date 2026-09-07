import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

/// عنصر وجهة تنقل سفلية — أيقونة + تسمية بحالة انتقائية ناعمة.
class WajhatakNavDestination {
  const WajhatakNavDestination({
    required this.icon,
    required this.selectedIcon,
    required this.label,
    this.badgeCount = 0,
  });

  final IconData icon;
  final IconData selectedIcon;
  final String label;
  final int badgeCount;
}

/// وجهة المستخدم مع صورته الشخصية.
class ProfileNavDestination extends WajhatakNavDestination {
  const ProfileNavDestination({
    required this.avatarUrl,
    required super.label,
  }) : super(icon: Icons.person_outline_rounded, selectedIcon: Icons.person_rounded);

  final String? avatarUrl;
}

/// شريط التنقل السفلي العصري — عائم بحواف منحنية ناعمة ومتجاوب.
class WajhatakBottomNavBar extends StatelessWidget {
  const WajhatakBottomNavBar({
    super.key,
    required this.selectedIndex,
    required this.onDestinationSelected,
    required this.destinations,
  });

  final int selectedIndex;
  final ValueChanged<int> onDestinationSelected;
  final List<WajhatakNavDestination> destinations;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final size = MediaQuery.sizeOf(context);
    final compact = size.width < 360;

    return Container(
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        border: Border(
          top: BorderSide(color: theme.colorScheme.outlineVariant),
        ),
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: compact ? 64 : 70,
          child: Row(
            textDirection: TextDirection.rtl,
            children: [
              for (var i = 0; i < destinations.length; i++)
                Expanded(
                  child: _NavItem(
                    destination: destinations[i],
                    selected: selectedIndex == i,
                    compact: compact,
                    onTap: () => onDestinationSelected(i),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.destination,
    required this.selected,
    required this.onTap,
    this.compact = false,
  });

  final WajhatakNavDestination destination;
  final bool selected;
  final bool compact;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final profile = destination is ProfileNavDestination
        ? destination as ProfileNavDestination
        : null;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                curve: Curves.easeOutCubic,
                padding: EdgeInsets.symmetric(
                  horizontal: compact ? 14 : 18,
                  vertical: 5,
                ),
                decoration: BoxDecoration(
                  color: selected
                      ? theme.colorScheme.primary.withValues(alpha: .13)
                      : Colors.transparent,
                  borderRadius: BorderRadius.circular(15),
                ),
                child: profile != null
                    ? _ProfileAvatar(
                        url: profile.avatarUrl,
                        selected: selected,
                        size: compact ? 21 : 23,
                      )
                    : Icon(
                        selected
                            ? destination.selectedIcon
                            : destination.icon,
                        size: compact ? 21 : 23,
                        color: selected
                            ? theme.colorScheme.primary
                            : theme.colorScheme.onSurfaceVariant,
                      ),
              ),
              if (destination.badgeCount > 0)
                Positioned(
                  top: -3,
                  left: compact ? 2 : 6,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 4.5,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.error,
                      borderRadius: BorderRadius.circular(99),
                      border: Border.all(
                        color: theme.colorScheme.surface,
                        width: 1.4,
                      ),
                    ),
                    child: Text(
                      destination.badgeCount > 99
                          ? '99+'
                          : '${destination.badgeCount}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 8,
                        fontWeight: FontWeight.w900,
                        height: 1,
                      ),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 3),
          AnimatedDefaultTextStyle(
            duration: const Duration(milliseconds: 180),
            style: theme.textTheme.labelSmall!.copyWith(
              fontSize: compact ? 10 : 11,
              fontWeight: selected ? FontWeight.w900 : FontWeight.w600,
              color: selected
                  ? theme.colorScheme.primary
                  : theme.colorScheme.onSurfaceVariant,
              height: 1,
            ),
            child: Text(destination.label),
          ),
        ],
      ),
    );
  }
}

class _ProfileAvatar extends StatelessWidget {
  const _ProfileAvatar({required this.url, required this.selected, this.size = 23});

  final String? url;
  final bool selected;
  final double size;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    if (url == null || url!.isEmpty) {
      return Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(
            color: selected
                ? theme.colorScheme.primary
                : theme.colorScheme.outline,
            width: selected ? 2 : 1.2,
          ),
        ),
        child: Icon(
          Icons.person_rounded,
          size: size * .62,
          color: selected
              ? theme.colorScheme.primary
              : theme.colorScheme.onSurfaceVariant,
        ),
      );
    }
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(
          color: selected
              ? theme.colorScheme.primary
              : theme.colorScheme.outline,
          width: selected ? 2 : 1.2,
        ),
      ),
      child: ClipOval(
        child: CachedNetworkImage(
          imageUrl: url!,
          fit: BoxFit.cover,
          width: size,
          height: size,
          errorWidget: (_, _, _) => Icon(
            Icons.person_rounded,
            size: size * .62,
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }
}
