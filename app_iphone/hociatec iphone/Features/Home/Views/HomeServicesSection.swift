import SwiftUI

struct HomeServicesSection: View {
    @EnvironmentObject private var container: AppContainer
    @ObservedObject var home: HomeViewModel

    var body: some View {
        Section("Services") {
            if home.isLoading && home.services.isEmpty {
                ProgressView("Chargement...")
                    .frame(maxWidth: .infinity, alignment: .center)
            } else if let error = home.error, home.services.isEmpty {
                Text(error)
                    .foregroundStyle(.red)
            } else if home.services.isEmpty {
                Text("Aucun service mis en avant pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(home.services.prefix(6)) { service in
                    VStack(alignment: .leading, spacing: 6) {
                        NavigationLink {
                            ServiceDetailView(api: container.services.serviceCatalog, serviceID: service.id)
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
                        if let durationLabel = service.durationLabel, !durationLabel.isEmpty {
                            Text("Durée : \(durationLabel)")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                        }
                    }
                    .padding(.vertical, 4)
                    .accessibilityElement(children: .contain)
                }
            }
        }
    }
}
