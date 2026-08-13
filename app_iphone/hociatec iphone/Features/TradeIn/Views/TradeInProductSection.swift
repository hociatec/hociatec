import SwiftUI

struct TradeInProductSection: View {
    @ObservedObject var viewModel: TradeInViewModel

    var body: some View {
        Section {
            Picker("Catégorie", selection: $viewModel.selectedCategory) {
                ForEach(viewModel.categories) { option in
                    Text(option.label).tag(option.value)
                }
            }
            TextField("Nom du produit", text: $viewModel.productName)
            TextField("Marque", text: $viewModel.brand)
            TextField("Modèle", text: $viewModel.model)
            TextField("Numéro de série", text: $viewModel.serialNumber)
            TextField("Prix d’achat (€)", text: $viewModel.purchasePrice)
                .keyboardType(.decimalPad)
            TextField("Année d’achat", text: $viewModel.purchaseYear)
                .keyboardType(.numberPad)
        }
    }
}
