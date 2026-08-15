import SwiftUI

struct MyRentalsView: View {
    private enum RentalFilter: String, CaseIterable, Identifiable {
        case all
        case upcoming
        case past

        var id: String { rawValue }

        var title: String {
            switch self {
            case .all: return "Toutes"
            case .upcoming: return "À venir"
            case .past: return "Passées"
            }
        }
    }

    private let service: RentalServing
    @StateObject private var viewModel: MyRentalsViewModel
    @State private var activeSheet: RentalRequestSheetState?
    @State private var activeFilter: RentalFilter = .all

    init(service: RentalServing) {
        self.service = service
        _viewModel = StateObject(wrappedValue: MyRentalsViewModel(service: service))
    }

    var body: some View {
        List {
            statsSection
            filterSection
            rentalSection(title: "Mes locations", items: filteredRentals)
        }
        .navigationTitle("Mes locations")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .sheet(item: $activeSheet) { sheet in
            RentalRequestSheet(
                rental: sheet.rental,
                action: sheet.action,
                onCancel: { activeSheet = nil },
                onSubmit: { requestedEndDate in
                    activeSheet = nil
                    Task {
                        await viewModel.requestChange(
                            for: sheet.rental,
                            action: sheet.action,
                            requestedEndDate: requestedEndDate
                        )
                    }
                }
            )
        }
        .overlay(alignment: .bottom) {
            if viewModel.isLoading && !(viewModel.upcoming.isEmpty && viewModel.past.isEmpty) {
                InlineLoadingStatus(message: "Actualisation des locations…")
                    .padding(.horizontal, 16)
                    .padding(.bottom, 8)
                    .background(.thinMaterial, in: Capsule())
                    .padding(.bottom, 8)
            }
        }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.successMessage)
    }

    private var statsSection: some View {
        Section("Résumé") {
            LabeledContent("À venir / en cours", value: "\(viewModel.upcoming.count)")
            LabeledContent("Terminées", value: "\(viewModel.past.count)")
            LabeledContent("Total", value: "\(viewModel.upcoming.count + viewModel.past.count)")
        }
    }

    private var filterSection: some View {
        Section("Filtrer") {
            Picker("Afficher", selection: $activeFilter) {
                ForEach(RentalFilter.allCases) { filter in
                    Text(filterLabel(for: filter)).tag(filter)
                }
            }
            .pickerStyle(.segmented)
            .accessibilityLabel("Filtrer mes locations")
        }
    }

    private var filteredRentals: [RentalItem] {
        switch activeFilter {
        case .all:
            return viewModel.upcoming + viewModel.past
        case .upcoming:
            return viewModel.upcoming
        case .past:
            return viewModel.past
        }
    }

    @ViewBuilder
    private func rentalSection(title: String, items: [RentalItem]) -> some View {
        Section(title) {
            if items.isEmpty {
                Text(emptyStateMessage)
                    .foregroundStyle(.secondary)
            } else {
                ForEach(items) { rental in
                    RentalRow(
                        rental: rental,
                        isSubmittingExtend: viewModel.submittingActionKey == "extend:\(rental.orderItemId)",
                        isSubmittingEndEarly: viewModel.submittingActionKey == "end_early:\(rental.orderItemId)",
                        requestExtend: { activeSheet = RentalRequestSheetState(rental: rental, action: .extend) },
                        requestEndEarly: { activeSheet = RentalRequestSheetState(rental: rental, action: .endEarly) }
                    )
                }
            }
        }
    }

    private func filterLabel(for filter: RentalFilter) -> String {
        switch filter {
        case .all:
            return "Toutes (\(viewModel.upcoming.count + viewModel.past.count))"
        case .upcoming:
            return "À venir (\(viewModel.upcoming.count))"
        case .past:
            return "Passées (\(viewModel.past.count))"
        }
    }

    private var emptyStateMessage: String {
        switch activeFilter {
        case .all:
            return "Aucune location disponible."
        case .upcoming:
            return "Aucune location à venir ou en cours."
        case .past:
            return "Aucune location terminée."
        }
    }
}

