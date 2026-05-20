import 'package:flutter/material.dart';

/// Keeps primary content readable on iPad instead of stretching edge-to-edge.
class TabletContentWidth extends StatelessWidget {
  const TabletContentWidth({
    super.key,
    required this.child,
    this.maxWidth = 920,
  });

  final Widget child;
  final double maxWidth;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.topCenter,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: maxWidth),
        child: child,
      ),
    );
  }
}
