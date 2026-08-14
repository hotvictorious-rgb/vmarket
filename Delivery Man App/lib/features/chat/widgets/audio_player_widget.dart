import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

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
    }
    setState(() => _isPlaying = !_isPlaying);
  }

  String _formatDuration(Duration duration) {
    String twoDigits(int n) => n.toString().padLeft(2, '0');
    return '${twoDigits(duration.inMinutes.remainder(60))}:${twoDigits(duration.inSeconds.remainder(60))}';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 250,
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: widget.isMe
            ? Theme.of(context).primaryColor.withValues(alpha: 0.1)
            : Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            padding: EdgeInsets.zero,
            icon: Icon(
              _isPlaying ? Icons.pause_circle_filled : Icons.play_circle_filled,
              color: widget.isMe
                  ? Theme.of(context).primaryColor
                  : Theme.of(context).hintColor,
              size: 35,
            ),
            onPressed: _togglePlayPause,
          ),
          Expanded(
            child: Slider(
              activeColor: widget.isMe
                  ? Theme.of(context).primaryColor
                  : Theme.of(context).hintColor,
              inactiveColor: Theme.of(context).hintColor.withValues(alpha: 0.3),
              min: 0.0,
              max: _duration.inMilliseconds > 0
                  ? _duration.inMilliseconds.toDouble()
                  : 1.0,
              value: _position.inMilliseconds
                  .toDouble()
                  .clamp(
                    0.0,
                    _duration.inMilliseconds > 0
                        ? _duration.inMilliseconds.toDouble()
                        : 1.0,
                  ),
              onChanged: (value) {
                _audioPlayer.seek(Duration(milliseconds: value.toInt()));
              },
            ),
          ),
          Text(
            _formatDuration(_position),
            style: rubikRegular.copyWith(
              fontSize: Dimensions.fontSizeSmall,
              color: widget.isMe
                  ? Theme.of(context).primaryColor
                  : Theme.of(context).hintColor,
            ),
          ),
        ],
      ),
    );
  }
}
