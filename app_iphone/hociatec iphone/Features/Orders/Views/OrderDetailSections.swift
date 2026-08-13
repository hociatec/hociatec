import SwiftUI

struct OrderDetailErrorSection: View {
    let error: String

    var body: some View {
        Section {
            Text(error)
                .foregroundColor(.red)
        }
    }
}

struct OrderDetailLoadingSection: View {
    var body: some View {
        Section {
            HStack {
                Spacer()
                ProgressView()
                Spacer()
            }
        }
    }
}

struct OrderDetailSummarySection: View {
    let order: OrderSummary

    var body: some View {
        Section {
            LabeledContent("Numéro") { Text(order.number) }
            LabeledContent("Statut") { Text(order.statusLabel) }
            LabeledContent("Créée le") {
                Text(OrderStatusPresentation.dateFormatter.string(from: order.createdAt))
            }
        }
    }
}

struct OrderDetailShippingSection: View {
    let order: OrderSummary

    var body: some View {
        Section {
            LabeledContent("Nom") { Text(order.shipping.name) }
            LabeledContent("Adresse") { Text(order.shipping.address) }
            LabeledContent("Code postal") { Text(order.shipping.postalCode) }
            LabeledContent("Ville") { Text(order.shipping.city) }
        }
    }
}

struct OrderDetailItemsSection: View {
    let order: OrderSummary

    var body: some View {
        Section {
            ForEach(order.items) { item in
                OrderDetailItemRow(item: item)
            }
        }
    }
}

private struct OrderDetailItemRow: View {
    let item: OrderLineItem

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(item.productName)
                .fontWeight(.semibold)
            Text("SKU: \(item.productSku)")
                .font(.caption)
                .foregroundStyle(.secondary)

            HStack {
                Text("Qté: \(item.quantity)")
                Spacer()
                Text(PriceFormatter.format(cents: item.unitPriceCents))
            }

            HStack {
                Text("Total ligne")
                Spacer()
                Text(PriceFormatter.format(cents: item.linePriceCents))
                    .fontWeight(.semibold)
            }
        }
        .padding(.vertical, 4)
    }
}

struct OrderDetailTotalSection: View {
    let order: OrderSummary

    var body: some View {
        Section {
            Text(PriceFormatter.format(cents: order.totalPriceCents))
                .fontWeight(.bold)
        }
    }
}

struct OrderDetailCancelSection: View {
    let isDisabled: Bool
    let onCancel: () -> Void

    var body: some View {
        Section {
            Button(role: .destructive, action: onCancel) {
                Text("Annuler la commande")
                    .frame(maxWidth: .infinity)
            }
            .disabled(isDisabled)
        }
    }
}
