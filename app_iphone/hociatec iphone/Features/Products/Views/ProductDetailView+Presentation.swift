import SwiftUI

extension View {
    func productDetailAlerts(
        alertState: Binding<ProductDetailAlertState>,
        dismiss: DismissAction,
        selectedTab: Binding<Int>
    ) -> some View {
        self
            .alert("Ajout au panier", isPresented: alertState.showAddAlert) {
                Button("Continuer", role: .cancel) { dismiss() }
                Button("Voir le panier") {
                    selectedTab.wrappedValue = 2
                    dismiss()
                }
            } message: {
                Text("\(alertState.wrappedValue.addedProductName) a été ajouté au panier.")
            }
            .alert("Stock insuffisant", isPresented: alertState.showStockAlert) {
                Button("OK", role: .cancel) {}
            } message: {
                Text(alertState.wrappedValue.stockAlertMessage)
            }
    }

    func productDetailFavoriteToolbar(viewModel: ProductDetailViewModel) -> some View {
        self.toolbar {
            ToolbarItem(placement: .topBarTrailing) {
                Button {
                    Task { await viewModel.toggleFavorite() }
                } label: {
                    Image(systemName: viewModel.isFavorite ? "heart.fill" : "heart")
                }
                .accessibilityLabel(viewModel.isFavorite ? "Retirer des favoris" : "Ajouter aux favoris")
            }
        }
    }
}
