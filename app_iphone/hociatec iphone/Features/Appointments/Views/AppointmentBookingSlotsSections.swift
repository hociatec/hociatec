import SwiftUI

struct AppointmentBookingSlotsSection: View {
    let slots: [AppointmentSlot]
    let selectedDate: Date?
    let selectedPrestation: AppointmentPrestation?
    let isLoading: Bool
    let error: String?
    let viewModel: AppointmentBookingViewModel
    let onBack: () -> Void

    var body: some View {
        Section {
            if isLoading && slots.isEmpty {
                ProgressView("Recherche des créneaux...")
            } else if let error, !error.isEmpty {
                Text(error)
                    .foregroundStyle(.red)
            } else if slots.isEmpty {
                Text("Aucun créneau disponible pour le jour sélectionné.")
                    .foregroundStyle(.secondary)
            } else {
                if let selectedDate {
                    Text(spokenDayFormatter.string(from: selectedDate).capitalized)
                        .font(.headline)
                }

                ForEach(slots) { slot in
                    NavigationLink {
                        AppointmentConfirmationView(viewModel: viewModel, slot: slot)
                    } label: {
                        AppointmentBookingSlotRow(slot: slot, selectedPrestation: selectedPrestation)
                    }
                }

                Button("Revenir au calendrier") {
                    onBack()
                }
                .buttonStyle(.bordered)
                .padding(.top, 8)
            }
        }
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
