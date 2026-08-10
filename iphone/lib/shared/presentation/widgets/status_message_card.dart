import 'package:flutter/material.dart';

class StatusMessageCard extends StatelessWidget {
  const StatusMessageCard({
    required this.message,
    this.padding = const EdgeInsets.all(18),
    this.textStyle,
    this.borderColor = const Color(0xFFE2D7CA),
    super.key,
  });

  final String message;
  final EdgeInsetsGeometry padding;
  final TextStyle? textStyle;
  final Color borderColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: padding,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: borderColor),
      ),
      child: Text(
        message,
        textAlign: TextAlign.center,
        style: textStyle ?? Theme.of(context).textTheme.bodyMedium,
      ),
    );
  }
}
