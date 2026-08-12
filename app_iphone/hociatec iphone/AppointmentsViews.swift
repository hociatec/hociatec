import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct AppointmentBookingView: View {
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var viewModel: AppointmentBookingViewModel
    @Environment(\.dismiss) private var dismiss
    @State private var startDate = Date()

    private let api: APIClient

    init(api: APIClient) {
        self.api = api
        _viewModel = StateObject(wrappedValue: AppointmentBookingViewModel(api: api))
    }

    var body: some View {
        Form {
            if !account.isLoggedIn {
                Section {
                    Text("Vous pouvez choisir une prestation et un créneau sans compte. La connexion est requise seulement pour confirmer.")
                        .foregroundStyle(.secondary)
                }
            }

            Section {
                if viewModel.prestations.isEmpty && viewModel.isLoading {
                    ProgressView("Chargement des prestations...")
                } else if viewModel.prestations.isEmpty {
                    Text("Aucune prestation disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    Picker("Prestation", selection: $viewModel.selectedPrestationId) {
                        ForEach(viewModel.prestations) { prestation in
                            Text(prestation.name)
                                .tag(Optional(prestation.id))
                        }
                    }
                    if let selected = viewModel.prestations.first(where: { $0.id == viewModel.selectedPrestationId }) {
                        HStack {
                            Label("\(selected.durationMinutes) min", systemImage: "clock")
                                .foregroundStyle(.secondary)
                            Spacer()
                            Text(PriceFormatter.format(cents: selected.priceCents))
                                .fontWeight(.semibold)
                        }
                    }
                }
            }

            Section {
                VStack(alignment: .leading, spacing: 6) {
                    Text("À partir du")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                    NumericDatePicker(date: $startDate)
                }
                Text("Recherche sur les 14 prochains jours.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            Section {
                if viewModel.isLoading && viewModel.slots.isEmpty {
                    ProgressView("Recherche des créneaux...")
                } else if let error = viewModel.error {
                    Text(error)
                        .foregroundStyle(.red)
                } else if viewModel.slots.isEmpty {
                    Text("Aucun créneau disponible sur la période.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(sortedDays, id: \.self) { day in
                        let slots = slotsByDay[day] ?? []
                        VStack(alignment: .leading, spacing: 8) {
                            Text(dayFormatter.string(from: day))
                                .font(.headline)
                            ForEach(slots) { slot in
                                NavigationLink {
                                    AppointmentConfirmationView(viewModel: viewModel, slot: slot)
                                } label: {
                                    HStack {
                                        VStack(alignment: .leading) {
                                            Text(timeRange(for: slot))
                                                .fontWeight(.semibold)
                                            if let selected = viewModel.prestations.first(where: { $0.id == viewModel.selectedPrestationId }) {
                                                Text(PriceFormatter.format(cents: selected.priceCents))
                                                    .font(.footnote)
                                                    .foregroundStyle(.secondary)
                                            }
                                        }
                                        Spacer()
                                        Image(systemName: "chevron.right")
                                            .foregroundStyle(.secondary)
                                    }
                                }
                            }
                        }
                        .padding(.vertical, 6)
                    }
                }
            }

            if let success = viewModel.successMessage {
                Section {
                    Label(success, systemImage: "checkmark.seal.fill")
                        .foregroundStyle(.green)
                }
            }
        }
        .navigationTitle("Rendez-vous")
        .task { await viewModel.initialize(startDate: startDate) }
        .onChangeCompat(viewModel.selectedPrestationId) { _ in
            Task { await viewModel.loadSlots(startDate: startDate) }
        }
        .onChangeCompat(startDate) { newDate in
            Task { await viewModel.loadSlots(startDate: newDate) }
        }
        .onChangeCompat(viewModel.successMessage) { value in
            guard value != nil else { return }
            Task {
                try? await Task.sleep(nanoseconds: 1_000_000_000)
                dismiss()
            }
        }
        .environment(\.locale, Locale(identifier: "fr_FR"))
        .environment(\.calendar, Calendar(identifier: .gregorian))
    }

    private var slotsByDay: [Date: [AppointmentSlot]] {
        let cal = Calendar(identifier: .gregorian)
        let startLocal = cal.startOfDay(for: startDate)
        let filtered = viewModel.slots.filter { cal.startOfDay(for: $0.startAt) >= startLocal }
        return Dictionary(grouping: filtered) { slot in
            cal.startOfDay(for: slot.startAt)
        }
    }

    private var sortedDays: [Date] {
        slotsByDay.keys.sorted()
    }

    private func timeRange(for slot: AppointmentSlot) -> String {
        "\(timeFormatter.string(from: slot.startAt)) - \(timeFormatter.string(from: slot.endAt))"
    }
}

struct MyAppointmentsView: View {
    private let api: APIClient
    @StateObject private var viewModel: MyAppointmentsViewModel

    private enum TabFilter: String, CaseIterable, Identifiable {
        case upcoming = "À venir"
        case past = "Passés"
        case cancelled = "Annulés"
        var id: String { rawValue }
        var label: String { rawValue }
    }

    @State private var tab: TabFilter = .upcoming

    init(api: APIClient) {
        self.api = api
        _viewModel = StateObject(wrappedValue: MyAppointmentsViewModel(api: api))
    }

    private var upcomingFiltered: [AppointmentSummary] {
        viewModel.upcoming.filter { !$0.isCancelledStatus }.sorted { $0.startAt < $1.startAt }
    }

    private var pastFiltered: [AppointmentSummary] {
        viewModel.past.filter { !$0.isCancelledStatus }.sorted { $0.startAt > $1.startAt }
    }

    private var cancelledAppointments: [AppointmentSummary] {
        (viewModel.upcoming + viewModel.past)
            .filter { $0.isCancelledStatus }
            .sorted { $0.startAt > $1.startAt }
    }

    private var nextUpcoming: AppointmentSummary? {
        upcomingFiltered.first
    }

    private var currentList: [AppointmentSummary] {
        switch tab {
        case .upcoming: return upcomingFiltered
        case .past: return pastFiltered
        case .cancelled: return cancelledAppointments
        }
    }

    private var currentListFiltered: [AppointmentSummary] {
        if tab == .upcoming, let next = nextUpcoming {
            return currentList.filter { $0.id != next.id }
        }
        return currentList
    }

    private var emptyStateMessage: String {
        switch tab {
        case .upcoming: return "Aucun rendez-vous à venir."
        case .past: return "Aucun rendez-vous passé."
        case .cancelled: return "Aucun rendez-vous annulé."
        }
    }

    var body: some View {
        List {
            if let error = viewModel.error {
                Section { Text(error).foregroundStyle(.red) }
            }

            Section {
                Picker("Afficher", selection: $tab) {
                    ForEach(TabFilter.allCases) { f in
                        Text(f.label).tag(f)
                    }
                }
                .pickerStyle(.segmented)
                .accessibilityLabel("Filtre des rendez-vous")
                .accessibilityValue(tab.label)
                .accessibilityHint("Sélectionnez pour filtrer la liste des rendez-vous")

                if tab == .upcoming, let next = nextUpcoming {
                    AppointmentCard(
                        appointment: next,
                        accentColor: .blue.opacity(0.15)
                    ) {
                        AppointmentDetailScreen(appointment: next, viewModel: viewModel)
                    }
                }
            }

            Section {
                if currentListFiltered.isEmpty {
                    // If we are in "À venir" and we already show the next upcoming above,
                    // do not show the empty state message.
                    if !(tab == .upcoming && nextUpcoming != nil) {
                        AppointmentEmptyState(
                            icon: "calendar.badge.exclamationmark",
                            message: emptyStateMessage
                        )
                        .frame(maxWidth: .infinity)
                        .listRowInsets(EdgeInsets())
                        .listRowSeparator(.hidden)

                        if tab == .upcoming {
                            NavigationLink {
                                AppointmentBookingView(api: api)
                            } label: {
                                Label("Prendre rendez-vous", systemImage: "calendar.badge.plus")
                                    .fontWeight(.semibold)
                            }
                        }
                    }
                } else {
                    ForEach(currentListFiltered) { appointment in
                        AppointmentCard(appointment: appointment) {
                            AppointmentDetailScreen(appointment: appointment, viewModel: viewModel)
                        }
                        .listRowInsets(EdgeInsets())
                        .listRowSeparator(.hidden)
                    }
                }
            }
        }
        .navigationTitle("Mes rendez-vous")
        .task { await viewModel.load(force: true) }
        .refreshable { await viewModel.load(force: true) }
        .overlay(alignment: Alignment.top) {
            if let msg = viewModel.successMessage {
                Text(msg)
                    .font(.subheadline)
                    .padding(.horizontal, 12)
                    .padding(.vertical, 8)
                    .background(Color.green.opacity(0.9))
                    .foregroundStyle(.white)
                    .clipShape(Capsule())
                    .padding(.top, 8)
                    .transition(.move(edge: .top).combined(with: .opacity))
                    .accessibilityLabel(msg)
                    .accessibilityHidden(false)
                    .onAppear {
#if canImport(UIKit)
                        UIAccessibility.post(notification: .announcement, argument: msg)
#endif
                        DispatchQueue.main.asyncAfter(deadline: .now() + 2.0) {
                            if viewModel.successMessage == msg { viewModel.successMessage = nil }
                        }
                    }
            }
        }
        .environment(\.locale, Locale(identifier: "fr_FR"))
    }
}

private struct AppointmentRow: View {
    let appointment: AppointmentSummary
    var accentColor: Color = Color.gray.opacity(0.12)

    private var accessibilitySummary: String {
        var parts: [String] = []
        parts.append("Rendez-vous")
        parts.append(appointment.prestation.name)
        let dateText = spokenDayFormatter.string(from: appointment.startAt)
        parts.append("le \(dateText)")
        parts.append("de \(timeFormatter.string(from: appointment.startAt)) à \(timeFormatter.string(from: appointment.endAt))")
        if let status = appointment.status, !status.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            parts.append("Statut: \(status.capitalized)")
        }
        if appointment.canCancel {
            parts.append("Annulable")
        }
        return parts.joined(separator: ", ")
    }

    private var timeRange: String {
        "\(timeFormatter.string(from: appointment.startAt)) - \(timeFormatter.string(from: appointment.endAt))"
    }

	private var statusStyle: (text: String, color: Color) {
		guard let status = appointment.status else { return ("-", .gray) }
		let normalized = status.lowercased()
		if normalized.contains("annul") || normalized.contains("cancel") { return (status.capitalized, .red) }
		if normalized.contains("conf") { return (status.capitalized, .green) }
		if normalized.contains("att") || normalized.contains("pend") { return (status.capitalized, .orange) }
		return (status.capitalized, .gray)
	}

	var body: some View { rowBase }

	private var rowBase: some View {
		HStack(alignment: .center, spacing: 12) {
			DateBadge(date: appointment.startAt)
			VStack(alignment: .leading, spacing: 6) {
				HStack(alignment: .center) {
                    Text(appointment.prestation.name)
                        .fontWeight(.semibold)
                        .lineLimit(1)
                    Spacer()
                    if appointment.status != nil {
                        Text(statusStyle.text)
                            .font(.caption2)
                            .padding(.horizontal, 8)
                            .padding(.vertical, 4)
                            .background(statusStyle.color.opacity(0.12))
                            .foregroundColor(statusStyle.color)
                            .clipShape(Capsule())
                    }
                }
                Text(dayFormatter.string(from: appointment.startAt))
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                Text(timeRange)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                if appointment.canCancel {
                    Label("Annulable", systemImage: "checkmark.shield")
                        .font(.caption2)
                        .padding(.horizontal, 6)
                        .padding(.vertical, 3)
                        .background(Color.orange.opacity(0.15))
                        .foregroundColor(.orange)
                        .clipShape(Capsule())
                }
            }
		}
		.padding(.vertical, 8)
		.padding(.horizontal, 4)
		.background(RoundedRectangle(cornerRadius: 12).fill(accentColor))
		.accessibilityElement(children: .ignore)
		.accessibilityLabel(Text(accessibilitySummary))
		.accessibilityHint("Touchez pour ouvrir les détails.")
	}
}

