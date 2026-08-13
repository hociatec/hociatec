import SwiftUI

struct CartSummarySection: View {
    let cart: Cart

    var body: some View {
        Section {
            HStack {
                Text("Total articles")
                Spacer()
                Text("\(cart.totalQuantity)")
                    .accessibilityLabel(cart.totalQuantity == 1 ? "1 article" : "\(cart.totalQuantity) articles")
            }
            HStack {
                Text("Total")
                Spacer()
                Text(PriceFormatter.format(cents: cart.totalPriceCents))
                    .fontWeight(.semibold)
            }
        }
    }
}
