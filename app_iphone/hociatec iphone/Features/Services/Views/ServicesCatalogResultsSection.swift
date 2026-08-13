import SwiftUI

struct ServicesCatalogResultsSection: View {
    let serviceCatalog: ServiceCatalogServing
    @ObservedObject var viewModel: ServicesCatalogViewModel

    var body: some View {
        Section("Services") {
            if viewModel.isLoading && viewModel.services.isEmpty {
                ProgressView("Chargement des services...")
            } else if let error = viewModel.error {
                Text(error).foregroundStyle(.red)
            } else if viewModel.services.isEmpty {
                Text(
                    viewModel.appliedSearch.isEmpty
                        ? "Aucun service publié pour le moment."
                        : "Aucun service ne correspond à cette recherche."
                )
                .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.services) { service in
                    NavigationLink {
                        ServiceDetailView(api: serviceCatalog, serviceID: service.id)
                    } label: {
                        ServiceCatalogRow(service: service)
                    }
                }
            }
        }
    }
}

private struct ServiceCatalogRow: View {
    let service: QuoteService

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(service.title)
                .fontWeight(.semibold)
            if let description = service.description, !description.isEmpty {
                Text(description)
                    .lineLimit(2)
                    .foregroundStyle(.secondary)
            }
            HStack {
                Text(PriceFormatter.format(cents: service.priceCents))
                    .font(.footnote)
                    .fontWeight(.semibold)
                Spacer()
                Text(service.durationLabel ?? "Sur étude")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
        .padding(.vertical, 4)
    }
}
