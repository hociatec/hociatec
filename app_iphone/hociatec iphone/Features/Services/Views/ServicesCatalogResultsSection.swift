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
                    ServiceCatalogRow(serviceCatalog: serviceCatalog, service: service)
                }
            }
        }
    }
}

private struct ServiceCatalogRow: View {
    let serviceCatalog: ServiceCatalogServing
    let service: QuoteService

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            NavigationLink {
                ServiceDetailView(api: serviceCatalog, serviceID: service.id)
            } label: {
                Text(service.title)
                    .fontWeight(.semibold)
                    .multilineTextAlignment(.leading)
                    .frame(maxWidth: .infinity, alignment: .leading)
            }
            .buttonStyle(.plain)
            .accessibilityAddTraits(.isHeader)

            if let description = service.description, !description.isEmpty {
                Text(description)
                    .lineLimit(2)
                    .foregroundStyle(.secondary)
            }
            Text("Mode de facturation : \(serviceBillingModeLabel(service.unit))")
                .font(.footnote)
                .foregroundStyle(.secondary)
            Text("Prix HT : \(PriceFormatter.format(cents: service.priceCents))")
                .font(.footnote)
                .fontWeight(.semibold)
            Text("Durée : \(service.durationLabel ?? "Sur étude")")
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
        .accessibilityElement(children: .contain)
    }
}
