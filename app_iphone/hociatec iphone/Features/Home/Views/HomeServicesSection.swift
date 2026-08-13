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
                    NavigationLink {
                        ServiceDetailView(api: container.services.serviceCatalog, serviceID: service.id)
                    } label: {
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
                                if let durationLabel = service.durationLabel, !durationLabel.isEmpty {
                                    Text(durationLabel)
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
                                }
                            }
                        }
                        .padding(.vertical, 4)
                    }
                }
            }
        }
    }
}
