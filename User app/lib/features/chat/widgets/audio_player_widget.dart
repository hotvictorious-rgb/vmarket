import 'dart:io';
import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_image_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/chat/widgets/whatsapp_bubble_tail.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';

class AudioPlayerWidget extends StatefulWidget {
  final String url;
  final bool isMe;
  final String? chatTime;
  final bool? isSeen;
  final String? avatarUrl;

  const AudioPlayerWidget({
    super.key,
    required this.url,
    required this.isMe,
    this.chatTime,
    this.isSeen,
    this.avatarUrl,
  });

  @override
  State<AudioPlayerWidget> createState() => _AudioPlayerWidgetState();
}

class _AudioPlayerWidgetState extends State<AudioPlayerWidget> with SingleTickerProviderStateMixin {
  final AudioPlayer _audioPlayer = AudioPlayer();
  bool _isPlaying = false;
  Duration _position = Duration.zero;
  Duration _duration = Duration.zero;
  double _playbackRate = 1.0;
  late AnimationController _pulseController;

  static const List<double> _waveformHeights = [
    12, 22, 16, 28, 36, 24, 18, 30, 38, 32, 26, 20, 30, 40, 32, 24, 16, 26, 36, 28, 20, 14, 24, 32, 18, 12, 22, 16, 26, 14
  ];

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1000),
    )..repeat(reverse: true);

    _initAudio();
  }

  void _initAudio() {
    _audioPlayer.onPositionChanged.listen((p) {
      if (mounted) setState(() => _position = p);
    });
    _audioPlayer.onDurationChanged.listen((d) {
      if (mounted && d > Duration.zero) setState(() => _duration = d);
    });
    _audioPlayer.onPlayerComplete.listen((event) {
      if (mounted) {
        setState(() {
          _isPlaying = false;
          _position = Duration.zero;
        });
      }
    });
  }

  Source _getSource(String url) {
    if (url.startsWith('http://') || url.startsWith('https://')) {
      return UrlSource(url);
    }
    final file = File(url);
    if (file.existsSync()) {
      return DeviceFileSource(url);
    }
    return UrlSource(url);
  }

  @override
  void dispose() {
    _pulseController.dispose();
    _audioPlayer.dispose();
    super.dispose();
  }

  void _togglePlayPause() async {
    try {
      if (_isPlaying) {
        await _audioPlayer.pause();
      } else {
        await _audioPlayer.play(_getSource(widget.url));
        await _audioPlayer.setPlaybackRate(_playbackRate);
      }
      if (mounted) {
        setState(() => _isPlaying = !_isPlaying);
      }
    } catch (e) {
      debugPrint("Audio playback error: $e");
    }
  }

  void _toggleSpeed() async {
    double nextRate = _playbackRate == 1.0 ? 1.5 : _playbackRate == 1.5 ? 2.0 : 1.0;
    setState(() => _playbackRate = nextRate);
    if (_isPlaying) {
      await _audioPlayer.setPlaybackRate(nextRate);
    }
  }

  void _seekToRelative(double relativeX) {
    if (_duration.inMilliseconds > 0) {
      final seekMs = (relativeX.clamp(0.0, 1.0) * _duration.inMilliseconds).toInt();
      _audioPlayer.seek(Duration(milliseconds: seekMs));
    }
  }

  String _formatDuration(Duration duration) {
    String twoDigits(int n) => n.toString().padLeft(2, "0");
    String twoDigitMinutes = twoDigits(duration.inMinutes.remainder(60));
    String twoDigitSeconds = twoDigits(duration.inSeconds.remainder(60));
    return "$twoDigitMinutes:$twoDigitSeconds";
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final double progress = (_duration.inMilliseconds > 0)
        ? (_position.inMilliseconds / _duration.inMilliseconds).clamp(0.0, 1.0)
        : 0.0;

    // Victorious Purple & WhatsApp Green themes
    final bubbleColor = widget.isMe
        ? (isDark ? const Color(0xFF381A52) : const Color(0xFFF3E5F5))
        : (isDark ? const Color(0xFF1F2C34) : Colors.white);

    final primaryColor = widget.isMe ? const Color(0xFF6A1B9A) : const Color(0xFF00A884);
    final activeWaveColor = widget.isMe ? const Color(0xFF6A1B9A) : const Color(0xFF00A884);
    final inactiveWaveColor = isDark ? Colors.white24 : Colors.grey.withValues(alpha: 0.35);

    return Stack(
      clipBehavior: Clip.none,
      children: [
        Container(
          width: 275,
          padding: const EdgeInsets.fromLTRB(10, 8, 10, 6),
          decoration: BoxDecoration(
            color: bubbleColor,
            borderRadius: BorderRadius.only(
              topLeft: const Radius.circular(16),
              topRight: const Radius.circular(16),
              bottomLeft: Radius.circular(widget.isMe ? 16 : 4),
              bottomRight: Radius.circular(widget.isMe ? 4 : 16),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: isDark ? 0.25 : 0.05),
                blurRadius: 4,
                offset: const Offset(0, 1),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // WhatsApp Circular Play/Pause button
                  GestureDetector(
                    onTap: _togglePlayPause,
                    child: AnimatedBuilder(
                      animation: _pulseController,
                      builder: (context, child) {
                        return Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: primaryColor,
                            shape: BoxShape.circle,
                            boxShadow: _isPlaying
                                ? [
                                    BoxShadow(
                                      color: primaryColor.withValues(alpha: 0.4 * _pulseController.value),
                                      blurRadius: 8,
                                      spreadRadius: 2,
                                    )
                                  ]
                                : [],
                          ),
                          child: Center(
                            child: Icon(
                              _isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded,
                              color: Colors.white,
                              size: 28,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                  const SizedBox(width: 8),

                  // Interactive Waveform Scrubber
                  Expanded(
                    child: LayoutBuilder(
                      builder: (context, constraints) {
                        return GestureDetector(
                          behavior: HitTestBehavior.opaque,
                          onTapDown: (details) {
                            final relativeX = details.localPosition.dx / constraints.maxWidth;
                            _seekToRelative(relativeX);
                          },
                          onHorizontalDragUpdate: (details) {
                            final relativeX = details.localPosition.dx / constraints.maxWidth;
                            _seekToRelative(relativeX);
                          },
                          child: Container(
                            height: 38,
                            alignment: Alignment.center,
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: List.generate(_waveformHeights.length, (index) {
                                final barProgress = index / _waveformHeights.length;
                                final bool isPassed = barProgress <= progress;
                                return Container(
                                  width: 3,
                                  height: _waveformHeights[index],
                                  decoration: BoxDecoration(
                                    color: isPassed ? activeWaveColor : inactiveWaveColor,
                                    borderRadius: BorderRadius.circular(2),
                                  ),
                                );
                              }),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                  const SizedBox(width: 6),

                  // Avatar or Mic Icon Badge
                  if (widget.avatarUrl != null && widget.avatarUrl!.isNotEmpty)
                    ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: CustomImageWidget(
                        image: widget.avatarUrl!,
                        height: 32,
                        width: 32,
                        fit: BoxFit.cover,
                      ),
                    )
                  else
                    Icon(
                      Icons.mic_rounded,
                      size: 22,
                      color: primaryColor,
                    ),
                ],
              ),
              const SizedBox(height: 2),

              // Duration, Speed Toggle & Timestamp Row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // Duration display (e.g. 0:15)
                  Padding(
                    padding: const EdgeInsets.only(left: 4),
                    child: Text(
                      _isPlaying
                          ? _formatDuration(_position)
                          : (_duration.inMilliseconds > 0 ? _formatDuration(_duration) : '0:00'),
                      style: textRegular.copyWith(
                        fontSize: 11,
                        color: isDark ? Colors.white60 : Colors.grey[600],
                      ),
                    ),
                  ),

                  // Speed toggle pill (1x, 1.5x, 2x)
                  GestureDetector(
                    onTap: _toggleSpeed,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: primaryColor.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '${_playbackRate}x',
                        style: textBold.copyWith(
                          fontSize: 10,
                          color: primaryColor,
                        ),
                      ),
                    ),
                  ),

                  // Embedded Timestamp & Checkmarks
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (widget.chatTime != null && widget.chatTime!.isNotEmpty)
                        Text(
                          widget.chatTime!,
                          style: textRegular.copyWith(
                            fontSize: 10,
                            color: isDark ? Colors.white54 : Colors.grey[500],
                          ),
                        ),
                      if (widget.isMe) ...[
                        const SizedBox(width: 4),
                        Icon(
                          Icons.done_all_rounded,
                          size: 14,
                          color: (widget.isSeen ?? false) ? const Color(0xFF53BDEB) : (isDark ? Colors.white38 : Colors.grey[400]),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ],
          ),
        ),

        // WhatsApp Tail
        Positioned(
          top: 0,
          right: widget.isMe ? -7 : null,
          left: !widget.isMe ? -7 : null,
          child: CustomPaint(
            size: const Size(8, 12),
            painter: WhatsAppBubbleTail(
              isMe: widget.isMe,
              color: bubbleColor,
            ),
          ),
        ),
      ],
    );
  }
}