private struct AppointmentCard<Destination: View>: View {
    let appointment: AppointmentSummary
    var accentColor: Color = Color.gray.opacity(0.08)
    @ViewBuilder var destination: () -> Destination

    var body: some View {
        NavigationLink {
            destination()
        } label: {
            AppointmentRow(
                appointment: appointment,
                accentColor: accentColor
            )
        }
        .buttonStyle(.plain)
        .accessibilityHint("Ouvrir les détails du rendez-vous")
    }
}

private struct AppointmentSummaryHeader: View {
    let upcomingCount: Int
    let pastCount: Int
    let cancelledCount: Int
    var isLoading: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack(spacing: 10) {
                SummaryPill(
                    title: "À venir",
                    value: upcomingCount,
                    color: .blue
                )
                SummaryPill(
                    title: "Passés",
                    value: pastCount,
                    color: .gray
                )
                SummaryPill(
                    title: "Annulés",
                    value: cancelledCount,
                    color: .red
                )
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            HStack(spacing: 6) {
                Image(systemName: isLoading ? "clock.arrow.2.circlepath" : "arrow.clockwise")
                    .foregroundStyle(.secondary)
                Text(isLoading ? "Mise à jour..." : "Glissez vers le bas pour rafraîchir")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
        }
        .padding(12)
        .background(RoundedRectangle(cornerRadius: 12).fill(Color.gray.opacity(0.08)))
        .listRowInsets(EdgeInsets())
        .listRowSeparator(.hidden)
        .accessibilityElement(children: .ignore)
        .accessibilityLabel("Résumé: \(upcomingCount) à venir, \(pastCount) passés, \(cancelledCount) annulés. \(isLoading ? "Mise à jour en cours" : "Tirez pour rafraîchir").")
    }

