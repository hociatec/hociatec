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
    @State private var activeSheet: RentalActionSheetState?
    @State private var activeFilter: RentalFilter = .all
    @Environment(\.openURL) private var openURL
    @Environment(\.scenePhase) private var scenePhase

    init(service: RentalServing) {
        self.service = service
        _viewModel = StateObject(wrappedValue: MyRentalsViewModel(service: service))
    }

    var body: some View {
        List {
            filterSection
            rentalSection(items: filteredRentals)
        }
        .navigationTitle("Mes locations")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .sheet(item: $activeSheet) { sheet in
            switch sheet.kind {
            case .extend:
                RentalRequestSheet(
                    rental: sheet.rental,
                    onCancel: { activeSheet = nil },
                    onSubmit: { requestedEndDate in
                        activeSheet = nil
                        Task {
                            await viewModel.requestChange(
                                for: sheet.rental,
                                action: .extend,
                                requestedEndDate: requestedEndDate
                            )
                        }
                    }
                )
            case .terminate:
                RentalTerminationSheet(
                    rental: sheet.rental,
                    onCancel: { activeSheet = nil },
                    onSubmit: { requestedEndDate, returnMode, returnRequestedDate in
                        activeSheet = nil
                        Task {
                            await viewModel.terminateRental(
                                for: sheet.rental,
                                requestedEndDate: requestedEndDate,
                                returnMode: returnMode,
                                returnRequestedDate: returnRequestedDate
                            )
                        }
                    }
                )
            }
        }
        .onChange(of: viewModel.checkoutURL) { _, newValue in
            guard let newValue else { return }
            openURL(newValue)
            viewModel.checkoutURL = nil
        }
        .onChange(of: scenePhase) { _, newValue in
            guard newValue == .active else { return }
            Task {
                await viewModel.load(force: true)
            }
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

    private var filterSection: some View {
        Section {
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
    private func rentalSection(items: [RentalItem]) -> some View {
        Section {
            if items.isEmpty {
                Text(emptyStateMessage)
                    .foregroundStyle(.secondary)
            } else {
                ForEach(items) { rental in
                    RentalRow(
                        rental: rental,
                        isSubmittingExtend: viewModel.submittingActionKey == "extend:\(rental.orderItemId)",
                        isSubmittingTerminate: viewModel.submittingActionKey == "terminate:\(rental.orderItemId)",
                        requestExtend: { activeSheet = RentalActionSheetState(rental: rental, kind: .extend) },
                        requestTerminate: { activeSheet = RentalActionSheetState(rental: rental, kind: .terminate) }
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
    let isSubmittingTerminate: Bool
    let requestExtend: () -> Void
    let requestTerminate: () -> Void

    private var isReturned: Bool {
        rental.returnPlan.status == "completed"
    }

    private var hasPendingRequest: Bool {
        rental.request.status == "pending" && rental.request.type != RentalRequestAction.extend.rawValue
    }

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

            if (rental.request.status == "pending" || rental.request.status == "pending_payment") && !isReturned {
                Text(pendingRequestLabel)
                    .font(.subheadline)
                    .foregroundStyle(.orange)
            }

            if rental.returnPlan.status != "none" {
                Text(returnLabel)
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }

            if !isReturned {
                HStack {
                    Button(isSubmittingExtend ? "Preparation..." : "Prolonger", action: requestExtend)
                        .buttonStyle(.bordered)
                        .disabled(isSubmittingExtend || isSubmittingTerminate || hasPendingRequest)

                    Button(isSubmittingTerminate ? "Envoi..." : "Terminer la location", action: requestTerminate)
                        .buttonStyle(.borderedProminent)
                        .disabled(isSubmittingExtend || isSubmittingTerminate)
                }
            }
        }
        .padding(.vertical, 4)
    }

    private var pendingRequestLabel: String {
        if rental.request.status == "pending_payment" {
            return "Paiement de prolongation en attente jusqu’au \(DatePresentation.formatAPIDay(rental.request.requestedEndDate))."
        }

        let actionLabel = rental.request.type == RentalRequestAction.extend.rawValue ? "prolongation" : "fin de location"
        let dateLabel = DatePresentation.formatAPIDay(rental.request.requestedEndDate)
        return "Demande en attente: \(actionLabel) jusqu’au \(dateLabel)"
    }

    private var returnLabel: String {
        if rental.returnPlan.status == "completed" {
            return "Matériel restitué. Cette location est clôturée."
        }

        let modeLabel = rental.returnPlan.mode == "pickup_home" ? "Récupération à domicile" : "Dépôt en boutique"
        return "\(modeLabel) prévu le \(DatePresentation.formatAPIDay(rental.returnPlan.requestedDate))"
    }
}

private struct RentalActionSheetState: Identifiable {
    enum Kind {
        case extend
        case terminate
    }

    let rental: RentalItem
    let kind: Kind

    var id: String {
        switch kind {
        case .extend:
            return "extend-\(rental.orderItemId)"
        case .terminate:
            return "terminate-\(rental.orderItemId)"
        }
    }
}

private struct RentalRequestSheet: View {
    let rental: RentalItem
    let onCancel: () -> Void
    let onSubmit: (String) -> Void

    @State private var requestedEndDate: Date
    private let minimumExtensionDate: Date

    init(
        rental: RentalItem,
        onCancel: @escaping () -> Void,
        onSubmit: @escaping (String) -> Void
    ) {
        self.rental = rental
        self.onCancel = onCancel
        self.onSubmit = onSubmit
        let calendar = Calendar(identifier: .gregorian)
        let today = calendar.startOfDay(for: Date())
        let tomorrow = calendar.date(byAdding: .day, value: 1, to: today) ?? today
        let baseDate = DatePresentation.parseAPIDay(rental.endDate)
            ?? DatePresentation.parseAPIDay(rental.startDate)
            ?? today
        let dayAfterCurrentEnd = calendar.date(byAdding: .day, value: 1, to: baseDate) ?? baseDate
        let minimumDate = max(tomorrow, dayAfterCurrentEnd)
        self.minimumExtensionDate = minimumDate
        _requestedEndDate = State(initialValue: minimumDate)
    }

    var body: some View {
        NavigationStack {
            Form {
                Section("Demande de prolongation") {
                    Text(rental.productName)
                    LabeledContent("Période actuelle") {
                        Text("\(DatePresentation.formatAPIDay(rental.startDate)) - \(DatePresentation.formatAPIDay(rental.endDate))")
                    }
                    LabeledContent("Nouvelle échéance", value: DateFormatters.frDay.string(from: requestedEndDate))
                    LocalizedDatePicker(
                        date: $requestedEndDate,
                        displayedComponents: [.date],
                        minimumDate: minimumExtensionDate,
                        style: .inline
                    )
                    .frame(maxWidth: .infinity, minHeight: 320)
                    Text("Choisissez la date exacte de nouvelle échéance. Le paiement sera calculé automatiquement selon la durée nécessaire.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
                submitSection
            }
            .navigationTitle("Prolonger")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Annuler", action: onCancel)
                }
            }
            .onChangeCompat(requestedEndDate) { newValue in
                if newValue < minimumExtensionDate {
                    requestedEndDate = minimumExtensionDate
                }
            }
        }
    }

    @ViewBuilder
    private var submitSection: some View {
        Section {
            ProductAddToCartButton(
                isLoading: false,
                isDisabled: false,
                label: "Continuer vers le paiement",
                action: {
                    onSubmit(DatePresentation.encodeAPIDay(requestedEndDate))
                }
            )
        }
    }
}

private struct RentalTerminationSheet: View {
    let rental: RentalItem
    let onCancel: () -> Void
    let onSubmit: (String, MyRentalsViewModel.ReturnMode, String) -> Void

    @State private var requestedEndDate: Date
    @State private var requestedDate: Date
    @State private var mode: MyRentalsViewModel.ReturnMode = .pickupHome
    private let minimumTerminationDate: Date

    init(
        rental: RentalItem,
        onCancel: @escaping () -> Void,
        onSubmit: @escaping (String, MyRentalsViewModel.ReturnMode, String) -> Void
    ) {
        self.rental = rental
        self.onCancel = onCancel
        self.onSubmit = onSubmit
        let calendar = Calendar(identifier: .gregorian)
        let today = calendar.startOfDay(for: Date())
        let rentalStartDate = DatePresentation.parseAPIDay(rental.startDate) ?? today
        let minimumDate = max(today, rentalStartDate)
        let fallbackDate = DatePresentation.parseAPIDay(rental.endDate) ?? minimumDate
        self.minimumTerminationDate = minimumDate
        _requestedEndDate = State(initialValue: max(minimumDate, fallbackDate))
        _requestedDate = State(initialValue: max(minimumDate, fallbackDate))
    }

    var body: some View {
        NavigationStack {
            Form {
                Section("Fin de location") {
                    Text(rental.productName)
                    LabeledContent("Date de fin", value: DateFormatters.frDay.string(from: requestedEndDate))
                    LocalizedDatePicker(
                        date: $requestedEndDate,
                        displayedComponents: [.date],
                        minimumDate: minimumTerminationDate,
                        maximumDate: DatePresentation.parseAPIDay(rental.endDate),
                        style: .inline
                    )
                    .frame(maxWidth: .infinity, minHeight: 320)
                }

                Section("Retour du materiel") {
                    Picker("Mode", selection: $mode) {
                        Text("Récupération à domicile").tag(MyRentalsViewModel.ReturnMode.pickupHome)
                        Text("Dépôt en boutique").tag(MyRentalsViewModel.ReturnMode.dropoffStore)
                    }
                    .pickerStyle(.inline)

                    LabeledContent("Date souhaitée", value: DateFormatters.frDay.string(from: requestedDate))
                    LocalizedDatePicker(
                        date: $requestedDate,
                        displayedComponents: [.date],
                        minimumDate: minimumTerminationDate,
                        maximumDate: requestedEndDate,
                        style: .inline
                    )
                    .frame(maxWidth: .infinity, minHeight: 320)
                }
                Section {
                    ProductAddToCartButton(
                        isLoading: false,
                        isDisabled: false,
                        label: "Valider",
                        action: {
                            onSubmit(
                                DatePresentation.encodeAPIDay(requestedEndDate),
                                mode,
                                DatePresentation.encodeAPIDay(requestedDate)
                            )
                        }
                    )
                }
            }
            .navigationTitle("Terminer")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Annuler", action: onCancel)
                }
            }
            .onChangeCompat(requestedEndDate) { newValue in
                if newValue < minimumTerminationDate {
                    requestedEndDate = minimumTerminationDate
                    return
                }
                if requestedDate > newValue {
                    requestedDate = newValue
                }
                if requestedDate < minimumTerminationDate {
                    requestedDate = minimumTerminationDate
                }
            }
            .onChangeCompat(requestedDate) { newValue in
                if newValue < minimumTerminationDate {
                    requestedDate = minimumTerminationDate
                    return
                }
                if newValue > requestedEndDate {
                    requestedDate = requestedEndDate
                }
            }
        }
    }
}
