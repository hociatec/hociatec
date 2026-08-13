import SwiftUI

struct AppointmentCard<Destination: View>: View {
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
