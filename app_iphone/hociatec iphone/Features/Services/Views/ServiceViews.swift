import SwiftUI
import Combine

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

struct ServicesCatalogView: View {
    let serviceCatalog: ServiceCatalogServing
    @StateObject private var viewModel: ServicesCatalogViewModel

    init(api: ServiceCatalogServing) {
        self.serviceCatalog = api
        _viewModel = StateObject(wrappedValue: ServicesCatalogViewModel(serviceCatalog: api))
    }

    var body: some View {
        List {
            Section {
                TextField("Rechercher un service", text: $viewModel.searchText)
                Button("Rechercher") {
                    viewModel.applySearch()
                    Task { await viewModel.load() }
                }
            }

            Section("Services") {
                if viewModel.isLoading && viewModel.services.isEmpty {
                    ProgressView("Chargement des services...")
                } else if let error = viewModel.error {
                    Text(error).foregroundStyle(.red)
                } else if viewModel.services.isEmpty {
                    Text(viewModel.appliedSearch.isEmpty ? "Aucun service publié pour le moment." : "Aucun service ne correspond à cette recherche.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.services) { service in
                        NavigationLink {
                            ServiceDetailView(api: serviceCatalog, serviceID: service.id)
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
                                    Text(service.durationLabel ?? "Sur étude")
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
                                }
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }

            if viewModel.totalPages > 1 {
                Section {
                    HStack {
                        Button("Précédent") {
                            viewModel.previousPage()
                            Task { await viewModel.load() }
                        }
                        .disabled(viewModel.page <= 1 || viewModel.isLoading)
                        Spacer()
                        Text("\(viewModel.page)/\(viewModel.totalPages)")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                        Spacer()
                        Button("Suivant") {
                            viewModel.nextPage()
                            Task { await viewModel.load() }
                        }
                        .disabled(viewModel.page >= viewModel.totalPages || viewModel.isLoading)
                    }
                }
            }
        }
        .navigationTitle("Services")
        .task { await viewModel.load() }
    }
}

@MainActor
private final class ServiceDetailViewModel: ObservableObject {
    @Published var service: QuoteService?
    @Published var isLoading = false
    @Published var error: String?

    private let serviceCatalog: ServiceCatalogServing
    private let serviceID: Int

    init(serviceCatalog: ServiceCatalogServing, serviceID: Int) {
        self.serviceCatalog = serviceCatalog
        self.serviceID = serviceID
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            service = try await serviceCatalog.publicService(id: serviceID)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

@MainActor
private final class ServicesCatalogViewModel: ObservableObject {
    @Published var services: [QuoteService] = []
    @Published var page = 1
    @Published var totalPages = 1
    @Published var searchText = ""
    @Published var appliedSearch = ""
    @Published var isLoading = false
    @Published var error: String?

    private let serviceCatalog: ServiceCatalogServing

    init(serviceCatalog: ServiceCatalogServing) {
        self.serviceCatalog = serviceCatalog
    }

    func applySearch() {
        appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        page = 1
    }

    func previousPage() {
        guard page > 1 else { return }
        page -= 1
    }

    func nextPage() {
        guard page < totalPages else { return }
        page += 1
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await serviceCatalog.quoteServices(page: page, perPage: 7, query: appliedSearch.isEmpty ? nil : appliedSearch)
            services = data.items
            totalPages = max(1, data.meta?.totalPages ?? 1)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private func serviceBillingModeLabel(_ value: String?) -> String {
    let normalized = (value ?? "")
        .folding(options: .diacriticInsensitive, locale: .current)
        .trimmingCharacters(in: .whitespacesAndNewlines)
        .lowercased()

    switch normalized {
    case "", "prix fixe":
        return "Prix fixe"
    case "heure", "horaire":
        return "Horaire"
    case "jour":
        return "À la journée"
    case "intervention":
        return "Par intervention"
    case "audit":
        return "Audit"
    case "installation":
        return "Installation"
    case "maintenance":
        return "Maintenance"
    default:
        return value?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? (value ?? "Prix fixe") : "Prix fixe"
    }
}
