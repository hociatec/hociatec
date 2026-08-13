import SwiftUI

struct AppointmentBookingGuestNoticeSection: View {
    let isLoggedIn: Bool

    var body: some View {
        if !isLoggedIn {
            Section {
                Text("Vous pouvez choisir une prestation et un créneau sans compte. La connexion est requise seulement pour confirmer.")
                    .foregroundStyle(.secondary)
            }
        }
    }
}

struct AppointmentBookingPrestationSection: View {
    let prestations: [AppointmentPrestation]
    @Binding var selectedPrestationId: Int?
    let selectedPrestation: AppointmentPrestation?
    let isLoading: Bool

    var body: some View {
        Section {
            if prestations.isEmpty && isLoading {
                ProgressView("Chargement des prestations...")
            } else if prestations.isEmpty {
                Text("Aucune prestation disponible pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                Picker("Prestation", selection: $selectedPrestationId) {
                    ForEach(prestations) { prestation in
                        Text(prestation.name)
                            .tag(Optional(prestation.id))
                    }
                }

                if let selectedPrestation {
                    HStack {
                        Label("\(selectedPrestation.durationMinutes) min", systemImage: "clock")
                            .foregroundStyle(.secondary)
                        Spacer()
                        Text(PriceFormatter.format(cents: selectedPrestation.priceCents))
                            .fontWeight(.semibold)
                    }
                }
            }
        }
    }
}

struct AppointmentBookingStartDateSection: View {
    @Binding var startDate: Date

    var body: some View {
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
    }
}

struct AppointmentBookingSlotsSection: View {
    let slotsByDay: [Date: [AppointmentSlot]]
    let sortedDays: [Date]
    let selectedPrestation: AppointmentPrestation?
    let isLoading: Bool
    let error: String?
    let viewModel: AppointmentBookingViewModel

    var body: some View {
        Section {
            if isLoading && slotsByDay.isEmpty {
                ProgressView("Recherche des créneaux...")
            } else if let error, !error.isEmpty {
                Text(error)
                    .foregroundStyle(.red)
            } else if slotsByDay.isEmpty {
                Text("Aucun créneau disponible sur la période.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(sortedDays, id: \.self) { day in
                    AppointmentBookingDaySection(
                        day: day,
                        slots: slotsByDay[day] ?? [],
                        selectedPrestation: selectedPrestation,
                        viewModel: viewModel
                    )
                }
            }
        }
    }
}

private struct AppointmentBookingDaySection: View {
    let day: Date
    let slots: [AppointmentSlot]
    let selectedPrestation: AppointmentPrestation?
    let viewModel: AppointmentBookingViewModel

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(dayFormatter.string(from: day))
                .font(.headline)

            ForEach(slots) { slot in
                NavigationLink {
                    AppointmentConfirmationView(viewModel: viewModel, slot: slot)
                } label: {
                    AppointmentBookingSlotRow(slot: slot, selectedPrestation: selectedPrestation)
                }
            }
        }
        .padding(.vertical, 6)
    }
}

private struct AppointmentBookingSlotRow: View {
    let slot: AppointmentSlot
    let selectedPrestation: AppointmentPrestation?

    var body: some View {
        HStack {
            VStack(alignment: .leading) {
                Text(AppointmentBookingPresentation.timeRange(for: slot))
                    .fontWeight(.semibold)

                if let selectedPrestation {
                    Text(PriceFormatter.format(cents: selectedPrestation.priceCents))
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

struct AppointmentBookingSuccessSection: View {
    let successMessage: String?

    var body: some View {
        if let successMessage, !successMessage.isEmpty {
            Section {
                Label(successMessage, systemImage: "checkmark.seal.fill")
                    .foregroundStyle(.green)
            }
        }
    }
}
