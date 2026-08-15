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
            case .extend, .endEarly:
                RentalRequestSheet(
                    rental: sheet.rental,
                    action: sheet.kind == .extend ? .extend : .endEarly,
                    onCancel: { activeSheet = nil },
                    onSubmit: { requestedEndDate in
                        activeSheet = nil
                        Task {
                            await viewModel.requestChange(
                                for: sheet.rental,
                                action: sheet.kind == .extend ? .extend : .endEarly,
                                requestedEndDate: requestedEndDate
                            )
                        }
                    }
                )
            case .returnPlan:
                RentalReturnSheet(
                    rental: sheet.rental,
                    onCancel: { activeSheet = nil },
                    onSubmit: { mode, requestedDate in
                        activeSheet = nil
                        Task {
                            await viewModel.planReturn(for: sheet.rental, mode: mode, requestedDate: requestedDate)
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
                        isSubmittingEndEarly: viewModel.submittingActionKey == "end_early:\(rental.orderItemId)",
                        isSubmittingReturn: viewModel.submittingActionKey == "return:\(rental.orderItemId)",
                        requestExtend: { activeSheet = RentalActionSheetState(rental: rental, kind: .extend) },
                        requestEndEarly: { activeSheet = RentalActionSheetState(rental: rental, kind: .endEarly) },
                        requestReturn: { activeSheet = RentalActionSheetState(rental: rental, kind: .returnPlan) }
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
    let isSubmittingReturn: Bool
    let requestExtend: () -> Void
    let requestEndEarly: () -> Void
    let requestReturn: () -> Void

    private var isReturned: Bool {
        rental.returnPlan.status == "completed"
    }

    private var hasPendingRequest: Bool {
        rental.request.status == "pending" || rental.request.status == "pending_payment"
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
                    Button(isSubmittingExtend ? "Préparation..." : "Prolonger", action: requestExtend)
                        .buttonStyle(.bordered)
                        .disabled(isSubmittingExtend || isSubmittingEndEarly || isSubmittingReturn || hasPendingRequest)
                    Button(isSubmittingEndEarly ? "Envoi..." : "Anticiper la fin", action: requestEndEarly)
                        .buttonStyle(.bordered)
                        .disabled(isSubmittingExtend || isSubmittingEndEarly || isSubmittingReturn || hasPendingRequest)
                }

                Button(isSubmittingReturn ? "Envoi..." : "Organiser la restitution", action: requestReturn)
                    .buttonStyle(.bordered)
                    .disabled(isSubmittingExtend || isSubmittingEndEarly || isSubmittingReturn)
            }
        }
        .padding(.vertical, 4)
    }

    private var pendingRequestLabel: String {
        if rental.request.status == "pending_payment" {
            return "Paiement de prolongation en attente jusqu’au \(DatePresentation.formatAPIDay(rental.request.requestedEndDate))."
        }

        let actionLabel = rental.request.type == RentalRequestAction.extend.rawValue ? "prolongation" : "fin anticipée"
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

private struct RentalExtensionOption: Identifiable, Hashable {
    let totalMonths: Int
    let additionalMonths: Int
    let endDate: Date

    var id: Int { totalMonths }

    var durationLabel: String {
        "+\(additionalMonths) mois"
    }

    var totalLabel: String {
        "\(totalMonths) mois au total"
    }

    var dateLabel: String {
        DateFormatters.frDay.string(from: endDate)
    }
}

private struct RentalActionSheetState: Identifiable {
    enum Kind {
        case extend
        case endEarly
        case returnPlan
    }

    let rental: RentalItem
    let kind: Kind

    var id: String {
        switch kind {
        case .extend:
            return "extend-\(rental.orderItemId)"
        case .endEarly:
            return "end-early-\(rental.orderItemId)"
        case .returnPlan:
            return "return-\(rental.orderItemId)"
        }
    }
}

private struct RentalRequestSheet: View {
    let rental: RentalItem
    let action: RentalRequestAction
    let onCancel: () -> Void
    let onSubmit: (String) -> Void

    private let extensionOptions: [RentalExtensionOption]
    @State private var requestedEndDate: Date
    @State private var selectedExtensionMonths: Int?

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
        let generatedOptions = Self.buildExtensionOptions(for: rental)
        self.extensionOptions = generatedOptions
        _selectedExtensionMonths = State(initialValue: generatedOptions.first?.totalMonths)
        _requestedEndDate = State(initialValue: generatedOptions.first?.endDate ?? fallback)
    }

    var body: some View {
        NavigationStack {
            Form {
                Section(action == .extend ? "Demande de prolongation" : "Demande de fin anticipée") {
                    Text(rental.productName)
                    LabeledContent("Période actuelle") {
                        Text("\(DatePresentation.formatAPIDay(rental.startDate)) - \(DatePresentation.formatAPIDay(rental.endDate))")
                    }
                    if action == .extend {
                        if extensionOptions.isEmpty {
                            Text("Impossible de calculer une échéance valide pour cette location.")
                                .foregroundStyle(.secondary)
                        } else {
                            LabeledContent("Nouvelle échéance", value: DateFormatters.frDay.string(from: requestedEndDate))
                            Text("Choisissez une échéance mensuelle valide. La prolongation sera ensuite envoyée au paiement.")
                                .font(.footnote)
                                .foregroundStyle(.secondary)

                            ForEach(extensionOptions) { option in
                                Button {
                                    selectedExtensionMonths = option.totalMonths
                                    requestedEndDate = option.endDate
                                } label: {
                                    HStack(alignment: .center, spacing: 12) {
                                        VStack(alignment: .leading, spacing: 4) {
                                            Text(option.durationLabel)
                                                .font(.headline)
                                            Text("Jusqu’au \(option.dateLabel)")
                                                .font(.subheadline)
                                                .foregroundStyle(.secondary)
                                        }
                                        Spacer()
                                        Text(option.totalLabel)
                                            .font(.footnote.weight(.semibold))
                                            .foregroundStyle(.secondary)
                                        Image(systemName: selectedExtensionMonths == option.totalMonths ? "largecircle.fill.circle" : "circle")
                                            .foregroundStyle(selectedExtensionMonths == option.totalMonths ? .accent : .secondary)
                                    }
                                    .padding(.vertical, 6)
                                    .contentShape(Rectangle())
                                }
                                .buttonStyle(.plain)
                            }
                        }
                    } else {
                        LabeledContent("Nouvelle date", value: DateFormatters.frDay.string(from: requestedEndDate))
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Date de fin anticipée souhaitée")
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
                submitSection
            }
            .navigationTitle(action == .extend ? "Prolonger" : "Fin anticipée")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Annuler", action: onCancel)
                }
            }
        }
    }

    private var minimumDate: Date {
        DatePresentation.parseAPIDay(rental.startDate) ?? Date()
    }

    @ViewBuilder
    private var submitSection: some View {
        Section {
            ProductAddToCartButton(
                isLoading: false,
                isDisabled: action == .extend && extensionOptions.isEmpty,
                label: action == .extend ? "Continuer vers le paiement" : "Envoyer la demande",
                action: {
                    onSubmit(DatePresentation.encodeAPIDay(requestedEndDate))
                }
            )
        }
    }

    private static func buildExtensionOptions(for rental: RentalItem, limit: Int = 6) -> [RentalExtensionOption] {
        guard let startDate = DatePresentation.parseAPIDay(rental.startDate) else {
            return []
        }

        let currentMonths = max(1, rental.rentalMonths ?? 1)
        let calendar = Calendar(identifier: .gregorian)

        return (1...limit).compactMap { offset in
            let totalMonths = currentMonths + offset
            guard let monthAnchor = calendar.date(byAdding: .month, value: totalMonths, to: startDate),
                  let endDate = calendar.date(byAdding: .day, value: -1, to: monthAnchor) else {
                return nil
            }

            return RentalExtensionOption(
                totalMonths: totalMonths,
                additionalMonths: offset,
                endDate: endDate
            )
        }
    }
}