    private struct SummaryPill: View {
        let title: String
        let value: Int
        let color: Color

        var body: some View {
            VStack(alignment: .leading, spacing: 4) {
                Text(title)
                    .font(.caption)
                    .foregroundStyle(.secondary)
                HStack(spacing: 4) {
                    Circle().fill(color).frame(width: 8, height: 8)
                    Text("\(value)")
                        .fontWeight(.semibold)
                }
            }
            .padding(.vertical, 8)
            .padding(.horizontal, 10)
            .background(RoundedRectangle(cornerRadius: 12).fill(color.opacity(0.08)))
        }
    }
}

private struct AppointmentEmptyState: View {
    let icon: String
    let message: String
    var action: (() async -> Void)?

    var body: some View {
        VStack(spacing: 10) {
            Image(systemName: icon)
                .font(.largeTitle)
                .foregroundStyle(.secondary)
                .accessibilityHidden(true)
            Text(message)
                .multilineTextAlignment(.center)
                .foregroundStyle(.secondary)
            if let action {
                Button {
                    Task { await action() }
                } label: {
                    Label("Actualiser", systemImage: "arrow.clockwise")
                }
                .buttonStyle(.bordered)
                .accessibilityHint("Actualiser la liste des rendez-vous")
            }
        }
        .padding(.vertical, 12)
    }
}

