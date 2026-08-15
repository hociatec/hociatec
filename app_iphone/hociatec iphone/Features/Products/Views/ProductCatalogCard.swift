import SwiftUI
import UIKit

struct ProductCatalogCard: View {
    let product: Product
    let imageURL: URL?
    let cart: CartViewModel
    @Binding var selectedTab: Int
    let isCompact: Bool
    var onFavoriteRemoved: (() -> Void)? = nil

    @EnvironmentObject private var container: AppContainer
    @State private var feedbackDialog: FeedbackDialogState?
    @State private var showRentalSheet = false
    @State private var rentalMonths = 1
    @State private var rentalStartDate = Calendar.current.startOfDay(for: Date())

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            NavigationLink {
                ProductCatalogDetailDestination(
                    product: product,
                    imageURL: imageURL,
                    cart: cart,
                    selectedTab: $selectedTab
                )
                .environmentObject(container)
            } label: {
                Text(product.name)
                    .font(isCompact ? .subheadline.weight(.semibold) : .headline)
                    .multilineTextAlignment(.leading)
                    .frame(maxWidth: .infinity, alignment: .leading)
            }
            .buttonStyle(.plain)
            .accessibilityHint("Afficher le détail du produit")
            .accessibilityAddTraits(.isHeader)

            ProductCatalogCardContent(
                product: product,
                imageURL: imageURL,
                isCompact: isCompact,
                showsTitle: false
            )

            ProductCatalogActions(
                product: product,
                cart: cart,
                addToCart: {
                    Task { await addCurrentProductToCart() }
                },
                configureRental: {
                    showRentalSheet = true
                },
                onFavoriteRemoved: onFavoriteRemoved
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
        .padding(.vertical, 6)
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
