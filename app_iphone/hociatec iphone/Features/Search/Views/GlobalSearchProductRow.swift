import SwiftUI
import UIKit

struct GlobalSearchProductRow: View {
    let product: Product
    var showsTitle: Bool = true
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @EnvironmentObject private var navigation: AppNavigationState
    @State private var feedbackDialog: FeedbackDialogState?
    @State private var showRentalSheet = false
    @State private var rentalMonths = 1
    @State private var rentalStartDate = Calendar.current.startOfDay(for: Date())

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if showsTitle {
                Text(product.name)
                    .fontWeight(.semibold)
                    .accessibilityAddTraits(.isHeader)
            }
            Text("Référence : \(product.sku)")
                .font(.footnote)
                .foregroundStyle(.secondary)
            Text("Type : \(productSellingContext(product))")
                .font(.footnote)
                .foregroundStyle(.secondary)
            if let configuration = productConfiguration(product) {
                Text("Configuration : \(configuration)")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Text(product.shortDescription)
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(2)
            Text(productPriceLabel(product))
                .font(.footnote.weight(.semibold))

            ProductCatalogActions(
                product: product,
                cart: cart,
                addToCart: {
                    Task { await addCurrentProductToCart() }
                },
                configureRental: {
                    showRentalSheet = true
                }
            )
        }
        .sheet(isPresented: $showRentalSheet) {
            RentalConfigurationSheet(
                rentalMonths: $rentalMonths,
                rentalStartDate: $rentalStartDate,
                confirmLabel: "Ajouter la location",
                onCancel: { showRentalSheet = false },
                onConfirm: {
                    showRentalSheet = false
                    Task { await addConfiguredRentalToCart() }
                }
            )
        }
        .accessibilityElement(children: .contain)
        .feedbackDialog($feedbackDialog)
    }

    private func addCurrentProductToCart() async {
        await cart.add(product: product, presentsFeedback: false)

        if let error = cart.error, !error.isEmpty {
            feedbackDialog = .error(error)
            UINotificationFeedbackGenerator().notificationOccurred(.error)
            return
        }

        feedbackDialog = .success(
            "\(product.name) a été ajouté au panier."
        )
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }

    private func addConfiguredRentalToCart() async {
        await cart.add(
            product: product,
            rentalMonths: rentalMonths,
            rentalStartDate: DatePresentation.encodeAPIDay(rentalStartDate),
            presentsFeedback: false
        )

        if let error = cart.error, !error.isEmpty {
            feedbackDialog = .error(error)
            UINotificationFeedbackGenerator().notificationOccurred(.error)
            return
        }

        feedbackDialog = .success(
            "\(product.name) a été ajouté au panier en location."
        )
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }
}
