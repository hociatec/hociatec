import 'package:flutter/material.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/status_message_card.dart';

class HomeEmptySectionState extends StatelessWidget {
  const HomeEmptySectionState({
    required this.message,
    super.key,
  });

  final String message;

  @override
  Widget build(BuildContext context) {
    return StatusMessageCard(
      message: message,
      borderColor: const Color(0xFFE2D6C8),
      textStyle: Theme.of(context).textTheme.bodyMedium?.copyWith(
            color: const Color(0xFF5C544D),
            height: 1.5,
          ),
    );
  }
}