private struct DateBadge: View {
    let date: Date

    private var day: String { dayFormatter.string(from: date) }
    private var hour: String { timeFormatter.string(from: date) }

    var body: some View {
        VStack(spacing: 4) {
            Text(day)
                .font(.caption)
                .fontWeight(.semibold)
            Text(hour)
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .padding(10)
        .background(RoundedRectangle(cornerRadius: 10).fill(Color.blue.opacity(0.1)))
        .accessibilityHidden(true)
    }
}

	private struct AppointmentDetailScreen: View {
	    let appointment: AppointmentSummary
	    @ObservedObject var viewModel: MyAppointmentsViewModel
	    @State private var isCancelling = false
	    @State private var showCancelAlert = false
	    @Environment(\.dismiss) private var dismiss

    private var timeRange: String {
        "\(timeFormatter.string(from: appointment.startAt)) - \(timeFormatter.string(from: appointment.endAt))"
    }

	    var body: some View {
	        Form {
	            if let error = viewModel.error, !error.isEmpty {
	                Section { Text(error).foregroundStyle(.red) }
	            }

	            Section("Détails") {
	                LabeledContent("Prestation") { Text(appointment.prestation.name) }
	                LabeledContent("Date") { Text(dayFormatter.string(from: appointment.startAt)) }
	                LabeledContent("Heure") { Text(timeRange) }
                if let status = appointment.status {
                    LabeledContent("Statut") { Text(status.capitalized) }
                }
            }
            if appointment.canCancel {
                Section("Actions") {
                    Button(role: .destructive) {
                        showCancelAlert = true
                    } label: {
                        if isCancelling {
                            ProgressView().frame(maxWidth: .infinity)
                        } else {
                            Text("Annuler le rendez-vous").frame(maxWidth: .infinity)
                        }
                    }
                    .disabled(isCancelling)
                    .accessibilityLabel("Annuler ce rendez-vous")
                    .accessibilityHint("Annule ce rendez-vous et revient à la liste")
                }
            }
        }
        .navigationTitle("Rendez-vous")
        .navigationBarTitleDisplayMode(.inline)
	        .alert(
	            "Annuler ce rendez-vous ?",
	            isPresented: $showCancelAlert
	        ) {
	            Button("Retour", role: .cancel) {
	                showCancelAlert = false
	            }
	            Button("Confirmer l’annulation", role: .destructive) {
	                guard !isCancelling else { return }
	                isCancelling = true
	                showCancelAlert = false
	                Task {
	                    let ok = await viewModel.cancel(appointmentID: appointment.id)
#if canImport(UIKit)
	                    UINotificationFeedbackGenerator().notificationOccurred(ok ? .success : .error)
#endif
	                    isCancelling = false
	                    if ok {
	                        dismiss()
	                    }
	                }
	            }
	        } message: {
	            Text("Cette action est irréversible. Le rendez-vous sera marqué comme annulé.")
	        }
	}
}

