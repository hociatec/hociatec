import SwiftUI

struct CheckoutSuccessView: View {
    let order: OrderSummary
    let orderService: OrderServing

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 10) {
                    Text("Confirmation du paiement")
                        .font(.title3.weight(.bold))
                    Text("Votre commande \(order.number) a bien été enregistrée. Vous pouvez suivre son avancement depuis votre espace client.")
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 4)
            }

            Section("Récapitulatif") {
                LabeledContent("Commande") { Text(order.number) }
                LabeledContent("Statut") { Text(order.statusLabel) }
                LabeledContent("Montant") {
                    Text(PriceFormatter.format(cents: order.totalPriceCents))
                        .fontWeight(.semibold)
                }
                LabeledContent("Créée le") { Text(OrderStatusPresentation.dateFormatter.string(from: order.createdAt)) }
            }

            Section {
                NavigationLink {
                    OrderDetailView(service: orderService, orderId: order.id)
                } label: {
                    Label("Voir la commande", systemImage: "doc.text")
                        .fontWeight(.semibold)
                }
            }
        }
        .navigationTitle("Paiement confirmé")
    }
}
