import SwiftUI

struct TradeInConditionSection: View {
    @ObservedObject var viewModel: TradeInViewModel

    var body: some View {
        Section("État") {
            Picker("État", selection: $viewModel.selectedCondition) {
                ForEach(viewModel.conditions) { option in
                    Text(option.label).tag(option.value)
                }
            }
            Toggle("Appareil fonctionnel", isOn: $viewModel.functional)
            Toggle("Accessoires inclus", isOn: $viewModel.hasAccessories)
            Toggle("Preuve d’achat disponible", isOn: $viewModel.hasProofOfPurchase)
            TextEditor(text: $viewModel.description)
                .frame(minHeight: 120)
        }
    }
}
