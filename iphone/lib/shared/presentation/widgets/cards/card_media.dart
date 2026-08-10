import 'package:flutter/material.dart';

class CardMedia extends StatelessWidget {
  const CardMedia({
    required this.imageUrl,
    required this.icon,
    required this.background,
    required this.height,
    super.key,
  });

  final String imageUrl;
  final IconData icon;
  final Object background;
  final double height;

  @override
  Widget build(BuildContext context) {
    final decoration = BoxDecoration(
      borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
      border: const Border(
        bottom: BorderSide(color: Color(0xFFDED8D1)),
      ),
      color: background is Color ? background as Color : null,
      gradient: background is Gradient ? background as Gradient : null,
    );

    return Container(
      decoration: decoration,
      padding: const EdgeInsets.all(18),
      child: SizedBox(
        height: height,
        width: double.infinity,
        child: imageUrl.isNotEmpty
            ? Image.network(
                imageUrl,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) =>
                    CardMediaPlaceholder(icon: icon),
              )
            : CardMediaPlaceholder(icon: icon),
      ),
    );
  }
}

class CardMediaPlaceholder extends StatelessWidget {
  const CardMediaPlaceholder({
    required this.icon,
    super.key,
  });

  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: <Color>[Color(0x26F39A20), Color(0x2100A8B5)],
        ),
      ),
      child: Center(
        child: Icon(
          icon,
          size: 40,
          color: const Color(0xFF9D5624),
        ),
      ),
    );
  }
}
