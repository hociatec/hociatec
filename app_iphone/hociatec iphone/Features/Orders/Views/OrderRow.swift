import SwiftUI

struct OrderRow: View {
    let order: OrderSummary
    let isCancelling: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Commande n°: \(order.number)")
                .font(.headline)

            Text("\(order.items.count) article\(order.items.count > 1 ? "s" : "")")
                .font(.caption2)
                .padding(.horizontal, 8)
                .padding(.vertical, 4)
                .background(Color.gray.opacity(0.15))
                .foregroundColor(.secondary)
                .clipShape(Capsule())

            Text(order.statusLabel)
                .font(.caption)
                .padding(.horizontal, 8)
                .padding(.vertical, 4)
                .background(OrderStatusPresentation.color(for: order.status).opacity(0.15))
                .foregroundColor(OrderStatusPresentation.color(for: order.status))
                .clipShape(Capsule())

            Text("Total: \(PriceFormatter.format(cents: order.totalPriceCents))")
            Text("Créée le: \(OrderStatusPresentation.dateFormatter.string(from: order.createdAt))")
                .font(.footnote)
                .foregroundColor(.secondary)
            Text("Livraison: \(order.shipping.name), \(order.shipping.city)")
                .font(.footnote)
                .foregroundColor(.secondary)

            if isCancelling {
                HStack {
                    ProgressView().scaleEffect(0.8)
                    Text("Annulation en cours...")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
            }
        }
    }
}
