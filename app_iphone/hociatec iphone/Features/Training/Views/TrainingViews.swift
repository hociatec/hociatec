import Foundation
import SwiftUI
import Combine

struct TrainingsCatalogView: View {
    let service: TrainingServing
    @StateObject private var viewModel: TrainingsCatalogViewModel

    init(api: TrainingServing) {
        self.service = api
        _viewModel = StateObject(wrappedValue: TrainingsCatalogViewModel(service: api))
    }

    var body: some View {
        List {
            Section {
                TextField("Rechercher une formation", text: $viewModel.searchText)
                if !viewModel.categories.isEmpty {
                    Picker("Catégorie", selection: $viewModel.selectedCategorySlug) {
                        Text("Toutes").tag("")
                        ForEach(viewModel.categories) { category in
                            Text(category.name).tag(category.slug)
                        }
                    }
                }
                Button("Rechercher") {
                    viewModel.applySearch()
                    Task { await viewModel.load() }
                }
            }

            Section("Formations") {
                if viewModel.isLoading && viewModel.trainings.isEmpty {
                    ProgressView("Chargement des formations...")
                } else if let error = viewModel.error {
                    Text(error).foregroundStyle(.red)
                } else if viewModel.trainings.isEmpty {
                    Text("Aucune formation publiée pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.trainings) { training in
                        NavigationLink {
                            TrainingDetailView(api: service, slug: training.slug)
                        } label: {
                            VStack(alignment: .leading, spacing: 8) {
                                Text(training.title)
                                    .fontWeight(.semibold)
                                Text(training.objective ?? training.shortDescription ?? "Formation accompagnée avec feuille de route pratique.")
                                    .lineLimit(3)
                                    .foregroundStyle(.secondary)
                                HStack {
                                    Text(training.categoryDetails?.name ?? training.category)
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
                                    Spacer()
                                    Text(PriceFormatter.format(cents: training.priceCents))
                                        .font(.footnote)
                                        .fontWeight(.semibold)
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
        .navigationTitle("Formations")
        .task {
            await viewModel.loadCategoriesIfNeeded()
            await viewModel.load()
        }
        .onChangeCompat(viewModel.selectedCategorySlug) { _ in
            viewModel.page = 1
            Task { await viewModel.load() }
        }
    }
}

struct TrainingDetailView: View {
    @StateObject private var viewModel: TrainingDetailViewModel

    init(api: TrainingServing, slug: String) {
        _viewModel = StateObject(wrappedValue: TrainingDetailViewModel(service: api, slug: slug))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.training == nil {
                Section {
                    ProgressView("Chargement de la formation...")
                }
            } else if let error = viewModel.error, viewModel.training == nil {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            } else if let training = viewModel.training {
                Section {
                    VStack(alignment: .leading, spacing: 10) {
                        Text(training.title)
                            .font(.title3.weight(.semibold))
                        Text(training.objective ?? training.shortDescription ?? "Formation accompagnée avec feuille de route pratique.")
                            .foregroundStyle(.secondary)
                        LabeledContent("Catégorie", value: training.categoryDetails?.name ?? training.category)
                        LabeledContent("Modalité", value: nonEmptyText(training.availableFormatDetails.map(\.label).joined(separator: ", ")) ?? "À confirmer")
                        LabeledContent("Durée", value: trainingDurationLabel(training.durationMinutes))
                        LabeledContent("Tarif", value: PriceFormatter.format(cents: training.priceCents))
                        if let audience = nonEmptyText(training.audience) {
                            LabeledContent("Public concerné", value: audience)
                        }
                    }
                    .padding(.vertical, 4)
                }

                Section("Feuille de route") {
                    if training.roadmap.isEmpty {
                        Text("Le programme détaillé sera communiqué avec les informations de session.")
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(training.roadmap.sorted { $0.position < $1.position }) { item in
                            VStack(alignment: .leading, spacing: 4) {
                                Text("\(item.position). \(item.title)")
                                    .fontWeight(.semibold)
                            }
                            .padding(.vertical, 2)
                        }
                    }
                }

                Section("Sessions") {
                    if viewModel.sessions.isEmpty {
                        Text("Aucune session ouverte pour le moment.")
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(viewModel.sessions) { session in
                            VStack(alignment: .leading, spacing: 8) {
                                HStack {
                                    Text(session.formatLabel)
                                        .fontWeight(.semibold)
                                    Spacer()
                                    Text(session.statusLabel)
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
                                }
                                LabeledContent("Début", value: trainingDateTimeFormatter.string(from: session.startsAt))
                                LabeledContent("Fin", value: trainingDateTimeFormatter.string(from: session.endsAt))
                                LabeledContent("Places restantes", value: "\(max(0, session.remainingSeats))/\(session.capacity)")
                                if let location = nonEmptyText(session.location) {
                                    LabeledContent("Lieu", value: location)
                                }
                                if let meetingURL = nonEmptyText(session.meetingUrl) {
                                    Link(destination: URL(string: meetingURL) ?? URL(string: "https://hociatec.fr/formations/\(training.slug)")!) {
                                        Label("Lien de session", systemImage: "link")
                                    }
                                }
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }
        }
        .navigationTitle("Formation")
        .task { await viewModel.load() }
    }
}

@MainActor
private final class TrainingsCatalogViewModel: ObservableObject {
    @Published var trainings: [Training] = []
    @Published var categories: [TrainingCategory] = []
    @Published var selectedCategorySlug = ""
    @Published var page = 1
    @Published var totalPages = 1
    @Published var searchText = ""
    @Published var appliedSearch = ""
    @Published var isLoading = false
    @Published var error: String?

    private let service: TrainingServing

    init(service: TrainingServing) {
        self.service = service
    }

    func applySearch() {
        appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        page = 1
    }

    func previousPage() { guard page > 1 else { return }; page -= 1 }
    func nextPage() { guard page < totalPages else { return }; page += 1 }

    func loadCategoriesIfNeeded() async {
        guard categories.isEmpty else { return }
        do {
            categories = try await service.trainingCategories().filter(\.isActive)
        } catch {
            if self.error == nil {
                self.error = error.localizedDescription
            }
        }
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await service.trainings(page: page, perPage: 8, query: appliedSearch.isEmpty ? nil : appliedSearch, category: selectedCategorySlug.isEmpty ? nil : selectedCategorySlug)
            trainings = data.items
            totalPages = max(1, data.meta.totalPages)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

@MainActor
private final class TrainingDetailViewModel: ObservableObject {
    @Published var training: Training?
    @Published var sessions: [TrainingSession] = []
    @Published var isLoading = false
    @Published var error: String?

    private let service: TrainingServing
    private let slug: String

    init(service: TrainingServing, slug: String) {
        self.service = service
        self.slug = slug
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await service.training(slug: slug)
            training = data.training
            sessions = data.sessions
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private func nonEmptyText(_ value: String?) -> String? {
    guard let trimmed = value?.trimmingCharacters(in: .whitespacesAndNewlines), !trimmed.isEmpty else {
        return nil
    }
    return trimmed
}

private func trainingDurationLabel(_ minutes: Int) -> String {
    if minutes >= 60 {
        let hours = Double(minutes) / 60.0
        if hours.rounded() == hours {
            return "\(Int(hours)) h"
        }
        return String(format: "%.1f h", hours).replacingOccurrences(of: ".", with: ",")
    }
    return "\(minutes) min"
}

private let trainingDateTimeFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .medium
    formatter.timeStyle = .short
    return formatter
}()
