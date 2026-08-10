import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/status_message_card.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';

class AsyncCollectionView<T> extends StatelessWidget {
  const AsyncCollectionView({
    required this.asyncValue,
    required this.emptyMessage,
    required this.errorFallback,
    required this.itemBuilder,
    this.padding = const EdgeInsets.all(20),
    this.itemSpacing = 12,
    super.key,
  });

  final AsyncValue<List<T>> asyncValue;
  final String emptyMessage;
  final String errorFallback;
  final Widget Function(BuildContext context, T item) itemBuilder;
  final EdgeInsetsGeometry padding;
  final double itemSpacing;

  @override
  Widget build(BuildContext context) {
    return asyncValue.when(
      data: (items) {
        if (items.isEmpty) {
          return _CenteredStatusMessage(message: emptyMessage);
        }

        return ListView.separated(
          padding: padding,
          itemCount: items.length,
          separatorBuilder: (context, index) => SizedBox(height: itemSpacing),
          itemBuilder: (context, index) => itemBuilder(context, items[index]),
        );
      },
      error: (error, stackTrace) => _CenteredStatusMessage(
        message: resolveApiErrorMessage(error, errorFallback),
      ),
      loading: () => const Center(child: CircularProgressIndicator()),
    );
  }
}

class _CenteredStatusMessage extends StatelessWidget {
  const _CenteredStatusMessage({
    required this.message,
  });

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: StatusMessageCard(message: message),
      ),
    );
  }
}
