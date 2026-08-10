enum CartActionResultKind {
  added,
  removed,
}

class CartActionResult {
  const CartActionResult(this.kind);

  final CartActionResultKind kind;

  bool get added => kind == CartActionResultKind.added;
  bool get removed => kind == CartActionResultKind.removed;
}
