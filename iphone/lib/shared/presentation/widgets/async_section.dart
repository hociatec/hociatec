import 'package:flutter/material.dart';

class AsyncSection<T> extends StatelessWidget {
  const AsyncSection({
    required this.value,
    required this.builder,
    required this.loadingLabel,
    required this.emptyLabel,
    super.key,
  });

  final AsyncSnapshot<T> value;
  final Widget Function(T data) builder;
  final String loadingLabel;
  final String emptyLabel;

  @override
  Widget build(BuildContext context) {
    if (value.connectionState == ConnectionState.waiting) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 24),
        child: Center(child: CircularProgressIndicator()),
      );
    }

    if (value.hasError) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 16),
        child: Text(
          value.error.toString(),
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.error,
              ),
        ),
      );
    }

    final data = value.data;
    if (data == null) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 16),
        child: Text(emptyLabel),
      );
    }

    return builder(data);
  }
}
