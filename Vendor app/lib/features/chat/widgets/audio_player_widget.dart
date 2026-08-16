import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

class AudioPlayerWidget extends StatefulWidget {
  final String url;
  final bool isMe;
  const AudioPlayerWidget({super.key, required this.url, required this.isMe});

  @override
  State<AudioPlayerWidget> createState() => _AudioPlayerWidgetState();
}

class _AudioPlayerWidgetState extends State<AudioPlayerWidget> {
  final AudioPlayer _audioPlayer = AudioPlayer();
  bool _isPlaying = false;
  Duration _position = Duration.zero;
  Duration _duration = Duration.zero;
  double _playbackRate = 1.0;

  static const List<double> _waveformHeights = [
    10, 18, 14, 24, 30, 20, 16, 26, 32, 28, 22, 18, 26, 34, 28, 20, 14, 22, 30, 24, 18, 12, 20, 28, 16, 10
  ];

  @override
  void initState() {
    super.initState();
    _audioPlayer.setSourceUrl(widget.url);
    _audioPlayer.onPositionChanged.listen((p) {
      if (mounted) setState(() => _position = p);
    });
    _audioPlayer.onDurationChanged.listen((d) {
      if (mounted) setState(() => _duration = d);
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

  @override
  void dispose() {
    _audioPlayer.dispose();
    super.dispose();
  }

  void _togglePlayPause() async {
    if (_isPlaying) {
      await _audioPlayer.pause();
    } else {
      await _audioPlayer.play(UrlSource(widget.url));
      await _audioPlayer.setPlaybackRate(_playbackRate);
    }
    setState(() => _isPlaying = !_isPlaying);
  }

  void _toggleSpeed() async {
    double nextRate = _playbackRate == 1.0 ? 1.5 : _playbackRate == 1.5 ? 2.0 : 1.0;
    setState(() {
      _playbackRate = nextRate;
    });
    if (_isPlaying) {
      await _audioPlayer.setPlaybackRate(nextRate);
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
    final double progress = (_duration.inMilliseconds > 0)
        ? (_position.inMilliseconds / _duration.inMilliseconds).clamp(0.0, 1.0)
        : 0.0;

    final primaryColor = widget.isMe ? const Color(0xFF4A148C) : Theme.of(context).primaryColor;
    final activeWaveColor = widget.isMe ? const Color(0xFF4A148C) : const Color(0xFF00A884);
    final inactiveWaveColor = Colors.grey.withValues(alpha: 0.35);

    return Container(
      width: 260,
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: widget.isMe
            ? const Color(0xFFF3E5F5)
            : Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
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
            children: [
              GestureDetector(
                onTap: _togglePlayPause,
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: primaryColor,
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: Icon(
                      _isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded,
                      color: Colors.white,
                      size: 26,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: GestureDetector(
                  onHorizontalDragUpdate: (details) {
                    if (_duration.inMilliseconds > 0) {
                      final RenderBox box = context.findRenderObject() as RenderBox;
                      final double relativeX = (details.localPosition.dx / box.size.width).clamp(0.0, 1.0);
                      final seekMs = (relativeX * _duration.inMilliseconds).toInt();
                      _audioPlayer.seek(Duration(milliseconds: seekMs));
                    }
                  },
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
              ),
              const SizedBox(width: 6),
              Icon(
                Icons.mic_rounded,
                size: 20,
                color: widget.isMe ? const Color(0xFF4A148C) : const Color(0xFF00A884),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Padding(
            padding: const EdgeInsets.only(left: 48, right: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  _isPlaying ? _formatDuration(_position) : (_duration.inMilliseconds > 0 ? _formatDuration(_duration) : '0:00'),
                  style: titilliumRegular.copyWith(
                    fontSize: 11,
                    color: Colors.grey[600],
                  ),
                ),
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
                      style: titilliumBold.copyWith(
                        fontSize: 10,
                        color: primaryColor,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
