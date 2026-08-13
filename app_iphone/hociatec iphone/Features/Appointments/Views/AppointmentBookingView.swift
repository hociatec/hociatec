import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct AppointmentBookingView: View {
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var viewModel: AppointmentBookingViewModel
    @Environment(\.dismiss) private var dismiss
    @State private var startDate = Date()

    init(service: AppointmentServing) {
        _viewModel = StateObject(wrappedValue: AppointmentBookingViewModel(service: service))
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
                    if let selected = selectedPrestation {
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
                                            if let selected = selectedPrestation {
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

    private var selectedPrestation: AppointmentPrestation? {
        viewModel.prestations.first(where: { $0.id == viewModel.selectedPrestationId })
    }

    private var slotsByDay: [Date: [AppointmentSlot]] {
        let calendar = Calendar(identifier: .gregorian)
        let startLocal = calendar.startOfDay(for: startDate)
        let filtered = viewModel.slots.filter { calendar.startOfDay(for: $0.startAt) >= startLocal }
        return Dictionary(grouping: filtered) { slot in
            calendar.startOfDay(for: slot.startAt)
        }
    }

    private var sortedDays: [Date] {
        slotsByDay.keys.sorted()
    }

    private func timeRange(for slot: AppointmentSlot) -> String {
        "\(timeFormatter.string(from: slot.startAt)) - \(timeFormatter.string(from: slot.endAt))"
    }
}
