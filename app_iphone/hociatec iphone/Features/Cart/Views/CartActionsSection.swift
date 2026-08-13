import SwiftUI

struct CartActionsSection: View {
    let isLoading: Bool
    let isEmpty: Bool
    let checkout: () -> Void
    let clear: () -> Void

    var body: some View {
        Section {
            Button(action: checkout) {
                if isLoading {
                    ProgressView().frame(maxWidth: .infinity)
                } else {
                    Text("Passer la commande")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(isEmpty)

            Button(role: .destructive, action: clear) {
                Text("Vider le panier").frame(maxWidth: .infinity)
            }
            .disabled(isEmpty)
        }
    }
}
