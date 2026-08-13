import SwiftUI

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
