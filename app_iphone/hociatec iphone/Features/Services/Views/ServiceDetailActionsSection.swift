import SwiftUI

struct ServiceDetailActionsSection: View {
    let container: AppContainer

    var body: some View {
        Section("Actions") {
            NavigationLink {
                QuoteRequestView(viewModel: container.makeQuoteViewModel())
            } label: {
                Label("Demander un devis", systemImage: "doc.badge.plus")
            }

            NavigationLink {
                AppointmentBookingView(service: container.services.appointments)
            } label: {
                Label("Prendre rendez-vous", systemImage: "calendar.badge.plus")
            }
        }
    }
}
