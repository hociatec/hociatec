import SwiftUI

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
