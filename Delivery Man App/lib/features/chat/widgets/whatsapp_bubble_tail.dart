import 'package:flutter/material.dart';

class WhatsAppBubbleTail extends CustomPainter {
  final Color color;
  final bool isMe;

  WhatsAppBubbleTail({required this.color, required this.isMe});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.fill;

    final path = Path();
    if (isMe) {
      path.moveTo(0, 0);
      path.lineTo(size.width, 0);
      path.quadraticBezierTo(0, size.height * 0.5, 0, size.height);
      path.close();
    } else {
      path.moveTo(size.width, 0);
      path.lineTo(0, 0);
      path.quadraticBezierTo(size.width, size.height * 0.5, size.width, size.height);
      path.close();
    }
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
