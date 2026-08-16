import SwiftUI

struct AppointmentCard<Destination: View>: View {
    let appointment: AppointmentSummary
    var accentColor: Color = Color.gray.opacity(0.08)
    @ViewBuilder var destination: () -> Destination

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            AppointmentRow(
                appointment: appointment,
                accentColor: accentColor
            )
            NavigationLink {
                destination()
            } label: {
                Label("Voir le rendez-vous", systemImage: "arrow.right.circle")
                    .font(.footnote.weight(.semibold))
            }
            .buttonStyle(.borderless)
        }
        .accessibilityHint("Ouvrir les détails du rendez-vous")
    }
}
