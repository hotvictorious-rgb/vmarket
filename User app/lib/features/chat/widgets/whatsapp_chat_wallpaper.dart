import 'package:flutter/material.dart';

class WhatsAppChatWallpaper extends StatelessWidget {
  final Widget child;
  final bool isDark;

  const WhatsAppChatWallpaper({
    super.key,
    required this.child,
    this.isDark = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      color: isDark ? const Color(0xFF121B22) : const Color(0xFFEFEAE2),
      child: Stack(
        children: [
          Positioned.fill(
            child: CustomPaint(
              painter: _DoodlePatternPainter(isDark: isDark),
            ),
          ),
          child,
        ],
      ),
    );
  }
}

class _DoodlePatternPainter extends CustomPainter {
  final bool isDark;
  _DoodlePatternPainter({required this.isDark});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = (isDark ? Colors.white : Colors.black).withValues(alpha: isDark ? 0.03 : 0.04)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.2;

    const double step = 64.0;
    for (double x = 16; x < size.width; x += step) {
      for (double y = 16; y < size.height; y += step) {
        final int iconType = ((x / step).toInt() + (y / step).toInt()) % 4;
        if (iconType == 0) {
          // Shopping bag icon
          canvas.drawRRect(
            RRect.fromRectAndRadius(Rect.fromLTWH(x, y, 16, 18), const Radius.circular(3)),
            paint,
          );
          canvas.drawArc(Rect.fromLTWH(x + 3, y - 4, 10, 8), 3.14, 3.14, false, paint);
        } else if (iconType == 1) {
          // Chat bubble doodle
          canvas.drawRRect(
            RRect.fromRectAndRadius(Rect.fromLTWH(x, y, 18, 14), const Radius.circular(4)),
            paint,
          );
        } else if (iconType == 2) {
          // Gift box doodle
          canvas.drawRect(Rect.fromLTWH(x, y + 3, 16, 14), paint);
          canvas.drawLine(Offset(x + 8, y + 3), Offset(x + 8, y + 17), paint);
        } else {
          // Star / Sparkle doodle
          canvas.drawLine(Offset(x + 8, y), Offset(x + 8, y + 16), paint);
          canvas.drawLine(Offset(x, y + 8), Offset(x + 16, y + 8), paint);
        }
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