private struct RentalReturnSheet: View {
    let rental: RentalItem
    let onCancel: () -> Void
    let onSubmit: (MyRentalsViewModel.ReturnMode, String) -> Void

    @State private var requestedDate: Date
    @State private var mode: MyRentalsViewModel.ReturnMode = .pickupHome

    init(
        rental: RentalItem,
        onCancel: @escaping () -> Void,
        onSubmit: @escaping (MyRentalsViewModel.ReturnMode, String) -> Void
    ) {
        self.rental = rental
        self.onCancel = onCancel
        self.onSubmit = onSubmit
        _requestedDate = State(initialValue: DatePresentation.parseAPIDay(rental.endDate) ?? Date())
    }

    var body: some View {
        NavigationStack {
            Form {
                Section("Restitution") {
                    Text(rental.productName)
                    Picker("Mode", selection: $mode) {
                        Text("Récupération à domicile").tag(MyRentalsViewModel.ReturnMode.pickupHome)
                        Text("Dépôt en boutique").tag(MyRentalsViewModel.ReturnMode.dropoffStore)
                    }
                    .pickerStyle(.inline)

                    LabeledContent("Date souhaitée", value: DateFormatters.frDay.string(from: requestedDate))
                    LocalizedDatePicker(
                        date: $requestedDate,
                        displayedComponents: [.date],
                        minimumDate: DatePresentation.parseAPIDay(rental.startDate) ?? Date(),
                        style: .inline
                    )
                    .frame(maxWidth: .infinity, minHeight: 320)
                }
                Section {
                    ProductAddToCartButton(
                        isLoading: false,
                        isDisabled: false,
                        label: "Planifier la restitution",
                        action: {
                            onSubmit(mode, DatePresentation.encodeAPIDay(requestedDate))
                        }
                    )
                }
            }
            .navigationTitle("Restitution")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Annuler", action: onCancel)
                }
            }
        }
    }
}