private struct RentalRow: View {
    let rental: RentalItem
    let isSubmittingExtend: Bool
    let isSubmittingEndEarly: Bool
    let requestExtend: () -> Void
    let requestEndEarly: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text(rental.productName)
                .font(.headline)
            Text(rental.timelineStatusLabel)
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Text("Commande \(rental.orderNumber ?? "-")")
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Text("Du \(DatePresentation.formatAPIDay(rental.startDate)) au \(DatePresentation.formatAPIDay(rental.endDate))")
                .font(.subheadline)
            Text("\(rental.rentalMonths ?? 0) mois · \(PriceFormatter.format(cents: rental.linePriceCents))")
                .font(.subheadline)
                .foregroundStyle(.secondary)

            if rental.request.status == "pending" {
                Text(pendingRequestLabel)
                    .font(.subheadline)
                    .foregroundStyle(.orange)
            }

            HStack {
                Button(isSubmittingExtend ? "Envoi..." : "Demander une prolongation", action: requestExtend)
                    .buttonStyle(.bordered)
                    .disabled(isSubmittingExtend || isSubmittingEndEarly)
                Button(isSubmittingEndEarly ? "Envoi..." : "Anticiper la fin", action: requestEndEarly)
                    .buttonStyle(.bordered)
                    .disabled(isSubmittingExtend || isSubmittingEndEarly)
            }
        }
        .padding(.vertical, 4)
    }

    private var pendingRequestLabel: String {
        let actionLabel = rental.request.type == RentalRequestAction.extend.rawValue ? "prolongation" : "fin anticipée"
        let dateLabel = DatePresentation.formatAPIDay(rental.request.requestedEndDate)
        return "Demande en attente: \(actionLabel) jusqu’au \(dateLabel)"
    }
}

private struct RentalRequestSheetState: Identifiable {
    let rental: RentalItem
    let action: RentalRequestAction

    var id: String {
        "\(action.rawValue)-\(rental.orderItemId)"
    }
}

private struct RentalRequestSheet: View {
    let rental: RentalItem
    let action: RentalRequestAction
    let onCancel: () -> Void
    let onSubmit: (String) -> Void

    @State private var requestedEndDate: Date

    init(
        rental: RentalItem,
        action: RentalRequestAction,
        onCancel: @escaping () -> Void,
        onSubmit: @escaping (String) -> Void
    ) {
        self.rental = rental
        self.action = action
        self.onCancel = onCancel
        self.onSubmit = onSubmit
        let fallback = DatePresentation.parseAPIDay(rental.endDate)
            ?? DatePresentation.parseAPIDay(rental.startDate)
            ?? Date()
        _requestedEndDate = State(initialValue: fallback)
    }

    var body: some View {
        NavigationStack {
            Form {
                Section(action == .extend ? "Demande de prolongation" : "Demande de fin anticipée") {
                    Text(rental.productName)
                    LabeledContent("Période actuelle") {
                        Text("\(DatePresentation.formatAPIDay(rental.startDate)) - \(DatePresentation.formatAPIDay(rental.endDate))")
                    }
                    LabeledContent("Nouvelle date", value: DateFormatters.frDay.string(from: requestedEndDate))
                    VStack(alignment: .leading, spacing: 8) {
                        Text(action == .extend ? "Nouvelle date de fin souhaitée (jj/mm/aaaa)" : "Date de fin anticipée souhaitée (jj/mm/aaaa)")
                            .font(.headline)
                        LocalizedDatePicker(
                            date: $requestedEndDate,
                            displayedComponents: [.date],
                            minimumDate: minimumDate,
                            style: .inline
                        )
                        .frame(maxWidth: .infinity, minHeight: 320)
                    }
                }
            }
            .navigationTitle(action == .extend ? "Prolonger" : "Fin anticipée")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Annuler", action: onCancel)
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Envoyer") {
                        onSubmit(DatePresentation.encodeAPIDay(requestedEndDate))
                    }
                }
            }
        }
    }

    private var minimumDate: Date {
        DatePresentation.parseAPIDay(rental.startDate) ?? Date()
    }
}
