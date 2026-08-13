import SwiftUI

struct ClientDashboardLoyaltySection: View {
    @ObservedObject var viewModel: ClientDashboardViewModel

    var body: some View {
        Section("Fidélité") {
            LabeledContent("Points disponibles") {
                VStack(alignment: .trailing, spacing: 2) {
                    Text("\(viewModel.loyalty.points) pts")
                        .fontWeight(.semibold)
                    Text("\(viewModel.loyalty.points)")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }
            LabeledContent("Valeur convertible") {
                VStack(alignment: .trailing, spacing: 2) {
                    Text(PriceFormatter.format(cents: viewModel.loyalty.euroCents))
                        .fontWeight(.semibold)
                    Text("\(viewModel.loyalty.euroCents / 100)")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
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

            if let message = viewModel.conversionMessage, !message.isEmpty {
                Text(message)
                    .font(.footnote)
                    .foregroundStyle(.green)
            }
        }
    }
}
