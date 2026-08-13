import SwiftUI

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
