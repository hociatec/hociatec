import SwiftUI

struct RentalMonthsSelector: View {
    let rentalMonths: Int
    let decreaseAction: () -> Void
    let increaseAction: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Durée de location")
                .font(.headline)
            HStack(spacing: 12) {
                Text("\(rentalMonths) mois")
                    .fontWeight(.semibold)
                Spacer()
                Button(action: decreaseAction) {
                    Image(systemName: "minus")
                        .frame(width: 30, height: 30)
                }
                .buttonStyle(.bordered)
                .accessibilityLabel("Moins")
                Button(action: increaseAction) {
                    Image(systemName: "plus")
                        .frame(width: 30, height: 30)
                }
                .buttonStyle(.bordered)
                .accessibilityLabel("Plus")
            }
        }
    }
}

struct ProductQuantityControls: View {
    let currentQuantity: Int
    let canIncrease: Bool
    let isLoading: Bool
    let decreaseAction: () -> Void
    let increaseAction: () -> Void

    var body: some View {
        HStack(spacing: 16) {
            Button(action: decreaseAction) {
                Image(systemName: "minus")
                    .frame(width: 44, height: 44)
            }
            .buttonStyle(.bordered)
            .accessibilityLabel("Moins")

            Text("Quantité: \(currentQuantity)")
                .fontWeight(.semibold)

            if canIncrease {
                Button(action: increaseAction) {
                    Image(systemName: "plus")
                        .frame(width: 44, height: 44)
                }
                .buttonStyle(.bordered)
                .accessibilityLabel("Plus")
                .disabled(isLoading)
                .allowsHitTesting(!isLoading)
            }
        }
        .padding(.top, 8)
    }
}

struct ProductPurchaseSection: View {
    let currentQuantity: Int
    let stockLimit: Int
    let isLoading: Bool
    let isOutOfStock: Bool
    let showRentalSelector: Bool
    let rentalMonths: Int
    let decreaseRentalMonths: () -> Void
    let increaseRentalMonths: () -> Void
    let decreaseQuantity: () -> Void
    let increaseQuantity: () -> Void
    let addToCart: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            if showRentalSelector {
                RentalMonthsSelector(
                    rentalMonths: rentalMonths,
                    decreaseAction: decreaseRentalMonths,
                    increaseAction: increaseRentalMonths
                )
            }

            if currentQuantity > 0 {
                ProductQuantityControls(
                    currentQuantity: currentQuantity,
                    canIncrease: currentQuantity < stockLimit,
                    isLoading: isLoading,
                    decreaseAction: decreaseQuantity,
                    increaseAction: increaseQuantity
                )
            } else {
                Button(action: addToCart) {
                    HStack {
                        Spacer()
                        if isLoading {
                            ProgressView()
                        } else {
                            Text("Ajouter au panier")
                                .fontWeight(.semibold)
                        }
                        Spacer()
                    }
                    .padding()
                    .background(Color.teal.opacity(0.15))
                    .foregroundStyle(.teal)
                    .clipShape(RoundedRectangle(cornerRadius: 12))
                }
                .disabled(isLoading || isOutOfStock)
                .padding(.top, 8)
            }
        }
    }
}