	private let timeFormatter: DateFormatter = {
	    let formatter = DateFormatter()
	    formatter.locale = Locale(identifier: "fr_FR")
    formatter.timeStyle = .short
    formatter.dateStyle = .none
    return formatter
}()

private let dayFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateFormat = "dd/MM/yyyy"
    return formatter
}()

private let spokenDayFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .full
    formatter.timeStyle = .none
    return formatter
}()

#if canImport(UIKit)
private struct LocalizedDatePicker: UIViewRepresentable {
    @Binding var date: Date
    var displayedComponents: DatePickerComponents = [.date]
    var locale: Locale = Locale(identifier: "fr_FR")
    var calendar: Calendar = Calendar(identifier: .gregorian)
    var minimumDate: Date? = nil
    var maximumDate: Date? = nil
    var style: UIDatePickerStyle = .compact

    func makeUIView(context: Context) -> UIDatePicker {
        let picker = UIDatePicker()
        picker.datePickerMode = mode
        picker.preferredDatePickerStyle = style
        picker.locale = locale
        picker.calendar = calendar
        picker.date = date
        picker.minimumDate = minimumDate
        picker.maximumDate = maximumDate
        picker.addTarget(context.coordinator, action: #selector(Coordinator.changed(_:)), for: .valueChanged)
        return picker
    }

    func updateUIView(_ picker: UIDatePicker, context: Context) {
        if picker.date != date { picker.setDate(date, animated: false) }
        if picker.locale != locale { picker.locale = locale }
        if picker.calendar != calendar { picker.calendar = calendar }
        if picker.preferredDatePickerStyle != style { picker.preferredDatePickerStyle = style }
        picker.minimumDate = minimumDate
        picker.maximumDate = maximumDate
    }

    func makeCoordinator() -> Coordinator { Coordinator(self) }

    private var mode: UIDatePicker.Mode {
        switch (displayedComponents.contains(.date), displayedComponents.contains(.hourAndMinute)) {
        case (true, true): return .dateAndTime
        case (true, false): return .date
        case (false, true): return .time
        default: return .date
        }
    }

    class Coordinator: NSObject {
        var parent: LocalizedDatePicker
        init(_ parent: LocalizedDatePicker) { self.parent = parent }
        @objc func changed(_ sender: UIDatePicker) {
            parent.date = sender.date
        }
    }
}
#endif

private struct NumericDatePicker: View {
    @Binding var date: Date
    var minimumDate: Date = Calendar(identifier: .gregorian).startOfDay(for: Date())
    var maximumDate: Date? = nil
    var calendar: Calendar = Calendar(identifier: .gregorian)

    @State private var day: Int = 1
    @State private var month: Int = 1
    @State private var year: Int = Calendar(identifier: .gregorian).component(.year, from: Date())

    private var minYear: Int { calendar.component(.year, from: minimumDate) }
    private var minMonth: Int { calendar.component(.month, from: minimumDate) }
    private var minDay: Int { calendar.component(.day, from: minimumDate) }

    private var currentYear: Int { calendar.component(.year, from: Date()) }
    private var maxYear: Int {
        if let maximumDate { return calendar.component(.year, from: maximumDate) }
        return currentYear + 10
    }

    private var yearRange: ClosedRange<Int> { minYear...maxYear }

    private var monthStart: Int { year == minYear ? minMonth : 1 }
    private var monthEnd: Int { 12 }

    private var dayStart: Int { (year == minYear && month == minMonth) ? minDay : 1 }
    private var dayEnd: Int {
        var comps = DateComponents()
        comps.year = year
        comps.month = month
        let date = calendar.date(from: comps) ?? Date()
        return calendar.range(of: .day, in: .month, for: date)?.count ?? 31
    }

