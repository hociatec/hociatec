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
