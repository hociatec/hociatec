import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct AppointmentDetailScreen: View {
    let appointment: AppointmentSummary
    @ObservedObject var viewModel: MyAppointmentsViewModel
    @State private var isCancelling = false
    @State private var showCancelAlert = false
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        Form {
            AppointmentErrorSection(error: viewModel.error)
            AppointmentDetailsSection(appointment: appointment)
            AppointmentCancelSection(
                canCancel: appointment.canCancel,
                isCancelling: isCancelling
            ) {
                showCancelAlert = true
            }
        }
        .navigationTitle("Rendez-vous")
        .navigationBarTitleDisplayMode(.inline)
        .alert("Annuler ce rendez-vous ?", isPresented: $showCancelAlert) {
            Button("Retour", role: .cancel) {
                showCancelAlert = false
            }
            Button("Confirmer l’annulation", role: .destructive) {
                guard !isCancelling else { return }
                isCancelling = true
                showCancelAlert = false
                Task {
                    let success = await viewModel.cancel(appointmentID: appointment.id)
#if canImport(UIKit)
                    UINotificationFeedbackGenerator().notificationOccurred(success ? .success : .error)
#endif
                    isCancelling = false
                    if success {
                        dismiss()
                    }
                }
            }
        } message: {
            Text("Cette action est irréversible. Le rendez-vous sera marqué comme annulé.")
        }
    }
}

struct AppointmentConfirmationView: View {
    @ObservedObject var viewModel: AppointmentBookingViewModel
    let slot: AppointmentSlot
    @EnvironmentObject private var account: AccountViewModel
    @EnvironmentObject private var navigation: AppNavigationState
    @Environment(\.dismiss) private var dismiss
    @State private var isConfirming = false

    var body: some View {
        Form {
            AppointmentConfirmationSummarySection(
                prestation: selectedPrestation,
                slot: slot
            )
            AppointmentBookingMessageSection(message: viewModel.bookingMessage)
            AppointmentLoginNoticeSection(isLoggedIn: account.isLoggedIn)
            AppointmentConfirmActionSection(
                isLoggedIn: account.isLoggedIn,
                isConfirming: isConfirming,
                onRequireLogin: {
                    navigation.showTab(4)
                    dismiss()
                }
            ) {
                await confirmAppointment()
            }
        }
        .navigationTitle("Récapitulatif")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var selectedPrestation: AppointmentPrestation? {
        AppointmentBookingPresentation.selectedPrestation(
            prestations: viewModel.prestations,
            selectedPrestationId: viewModel.selectedPrestationId
        )
    }

    private func confirmAppointment() async {
        guard !isConfirming else { return }
        isConfirming = true
        let result = await viewModel.book(slot: slot)
        isConfirming = false
        if result != nil {
#if canImport(UIKit)
            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
        }
    }
}
