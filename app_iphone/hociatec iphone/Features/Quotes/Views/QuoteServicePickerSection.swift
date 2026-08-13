import SwiftUI

struct QuoteServicePickerSection: View {
    let services: [QuoteService]
    let isLoading: Bool
    @Binding var searchText: String
    let onSelect: (QuoteService) -> Void

    var body: some View {
        Section {
            if isLoading {
                ProgressView("Chargement...")
            } else {
                TextField("Rechercher", text: $searchText)
                if services.isEmpty {
                    Text("Aucun service correspondant.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(services) { service in
                        Button {
                            onSelect(service)
                        } label: {
                            HStack {
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(service.title).fontWeight(.semibold)
                                    if let description = service.description, !description.isEmpty {
                                        Text(description)
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                            .lineLimit(2)
                                    }
                                }
                                Spacer()
                                Text(PriceFormatter.format(cents: service.priceCents))
                                    .foregroundStyle(.secondary)
                            }
                        }
                        .buttonStyle(.plain)
                    }
                }
            }
        }
    }
}
