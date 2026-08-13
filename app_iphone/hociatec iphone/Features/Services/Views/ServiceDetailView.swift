import SwiftUI

struct ServiceDetailView: View {
    let serviceCatalog: ServiceCatalogServing
    let serviceID: Int
    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel: ServiceDetailViewModel

    init(api: ServiceCatalogServing, serviceID: Int) {
        self.serviceCatalog = api
        self.serviceID = serviceID
        _viewModel = StateObject(wrappedValue: ServiceDetailViewModel(serviceCatalog: api, serviceID: serviceID))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.service == nil {
                Section {
                    ProgressView("Chargement du service...")
                        .frame(maxWidth: .infinity, alignment: .center)
                }
            } else if let error = viewModel.error {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            } else if let service = viewModel.service {
                Section {
                    if let imageURL = serviceCatalog.assetURL(for: service.imageUrl) {
                        AsyncImage(url: imageURL) { phase in
                            switch phase {
                            case .success(let image):
                                image
                                    .resizable()
                                    .scaledToFit()
                                    .frame(maxWidth: .infinity, maxHeight: 220)
                                    .clipShape(RoundedRectangle(cornerRadius: 16))
                            case .failure:
                                servicePlaceholder
                            default:
                                ProgressView()
                                    .frame(maxWidth: .infinity, minHeight: 180)
                            }
                        }
                        .listRowInsets(EdgeInsets())
                    } else {
                        servicePlaceholder
                    }

                    VStack(alignment: .leading, spacing: 10) {
                        Text(service.title)
                            .font(.title2)
                            .fontWeight(.bold)
                        Text(service.description?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false
                            ? (service.description ?? "")
                            : "Les informations détaillées de ce service seront précisées avec votre besoin.")
                            .foregroundStyle(.secondary)
                    }
                    .padding(.top, 8)
                }

                Section {
                    HStack(spacing: 12) {
                        serviceFactCard(
                            title: "Base tarifaire",
                            value: PriceFormatter.format(cents: service.priceCents)
                        )
                        serviceFactCard(
                            title: "Facturation",
                            value: serviceBillingModeLabel(service.unit)
                        )
                    }
                    HStack(spacing: 12) {
                        serviceFactCard(
                            title: "Durée estimée",
                            value: service.durationLabel ?? "Sur étude"
                        )
                        serviceFactCard(
                            title: "TVA",
                            value: "\(Int(service.vatRate.rounded())) %"
                        )
                    }
                }

                Section("Actions") {
                    NavigationLink {
                        QuoteRequestView(viewModel: container.makeQuoteViewModel())
                    } label: {
                        Label("Demander un devis", systemImage: "doc.badge.plus")
                    }

                    NavigationLink {
                        AppointmentBookingView(service: container.services.appointments)
                    } label: {
                        Label("Prendre rendez-vous", systemImage: "calendar.badge.plus")
                    }
                }
            }
        }
        .navigationTitle(viewModel.service?.title ?? "Service")
        .navigationBarTitleDisplayMode(.inline)
        .task { await viewModel.load() }
    }

    private var servicePlaceholder: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 16)
                .fill(Color.gray.opacity(0.08))
            Image(systemName: "wrench.and.screwdriver")
                .font(.system(size: 42))
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, minHeight: 180)
    }

    private func serviceFactCard(title: String, value: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.headline)
                .fontWeight(.semibold)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(14)
        .background(Color(.secondarySystemBackground))
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }
}
