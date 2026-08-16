import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:record/record.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';

class WhatsAppVoiceRecordBar extends StatefulWidget {
  final Function(String path) onSend;
  final VoidCallback onCancel;
  final bool isLocked;

  const WhatsAppVoiceRecordBar({
    super.key,
    required this.onSend,
    required this.onCancel,
    this.isLocked = false,
  });

  @override
  State<WhatsAppVoiceRecordBar> createState() => _WhatsAppVoiceRecordBarState();
}

class _WhatsAppVoiceRecordBarState extends State<WhatsAppVoiceRecordBar> with SingleTickerProviderStateMixin {
  final AudioRecorder _audioRecorder = AudioRecorder();
  Timer? _timer;
  int _seconds = 0;
  String? _recordedPath;
  bool _isPaused = false;
  late AnimationController _blinkController;

  @override
  void initState() {
    super.initState();
    _blinkController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    )..repeat(reverse: true);
    _startRecording();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _blinkController.dispose();
    _audioRecorder.dispose();
    super.dispose();
  }

  Future<void> _startRecording() async {
    try {
      if (await _audioRecorder.hasPermission()) {
        final dir = await getTemporaryDirectory();
        _recordedPath = '${dir.path}/vn_${DateTime.now().millisecondsSinceEpoch}.m4a';
        await _audioRecorder.start(
          const RecordConfig(encoder: AudioEncoder.aacLc),
          path: _recordedPath!,
        );
        _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
          if (mounted && !_isPaused) setState(() => _seconds++);
        });
      }
    } catch (e) {
      debugPrint("Voice recording start error: $e");
    }
  }

  Future<void> stopAndSend() async {
    _timer?.cancel();
    final path = await _audioRecorder.stop();
    if (path != null && File(path).existsSync()) {
      widget.onSend(path);
    }
  }

  Future<void> stopAndCancel() async {
    _timer?.cancel();
    final path = await _audioRecorder.stop();
    if (path != null && File(path).existsSync()) {
      try {
        File(path).deleteSync();
      } catch (_) {}
    }
    widget.onCancel();
  }

  String _formatTime(int sec) {
    final m = (sec ~/ 60).toString().padLeft(2, '0');
    final s = (sec % 60).toString().padLeft(2, '0');
    return '$m:$s';
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      height: 48,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1F2C34) : Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.06),
            blurRadius: 4,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Row(
        children: [
          // Flashing Red Recording Dot
          FadeTransition(
            opacity: _blinkController,
            child: Container(
              width: 10,
              height: 10,
              decoration: const BoxDecoration(
                color: Colors.redAccent,
                shape: BoxShape.circle,
              ),
            ),
          ),
          const SizedBox(width: 8),

          // Duration Timer
          Text(
            _formatTime(_seconds),
            style: textMedium.copyWith(
              fontSize: 14,
              color: isDark ? Colors.white : Colors.black87,
            ),
          ),

          const Spacer(),

          if (!widget.isLocked) ...[
            // Slide to Cancel
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.chevron_left_rounded, color: Colors.grey, size: 20),
                Text(
                  'Slide to cancel',
                  style: textRegular.copyWith(fontSize: 13, color: Colors.grey),
                ),
              ],
            ),
          ] else ...[
            // Locked Mode Actions: Delete Trash & Send Button
            IconButton(
              icon: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 22),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
              onPressed: stopAndCancel,
            ),
            const SizedBox(width: 16),
            IconButton(
              icon: Icon(
                _isPaused ? Icons.play_arrow_rounded : Icons.pause_rounded,
                color: const Color(0xFF4A148C),
                size: 24,
              ),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
              onPressed: () async {
                if (_isPaused) {
                  await _audioRecorder.resume();
                } else {
                  await _audioRecorder.pause();
                }
                setState(() => _isPaused = !_isPaused);
              },
            ),
            const SizedBox(width: 12),
            GestureDetector(
              onTap: stopAndSend,
              child: Container(
                width: 36,
                height: 36,
                decoration: const BoxDecoration(
                  color: Color(0xFF4A148C),
                  shape: BoxShape.circle,
                ),
                child: const Center(
                  child: Icon(Icons.send_rounded, color: Colors.white, size: 18),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
