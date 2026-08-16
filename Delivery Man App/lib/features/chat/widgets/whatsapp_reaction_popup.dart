import 'package:flutter/material.dart';

class WhatsAppReactionPopup extends StatelessWidget {
  final Function(String emoji) onReactionSelected;
  final VoidCallback onDismiss;

  const WhatsAppReactionPopup({
    super.key,
    required this.onReactionSelected,
    required this.onDismiss,
  });

  static const List<String> _reactions = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

  static void show({
    required BuildContext context,
    required Rect targetRect,
    required Function(String emoji) onReactionSelected,
  }) {
    final overlay = Overlay.of(context);
    late OverlayEntry overlayEntry;

    overlayEntry = OverlayEntry(
      builder: (context) => Stack(
        children: [
          Positioned.fill(
            child: GestureDetector(
              behavior: HitTestBehavior.translucent,
              onTap: () => overlayEntry.remove(),
              child: Container(color: Colors.transparent),
            ),
          ),
          Positioned(
            left: (targetRect.left - 10).clamp(16.0, MediaQuery.of(context).size.width - 290),
            top: (targetRect.top - 55).clamp(60.0, MediaQuery.of(context).size.height - 100),
            child: Material(
              color: Colors.transparent,
              child: WhatsAppReactionPopup(
                onReactionSelected: (emoji) {
                  overlayEntry.remove();
                  onReactionSelected(emoji);
                },
                onDismiss: () => overlayEntry.remove(),
              ),
            ),
          ),
        ],
      ),
    );

    overlay.insert(overlayEntry);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1F2C34) : Colors.white,
        borderRadius: BorderRadius.circular(30),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.4 : 0.15),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: _reactions.map((emoji) {
          return TweenAnimationBuilder<double>(
            tween: Tween(begin: 0.0, end: 1.0),
            duration: const Duration(milliseconds: 200),
            builder: (context, scale, child) {
              return Transform.scale(
                scale: scale,
                child: child,
              );
            },
            child: GestureDetector(
              onTap: () => onReactionSelected(emoji),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                child: Text(
                  emoji,
                  style: const TextStyle(fontSize: 24),
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}
