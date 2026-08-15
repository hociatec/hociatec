import SwiftUI

struct CartItemRow: View {
    let item: CartItem
    let isLoading: Bool
    let decreaseRentalMonths: () -> Void
    let increaseRentalMonths: () -> Void
    let decreaseQuantity: () -> Void
    let increaseQuantity: () -> Void

    private var currentMonths: Int {
        max(1, item.rentalMonths ?? 1)
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(item.product.name)
                .fontWeight(.semibold)

            if item.product.sellingType == .rental {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Du \(DatePresentation.formatAPIDay(item.rentalStartDate)) au \(DatePresentation.formatAPIDay(item.rentalEndDate))")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                    Text("Sous-total: \(PriceFormatter.format(cents: item.linePriceCents))")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }
                CartRentalMonthsControl(
                    currentMonths: currentMonths,
                    isLoading: isLoading,
                    decreaseAction: decreaseRentalMonths,
                    increaseAction: increaseRentalMonths
                )
            }

            HStack {
                Text(PriceFormatter.format(cents: item.product.effectivePriceCents))
                if item.product.sellingType == .rental {
                    Text("/mois")
                        .foregroundStyle(.secondary)
                }
                Spacer()
                CartQuantityControl(
                    quantity: item.quantity,
                    decreaseAction: decreaseQuantity,
                    increaseAction: increaseQuantity
                )
            }
        }
        .padding(.vertical, 6)
        .accessibilityElement(children: .contain)
    }
}

private struct CartRentalMonthsControl: View {
    let currentMonths: Int
    let isLoading: Bool
    let decreaseAction: () -> Void
    let increaseAction: () -> Void

    var body: some View {
        HStack(spacing: 12) {
            Text("Durée")
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Text("\(currentMonths) mois")
                .font(.subheadline)
                .fontWeight(.semibold)
            Spacer()
            Button(action: decreaseAction) {
                Image(systemName: "minus").frame(width: 32, height: 32)
            }
            .buttonStyle(.bordered)
            .accessibilityLabel("Réduire la durée de location")
            .disabled(isLoading || currentMonths <= 1)

            Button(action: increaseAction) {
                Image(systemName: "plus").frame(width: 32, height: 32)
            }
            .buttonStyle(.bordered)
            .accessibilityLabel("Augmenter la durée de location")
            .disabled(isLoading || currentMonths >= 36)
        }
        .accessibilityElement(children: .combine)
        .accessibilityLabel("Durée de location : \(currentMonths) mois")
    }
}

private struct CartQuantityControl: View {
    let quantity: Int
    let decreaseAction: () -> Void
    let increaseAction: () -> Void

    var body: some View {
        HStack(spacing: 12) {
            Button(action: decreaseAction) {
                Image(systemName: "minus").frame(width: 32, height: 32)
            }
            .buttonStyle(.bordered)
            .accessibilityLabel("Moins")

            Text("\(quantity)")
                .fontWeight(.semibold)

            Button(action: increaseAction) {
                Image(systemName: "plus").frame(width: 32, height: 32)
            }
            .buttonStyle(.bordered)
            .accessibilityLabel("Plus")
        }
    }
}