    var body: some View {
        HStack(spacing: 8) {
            Picker("Jour", selection: $day) {
                ForEach(Array(dayStart...dayEnd), id: \.self) { d in
                    Text(String(format: "%02d", d)).tag(d)
                }
            }
            .pickerStyle(.wheel)

            Text("/")
                .foregroundStyle(.secondary)

            Picker("Mois", selection: $month) {
                ForEach(Array(monthStart...monthEnd), id: \.self) { m in
                    Text(String(format: "%02d", m)).tag(m)
                }
            }
            .pickerStyle(.wheel)

            Text("/")
                .foregroundStyle(.secondary)

            Picker("Année", selection: $year) {
                ForEach(Array(yearRange), id: \.self) { y in
                    Text(String(format: "%04d", y)).tag(y)
                }
            }
            .pickerStyle(.wheel)
        }
        .frame(maxWidth: .infinity)
        .onAppear {
            // Ensure initial date respects minimum
            if date < minimumDate {
                date = minimumDate
            }
            syncFromDate()
            clampComponentsToMinimum()
            syncToDate()
        }
        .onChangeCompat(date) { newValue in
            if newValue < minimumDate {
                date = minimumDate
            }
            syncFromDate()
            clampComponentsToMinimum()
        }
        .onChangeCompat(year) { _ in
            clampComponentsToMinimum()
            syncToDate()
        }
        .onChangeCompat(month) { _ in
            clampComponentsToMinimum()
            syncToDate()
        }
        .onChangeCompat(day) { _ in
            clampComponentsToMinimum()
            syncToDate()
        }
    }

    private func clampComponentsToMinimum() {
        if year < minYear { year = minYear }
        if year == minYear && month < minMonth { month = minMonth }
        if year == minYear && month == minMonth && day < minDay { day = minDay }
        if day > dayEnd { day = dayEnd }
    }

    private func syncFromDate() {
        let cal = calendar
        day = cal.component(.day, from: date)
        month = cal.component(.month, from: date)
        year = cal.component(.year, from: date)
    }

    private func syncToDate() {
        var comps = DateComponents()
        comps.calendar = calendar
        comps.year = year
        comps.month = month
        comps.day = day
        if let newDate = comps.date {
            if let maximumDate, newDate > maximumDate {
                date = maximumDate
                syncFromDate()
                return
            }
            if newDate < minimumDate {
                date = minimumDate
                syncFromDate()
                return
            }
            if newDate != date {
                date = newDate
            }
        }
    }
}

private struct AppointmentConfirmationView: View {
    @ObservedObject var viewModel: AppointmentBookingViewModel
    let slot: AppointmentSlot
    @EnvironmentObject private var account: AccountViewModel
    @Environment(\.dismiss) private var dismiss
    @State private var isConfirming = false

    private var selectedPrestation: AppointmentPrestation? {
        viewModel.prestations.first(where: { $0.id == viewModel.selectedPrestationId })
    }

    var body: some View {
        Form {
            Section {
                if let p = selectedPrestation {
                    LabeledContent("Prestation") { Text(p.name) }
                    LabeledContent("Durée") { Text("\(p.durationMinutes) min") }
                    LabeledContent("Prix") { Text(PriceFormatter.format(cents: p.priceCents)) }
                }
                LabeledContent("Date") { Text(dayFormatter.string(from: slot.startAt)) }
                LabeledContent("Heure") { Text("\(timeFormatter.string(from: slot.startAt)) - \(timeFormatter.string(from: slot.endAt))") }
            }

            if let message = viewModel.bookingMessage {
                Section {
                    Label(message, systemImage: "checkmark.seal.fill")
                        .foregroundStyle(.green)
                }
            }

            if !account.isLoggedIn {
                Section {
                    Text("Connectez-vous pour valider.")
                        .foregroundStyle(.secondary)
                }
            }

            Section {
                Button {
                    Task {
                        guard !isConfirming else { return }
                        isConfirming = true
                        let result = await viewModel.book(slot: slot)
                        isConfirming = false
                        if result != nil {
#if canImport(UIKit)
                            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
                            // Laisse le temps d'afficher le message de succès avant de revenir
                            try? await Task.sleep(nanoseconds: 1_000_000_000)
                            dismiss()
                        }
                    }
                } label: {
                    if isConfirming {
                        ProgressView()
                            .frame(maxWidth: .infinity)
                    } else {
                        Text("Valider")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .disabled(!account.isLoggedIn || isConfirming)
            }
        }
        .navigationTitle("Récapitulatif")
        .navigationBarTitleDisplayMode(.inline)
    }
}
