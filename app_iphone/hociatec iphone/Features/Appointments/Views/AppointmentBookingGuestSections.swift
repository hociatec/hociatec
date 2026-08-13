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
