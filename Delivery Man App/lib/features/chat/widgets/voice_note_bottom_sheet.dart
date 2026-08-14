import 'dart:async';
import 'dart:io';
import 'package:audioplayers/audioplayers.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:path_provider/path_provider.dart';
import 'package:record/record.dart';
import 'package:sixvalley_delivery_boy/features/chat/controllers/chat_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class VoiceNoteBottomSheet extends StatefulWidget {
  final ChatController chatController;
  const VoiceNoteBottomSheet(this.chatController, {super.key});

  @override
  State<VoiceNoteBottomSheet> createState() => _VoiceNoteBottomSheetState();
}

class _VoiceNoteBottomSheetState extends State<VoiceNoteBottomSheet> {
  final AudioRecorder _audioRecorder = AudioRecorder();
  final AudioPlayer _audioPlayer = AudioPlayer();

  bool _isRecording = false;
  bool _hasRecorded = false;
  bool _isPlaying = false;
  String? _audioPath;
  int _recordDuration = 0;
  Timer? _timer;
  Duration _audioPosition = Duration.zero;
  Duration _audioDuration = Duration.zero;

  @override
  void initState() {
    super.initState();
    _audioPlayer.onPositionChanged.listen((p) {
      if (mounted) setState(() => _audioPosition = p);
    });
    _audioPlayer.onDurationChanged.listen((d) {
      if (mounted) setState(() => _audioDuration = d);
    });
    _audioPlayer.onPlayerComplete.listen((event) {
      if (mounted) {
        setState(() {
          _isPlaying = false;
          _audioPosition = Duration.zero;
        });
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _audioRecorder.dispose();
    _audioPlayer.dispose();
    super.dispose();
  }

  void _startTimer() {
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() => _recordDuration++);
    });
  }

  String _formatDuration(int seconds) {
    final int minutes = seconds ~/ 60;
    final int remainingSeconds = seconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${remainingSeconds.toString().padLeft(2, '0')}';
  }

  Future<void> _startRecording() async {
    try {
      if (await _audioRecorder.hasPermission()) {
        final dir = await getTemporaryDirectory();
        _audioPath = '${dir.path}/voice_note_${DateTime.now().millisecondsSinceEpoch}.m4a';
        await _audioRecorder.start(const RecordConfig(encoder: AudioEncoder.aacLc), path: _audioPath!);
        setState(() {
          _isRecording = true;
          _recordDuration = 0;
        });
        _startTimer();
      } else {
        Get.snackbar('permission_denied'.tr, 'microphone_permission_denied'.tr,
            snackPosition: SnackPosition.BOTTOM);
      }
    } catch (e) {
      debugPrint('Error starting recording: $e');
    }
  }

  Future<void> _stopRecording() async {
    _timer?.cancel();
    final path = await _audioRecorder.stop();
    setState(() {
      _isRecording = false;
      if (path != null) {
        _hasRecorded = true;
        _audioPath = path;
      }
    });
  }

  Future<void> _cancelRecording() async {
    _timer?.cancel();
    if (_isRecording) await _audioRecorder.stop();
    if (_audioPath != null) {
      final file = File(_audioPath!);
      if (file.existsSync()) file.deleteSync();
    }
    Navigator.pop(context);
  }

  Future<void> _playPause() async {
    if (_isPlaying) {
      await _audioPlayer.pause();
    } else {
      await _audioPlayer.play(DeviceFileSource(_audioPath!));
    }
    setState(() => _isPlaying = !_isPlaying);
  }

  Future<void> _sendVoiceNote() async {
    if (_audioPath != null) {
      final file = File(_audioPath!);
      if (file.existsSync()) {
        PlatformFile pFile = PlatformFile(
          name: _audioPath!.split('/').last,
          size: file.lengthSync(),
          path: _audioPath!,
        );
        widget.chatController.addVoiceNote(pFile);
      }
    }
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: MediaQuery.sizeOf(context).width,
      padding: EdgeInsets.all(Dimensions.paddingSizeDefault),
      decoration: BoxDecoration(
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(30),
          topRight: Radius.circular(30),
        ),
        color: Get.isDarkMode
            ? Theme.of(context).textTheme.bodyLarge?.color
            : Theme.of(context).cardColor,
      ),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40, height: 4,
              decoration: BoxDecoration(
                color: Theme.of(context).hintColor.withValues(alpha: 0.5),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 20),
            if (!_hasRecorded) ...[
              Text(
                _isRecording ? _formatDuration(_recordDuration) : 'record_voice_note'.tr,
                style: rubikMedium.copyWith(
                  fontSize: Dimensions.fontSizeExtraLarge,
                  color: _isRecording ? Colors.red : null,
                ),
              ),
              const SizedBox(height: 30),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  IconButton(
                    onPressed: _cancelRecording,
                    icon: Icon(Icons.close, color: Theme.of(context).colorScheme.error, size: 30),
                  ),
                  GestureDetector(
                    onTap: _isRecording ? _stopRecording : _startRecording,
                    child: CircleAvatar(
                      radius: 40,
                      backgroundColor: _isRecording
                          ? Theme.of(context).colorScheme.error
                          : Theme.of(context).primaryColor,
                      child: Icon(
                        _isRecording ? Icons.stop : Icons.mic,
                        color: Colors.white, size: 40,
                      ),
                    ),
                  ),
                  const SizedBox(width: 48),
                ],
              ),
            ] else ...[
              Row(
                children: [
                  IconButton(
                    onPressed: _playPause,
                    icon: Icon(
                      _isPlaying ? Icons.pause_circle_filled : Icons.play_circle_filled,
                      size: 40, color: Theme.of(context).primaryColor,
                    ),
                  ),
                  Expanded(
                    child: Slider(
                      activeColor: Theme.of(context).primaryColor,
                      inactiveColor: Theme.of(context).hintColor.withValues(alpha: 0.3),
                      value: _audioPosition.inMilliseconds.toDouble(),
                      max: _audioDuration.inMilliseconds > 0
                          ? _audioDuration.inMilliseconds.toDouble()
                          : 1.0,
                      onChanged: (value) {
                        _audioPlayer.seek(Duration(milliseconds: value.toInt()));
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  TextButton.icon(
                    onPressed: () => setState(() { _hasRecorded = false; _audioPath = null; }),
                    icon: Icon(Icons.delete, color: Theme.of(context).colorScheme.error),
                    label: Text('delete'.tr,
                        style: rubikRegular.copyWith(color: Theme.of(context).colorScheme.error)),
                  ),
                  ElevatedButton.icon(
                    onPressed: _sendVoiceNote,
                    style: ElevatedButton.styleFrom(backgroundColor: Theme.of(context).primaryColor),
                    icon: const Icon(Icons.send, color: Colors.white),
                    label: Text('send'.tr, style: rubikMedium.copyWith(color: Colors.white)),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 15),
          ],
        ),
      ),
    );
  }
}
