import SwiftUI

extension View {
    /// Compatibilité iOS 17 : évite l’avertissement de dépréciation en conservant l’ancien comportement.
    @ViewBuilder
    func onChangeCompat<V: Equatable>(_ value: V, initial: Bool = false, perform action: @escaping (V) -> Void) -> some View {
        if #available(iOS 17, *) {
            onChange(of: value, initial: initial) { _, newValue in
                action(newValue)
            }
        } else {
            onChange(of: value, perform: action)
        }
    }
}
