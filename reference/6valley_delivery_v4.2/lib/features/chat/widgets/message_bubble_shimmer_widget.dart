import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';


class MessageBubbleShimmerWidget extends StatelessWidget {
  final bool isMe;
  const MessageBubbleShimmerWidget({Key? key, required this.isMe}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(padding: isMe ?  const EdgeInsets.fromLTRB(50, 5, 10, 5) : const EdgeInsets.fromLTRB(10, 5, 50, 5),
      child: Row(mainAxisSize: MainAxisSize.min,
        mainAxisAlignment: isMe ? MainAxisAlignment.end : MainAxisAlignment.start, children: [
          Flexible(child: Shimmer(enabled: true,
              gradient: const LinearGradient(colors: []),
              child: Container(height: 30, width: MediaQuery.of(context).size.width,
                decoration: BoxDecoration(borderRadius: BorderRadius.only(
                    topLeft: const Radius.circular(10),
                    bottomLeft: isMe ? const Radius.circular(10) : const Radius.circular(0),
                    bottomRight: isMe ? const Radius.circular(0) : const Radius.circular(10),
                    topRight: const Radius.circular(10)),
                  color: isMe ? Theme.of(context).hintColor : Theme.of(context).disabledColor)))),
        ],
      ),
    );
  }
}
