import SwiftUI

struct CartActionsSection: View {
    let isLoading: Bool
    let isEmpty: Bool
    let canCheckout: Bool
    let showsAddressRequirement: Bool
    let checkout: () -> Void
    let clear: () -> Void
    let addressesDestination: AnyView

    var body: some View {
        Section {
            if showsAddressRequirement {
                VStack(alignment: .leading, spacing: 8) {
                    Text("Définissez une adresse par défaut avant de valider votre commande.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)

                    NavigationLink {
                        addressesDestination
                    } label: {
                        Text("Définir une adresse par défaut")
                            .fontWeight(.semibold)
                    }
                }
                .padding(.vertical, 4)
            }

            Button(action: checkout) {
                if isLoading {
                    ProgressView().frame(maxWidth: .infinity)
                } else {
                    Text("Passer la commande")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(!canCheckout)

            Button(role: .destructive, action: clear) {
                Text("Vider le panier").frame(maxWidth: .infinity)
            }
            .disabled(isEmpty)
        }
    }
}
