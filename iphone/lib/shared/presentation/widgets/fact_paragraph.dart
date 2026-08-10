import 'package:flutter/material.dart';

class FactParagraph extends StatelessWidget {
  const FactParagraph({
    required this.label,
    required this.value,
    this.showDivider = true,
    super.key,
  });

  final String label;
  final String value;
  final bool showDivider;

  @override
  Widget build(BuildContext context) {
    final border = showDivider
        ? const Border(
            bottom: BorderSide(color: Color(0xFFEBE6DF)),
          )
        : null;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.only(bottom: 10, top: 2),
      decoration: BoxDecoration(border: border),
      child: Semantics(
        container: true,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              '$label:',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: const Color(0xFF63584F),
                    fontWeight: FontWeight.w800,
                    height: 1.45,
                  ),
            ),
            const SizedBox(height: 2),
            Text(
              value,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: const Color(0xFF1F2330),
                    fontWeight: FontWeight.w700,
                    height: 1.5,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
