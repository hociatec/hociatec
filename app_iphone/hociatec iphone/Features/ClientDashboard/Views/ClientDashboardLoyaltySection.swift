import SwiftUI

struct ClientDashboardLoyaltySection: View {
    @ObservedObject var viewModel: ClientDashboardViewModel

    var body: some View {
        Section("Fidélité") {
            LabeledContent("Points disponibles") {
                Text("\(viewModel.loyalty.points) pts")
                    .fontWeight(.semibold)
            }
            LabeledContent("Valeur convertible") {
                Text(PriceFormatter.format(cents: viewModel.loyalty.euroCents))
                    .fontWeight(.semibold)
            }
            LabeledContent("Points à convertir") {
                TextField("0", text: $viewModel.convertPoints)
                    .keyboardType(.numberPad)
                    .multilineTextAlignment(.trailing)
            }

            Text("\(PriceFormatter.format(cents: viewModel.convertedEuroCents)) en bon de réduction")
                .font(.footnote)
                .foregroundStyle(.secondary)

            Button("Convertir") {
                Task { await viewModel.convertLoyalty() }
            }
            .disabled(!viewModel.canConvert)
        }
    }
}
